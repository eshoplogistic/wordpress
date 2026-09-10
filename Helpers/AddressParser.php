<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Разбор произвольных строк адреса (поля "Адрес"/"Квартира, офис и т.п." WooCommerce)
 * на улицу/дом/квартиру/район. Покупатели пишут туда что угодно, в любом порядке,
 * с запятыми или без, поэтому парсер работает по принципу "нашли уверенный признак —
 * заполнили поле, не нашли — оставили пустым", а не пытается угадать во что бы то ни стало.
 */
class AddressParser
{
    // "д." - частая аббревиатура и для "дом", и для "деревня", поэтому проверяется
    // только как отдельное слово (с границей \p{L} после), а не как часть другого слова.
    private const LABEL_GROUPS = array(
        'district' => '(?:р-?н\.?|район)',
        'room'     => '(?:кв\.?|квартира|оф\.?|офис|пом\.?|помещение|apt\.?|apartment)',
        'extra'    => '(?:корп\.?|корпус|стр\.?|строение|литер|лит\.?)',
        'building' => '(?:д\.?|дом|building)',
        'street'   => '(?:ул\.?|улица|пр-?кт\.?|пр-?т\.?|проспект|пер\.?|переулок|ш\.?|шоссе|б-?р\.?|бульвар|наб\.?|набережная|пр-?д\.?|проезд|пл\.?|площадь|аллея|туп\.?|тупик|тракт|кв-?л\.?|квартал|линия)',
    );

    // Административные/населённо-пунктовые пометки, иногда попадающие в поле "Адрес"
    // целиком вместе с городом ("г. Тверь") или указывающие на микрорайон, а не на саму
    // улицу ("мкр. Северный, ул. Мира, 1"). Такие куски пропускаем, а не угадываем в них улицу.
    private const LOCATION_PREFIX = '(?:г\.?|город|обл\.?|область|респ\.?|республика|край|пос\.?|посёлок|поселок|рп\.?|дер\.?|деревня|село|ст-ца|станица|аул|нп\.?|мкр\.?|микрорайон)';

    private const HOUSE_NUMBER = '\d+[a-zа-яё]?(?:[\/\-]\d+[a-zа-яё]?)?(?:\s*(?:к|корп\.?|с|стр\.?)\s*\d+)?';

    public static function parse(string $address1, string $address2 = '', string $knownCity = '', string $knownRegion = ''): array
    {
        $result = array(
            'street' => '',
            'building' => '',
            'room' => '',
            'district' => '',
        );

        $text = trim($address1 . "\n" . $address2);
        if ($text === '') {
            return $result;
        }

        $labelMatches = self::findLabelMatches($text);

        if (!$labelMatches) {
            foreach (self::dropKnownLocation(self::splitSegments($text), $knownCity, $knownRegion) as $segment) {
                self::resolveUnlabeled($segment, $result);
            }

            return self::trimResult($result);
        }

        $leading = trim(substr($text, 0, $labelMatches[0]['start']));
        $leadingSegments = self::dropKnownLocation(self::splitSegments($leading), $knownCity, $knownRegion);
        foreach ($leadingSegments as $segment) {
            self::resolveUnlabeled($segment, $result);
        }

        foreach ($labelMatches as $i => $match) {
            $end = isset($labelMatches[$i + 1]) ? $labelMatches[$i + 1]['start'] : strlen($text);
            $span = substr($text, $match['valueStart'], $end - $match['valueStart']);
            list($value, $tail) = self::splitHeadTail($span);

            if ($value !== '') {
                switch ($match['type']) {
                    case 'district':
                        if ($result['district'] === '') {
                            $result['district'] = $value;
                        }
                        break;
                    case 'room':
                        if ($result['room'] === '') {
                            $result['room'] = $value;
                        }
                        break;
                    case 'building':
                    case 'extra':
                        $result['building'] = self::appendBuilding($result['building'], $value);
                        break;
                    case 'street':
                        list($name, $house) = self::splitTrailingHouseNumber($value);
                        if ($result['street'] === '') {
                            $result['street'] = $name;
                        }
                        if ($house !== null && $result['building'] === '') {
                            $result['building'] = $house;
                        }
                        break;
                }
            }

            // То, что осталось после первой запятой в этом же куске (например "10" в
            // "ул. Ленина, 10" без метки "д.") - не относится к текущей метке, но может
            // быть домом/районом и т.п. без метки, поэтому прогоняем через тот же разбор.
            if ($tail !== '') {
                foreach (self::dropKnownLocation(self::splitSegments($tail), $knownCity, $knownRegion) as $segment) {
                    self::resolveUnlabeled($segment, $result);
                }
            }
        }

        return self::trimResult($result);
    }

    private static function findLabelMatches(string $text): array
    {
        $pattern = '/';
        $parts = array();
        foreach (self::LABEL_GROUPS as $type => $group) {
            $parts[] = '(?P<' . $type . '>' . $group . ')(?![\p{L}])';
        }
        $pattern .= implode('|', $parts) . '/iu';

        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return array();
        }

        $result = array();
        foreach ($matches as $matchSet) {
            foreach (array_keys(self::LABEL_GROUPS) as $type) {
                if (isset($matchSet[$type]) && $matchSet[$type][1] !== -1) {
                    $result[] = array(
                        'type' => $type,
                        'start' => $matchSet[$type][1],
                        'valueStart' => $matchSet[$type][1] + strlen($matchSet[$type][0]),
                    );
                    break;
                }
            }
        }

        usort($result, static function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        return $result;
    }

    private static function splitHeadTail(string $span): array
    {
        $parts = preg_split('/[,;\n]/u', $span, 2);

        $head = trim($parts[0], " \t\n\r\0\x0B.");
        $tail = isset($parts[1]) ? trim($parts[1]) : '';

        return array($head, $tail);
    }

    private static function resolveUnlabeled(string $segment, array &$result): void
    {
        if ($segment === '') {
            return;
        }

        if (preg_match('/^' . self::LOCATION_PREFIX . '(?![\p{L}])\s*\S/iu', $segment)) {
            return;
        }

        list($name, $house) = self::splitTrailingHouseNumber($segment);

        if ($name !== '' && $house !== null) {
            if ($result['street'] === '') {
                $result['street'] = $name;
            }
            if ($result['building'] === '') {
                $result['building'] = $house;
            }
            return;
        }

        if (preg_match('/^' . self::HOUSE_NUMBER . '$/iu', $segment)) {
            if ($result['street'] !== '' && $result['building'] === '') {
                $result['building'] = $segment;
            }
            return;
        }

        // Ограничение по числу слов отсекает случайные свободные комментарии без адреса
        // ("просто текст без адреса вообще") - настоящие названия улиц короче.
        // str_word_count() тут не подходит - он не считает кириллицу словами.
        if ($result['street'] === '' && count(preg_split('/\s+/u', trim($segment))) <= 4
            && preg_match('/^[\p{L}][\p{L}\.\-\s]*$/u', $segment)
        ) {
            $result['street'] = $segment;
        }

        // Ничего не подошло (мусор вроде "12.15 555 ПРИМЕР ТЕСТА") - оставляем как есть,
        // не вставляем предположения в поля заявки.
    }

    private static function splitSegments(string $text): array
    {
        $parts = preg_split('/[,;\n]+/u', $text);
        if ($parts === false) {
            return array();
        }

        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, static function ($segment) {
            return $segment !== '';
        }));
    }

    private static function dropKnownLocation(array $segments, string $knownCity, string $knownRegion): array
    {
        $known = array_filter(array(
            self::normalizeLocation($knownCity),
            self::normalizeLocation($knownRegion),
        ), static function ($value) {
            return $value !== '';
        });

        if (!$known) {
            return $segments;
        }

        return array_values(array_filter($segments, static function ($segment) use ($known) {
            return !in_array(self::normalizeLocation($segment), $known, true);
        }));
    }

    private static function normalizeLocation(string $value): string
    {
        $value = preg_replace('/^' . self::LOCATION_PREFIX . '(?![\p{L}])\s*/iu', '', trim($value));

        return mb_strtolower(trim((string) $value));
    }

    private static function splitTrailingHouseNumber(string $text): array
    {
        $text = trim($text);

        if (!preg_match('/^(.+?)\s+(' . self::HOUSE_NUMBER . ')$/iu', $text, $matches)) {
            return array($text, null);
        }

        $name = trim($matches[1]);
        if ($name === '' || !preg_match('/\p{L}/u', $name)) {
            return array($text, null);
        }

        return array($name, $matches[2]);
    }

    private static function appendBuilding(string $existing, string $value): string
    {
        if ($existing === '') {
            return $value;
        }

        return $existing . ', ' . $value;
    }

    private static function trimResult(array $result): array
    {
        foreach ($result as $key => $value) {
            $result[$key] = trim($value);
        }

        return $result;
    }
}

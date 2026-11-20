<?php
use eshoplogistic\WCEshopLogistic\Helpers\SenderCities;
use eshoplogistic\WCEshopLogistic\Services\CalculationService;
use eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods\SdekDoor;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\Classes\Shipping\Base;
if (!defined('WP_CLI') || !WP_CLI) return;

function esl_check($ok, $message) { if (!$ok) throw new Exception($message); echo "PASS: $message\n"; }
$cities = [['fias'=>'city-a','name'=>'Москва','region'=>'Москва'],['fias'=>'city-b','name'=>'Тверь','region'=>'Тверская область']];
add_filter('pre_option_wc_esl_shipping_sender_cities', fn() => $cities);
esl_check(count(SenderCities::sanitize(array_merge($cities, [$cities[0], null, ['fias'=>'bad'], ['fias'=>[],'name'=>[]]]))) === 2, 'sanitize malformed and duplicate cities');
esl_check(SenderCities::getByFias('missing') === null && SenderCities::getByFias([]) === null, 'unknown and malformed origin falls back');
esl_check(isset(SenderCities::getOptions('removed')['removed']), 'removed city remains in profile options');
// Verify generated API payload without network or persistent changes.
$data = new class implements \eshoplogistic\WCEshopLogistic\Contracts\OrderDataInterface { public function getHash() {return 'test';} public function getItems() {return [];} };
$api = new class { public $requests=[]; public function calculateDelivery($service,$data) { $this->requests[]=$data; return new \eshoplogistic\WCEshopLogistic\Http\Response\CollectionResponse([]); } };
$service = new CalculationService();
$property = new ReflectionProperty($service, 'api'); $property->setValue($service,$api);
$service->calculate('sdek',$data,'','destination','card','','');
$service->calculate('sdek',$data,'city-a','destination','card','','');
esl_check(!array_key_exists('from',$api->requests[0]) && $api->requests[1]['from']==='city-a','API omits default origin and sends selected FIAS');
// Session is deliberately in memory; the live customer session is untouched.
WC()->session = new class extends WC_Session { public function init() {} public function save_data() {} };
$session = new SessionService();
$session->set('billing',['city'=>'Казань','fias'=>'destination','services'=>[]]);
add_filter('pre_option_wc_esl_shipping_api_key', fn()=>'test-key');
add_filter('pre_option_wc_esl_shipping_account_services', fn()=>['sdek'=>['name'=>'СДЭК']]);
foreach ([900001=>'city-a',900002=>'city-b',900003=>'removed'] as $id=>$from) {
    add_filter('pre_option_woocommerce_wc_esl_sdek_door_'.$id.'_settings', fn()=>['title'=>'Test '.$id,'sender_city'=>$from]);
    $origin = $from === 'removed' ? '' : $from;
    $hash = (new CheckoutOrderData([]))->getHash();
    $key = WC_ESL_PREFIX.md5($hash.'_test-key_destination_'.$origin.'__sdek');
    add_filter('pre_transient_'.$key, fn()=>['door'=>['price'=>['value'=>$id],'time'=>['value'=>1,'unit'=>'день']],'debug'=>[]]);
    $method = new SdekDoor($id);
    $rate = $method->calculate_shipping_basic(['contents'=>[]]);
    esl_check($rate['id']==='wc_esl_sdek_door:'.$id && $rate['cost']===$id, 'separate rate and cache for instance '.$id);
    $meta = json_decode($rate['meta_data']['esl_shipping_methods'],true);
    esl_check($meta['sender_fias']===$origin, 'order rate metadata preserves origin '.$id);
    $item = new WC_Order_Item_Shipping();
    $item->set_method_id('wc_esl_sdek_door'); $item->set_instance_id($id);
    $item->add_meta_data('esl_shipping_methods', $rate['meta_data']['esl_shipping_methods']);
    (new \eshoplogistic\WCEshopLogistic\Modules\OrderCreator())->saveOrderShipping($item);
    esl_check(json_decode($item->get_meta('esl_shipping_methods'),true)['sender_fias']===$origin, 'order saver preserves selected instance '.$id);
}
add_filter('pre_option_wc_esl_shipping_api_key_wcart', fn()=>'widget-key');
foreach (['city-a','city-b',''] as $from) {
    $cache = ['city'=>'destination','key'=>'widget-key','service'=>'sdek']; if ($from) $cache['from']=$from;
    $key = md5('widget/calculation'.json_encode($cache));
    add_filter('pre_transient_'.$key, fn()=>['debug'=>['origin'=>$from],'data'=>[]]);
    $result = Base::getFrameCalculationData(['key'=>'sdek','from'=>$from],['fias'=>'destination']);
    esl_check($result['debug']['origin']===$from && $result['sender_fias']===$from, 'widget cache isolated for origin '.($from ?: 'default'));
}
$helper = new \eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper();
esl_check($helper->getTypeMethod('wc_esl_sdek_terminal:900001') === 'terminal', 'terminal recognition supports instance rate IDs');
esl_check(Base::getFrameCalculationData(['key'=>'sdek','from'=>'removed'], ['fias'=>'destination']) === null, 'removed widget origin cannot reuse old calculation');
$handler = new \eshoplogistic\WCEshopLogistic\Blocks\Ajax\BlocksCheckoutHandler();
$changed = new ReflectionMethod($handler, 'hasFrameChanged');
esl_check($changed->invoke($handler, ['from'=>'city-a','price'=>['value'=>100]], ['from'=>'city-b','price'=>['value'=>100]]) === true, 'Blocks invalidates equal-price selections when origin changes');
echo "All checks passed. No API calls or persistent settings changes.\n";

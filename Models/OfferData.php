<?php

namespace eshoplogistic\WCEshopLogistic\Models;

use eshoplogistic\WCEshopLogistic\Contracts\OfferInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class OfferData implements OfferInterface
{
    /**
     * @var array $data
     */
    private $data;

    /**
     * @var WC_Product $product
     */
    private $product;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->product = $this->get('data');
    }

    public function getArticle()
    {
        $productId = $this->get('product_id');
        $variationId = $this->get('variation_id');

        if($variationId !== 0) return $variationId;

        return $productId;
    }

    public function getName()
    {
        return $this->product->get_title();
    }

    public function getQuantity()
    {
        return $this->get('quantity');
    }

    public function getTotal()
    {
        return $this->get('line_total');
    }

    public function getPrice()
    {
        return apply_filters('wc_esl_offer_data_price', $this->product->get_price());
    }

    public function getWeight()
    {
        return round(
            floatval(
                $this->prepareWeight($this->product->get_weight())
            ) * intval($this->get('quantity')),
            2
        );
    }

    public function getLength()
    {
        return round(
            floatval(
                $this->prepareDimensions($this->product->get_length())
            ) * intval($this->get('quantity')),
            2
        );
    }

    public function getWidth()
    {
        return round(
            floatval(
                $this->prepareDimensions($this->product->get_width())
            ) * intval($this->get('quantity')),
            2
        );
    }

    public function getHeight()
    {
        return round(
            floatval(
                $this->prepareDimensions($this->product->get_height())
            ) * intval($this->get('quantity')),
            2
        );
    }

    public function getDimensions()
    {
        return $this->getLength() . '*' . $this->getWidth() . '*' . $this->getHeight();
    }

    private function prepareWeight($weight)
    {
        switch (get_option('woocommerce_weight_unit')) {
            case 'kg':
                return $weight;
                break;

            case 'g':
                return $weight / 1000;
                break;

            case 'lbs':
                return $weight / 2.2046;
                break;

            case 'oz':
                return $weight / 35.274;
                break;
            
            default:
                return $weight;
                break;
        }
    }

    private function prepareDimensions($dimension)
    {
        switch (get_option('woocommerce_dimension_unit')) {
            case 'cm':
                return $dimension;
                break;

            case 'm':
                return $dimension * 100;
                break;

            case 'mm':
                return $dimension / 10;
                break;

            case 'in':
                return $dimension / 0.39370;
                break;

            case 'yd':
                return $dimension / 0.010936;
                break;
            
            default:
                return $dimension;
                break;
        }
    }

    private function get($key)
    {
        if(!isset($this->data[$key])) throw new \Exception(__("Значение с таким ключом не найдено", WC_ESL_DOMAIN));

        return $this->data[$key];
    }
}
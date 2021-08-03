<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

class ShippingHelper
{
    public function isEslMethod($methodId)
    {
        return (strpos($methodId, WC_ESL_PREFIX) !== false);
    }

    public function getTypeMethod($methodId)
    {
        if(!$this->isEslMethod($methodId)) return null;

        $idWithoutPrefix = explode(WC_ESL_PREFIX, $methodId)[1];
        
        return explode('_', $idWithoutPrefix)[1];
    }

    public function getSlugMethod($methodId)
    {
        if(!$this->isEslMethod($methodId)) return null;

        $idWithoutPrefix = explode(WC_ESL_PREFIX, $methodId)[1];
        
        return explode('_', $idWithoutPrefix)[0];
    }
}
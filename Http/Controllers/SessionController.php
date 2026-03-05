<?php

namespace eshoplogistic\WCEshopLogistic\Http\Controllers;

use eshoplogistic\WCEshopLogistic\Contracts\ResponseInterface;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\Http\Foundation\Controller;
use eshoplogistic\WCEshopLogistic\Http\Request;

if ( ! defined('ABSPATH') ) {
    exit;
}

class SessionController extends Controller
{
    /**
     * @var SessionService $options
     */
    private $session;

    public function __construct()
    {
        $this->session = new SessionService();
    }

    /**
     * @param Request $request
     * 
     * @return ResponseInterface
     */
    public function saveShippingAddress(Request $request) : ResponseInterface
    {
        try {
            $data = $request->get('data');
            $this->session->save($data);

            return $this->json([
                'success' => true,
                'session' => $this->session->getAll()
            ]);
        } catch(\Exception $e) {
            return $this->json([
                'success' => false,
                'session' => $this->session->getAll()
            ]);
        }
    }

    /**
     * Get current shipping/billing city data
     * 
     * @param Request $request
     * 
     * @return ResponseInterface
     */
    public function getShippingData(Request $request) : ResponseInterface
    {
        try {
            $mode = $request->get('mode', 'billing');
            
            error_log('🔍 [SessionController] getShippingData called with mode: ' . $mode);
            
            $city_data = $this->session->get($mode);
            
            error_log('📦 [SessionController] Retrieved city data: ' . json_encode($city_data));
            
            if (!$city_data) {
                error_log('⚠️ [SessionController] No city data found, returning empty');
                $city_data = [
                    'city' => '',
                    'fias' => '',
                    'services' => [],
                    'postcode' => '',
                    'region' => ''
                ];
            }

            error_log('✅ [SessionController] Returning city data: ' . json_encode($city_data));
            
            return $this->json([
                'success' => true,
                'data' => $city_data
            ]);
        } catch(\Exception $e) {
            error_log('❌ [SessionController] Error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
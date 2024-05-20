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
}
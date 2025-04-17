<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Http\Controllers\OrderController;
use eshoplogistic\WCEshopLogistic\Http\Controllers\WidgetController;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Routes implements ModuleInterface
{
    public function init()
    {
        add_action('rest_api_init', [$this, 'initRestRoutes']);
    }

    public function initRestRoutes()
    {
        register_rest_route( 'wc-esl/v1', '/order', array(
            'methods'  => 'POST',
            'callback' => [$this, 'createOrder'],
            'permission_callback' => '__return_true'
        ));

	    register_rest_route( 'wc-esl/v2', '/widget-data', array(
		    'methods'  => 'POST',
		    'callback' => [$this, 'widgetLogData'],
		    'permission_callback' => '__return_true'
	    ));
    }

    /**
     *
     * @param \WP_REST_Request $request.
     *
     * @return \WP_Error|array
     */
    public function createOrder(\WP_REST_Request $request)
    {
        try {

            $orderController = new OrderController();
            $response = $orderController->save($request);


            $response->send();

        } catch(\Exception $e) {
            $logger = new \WC_Logger();
            $logger->debug($e->getMessage());
        }
    }

	/**
	 *
	 * @param \WP_REST_Request $request.
	 *
	 * @return \WP_Error|array
	 */
	public function widgetLogData(\WP_REST_Request $request)
	{
		try {

			$widgetController = new widgetController();
			$response = $widgetController->process($request);
			$response->send();

		} catch(\Exception $e) {
			$logger = new \WC_Logger();
			$logger->debug($e->getMessage());
		}
	}
}
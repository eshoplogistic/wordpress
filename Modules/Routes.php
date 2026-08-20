<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;
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
            // Public: guest checkout has no WP user session to authorize against.
            // OrderController::save() applies its own layered protection instead (merchant
            // widget secret when configured, per-IP rate limiting, and server-derived product
            // pricing) -- see the comment at the top of that method for details.
            'permission_callback' => '__return_true'
        ));

	    register_rest_route( 'wc-esl/v2', '/widget-data', array(
		    'methods'  => 'POST',
		    'callback' => [$this, 'widgetLogData'],
		    // Public: called by the anonymous storefront widget. WidgetController::process()
		    // restricts it to the "widget/*" method namespace so it cannot be used to reach
		    // account/order-management API methods with the site's API key.
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
            EslLogger::debug( '[ESL createOrder] ' . $e->getMessage() );
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
			EslLogger::debug( '[ESL widgetLogData] ' . $e->getMessage() );
		}
	}
}
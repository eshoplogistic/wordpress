<?php

namespace eshoplogistic\WCEshopLogistic\Http\Response;

use eshoplogistic\WCEshopLogistic\Contracts\ApiResponseInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
    exit;
}

class ErrorResponse implements ApiResponseInterface, \JsonSerializable
{
    /**
     * @var array
     */
    public $errors;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var string
     */
	private $status;

    /**
     * @param array $response
     */
    public function __construct( $response )
    {
	    $optionsRepository = new OptionsRepository();
	    $moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');
		if($moduleVersion){
			$this->errors = $response['errors'] ?? '';
			$this->messages = $response['http_status_message'] ?? '';
			$this->status = $response['http_status'] ?? '';
		}else{
			$this->errors = $response['errors'] ?? '';
			$this->messages = $response['msg'] ?? '';
			$this->status = $response['status'] ?? '';
		}

    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return true;
    }

	/**
	 * @return array
	 */
	public function data(): array
	{
		return [];
	}

    public function jsonSerialize()
    {
        return [
            'success' => false,
            'data' => [
                'errors' => $this->errors,
                'messages' => $this->messages,
                'status' => $this->status
            ]
        ];
    }
}
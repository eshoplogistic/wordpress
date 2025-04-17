<?php
namespace eshoplogistic\WCEshopLogistic\Cron;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Modules\Unloading;

if ( ! defined( 'WPINC' ) ) {
	die;
}


class UnloadingCron
{

	public $addForm;

	public function init()
	{
		//wp_clear_scheduled_hook( 'esl_update_status_cron' );
		$optionsRepository = new OptionsRepository();
		$this->addForm = $optionsRepository->getOption('wc_esl_shipping_add_form');

		if(isset($this->addForm['cronStatusEnable'])){
			add_filter( 'cron_schedules', [$this, 'cron_esl_custom_min']);

			if( ! wp_next_scheduled( 'esl_update_status_cron' ) )
				wp_schedule_event( time(), 'esl_custom_min', 'esl_update_status_cron');

			add_action( 'esl_update_status_cron', [$this, 'updateStatus'] );
		}else{
			wp_clear_scheduled_hook( 'esl_update_status_cron' );
		}

	}

	public function updateStatus(){

		$unloading = new Unloading();
		//$wpStatuses = $unloading->getStatusWp();
		$wpStatusesKeys = array();
		if(isset($this->addForm['statusEnd'])){
			$wpStatusesKeys = $this->addForm['statusEnd'];
		}

		if(!$wpStatusesKeys)
			return false;

		$args = array(
			'status' => $wpStatusesKeys,
			'limit' => -1
		);
		$orders = wc_get_orders( $args );

		foreach ($orders as $order){
			$orderShippings = $order->get_shipping_methods();
			$orderId = $order->get_id();
			$orderShipping = array();

			foreach ($orderShippings as $key=>$item){
				$orderShipping = array(
					'id' => $item->get_method_id(),
					'name' => $item->get_method_title(),
				);
			}
			$checkDelivery = stripos($orderShipping['id'], WC_ESL_PREFIX);
			if($checkDelivery === false)
				continue;

			$checkName = $unloading->getMethodByName($orderShipping['name']);
			if(!$checkName['name'])
				continue;

			$status = $unloading->infoOrder($orderId, $checkName['name']);
			if(isset($status['success']) && $status['success'] === false){
				$result = $status['data']['messages'] ?? 'Ошибка при получении данных';
			}else{
				$result = $unloading->updateStatusById($status, $orderId);
			}

			$logger = wc_get_logger();
			$context = array( 'source' => 'esl-info-cron-status' );
			$logger->info( print_r($status, true),  $context);
		}

	}

	public function cron_esl_custom_min( $schedules ) {
		$timeMin = 180;
		if(isset($this->addForm['cronStatusTime'])){
			$timeMin = (int)$this->addForm['cronStatusTime'];
		}

		$schedules['esl_custom_min'] = array(
			'interval' => 60 * $timeMin,
			'display' => 'ESL - Интервал выбранный пользователем'
		);
		return $schedules;
	}
}
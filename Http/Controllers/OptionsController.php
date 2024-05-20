<?php

namespace eshoplogistic\WCEshopLogistic\Http\Controllers;

use eshoplogistic\WCEshopLogistic\Contracts\ResponseInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Http\Foundation\Controller;
use eshoplogistic\WCEshopLogistic\Http\Request;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApiV2;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;

if ( ! defined('ABSPATH') ) {
	exit;
}

class OptionsController extends Controller
{
	/**
	 * @var OptionsRepository $options
	 */
	private $options;

	public function __construct()
	{
		$this->options = new OptionsRepository();
	}

	/**
	 * @param Request $request
	 *
	 * @return ResponseInterface
	 */
	public function save(Request $request): ResponseInterface
	{
		$data = $request->get('data');

		$result = $this->validate($data);

		if (true !== $result) {
			return $this->json([
				'status' => 'error',
				'msg'    => __('Ошибка сохранения настроек', WC_ESL_DOMAIN)
			]);
		}

		$this->options->save($data);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Настройки успешно сохранены', WC_ESL_DOMAIN),
			'data'   => $this->options->getAll()
		]);
	}

	/**
	 * @param string $api_key
	 *
	 * @return ResponseInterface
	 */
	public function saveApiKey(string $api_key): ResponseInterface
	{
		if(empty($api_key)) {
			return $this->json([
				'status' => 'error',
				'msg'    => __('API ключ пустой', WC_ESL_DOMAIN)
			]);
		}

		$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
		$eshopLogisticApi->setApiKey($api_key);

		$info_account = $eshopLogisticApi->infoAccount($api_key);
		$init_account = $eshopLogisticApi->initAccount();
		$all_services = $eshopLogisticApi->allServices();

		if(
			$info_account->hasErrors() ||
			$init_account->hasErrors() ||
			$all_services->hasErrors()
		) {
			$errorMsg = 'API ключ введён некорректно';
			$info_account_data = $info_account->jsonSerialize();
			if(isset($info_account_data['data']['messages']))
				$errorMsg = $info_account_data['data']['messages'];

			return $this->json([
				'status' => 'error',
				'msg'    => __($errorMsg, WC_ESL_DOMAIN)
			]);
		}

		$data = [];
		$data['wc_esl_shipping'] = [];

		$data['wc_esl_shipping']['api_key'] = $api_key;

		if(!empty($info_account->data())) {

			$data['wc_esl_shipping']['account_balance'] = $info_account->data()['balance'];
			$data['wc_esl_shipping']['account_blocked'] = $info_account->data()['blocked'];
			$data['wc_esl_shipping']['account_domain'] = $info_account->data()['domain'];
			$data['wc_esl_shipping']['account_free_days'] = $info_account->data()['free_days'];
			$data['wc_esl_shipping']['account_paid_days'] = $info_account->data()['paid_days'];
			$data['wc_esl_shipping']['account_services'] = $info_account->data()['services'];
			$data['wc_esl_shipping']['account_settings'] = $info_account->data()['settings'];
		}

		if(!empty($init_account->data())) {
			$data['wc_esl_shipping']['account_init_services'] = $init_account->data();
		}

		$this->options->save($data);

		return $this->json([
			'status' => 'success',
			'msg'    => __('API ключ успешно сохранён', WC_ESL_DOMAIN),
			'data'   => $this->options->getAll()
		]);
	}

	/**
	 * @param string $api_key
	 *
	 * @return ResponseInterface
	 */
	public function saveApiKeyWCart(string $api_key): ResponseInterface
	{
		$this->options->save([
			'wc_esl_shipping' => [
				'api_key_wcart' => $api_key
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Ключ виджета успешно сохранён', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_widget_key_wcart')
		]);
	}

	/**
	 * @param string $api_key_ya
	 *
	 * @return ResponseInterface
	 */
	public function saveApiKeyYa(string $api_key_ya)
	{
		$this->options->save([
			'wc_esl_shipping' => [
				'api_key_ya' => $api_key_ya
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Ключ виджета успешно сохранён', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_widget_key_ya')
		]);
	}

	/**
	 * @param string $secretCode
	 *
	 * @return ResponseInterface
	 */
	public function saveWidgetSecretCode($secretCode) {
		if(empty($secretCode)) return $this->json(['status' => 'error', 'msg' => __('Секретный код пустой', WC_ESL_DOMAIN)]);

		$this->options->save([
			'wc_esl_shipping' => [
				'widget_secret_code' => $secretCode
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Секретный код успешно сохранён', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_widget_secret_code')
		]);
	}

	/**
	 * @param string $key
	 *
	 * @return ResponseInterface
	 */
	public function saveWidgetKey($key) {
		if(empty($key)) return $this->json(['status' => 'error', 'msg' => __('Ключ виджета пустой', WC_ESL_DOMAIN)]);

		$this->options->save([
			'wc_esl_shipping' => [
				'widget_key' => $key
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Ключ виджета успешно сохранён', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_widget_key')
		]);
	}

	/**
	 * @param string $key
	 *
	 * @return ResponseInterface
	 */
	public function saveWidgetBut($key) {
		if(empty($key)) return $this->json(['status' => 'error', 'msg' => __('Название пустое', WC_ESL_DOMAIN)]);

		$this->options->save([
			'wc_esl_shipping' => [
				'widget_but' => $key
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Название виджета успешно сохранено', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_widget_but')
		]);
	}

	/**
	 * @param string $key
	 *
	 * @return ResponseInterface
	 */
	public function saveAddForm($key) {
		if(empty($key)) return $this->json(['status' => 'error', 'msg' => __('Название пустое', WC_ESL_DOMAIN)]);

		$this->options->save([
			'wc_esl_shipping' => [
				'add_form' => $key
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Доп. настройки успешно сохранены', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_add_form')
		]);
	}

	/**
	 * @param string $key
	 *
	 * @return ResponseInterface
	 */
	public function saveExportForm($key) {
		if(empty($key)) return $this->json(['status' => 'error', 'msg' => __('Название пустое', WC_ESL_DOMAIN)]);

		$this->options->save([
			'wc_esl_shipping' => [
				'export_form' => $key
			]
		]);

		return $this->json([
			'status' => 'success',
			'msg'    => __('Данные по выгрузке успешно сохранены', WC_ESL_DOMAIN),
			'data'   => $this->options->getOption('wc_esl_shipping_export_form')
		]);
	}

	/**
	 * @param string $data
	 *
	 * @return ResponseInterface
	 */
	public function unloadingSend(array $data): ResponseInterface
	{

		return $this->json([
			'status' => 'success',
			'msg'    => __('Выгрузка завершена', WC_ESL_DOMAIN),
			'data'   => ''
		]);
	}

	/**
	 * @param array $data
	 *
	 * @return array|bool
	 */
	private function validate($data)
	{
		$errors = [];

		return $errors ? $errors : true;
	}
}
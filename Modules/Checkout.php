<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Checkout implements ModuleInterface
{
    public function init()
    {
        //add_action('woocommerce_after_checkout_billing_form', [$this, 'injectBillingFields']);
        //add_action('woocommerce_after_checkout_shipping_form', [$this, 'injectShippingFields']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'clearShippingCache']);
        add_action('woocommerce_after_shipping_rate', [$this, 'infoShippingMethodItem']);
	    add_action('woocommerce_review_order_before_payment', [$this, 'injectShippingFormFields']);
	    add_action('cfw_checkout_after_shipping_methods', [$this, 'injectShippingFormFields']);
	    add_action('woocommerce_after_shipping_rate', [$this, 'buttonTerminal'], 10, 2);
    }

    public function buttonTerminal($method){
        $idDelivery = $method->id;
	    $shippingHelper = new ShippingHelper();
	    $typeMethod = $shippingHelper->getTypeMethod($idDelivery);
	    $chosenShipping = WC()->session->chosen_shipping_methods;

	    $optionsRepository = new OptionsRepository();
	    $moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');
        if($idDelivery == 'wc_esl_postrf_terminal' && !$moduleVersion){
	        $typeMethod = 'door';
        }

	    if($typeMethod == 'terminal' && in_array($idDelivery, $chosenShipping) && is_checkout()){
		    $addOption = $optionsRepository->getOption('wc_esl_shipping_add_form');
            if(isset($addOption['pvzName']) && $addOption['pvzName']){
                $pvzName = $addOption['pvzName'];
            }else{
	            $pvzName = 'Выбрать пункт <br> выдачи';
            }

		    echo '<button
                    class="wc-esl-terminals__button_under"
                    type="button"
                    onclick="terminalButtonClick()">
                		'.$pvzName.'
                    </button>
                    <script>
	                    for (const elem of document.querySelectorAll(".wc-esl-terminals__button"))
							    elem.style.display = "none"
					                
                        function terminalButtonClick () {
                            let items = document.getElementsByClassName("wc-esl-terminals__button")
                            if( items.length < 1 ) 
                                return ""

                            for (const item of items){
                                 let isVisible = item.closest(".show")
                                 if(isVisible)
                                     item.click()
                            }
                           
                        }
                    </script>';
        }
    }

    public function clearShippingCache()
    {
        $packages = WC()->cart->get_shipping_packages();
        
        foreach ( $packages as $key => $value ) {
            $shipping_session = "shipping_for_package_$key";
    
            unset( WC()->session->$shipping_session );
        }
    }


	public function injectShippingFormFields($item)
	{
		$this->injectFields( 'billing' );
		$this->injectFields( 'shipping' );
	}

    public function injectBillingFields()
    {
        $this->injectFields( 'billing' );
    }

    public function injectShippingFields()
    {
        $this->injectFields( 'shipping' );
    }

    private function injectFields($type)
    {
	    $optionsRepository = new OptionsRepository();
	    $frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');
	    $addForm = $optionsRepository->getOption('wc_esl_shipping_add_form');
	    $moduleVersion = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );
	    $citySelectModal = false;
	    if(isset($addForm['citySelectModal']) && $addForm['citySelectModal'] == 'true')
		    $citySelectModal = $addForm['citySelectModal'];

	    if($frameEnable){
	        $this->renderCheckoutFieldsFrame($type);
        }else{
	        $this->renderCheckoutFields($type);
        }

        if($citySelectModal && $moduleVersion)
            $this->renderCheckoutCity();
    }

    private function renderCheckoutCity(){
        ?>
        <div id="modal-esl-city" class="modal-esl-frame">
            <div class="modal_content">
                <div class="title">
                    <span class="close_modal_window">×</span>
                    <p><strong>Выберите свой населённый пункт</strong><br>Начните ввод названия населённого пункта для поиска</p>
                </div>
                <input id="esl_modal-search" value="" placeholder="Населенный пункт" data-mode="billing">
                <div id="esl_result-search"></div>
            </div>
        </div>
        <?php
    }

    private function renderCheckoutFields($type)
    {
	    $optionsRepository = new OptionsRepository();
	    $tipsCities = 'Для расчёта доставки укажите населённый пункт';
        $tipsCitiesTmp = $optionsRepository->getOption('wc_esl_shipping_add_form');
        if(isset($tipsCitiesTmp['citiesTips']) && $tipsCitiesTmp['citiesTips'])
            $tipsCities = $tipsCitiesTmp['citiesTips'];
	    ?>

        <div id="tips-city-container" style="display: none;">
            <i class="ico">☓</i>
            <?php echo $tipsCities ?>
        </div>
        <div id="wc-esl-terminals-wrap-<?php echo esc_attr($type) ?>" class="wc-esl-terminals__container">
		    <?php
		    $sessionService = new SessionService();
		    ?>
            <button
                    class="wc-esl-terminals__button"
                    type="button"
                    data-mode="<?php echo esc_attr($type) ?>"
            >
			    <?php echo $sessionService->get('terminal_location') ? __('Выбрать другой пункт выдачи', WC_ESL_DOMAIN) : __('Выбрать пункт выдачи', WC_ESL_DOMAIN) ?>
            </button>

		    <?php
		    woocommerce_form_field(
			    "wc_esl_{$type}_terminal",
			    array(
				    'label' => __('Пункт выдачи', WC_ESL_DOMAIN),
				    'required' => true,
				    'custom_attributes' => array(
					    'readonly' => true
				    )
			    ),
			    $sessionService->get('terminal_location') ? $sessionService->get('terminal_location') : ''
		    );
		    ?>

        </div>

	    <?php
    }

	private function renderCheckoutFieldsFrame($type)
	{
		?>
		<?php
		$sessionService = new SessionService();
		$optionsRepository = new OptionsRepository();
		$moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');
		$widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');
		$apiKeyWCart = $optionsRepository->getOption('wc_esl_shipping_api_key_wcart');
        $shippingEsl = $sessionService->get('esl_shipping_frame');
		$count = 0;
        $countText = 'служб';
		$tipsCities = 'Для расчёта доставки укажите населённый пункт';
		$tipsCitiesTmp = $optionsRepository->getOption('wc_esl_shipping_add_form');
        $eslLoader = false;

		if(isset($tipsCitiesTmp['citiesTips']) && $tipsCitiesTmp['citiesTips'])
			$tipsCities = $tipsCitiesTmp['citiesTips'];

        if(isset($tipsCitiesTmp['eslLoader'])){
            $eslLoader = $tipsCitiesTmp['eslLoader'];
            $eslLoader = wp_get_attachment_image_url($eslLoader, 'full');
        }

        if(isset($shippingEsl['deliveryMethods']) && is_array($shippingEsl['deliveryMethods'])){
	        foreach ($shippingEsl['deliveryMethods'] as $key=>$value){
		        if($shippingEsl['mode'] === $value['keyShipper']){
			        $count = count($value['services']);
		        }
	        }
        }

        if($count === 1)
            $countText = 'служба';
        if($count > 1 && $count < 5)
            $countText = 'службы';

		$addOption = $optionsRepository->getOption('wc_esl_shipping_add_form');
		if(isset($addOption['pvzName']) && $addOption['pvzName']){
			$pvzName = $addOption['pvzName'];
		}else{
			$pvzName = 'Выбрать способ доставки и пункт самовывоза';
		}

		?>
        <div id="tips-city-container" style="display: none;">
            <i class="ico">☓</i>
			<?php echo $tipsCities ?>
        </div>

        <div id="wc-esl-terminals-wrap-button-<?php echo esc_attr($type) ?>" class="wc-esl-terminals__container wc-esl-terminals__frame">
            <div class="esl_desct_delivery" style="display: none;">
                <p>Всего доступно <span class="count"><?php echo $count; ?></span>
                <span class="countText"><?php echo $countText; ?></span> доставки.
                    <br><span class="addText">Выбран самый дешевый вариант.</span></p>
            </div>
            <button
                    class="wc-esl-terminals__button wc-esl-frame__button"
                    type="button"
                    data-mode="<?php echo esc_attr($type) ?>"
            >
		        <?php echo __($pvzName, WC_ESL_DOMAIN) ?>
            </button>
        </div>

        <div id="modal-esl-frame" class="modal-esl-frame">
            <div class="modal_content">
                <div class="title">
                    <span class="close_modal_window">×</span>
                </div>
                <?php if(isset($moduleVersion) && $moduleVersion == '1'):?>
                    <div id="eShopLogisticWidgetCart" data-key="<?php echo $apiKeyWCart ?>" data-lazy-load="false" data-controller="/?rest_route=/wc-esl/v2/widget-data/" data-v-app></div>
                <?php else: ?>
                    <div id="eShopLogisticStatic" data-key="<?php echo $widgetKey ?>"></div>
                <?php endif; ?>
                <div class="footer">
                    <input id="buttonModalDoor" type="button"  value="Выбрать">
                </div>
            </div>
        </div>

        <div id="wc-esl-terminals-wrap-<?php echo esc_attr($type) ?>" class="wc-esl-terminals__container">
			<?php
			woocommerce_form_field(
				"wc_esl_{$type}_terminal",
				array(
					'label' => __('Пункт выдачи', WC_ESL_DOMAIN),
					'required' => true,
					'description' => __( 'Выберите на карте', 'woocommerce' ),
					'custom_attributes' => array(
						'readonly' => true
					)
				),
				$sessionService->get('terminal_location') ? $sessionService->get('terminal_location') : ''
			);
			?>

        </div>

        <div class="preloader">
            <?php if($eslLoader): ?>
                <div class="preloader__img">
                    <img src="<?php echo $eslLoader ?>" width="150" height="150">
                </div>
            <?php else: ?>
                <div class="preloader__row">
                    <div class="preloader__item"></div>
                    <div class="preloader__item"></div>
                </div>
            <?php endif; ?>
        </div>


		<?php
	}


	public function infoShippingMethodItem($item)
    {
        $sessionService = new SessionService();
        $optionsRepository = new OptionsRepository();

        $shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];
        $paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');

        if(!isset($shippingMethods[$item->id])) return;
    }
}
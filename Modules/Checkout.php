<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
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
        add_action('woocommerce_checkout_update_order_meta',[$this, 'addSaveField'], 25);
    }


    function addSaveField( $order_id ){
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout flow validates nonce before order meta hooks.
        $posted = wp_unslash( $_POST );
        $fields = array(
            'esl_billing_field_street',
            'esl_billing_field_building',
            'esl_billing_field_room',
            'esl_shipping_field_street',
            'esl_shipping_field_building',
            'esl_shipping_field_room',
        );

        foreach ( $fields as $field ) {
            if ( ! empty( $posted[ $field ] ) ) {
                update_post_meta( $order_id, $field, sanitize_text_field( $posted[ $field ] ) );
            }
        }

    }

    public function buttonTerminal($method){
        $idDelivery = $method->id;
	    $shippingHelper = new ShippingHelper();
	    $typeMethod = $shippingHelper->getTypeMethod($idDelivery);
	    $chosenShipping = WC()->session->chosen_shipping_methods;

	    if($typeMethod == 'terminal' && in_array($idDelivery, $chosenShipping) && is_checkout()){
		    $optionsRepository = new OptionsRepository();
		    $addOption = $optionsRepository->getOption('wc_esl_shipping_add_form');
		    $pvzName2 = '';
            if(isset($addOption['pvzName']) && $addOption['pvzName']){
                $pvzName = $addOption['pvzName'];
            }else{
	            $pvzName = 'Выбрать пункт';
                $pvzName2 = 'выдачи';
            }

		    echo '<button
                    class="wc-esl-terminals__button_under"
                    type="button"
                    onclick="terminalButtonClick()">
                		'.esc_html($pvzName).'<br>'.esc_html($pvzName2).'
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
        woocommerce_form_field(
            'esl_billing_field_street',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Улица',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
        woocommerce_form_field(
            'esl_billing_field_building',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Здание',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
        woocommerce_form_field(
            'esl_billing_field_room',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Квартира / офис',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
    }

    public function injectShippingFields()
    {
        woocommerce_form_field(
            'esl_shipping_field_street',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Улица',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
        woocommerce_form_field(
            'esl_shipping_field_building',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Здание',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
        woocommerce_form_field(
            'esl_shipping_field_room',
            array(
                'type'          => 'text',
                'required'	=> true,
                'class'         => array( 'true-field', 'form-row-wide' ),
                'label'         => 'Квартира / офис',
                'label_class'   => 'true-label', // класс лейбла
            ),
        );
    }

    private function injectFields($type)
    {
	    $optionsRepository = new OptionsRepository();
	    $frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');
	    $addForm = $optionsRepository->getOption('wc_esl_shipping_add_form');
	    $citySelectModal = false;
	    if(isset($addForm['citySelectModal']) && $addForm['citySelectModal'] == 'true')
		    $citySelectModal = $addForm['citySelectModal'];

	    if($frameEnable){
	        $this->renderCheckoutFieldsFrame($type);
        }else{
	        $this->renderCheckoutFields($type);
        }

        if($citySelectModal)
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
            <?php echo esc_html($tipsCities) ?>
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
			    <?php echo $sessionService->get('terminal_location') ? esc_html__('Выбрать другой пункт выдачи', 'eshoplogisticru') : esc_html__('Выбрать пункт выдачи', 'eshoplogisticru') ?>
            </button>

		    <?php
		    woocommerce_form_field(
			    "wc_esl_{$type}_terminal",
			    array(
				    'label' => __('Пункт выдачи', 'eshoplogisticru'),
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
		$widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');
		$apiKeyWCart = $optionsRepository->getOption('wc_esl_shipping_api_key_wcart');
        $shippingEsl = $sessionService->get('esl_shipping_frame');
		
		// Get widget data for static display
        $widgetOffersEsl = $this->infoCart();
		$paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');
        $modeShipping = $sessionService->get('mode_shipping');
        if ( ! in_array( $modeShipping, array( 'billing', 'shipping' ), true ) ) {
            $modeShipping = 'shipping';
        }

        $widgetCityEsl = $sessionService->get( $modeShipping ) ? $sessionService->get( $modeShipping ) : array();
		
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
			<?php echo esc_html($tipsCities) ?>
        </div>

        <div id="wc-esl-terminals-wrap-button-<?php echo esc_attr($type) ?>" class="wc-esl-terminals__container wc-esl-terminals__frame">
            <div class="esl_desct_delivery" style="display: none;">
                <p>Всего доступно <span class="count"><?php echo esc_html($count); ?></span>
                <span class="countText"><?php echo esc_html($countText); ?></span> доставки.
                    <br><span class="addText">Выбран самый дешевый вариант.</span></p>
            </div>
            <button
                    class="wc-esl-terminals__button wc-esl-frame__button"
                    type="button"
                    data-mode="<?php echo esc_attr($type) ?>"
            >
		        <?php echo esc_html($pvzName) ?>
            </button>
        </div>

        <div id="modal-esl-frame" class="modal-esl-frame">
            <div class="modal_content">
                <div class="title">
                    <span class="close_modal_window">×</span>
                </div>
                <div id="eShopLogisticWidgetCart" data-key="<?php echo esc_attr($apiKeyWCart) ?>" data-lazy-load="false" data-controller="/?rest_route=/wc-esl/v2/widget-data/" data-v-app></div>
                <div id="boxEshoplogistic" class="boxEshoplogistic" style="display:none;">
                    <div id='eShopLogisticWidgetKey' data-key='<?php echo esc_attr($widgetKey)?>'></div>
                    <input id='widgetOffersEsl' value='<?php echo esc_attr(json_encode($widgetOffersEsl)); ?>' type='hidden'>
                    <input id='widgetCityEsl' value='<?php echo esc_attr(json_encode($widgetCityEsl)); ?>' type='hidden'>
                    <input id='widgetPaymentEsl' value='<?php echo esc_attr(json_encode($paymentMethods ? $paymentMethods : array())); ?>' type='hidden'>
                </div>
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
					'label' => esc_html__('Пункт выдачи', 'eshoplogisticru'),
					'required' => true,
					'description' => esc_html__( 'Выберите на карте', 'eshoplogisticru' ),
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
                    <img src="<?php echo esc_html($eslLoader) ?>" width="150" height="150">
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

    private function infoCart()
    {
        $items = WC()->cart->get_cart_contents();
        $data = new CheckoutOrderData($items);
        $offers = array();

        if($data->getItems()) {
            foreach($data->getItems() as $item) {
                $offers[] = array(
                    'article' => $item->getArticle(),
                    'name' => $item->getName(),
                    'count' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                    'weight' => $item->getWeight(),
                    'dimensions' => $item->getDimensions(),
                );
            }
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook name retained for backward compatibility.
        $offers = apply_filters( 'esl_offers_filter', $offers );

        return $offers;
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
<?php

class Smart2Pay_Globalpay_Model_Pay extends Mage_Payment_Model_Method_Abstract
{
    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PROCESSING = 7;

    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20;

    protected $_code = 'globalpay';
    protected $_formBlockType = 'globalpay/paymethod_form';
    protected $_infoBlockType = 'globalpay/info_globalpay';

    // method config
    public $method_config = array();

    public function __construct()
    {
        parent::__construct();

        // get environment type
        $environment = $this->getConfigData('environment'); // [test | live]

        // get config
        $this->method_config = array(
            'post_url' => $this->getConfigData('post_url_'.$environment),
            'signature' => $this->getConfigData('signature_'.$environment),
            'mid' => $this->getConfigData('mid_'.$environment),
            'return_url' => $this->getConfigData('return_url'),
            'methods' => $this->getConfigData('methods'),
            'methods_display_mode' => $this->getConfigData('methods_display_mode'),
            'show_text_img' => $this->getConfigData('show_text_img'),
            'show_methods_in_grid' => $this->getConfigData('show_methods_in_grid'),
            'grid_column_number' => $this->getConfigData('grid_column_number'),
            'adjust_for_one_page_checkout' => $this->getConfigData('adjust_for_one_page_checkout'),
            'display_surcharge' => $this->getConfigData('display_surcharge'),
            'autoselect_s2p' => $this->getConfigData('autoselect_s2p'),
            'send_customer_email' => $this->getConfigData('send_customer_email'),
            'send_customer_name' => $this->getConfigData('send_customer_name'),
            'send_country' => $this->getConfigData('send_country'),
            'send_payment_method' => $this->getConfigData('send_payment_method'),
            'notify_payment_instructions'  => $this->getConfigData('notify_payment_instructions'),
            'send_product_description' => $this->getConfigData('send_product_description'),
            'product_description_ref' => $this->getConfigData('product_description_ref'),
            'product_description_custom' => $this->getConfigData('product_description_custom'),
            'skip_payment_page' => $this->getConfigData('skip_payment_page'),
            'debug_form' => $this->getConfigData('debug_form'),
            'redirect_in_iframe' => $this->getConfigData('redirect_in_iframe'),
            'skin_id' => $this->getConfigData('skin_id'),
            'site_id' => $this->getConfigData('site_id'),
            'message_data_2' => $this->getConfigData('message_data_2'),
            'message_data_3' => $this->getConfigData('message_data_3'),
            'message_data_4' => $this->getConfigData('message_data_4'),
            'message_data_7' => $this->getConfigData('message_data_7'),
            'order_status' => $this->getConfigData('order_status'),
            'order_status_on_2' => $this->getConfigData('order_status_on_2'),
            'order_status_on_3' => $this->getConfigData('order_status_on_3'),
            'order_status_on_4' => $this->getConfigData('order_status_on_4'),
            'order_status_on_5' => $this->getConfigData('order_status_on_5'),
            'auto_invoice' => $this->getConfigData('auto_invoice'),
            'auto_ship' => $this->getConfigData('auto_ship'),
            'notify_customer' => $this->getConfigData('notify_customer'),
        );
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData( $data )
    {
        if( !($data instanceof Varien_Object) )
            $data = new Varien_Object($data);

        /** @var Mage_Checkout_Model_Session $chkout */
        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        if( !($method_id = $data->getMethodId())
         or !($chkout = Mage::getSingleton('checkout/session'))
         or !($quote = $chkout->getQuote())
         or !($billingAddress = $quote->getBillingAddress())
         or !($countryCode = $billingAddress->getCountryId())
         or !($countryId = Mage::getModel('globalpay/country')->load($countryCode, 'code')->getId())
         or !($configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' ))
         or !($enabled_methods = $configured_methods_obj->get_configured_methods( $countryId, array( 'id_in_index' => true ) ))
         or empty( $enabled_methods[$method_id] ) )
        {
            Mage::throwException( Mage::helper('payment')->__( 'Couldn\'t get payment method details. Please try again.' ) );
            return $this;
        }

        $info = $this->getInfoInstance();

        //$info->setMethodId( $method_id );

        $_SESSION['globalpay_method'] = $method_id;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        if( !empty( $enabled_methods[$method_id]['surcharge'] ) )
        {
            $info->setS2pSurchargePercent( $enabled_methods[$method_id]['surcharge'] );

            if( ($total_amount = $quote->getGrandTotal()) )
                $total_amount -= $info->getS2pSurchargeAmount();
            if( ($total_base_amount = $quote->getBaseGrandTotal()) )
                $total_base_amount -= $info->getS2pSurchargeBaseAmount();

            $surcharge_amount = 0;
            if( !empty( $total_amount ) )
                $surcharge_amount = ( $total_amount * $enabled_methods[ $method_id ]['surcharge'] ) / 100;
            $surcharge_base_amount = 0;
            if( !empty( $total_base_amount ) )
                $surcharge_base_amount = ($total_base_amount * $enabled_methods[$method_id]['surcharge']) / 100;

            $logger_obj->write( 'Total ['.$total_amount.'], Surcharge ['.$surcharge_amount.'] ['.$enabled_methods[$method_id]['surcharge'].'%]' );

            $info->setS2pSurchargeAmount( $surcharge_amount );
            $info->setS2pSurchargeBaseAmount( $surcharge_base_amount );

            $quote->setTotalsCollectedFlag( false );

            // if( !$quote->setTotalsCollectedFlag( true ) )
                $quote->collectTotals();

            //$s2p_surcharge = array(
            //    's2p_surcharge_percent' => $enabled_methods[$method_id]['surcharge'],
            //    's2p_surcharge_amount' => $surcharge_amount,
            //);
            //
            //$info->setAdditionalInformation( $s2p_surcharge );
        }

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        $redirect_url = Mage::getUrl( 'globalpay', array( '_secure' => true ) );

        $_SESSION['s2p_handle_payment'] = true;

        Mage::getModel('globalpay/logger')->write( $redirect_url, 'info');

        return $redirect_url;
    }
}


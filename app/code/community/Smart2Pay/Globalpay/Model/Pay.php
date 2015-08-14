<?php

class Smart2Pay_Globalpay_Model_Pay extends Mage_Payment_Model_Method_Abstract // implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PROCESSING = 7;

    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20;

    protected $_code = 'globalpay';
    protected $_formBlockType = 'globalpay/paymethod_form';
    protected $_infoBlockType = 'globalpay/info_globalpay';

    // method config
    public $method_config = array();

    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'Profile';
        var_dump( $profile->getData() );
        $buf = ob_get_clean();

        $helper_obj->logf( 'validateRecurringProfile: ['.$buf.']' );
    }

    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    ) {
        //$token = $paymentInfo->
        //getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN);
        //$profile->setToken($token);
        //$this->_pro->submitRecurringProfile($profile, $paymentInfo);

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'Profile';
        var_dump( $profile->getData() );
        echo "\n\n".'Payment Info';
        var_dump( $paymentInfo->getData() );
        $buf = ob_get_clean();

        $helper_obj->logf( 'submitRecurringProfile: ['.$buf.']' );

        /** @var Mage_Sales_Model_Quote $oQuote */
        $quote = $paymentInfo->getQuote();

        /** @var Mage_Customer_Model_Customer $oCustomer */
        $customer = $quote->getCustomer();
        //$customer->save();

        $profile->setCustomerId($customer->getId());
        $profile->setReferenceId($quote->getId());
        $profile->setAdditionalInfo($paymentInfo->getAdditionalInformation());
        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
        $profile->save();
        return $this;
    }

    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails( $referenceId, Varien_Object $result )
    {

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'referenceId';
        var_dump( $referenceId );
        echo "\n\n".'result';
        var_dump( $result->getData() );
        $buf = ob_get_clean();

        $helper_obj->logf( 'getRecurringProfileDetails: ['.$buf.']' );

        return true;
    }

    /**
     * Whether can get recurring profile details
     */
    public function canGetRecurringProfileDetails()
    {

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'canGetRecurringProfileDetails';
        $buf = ob_get_clean();

        $helper_obj->logf( 'canGetRecurringProfileDetails: ['.$buf.']' );

        return true;
    }

    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'Profile';
        var_dump( $profile->getData() );
        $buf = ob_get_clean();

        $helper_obj->logf( 'updateRecurringProfile: ['.$buf.']' );
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        ob_start();
        echo "\n\n".'Profile';
        var_dump( $profile->getData() );
        $buf = ob_get_clean();

        $helper_obj->logf( 'updateRecurringProfileStatus: ['.$buf.']' );
    }

    public function __construct()
    {
        parent::__construct();

        // get environment type
        $environment = $this->getConfigData('environment'); // [demo | test | live]

        // get config
        $this->method_config = array(
            'environment' => $environment,
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

        if( $environment == 'demo' )
        {
            // demo environment
            $this->method_config['post_url'] = 'https://apitest.smart2pay.com';
            $this->method_config['signature'] = '8bf71f75-68d9';
            $this->method_config['mid'] = '1045';
            $this->method_config['site_id'] = '30122';
        } elseif( in_array( $environment, array( 'test', 'live' ) ) )
        {
            $this->method_config['post_url'] = $this->getConfigData( 'post_url_' . $environment );
            $this->method_config['signature'] = $this->getConfigData( 'signature_' . $environment );
            $this->method_config['mid'] = $this->getConfigData( 'mid_' . $environment );
        } else
        {
            $this->method_config['post_url'] = 'https://apitest.smart2pay.com';
            $this->method_config['signature'] = '';
            $this->method_config['mid'] = 0;
        }

        // Not enabled yet
        //$this->method_config['display_surcharge'] = 0;
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
         or !($quote = $chkout->getQuote()) )
        {
            //Mage::throwException( Mage::helper('payment')->__( 'Cannot get payment method details. Please try again.' ) );
            return $this;
        }

        if( !($billingAddress = $quote->getBillingAddress())
         or !($countryCode = $billingAddress->getCountryId())
         or !($countryId = Mage::getModel('globalpay/country')->load($countryCode, 'code')->getId()) )
        {
            Mage::throwException( Mage::helper('payment')->__( 'Cannot get country from billing address.' ) );
            return $this;
        }

        if( !($configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' ))
         or !($enabled_methods = $configured_methods_obj->get_configured_methods( $countryId, array( 'id_in_index' => true ) ))
         or empty( $enabled_methods[$method_id] ) )
        {
            Mage::throwException( Mage::helper('payment')->__( 'Cannot get any payment method for selected country.' ) );
            return $this;
        }

        $info = $this->getInfoInstance();

        $_SESSION['globalpay_method'] = $method_id;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        if( !empty( $enabled_methods[$method_id]['surcharge'] )
         or !empty( $enabled_methods[$method_id]['fixed_amount'] ) )
        {
            $info->setS2pSurchargePercent( $enabled_methods[$method_id]['surcharge'] );

            if( ($total_amount = $quote->getGrandTotal()) )
                $total_amount -= ($info->getS2pSurchargeAmount() + $info->getS2pSurchargeFixedAmount());
            if( ($total_base_amount = $quote->getBaseGrandTotal()) )
                $total_base_amount -= ($info->getS2pSurchargeBaseAmount() + $info->getS2pSurchargeFixedBaseAmount());

            $surcharge_amount = 0;
            if( !empty( $total_amount )
            and (float)$enabled_methods[ $method_id ]['surcharge'] != 0 )
                $surcharge_amount = ( $total_amount * $enabled_methods[ $method_id ]['surcharge'] ) / 100;
            $surcharge_base_amount = 0;
            if( !empty( $total_base_amount )
            and (float)$enabled_methods[ $method_id ]['surcharge'] != 0 )
                $surcharge_base_amount = ($total_base_amount * $enabled_methods[$method_id]['surcharge']) / 100;

            $surcharge_fixed_amount = 0;
            $surcharge_fixed_base_amount = 0;
            if( (float)$enabled_methods[$method_id]['fixed_amount'] != 0 )
                $surcharge_fixed_base_amount = $enabled_methods[$method_id]['fixed_amount'];
            if( $surcharge_fixed_base_amount != 0 )
                $surcharge_fixed_amount = $quote->getStore()->getBaseCurrency()->convert( $surcharge_fixed_base_amount, $quote->getQuoteCurrencyCode() );

            //$logger_obj->write( 'Total ['.$total_amount.'] Base ('.$total_base_amount.'), '.
            //                    'SurchargeFixed ['.$surcharge_fixed_amount.'] BaseFixed ('.$surcharge_fixed_base_amount.'), '.
            //                    'Surcharge ['.$surcharge_amount.'] Base ('.$surcharge_base_amount.') '.
            //                    ' ['.$enabled_methods[$method_id]['surcharge'].'%]' );

            $info->setS2pSurchargeAmount( $surcharge_amount );
            $info->setS2pSurchargeBaseAmount( $surcharge_base_amount );
            $info->setS2pSurchargeFixedAmount( $surcharge_fixed_amount );
            $info->setS2pSurchargeFixedBaseAmount( $surcharge_fixed_base_amount );

            // Recollect totals for surcharge amount
            $quote->setTotalsCollectedFlag( false );
            $quote->collectTotals();
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


<?php
class Smart2Pay_Globalpay_Model_Pay extends Mage_Payment_Model_Method_Abstract // implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    const XML_PATH_EMAIL_PAYMENT_CONFIRMATION = 'payment/globalpay/payment_confirmation_template';
    const XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS = 'payment/globalpay/payment_instructions_template';
    const XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS_SIBS = 'payment/globalpay/payment_instructions_template_sibs';

    const ERR_SDK_PAYMENT_INIT = 1000;

    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PROCESSING = 7,
          S2P_STATUS_CAPTURED = 11;

    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20, PAYMENT_METHOD_SMARTCARDS = 6,
          PAYMENT_METHOD_KLARNA_CHECKOUT = 1052, PAYMENT_METHOD_KLARNA_INVOICE = 75;

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
     *
     * @return Smart2Pay_Globalpay_Model_Pay
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
        $environment = $this->getEnvironment(); // [demo | test | live]

        // get config
        $this->method_config = array(
            'environment' => $environment,
            'last_sync_demo' => $this->getConfigData('last_sync_demo'),
            'last_sync_test' => $this->getConfigData('last_sync_test'),
            'last_sync_live' => $this->getConfigData('last_sync_live'),
            'return_url' => $this->getConfigData('return_url'),
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
            'debug_form' => false,
            'redirect_in_iframe' => $this->getConfigData('redirect_in_iframe'),
            'skin_id' => $this->getConfigData('skin_id'),
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
            'use_3dsecure' => $this->getConfigData('use_3dsecure'),
            'notify_customer' => $this->getConfigData('notify_customer'),
        );

        $api_settings = $this->getApiSettingsByEnvironment( $environment );
        foreach( $api_settings as $key => $val )
            $this->method_config[$key] = $val;

        // Not enabled yet
        //$this->method_config['display_surcharge'] = 0;
    }

    public function getApiSettingsByEnvironment( $environment = false )
    {
        if( empty( $environment ) )
            $environment = $this->getEnvironment();

        $api_settings = array();
        if( $environment == 'demo' )
        {
            // demo environment
            $api_settings['api_environment'] = 'test';
            $api_settings['apikey'] = 'jkIyM689LNUizQFL6NxC2s9Nbr9AHKPYyGq2o/xcm3yYb/G0ca';
            $api_settings['site_id'] = '33685';
            $api_settings['last_sync'] = $this->getConfigData( 'last_sync_demo' );
        } elseif( in_array( $environment, array( 'test', 'live' ) ) )
        {
            $api_settings['api_environment'] = $environment;
            $api_settings['apikey'] = $this->getConfigData( 'apikey_' . $environment );
            $api_settings['site_id'] = $this->getConfigData( 'site_id_' . $environment );
            $api_settings['last_sync'] = $this->getConfigData( 'last_sync_' . $environment );
        } else
        {
            $api_settings['api_environment'] = '';
            $api_settings['apikey'] = '';
            $api_settings['site_id'] = 0;
            $api_settings['last_sync'] = false;
        }

        return $api_settings;
    }

    public function getEnvironment()
    {
        static $_environment = false;

        if( $_environment !== false )
            return $_environment;

        if( !($_environment = $this->getConfigData('environment')) )
            $_environment = 'demo';

        $_environment = strtolower( trim( $_environment ) );
        if( !in_array( $_environment, array( 'demo', 'live', 'test' )) )
            $_environment = 'demo';

        return $_environment;
    }

    public function getSDKVersion()
    {
        /** @var Smart2Pay_Globalpay_Helper_Sdk $sdk_obj */
        $sdk_obj = Mage::helper( 'globalpay/sdk' );

        return $sdk_obj::get_sdk_version();
    }

    public function upate_last_methods_sync_option( $value, $environment = false )
    {
        if( $environment === false )
            $environment = $this->getEnvironment();

        Mage::getConfig()->saveConfig('payment/globalpay/last_sync_'.$environment, $value, 'default', 0);
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Method_Abstract
     */
    public function assignData( $data )
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

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

        Mage::getSingleton('customer/session')->setGlobalPayMethod( $method_id );
        $_SESSION['globalpay_method'] = $method_id;

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

    /**
     * Return Quote or Order Object depending what the Payment is
     *
     * @return Mage_Sales_Model_Order|Mage_Sales_Model_Quote
     */
    public function currentOrderorQuote()
    {
        static $order_or_quote = false;

        if( $order_or_quote !== false )
            return $order_or_quote;

        $info = $this->getInfoInstance();

        if( $info instanceof Mage_Sales_Model_Order_Payment )
            $order_or_quote = $info->getOrder();
        else
            $order_or_quote = $info->getQuote();

        return $order_or_quote;
    }

    /**
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Quote $order_or_quote
     *
     * @return int
     */
    private function _get_order_id( $order_or_quote )
    {
        if( $order_or_quote instanceof Mage_Sales_Model_Order )
            return $order_or_quote->getRealOrderId();

        elseif( $order_or_quote instanceof Mage_Sales_Model_Quote )
            return $order_or_quote->getReservedOrderId();

        return 0;
    }

    /**
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Quote $order_or_quote
     *
     * @return string
     */
    private function _get_currency( $order_or_quote )
    {
        if( $order_or_quote instanceof Mage_Sales_Model_Order )
            return $order_or_quote->getOrderCurrency()->getCurrencyCode();

        elseif( $order_or_quote instanceof Mage_Sales_Model_Quote )
            return $order_or_quote->getQuoteCurrencyCode();

        return '';
    }

    /**
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Quote $order_or_quote
     *
     * @return float
     */
    private function _get_shipping_amount( $order_or_quote )
    {
        if( $order_or_quote instanceof Mage_Sales_Model_Order )
            return $order_or_quote->getShippingAmount();

        elseif( $order_or_quote instanceof Mage_Sales_Model_Quote )
            return $order_or_quote->getShippingAddress()->getShippingAmount();

        return 0;
    }

    public function getOrderPlaceRedirectUrl()
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper('globalpay/helper');
        /** @var Smart2Pay_Globalpay_Helper_Sdk $sdk_obj */
        $sdk_obj = Mage::helper('globalpay/sdk');
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $transactions_logger_obj */
        $transactions_logger_obj = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        if( !($method_id = Mage::getSingleton('customer/session')->getGlobalPayMethod()) )
        {
            Mage::throwException( $helper_obj->__( 'Couldn\'t obtain payment method ID from customer session.' ) );
        }

        $order = $this->currentOrderorQuote();

        $api_credentials = $sdk_obj->get_api_credentials();

        //
        //  Transferred code
        //
        $merchant_transaction_id = $this->_get_order_id( $order ); // $order->getRealOrderId();

        // assume live environment if we don't get something valid from config
        $environment = $this->method_config['environment'];

        if( $environment == 'demo' )
            $merchant_transaction_id = base_convert( time(), 10, 36 ).'_'.$merchant_transaction_id;

        if( !($surcharge_amount = $order->getPayment()->getS2pSurchargeAmount()) )
            $surcharge_amount = 0;
        if( !($surcharge_fixed_amount = $order->getPayment()->getS2pSurchargeFixedAmount()) )
            $surcharge_fixed_amount = 0;

        $total_surcharge_amount = $surcharge_amount + $surcharge_fixed_amount;

        $order_original_amount = $amount_to_pay = $order->getGrandTotal();

        $articles_params = array();
        $articles_params['transport_amount'] = $this->_get_shipping_amount( $order ); // $order->getShippingAmount();
        $articles_params['total_surcharge'] = $total_surcharge_amount;
        $articles_params['amount_to_pay'] = $amount_to_pay;

        $order_products_arr = array();
        if( ($order_products = $order->getAllItems())
        and is_array( $order_products ) )
        {
            /** @var Mage_Sales_Model_Order_Item $product_obj */
            foreach( $order_products as $product_obj )
            {
                $order_products_arr[] = $product_obj->getData();
            }
        }

        $original_amount = $articles_params['amount_to_pay'] - $articles_params['total_surcharge'];

        $articles_str = '';
        $sdk_articles_arr = array();
        $articles_diff = 0;
        if( ($articles_check = $helper_obj->cart_products_to_string( $order_products_arr, $original_amount, $articles_params )) )
        {
            $articles_str = $articles_check['buffer'];
            $sdk_articles_arr = $articles_check['sdk_articles_arr'];

            if( !empty( $articles_check['total_difference_amount'] )
                and $articles_check['total_difference_amount'] >= -0.01 and $articles_check['total_difference_amount'] <= 0.01 )
            {
                $articles_diff = $articles_check['total_difference_amount'];

                //if( $method_id == self::PAYMENT_METHOD_KLARNA_CHECKOUT
                // or $method_id == self::PAYMENT_METHOD_KLARNA_INVOICE )
                //    $amount_to_pay += $articles_diff;
            }
        }

        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $include_metod_ids = array();
        if( empty( $method_id )
        and ($country_code = $order->getBillingAddress()->getCountry())
        and ($countryId = Mage::getModel('globalpay/country')->load( $country_code, 'code')->getId())
        and ($configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' ))
        and ($enabled_methods = $configured_methods_obj->get_configured_methods( $countryId, array( 'id_in_index' => true ) ))
        and is_array( $enabled_methods ) )
        {
            // MethodID is empty... take all payment methods configured from admin
            $include_metod_ids = array_keys( $enabled_methods );
        }

        $currency = $this->_get_currency( $order );

        //
        // SDK functionality
        //
        $payment_arr = array();
        $payment_arr['merchanttransactionid'] = $merchant_transaction_id;
        $payment_arr['amount'] = number_format( $amount_to_pay, 2, '.', '' ) * 100;
        $payment_arr['currency'] = $currency;
        $payment_arr['methodid'] = $method_id;

        if( empty( $method_id ) and !empty( $include_metod_ids ) )
            $payment_arr['includemethodids'] = $include_metod_ids;

        if( !empty( $this->method_config['skin_id'] ) )
            $payment_arr['skinid'] = $this->method_config['skin_id'];

        if( !empty( $this->method_config['product_description_ref'] )
         or empty( $this->method_config['product_description_custom'] ) )
            $payment_arr['description'] = 'Ref. no.: '.$merchant_transaction_id;
        else
            $payment_arr['description'] = $this->method_config['product_description_custom'];

        /** @var Mage_Core_Helper_Http $http_helper_obj */
        if( ($http_helper_obj = Mage::helper('core/http'))
        and ($remote_ip = $http_helper_obj->getRemoteAddr(false)) )
            $payment_arr['clientip'] = $remote_ip;

        $payment_arr['customer'] = array();
        $payment_arr['billingaddress'] = array();

        if( ($customer_fname = $order->getBillingAddress()->getFirstname()) )
            $payment_arr['customer']['firstname'] = $customer_fname;
        if( ($customer_lname = $order->getBillingAddress()->getLastname()) )
            $payment_arr['customer']['lastname'] = $customer_lname;
        if( ($customer_email = $order->getCustomerEmail()) )
            $payment_arr['customer']['email'] = $customer_email;
        if( ($dateofbirth = $order->getCustomerDob()) )
            $payment_arr['customer']['dateofbirth'] = Mage::getSingleton( 'core/date' )->gmtDate( 'Ymd', $dateofbirth );
        if( ($customer_phone = $order->getBillingAddress()->getTelephone()) )
            $payment_arr['customer']['phone'] = $customer_phone;
        if( ($customer_company = $order->getBillingAddress()->getCompany()) )
            $payment_arr['customer']['company'] = $customer_company;

        if( ($baddress_country = $order->getBillingAddress()->getCountryId()) )
            $payment_arr['billingaddress']['country'] = $baddress_country;
        if( ($baddress_city = $order->getBillingAddress()->getCity()) )
            $payment_arr['billingaddress']['city'] = $baddress_city;
        if( ($baddress_zip = $order->getBillingAddress()->getPostcode()) )
            $payment_arr['billingaddress']['zipcode'] = $baddress_zip;
        if( ($baddress_state = $order->getBillingAddress()->getRegion()) )
            $payment_arr['billingaddress']['state'] = $baddress_state;
        if( ($baddress_street = $order->getBillingAddress()->getStreetFull()) )
            $payment_arr['billingaddress']['street'] = str_replace( "\n", ' ', $baddress_street );

        if( empty( $payment_arr['customer'] ) )
            unset( $payment_arr['customer'] );
        if( empty( $payment_arr['billingaddress'] ) )
            unset( $payment_arr['billingaddress'] );

        if( !empty( $sdk_articles_arr ) )
            $payment_arr['articles'] = $sdk_articles_arr;

        if( $method_id == self::PAYMENT_METHOD_SMARTCARDS )
        {
            if( $this->method_config['use_3dsecure'] )
                $payment_arr['3dsecure'] = true;

            if( !($payment_request = $sdk_obj->card_init_payment( $payment_arr )) )
            {
                if( !$sdk_obj->has_error() )
                    $error_msg = 'Couldn\'t initiate request to server.';
                else
                    $error_msg = 'Call error: '.strip_tags( $sdk_obj->get_error() );

                $logger_obj->write( $error_msg, 'SDK_payment_error' );

                $messages_arr = array();
                $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                    'message' => $error_msg,
                    'class' => __CLASS__,
                    'method' => __METHOD__,
                );

                throw $helper_obj->mage_exception( self::ERR_SDK_PAYMENT_INIT, $messages_arr );
            }
        } else
        {
            if( !($payment_request = $sdk_obj->init_payment( $payment_arr )) )
            {
                if( !$sdk_obj->has_error() )
                    $error_msg = 'Couldn\'t initiate request to server.';
                else
                    $error_msg = 'Call error: '.strip_tags( $sdk_obj->get_error() );

                $logger_obj->write( $error_msg, 'SDK_payment_error' );

                $messages_arr = array();
                $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                    'message' => $error_msg,
                    'class' => __CLASS__,
                    'method' => __METHOD__,
                );

                throw $helper_obj->mage_exception( self::ERR_SDK_PAYMENT_INIT, $messages_arr );
            }
        }

        $s2p_transaction_arr = array();
        if( !empty( $method_id ) )
            $s2p_transaction_arr['method_id'] = $method_id;
        if( !empty( $payment_request['id'] ) )
            $s2p_transaction_arr['payment_id'] = $payment_request['id'];
        if( !empty( $merchant_transaction_id ) )
            $s2p_transaction_arr['merchant_transaction_id'] = $merchant_transaction_id;
        $s2p_transaction_arr['environment'] = $environment;
        $s2p_transaction_arr['site_id'] = $api_credentials['site_id'];
        $s2p_transaction_arr['payment_status'] = ((!empty( $payment_request['status'] ) and !empty( $payment_request['status']['id'] ))?$payment_request['status']['id']:0);

        $redirect_parameters = array();
        $redirect_parameters['_query'] = array();

        $redirect_to_payment = true;
        if( $method_id == self::PAYMENT_METHOD_BT or $method_id == self::PAYMENT_METHOD_SIBS )
            $redirect_to_payment = false;

        $extra_data_arr = array();
        if( !empty( $payment_request['referencedetails'] ) and is_array( $payment_request['referencedetails'] ) )
        {
            // Hack for methods that should return amount to pay
            if( ($method_id == self::PAYMENT_METHOD_BT or $method_id == self::PAYMENT_METHOD_SIBS)
            and empty( $payment_request['referencedetails']['amounttopay'] ) )
            {
                $redirect_to_payment = true;

                $account_currency = false;
                if( !empty( $payment_request['referencedetails']['accountcurrency'] ) )
                    $account_currency = $payment_request['referencedetails']['accountcurrency'];
                elseif( $method_id == self::PAYMENT_METHOD_SIBS )
                    $account_currency = 'EUR';

                if( $account_currency
                and strtolower( $currency ) == strtolower( $account_currency ) )
                {
                    $payment_request['referencedetails']['amounttopay'] = number_format( $payment_arr['amount']/100, 2, '.', '' ).' '.$currency;
                    $redirect_to_payment = false;
                }
            }

            foreach( $payment_request['referencedetails'] as $key => $val )
            {
                if( is_null( $val ) )
                    continue;

                $redirect_parameters['_query'][$key] = $val;
                $extra_data_arr[$key] = $val;
            }
        }

        if( !($transaction_arr = $transactions_logger_obj->write( $s2p_transaction_arr, $extra_data_arr )) )
        {
            $messages_arr = array();
            $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                'message' => 'Failed saving transaction for order. Please try again.',
                'class' => __CLASS__,
                'method' => __METHOD__,
            );

            throw $helper_obj->mage_exception( self::ERR_SDK_PAYMENT_INIT, $messages_arr );
        }

        if( empty( $payment_request['redirecturl'] ) )
        {
            $messages_arr = array();
            $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                'message' => 'Redirect URL not provided in API response. Please try again.',
                'class' => __CLASS__,
                'method' => __METHOD__,
            );

            throw $helper_obj->mage_exception( self::ERR_SDK_PAYMENT_INIT, $messages_arr );
        }

        //
        //  END Transferred code
        //

        $real_order_obj = false;
        if( ($real_order_id = $this->_get_order_id( $order )) )
        {
            try
            {
                $real_order_obj = new Mage_Sales_Model_Order();
                $real_order_obj->loadByIncrementId( $real_order_id );
            } catch ( Exception $ex ) {
                $real_order_obj = false;
            }
        }

        if( !empty( $redirect_to_payment ) )
            $redirect_url = $payment_request['redirecturl'];

        else
        {
            if( !empty( $real_order_obj )
            and !empty( $method_id )
            and $this->method_config['notify_payment_instructions']
            and in_array( $method_id, array( self::PAYMENT_METHOD_BT, self::PAYMENT_METHOD_SIBS ) ) )
            {
                // Inform customer
                $this->sendPaymentDetailsForRealOrder( $real_order_obj, $extra_data_arr );
            }

            $redirect_parameters['_secure'] = true;

            $redirect_url = Mage::getUrl( 'checkout/onepage/success', $redirect_parameters );
        }

        if( !empty( $real_order_obj ) )
        {
            //send e-mail to customer about order creation before redirect to Smart2Pay
            try
            {
                $real_order_obj->sendNewOrderEmail();
            } catch ( Exception $ex ) {
            }
        }

        return $redirect_url;
    }

    public function sendPaymentDetailsForRealOrder( Mage_Sales_Model_Order $order, $payment_details_arr )
    {
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        $payment_details_arr = $s2pTransactionLogger::validateTransactionLoggerExtraParams( $payment_details_arr, array( 'keep_default_values' => true ) );

        try
        {
            /** @var $order Mage_Sales_Model_Order */
            /** @var $store_obj Mage_Core_Model_Store */
            /**
             * get data for template
             */
            $store_obj = $order->getStore();

            //$siteUrl = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK );
            // $siteName = Mage::app()->getWebsite(1)->getName();
            //$siteName = Mage::app()->getWebsite()->getName();

            $siteUrl = $store_obj->getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK );
            $siteName = Mage::app()->getWebsite( $store_obj->getWebsiteId() )->getName();

            $order_increment_id = $order->getRealOrderId();

            $supportEmail = Mage::getStoreConfig( 'trans_email/ident_support/email', $order->getStoreId() );
            $supportName = Mage::getStoreConfig( 'trans_email/ident_support/name', $order->getStoreId() );

            $localeCode = Mage::getStoreConfig( 'general/locale/code', $order->getStoreId() );

            if( ($s2p_transaction_arr = $s2pTransactionLogger->getTransactionDetailsAsArray( $order_increment_id ))
            and $s2p_transaction_arr['method_id'] == self::PAYMENT_METHOD_SIBS )
            {
                $templateId = Mage::getStoreConfig( self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS_SIBS, $order->getStoreId() );
            } else
            {
                $templateId = Mage::getStoreConfig( self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS, $order->getStoreId() );
            }

            /** @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate = Mage::getModel( 'core/email_template' );
            if( is_numeric( $templateId ) )
                // loads from database @table core_email_template
                $mailTemplate->load( $templateId );
            else
                $mailTemplate->loadDefault( $templateId, $localeCode );

            if( !($subject = $mailTemplate->getTemplateSubject()) )
                $subject = $this->__( 'PaymentInstructionsSubject', $order_increment_id );

            $subject = $siteName.' - '.$subject;

            $mailTemplate->setSenderName( $supportName );
            $mailTemplate->setSenderEmail( $supportEmail );

            $mailTemplate->setTemplateSubject( $subject );

            // Extra details
            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtDate();
            $payment_details_arr['support_email'] = $supportEmail;

            if( !$mailTemplate->send( $order->getCustomerEmail(), $order->getCustomerName(), $payment_details_arr ) )
                $s2pLogger->write( 'Error sending payment instructions email to ['.$order->getCustomerEmail().']', 'email_template', $order_increment_id );

        } catch( Exception $e )
        {
            $s2pLogger->write( $e->getMessage(), 'exception' );
        }
    }

    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), 'Smart2pay_Globalpay' );
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }
}


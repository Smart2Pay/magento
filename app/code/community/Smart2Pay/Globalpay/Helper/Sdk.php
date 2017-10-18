<?php

class Smart2Pay_Globalpay_Helper_Sdk extends Mage_Payment_Helper_Data
{
    const ERR_GENERIC = 1;

    // After how many hours from last sync action is merchant allowed to sync methods again?
    const RESYNC_AFTER_HOURS = 2;

    private static $_sdk_inited = false;

    private static $_error_msg = '';

    private static function _init_sdk()
    {
        if( empty( self::$_sdk_inited )
        and @is_dir( __DIR__.'/../_' )
        and @file_exists( __DIR__.'/../_/bootstrap.php' ) )
        {
            include_once( __DIR__.'/../_/bootstrap.php' );

            \S2P_SDK\S2P_SDK_Module::st_debugging_mode( false );
            \S2P_SDK\S2P_SDK_Module::st_detailed_errors( false );
            \S2P_SDK\S2P_SDK_Module::st_throw_errors( false );

            self::$_sdk_inited = true;
        }

        return self::$_sdk_inited;
    }

    public function get_error()
    {
        return self::$_error_msg;
    }

    public static function st_has_error()
    {
        return (!empty( self::$_error_msg ));
    }

    public function has_error()
    {
        return self::st_has_error();
    }

    private static function _st_set_error( $error_msg )
    {
        self::$_error_msg = $error_msg;
    }

    private function _set_error( $error_msg )
    {
        self::_st_set_error( $error_msg );
    }

    private static function _st_reset_error()
    {
        self::$_error_msg = '';
    }

    private function _reset_error()
    {
        self::_st_reset_error();
    }

    public static function get_sdk_version()
    {
        self::_st_reset_error();

        if( !self::_init_sdk() )
        {
            self::_st_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        if( !defined( 'S2P_SDK_VERSION' ) )
        {
            self::_st_set_error( 'Unknown Smart2Pay SDK version.' );
            return false;
        }

        return S2P_SDK_VERSION;
    }

    public function last_methods_sync_option( $value = null )
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        if( $value === null )
            return $paymentModel->method_config['last_sync'];

        if( empty( $value ) )
            $value = date( $helper_obj::SQL_DATETIME );

        $paymentModel->upate_last_methods_sync_option( $value );

        return $value;
    }

    public function get_api_credentials()
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');

        if( !in_array( $paymentModel->method_config['environment'], array( 'live', 'test' ) ) )
            $environment = 'test';
        else
            $environment = $paymentModel->method_config['environment'];

        $return_arr = array();
        $return_arr['api_key'] = (empty( $paymentModel->method_config['apikey'] )?'':$paymentModel->method_config['apikey']);
        $return_arr['site_id'] = (empty( $paymentModel->method_config['site_id'] )?0:$paymentModel->method_config['site_id']);
        $return_arr['skin_id'] = (empty( $paymentModel->method_config['skin_id'] )?0:$paymentModel->method_config['skin_id']);
        $return_arr['environment'] = $environment;

        return $return_arr;
    }

    public function get_available_methods()
    {
        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment']; // test or live

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'assigned_methods';

        $api_parameters['get_variables'] = array(
            'additional_details' => true,
        );
        $api_parameters['method_params'] = array();

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = \S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['methods'] ) or !is_array( $call_result['call_result']['methods'] ) )
        {
            if( ($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call failed while obtaining methods list.' );

            return false;
        }

        return $call_result['call_result']['methods'];
    }

    public function get_method_details( $method_id )
    {
        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'method_details';

        $api_parameters['get_variables'] = array(
            'id' => $method_id,
        );
        $api_parameters['method_params'] = array();

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['method'] ) or !is_array( $call_result['call_result']['method'] ) )
        {
            if( ($error_arr = S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call failed while obtaining method details.' );

            return false;
        }

        return $call_result['call_result']['method'];
    }

    public function init_payment( $payment_details_arr )
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');

        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        if( empty( $paymentModel->method_config['return_url'] ) )
        {
            $this->_set_error( 'Return URL in plugin settings is invalid.' );
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'payments';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = array();
        $api_parameters['method_params'] = array( 'payment' => $payment_details_arr );

        if( empty( $api_parameters['method_params']['payment']['tokenlifetime'] ) )
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;

        $api_parameters['method_params']['payment']['returnurl'] = $paymentModel->method_config['return_url'];

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['payment'] ) or !is_array( $call_result['call_result']['payment'] ) )
        {
            if( ($error_arr = S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call to initialize payment failed. Please try again.' );

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    public function card_init_payment( $payment_details_arr )
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');

        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        if( empty( $paymentModel->method_config['return_url'] ) )
        {
            $this->_set_error( 'Return URL in plugin settings is invalid.' );
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'cards';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = array();
        $api_parameters['method_params'] = array( 'payment' => $payment_details_arr );

        if( empty( $api_parameters['method_params']['payment']['tokenlifetime'] ) )
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;

        if( !isset( $api_parameters['method_params']['payment']['capture'] ) )
            $api_parameters['method_params']['payment']['capture'] = true;
        if( !isset( $api_parameters['method_params']['payment']['retry'] ) )
            $api_parameters['method_params']['payment']['retry'] = false;
        if( !isset( $api_parameters['method_params']['payment']['3dsecure'] ) )
            $api_parameters['method_params']['payment']['3dsecure'] = true;
        if( !isset( $api_parameters['method_params']['payment']['generatecreditcardtoken'] ) )
            $api_parameters['method_params']['payment']['generatecreditcardtoken'] = false;

        $api_parameters['method_params']['payment']['returnurl'] = $paymentModel->method_config['return_url'];

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['payment'] ) or !is_array( $call_result['call_result']['payment'] ) )
        {
            if( ($error_arr = S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call to initialize card payment failed. Please try again.' );

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    /**
     * @return bool|string
     */
    public function seconds_to_launch_sync_str()
    {
        if( !($seconds_to_sync = $this->seconds_to_launch_sync()) )
            return false;

        $hours_to_sync = floor( $seconds_to_sync / 1200 );
        $minutes_to_sync = floor( ($seconds_to_sync - ($hours_to_sync * 1200)) / 60 );
        $seconds_to_sync -= ($hours_to_sync * 1200) + ($minutes_to_sync * 60);

        $sync_interval = '';
        if( $hours_to_sync )
            $sync_interval = $hours_to_sync.' hour(s)';

        if( $hours_to_sync or $minutes_to_sync )
            $sync_interval .= ($sync_interval!=''?', ':'').$minutes_to_sync.' minute(s)';

        $sync_interval .= ($sync_interval!=''?', ':'').$seconds_to_sync.' seconds';

        return $sync_interval;
    }

    public function seconds_to_launch_sync()
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        $resync_seconds = self::RESYNC_AFTER_HOURS * 1200;
        $time_diff = 0;
        if( !($last_sync_date = $this->last_methods_sync_option())
         or ($time_diff = abs( $helper_obj::seconds_passed( $last_sync_date ) )) > $resync_seconds )
            return 0;

        return $resync_seconds - $time_diff;
    }

    public function refresh_available_methods()
    {
        /** @var Smart2Pay_Globalpay_Model_Method $methods_obj */
        $methods_obj = Mage::getModel( 'globalpay/method' );

        $this->_reset_error();

        if( ($seconds_to_sync = $this->seconds_to_launch_sync_str()) )
        {
            $this->_set_error( 'You can syncronize methods once every '.self::RESYNC_AFTER_HOURS.' hours. Time left: '.$seconds_to_sync );
            return false;
        }

        if( !($available_methods = $this->get_available_methods())
         or !is_array( $available_methods ) )
        {
            if( !$this->has_error() )
                $this->_set_error( 'Couldn\'t obtain a list of methods.' );
            return false;
        }

        if( true !== ($error_msg = $methods_obj->save_methods_from_sdk_response( $available_methods )) )
        {
            $this->_set_error( $error_msg );
            return false;
        }

        $this->last_methods_sync_option( false );

        return true;
    }
}


<?php

class Smart2pay_Globalpay_IndexController extends Mage_Core_Controller_Front_Action
{
    const XML_PATH_EMAIL_PAYMENT_CONFIRMATION = 'payment/globalpay/payment_confirmation_template';
    const XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS = 'payment/globalpay/payment_instructions_template';
    const XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS_SIBS = 'payment/globalpay/payment_instructions_template_sibs';

    /**
     * Loads s2p in iFrame
     * If !isset($_SESSION['s2p_handle_payment']) redirect to cart/checkout
     */
    public function indexAction()
    {
        // not used anymore...
        $this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . 'checkout/cart/');
        return;

        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        // $s2pLogger = Mage::getModel( 'globalpay/logger' );
        //
        // // check if there is an submited order
        // if( isset( $_SESSION['s2p_handle_payment'] ) )
        // {
        //     unset( $_SESSION['s2p_handle_payment'] );
        //     $this->loadLayout();
        //     $this->renderLayout();
        //
        //     $s2pLogger->write('>>> Redirect OK :::', 'info');
        // } else
        // {
        //     $s2pLogger->write('>>> Redirect NOT OK, session empty. :::', 'info');
        //
        //     $this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . 'checkout/cart/');
        // }
    }    

    public function handleResponseAction()
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        $s2pLogger->write( '--- Notification START --------------------', 'info' );

        /** @var Smart2Pay_Globalpay_Helper_Sdk $sdk_obj */
        $sdk_obj = Mage::helper('globalpay/sdk');
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel( 'sales/order' );
        /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
        $s2pHelper = Mage::helper( 'globalpay/helper' );
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel( 'globalpay/pay' );

        if( !($sdk_version = $sdk_obj::get_sdk_version())
         or !defined( 'S2P_SDK_DIR_CLASSES' )
         or !defined( 'S2P_SDK_DIR_METHODS' ) )
        {
            $s2pLogger->write( 'Unknown SDK version', 'error' );
            return;
        }

        $api_credentials = $sdk_obj->get_api_credentials();

        $s2pLogger->write( 'SDK version: '.$sdk_version, 'info' );

        if( !defined( 'S2P_SDK_NOTIFICATION_IDENTIFIER' ) )
            define( 'S2P_SDK_NOTIFICATION_IDENTIFIER', microtime( true ) );

        S2P_SDK\S2P_SDK_Notification::logging_enabled( false );

        $notification_params = array();
        $notification_params['auto_extract_parameters'] = true;

        /** @var S2P_SDK\S2P_SDK_Notification $notification_obj */
        if( !($notification_obj = S2P_SDK\S2P_SDK_Module::get_instance( 'S2P_SDK_Notification', $notification_params ))
         or $notification_obj->has_error() )
        {
            if( (S2P_SDK\S2P_SDK_Module::st_has_error() and $error_arr = S2P_SDK\S2P_SDK_Module::st_get_error())
             or (!empty( $notification_obj ) and $notification_obj->has_error() and ($error_arr = $notification_obj->get_error())) )
                $error_msg = 'Error ['.$error_arr['error_no'].']: '.$error_arr['display_error'];
            else
                $error_msg = 'Error initiating notification object.';

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            return;
        }

        if( !($notification_type = $notification_obj->get_type())
         or !($notification_title = $notification_obj::get_type_title( $notification_type )) )
        {
            $error_msg = 'Unknown notification type.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            return;
        }

        if( !($result_arr = $notification_obj->get_array()) )
        {
            $error_msg = 'Couldn\'t extract notification object.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            return;
        }

        if( $notification_type != $notification_obj::TYPE_PAYMENT )
        {
            $error_msg = 'Plugin currently supports only payment notifications.';

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            return;
        }

        if( empty( $result_arr['payment'] ) or !is_array( $result_arr['payment'] )
         or empty( $result_arr['payment']['merchanttransactionid'] )
         or !($order->loadByIncrementId( $result_arr['payment']['merchanttransactionid'] ))
         or !($s2p_transaction_arr = $s2pTransactionLogger->getTransactionDetailsAsArray( $result_arr['payment']['merchanttransactionid'] ))
          )
        {
            $error_msg = 'Couldn\'t load order or transaction as provided in notification.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            return;
        }

        $merchanttransactionid = $result_arr['payment']['merchanttransactionid'];
        $payment_arr = $result_arr['payment'];

        if( empty( $s2p_transaction_arr['environment'] )
         or !($api_credentials = $payMethod->getApiSettingsByEnvironment( $s2p_transaction_arr['environment'] ))
         or empty( $api_credentials['site_id'] ) or empty( $api_credentials['apikey'] ) )
        {
            $error_msg = 'Couldn\'t load Smart2Pay API credentials for environment ['.$s2p_transaction_arr['environment'].'].';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
            return;
        }

        \S2P_SDK\S2P_SDK_Module::one_call_settings(
            array(
                'api_key' => $api_credentials['apikey'],
                'site_id' => $api_credentials['site_id'],
                'environment' => $api_credentials['api_environment'],
            ) );

        if( !$notification_obj->check_authentication() )
        {
            if( $notification_obj->has_error()
                and ($error_arr = $notification_obj->get_error()) )
                $error_msg = 'Error: '.$error_arr['display_error'];
            else
                $error_msg = 'Authentication failed.';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
            return;
        }

        $s2pLogger->write( 'Received notification type ['.$notification_title.'].', 'info', $merchanttransactionid  );

        switch( $notification_type )
        {
            case $notification_obj::TYPE_PAYMENT:

                if( empty( $payment_arr['status'] ) or empty( $payment_arr['status']['id'] ) )
                {
                    $error_msg = 'Status not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                    echo $error_msg;
                    return;
                }

                if( !isset( $payment_arr['amount'] ) or !isset( $payment_arr['currency'] ) )
                {
                    $error_msg = 'Amount or Currency not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                    echo $error_msg;
                    return;
                }

                $order->addStatusHistoryComment( 'S2P Notification: payment notification received (Status: '.$payment_arr['status']['id'].').' );

                if( !($status_title = S2P_SDK\S2P_SDK_Meth_Payments::valid_status( $payment_arr['status']['id'] )) )
                    $status_title = '(unknown)';

                $edit_arr = array();
                $edit_arr['merchant_transaction_id'] = $merchanttransactionid;
                if( !empty( $payment_arr['methodid'] ) )
                    $edit_arr['method_id'] = $payment_arr['methodid'];
                $edit_arr['payment_status'] = $payment_arr['status']['id'];

                if( empty( $s2p_transaction_arr['extra_data'] ) )
                    $transaction_extra_data_arr = array();
                else
                    $transaction_extra_data_arr = $s2pHelper->parse_string( $s2p_transaction_arr['extra_data'] );

                if( !empty( $payment_request['referencedetails'] ) and is_array( $payment_request['referencedetails'] ) )
                {
                    foreach( $payment_request['referencedetails'] as $key => $val )
                    {
                        if( is_null( $val ) )
                            continue;

                        $transaction_extra_data_arr[$key] = $val;
                    }
                }

                if( !($new_transaction_arr = $s2pTransactionLogger->write( $edit_arr, $transaction_extra_data_arr )) )
                {
                    $error_msg = 'Couldn\'t save transaction details to database [#'.$s2p_transaction_arr['id'].', Order: '.$s2p_transaction_arr['merchant_transaction_id'].'].';

                    $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                    echo $error_msg;
                    return;
                }

                // Send order confirmation email (if not already sent)
                if( !$order->getEmailSent() )
                    $order->sendNewOrderEmail();

                $s2pLogger->write( 'Received '.$status_title.' notification for order '.$payment_arr['merchanttransactionid'].'.', 'info', $merchanttransactionid );

                // Update database according to payment status
                switch( $payment_arr['status']['id'] )
                {
                    default:
                        $order->addStatusHistoryComment( 'Smart2Pay status ID "'.$payment_arr['status']['id'].'" occurred.' );
                    break;

                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_OPEN:

                        $order->addStatusHistoryComment( 'Smart2Pay status ID "'.$payment_arr['status']['id'].'" occurred.', $payMethod->method_config['order_status'] );

                        if( !empty( $payment_arr['methodid'] )
                        and $payMethod->method_config['notify_payment_instructions']
                        and in_array( $payment_arr['methodid'], array( $payMethod::PAYMENT_METHOD_BT, $payMethod::PAYMENT_METHOD_SIBS ) ) )
                        {
                            // Inform customer
                            $this->sendPaymentDetails( $order, $transaction_extra_data_arr );
                        }
                    break;

                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_SUCCESS:
                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CAPTURED:
                        $orderAmount =  number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;
                        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

                        if( strcmp( $orderAmount, $payment_arr['amount'] ) != 0
                         or $orderCurrency != $payment_arr['currency'] )
                            $order->addStatusHistoryComment( 'S2P Notification: notification has different amount ['.$orderAmount.'/'.$payment_arr['amount'] . '] and/or currency ['.$orderCurrency.'/' . $payment_arr['currency'] . ']!. Please contact support@smart2pay.com', $payMethod->method_config['order_status_on_4'] );

                        else
                        {
                            $order->addStatusHistoryComment( 'S2P Notification: order has been paid. [MethodID: '. $payment_arr['methodid'] .']', $payMethod->method_config['order_status_on_2'] );

                            // Generate invoice
                            if( $payMethod->method_config['auto_invoice'] )
                            {
                                // Create and pay Order Invoice
                                if( !$order->canInvoice() )
                                    $s2pLogger->write( 'Order can not be invoiced', 'warning', $merchanttransactionid );

                                else
                                {
                                    /** @var Mage_Sales_Model_Order_Invoice $invoice */
                                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    $invoice->setRequestedCaptureCase( Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE );
                                    $invoice->register();
                                    $transactionSave = Mage::getModel('core/resource_transaction')
                                        ->addObject( $invoice )
                                        ->addObject( $invoice->getOrder() );
                                    $transactionSave->save();

                                    $order->addStatusHistoryComment( 'S2P Notification: order has been automatically invoiced.', $payMethod->method_config['order_status_on_2'] );
                                }
                            }

                            // Check shipment
                            if( $payMethod->method_config['auto_ship'] )
                            {
                                if( !$order->canShip() )
                                    $s2pLogger->write( 'Order can not be shipped', 'warning' );

                                else
                                {
                                    $itemQty =  $order->getItemsCollection()->count();
                                    $shipment = Mage::getModel( 'sales/service_order', $order )->prepareShipment( $itemQty );
                                    $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                    $shipmentId = $shipment->create( $order->getIncrementId() );
                                    $order->addStatusHistoryComment( 'S2P Notification: order has been automatically shipped.', $payMethod->method_config['order_status_on_2'] );
                                }
                            }

                            // Inform customer
                            if( $payMethod->method_config['notify_customer'] )
                            {
                                $this->informCustomer( $order, $payment_arr['amount'], $payment_arr['currency'] );
                            }
                        }
                    break;

                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CANCELLED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has been canceled.', $payMethod->method_config['order_status_on_3'] );

                        if( !$order->canCancel() )
                            $s2pLogger->write( 'Cannot cancel the order', 'warning', $merchanttransactionid );
                        else
                            $order->cancel();
                    break;

                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_FAILED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has failed.', $payMethod->method_config['order_status_on_4'] );
                    break;

                    case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_EXPIRED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has expired.', $payMethod->method_config['order_status_on_5'] );
                    break;
                }

            break;

            case $notification_obj::TYPE_PREAPPROVAL:
                $s2pLogger->write( 'Preapprovals not implemented.', 'error', $merchanttransactionid );
            break;
        }

        $order->save();

        if( $notification_obj->respond_ok() )
            $s2pLogger->write( '--- Sent OK -------------------------------', 'info', $merchanttransactionid );

        else
        {
            if( $notification_obj->has_error()
                and ($error_arr = $notification_obj->get_error()) )
                $error_msg = 'Error: '.$error_arr['display_error'];
            else
                $error_msg = 'Couldn\'t send ok response.';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
        }

        exit;

        try
        {
            $raw_input = file_get_contents( 'php://input' );
	        parse_str( $raw_input, $response );
		
            $recomposedHashString = '';
            if( !empty( $raw_input ) )
            {
                $pairs = explode( '&', $raw_input );
                foreach( $pairs as $pair )
                {
                    $nv = explode( "=", $pair, 2 );
                    if( !isset( $nv[1] ) )
                        continue;

                    if( strtolower( $nv[0] ) != 'hash' )
                        $recomposedHashString .= $nv[0] . $nv[1];
                }
	        }

	        $recomposedHashString .= $payMethod->method_config['signature'];

            $s2pLogger->write( 'NotificationRecevied:\"' . $raw_input . '\"', 'info' );

            if( empty( $response['Hash'] ) )
                $response['Hash'] = '';

            // Message is intact
            if( $s2pHelper->computeSHA256Hash( $recomposedHashString ) != $response['Hash'] )
                $s2pLogger->write( 'Hashes do not match! received: [' . $response['Hash'] . '] recomposed [' . $s2pHelper->computeSHA256Hash( $recomposedHashString ) . ']', 'warning' );

            elseif( empty( $response['MerchantTransactionID'] ) )
                $s2pLogger->write( 'Unknown merchant transaction ID in request', 'error' );

            else
            {
                $s2pLogger->write( 'Hashes match', 'info' );

                $order->loadByIncrementId( $response['MerchantTransactionID'] );
                $order->addStatusHistoryComment( 'S2P Notification: notification received:<br>' . $raw_input );

                /**
                 * Check status ID
                 */
                switch( $response['StatusID'] )
                {
                    // Status = success
                    case $payMethod::S2P_STATUS_OPEN:

                        if( !empty( $response['MethodID'] )
                        and $payMethod->method_config['notify_payment_instructions']
                        and in_array( $response['MethodID'], array( $payMethod::PAYMENT_METHOD_BT, $payMethod::PAYMENT_METHOD_SIBS ) ) )
                        {
                            $payment_details_arr = self::defaultPaymentDetailsParams();

                            if( isset($response['ReferenceNumber']) )
                                $payment_details_arr['reference_number'] = $response['ReferenceNumber'];
                            if( isset($response['AmountToPay']) )
                                $payment_details_arr['amount_to_pay'] = $response['AmountToPay'];
                            if( isset($response['AccountHolder']) )
                                $payment_details_arr['account_holder'] = $response['AccountHolder'];
                            if( isset($response['BankName']) )
                                $payment_details_arr['bank_name'] = $response['BankName'];
                            if( isset($response['AccountNumber']) )
                                $payment_details_arr['account_number'] = $response['AccountNumber'];
                            if( isset($response['AccountCurrency']) )
                                $payment_details_arr['account_currency'] = $response['AccountCurrency'];
                            if( isset($response['SWIFT_BIC']) )
                                $payment_details_arr['swift_bic'] = $response['SWIFT_BIC'];
                            if( isset($response['IBAN']) )
                                $payment_details_arr['iban'] = $response['IBAN'];
                            if( isset($response['EntityNumber']) )
                                $payment_details_arr['entity_number'] = $response['EntityNumber'];

                            // Inform customer
                            $this->sendPaymentDetails( $order, $payment_details_arr );
                        }
                    break;

                    // Status = success
                    case $payMethod::S2P_STATUS_SUCCESS:
                        // cheking amount  and currency
                        $orderAmount =  number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;
                        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

                        if( strcmp( $orderAmount, $response['Amount'] ) != 0
                         or $orderCurrency != $response['Currency'] )
                            $order->addStatusHistoryComment( 'S2P Notification: notification has different amount ['.$orderAmount.'/'.$response['Amount'] . '] and/or currency ['.$orderCurrency.'/' . $response['Currency'] . ']!. Please contact support@smart2pay.com', $payMethod->method_config['order_status_on_4'] );

                        else
                        {
                            $order->addStatusHistoryComment( 'S2P Notification: order has been paid. [MethodID: '. $response['MethodID'] .']', $payMethod->method_config['order_status_on_2'] );

                            // Generate invoice
                            if( $payMethod->method_config['auto_invoice'] )
                            {
                                // Create and pay Order Invoice
                                if( !$order->canInvoice() )
                                    $s2pLogger->write('Order can not be invoiced', 'warning');

                                else
                                {
                                    /** @var Mage_Sales_Model_Order_Invoice $invoice */
                                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    $invoice->setRequestedCaptureCase( Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE );
                                    $invoice->register();
                                    $transactionSave = Mage::getModel('core/resource_transaction')
                                        ->addObject( $invoice )
                                        ->addObject( $invoice->getOrder() );
                                    $transactionSave->save();

                                    $order->addStatusHistoryComment('S2P Notification: order has been automatically invoiced.', $payMethod->method_config['order_status_on_2']);
                                }
                            }

                            // Check shipment
                            if( $payMethod->method_config['auto_ship'] )
                            {
                                if( !$order->canShip() )
                                    $s2pLogger->write( 'Order can not be shipped', 'warning' );

                                else
                                {
                                    $itemQty =  $order->getItemsCollection()->count();
                                    $shipment = Mage::getModel( 'sales/service_order', $order )->prepareShipment( $itemQty );
                                    $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                    $shipmentId = $shipment->create( $order->getIncrementId() );
                                    $order->addStatusHistoryComment( 'S2P Notification: order has been automatically shipped.', $payMethod->method_config['order_status_on_2'] );
                                }
                            }

                            // Inform customer
                            if( $payMethod->method_config['notify_customer'] )
                            {
                                $this->informCustomer( $order, $response['Amount'], $response['Currency'] );
                            }
                        }
                    break;

                    // Status = canceled
                    case $payMethod::S2P_STATUS_CANCELLED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has been canceled.', $payMethod->method_config['order_status_on_3'] );

                        if( !$order->canCancel() )
                            $s2pLogger->write('Can not cancel the order', 'warning');

                        else
                            $order->cancel();
                    break;

                    // Status = failed
                    case $payMethod::S2P_STATUS_FAILED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has failed.', $payMethod->method_config['order_status_on_4'] );
                    break;

                    // Status = expired
                    case $payMethod::S2P_STATUS_EXPIRED:
                        $order->addStatusHistoryComment( 'S2P Notification: payment has expired.', $payMethod->method_config['order_status_on_5'] );
                    break;

                    default:
                        $order->addStatusHistoryComment( 'Smart2Pay status "'.$response['StatusID'].'" occurred.', $payMethod->method_config['order_status'] );
                    break;
                }

                $order->save();

                $s2p_transaction_arr = array();
                if( isset( $response['MethodID'] ) )
                    $s2p_transaction_arr['method_id'] = $response['MethodID'];
                if( isset( $response['PaymentID'] ) )
                    $s2p_transaction_arr['payment_id'] = $response['PaymentID'];
                if( isset( $response['MerchantTransactionID'] ) )
                    $s2p_transaction_arr['merchant_transaction_id'] = $response['MerchantTransactionID'];
                if( isset( $response['SiteID'] ) )
                    $s2p_transaction_arr['site_id'] = $response['SiteID'];

                $s2p_transaction_extra_arr = array();
                $s2p_default_transaction_extra_arr = $s2pTransactionLogger::defaultTransactionLoggerExtraParams();
                foreach( $s2p_default_transaction_extra_arr as $key => $val )
                {
                    if( array_key_exists( $key, $response ) )
                        $s2p_transaction_extra_arr[$key] = $response[$key];
                }

                $s2pTransactionLogger->write( $s2p_transaction_arr, $s2p_transaction_extra_arr );

                // NotificationType IS payment
                if( strtolower( $response['NotificationType'] ) == 'payment' )
                {
                    // prepare string for 'da hash
                    $responseHashString = "notificationTypePaymentPaymentId".$response['PaymentID'].$payMethod->method_config['signature'];

                    // prepare response data
                    $responseData = array(
                        'NotificationType' => 'Payment',
                        'PaymentID' => $response['PaymentID'],
                        'Hash' => $s2pHelper->computeSHA256Hash( $responseHashString )
                    );

                    // output response
                    echo 'NotificationType=payment&PaymentID='.$responseData['PaymentID'].'&Hash='.$responseData['Hash'];
                }
            }
        } catch ( Exception $e )
        {
            $s2pLogger->write( $e->getMessage(), 'exception' );
        }

        $s2pLogger->write( '::: END HANDLE RESPONSE <<<', 'info' );
    }
    
    public function informCustomer(Mage_Sales_Model_Order $order, $amount, $currency)
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        try
        {
            /** @var $order Mage_Sales_Model_Order */
            /** @var $store_obj Mage_Core_Model_Store */
            /**
             * get data for template
             */
            $store_obj = $order->getStore();

            $siteUrl = $store_obj->getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK ); // Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK );
            $siteName = Mage::app()->getWebsite( $store_obj->getWebsiteId() )->getName();

            $supportEmail = Mage::getStoreConfig( 'trans_email/ident_support/email', $order->getStoreId() );
            $supportName = Mage::getStoreConfig( 'trans_email/ident_support/name', $order->getStoreId() );

            $localeCode = Mage::getStoreConfig( 'general/locale/code', $order->getStoreId() );

            $templateId = Mage::getStoreConfig( self::XML_PATH_EMAIL_PAYMENT_CONFIRMATION, $order->getStoreId() );

            $order_increment_id = $order->getRealOrderId();


            /** @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate = Mage::getModel('core/email_template');
            if (is_numeric($templateId)) { // loads from database @table core_email_template
                $mailTemplate->load($templateId);
            } else {
                $mailTemplate->loadDefault($templateId, $localeCode);
            }

            if( !($subject = $mailTemplate->getTemplateSubject()) )
                $subject = $this->__( 'Payment confirmation', $order_increment_id );

            $subject = $siteName.' - '.$subject;

            $mailTemplate->setSenderName( $supportName );
            $mailTemplate->setSenderEmail( $supportEmail );

            $mailTemplate->setTemplateSubject( $subject );

            if( !$mailTemplate->send( $order->getCustomerEmail(), $order->getCustomerName(), array(
                    'site_url' => $siteUrl,
                    'order_increment_id' => $order->getRealOrderId(),
                    'site_name' => $siteName,
                    'customer_name' => $order->getCustomerName(),
                    'order_date' => $order->getCreatedAtDate(),
                    'total_paid' => number_format(($amount / 100), 2),
                    'currency' => $currency,
                    'support_email' => $supportEmail
                )
            ) )
                $s2pLogger->write( 'Error sending customer informational email to ['.$order->getCustomerEmail().']', 'email_template' );

        } catch (Exception $e) {
            Mage::getModel('globalpay/logger')->write($e->getMessage(), 'exception');
        }
    }

    // http://demo.smart2pay.com/_redirectURLs/success.html?
    // data=2
    // &MerchantTransactionID=14915837935
    // &ReferenceNumber=HPP1527936
    // &AmountToPay=1RON
    // &AccountHolder=SC+SMART2PAY+SRL
    // &BankName=BRD+Groupe+Societe+Generale
    // &AccountNumber=SV67447622400
    // &IBAN=RO16BRDE240SV67447622400
    // &SWIFT_BIC=BRDEROBUXXX
    // &AccountCurrency=RON
    // &Hash=188f6a5bd3d0ac9b421fbc5453a52e58a46e44120daf306f3c694bda3fc65469
    public function infoAction()
    {
        $query = $this->getRequest()->getParams();

        if( !isset( $query['data'] ) )
        {
            // $this->_redirectUrl( Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK ) );
            $query['data'] = 0;
        }

        /** @var Mage_Checkout_Model_Session $session_obj */
        $session_obj = Mage::getSingleton( 'checkout/session' );
        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel( 'globalpay/pay' );
        $query = $this->getRequest()->getQuery();

        if( empty( $query['data'] ) )
            $query['data'] = 0;

        $data = $query['data'];

        if( $data == $payMethod::S2P_STATUS_SUCCESS
         or $data == $payMethod::S2P_STATUS_CAPTURED )
        {
            if( !empty( $payMethod->method_config['message_data_' . $payMethod::S2P_STATUS_SUCCESS] ) )
                $session_obj->addSuccess( $payMethod->method_config['message_data_' . $payMethod::S2P_STATUS_SUCCESS] );

            // session_write_close();

            if( isset( $query['ReferenceNumber'] ) )
            {
                /**
                 * Order details sent in backend notification
                 *
                $payment_details_arr = self::defaultPaymentDetailsParams();

                if( isset( $query['ReferenceNumber'] ) )
                    $payment_details_arr['reference_number'] = $query['ReferenceNumber'];
                if( isset( $query['AmountToPay'] ) )
                    $payment_details_arr['amount_to_pay'] = $query['AmountToPay'];
                if( isset( $query['AccountHolder'] ) )
                    $payment_details_arr['account_holder'] = $query['AccountHolder'];
                if( isset( $query['BankName'] ) )
                    $payment_details_arr['bank_name'] = $query['BankName'];
                if( isset( $query['AccountNumber'] ) )
                    $payment_details_arr['account_number'] = $query['AccountNumber'];
                if( isset( $query['AccountCurrency'] ) )
                    $payment_details_arr['account_currency'] = $query['AccountCurrency'];
                if( isset( $query['SWIFT_BIC'] ) )
                    $payment_details_arr['swift_bic'] = $query['SWIFT_BIC'];
                if( isset( $query['IBAN'] ) )
                    $payment_details_arr['iban'] = $query['IBAN'];
                if( isset( $query['EntityNumber'] ) )
                    $payment_details_arr['entity_number'] = $query['EntityNumber'];

                if( isset( $query['MerchantTransactionID'] ) )
                {
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId( $query['MerchantTransactionID'] );

                    // Inform customer
                    if( $payMethod->method_config['notify_payment_instructions'] )
                        $this->sendPaymentDetails( $order, $payment_details_arr );
                }
                /**/

                // session_write_close();
                $this->_redirect( 'checkout/onepage/success', $query ); //send the payment details further to onepage/success
            } else
            {
                $this->_redirect( 'checkout/onepage/success' );
            }

		} elseif( in_array( $data, array( $payMethod::S2P_STATUS_CANCELLED, $payMethod::S2P_STATUS_FAILED ) ) )
        {
            if( !empty( $payMethod->method_config['message_data_'.$data] ) )
                $session_obj->addError( $payMethod->method_config['message_data_'.$data] );

            // session_write_close();

            $this->_redirect( 'checkout/onepage/failure' );
        } else
        {
            if( !empty( $payMethod->method_config['message_data_7'] ) )
                $session_obj->addNotice( $payMethod->method_config['message_data_7'] );

            // session_write_close();

            $this->_redirect( 'checkout/onepage/success' );
        }
    }

    public function sendPaymentDetails( Mage_Sales_Model_Order $order, $payment_details_arr )
    {
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel('globalpay/pay');
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
            and $s2p_transaction_arr['method_id'] == $payMethod::PAYMENT_METHOD_SIBS )
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

}

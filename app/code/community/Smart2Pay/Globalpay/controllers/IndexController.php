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
        // check if there is an submited order and a 
        if( isset( $_SESSION['s2p_handle_payment'] ) )
        {
            unset( $_SESSION['s2p_handle_payment'] );
            $this->loadLayout();
            $this->renderLayout();

	        Mage::getModel('globalpay/logger')->write('>>> Redirect OK :::', 'info');
        } else
        {
 	        Mage::getModel('globalpay/logger')->write('>>> Redirect NOT OK, session empty. :::', 'info');

            $this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . 'checkout/cart/');
        }
    }    

    /** 
     * Process s2p response
     * Expected response content: {
     * "NotificationType":"payment",
     * "MethodID":"27",
     * "PaymentID":"18899",
     * "MerchantTransactionID":"926927",
     * "StatusID":"2",
     * "Amount":"100",
     * "Currency":"EUR",
     * "Hash":"fb9810cb1ac334092aa1f3033be20127676244b343f1ef2ffb447b9a8ced04ba"}
     */
    // MethodID: 20:
    // NotificationType=Payment&
    // MethodID=20&
    // PaymentID=1528069&
    // MerchantTransactionID=100000017&
    // StatusID=1&
    // Amount=2500&
    // Currency=USD&
    // SiteID=30112&
    // ReferenceNumber=000 887 453&
    // EntityNumber=11302&
    // AmountToPay=22.03 EUR

    // MethodID: 1:
    // NotificationType=Payment&
    // MethodID=1&
    // PaymentID=1528073&
    // MerchantTransactionID=100000018&
    // StatusID=1&
    // Amount=2500&
    // Currency=USD&
    // SiteID=30112&
    // ReferenceNumber=HPP1528073&
    // AmountToPay=88.76RON&
    // AccountHolder=SC SMART2PAY SRL&
    // BankName=BRD Groupe Societe Generale&
    // AccountNumber=SV67447622400&
    // IBAN=RO16BRDE240SV67447622400&
    // SWIFT_BIC=BRDEROBUXXX&
    // AccountCurrency=RON&
    // Hash=07d258fcce37e3be92b251cd4fe047ae6e6e6388a6d565e70d0b42d17851bb6f
    public function handleResponseAction()
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        $s2pLogger->write( '>>> START HANDLE RESPONSE :::', 'info' );

        /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
        $s2pHelper = Mage::helper( 'globalpay/helper' );
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel( 'globalpay/pay' );
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel( 'sales/order' );

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
                $order->addStatusHistoryComment( 'Smart2Pay :: notification received:<br>' . $raw_input );

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
                        and $orderCurrency != $response['Currency'] )
                            $order->addStatusHistoryComment( 'Smart2Pay :: notification has different amount ['.$orderAmount.'/'.$response['Amount'] . '] and/or currency ['.$orderCurrency.'/' . $response['Currency'] . ']!. Please contact support@smart2pay.com', $payMethod->method_config['order_status_on_4'] );

                        else
                        {
                            $order->addStatusHistoryComment( 'Smart2Pay :: order has been paid. [MethodID: '. $response['MethodID'] .']', $payMethod->method_config['order_status_on_2'] );

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

                                    $order->addStatusHistoryComment('Smart2Pay :: order has been automatically invoiced.', $payMethod->method_config['order_status_on_2']);
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
                                    $order->addStatusHistoryComment( 'Smart2Pay :: order has been automatically shipped.', $payMethod->method_config['order_status_on_2'] );
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
                        $order->addStatusHistoryComment( 'Smart2Pay :: payment has been canceled.', $payMethod->method_config['order_status_on_3'] );

                        if( !$order->canCancel() )
                            $s2pLogger->write('Can not cancel the order', 'warning');

                        else
                            $order->cancel();
                    break;

                    // Status = failed
                    case $payMethod::S2P_STATUS_FAILED:
                        $order->addStatusHistoryComment( 'Smart2Pay :: payment has failed.', $payMethod->method_config['order_status_on_4'] );
                    break;

                    // Status = expired
                    case $payMethod::S2P_STATUS_EXPIRED:
                        $order->addStatusHistoryComment( 'Smart2Pay :: payment has expired.', $payMethod->method_config['order_status_on_5'] );
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
        try
        {
            /** @var $order Mage_Sales_Model_Order */
            /**
             * get data for template
             */
            $siteUrl = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK );
            $siteName = Mage::app()->getWebsite(1)->getName();

            $subject = $siteName.' - Payment confirmation';

            $supportEmail = Mage::getStoreConfig('trans_email/ident_support/email');
            $supportName = Mage::getStoreConfig('trans_email/ident_support/name');

            $localeCode = Mage::getStoreConfig('general/locale/code', $order->getStoreId());

            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_PAYMENT_CONFIRMATION);


            /** @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate = Mage::getModel('core/email_template');
            if (is_numeric($templateId)) { // loads from database @table core_email_template
                $mailTemplate->load($templateId);
            } else {
                $mailTemplate->loadDefault($templateId, $localeCode);
            }

            $mailTemplate->setSenderName($supportName);
            $mailTemplate->setSenderEmail($supportEmail);
            $mailTemplate->setTemplateSubject('Payment Confirmation');
            $mailTemplate->setTemplateSubject($subject);

            $mailTemplate->send($order->getCustomerEmail(), $order->getCustomerName(), array(
                    'site_url' => $siteUrl,
                    'order_increment_id' => $order->getRealOrderId(),
                    'site_name' => $siteName,
                    'customer_name' => $order->getCustomerName(),
                    'order_date' => $order->getCreatedAtDate(),
                    'total_paid' => number_format(($amount / 100), 2),
                    'currency' => $currency,
                    'support_email' => $supportEmail
                )
            );
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
            $this->_redirectUrl( Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK ) );
            $query['data'] = 0;
        }

        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel('globalpay/pay');
        $query = $this->getRequest()->getQuery();

        if( empty( $query['data'] ) )
            $query['data'] = 0;

        $data = $query['data'];

        if( $data == $payMethod::S2P_STATUS_SUCCESS )
        {
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

                session_write_close();
                $this->_redirect('checkout/onepage/success', $query ); //send the payment details further to onepage/success
            } else
            {
                if( !empty( $payMethod->method_config['message_data_' . $data] ) )
                    Mage::getSingleton('checkout/session')->addSuccess( $payMethod->method_config['message_data_' . $data] );

                session_write_close();
                $this->_redirect('checkout/onepage/success');
            }
		} elseif( in_array( $data, array( $payMethod::S2P_STATUS_CANCELLED, $payMethod::S2P_STATUS_FAILED ) ) )
        {
            if( !empty( $payMethod->method_config['message_data_'.$data] ) )
                Mage::getSingleton( 'checkout/session' )->addError( $payMethod->method_config['message_data_'.$data] );
            session_write_close();
            $this->_redirect( 'checkout/cart' );
        } else
        {
            if( !empty( $payMethod->method_config['message_data_7'] ) )
                Mage::getSingleton('checkout/session')->addNotice( $payMethod->method_config['message_data_7'] );
            session_write_close();
            $this->_redirect('checkout/onepage/success');
        }
    }

    static function defaultPaymentDetailsParams()
    {
        return array(
            'reference_number' => 0,
            'amount_to_pay' => 0,
            'account_holder' => '',
            'bank_name' => '',
            'account_number' => '',
            'account_currency' => '',
            'swift_bic' => '',
            'iban' => '',
            'entity_number' => '',
        );
    }

    static function validatePaymentDetailsParams( $query_arr )
    {
        if( empty( $query_arr ) or !is_array( $query_arr ) )
            $query_arr = array();

        $default_values = self::defaultPaymentDetailsParams();
        foreach( $default_values as $key => $val )
        {
            if( !array_key_exists( $key, $query_arr ) )
                $query_arr[$key] = $val;
        }

        return $query_arr;
    }

    public function sendPaymentDetails( Mage_Sales_Model_Order $order, $payment_details_arr )
    {
        $payment_details_arr = self::validatePaymentDetailsParams( $payment_details_arr );

        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $payMethod */
        $payMethod = Mage::getModel('globalpay/pay');
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        try
        {
            /** @var $order Mage_Sales_Model_Order */
            /**
             * get data for template
             */
            $siteUrl = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_LINK );
            $siteName = Mage::app()->getWebsite(1)->getName();

            $order_increment_id = $order->getRealOrderId();

            $subject = $siteName." - ".$this->__( 'PaymentInstructionsSubject', $order_increment_id );

            $supportEmail = Mage::getStoreConfig( 'trans_email/ident_support/email' );
            $supportName = Mage::getStoreConfig( 'trans_email/ident_support/name' );

            $localeCode = Mage::getStoreConfig( 'general/locale/code', $order->getStoreId() );

            if( ($s2p_transaction_arr = $s2pTransactionLogger->getTransactionDetailsAsArray( $order_increment_id ))
            and $s2p_transaction_arr['method_id'] == $payMethod::PAYMENT_METHOD_SIBS )
            {
                $s2pLogger->write( '['.self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS_SIBS.']', 'email_template' );
                $templateId = Mage::getStoreConfig( self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS_SIBS );
            } else
            {
                $s2pLogger->write( '['.self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS.']', 'email_template' );
                $templateId = Mage::getStoreConfig( self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS );
            }

            /** @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate = Mage::getModel( 'core/email_template' );
            if( is_numeric( $templateId ) )
                // loads from database @table core_email_template
                $mailTemplate->load( $templateId );
            else
                $mailTemplate->loadDefault( $templateId, $localeCode );

            $mailTemplate->setSenderName( $supportName );
            $mailTemplate->setSenderEmail( $supportEmail );
            $mailTemplate->setTemplateSubject( $this->__('PaymentInstructions') );
            $mailTemplate->setTemplateSubject( $subject );

            // Extra details
            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtDate();
            $payment_details_arr['support_email'] = $supportEmail;

            if( !$mailTemplate->send( $order->getCustomerEmail(), $order->getCustomerName(), $payment_details_arr ) )
                $s2pLogger->write( 'Error sending payment instructions email to ['.$order->getCustomerEmail().']', 'email_template' );

        } catch( Exception $e )
        {
            $s2pLogger->write( $e->getMessage(), 'exception' );
        }
    }

}

<?php
class Smart2pay_Globalpay_IndexController extends Mage_Core_Controller_Front_Action{

    const XML_PATH_EMAIL_PAYMENT_CONFIRMATION = 'payment/globalpay/payment_confirmation_template';
    const XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS = 'payment/globalpay/payment_instructions_template';

    /**
     * Loads s2p in iFrame
     * If !isset($_SESSION['s2p_handle_payment']) redirect to cart/checkout
     */
    public function indexAction()
    {
        // check if there is an submited order and a 
        if(isset($_SESSION['s2p_handle_payment'])){ 
            unset($_SESSION['s2p_handle_payment']);
            $this->loadLayout();
            $this->renderLayout();
	    Mage::getModel('globalpay/logger')->write('>>> Redirect OK :::', 'info');
	
        }
        else{
 	    Mage::getModel('globalpay/logger')->write('>>> Redirect NOT OK, session empty. :::', 'info');

            $this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . 'checkout/cart/');

        }        
    }    

    /** 
     * Process s2p response
     * Expected response content: {"NotificationType":"payment","MethodID":"27","PaymentID":"18899","MerchantTransactionID":"926927","StatusID":"2","Amount":"100","Currency":"EUR","Hash":"fb9810cb1ac334092aa1f3033be20127676244b343f1ef2ffb447b9a8ced04ba"}
     */
    public function handleResponseAction()
    {
        Mage::getModel('globalpay/logger')->write('>>> START HANDLE RESPONSE :::', 'info');

        // get assets
            /* @var Mage_Sales_Model_Order $order
             * @var Smart2Pay_Globalpay_Model_Pay $payMethod
             */
        $s2pHelper = Mage::helper('globalpay/helper');
        $payMethod = Mage::getModel('globalpay/pay');
        $order = Mage::getModel('sales/order');

        try {
            $raw_input = file_get_contents("php://input");
	        parse_str($raw_input, $response);
		
            $vars = array();
            $recomposedHashString = '';
            if(!empty($raw_input)){
            $pairs    = explode("&", $raw_input);
            foreach ($pairs as $pair) {
                $nv                = explode("=", $pair);
                $name            = $nv[0];
                $vars[$name]    = $nv[1];
                if(strtolower($name) != 'hash'){
                    $recomposedHashString .= $name . $vars[$name];
                }
            }
	   }

	    $recomposedHashString .= $payMethod->method_config['signature'];

            Mage::getModel('globalpay/logger')->write('NotificationRecevied:\"' . $raw_input . '\"', 'info');

            // Message is intact
            if($s2pHelper->computeSHA256Hash($recomposedHashString) == $response['Hash']){

                Mage::getModel('globalpay/logger')->write('Hashes match', 'info');

                $order->loadByIncrementId($response['MerchantTransactionID']);
                $order->addStatusHistoryComment('Smart2Pay :: notification received:<br>' . $raw_input);
                /**
                 * Check status ID
                 */
                switch($response['StatusID']){
                    // Status = success
                    case "2":
                        // cheking amount  and currency
                    $orderAmount =  number_format($order->getGrandTotal(), 2, '.', '') * 100;
                    $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

                    if( strcmp($orderAmount,$response['Amount']) == 0 && $orderCurrency == $response['Currency']){
                            $order->addStatusHistoryComment('Smart2Pay :: order has been paid. [MethodID:'. $response['MethodID'] .']', $payMethod->method_config['order_status_on_2'] );
                            if ($payMethod->method_config['auto_invoice']) {
                                // Create and pay Order Invoice
                                if($order->canInvoice()) {
                                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                                    $invoice->register();
                                    $transactionSave = Mage::getModel('core/resource_transaction')
                                        ->addObject($invoice)
                                        ->addObject($invoice->getOrder());
                                    $transactionSave->save();
                                    $order->addStatusHistoryComment('Smart2Pay :: order has been automatically invoiced.', $payMethod->method_config['order_status_on_2']);
                                } else {
                                    Mage::getModel('globalpay/logger')->write('Order can not be invoiced', 'warning');
                                }
                            }
                            if ($payMethod->method_config['auto_ship']) {
                                if ($order->canShip()) {
                                    $itemQty =  $order->getItemsCollection()->count();
                                    $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
                                    $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                    $shipmentId = $shipment->create($order->getIncrementId());
                                    $order->addStatusHistoryComment('Smart2Pay :: order has been automatically shipped.', $payMethod->method_config['order_status_on_2']);
                                } else {
                                    Mage::getModel('globalpay/logger')->write('Order can not be shipped', 'warning');
                                }
                            }
                            if ($payMethod->method_config['notify_customer']) {
                                // Inform customer
                                $this->informCustomer($order, $response['Amount'], $response['Currency']);
                            }
                    }
                    else{
                        $order->addStatusHistoryComment('Smart2Pay :: notification has different amount['.$orderAmount.'/'.$response['Amount'] . '] and/or currency['.$orderCurrency.'/' . $response['Currency'] . ']!. Please contact support@smart2pay.com', $payMethod->method_config['order_status_on_4']);
                    }
                    break;
                    // Status = canceled
                    case 3:
                        $order->addStatusHistoryComment('Smart2Pay :: payment has been canceled.', $payMethod->method_config['order_status_on_3']);
                        if ($order->canCancel()) {
                            $order->cancel();
                        } else {
                            Mage::getModel('globalpay/logger')->write('Can not cancel the order', 'warning');
                        }
                        break;
                    // Status = failed
                    case 4:
                        $order->addStatusHistoryComment('Smart2Pay :: payment has failed.', $payMethod->method_config['order_status_on_4']);
                        break;
                    // Status = expired
                    case 5:
                        $order->addStatusHistoryComment('Smart2Pay :: payment has expired.', $payMethod->method_config['order_status_on_5']);
                        break;

                    default:
                        $order->addStatusHistoryComment('Smart2Pay status "'.$response['StatusID'].'" occurred.', $payMethod->method_config['order_status']);
                        break;
                }

                $order->save();

                // NotificationType IS payment
                if(strtolower($response['NotificationType']) == 'payment'){
                    // prepare string for 'da hash
                    $responseHashString = "notificationTypePaymentPaymentId".$response['PaymentID'].$payMethod->method_config['signature'];
                    // prepare response data
                    $responseData = array(
                        'NotificationType' => 'Payment',
                        'PaymentID' => $response['PaymentID'],
                        'Hash' => $s2pHelper->computeSHA256Hash($responseHashString)
                    );
                    // output response
                    echo "NotificationType=payment&PaymentID=".$responseData['PaymentID']."&Hash=".$responseData['Hash'];
                }
            }
            else{
                Mage::getModel('globalpay/logger')->write('Hashes do not match (received:' . $response['Hash'] . ')(recomposed:' . $s2pHelper->computeSHA256Hash($recomposedHashString) . ')', 'warning');
            }
        } catch (Exception $e) {
            Mage::getModel('globalpay/logger')->write($e->getMessage(), 'exception');
        }
        Mage::getModel('globalpay/logger')->write('::: END HANDLE RESPONSE <<<', 'info');
    }
    
    public function informCustomer(Mage_Sales_Model_Order $order, $amount, $currency)
    {
        try{
            /** @var $order Mage_Sales_Model_Order */
            /**
             * get data for template
             */
            $siteUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $siteName = Mage::app()->getWebsite(1)->getName();

            $subject = $siteName." - Payment confirmation";

            $supportEmail = Mage::getStoreConfig('trans_email/ident_support/email');
            $supportName = Mage::getStoreConfig('trans_email/ident_support/name');

            $localeCode = Mage::getStoreConfig('general/locale/code', $order->getStoreId());

            $storeId = Mage::app()->getStore()->getStoreId();

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

    public function infoAction()
    {
	
        $query = $this->getRequest()->getParams();

        if (!isset($query['data'])) {
            $this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK));
        }
	
		$paymethod = Mage::getModel('globalpay/pay');
        $query = $this->getRequest()->getQuery();
        $data = $query['data'];
		
		if ($data == 2){
            if(isset($query['ReferenceNumber'])) {
                $referenceNumber = $query['ReferenceNumber'];

                if (isset($query['AmountToPay'])) {
                    $amountToPay = $query['AmountToPay'];
                }
                if (isset($query['AccountHolder'])) {
                    $accountHolder = $query['AccountHolder'];
                }
                if (isset($query['BankName'])) {
                    $bankName = $query['BankName'];
                }
                if (isset($query['AccountNumber'])) {
                    $accountNumber = $query['AccountNumber'];
                }
                if (isset($query['SWIFT_BIC'])) {
                    $SWIFT_BIC = $query['SWIFT_BIC'];
                }
                if (isset($query['AccountCurrency'])) {
                    $accountCurrency = $query['AccountCurrency'];
                }

                if (isset($query['MerchantTransactionID'])) {
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($query['MerchantTransactionID']);
                    //$this->sendPaymentDetails($order, $referenceNumber, $amountToPay, $accountHolder, $bankName, $accountNumber, $SWIFT_BIC, $accountCurrency);
                    if ($paymethod->method_config['notify_payment_instructions']) {
                        // Inform customer
                        $this->sendPaymentDetails($order, $referenceNumber, $amountToPay, $accountHolder, $bankName, $accountNumber, $SWIFT_BIC, $accountCurrency);
                    }

                }
                session_write_close();
                $this->_redirect('checkout/onepage/success', $query); //send the payment details further to onepage/success
            }
            else{
                Mage::getSingleton('checkout/session')->addSuccess($paymethod->method_config['message_data_' . $data]);
                session_write_close();
                $this->_redirect('checkout/onepage/success');
            }
		}
		else if (in_array($data, array(3, 4))) {
				Mage::getSingleton('checkout/session')->addError($paymethod->method_config['message_data_' . $data]);
				session_write_close();
				$this->_redirect('checkout/cart');
			} 
		else {
				Mage::getSingleton('checkout/session')->addSuccess($paymethod->method_config['message_data_7']);
					session_write_close();
					$this->_redirect('checkout/onepage/success');
			}
    }

    public function sendPaymentDetails(Mage_Sales_Model_Order $order, $referenceNumber, $amountToPay, $accountHolder, $bankName, $accountNumber, $SWIFT_BIC, $accountCurrency)
    {
        try{
            /** @var $order Mage_Sales_Model_Order */
            /**
             * get data for template
             */
            $siteUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $siteName = Mage::app()->getWebsite(1)->getName();

            $subject = $siteName." - ".$this->__('PaymentInstructions');

            $supportEmail = Mage::getStoreConfig('trans_email/ident_support/email');
            $supportName = Mage::getStoreConfig('trans_email/ident_support/name');

            $localeCode = Mage::getStoreConfig('general/locale/code', $order->getStoreId());

            $storeId = Mage::app()->getStore()->getStoreId();

            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_PAYMENT_INSTRUCTIONS);


            /** @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate = Mage::getModel('core/email_template');
            if (is_numeric($templateId)) { // loads from database @table core_email_template
                $mailTemplate->load($templateId);
            } else {
                $mailTemplate->loadDefault($templateId, $localeCode);
            }

            $mailTemplate->setSenderName($supportName);
            $mailTemplate->setSenderEmail($supportEmail);
            $mailTemplate->setTemplateSubject($this->__('PaymentInstructions'));
            $mailTemplate->setTemplateSubject($subject);

            $mailTemplate->send($order->getCustomerEmail(), $order->getCustomerName(), array(
                    'site_url' => $siteUrl,
                    'order_increment_id' => $order->getRealOrderId(),
                    'site_name' => $siteName,
                    'customer_name' => $order->getCustomerName(),
                    'order_date' => $order->getCreatedAtDate(),
                    'reference_number' => $referenceNumber,
                    'amount_to_pay' => $amountToPay,
                    'account_holder' => $accountHolder,
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'swift_bic' => $SWIFT_BIC,
                    'account_currency' => $accountCurrency,
                    'support_email' => $supportEmail
                )
            );
        } catch (Exception $e) {
            Mage::getModel('globalpay/logger')->write($e->getMessage(), 'exception');
        }
    }

}

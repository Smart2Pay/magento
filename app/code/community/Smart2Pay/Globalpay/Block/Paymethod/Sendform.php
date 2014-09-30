<?php
class Smart2Pay_Globalpay_Block_Paymethod_Sendform extends Mage_Core_Block_Template{
        public $form_data;
        public $message_to_hash;
        public $hash;
        
        public function __construct() {
            parent::__construct();

            /** @var Mage_Sales_Model_Order $order */
            $paymentModel = Mage::getModel('globalpay/pay');
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId(); 
            $order = Mage::getModel('sales/order');
            $order->load($order_id);
            $order_id = $order->getRealOrderId();
                        
            // FORM DATA
            $this->form_data = $paymentModel->method_config;
            $this->form_data['method_id'] = $_SESSION['globalpay_method'];
            $this->form_data['order_id'] = $order_id;
            $this->form_data['currency'] = $order->getOrderCurrency()->getCurrencyCode();
            $this->form_data['amount'] = number_format($order->getGrandTotal(), 2, '.', '') * 100;
                
            //anonymous user, get the info from billing details
            if($order->getCustomerId() === NULL){
                $this->form_data['customer_last_name'] = substr(trim($order->getBillingAddress()->getLastname()),0,30);
                $this->form_data['customer_first_name'] = substr(trim($order->getBillingAddress()->getFirstname()),0,30);
                $this->form_data['customer_name'] = substr(trim($this->form_data['customer_first_name'] . ' ' . $this->form_data['customer_last_name']),0,30);
            }
            //else, they're a normal registered user.
            else {
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                $this->form_data['customer_name'] = substr(trim($order->getCustomerName()),0,30);
                $this->form_data['customer_last_name'] = substr(trim($customer->getDefaultBillingAddress()->getLastname()),0,30);
                $this->form_data['customer_first_name'] = substr(trim($customer->getDefaultBillingAddress()->getFirstname()),0,30);
            }
                
            $this->form_data['customer_email'] = trim($order->getCustomerEmail());
            $this->form_data['country'] = $order->getBillingAddress()->getCountry();
                

            $messageToHash = 'MerchantID'.$this->form_data['mid']
                                .'MerchantTransactionID'.$this->form_data['order_id']
                                .'Amount'.$this->form_data['amount']
                                .'Currency'.$this->form_data['currency']
                                .'ReturnURL'.$this->form_data['return_url'];
            if(!$this->form_data['method_id']){
                            $messageToHash .= 'IncludeMethodIDs'.$this->form_data['methods'];
            }
            if($this->form_data['site_id']){
                $messageToHash .= 'SiteID'.$this->form_data['site_id'];
            }

            $messageToHash .= "CustomerName".$this->form_data['customer_name'];
            $messageToHash .= "CustomerLastName".$this->form_data['customer_last_name'];
            $messageToHash .= "CustomerFirstName".$this->form_data['customer_first_name'];
            $messageToHash .= "CustomerEmail".$this->form_data['customer_email'];
            $messageToHash .= "Country".$this->form_data['country'];
            $messageToHash .= "MethodID".$this->form_data['method_id'];


            if($this->form_data['product_description_ref']){
                $messageToHash .= "Description"."Ref. no.: ".$this->form_data['order_id'];
            }
            else{
                $messageToHash .= "Description".$this->form_data['product_description_custom'];
            }

            if($this->form_data['skip_payment_page']){
                if(!in_array($this->form_data['method_id'], array(1, 20)) || $this->form_data['notify_payment_instructions']){
                    $messageToHash .= "SkipHpp1";
                }
            }
            if($this->form_data['redirect_in_iframe']){
                $messageToHash .= "RedirectInIframe1";
            }
            if($this->form_data['skin_id']){
                $messageToHash .= "SkinID".$this->form_data['skin_id'];
            }


            $messageToHash .= $this->form_data['signature'];

		    Mage::getModel('globalpay/logger')->write($messageToHash, 'info');

            $this->form_data['hash'] = Mage::helper('globalpay/helper')->computeSHA256Hash($messageToHash);

            //
            $this->message_to_hash = $messageToHash;
            $this->hash = $this->form_data['hash'];
            //

            //send e-mail to customer about order creation before redirect to Smart2Pay
            try{
                    $order = new Mage_Sales_Model_Order();
                    $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
                    $order->loadByIncrementId($incrementId);
                    $order->sendNewOrderEmail();
            }
            catch (Exception $ex) {
            }

        }        
}
?>

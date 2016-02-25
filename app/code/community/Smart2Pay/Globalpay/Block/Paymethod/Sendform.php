<?php

class Smart2Pay_Globalpay_Block_Paymethod_Sendform extends Mage_Core_Block_Template
{
    public $form_data;
    public $message_to_hash;
    public $hash;

    public function __construct()
    {
        parent::__construct();

        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );
        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
        $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');

        if( !empty( $_SESSION['globalpay_method'] ) )
            $method_id = $_SESSION['globalpay_method'];
        else
            $method_id = 0;

        $order->load( $order_id );

        $merchant_transaction_id = $order->getRealOrderId();

        // assume live environment if we don't get something valid from config
        $environment = (!empty( $paymentModel->method_config['environment'] )?strtolower( trim( $paymentModel->method_config['environment'] ) ):'live');

        if( $environment == 'demo' )
        {
            $merchant_transaction_id = base_convert( time(), 10, 36 ).'_'.$merchant_transaction_id;
        }

        if( !($surcharge_amount = $order->getPayment()->getS2pSurchargeAmount()) )
            $surcharge_amount = 0;
        if( !($surcharge_fixed_amount = $order->getPayment()->getS2pSurchargeFixedAmount()) )
            $surcharge_fixed_amount = 0;

        $total_surcharge_amount = $surcharge_amount + $surcharge_fixed_amount;

        $order_original_amount = $amount_to_pay = $order->getGrandTotal();

        $articles_params = array();
        $articles_params['transport_amount'] = $order->getShippingAmount();
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
        $articles_diff = 0;
        if( ($articles_check = $helper_obj->cart_products_to_string( $order_products_arr, $original_amount, $articles_params )) )
        {
            $articles_str = $articles_check['buffer'];

            if( !empty( $articles_check['total_difference_amount'] )
                and $articles_check['total_difference_amount'] >= -0.01 and $articles_check['total_difference_amount'] <= 0.01 )
            {
                $articles_diff = $articles_check['total_difference_amount'];

                //if( $method_id == $paymentModel::PAYMENT_METHOD_KLARNA_CHECKOUT
                // or $method_id == $paymentModel::PAYMENT_METHOD_KLARNA_INVOICE )
                //    $amount_to_pay += $articles_diff;
            }
        }

        // FORM DATA
        $this->form_data = $paymentModel->method_config;

        $this->form_data['environment'] = $environment;

        $this->form_data['method_id'] = $method_id;

        $this->form_data['order_id'] = $merchant_transaction_id;
        $this->form_data['currency'] = $order->getOrderCurrency()->getCurrencyCode();
        $this->form_data['amount'] = number_format( $amount_to_pay, 2, '.', '' ) * 100; // number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;

        //anonymous user, get the info from billing details
        if( $order->getCustomerId() === NULL )
        {
            $this->form_data['customer_last_name'] = $helper_obj->s2p_mb_substr( $order->getBillingAddress()->getLastname(), 0, 30 );
            $this->form_data['customer_first_name'] = $helper_obj->s2p_mb_substr( $order->getBillingAddress()->getFirstname(), 0, 30 );
            $this->form_data['customer_name'] = $helper_obj->s2p_mb_substr( $this->form_data['customer_first_name'] . ' ' . $this->form_data['customer_last_name'], 0, 30 );
        }
        //else, they're a normal registered user.
        else
        {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $this->form_data['customer_name'] = $helper_obj->s2p_mb_substr( $order->getCustomerName(), 0, 30 );
            $this->form_data['customer_last_name'] = $helper_obj->s2p_mb_substr( $customer->getDefaultBillingAddress()->getLastname(), 0, 30 );
            $this->form_data['customer_first_name'] = $helper_obj->s2p_mb_substr( $customer->getDefaultBillingAddress()->getFirstname(), 0, 30 );
        }

        $this->form_data['customer_name'] = trim( $this->form_data['customer_name'] );
        $this->form_data['customer_last_name'] = trim( $this->form_data['customer_last_name'] );
        $this->form_data['customer_first_name'] = trim( $this->form_data['customer_first_name'] );

        $this->form_data['customer_email'] = trim($order->getCustomerEmail());
        $this->form_data['country'] = $order->getBillingAddress()->getCountry();

        $messageToHash = 'MerchantID'.$this->form_data['mid'].
                         'MerchantTransactionID'.$this->form_data['order_id'].
                         'Amount'.$this->form_data['amount'].
                         'Currency'.$this->form_data['currency'].
                         'ReturnURL'.$this->form_data['return_url'];

        if( !$this->form_data['method_id'] )
            $messageToHash .= 'IncludeMethodIDs'.$this->form_data['methods'];

        if( $this->form_data['site_id'] )
            $messageToHash .= 'SiteID'.$this->form_data['site_id'];

        $messageToHash .= 'CustomerName'.$this->form_data['customer_name'];
        $messageToHash .= 'CustomerLastName'.$this->form_data['customer_last_name'];
        $messageToHash .= 'CustomerFirstName'.$this->form_data['customer_first_name'];
        $messageToHash .= 'CustomerEmail'.$this->form_data['customer_email'];
        $messageToHash .= 'Country'.$this->form_data['country'];
        $messageToHash .= 'MethodID'.$this->form_data['method_id'];

        if( $this->form_data['product_description_ref'] )
            $messageToHash .= 'Description'.'Ref. no.: '.$this->form_data['order_id'];
        else
            $messageToHash .= 'Description'.$this->form_data['product_description_custom'];

        if( $this->form_data['skip_payment_page'] )
        {
            if( !in_array( $this->form_data['method_id'], array( $paymentModel::PAYMENT_METHOD_BT, $paymentModel::PAYMENT_METHOD_SIBS ) )
             or $this->form_data['notify_payment_instructions'] )
                $messageToHash .= 'SkipHpp1';
        }

        if( $this->form_data['redirect_in_iframe'] )
            $messageToHash .= 'RedirectInIframe1';

        if( $this->form_data['skin_id'] )
            $messageToHash .= 'SkinID'.$this->form_data['skin_id'];

        $this->form_data['articles'] = $articles_str;
        if( !empty( $articles_str ) )
            $messageToHash .= 'Articles'.$this->form_data['articles'];

        $messageToHash .= $this->form_data['signature'];

        $s2pLogger->write( 'Form hash: ['.$messageToHash.']', 'info' );

        $this->form_data['hash'] = $helper_obj->computeSHA256Hash( $messageToHash );

        $this->message_to_hash = $messageToHash;
        $this->hash = $this->form_data['hash'];

        $s2p_transaction_arr = array();
        if( !empty( $this->form_data['method_id'] ) )
            $s2p_transaction_arr['method_id'] = $this->form_data['method_id'];
        if( !empty( $merchant_transaction_id ) )
            $s2p_transaction_arr['merchant_transaction_id'] = $merchant_transaction_id;
        if( !empty( $this->form_data['site_id'] ) )
            $s2p_transaction_arr['site_id'] = $this->form_data['site_id'];
        $s2p_transaction_arr['environment'] = $environment;

        $s2pTransactionLogger->write( $s2p_transaction_arr );

        //send e-mail to customer about order creation before redirect to Smart2Pay
        try
        {
            $order = new Mage_Sales_Model_Order();
            $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $order->loadByIncrementId( $incrementId );
            $order->sendNewOrderEmail();
        } catch ( Exception $ex ) {
        }
    }

}

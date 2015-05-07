<?php

class Smart2Pay_Globalpay_Block_Info_Globalpay extends Mage_Payment_Block_Info
{
    protected $_display_lines = true;

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate( 'smart2pay/globalpay/methodinfo.phtml' );
    }

    /**
     * Render as PDF
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate( 'smart2pay/globalpay/methodinfopdf.phtml' );
        return $this->toHtml();
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation( $transport = null )
    {
        if( null !== $this->_paymentSpecificInformation )
            return $this->_paymentSpecificInformation;

        if( $transport === null )
            $transport = new Varien_Object;
        elseif( is_array( $transport ) )
            $transport = new Varien_Object( $transport );

        $transport = parent::_prepareSpecificInformation( $transport );

        /** @var Smart2Pay_Globalpay_Model_Pay $s2pPayModel */
        $s2pPayModel = Mage::getModel( 'globalpay/pay' );
        /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
        $s2pHelper = Mage::helper( 'globalpay/helper' );
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        $controller_module_name = $this->getRequest()->getControllerModule();
        $controller_name = $this->getRequest()->getControllerName();
        $action_name = $this->getRequest()->getActionName();
        $route_name = $this->getRequest()->getRouteName();
        $module_name = $this->getRequest()->getModuleName();

        //$s2pLogger->write( 'Called from ['.$controller_module_name.']['.$controller_name.']'.
        //                   '['.$action_name.']['.$route_name.']['.$module_name.']', 'payment_info' );

        $payment_info_arr = array();
        if( 'onepage' == $controller_name
        and 'checkout' == $module_name )
        {
            // Display details in checkout...
            $this->_display_lines = false;

            /** @var Mage_Checkout_Model_Session $chkout */
            if( empty( $_SESSION['globalpay_method'] )
             or !($chkout = Mage::getSingleton('checkout/session'))
             or !($quote = $chkout->getQuote())
             or !($billingAddress = $quote->getBillingAddress())
             or !($countryCode = $billingAddress->getCountryId())
             or !($countryId = Mage::getModel('globalpay/country')->load($countryCode, 'code')->getId()) )
                return $transport;

            /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
            $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

            $method_arr = false;
            if( ($methods_arr = $configured_methods_obj->get_configured_methods( $countryId, array( 'id_in_index' => true ) ))
            and is_array( $methods_arr )
            and !empty( $methods_arr[$_SESSION['globalpay_method']] ) )
                $method_arr = $methods_arr[$_SESSION['globalpay_method']];

            if( empty( $method_arr ) )
                $payment_info_arr['Payment Method'] = 'N/A ['.$_SESSION['globalpay_method'].']';
            else
                $payment_info_arr['Payment Method'] = $method_arr['display_name'];

            $info = $this->getInfo();

            $surcharge_amount = $info->getS2pSurchargeAmount();
            $surcharge_percent = $info->getS2pSurchargePercent();
            $surcharge_fixed_amount = $info->getS2pSurchargeFixedAmount();

            if( !empty( $method_arr['surcharge'] ) )
            {
                if( (int)$s2pPayModel->method_config['display_surcharge'] )
                {
                    if( ($surcharge_percent_label = $s2pHelper->format_surcharge_percent_label( $surcharge_amount, $surcharge_percent, array( 'use_translate' => false ) )) )
                        $payment_info_arr[$surcharge_percent_label] = $s2pHelper->format_surcharge_percent_value( $surcharge_amount, $surcharge_percent );

                    $surcharge_fixed_amount_str = $quote->getStore()->getCurrentCurrency()->format( $surcharge_fixed_amount, array(), false );

                    if( ($surcharge_percent_label = $s2pHelper->format_surcharge_fixed_amount_label( $surcharge_fixed_amount, array( 'use_translate' => false ) )) )
                        $payment_info_arr[$surcharge_percent_label] = $s2pHelper->format_surcharge_fixed_amount_value( $surcharge_fixed_amount_str,
                                                                            array(
                                                                                'format_price' => false,
                                                                                //'include_container' => false,
                                                                                //'format_currency' => $quote->getStore()->getCurrentCurrency(),
                                                                            ) );
                }

                $surcharge_total_amount = $surcharge_amount + $surcharge_fixed_amount;
                $surcharge_amount_str = $quote->getStore()->getCurrentCurrency()->format( $surcharge_total_amount, array(), false );

                if( ($surcharge_amount_label = $s2pHelper->format_surcharge_label( $surcharge_total_amount, $surcharge_percent, array( 'include_percent' => false, 'use_translate' => false ) )) )
                    $payment_info_arr[$surcharge_amount_label] = $s2pHelper->format_surcharge_value( $surcharge_amount_str, $surcharge_percent,
                                                                    array(
                                                                        'format_price' => false,
                                                                        //'include_container' => false,
                                                                        //'format_currency' => $quote->getStore()->getCurrentCurrency(),
                                                                        ) );
            }
        } else /*if( in_array( $controller_name, array( 'sales_order', 'sales_order_invoice', 'sales_order_shipment', 'sales_order_creditmemo', 'order' ) )
            or $controller_module_name == 'Smart2Pay_Globalpay' ) */
        {
            if( $action_name == 'print' )
                $this->_display_lines = false;

            // display details when in view order...

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel( 'sales/order' );
            /** @var Smart2Pay_Globalpay_Model_Resource_Method_Collection $s2pMethodCollection */
            $s2pMethodCollection = Mage::getModel( 'globalpay/method' )->getCollection();
            /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
            $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );

            if( !($info = $this->getInfo())
             or empty( $info['parent_id'] )
             or !$order->load( $info['parent_id'] )
             or !($merchant_transaction_id = $order->getIncrementId()) )
                return $transport;

            $payment_info_arr = array();

            if( !($s2p_transaction_arr = $s2pTransactionLogger->getTransactionDetailsAsArray( $merchant_transaction_id )) )
                return $transport;

            if( empty( $s2p_transaction_arr['method_id'] ) )
                $payment_info_arr['Payment Method'] = 'N/A';

            else
            {
                $method_arr = false;
                $s2pMethodCollection->addFieldToSelect( '*' )
                                    ->addFieldToFilter( 'method_id', $s2p_transaction_arr['method_id'] );
                if( ($methods_list_arr = $s2pMethodCollection->getData())
                and is_array( $methods_list_arr ) )
                    $method_arr = array_pop( $methods_list_arr );

                if( empty( $method_arr ) )
                    $payment_info_arr['Payment Method'] = 'N/A';
                else
                    $payment_info_arr['Payment Method'] = $method_arr['display_name'];
            }

            if( ($payment = $order->getPayment())
            and ($payment->getS2pSurchargeAmount() or $payment->getS2pSurchargeFixedAmount()) )
            {
                $surcharge_amount = $payment->getS2pSurchargeAmount();
                $surcharge_percent = $payment->getS2pSurchargePercent();
                $surcharge_fixed_amount = $payment->getS2pSurchargeFixedAmount();

                if( $s2pHelper->isAdmin()
                 or (int)$s2pPayModel->method_config['display_surcharge'] )
                {
                    if( ($surcharge_percent_label = $s2pHelper->format_surcharge_percent_label( $surcharge_amount, $surcharge_percent, array( 'use_translate' => false ) )) )
                        $payment_info_arr[$surcharge_percent_label] = $s2pHelper->format_surcharge_percent_value( $surcharge_amount, $surcharge_percent );

                    $surcharge_fixed_amount_str = $order->getOrderCurrency()->format( $surcharge_fixed_amount, array(), false );

                    if( ($surcharge_percent_label = $s2pHelper->format_surcharge_fixed_amount_label( $surcharge_fixed_amount, array( 'use_translate' => false ) )) )
                        $payment_info_arr[$surcharge_percent_label] = $s2pHelper->format_surcharge_fixed_amount_value( $surcharge_fixed_amount_str,
                                                                            array(
                                                                                'format_price' => false,
                                                                                //'include_container' => false,
                                                                                //'format_currency' => $quote->getStore()->getCurrentCurrency(),
                                                                            ) );
                }

                $surcharge_total_amount = $surcharge_amount + $surcharge_fixed_amount;
                $surcharge_amount_str = $order->getOrderCurrency()->format( $surcharge_total_amount, array(), false );

                if( ($surcharge_amount_label = $s2pHelper->format_surcharge_label( $surcharge_total_amount, $surcharge_percent, array( 'include_percent' => false, 'use_translate' => false ) )) )
                    $payment_info_arr[$surcharge_amount_label] = $s2pHelper->format_surcharge_value( $surcharge_amount_str, $surcharge_percent,
                                                                    array(
                                                                        'format_price' => false,
                                                                        //'include_container' => false,
                                                                        //'format_currency' => $quote->getStore()->getCurrentCurrency(),
                                                                    ) );
            }

            if( ($controller_name == 'order'
                    and in_array( $action_name, array( 'view' ) ))
            or ($controller_name == 'sales_order'
                    and in_array( $action_name, array( 'view', 'email' ) ))
            or ($controller_name == 'sales_order_creditmemo'
                    and in_array( $action_name, array( 'new', 'view', 'email' ) ))
              )
            {
                if( $s2pHelper->isAdmin() )
                {
                    $payment_info_arr['Environment'] = ( ! empty( $s2p_transaction_arr['environment'] ) ? ucfirst( $s2p_transaction_arr['environment'] ): 'N/A' );
                    $payment_info_arr['PaymentID'] = ( ! empty( $s2p_transaction_arr['payment_id'] ) ? $s2p_transaction_arr['payment_id'] : 'N/A' );
                    $payment_info_arr['SiteID']    = ( ! empty( $s2p_transaction_arr['site_id'] ) ? $s2p_transaction_arr['site_id'] : 'N/A' );

                    if( ! empty( $s2p_transaction_arr['created'] ) )
                        $payment_info_arr['Created'] = $this->formatDate( $s2p_transaction_arr['created'], 'medium', true );
                    else
                        $payment_info_arr['Created'] = 'N/A';

                    if( ! empty( $s2p_transaction_arr['updated'] ) )
                        $payment_info_arr['Last Update'] = $this->formatDate( $s2p_transaction_arr['updated'], 'medium', true );
                    else
                        $payment_info_arr['Last Update'] = 'N/A';
                }

                if( ! empty( $s2p_transaction_arr['extra_data'] )
                    and ( $extra_data_arr = $s2pHelper->parse_string( $s2p_transaction_arr['extra_data'] ) )
                )
                {
                    foreach( $extra_data_arr as $key => $val )
                        $payment_info_arr[ $this->__( $key ) ] = $val;
                }
            }
        }

        if( !empty( $payment_info_arr ) )
            $transport->addData( $payment_info_arr );

        return $transport;
    }

}

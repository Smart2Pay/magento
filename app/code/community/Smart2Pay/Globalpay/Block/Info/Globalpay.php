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

        $no_information_available_arr = array( $this->__( 'No information available' ) => '' );

        $payment_info_arr = array();
        if( 'checkout' == $this->getRequest()->getRouteName()
        and 'checkout' == $this->getRequest()->getModuleName() )
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

            if( !empty( $method_arr['surcharge'] )
            and (int)$s2pPayModel->method_config['display_surcharge'] )
            {
                $payment_info_arr['Payment Method'] .= ' ('.($method_arr['surcharge']>0?'+':'').$method_arr['surcharge'].'%)';
            }

            $info = $this->getInfo();

            $payment_info_arr['Surcharge'] = $info->getS2pSurchargePercent().'%';

            $amount_str = $quote->getStore()->formatPrice( $info->getS2pSurchargeAmount(), false );

            $payment_info_arr['Surcharge Amount'] = $amount_str;
        } else
        {
            // display details when in view order...

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel( 'sales/order' );
            /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
            $s2pHelper = Mage::helper( 'globalpay/helper' );
            /** @var Smart2Pay_Globalpay_Model_Resource_Method_Collection $s2pMethodCollection */
            $s2pMethodCollection = Mage::getModel( 'globalpay/method' )->getCollection();
            /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
            $s2pLogger = Mage::getModel( 'globalpay/logger' );
            /** @var Smart2Pay_Globalpay_Model_Transactionlogger $s2pTransactionLogger */
            $s2pTransactionLogger = Mage::getModel( 'globalpay/transactionlogger' );

            try
            {
                if( ! ($info = $this->getInfo())
                    or empty( $info['entity_id'] )
                    or !$order->load( $info['entity_id'] )
                    or !($merchant_transaction_id = $order->getIncrementId()) )
                    return $transport;

                if( !($s2p_transaction_arr = $s2pTransactionLogger->getTransactionDetailsAsArray( $merchant_transaction_id )) )
                    return $transport->addData( $no_information_available_arr );

                $payment_info_arr = array();

                if( empty( $s2p_transaction_arr['method_id'] ) )
                    $payment_info_arr['Payment Method'] = 'N/A';

                else
                {
                    $method_arr = false;
                    $s2pMethodCollection->addFieldToSelect( '*' )
                                        ->addFieldToFilter( 'method_id', $s2p_transaction_arr['method_id'] );
                    if( ( $methods_list_arr = $s2pMethodCollection->getData() )
                        and is_array( $methods_list_arr )
                    )
                        $method_arr = array_pop( $methods_list_arr );

                    if( empty( $method_arr ) )
                        $payment_info_arr['Payment Method'] = 'N/A';
                    else
                        $payment_info_arr['Payment Method'] = $method_arr['display_name'];
                }

                if( $s2pHelper->isAdmin() )
                {
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
            } catch( Exception $e )
            {
                $s2pLogger->write( 'Exception (' . $e->getMessage() . ')', 'trans_logger' );
                //$transport->addData( $no_information_available_arr );
            }
        }

        if( !empty( $payment_info_arr ) )
            $transport->addData( $payment_info_arr );

        return $transport;
    }

}

<?php

class Smart2Pay_Globalpay_Block_Paymethod_Form extends Mage_Payment_Block_Form
{
            
    public $method_config = array();
    
    protected function _construct()
    {
        parent::_construct();        
        // set template
        $this->setTemplate('smart2pay/globalpay/paymethod/form.phtml');

        // set method config
        $this->method_config = Mage::getModel('globalpay/pay')->method_config;
    }
    
    public function getPaymentMethods()
    {
        /** @var Mage_Checkout_Model_Session $chkout */
        if( !($chkout = Mage::getSingleton('checkout/session'))
         or !($quote = $chkout->getQuote()) )
            return array();

        if( !($billingAddress = $quote->getBillingAddress())
         or !($countryCode = $billingAddress->getCountryId())
         or !($countryId = Mage::getModel('globalpay/country')->load($countryCode, 'code')->getId()) )
            return $this->__( 'Couldn\'t obtain country from billing address.' );


        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

        return $configured_methods_obj->get_configured_methods( $countryId );
        /**
         *
         * OLD way...
         *
        $pay_method = Mage::getModel('globalpay/pay');

        $collection = Mage::getModel('globalpay/countrymethod')->getCollection();
        $collection->addFieldToSelect('*');
        $collection->addFieldToFilter('country_id', array(
            'in' => array($countryId)
        ));
        $collection->addFieldToFilter('s2p_gp_methods.method_id', array(
            'in' => explode(",", $pay_method->method_config['methods'])
        ));
        $collection->addFieldToFilter('active', array(
            'in' => array(1)
        ));
        $collection->getSelect()->join(
                's2p_gp_methods',
                's2p_gp_methods.method_id = main_table.method_id'
        );
        $collection->setOrder('priority', 'ASC');
        return $collection->getData();
         *
        **/
    }
}

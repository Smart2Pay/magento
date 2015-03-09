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
        $pay_method = Mage::getModel('globalpay/pay');
        $chkout = Mage::getSingleton('checkout/session');
        $quote = $chkout->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $countryCode = $billingAddress->getCountryId();
        $countryId = Mage::getModel('globalpay/country')->load($countryCode, 'code')->getId();
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
    }
}

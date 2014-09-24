<?php
class Smart2Pay_Globalpay_Model_Countrymethod extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/countrymethod_collection';
    
    protected function _construct()
    {
        $this->_init('globalpay/countrymethod');
    }
} 
?>

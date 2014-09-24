<?php
class Smart2Pay_Globalpay_Model_Country extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/country_collection';
    
    protected function _construct()
    {
        $this->_init('globalpay/country');
    }
} 
?>

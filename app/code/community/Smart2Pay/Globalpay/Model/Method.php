<?php
class Smart2Pay_Globalpay_Model_Method extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/method_collection';
            
    protected function _construct()
    {
        $this->_init('globalpay/method');
    }
       
}

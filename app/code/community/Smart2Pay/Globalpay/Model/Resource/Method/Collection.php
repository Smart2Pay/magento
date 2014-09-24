<?php
class Smart2Pay_Globalpay_Model_Resource_Method_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    protected function _construct() {
        $this->_init('globalpay/method');
    }

}
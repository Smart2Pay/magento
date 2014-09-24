<?php
class Smart2Pay_Globalpay_Model_Resource_Method extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct() {
        $this->_init('globalpay/method', 'method_id');
    }

}
?>

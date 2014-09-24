<?php
class Smart2Pay_Globalpay_Model_Resource_Logger extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct() {
        $this->_init('globalpay/logger', 'logger_id');
    }

}
?>

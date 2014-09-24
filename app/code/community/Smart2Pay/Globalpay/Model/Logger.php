<?php

class Smart2Pay_Globalpay_Model_Logger extends Mage_Core_Model_Abstract
{
    /**
     * Todo:
     *      - set loggin true/false
     *      - set log_type logged
     */

    protected $_resourceCollectionName = 'globalpay/logger_collection';

    protected function _construct()
    {
        $this->_init('globalpay/logger');
    }

    public function write($message = '', $type = 'info', $file = '', $line = '') {
        try {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');

            $backtrace = debug_backtrace();
            $file = $backtrace[0]['file'];
            $line = $backtrace[0]['line'];

            $query = 'INSERT INTO s2p_gp_logs
                        (log_message, log_type, log_source_file, log_source_file_line)
                      VALUES
                        (\'' . $message . '\', \'' . $type . '\', \'' . $file . '\', \'' . $line . '\')
            ';
            $conn->query($query);
        } catch (Exception $e) {
            Zend_Debug::dump($e->getMessage());
            die;
        }
    }

}
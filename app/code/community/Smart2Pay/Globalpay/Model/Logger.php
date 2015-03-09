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

    public function write( $message = '', $type = 'info', $file = '', $line = '' )
    {
        try
        {
            /** @var Magento_Db_Adapter_Pdo_Mysql $conn */
            if( !($conn = Mage::getSingleton('core/resource')->getConnection('core_write')) )
                return false;

            if( empty( $file ) or empty( $line ) )
            {
                $backtrace = debug_backtrace();
                $file = $backtrace[0]['file'];
                $line = $backtrace[0]['line'];
            }

            $insert_arr = array();
            $insert_arr['log_message'] = $message;
            $insert_arr['log_type'] = $type;
            $insert_arr['log_source_file'] = $file;
            $insert_arr['log_source_file_line'] = $line;

            $conn->insert( 's2p_gp_logs', $insert_arr );

        } catch( Exception $e )
        {
            Zend_Debug::dump($e->getMessage());
            die;
        }

        return true;
    }

}

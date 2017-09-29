<?php

class Smart2Pay_Globalpay_Model_Resource_Logger extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init( 'globalpay/logger', 'log_id' );
    }

    public function write( $message, $type = 'info', $transaction_id = 0, $file = '', $line = '' )
    {
        if( empty( $message ) )
            return true;

        try
        {
            /** @var Varien_Db_Adapter_Interface $conn */
            if( !($conn = $this->_getWriteAdapter()) )
                return false;

            if( empty( $file ) or empty( $line ) )
            {
                $backtrace = debug_backtrace();
                if( !empty( $backtrace[1] ) )
                {
                    $file = $backtrace[1]['file'];
                    $line = $backtrace[1]['line'];
                }
            }

            $insert_arr = array();
            $insert_arr['log_message'] = $message;
            $insert_arr['log_type'] = $type;
            $insert_arr['transaction_id'] = $transaction_id;
            $insert_arr['log_source_file'] = (strlen( $file )>255?substr( $file, 0, 254 ):$file);
            $insert_arr['log_source_file_line'] = $line;

            $conn->insert( $this->getTable( 'globalpay/logger' ), $insert_arr );

        } catch( Exception $e )
        {
            Zend_Debug::dump($e->getMessage());
            die;
        }

        return true;
    }

}


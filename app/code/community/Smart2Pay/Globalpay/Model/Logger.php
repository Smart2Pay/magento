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

    public function write( $message, $type = 'info', $transaction_id = 0, $file = '', $line = '' )
    {
        /** @var Smart2Pay_Globalpay_Model_Resource_Logger $my_resource */
        if( !($my_resource = $this->getResource()) )
            return false;

        return $my_resource->write( $message, $type, $transaction_id, $file, $line );
    }

}

<?php

class Smart2Pay_Globalpay_Block_Info extends Mage_Core_Block_Template
{
    const S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PROCESSING = 7;

    public $message;

    public function __construct()
    {
        $this->message = $this->getMessage();
        parent::__construct();
    }

    private function getMessage()
    {
        /**@var $paymethod Smart2Pay_Globalpay_Model_Pay */
        $paymethod = Mage::getModel('globalpay/pay');
        $query = $this->getRequest()->getQuery();

        if( empty( $query ) or !is_array( $query ) )
            $query = array();

        if( empty( $query['data'] ) )
            $query['data'] = 0;

        if( in_array( $query['data'], array( self::S2P_STATUS_SUCCESS, self::S2P_STATUS_CANCELLED, self::S2P_STATUS_FAILED, self::S2P_STATUS_PROCESSING ) ) )
            return $paymethod->method_config['message_data_' . $query['data']];

        return $paymethod->method_config['message_data_7'];
    }
}
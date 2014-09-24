<?php

class Smart2Pay_Globalpay_Block_Info extends Mage_Core_Block_Template
{
    public $message;

    public function __construct()
    {
        $this->message = $this->getMessage();
        parent::__construct();
    }

    private function getMessage()
    {
        /**@var $paymethod Smart2Pay_Globalpay_Model_Paymethod*/
        $paymethod = Mage::getModel('globalpay/pay');
        $query = $this->getRequest()->getQuery();
        $data = $query['data'];

        if (in_array($data, array(2, 3, 4, 7))) {
            return $paymethod->method_config['message_data_' . $data];
        } else {
            return $paymethod->method_config['message_data_7'];
        }
    }
}
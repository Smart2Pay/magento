<?php

class Smart2Pay_Globalpay_Block_Paymentinstructions extends Mage_Checkout_Block_Onepage_Success
{
    public $display_params = array();

    protected function _construct()
    {
        parent::_construct();

        /** @var Smart2Pay_Globalpay_Model_Transactionlogger $transactions_logger_obj */
        $transactions_logger_obj = Mage::getModel( 'globalpay/transactionlogger' );

        $this->display_params = array();
        if( ($params_arr = $transactions_logger_obj::defaultTransactionLoggerExtraParams()) )
        {
            foreach( $params_arr as $key => $def_val )
            {
                if( ($req_val = $this->getRequest()->getParam( $key, $def_val )) === $def_val )
                    continue;

                $this->display_params[$key] = $req_val;
            }
        }
    }
}

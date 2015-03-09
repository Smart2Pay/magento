<?php

class Smart2Pay_Globalpay_Model_Transactionlogger extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/transactionlogger_collection';

    protected function _construct()
    {
        $this->_init( 'globalpay/transactionlogger' );
    }

    static function defaultTransactionLoggerExtraParams()
    {
        return array(
            // Method ID 1 (Bank transfer)
            'AccountHolder' => '',
            'BankName' => '',
            'AccountNumber' => '',
            'IBAN' => '',
            'SWIFT_BIC' => '',
            'AccountCurrency' => '',

            // Method ID 20 (Multibanco SIBS)
            'EntityNumber' => '',

            // Common to method id 20 and 1
            'ReferenceNumber' => '',
            'AmountToPay' => '',
        );
    }

    static function validateTransactionLoggerExtraParams( $params_arr )
    {
        if( empty( $params_arr ) or !is_array( $params_arr ) )
            return array();

        $default_values = self::defaultTransactionLoggerExtraParams();
        $new_params_arr = array();
        foreach( $default_values as $key => $val )
        {
            if( !array_key_exists( $key, $params_arr ) )
                continue;

            if( is_int( $val ) )
                $new_val = intval( $params_arr[$key] );
            elseif( is_string( $val ) )
                $new_val = trim( $params_arr[$key] );
            else
                $new_val = $params_arr[$key];

            if( $new_val === $val )
                continue;

            $new_params_arr[$key] = $new_val;
        }

        return $new_params_arr;
    }

    static function defaultTransactionLoggerParams()
    {
        return array(
            'method_id' => 0,
            'payment_id' => 0,
            'merchant_transaction_id' => '',
            'site_id' => 0,
            'extra_data' => '',
        );
    }

    static function validateTransactionLoggerParams( $params_arr )
    {
        if( empty( $params_arr ) or !is_array( $params_arr ) )
            $params_arr = array();

        $default_values = self::defaultTransactionLoggerParams();
        $new_params_arr = array();
        foreach( $default_values as $key => $val )
        {
            if( !array_key_exists( $key, $params_arr ) )
            {
                $new_params_arr[$key] = $val;
                continue;
            }

            if( is_int( $val ) )
                $new_val = intval( $params_arr[$key] );
            elseif( is_string( $val ) )
                $new_val = trim( $params_arr[$key] );
            else
                $new_val = $params_arr[$key];

            $new_params_arr[$key] = $new_val;
        }

        return $new_params_arr;
    }

    /**
     *
     * @var string $merchant_transaction_id
     * @return bool|array
     */
    public function getTransactionDetailsAsArray( $merchant_transaction_id )
    {
        if( empty( $merchant_transaction_id ) )
            return false;

        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );
        /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
        $s2pHelper = Mage::helper( 'globalpay/helper' );

        $s2p_transaction_arr = false;
        try
        {
            /** @var Magento_Db_Adapter_Pdo_Mysql $conn_read */
            if( !($conn_read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' ))
                or !($s2p_transaction_list_arr = $conn_read->fetchAssoc( $conn_read->select()
                    ->from( 's2p_gp_transactions' )
                    ->where( 'merchant_transaction_id = \''.$s2pHelper->prepare_data( $merchant_transaction_id ).'\'' )
                    ->limit( 1, 0 ) ))
                or !is_array( $s2p_transaction_list_arr )
                or !($s2p_transaction_arr = array_pop( $s2p_transaction_list_arr )) )
                $s2p_transaction_arr = false;
        } catch( Exception $e )
        {
            $s2pLogger->write( 'Exception ('.$e->getMessage().')', 'trans_logger' );
        }

        return $s2p_transaction_arr;
    }

    public function write( $transaction_arr, array $extra_params = array() )
    {
        /** @var Smart2Pay_Globalpay_Helper_Helper $s2pHelper */
        $s2pHelper = Mage::helper( 'globalpay/helper' );
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        if( !($transaction_extra_arr = self::validateTransactionLoggerExtraParams( $extra_params ))
         or !is_array( $transaction_extra_arr ) )
            $transaction_extra_str = '';
        else
            $transaction_extra_str = $s2pHelper->to_string( $transaction_extra_arr );

        $transaction_arr['extra_data'] = $transaction_extra_str;

        if( !($insert_arr = self::validateTransactionLoggerParams( $transaction_arr )) )
            return false;

        $insert_arr['updated'] = new Zend_Db_Expr('NOW()');

        try
        {
            /** @var Magento_Db_Adapter_Pdo_Mysql $conn_write */
            /** @var Magento_Db_Adapter_Pdo_Mysql $conn_read */
            if( !($conn_write = Mage::getSingleton('core/resource')->getConnection( 'core_write' ))
             or !($conn_read = Mage::getSingleton('core/resource')->getConnection( 'core_read' )) )
                return false;

            if( !empty( $transaction_arr['merchant_transaction_id'] )
            and ($existing_id = $conn_read->fetchOne( 'SELECT id FROM s2p_gp_transactions WHERE merchant_transaction_id = \''.$s2pHelper->prepare_data( $transaction_arr['merchant_transaction_id'] ).'\' LIMIT 0, 1' )) )
            {
                // we should update record
                $conn_write->update( 's2p_gp_transactions', $insert_arr, 'id = \''.$existing_id.'\'' );

                $s2pLogger->write( 'Update transaction ['.$existing_id.']', 'trans_logger' );
            } else
            {
                // we should insert record
                //$conn_write->query( $sql );
                $conn_write->insert( 's2p_gp_transactions', $insert_arr );

                $s2pLogger->write( 'Insert transaction', 'trans_logger' );
            }
        } catch ( Exception $e )
        {
            $s2pLogger->write( 'Exception ('.$e->getMessage().')', 'trans_logger' );
            return false;
        }

        return $insert_arr;
    }

}

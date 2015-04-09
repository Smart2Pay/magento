<?php

class Smart2Pay_Globalpay_Model_Resource_Configuredmethods extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('globalpay/configuredmethods', 'id');
    }

    public function truncate_table()
    {
        $this->_getWriteAdapter()->truncateTable( $this->getMainTable() );
    }

    /**
     * @param Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $collection
     *
     * @return bool
     */
    public function delete_from_collection( Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $collection )
    {
        if( !($collection instanceof Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection) )
            return false;

        if( !($it = $collection->getIterator()) )
            return false;

        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $item */
        foreach( $it as $item )
            $item->delete();

        return true;
    }

    /**
     * @param int $method_id
     * @param int $country_id
     * @param array $params
     *
     * @return bool
     */
    public function insert_or_update( $method_id, $country_id, $params )
    {
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_write */
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_read */
        $method_id = intval( $method_id );
        $country_id = intval( $country_id );
        if( empty( $method_id )
         or empty( $params ) or !is_array( $params )
         or !($conn_read = $this->_getReadAdapter())
         or !($conn_write = $this->_getWriteAdapter()) )
            return false;

        if( empty( $params['surcharge'] ) )
            $params['surcharge'] = 0;
        if( empty( $params['fixed_amount'] ) )
            $params['fixed_amount'] = 0;

        $insert_arr = array();
        $insert_arr['surcharge'] = $params['surcharge'];
        $insert_arr['fixed_amount'] = $params['fixed_amount'];

        try
        {
            if( ( $existing_id = $conn_read->fetchOne( 'SELECT id FROM ' . $this->getMainTable() . ' WHERE method_id = \'' . $method_id . '\' AND country_id = \'' . $country_id . '\' LIMIT 0, 1' ) ) )
            {
                // we should update record
                $conn_write->update( $this->getMainTable(), $insert_arr, 'id = \'' . $existing_id . '\'' );
            } else
            {
                $insert_arr['method_id']  = $method_id;
                $insert_arr['country_id'] = $country_id;

                $conn_write->insert( $this->getMainTable(), $insert_arr );

            }
        } catch( Zend_Db_Adapter_Exception $e )
        {
            /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
            $s2pLogger = Mage::getModel( 'globalpay/logger' );

            $s2pLogger->write( 'DB Error ['.$e->getMessage().']', 'configured_method' );
            return false;
        }

        return true;
    }
}

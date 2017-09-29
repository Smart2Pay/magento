<?php

class Smart2Pay_Globalpay_Model_Resource_Method extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('globalpay/method', 'id');
    }

    /**
     * @param Smart2Pay_Globalpay_Model_Resource_Method_Collection $collection
     *
     * @return bool
     */
    public function delete_from_collection( Smart2Pay_Globalpay_Model_Resource_Method_Collection $collection )
    {
        if( !($collection instanceof Smart2Pay_Globalpay_Model_Resource_Method_Collection) )
            return false;

        if( !($it = $collection->getIterator()) )
            return false;

        /** @var Smart2Pay_Globalpay_Model_Method $item */
        foreach( $it as $item )
            $item->delete();

        return true;
    }

    /**
     * @param int $method_id
     * @param string $environment
     * @param array $params
     *
     * @return bool|array
     */
    public function insert_or_update( $method_id, $environment, $params )
    {
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_write */
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_read */
        $method_id = intval( $method_id );
        $environment = strtolower( trim( $environment ) );
        if( empty( $method_id )
         or empty( $params ) or !is_array( $params )
         or empty( $environment ) or !in_array( $environment, array( 'demo', 'test', 'live' ) )
         or !($conn_read = $this->_getReadAdapter())
         or !($conn_write = $this->_getWriteAdapter()) )
            return false;

        try
        {
            if( ( $method_arr = $conn_read->fetchAssoc( 'SELECT * FROM ' . $this->getMainTable() . ' WHERE method_id = \'' . $method_id . '\' AND environment = \'' . $environment . '\' LIMIT 0, 1' ) ) )
            {
                // we should update record
                $conn_write->update( $this->getMainTable(), $params, 'id = \'' . $method_arr['id'] . '\'' );

                foreach( $params as $key => $val )
                {
                    if( array_key_exists( $key, $method_arr ) )
                        $method_arr[$key] = $val;
                }
            } else
            {
                if( empty( $params['display_name'] ) )
                    return false;

                $method_arr = $params;
                $method_arr['method_id'] = $method_id;
                $method_arr['environment'] = $environment;

                $conn_write->insert( $this->getMainTable(), $method_arr );

                $method_arr['id'] = $conn_write->lastInsertId();
            }
        } catch( Zend_Db_Adapter_Exception $e )
        {
            /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
            $s2pLogger = Mage::getModel( 'globalpay/logger' );

            $s2pLogger->write( 'DB Error ['.$e->getMessage().']', 'methods' );
            return false;
        }

        return $method_arr;
    }

}


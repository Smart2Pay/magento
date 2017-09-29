<?php

class Smart2Pay_Globalpay_Model_Resource_Countrymethod extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('globalpay/countrymethod', 'id');
    }

    /**
     * @param Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $collection
     *
     * @return bool
     */
    public function delete_from_collection( Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $collection )
    {
        if( !($collection instanceof Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection) )
            return false;

        if( !($it = $collection->getIterator()) )
            return false;

        /** @var Smart2Pay_Globalpay_Model_Countrymethod $item */
        foreach( $it as $item )
            $item->delete();

        return true;
    }

    /**
     * @param int $method_id
     * @param int $country_id
     * @param string $environment
     * @param int $priority
     *
     * @return bool|array
     */
    public function insert_or_update( $method_id, $country_id, $environment, $priority = 0 )
    {
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_write */
        /** @var Magento_Db_Adapter_Pdo_Mysql $conn_read */
        $method_id = intval( $method_id );
        $country_id = intval( $country_id );
        $environment = strtolower( trim( $environment ) );
        if( empty( $method_id ) or empty( $country_id )
         or empty( $environment ) or !in_array( $environment, array( 'demo', 'test', 'live' ) )
         or !($conn_read = $this->_getReadAdapter())
         or !($conn_write = $this->_getWriteAdapter()) )
            return false;

        try
        {
            if( ($country_method_arr = $conn_read->fetchAssoc( 'SELECT * FROM ' . $this->getMainTable() .
                                                               ' WHERE method_id = \'' . $method_id . '\' AND country_id = \'' . $country_id . '\' AND environment = \'' . $environment . '\''.
                                                               ' LIMIT 0, 1' ) ) )
            {
                $params = array();
                $params['priority'] = $priority;

                // we should update record
                $conn_write->update( $this->getMainTable(), $params, 'id = \'' . $country_method_arr['id'] . '\'' );

                foreach( $params as $key => $val )
                {
                    if( array_key_exists( $key, $country_method_arr ) )
                        $country_method_arr[$key] = $val;
                }
            } else
            {
                $country_method_arr = array();
                $country_method_arr['method_id'] = $method_id;
                $country_method_arr['country_id'] = $country_id;
                $country_method_arr['environment'] = $environment;
                $country_method_arr['priority'] = $priority;

                $conn_write->insert( $this->getMainTable(), $country_method_arr );

                $country_method_arr['id'] = $conn_write->lastInsertId();
            }
        } catch( Zend_Db_Adapter_Exception $e )
        {
            /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
            $s2pLogger = Mage::getModel( 'globalpay/logger' );

            $s2pLogger->write( 'DB Error ['.$e->getMessage().']', 'countries_methods' );
            return false;
        }

        return $country_method_arr;
    }
}

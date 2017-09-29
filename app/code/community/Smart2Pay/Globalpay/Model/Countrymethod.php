<?php

class Smart2Pay_Globalpay_Model_Countrymethod extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/countrymethod_collection';
    
    protected function _construct()
    {
        $this->_init('globalpay/countrymethod');
    }

    public function delete_country_methods_for_environment( $environment, $method_id = 0 )
    {
        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod $my_resource */
        $my_resource = $this->getResource();

        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter( 'environment', $environment );
        if( !empty( $method_id ) )
            $my_collection->addFieldToFilter( 'method_id', $method_id );

        return $my_resource->delete_from_collection( $my_collection );
    }

    /**
     * @param int $method_id
     * @param array $countries_arr
     * @param string $environment
     * @param bool|array $params
     *
     * @return bool|string
     */
    public function update_method_countries( $method_id, $countries_arr, $environment, $params = false )
    {
        /** @var Smart2Pay_Globalpay_Model_Country $country_obj */
        $country_obj = Mage::getModel('globalpay/country');
        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod $my_resource */
        $my_resource = $this->getResource();
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['delete_before_update'] ) )
            $params['delete_before_update'] = true;

        $method_id = intval( $method_id );
        $environment = strtolower( trim( $environment ) );
        if( empty( $method_id )
         or empty( $environment ) or !in_array( $environment, array( 'demo', 'test', 'live' ) ) )
            return 'Bad parameters when updating method countries.';

        if( !($db_countries_arr = $country_obj->get_countries_code_as_key()) )
        {
            $s2pLogger->write( 'Couldn\'t retrieve countries from database.', 'update_method_countries' );

            return 'Couldn\'t retrieve countries from database.';
        }

        if( !empty( $params['delete_before_update'] )
        and !$this->delete_country_methods_for_environment( $environment, $method_id ) )
        {
            $s2pLogger->write( 'Couldn\'t delete existing method countries.', 'update_method_countries' );

            return 'Couldn\'t delete existing method countries.';
        }

        foreach( $countries_arr as $country )
        {
            $country = strtoupper( trim( $country ) );
            if( empty( $db_countries_arr[$country] ) )
                continue;

            if( !$my_resource->insert_or_update( $method_id, $db_countries_arr[$country], $environment ) )
            {
                $s2pLogger->write( 'Couldn\'t update method countries for method #'.$method_id.'.', 'update_method_countries' );

                $this->delete_country_methods_for_environment( $environment, $method_id );

                return 'Couldn\'t update method countries for method #'.$method_id.'.';
            }
        }

        return true;
    }
}


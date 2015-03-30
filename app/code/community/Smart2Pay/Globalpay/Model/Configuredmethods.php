<?php
class Smart2Pay_Globalpay_Model_Configuredmethods extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/configuredmethods_collection';
            
    protected function _construct()
    {
        $this->_init('globalpay/configuredmethods');
    }

    public function get_all_methods()
    {
        static $return_arr = false;

        if( !empty( $return_arr ) )
            return $return_arr;

        /** @var Smart2Pay_Globalpay_Model_Resource_Method_Collection $methods_collection */
        $methods_collection = Mage::getModel( 'globalpay/method' )->getCollection();

        $methods_collection->addFieldToSelect( array( 'method_id', 'display_name', 'description', 'logo_url' ) );
        $methods_collection->addFieldToFilter( 'active', 1 );
        $methods_collection->setOrder( 'display_name', 'ASC' );

        $return_arr = array();

        while( ($method_obj = $methods_collection->fetchItem())
               and ($method_arr = $method_obj->getData()) )
        {
            if( empty( $method_arr['method_id'] ) )
                continue;

            $return_arr[$method_arr['method_id']] = $method_arr;

            $return_arr[$method_arr['method_id']]['countries_list'] = $this->get_countries_for_method( $method_arr['method_id'] );
        }

        return $return_arr;
    }

    public function get_countries_for_method( $method_id )
    {
        $method_id = intval( $method_id );
        if( empty( $method_id ) )
            return array();

        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $methodcountries_collection */
        $methodcountries_collection = Mage::getModel( 'globalpay/countrymethod' )->getCollection();

        $methodcountries_collection->addFieldToSelect( '*' );

        $methodcountries_collection->addFieldToFilter( 'method_id', $method_id );

        $methodcountries_collection->getSelect()->join(
            's2p_gp_countries',
            's2p_gp_countries.country_id = main_table.country_id' );

        $methodcountries_collection->setOrder( 's2p_gp_countries_methods.priority', 'ASC' );

        $return_arr = array();

        while( ($country_obj = $methodcountries_collection->fetchItem())
               and ($country_arr = $country_obj->getData()) )
        {
            if( empty( $country_arr['country_id'] ) )
                continue;

            $return_arr[$country_arr['country_id']] = $country_arr;
        }

        return $return_arr;

    }

    public function get_configured_methods( $country_id, $params = false )
    {
        $country_id = intval( $country_id );
        if( empty( $country_id ) )
            return array();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['id_in_index'] ) )
            $params['id_in_index'] = false;

        // 1. get a list of methods available for provided country
        // 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        // 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)

        //
        // START 1. get a list of methods available for provided country
        //

        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $cm_collection */
        $cm_collection = Mage::getModel( 'globalpay/countrymethod' )->getCollection();
        $cm_collection->addFieldToSelect( '*' );
        $cm_collection->addFieldToFilter( 'country_id', $country_id );

        $cm_collection->getSelect()->join(
            's2p_gp_methods',
            's2p_gp_methods.method_id = main_table.method_id'
        );

        $cm_collection->setOrder( 'priority', 'ASC' );

        $methods_arr = array();
        $method_ids_arr = array();
        $enabled_method_ids_arr = array();


        while( ($method_obj = $cm_collection->fetchItem())
               and ($method_arr = $method_obj->getData()) )
        {
            if( empty( $method_arr['method_id'] ) )
                continue;

            $method_ids_arr[] = $method_arr['method_id'];
            $methods_arr[$method_arr['method_id']] = $method_arr;
        }

        //
        // END 1. get a list of methods available for provided country
        //

        //
        // START 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        //
        /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect( '*' );
        $my_collection->addFieldToFilter( 'country_id', 0 );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        //

        //
        // START 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)
        //
        /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect( '*' );
        $my_collection->addFieldToFilter( 'country_id', $country_id );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)
        //

        // clean methods array of methods that are not enabled
        $methods_result = array();
        foreach( $methods_arr as $method_id => $method_arr )
        {
            if( empty( $enabled_method_ids_arr[$method_id] ) )
                continue;

            if( empty( $params['id_in_index'] ) )
                $methods_result[] = $method_arr;
            else
                $methods_result[$method_id] = $method_arr;
        }

        return $methods_result;
    }

    function save_configured_methods( $configured_methods_arr )
    {
        if( empty( $configured_methods_arr ) or !is_array( $configured_methods_arr ) )
            return false;

        foreach( $configured_methods_arr as $method_id => $configured_method_arr )
        {
            $method_id = intval( $method_id );
            if( empty( $method_id )
             or empty( $configured_method_arr ) or !is_array( $configured_method_arr ) )
                continue;
        }
    }

}

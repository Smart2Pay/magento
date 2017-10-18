<?php
class Smart2Pay_Globalpay_Model_Configuredmethods extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/configuredmethods_collection';
            
    protected function _construct()
    {
        $this->_init('globalpay/configuredmethods');
    }

    public function get_all_methods( $params = false )
    {
        static $cached_return_arr = false;

        // Cache result for default parameters
        if( empty( $params ) and !empty( $cached_return_arr ) )
            return $cached_return_arr;

        $cache_result = false;
        if( empty( $params ) )
            $cache_result = true;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();
        if( !isset( $params['method_ids'] ) or !is_array( $params['method_ids'] ) )
            $params['method_ids'] = false;
        if( !isset( $params['include_countries'] ) )
            $params['include_countries'] = true;
        if( empty( $params['order_by'] ) or !in_array( $params['order_by'], array( 'display_name', 'method_id' ) ) )
            $params['order_by'] = 'display_name';

        if( !isset( $params['environment'] ) or empty( $params['environment'] ) )
        {
            /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
            $paymentModel = Mage::getModel('globalpay/pay');

            $params['environment'] = $paymentModel->getEnvironment();
        }

        // we received an empty array of ids, so we should return empty result...
        if( is_array( $params['method_ids'] ) and empty( $params['method_ids'] ) )
            return array();

        $method_ids_arr = false;
        if( !empty( $params['method_ids'] ) )
        {
            $method_ids_arr = array();
            foreach( $params['method_ids'] as $method_id )
            {
                $method_id = intval( $method_id );
                if( empty( $method_id ) )
                    continue;

                $method_ids_arr[] = $method_id;
            }
        }

        /** @var Smart2Pay_Globalpay_Model_Resource_Method_Collection $methods_collection */
        $methods_collection = Mage::getModel( 'globalpay/method' )->getCollection();

        $methods_collection->addFieldToSelect( array( 'method_id', 'display_name', 'description', 'logo_url' ) );

        $methods_collection->addFieldToFilter( 'active', 1 );
        $methods_collection->addFieldToFilter( 'environment', $params['environment'] );

        if( !empty( $method_ids_arr ) )
            $methods_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        $methods_collection->setOrder( $params['order_by'], $methods_collection::SORT_ORDER_ASC );
        $methods_collection->getSelect()->order( $params['order_by'].' '.$methods_collection::SORT_ORDER_ASC );

        $return_arr = array();

        while( ($method_obj = $methods_collection->fetchItem())
           and ($method_arr = $method_obj->getData()) )
        {
            if( empty( $method_arr['method_id'] ) )
                continue;

            $return_arr[$method_arr['method_id']] = $method_arr;

            if( !empty( $params['include_countries'] ) )
                $return_arr[$method_arr['method_id']]['countries_list'] = $this->get_countries_for_method( $method_arr['method_id'] );
            else
                $return_arr[$method_arr['method_id']]['countries_list'] = array();
        }

        if( $cache_result )
            $cached_return_arr = $return_arr;

        return $return_arr;
    }

    public function get_countries_for_method( $method_id, $environment = false )
    {
        if( empty( $environment ) )
        {
            /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
            $paymentModel = Mage::getModel('globalpay/pay');

            $environment = $paymentModel->getEnvironment();
        }

        $method_id = intval( $method_id );
        if( empty( $method_id ) )
            return array();

        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $methodcountries_collection */
        $methodcountries_collection = Mage::getModel( 'globalpay/countrymethod' )->getCollection();

        $methodcountries_collection->addFieldToSelect( '*' );

        $methodcountries_collection->addFieldToFilter( 'method_id', $method_id );
        $methodcountries_collection->addFieldToFilter( 'environment', $environment );

        $methodcountries_collection->getSelect()->joinLeft(
            array( 's2p_c' => $methodcountries_collection->getTable( 'globalpay/country' ) ),
            's2p_c.country_id = main_table.country_id'
        );

        $methodcountries_collection->setOrder( 'main_table.priority', 'ASC' );
        $methodcountries_collection->getSelect()->order( 'main_table.priority '.$methodcountries_collection::SORT_ORDER_ASC );

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

    /**
     * @param bool|array $params
     *
     * @return array
     */
    public function get_all_configured_methods( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['environment'] ) or empty( $params['environment'] ) )
        {
            /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
            $paymentModel = Mage::getModel('globalpay/pay');

            $params['environment'] = $paymentModel->getEnvironment();
        }

        // $return_arr[{method_ids}][{country_ids}]['surcharge'], $return_arr[{method_ids}][{country_ids}]['base_amount'], ...
        $return_arr = array();

        /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect( '*' );
        $my_collection->addFieldToFilter( 'environment', $params['environment'] );

        while( ($configured_method_obj = $my_collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $return_arr[$configured_method_arr['method_id']][$configured_method_arr['country_id']] = $configured_method_arr;
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

        if( !isset( $params['environment'] ) or empty( $params['environment'] ) )
        {
            /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
            $paymentModel = Mage::getModel('globalpay/pay');

            $params['environment'] = $paymentModel->getEnvironment();
        }

        // 1. get a list of methods available for provided country
        // 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        // 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)

        /** @var Smart2Pay_Globalpay_Model_Country $country_model */
        if( !($country_model = Mage::getModel( 'globalpay/country' ))
         or !($international_id = $country_model->get_international_id()) )
            $international_id = 0;

        //
        // START 1. get a list of methods available for provided country
        //

        /** @var Smart2Pay_Globalpay_Model_Resource_Countrymethod_Collection $cm_collection */
        $cm_collection = Mage::getModel( 'globalpay/countrymethod' )->getCollection();
        $cm_collection->addFieldToSelect( '*' );

        if( !empty( $international_id ) )
            $cm_collection->addFieldToFilter(
                array( 'main_table.country_id', 'main_table.country_id' ),
                array( $country_id, $international_id )
            );
        else
            $cm_collection->addFieldToFilter( 'main_table.country_id', $country_id );

        $cm_collection->addFieldToFilter( 'main_table.environment', $params['environment'] );

        $cm_collection->getSelect()->joinInner(
            array( 's2p_m' => $cm_collection->getTable( 'globalpay/method' ) ),
            's2p_m.method_id = main_table.method_id AND s2p_m.environment = \''.$params['environment'].'\''
        );

        $cm_collection->setOrder( 'main_table.priority', $cm_collection::SORT_ORDER_ASC );
        $cm_collection->getSelect()->order( 'main_table.priority '.$cm_collection::SORT_ORDER_ASC );
        $cm_collection->setOrder( 's2p_m.display_name', $cm_collection::SORT_ORDER_ASC );
        $cm_collection->getSelect()->order( 's2p_m.display_name '.$cm_collection::SORT_ORDER_ASC );

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
        $my_collection->addFieldToFilter( 'environment', $params['environment'] );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
           and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

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
        $my_collection->addFieldToFilter( 'environment', $params['environment'] );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
           and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

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

    /**
     * @param array $configured_methods_arr
     *
     * @return array|bool
     */
    function save_configured_methods( $configured_methods_arr )
    {
        if( !is_array( $configured_methods_arr ) )
            return false;

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');
        /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods $my_resource */
        $my_resource = $this->getResource();

        $environment = $paymentModel->getEnvironment();

        $saved_method_ids = array();
        $errors_arr = array();
        foreach( $configured_methods_arr as $method_id => $surcharge_per_countries )
        {
            $method_id = intval( $method_id );
            if( empty( $method_id )
             or empty( $surcharge_per_countries ) or !is_array( $surcharge_per_countries )
             or !($countries_ids = array_keys( $surcharge_per_countries )) )
                continue;

            $provided_countries = array();
            foreach( $surcharge_per_countries as $country_id => $country_surcharge )
            {
                $country_id = intval( $country_id );
                if( !is_array( $country_surcharge ) )
                    continue;

                if( empty( $country_surcharge['surcharge'] ) )
                    $country_surcharge['surcharge'] = 0;
                if( empty( $country_surcharge['fixed_amount'] ) )
                    $country_surcharge['fixed_amount'] = 0;
                $country_surcharge['environment'] = $environment;

                if( !$my_resource->insert_or_update( $method_id, $country_id, $country_surcharge ) )
                    $errors_arr[] = $helper_obj->__( 'Error saving method ID '.$method_id.', for country '.$country_id.'.' );

                $provided_countries[] = $country_id;
            }

            // Delete countries which are not provided for current method
            /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $my_collection */
            $my_collection = $this->getCollection();
            $my_collection->addFieldToFilter( 'method_id', $method_id );
            $my_collection->addFieldToFilter( 'environment', $environment );
            if( !empty( $provided_countries ) )
                $my_collection->addFieldToFilter( 'country_id', array( 'nin' => $provided_countries ) );

            $my_resource->delete_from_collection( $my_collection );

            $saved_method_ids[] = $method_id;
        }

        // delete rest of methods not in $saved_method_ids array...
        /** @var Smart2Pay_Globalpay_Model_Resource_Configuredmethods_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter( 'environment', $environment );
        if( !empty( $saved_method_ids ) )
            $my_collection->addFieldToFilter( 'method_id', array( 'nin' => $saved_method_ids ) );

        $my_resource->delete_from_collection( $my_collection );

        if( !empty( $errors_arr ) )
            return $errors_arr;

        return true;
    }

}

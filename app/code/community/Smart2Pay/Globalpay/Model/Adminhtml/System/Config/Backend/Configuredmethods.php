<?php

class Smart2Pay_Globalpay_Model_Adminhtml_System_Config_Backend_Configuredmethods extends Mage_Core_Model_Config_Data
{
    const ERR_SURCHARGE_PERCENT = 1, ERR_SURCHARGE_SAVE = 2, ERR_METHODS_REFRESH = 3;

    // Array created in _beforeSave() and commited to database in _afterSave()
    // $_methods_to_save[{method_ids}][{country_ids}]['surcharge'], $_methods_to_save[{method_ids}][{country_ids}]['fixed_amount'], ...
    protected $_methods_to_save = array();

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'globalpay_configured_methods';

    protected function _afterLoad()
    {
        return $this;
    }

    protected function _beforeSave()
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        // $logger_obj = Mage::getModel( 'globalpay/logger' );
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        if( ($s2p_submit_syncronize_methods = Mage::app()->getRequest()->getParam( 's2p_submit_syncronize_methods', false )) )
        {
            /** @var Smart2Pay_Globalpay_Helper_Sdk $sdk_obj */
            $sdk_obj = Mage::helper( 'globalpay/sdk' );

            // Don't let system write in db when we sync methods
            $this->_methods_to_save = false;
            $this->_dataSaveAllowed = false;

            if( !$sdk_obj->refresh_available_methods() )
            {
                if( $sdk_obj->has_error() )
                    $error_msg = $sdk_obj->get_error();
                else
                    $error_msg = $helper_obj->__( 'Error refreshing Smart2Pay payment methods.' );

                $messages_arr = array();
                $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                    'message' => $error_msg,
                    'class' => __CLASS__,
                    'method' => __METHOD__,
                );

                throw $helper_obj->mage_exception( self::ERR_METHODS_REFRESH, $messages_arr );
            }

            return $this;
        }

        if( null !== ($form_s2p_enabled_methods = Mage::app()->getRequest()->getParam( 's2p_enabled_methods', null )) )
        {
            /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
            $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

            if( empty( $form_s2p_enabled_methods )
             or !is_array( $form_s2p_enabled_methods ) )
                $form_s2p_enabled_methods = array();
            if( !($form_s2p_surcharge = Mage::app()->getRequest()->getParam( 's2p_surcharge', array() ))
             or !is_array( $form_s2p_surcharge ) )
                $form_s2p_surcharge = array();
            if( !($form_s2p_fixed_amount = Mage::app()->getRequest()->getParam( 's2p_fixed_amount', array() ))
             or !is_array( $form_s2p_fixed_amount ) )
                $form_s2p_fixed_amount = array();
            if( !($form_s2p_per_country = Mage::app()->getRequest()->getParam( 's2p_per_country', array() ))
             or !is_array( $form_s2p_per_country ) )
                $form_s2p_per_country = array();

            $existing_methods_params_arr = array();
            $existing_methods_params_arr['method_ids'] = $form_s2p_enabled_methods;
            $existing_methods_params_arr['include_countries'] = true;

            if( !($db_existing_methods = $configured_methods_obj->get_all_methods( $existing_methods_params_arr )) )
                $db_existing_methods = array();

            $messages_arr = array();
            $last_code = 0;

            $this->_methods_to_save = array();
            foreach( $db_existing_methods as $method_id => $method_details )
            {
                if( empty( $form_s2p_surcharge[$method_id] ) )
                    $form_s2p_surcharge[$method_id] = 0;
                if( empty( $form_s2p_fixed_amount[$method_id] ) )
                    $form_s2p_fixed_amount[$method_id] = 0;

                if( !is_numeric( $form_s2p_surcharge[$method_id] ) )
                {
                    $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                                                'message' => $helper_obj->__( 'Please provide a valid percent for method ' . $method_details['display_name'] . '.' ),
                                                'class' => __CLASS__,
                                                'method' => __METHOD__,
                                                );
                    $last_code = self::ERR_SURCHARGE_PERCENT;
                    continue;
                }

                if( !is_numeric( $form_s2p_fixed_amount[$method_id] ) )
                {
                    $messages_arr[Mage_Core_Model_Message::ERROR][] = array(
                                                'message' => $helper_obj->__( 'Please provide a valid fixed amount for method ' . $method_details['display_name'] . '.' ),
                                                'class' => __CLASS__,
                                                'method' => __METHOD__,
                                                );
                    $last_code = self::ERR_SURCHARGE_PERCENT;
                    continue;
                }

                if( empty( $this->_methods_to_save[$method_id] ) )
                    $this->_methods_to_save[$method_id] = array();

                // Country id 0 means default settings...
                $this->_methods_to_save[$method_id][0] = array();
                $this->_methods_to_save[$method_id][0]['surcharge'] = $form_s2p_surcharge[$method_id];
                $this->_methods_to_save[$method_id][0]['fixed_amount'] = $form_s2p_fixed_amount[$method_id];
                $this->_methods_to_save[$method_id][0]['3dsecure'] = -1;
                $this->_methods_to_save[$method_id][0]['disabled'] = 0;

                // Check custom country settings
                if( !empty( $form_s2p_per_country )
                and !empty( $form_s2p_per_country[$method_id] ) and is_array( $form_s2p_per_country[$method_id] )
                and !empty( $method_details['countries_list'] ) and is_array( $method_details['countries_list'] ) )
                {
                    foreach( $method_details['countries_list'] as $country_id => $country_arr )
                    {
                        if( empty( $form_s2p_per_country[$method_id][$country_id] )
                         or !is_array( $form_s2p_per_country[$method_id][$country_id] )
                         or empty( $form_s2p_per_country[$method_id][$country_id]['is_custom'] ) )
                            continue;

                        // -1 is default
                        if( !isset( $form_s2p_per_country[$method_id][$country_id]['3dsecure'] )
                         or !in_array( $form_s2p_per_country[$method_id][$country_id]['3dsecure'], array( 0, 1 ) ) )
                            $form_s2p_per_country[$method_id][$country_id]['3dsecure'] = -1;

                        $this->_methods_to_save[$method_id][$country_id] = array();
                        $this->_methods_to_save[$method_id][$country_id]['surcharge'] = (!empty( $form_s2p_per_country[$method_id][$country_id]['surcharge'] )?$form_s2p_per_country[$method_id][$country_id]['surcharge']:0);
                        $this->_methods_to_save[$method_id][$country_id]['fixed_amount'] = (!empty( $form_s2p_per_country[$method_id][$country_id]['fixed_amount'] )?$form_s2p_per_country[$method_id][$country_id]['fixed_amount']:0);
                        $this->_methods_to_save[$method_id][$country_id]['3dsecure'] = $form_s2p_per_country[$method_id][$country_id]['3dsecure'];
                        $this->_methods_to_save[$method_id][$country_id]['disabled'] = (!empty( $form_s2p_per_country[$method_id][$country_id]['disabled'] )?1:0);
                    }
                }
            }

            if( !empty( $messages_arr ) )
            {
                // Don't let system write in db if we have an error
                $this->_methods_to_save = array();
                $this->_dataSaveAllowed = false;

                throw $helper_obj->mage_exception( $last_code, $messages_arr );
            }

            // $logger_obj->write( 'Saving ['.print_r( $this->_methods_to_save, true ).']', 'config_save' );
        }

        return $this;
    }

    protected function _afterSave()
    {
        if( !is_array( $this->_methods_to_save ) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

        if( ($save_result = $configured_methods_obj->save_configured_methods( $this->_methods_to_save )) !== true )
        {
            if( !is_array( $save_result ) )
            {
                /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
                $helper_obj = Mage::helper( 'globalpay/helper' );

                $error_msg = $helper_obj->__( 'Error saving methods to database. Please try again.' );
            } else
                $error_msg = implode( '<br/>', $save_result );

            throw Mage::exception( 'Mage_Core', $error_msg, self::ERR_SURCHARGE_SAVE );
        }

        return $this;
    }

    protected function _hasModelChanged()
    {
        return true;
    }
}

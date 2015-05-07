<?php

class Smart2Pay_Globalpay_Model_Adminhtml_System_Config_Backend_Configuredmethods extends Mage_Core_Model_Config_Data
{
    const ERR_SURCHARGE_PERCENT = 1, ERR_SURCHARGE_SAVE = 2;

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
        $logger_obj = Mage::getModel( 'globalpay/logger' );
        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );
        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper( 'globalpay/helper' );

        if( !($form_s2p_enabled_methods = Mage::app()->getRequest()->getParam( 's2p_enabled_methods', array() ))
         or !is_array( $form_s2p_enabled_methods ) )
            $form_s2p_enabled_methods = array();
        if( !($form_s2p_surcharge = Mage::app()->getRequest()->getParam( 's2p_surcharge', array() ))
         or !is_array( $form_s2p_surcharge ) )
            $form_s2p_surcharge = array();
        if( !($form_s2p_fixed_amount = Mage::app()->getRequest()->getParam( 's2p_fixed_amount', array() ))
         or !is_array( $form_s2p_fixed_amount ) )
            $form_s2p_fixed_amount = array();

        $existing_methods_params_arr = array();
        $existing_methods_params_arr['method_ids'] = $form_s2p_enabled_methods;
        $existing_methods_params_arr['include_countries'] = false;

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

            // TODO: add country ids instead of only 0 (all countries)
            $this->_methods_to_save[$method_id][0]['surcharge'] = $form_s2p_surcharge[$method_id];
            $this->_methods_to_save[$method_id][0]['fixed_amount'] = $form_s2p_fixed_amount[$method_id];
        }

        if( !empty( $messages_arr ) )
        {
            // Don't let system write in db if we have an error
            $this->_methods_to_save = array();
            $this->_dataSaveAllowed = false;

            throw $helper_obj->mage_exception( $last_code, $messages_arr );
        }

        // $logger_obj->write( 'Saving ['.print_r( $this->_methods_to_save, true ).']', 'config_save' );

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
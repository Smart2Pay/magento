<?php

class Smart2Pay_Globalpay_Model_System_Config_Backend_Configuredmethods extends Mage_Core_Model_Config_Data
{
    protected function _afterLoad()
    {
        /**
        if( ! is_array( $this->getValue() ) )
        {
            $value = $this->getValue();
            $this->setValue( empty( $value ) ? false : unserialize( $value ) );
        }
         **/
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'afterload', 'config_save' );

        return $this;

        // s2p_enabled_methods
    }

    protected function _beforeSave()
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'Saving', 'config_save' );

        // Don't let system write junk about this setting
        $this->_dataSaveAllowed = true;

        return $this;
    }

    protected function _afterSave()
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'After saving', 'config_save' );

        // Don't let system write junk about this setting
        //$this->_dataSaveAllowed = false;
        return $this;
    }

    protected function _hasModelChanged()
    {
        return true;
    }
}
<?php
class Smart2Pay_Globalpay_Model_Method extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/method_collection';
            
    protected function _construct()
    {
        $this->_init('globalpay/method');
    }

    public function delete_methods_for_environment( $environment )
    {
        /** @var Smart2Pay_Globalpay_Model_Countrymethod $country_methods_obj */
        $country_methods_obj = Mage::getModel( 'globalpay/countrymethod' );

        if( !$country_methods_obj->delete_country_methods_for_environment( $environment ) )
            return false;

        /** @var Smart2Pay_Globalpay_Model_Resource_Method $my_resource */
        $my_resource = $this->getResource();

        /** @var Smart2Pay_Globalpay_Model_Resource_Method_Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter( 'environment', $environment );

        return $my_resource->delete_from_collection( $my_collection );
    }

    /**
     * @param array $methods_arr
     *
     * @return string|bool
     */
    public function save_methods_from_sdk_response( $methods_arr )
    {
        /** @var Smart2Pay_Globalpay_Model_Logger $s2pLogger */
        $s2pLogger = Mage::getModel( 'globalpay/logger' );
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');
        /** @var Smart2Pay_Globalpay_Model_Countrymethod $country_method_obj */
        $country_method_obj = Mage::getModel('globalpay/countrymethod');
        /** @var Smart2Pay_Globalpay_Model_Resource_Method $my_resource */
        $my_resource = $this->getResource();

        if( !is_array( $methods_arr ) )
        {
            $s2pLogger->write( 'SDK methods response is not an array.', 'SDK_methods_update' );

            return 'You should provide an array of payment methods to be saved.';
        }

        $s2pLogger->write( 'Updating '.count( $methods_arr ).' methods for environment '.$paymentModel->method_config['environment'].' from SDK response.', 'SDK_methods_update' );

        if( !$this->delete_methods_for_environment( $paymentModel->method_config['environment'] ) )
        {
            $s2pLogger->write( 'Couldn\'t delete existing methods from database.', 'SDK_methods_update' );

            return 'Couldn\'t delete existing methods from database.';
        }

        foreach( $methods_arr as $method_arr )
        {
            if( empty( $method_arr ) or !is_array( $method_arr )
             or empty( $method_arr['id'] ) )
                continue;

            $row_method_arr = array();
            $row_method_arr['display_name'] = $method_arr['displayname'];
            $row_method_arr['description'] = $method_arr['description'];
            $row_method_arr['logo_url'] = $method_arr['logourl'];
            $row_method_arr['guaranteed'] = (!empty( $method_arr['guaranteed'] )?1:0);
            $row_method_arr['active'] = (!empty( $method_arr['active'] )?1:0);

            if( !($db_method = $my_resource->insert_or_update( $method_arr['id'], $paymentModel->method_config['environment'], $row_method_arr )) )
            {
                $s2pLogger->write( 'Error saving method details in database (#'.$method_arr['id'].').', 'SDK_methods_update' );

                $this->delete_methods_for_environment( $paymentModel->method_config['environment'] );

                return 'Error saving method details in database (#'.$method_arr['id'].').';
            }

            if( !empty( $method_arr['countries'] ) and is_array( $method_arr['countries'] ) )
            {
                if( true !== ($error_msg = $country_method_obj->update_method_countries( $method_arr['id'], $method_arr['countries'],
                                                                                         $paymentModel->method_config['environment'], array( 'delete_before_update' => false ) )) )
                {
                    $s2pLogger->write( 'Error saving method countries in database (#'.$method_arr['id'].').', 'SDK_methods_update' );

                    $this->delete_methods_for_environment( $paymentModel->method_config['environment'] );

                    return $error_msg;
                }
            }

            $saved_method_ids[] = $db_method['id'];
        }

        return true;
    }
}

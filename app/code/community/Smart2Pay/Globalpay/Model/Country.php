<?php

class Smart2Pay_Globalpay_Model_Country extends Mage_Core_Model_Abstract
{
    protected $_resourceCollectionName = 'globalpay/country_collection';
    
    protected function _construct()
    {
        $this->_init('globalpay/country');
    }

    public function get_countries_code_as_key()
    {
        /** @var Smart2Pay_Globalpay_Model_Resource_Country_Collection $my_collection */
        $my_collection = $this->getCollection();

        $return_arr = array();
        while( ($country_obj = $my_collection->fetchItem())
        and ($country_arr = $country_obj->getData()) )
        {
            $return_arr[$country_arr['code']] = $country_arr['country_id'];
        }

        return $return_arr;
    }

    public function get_international_id()
    {
        return $this->load( 'AA', 'code' )->getId();
    }
}

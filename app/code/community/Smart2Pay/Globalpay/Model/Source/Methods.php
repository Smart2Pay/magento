<?php
class Smart2Pay_Globalpay_Model_Source_Methods
{
    public $methods = array();

    public function __construct()
    {
        $methods = Mage::getModel('globalpay/method')->getCollection()->toArray();
        foreach( $methods['items'] as $item )
        {
            $this->methods[$item['method_id']] = $item['display_name'];
        }
    }    

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        /*
        return array(
            array('value' => 'test', 'label' => 'Test'),
            array('value' => 'live', 'label' => 'Live'),
        );
         * 
         */
        $to_return = array();
        foreach( $this->methods as $value => $label )
            $to_return[] = array( 'value' => $value, 'label' => $label );

        return $to_return;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->methods;
    }
}


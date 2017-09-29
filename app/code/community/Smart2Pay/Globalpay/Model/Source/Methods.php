<?php
class Smart2Pay_Globalpay_Model_Source_Methods
{
    public $methods = array();

    public function __construct()
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $paymentModel */
        $paymentModel = Mage::getModel('globalpay/pay');

        $environment = $paymentModel->getEnvironment();

        $methods_collection = Mage::getModel('globalpay/method')->getCollection();
        $methods_collection->addFieldToFilter( 'environment', $environment );

        $methods = $methods_collection->toArray();
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


<?php
class Smart2Pay_Globalpay_Model_Source_Testlive
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'demo', 'label' => 'Demo'),
            array('value' => 'test', 'label' => 'Test'),
            array('value' => 'live', 'label' => 'Live'),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'demo' => 'Demo',
            'test' => 'Test',
            'live' => 'Live',
        );
    }

}
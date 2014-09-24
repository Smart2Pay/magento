<?php

class Smart2Pay_Globalpay_Model_Source_Displaymodes
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'logo', 'label' => 'Logo'),
            array('value' => 'text', 'label' => 'Text'),
            array('value' => 'both', 'label' => 'Logo and Text'),
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
            'logo' => 'Logo',
            'text' => 'Text',
            'both' => 'Loto and Text'
        );
    }

}
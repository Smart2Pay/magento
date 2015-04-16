<?php

class Smart2Pay_Globalpay_Block_Adminhtml_System_Config_Configuredmethods extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * @var string
     */
    protected $_wizardTemplate = 'smart2pay/globalpay/system/config/methods.phtml';
    protected $_code = 'globalpay';

    public $base_currency;

    function __construct( array $args = array() )
    {
        parent::__construct( $args );

        $this->base_currency = Mage::app()->getBaseCurrencyCode();
    }

    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate($this->_wizardTemplate);
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement( $element );

        return $this->_toHtml();
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement( $element );
        return $this->_toHtml();
    }

    public function get_all_methods()
    {
        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

        return $configured_methods_obj->get_all_methods();
    }

    public function get_all_configured_methods()
    {
        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

        return $configured_methods_obj->get_all_configured_methods();
    }

    public function get_countries_for_method( $method_id )
    {
        /** @var Smart2Pay_Globalpay_Model_Configuredmethods $configured_methods_obj */
        $configured_methods_obj = Mage::getModel( 'globalpay/configuredmethods' );

        return $configured_methods_obj->get_countries_for_method( $method_id );
    }
}

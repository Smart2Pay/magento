<?php
	class Smart2Pay_Globalpay_Model_Pay extends Mage_Payment_Model_Method_Abstract
	{
	    protected $_code = 'globalpay';
		
            protected $_formBlockType = 'globalpay/paymethod_form';
            
            // method config
            public $method_config = array();            
            
            public function __construct() {
                parent::__construct();
                // get environment type
                $environment = $this->getConfigData('environment'); // [test | live]
                // get config
                $this->method_config = array(
                    'post_url' => $this->getConfigData('post_url_'.$environment),
                    'signature' => $this->getConfigData('signature_'.$environment),
                    'mid' => $this->getConfigData('mid_'.$environment),
                    'return_url' => $this->getConfigData('return_url'),
                    'methods' => $this->getConfigData('methods'),
                    'methods_display_mode' => $this->getConfigData('methods_display_mode'),
                    'show_text_img' => $this->getConfigData('show_text_img'),
                    'show_methods_in_grid' => $this->getConfigData('show_methods_in_grid'),
                    'grid_column_number' => $this->getConfigData('grid_column_number'),
                    'adjust_for_one_page_checkout' => $this->getConfigData('adjust_for_one_page_checkout'),
                    'autoselect_s2p' => $this->getConfigData('autoselect_s2p'), 
                    'send_customer_email' => $this->getConfigData('send_customer_email'),
                    'send_customer_name' => $this->getConfigData('send_customer_name'),
                    'send_country' => $this->getConfigData('send_country'),
                    'send_payment_method' => $this->getConfigData('send_payment_method'),
                    'notify_payment_instructions'  => $this->getConfigData('notify_payment_instructions'),
                    'send_product_description' => $this->getConfigData('send_product_description'),
                    'product_description_ref' => $this->getConfigData('product_description_ref'),
                    'product_description_custom' => $this->getConfigData('product_description_custom'),
                    'skip_payment_page' => $this->getConfigData('skip_payment_page'),
                    'debug_form' => $this->getConfigData('debug_form'),
                    'redirect_in_iframe' => $this->getConfigData('redirect_in_iframe'),
                    'skin_id' => $this->getConfigData('skin_id'),
                    'site_id' => $this->getConfigData('site_id'),
                    'message_data_2' => $this->getConfigData('message_data_2'),
                    'message_data_3' => $this->getConfigData('message_data_3'),
                    'message_data_4' => $this->getConfigData('message_data_4'),
                    'message_data_7' => $this->getConfigData('message_data_7'),
                    'order_status' => $this->getConfigData('order_status'),
                    'order_status_on_2' => $this->getConfigData('order_status_on_2'),
                    'order_status_on_3' => $this->getConfigData('order_status_on_3'),
                    'order_status_on_4' => $this->getConfigData('order_status_on_4'),
                    'order_status_on_5' => $this->getConfigData('order_status_on_5'),
                    'auto_invoice' => $this->getConfigData('auto_invoice'),
                    'auto_ship' => $this->getConfigData('auto_ship'),
                    'notify_customer' => $this->getConfigData('notify_customer'),
                );
            }
            
            public function assignData($data)
            {
                if (!($data instanceof Varien_Object)) {
                    $data = new Varien_Object($data);
                }
                $_SESSION['globalpay_method'] = $data->getMethodId();
            }

            public function getOrderPlaceRedirectUrl()
            {   
                $_SESSION['s2p_handle_payment'] = true;
                //return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)."index.php/globalpay/"; 
                Mage::getModel('globalpay/logger')->write(Mage::getUrl('globalpay', array('_secure' => true)), 'info');
                return Mage::getUrl('globalpay', array('_secure' => true));
            }	
	}
?>

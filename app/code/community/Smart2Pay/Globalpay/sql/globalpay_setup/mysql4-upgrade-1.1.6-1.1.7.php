<?php

    /* @var $installer Mage_Paypal_Model_Resource_Setup */
    /* @var $this Mage_Paypal_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    // Adding new payment methods
    $installer->run( "INSERT INTO `{$installer->getTable('globalpay/method')}` (`method_id`, `display_name`, `provider_value`, `description`, `logo_url`, `guaranteed`, `active`) VALUES
            (1051, 'Globe GCash', 'dragonpay', 'Globe GCash description', 'gcashlogo.jpg', 1, 1),
            (1048, 'BankTransfer Japan', 'degica', 'BankTransfer Japan description', 'degica_bank_transfer.gif', 1, 1),
            (1046, 'Konbini', 'degica', 'Konbini description', 'degica_kombini.png', 1, 1);" );

    $installer->run( "INSERT INTO `{$installer->getTable('globalpay/countrymethod')}` (`id`, `country_id`, `method_id`, `priority`) VALUES
                (542,152,1051,99),
                (543,110,1048,99),
                (544,110,1046,99);" );

    $installer->run( "UPDATE `{$installer->getTable('globalpay/method')}` SET `display_name` = 'Finish Banks' WHERE `method_id` = 65" );

    $installer->endSetup();

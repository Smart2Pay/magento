<?php

    /* @var $installer Smart2Pay_Globalpay_Model_Resource_Setup */
    /* @var $this Smart2Pay_Globalpay_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    // More method per country customisation
    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/logger')."` CHANGE `transaction_id` `transaction_id` VARCHAR(100) NULL DEFAULT NULL;".
                     "ALTER TABLE `".$this->getTable('globalpay/logger')."` ADD INDEX(`transaction_id`);".
                     "ALTER TABLE `".$this->getTable('globalpay/logger')."` ADD INDEX(`log_type`);" );

    $installer->endSetup();

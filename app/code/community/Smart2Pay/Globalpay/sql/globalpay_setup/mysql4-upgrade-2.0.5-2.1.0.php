<?php

    /* @var $installer Smart2Pay_Globalpay_Model_Resource_Setup */
    /* @var $this Smart2Pay_Globalpay_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    // More method per country customisation
    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/configuredmethods')."` ADD `3dsecure` tinyint(2) NOT NULL DEFAULT '-1' COMMENT 'For SmartCards, -1 default';".
                     "ALTER TABLE  `".$this->getTable('globalpay/configuredmethods')."` ADD `disabled` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Disable method for country';" );

    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/transactionlogger')."` ADD `3dsecure` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Was this a 3DSecure transaction';" );

    $installer->endSetup();

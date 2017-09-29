<?php

    /* @var $installer Smart2Pay_Globalpay_Model_Resource_Setup */
    /* @var $this Smart2Pay_Globalpay_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/countrymethod')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/countrymethod')}` (
            `id` int(11) NOT NULL auto_increment,
            `environment` varchar(50) collate utf8_unicode_ci default NULL,
            `country_id` int(11) default NULL,
            `method_id` int(11) default NULL,
            `priority` int(2) default NULL,
            PRIMARY KEY  (`id`),
            KEY `environment` (`environment`),
            KEY `country_id` (`country_id`),
            KEY `method_id` (`method_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ");

    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/method')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/method')}` (
            `id` int(11) NOT NULL auto_increment,
            `method_id` int(11) NOT NULL DEFAULT '0',
            `environment` varchar(50) collate utf8_unicode_ci default NULL,
            `display_name` varchar(255) collate utf8_unicode_ci default NULL,
            `description` text collate utf8_unicode_ci,
            `logo_url` varchar(255) collate utf8_unicode_ci default NULL,
            `guaranteed` int(1) default NULL,
            `active` int(1) default NULL,
            PRIMARY KEY  (`id`),
            KEY `method_id` (`method_id`),
            KEY `environment` (`environment`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ");

    $installer->run( "DROP TABLE IF EXISTS `{$installer->getTable('globalpay/configuredmethods')}`;".
                     " CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/configuredmethods')}` (
          `id` int(11) NOT NULL auto_increment,
          `environment` varchar(50) collate utf8_unicode_ci default NULL,
          `method_id` int(11) NOT NULL DEFAULT '0',
          `country_id` int(11) NOT NULL DEFAULT '0' COMMENT '0 for all countries',
          `surcharge` DECIMAL(6, 2) NOT NULL DEFAULT '0' COMMENT 'Surcharge percent from total order amount to be used as payment fee',
          `fixed_amount` DECIMAL(6, 2) NOT NULL DEFAULT '0' COMMENT 'Surcharge fixed amount to be used as payment fee',
          PRIMARY KEY (`id`),
          KEY `environment` (`environment`),
          KEY `method_id` (`method_id`),
          KEY `country_id` (`country_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Payment methods to be used and their surcharge (if applicable)';" );

    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/logger')."` ADD `transaction_id` INT NULL DEFAULT '0' COMMENT 'Transaction linked to this log' AFTER `log_type`;" );

    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/transactionlogger')."` ADD `payment_status` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'S2P transaction status' AFTER `payment_id`;" );

    $installer->endSetup();

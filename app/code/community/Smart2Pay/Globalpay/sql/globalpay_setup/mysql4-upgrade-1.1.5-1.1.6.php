<?php
    //die('Trying to setup Smart2Pay_Globalpay database');

    /* @var $installer Mage_Paypal_Model_Resource_Setup */
    /* @var $this Mage_Paypal_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    //$this->getConnection()->addColumn( $this->getTable('sales/quote_address'), 's2p_surcharge_amount', array(
    //        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    //        'length' => '10,2',
    //        'nullable' => false,
    //        'default' => 0,
    //        'comment' => 'Surcharge percent amount',
    //    ) );

    // Adding fields in quote address for our surcharge fee
    $installer->run( "ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `s2p_surcharge_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `s2p_surcharge_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `s2p_surcharge_percent` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `s2p_surcharge_fixed_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `s2p_surcharge_fixed_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';"
    );

    // Adding fields in quote payment for our surcharge fee
    $installer->run( "ALTER TABLE  `".$this->getTable('sales/quote_payment')."` ADD  `s2p_surcharge_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_payment')."` ADD  `s2p_surcharge_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_payment')."` ADD  `s2p_surcharge_percent` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_payment')."` ADD  `s2p_surcharge_fixed_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/quote_payment')."` ADD  `s2p_surcharge_fixed_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';"
    );

    // Adding fields in order for our surcharge fee
    $installer->run( "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_percent` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_fixed_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_fixed_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_amount_invoiced` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/order_payment')."` ADD `s2p_surcharge_base_amount_invoiced` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';"
    );

    // Adding fields in invoice for our surcharge fee
    $installer->run( "ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `s2p_surcharge_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `s2p_surcharge_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `s2p_surcharge_fixed_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';".
                     "ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `s2p_surcharge_fixed_base_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';"
    );

    // Adding field that tells transaction environment
    $installer->run( "ALTER TABLE  `".$this->getTable('globalpay/transactionlogger')."` ADD `environment` VARCHAR(20) NULL DEFAULT 'live' COMMENT 'Environment of transaction' AFTER `site_id`;" );

    $installer->run( "DROP TABLE IF EXISTS `{$installer->getTable('globalpay/configuredmethods')}`;".
        " CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/configuredmethods')}` (
          `id` int(11) NOT NULL auto_increment,
          `method_id` int(11) NOT NULL DEFAULT '0',
          `country_id` int(11) NOT NULL DEFAULT '0' COMMENT '0 for all countries',
          `surcharge` DECIMAL(6, 2) NOT NULL DEFAULT '0' COMMENT 'Surcharge percent from total order amount to be used as payment fee',
          `fixed_amount` DECIMAL(6, 2) NOT NULL DEFAULT '0' COMMENT 'Surcharge fixed amount to be used as payment fee',
          PRIMARY KEY (`id`),
          KEY `method_id` (`method_id`),
          KEY `country_id` (`country_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Payment methods to be used and their surcharge (if applicable)';" );

    $installer->endSetup();

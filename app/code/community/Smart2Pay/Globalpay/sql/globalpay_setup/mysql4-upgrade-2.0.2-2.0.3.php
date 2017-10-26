<?php

    /* @var $installer Smart2Pay_Globalpay_Model_Resource_Setup */
    /* @var $this Smart2Pay_Globalpay_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    $installer->run("DELETE FROM `{$installer->getTable('globalpay/country')}` WHERE `code` = 'AA';");

    $installer->endSetup();

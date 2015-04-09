<?php

class Smart2Pay_Globalpay_Model_Sales_Quote_Address_Total_Surcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'globalpay';

    public function collect( Mage_Sales_Model_Quote_Address $address )
    {
        parent::collect( $address );

        /** @var Smart2Pay_Globalpay_Model_Pay $pay_obj */
        if( $address->getAddressType() != Mage_Sales_Model_Quote_Address::TYPE_BILLING
         or !($quote = $address->getQuote())
         or !($payment_obj = $quote->getPayment()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        if( !($payment_amount = $payment_obj->getS2pSurchargeAmount()) )
            $payment_amount = 0;
        if( !($payment_fixed_amount = $payment_obj->getS2pSurchargeFixedAmount()) )
            $payment_fixed_amount = 0;
        if( !($payment_base_amount = $payment_obj->getS2pSurchargeBaseAmount()) )
            $payment_base_amount = 0;
        if( !($payment_fixed_base_amount = $payment_obj->getS2pSurchargeFixedBaseAmount()) )
            $payment_fixed_base_amount = 0;
        if( !($payment_percent = $payment_obj->getS2pSurchargePercent()) )
            $payment_percent = 0;

        if( $payment_obj->isDeleted() )
        {
            //$logger_obj->write( 'Deleted Payment ['.$payment_amount.'] Base ['.$payment_base_amount.'] Percent ['.$payment_percent.']' );

            // clean surcharge as payment was deleted...
            if( $payment_amount != 0 )
            {
                // we already have a surcharge... extract it from total
                $address->setGrandTotal( $address->getGrandTotal() - ($payment_amount + $payment_fixed_amount) );
            }

            if( $payment_base_amount != 0 )
            {
                // we already have a surcharge... extract it from total
                $address->setBaseGrandTotal( $address->getBaseGrandTotal() - ($payment_base_amount + $payment_fixed_base_amount) );
            }

            $payment_obj->setS2pSurchargeAmount( 0 );
            $payment_obj->setS2pSurchargeFixedAmount( 0 );
            $payment_obj->setS2pSurchargeBaseAmount( 0 );
            $payment_obj->setS2pSurchargeFixedBaseAmount( 0 );
            $payment_obj->setS2pSurchargePercent( 0 );

            $address->setS2pSurchargeAmount( 0 );
            $address->setS2pSurchargeFixedAmount( 0 );
            $address->setS2pSurchargeBaseAmount( 0 );
            $address->setS2pSurchargeFixedBaseAmount( 0 );
            $address->setS2pSurchargePercent( 0 );

            $this->_setAmount( 0 );
            $this->_setBaseAmount( 0 );

            return $this;
        }

        //$logger_obj->write( 'Surcharge ['.$payment_amount.'] Base ['.$payment_base_amount.'] Percent ['.$payment_percent.']' );

        $address->setS2pSurchargeAmount( $payment_amount );
        $address->setS2pSurchargeFixedAmount( $payment_fixed_amount );

        $this->_addAmount( $address->getS2pSurchargeAmount() + $address->getS2pSurchargeFixedAmount() );

        $address->setS2pSurchargeBaseAmount( $payment_base_amount );
        $address->setS2pSurchargeFixedBaseAmount( $payment_fixed_base_amount );

        $this->_addBaseAmount( $address->getS2pSurchargeBaseAmount() + $address->getS2pSurchargeFixedBaseAmount() );

        //$logger_obj->write( 'Total ['.$address->getGrandTotal().'] Base ['.$address->getBaseGrandTotal().']' );

        $address->setS2pSurchargePercent( $payment_percent );

        return $this;
    }

    public function fetch( Mage_Sales_Model_Quote_Address $address )
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $pay_obj */
        if( $address->getAddressType() != Mage_Sales_Model_Quote_Address::TYPE_BILLING
         or !($quote = $address->getQuote())
         or !($payment_obj = $quote->getPayment())
         or $payment_obj->isDeleted()
         or (!$payment_obj->getS2pSurchargeAmount() and !$payment_obj->getS2pSurchargeFixedAmount()) )
            return $this;

        $payment_percent = $payment_obj->getS2pSurchargePercent();
        $payment_fixed_amount = $payment_obj->getS2pSurchargeFixedAmount();
        $payment_amount = $payment_obj->getS2pSurchargeAmount();

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper('globalpay/helper');

        $address->addTotal( array(
            'code'  => $this->getCode(),
            'title' => $helper_obj->format_surcharge_label( $payment_amount, $payment_percent, array( 'use_translate' => true ) ),
            'value' => $helper_obj->format_surcharge_value( $payment_amount + $payment_fixed_amount, $payment_percent ),
        ) );

        return $this;
    }
}

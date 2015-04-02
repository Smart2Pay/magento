<?php

class Smart2Pay_Globalpay_Model_Sales_Quote_Address_Total_Surcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'globalpay';

    public function collect( Mage_Sales_Model_Quote_Address $address )
    {
        parent::collect( $address );

        /** @var Smart2Pay_Globalpay_Model_Pay $pay_obj */
        if( $address->getAddressType() != 'billing'
         or !($quote = $address->getQuote())
         or !($payment_obj = $quote->getPayment()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        if( !($payment_amount = $payment_obj->getS2pSurchargeAmount()) )
            $payment_amount = 0;
        if( !($payment_base_amount = $payment_obj->getS2pSurchargeBaseAmount()) )
            $payment_base_amount = 0;
        if( !($payment_percent = $payment_obj->getS2pSurchargePercent()) )
            $payment_percent = 0;


        if( $payment_obj->isDeleted() )
        {
            $logger_obj->write( 'Deleted Payment ['.$payment_amount.'] Base ['.$payment_base_amount.'] Percent ['.$payment_percent.']' );

            // clean surcharge as payment was deleted...
            if( $payment_amount != 0 )
            {
                // we already have a surcharge... extract it from total
                $address->setGrandTotal( $address->getGrandTotal() - $payment_amount );
            }

            if( $payment_base_amount != 0 )
            {
                // we already have a surcharge... extract it from total
                $address->setBaseGrandTotal( $address->getBaseGrandTotal() - $payment_base_amount );
            }

            $payment_obj->setS2pSurchargeAmount( 0 );
            $address->setS2pSurchargeAmount( 0 );

            $payment_obj->setS2pSurchargeBaseAmount( 0 );
            $address->setS2pSurchargeBaseAmount( 0 );

            $payment_obj->setS2pSurchargePercent( 0 );
            $address->setS2pSurchargePercent( 0 );

            $this->_setAmount( 0 );
            $this->_setBaseAmount( 0 );

            return $this;
        }

        $address_amount = $address->getS2pSurchargeAmount();
        $address_base_amount = $address->getS2pSurchargeBaseAmount();
        $address_percent = $address->getS2pSurchargePercent();

        if( true or $address_amount != $payment_amount )
        {
            $balance = $payment_amount - $address_amount;

            $logger_obj->write( 'Address [' . $address_amount . '(' . $address_percent . '%)] Payment [' . $payment_amount . '(' . $payment_percent . '%)] DIF [' . $balance . ']' );

            $address->setS2pSurchargeAmount( $payment_amount );
            $address->setGrandTotal( $address->getGrandTotal() + $address->getS2pSurchargeAmount() );
        }

        if( true or $address_base_amount != $payment_base_amount )
        {
            $balance = $payment_base_amount - $address_base_amount;

            $logger_obj->write( 'Base Address [' . $address_amount . '(' . $address_percent . '%)] Payment [' . $payment_amount . '(' . $payment_percent . '%)] DIF [' . $balance . ']' );

            $address->setS2pSurchargeBaseAmount( $payment_base_amount );
            $address->setBaseGrandTotal( $address->getBaseGrandTotal() + $address->getS2pSurchargeBaseAmount() );
        }

        $address->setS2pSurchargePercent( $payment_percent );

        return $this;
    }

    public function fetch( Mage_Sales_Model_Quote_Address $address )
    {
        /** @var Smart2Pay_Globalpay_Model_Pay $pay_obj */
        if( $address->getAddressType() != 'billing'
         or !($quote = $address->getQuote())
         or !($payment_obj = $quote->getPayment())
         or $payment_obj->isDeleted()
         or !($payment_amount = $payment_obj->getS2pSurchargeAmount())
         or !($payment_percent = $payment_obj->getS2pSurchargePercent()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper('globalpay/helper');

        $address->addTotal( array(
            'code'  => $this->getCode(),
            'title' => $helper_obj->format_surcharge_label( $payment_amount, $payment_percent ),
            'value' => $helper_obj->format_surcharge_value( $payment_amount, $payment_percent ),
        ) );

        return $this;
    }
}

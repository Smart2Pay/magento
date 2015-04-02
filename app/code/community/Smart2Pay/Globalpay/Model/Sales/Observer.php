<?php

class Smart2Pay_Globalpay_Model_Sales_Observer
{
    public function __construct()
    {
    }

    /**
     *   Removes applied surcharge from payment method when cart is changing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Smart2Pay_Globalpay_Model_Sales_Observer
     */
    public function remove_surcharge_from_cart( $observer )
    {
        $event = $observer->getEvent();

        /** @var Mage_Checkout_Model_Cart $cart */
        if( !($cart = $event->getCart())
         or !($quote = $cart->getQuote())
         or !($payment_obj = $quote->getPayment()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'remove_surcharge_from_cart', 'observer' );

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
        }

        return $this;
    }

    /**
     *   Removes applied surcharge from payment method when cart is changing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Smart2Pay_Globalpay_Model_Sales_Observer
     */
    public function remove_surcharge_from_quote( $observer )
    {
        $event = $observer->getEvent();

        /** @var Mage_Sales_Model_Quote $quote */
        if( !($quote = $event->getQuote())
         or !($payment_obj = $quote->getPayment()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'remove_surcharge_from_quote', 'observer' );

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
        }

        return $this;
    }

    /**
     *   Removes applied surcharge from payment method when cart is changing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Smart2Pay_Globalpay_Model_Sales_Observer
     */
    public function remove_surcharge_from_quote_item( $observer )
    {
        $event = $observer->getEvent();

        /** @var Mage_Sales_Model_Quote_Item $quote_item */
        if( !($quote_item = $event->getQuoteItem())
         or !($quote = $quote_item->getQuote())
         or !($payment_obj = $quote->getPayment()) )
            return $this;

        /** @var Smart2Pay_Globalpay_Model_Logger $logger_obj */
        $logger_obj = Mage::getModel( 'globalpay/logger' );

        $logger_obj->write( 'remove_surcharge_from_quote_item', 'observer' );

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
        }

        return $this;
    }
}

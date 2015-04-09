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

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );
        $payment_obj->setS2pSurchargeFixedAmount( 0 );
        $payment_obj->setS2pSurchargeFixedBaseAmount( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() - $address_obj->getS2pSurchargeFixedAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() - $address_obj->getS2pSurchargeFixedBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
            $address_obj->setS2pSurchargeFixedAmount( 0 );
            $address_obj->setS2pSurchargeFixedBaseAmount( 0 );
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

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );
        $payment_obj->setS2pSurchargeFixedAmount( 0 );
        $payment_obj->setS2pSurchargeFixedBaseAmount( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() - $address_obj->getS2pSurchargeFixedAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() - $address_obj->getS2pSurchargeFixedBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
            $address_obj->setS2pSurchargeFixedAmount( 0 );
            $address_obj->setS2pSurchargeFixedBaseAmount( 0 );
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

        $payment_obj->setS2pSurchargeAmount( 0 );
        $payment_obj->setS2pSurchargeBaseAmount( 0 );
        $payment_obj->setS2pSurchargePercent( 0 );
        $payment_obj->setS2pSurchargeFixedAmount( 0 );
        $payment_obj->setS2pSurchargeFixedBaseAmount( 0 );

        if( ($address_obj = $quote->getBillingAddress()) )
        {
            $address_obj->setGrandTotal( $address_obj->getGrandTotal() - $address_obj->getS2pSurchargeAmount() - $address_obj->getS2pSurchargeFixedAmount() );
            $address_obj->setBaseGrandTotal( $address_obj->getBaseGrandTotal() - $address_obj->getS2pSurchargeBaseAmount() - $address_obj->getS2pSurchargeFixedBaseAmount() );

            $address_obj->setS2pSurchargeAmount( 0 );
            $address_obj->setS2pSurchargeBaseAmount( 0 );
            $address_obj->setS2pSurchargePercent( 0 );
            $address_obj->setS2pSurchargeFixedAmount( 0 );
            $address_obj->setS2pSurchargeFixedBaseAmount( 0 );
        }

        return $this;
    }

    public function invoice_save( Varien_Event_Observer $observer )
    {
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        /** @var Mage_Sales_Model_Order $order */
        if( !($event = $observer->getEvent())
         or !($invoice = $event->getInvoice())
         or (
                !(float)$invoice->getS2pSurchargeAmount() and !(float)$invoice->getS2pSurchargeBaseAmount()
            and !(float)$invoice->getS2pSurchargeFixedAmount() and !(float)$invoice->getS2pSurchargeFixedBaseAmount()
            )
         or !($order = $invoice->getOrder())
         or !($order_payment = $order->getPayment()) )
            return $this;

        $order_payment->setS2pSurchargeAmountInvoiced( $order_payment->getS2pSurchargeAmountInvoiced() + $invoice->getS2pSurchargeAmount() + $invoice->getS2pSurchargeFixedAmount() );
        $order_payment->setS2pSurchargeBaseAmountInvoiced( $order_payment->getS2pSurchargeBaseAmountInvoiced() + $invoice->getS2pSurchargeBaseAmount() + $invoice->getS2pSurchargeFixedBaseAmount() );

        return $this;
    }

    public function order_payment_place( Varien_Event_Observer $observer )
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        /** @var Mage_Sales_Model_Order $order */
        if( !($event = $observer->getEvent())
         or !($payment = $event->getPayment())
         or !($order = $payment->getOrder())
         or (
                !(float)$payment->getS2pSurchargeAmount() and !(float)$payment->getS2pSurchargeBaseAmount()
            and !(float)$payment->getS2pSurchargeFixedAmount() and !(float)$payment->getS2pSurchargeFixedBaseAmount()
            ) )
            return $this;

        $order->setGrandTotal( $order->getGrandTotal() + $payment->getS2pSurchargeAmount() + $payment->getS2pSurchargeFixedAmount() );
        $order->setBaseGrandTotal( $order->getBaseGrandTotal() + $payment->getS2pSurchargeBaseAmount() + $payment->getS2pSurchargeFixedBaseAmount() );

        return $this;
    }
}

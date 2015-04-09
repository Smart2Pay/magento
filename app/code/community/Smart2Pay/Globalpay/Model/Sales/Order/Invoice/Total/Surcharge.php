<?php

class Smart2Pay_Globalpay_Model_Sales_Order_Invoice_Total_Surcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect( Mage_Sales_Model_Order_Invoice $invoice )
    {
        if( !($order = $invoice->getOrder())
         or !($order_payment = $order->getPayment()) )
            return $this;

        $invoice_amount = $order_payment->getS2pSurchargeAmount() + $order_payment->getS2pSurchargeFixedAmount() - $order_payment->getS2pSurchargeAmountInvoiced();
        $invoice_base_amount = $order_payment->getS2pSurchargeBaseAmount() + $order_payment->getS2pSurchargeFixedBaseAmount() - $order_payment->getS2pSurchargeBaseAmountInvoiced();

        if( $invoice_amount <= 0 or $invoice_base_amount <= 0 )
            return $this;

        $invoice->setGrandTotal( $invoice->getGrandTotal() + $invoice_amount );
        $invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $invoice_base_amount );

        $invoice->setS2pSurchargeAmount( $invoice_amount );
        $invoice->setS2pSurchargeBaseAmount( $invoice_base_amount );

        if( ($surcharge_amount = $order_payment->getS2pSurchargeAmount() - $order_payment->getS2pSurchargeAmountInvoiced()) < 0 )
            $surcharge_amount = 0;

        if( ($surcharge_fixed_amount = $invoice_amount - $surcharge_amount) < 0 )
            $surcharge_fixed_amount = 0;

        if( ($surcharge_base_amount = $order_payment->getS2pSurchargeBaseAmount() - $order_payment->getS2pSurchargeBaseAmountInvoiced()) < 0 )
            $surcharge_base_amount = 0;

        if( ($surcharge_fixed_base_amount = $invoice_base_amount - $surcharge_base_amount) < 0 )
            $surcharge_fixed_base_amount = 0;

        $invoice->setS2pSurchargeAmount( $surcharge_amount );
        $invoice->setS2pSurchargeBaseAmount( $surcharge_base_amount );
        $invoice->setS2pSurchargeFixedAmount( $surcharge_fixed_amount );
        $invoice->setS2pSurchargeFixedBaseAmount( $surcharge_fixed_base_amount );

        /*
        $feeAmountLeft = $order_payment->getS2pSurchargeAmount() + $order_payment->getS2pSurchargeFixedAmount() - $order_payment->getS2pSurchargeAmountInvoiced();
        $baseFeeAmountLeft = $order_payment->getS2pSurchargeBaseAmount() + $order_payment->getS2pSurchargeFixedBaseAmount() - $order_payment->getS2pSurchargeBaseAmountInvoiced();

        if( abs( $baseFeeAmountLeft ) < $invoice->getBaseGrandTotal() )
        {
            $invoice->setGrandTotal( $invoice->getGrandTotal() + $feeAmountLeft );
            $invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $baseFeeAmountLeft );
        } else
        {
            $feeAmountLeft = $invoice->getGrandTotal() * -1;
            $baseFeeAmountLeft = $invoice->getBaseGrandTotal() * -1;

            $invoice->setGrandTotal(0);
            $invoice->setBaseGrandTotal(0);
        }

        //$amount_invoiced = $feeAmountLeft;
        //if( $invoice->getS2pSurchargeAmount() < $amount_invoiced )
        $invoice->setS2pSurchargeAmount( $feeAmountLeft );
        $invoice->setS2pSurchargeBaseAmount( $baseFeeAmountLeft );
        */

        return $this;
    }
}

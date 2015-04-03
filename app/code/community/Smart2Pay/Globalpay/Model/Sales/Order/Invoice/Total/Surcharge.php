<?php

class Smart2Pay_Globalpay_Model_Sales_Order_Invoice_Total_Surcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect( Mage_Sales_Model_Order_Invoice $invoice )
    {
        if( !($order = $invoice->getOrder())
         or !($order_payment = $order->getPayment()) )
            return $this;

        $feeAmountLeft = $order_payment->getS2pSurchargeAmount() - $order_payment->getS2pSurchargeAmountInvoiced();
        $baseFeeAmountLeft = $order_payment->getS2pSurchargeBaseAmount() - $order_payment->getS2pSurchargeBaseAmountInvoiced();

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

        $invoice->setS2pSurchargeAmount( $feeAmountLeft );
        $invoice->setS2pSurchargeBaseAmount( $baseFeeAmountLeft );

        return $this;
    }
}

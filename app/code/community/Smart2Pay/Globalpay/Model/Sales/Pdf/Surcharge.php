<?php

class Smart2Pay_Globalpay_Model_Sales_Pdf_Surcharge extends Mage_Sales_Model_Order_Pdf_Total_Default
{

    /**
     * Check if we can display total information in PDF
     *
     * @return bool
     */
    public function canDisplay()
    {
        $amount = $this->getAmount();
        $invoice_obj = $this->getSource();
        if( !($invoice_obj instanceof Mage_Sales_Model_Order_Invoice) )
            return false;

        return $this->getDisplayZero() || ($amount != 0);
    }

    /**
     * Get Total amount from source
     *
     * @return float
     */
    public function getAmount()
    {
        $invoice_obj = $this->getSource();
        if( !($invoice_obj instanceof Mage_Sales_Model_Order_Invoice) )
            return 0;

        return $invoice_obj->getS2pSurchargeAmount() + $invoice_obj->getS2pSurchargeFixedAmount();
    }

    /**
     * Check if tax amount should be included to grandtotal block
     * array(
     *  $index => array(
     *      'amount'   => $amount,
     *      'label'    => $label,
     *      'font_size'=> $font_size
     *  )
     * )
     * @return array
     */
    public function getTotalsForDisplay()
    {
        $source = $this->getSource();
        if( !($source instanceof Mage_Sales_Model_Order_Invoice)
         or !$this->getOrder() )
            return array();

        /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
        $helper_obj = Mage::helper('globalpay/helper');

        $amount = $this->getAmount();

        $totals = array();
        $totals['label'] = $helper_obj->format_surcharge_label( $amount, 0 );
        $totals['amount'] = $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt( $amount );
        $totals['font_size'] = ($this->getFontSize() ? $this->getFontSize() : 7);

        return array( $totals );
    }


}

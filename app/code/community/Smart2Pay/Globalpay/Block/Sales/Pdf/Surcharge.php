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
        //$amount = $this->getAmount();
        //return $this->getDisplayZero() || ($amount != 0);
        return true;
    }

    /**
     * Get Total amount from source
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->getSource()->getDataUsingMethod($this->getSourceField());
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

        $totals = array();
        $totals['label'] = '['.get_class( $source ).']';
        $totals['amount'] = 0;
        $totals['font_size'] = ($this->getFontSize() ? $this->getFontSize() : 7);

        return $totals;
    }


}

<?php
class Smart2Pay_Globalpay_Block_Sales_Order_Invoice_Total extends Mage_Core_Block_Template
{
    /**
     * Get label cell tag properties
     *
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get order store object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get totals source object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get value cell tag properties
     *
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * Add totals for surcharge
     *
     * @return Smart2Pay_Globalpay_Block_Adminhtml_Sales_Order_Invoice_Total
     */
    public function initTotals()
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_Invoice_View_Form $parent_block */
        $parent_block = $this->getParentBlock();

        if( ($invoice_obj = $parent_block->getInvoice())
        and ($order_payment_obj = $parent_block->getOrder()->getPayment())
        and ((float)$invoice_obj->getS2pSurchargeAmount() != 0 or (float)$invoice_obj->getS2pSurchargeFixedAmount() != 0) )
        {
            /** @var Smart2Pay_Globalpay_Helper_Helper $helper_obj */
            $helper_obj = Mage::helper('globalpay/helper');

            //$source = $this->getSource();
            $amount = $invoice_obj->getS2pSurchargeAmount();
            $fixed_amount = $invoice_obj->getS2pSurchargeFixedAmount();
            $base_amount = $invoice_obj->getS2pSurchargeBaseAmount();
            $base_fixed_amount = $invoice_obj->getS2pSurchargeFixedBaseAmount();
            $percent = $order_payment_obj->getS2pSurchargePercent();

            $this->getParentBlock()->addTotal(new Varien_Object(array(
                'code'   => 'globalpay',
                'strong' => false,
                'label'  => $helper_obj->format_surcharge_label( $amount, $percent ),
                'value'  => $helper_obj->format_surcharge_value( $amount + $fixed_amount, $percent ),
                'base_value'  => $helper_obj->format_surcharge_value( $base_amount + $base_fixed_amount, $percent ),
                'area'  => 'footer',
            )), 'shipping');
        }

        return $this;
    }
}

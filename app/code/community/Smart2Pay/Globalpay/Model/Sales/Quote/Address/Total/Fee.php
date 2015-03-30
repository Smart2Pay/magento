<?php

class Smart2Pay_Globalpay_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'globalpay';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this; //this makes only address type shipping to come through
        }


        $quote = $address->getQuote();

        if(Excellence_Fee_Model_Fee::canApply($address)){ //your business logic
            $exist_amount = $quote->getFeeAmount();
            $fee = Excellence_Fee_Model_Fee::getFee();
            $balance = $fee - $exist_amount;
            $address->setFeeAmount($balance);
            $address->setBaseFeeAmount($balance);

            $quote->setFeeAmount($balance);

            $address->setGrandTotal($address->getGrandTotal() + $address->getFeeAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseFeeAmount());
        }
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getFeeAmount();
        $address->addTotal(array(
            'code'=>$this->getCode(),
            'title'=>Mage::helper('fee')->__('Fee'),
            'value'=> $amt
        ));
        return $this;
    }
}

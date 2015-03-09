<?php

class Smart2Pay_Globalpay_Block_Paymentinstructions extends Mage_Checkout_Block_Onepage_Success
{
    public $referenceNumber;
    public $amountToPay;
    public $accountHolder;
    public $merchantTransactionID;
    public $bankName;
    public $accountNumber;
    public $SWIFT_BIC;
    public $IBAN;
    public $accountCurrency;
    public $entityNumber;
    public $displayPaymentInstructions = false;

    public function __construct()
    {
        parent::__construct();

        if( !($query = $this->getRequest()->getParams()) )
        {
            $this->displayPaymentInstructions = false;
            return;
        }

        if( empty( $query['ReferenceNumber'] ) )
            $this->displayPaymentInstructions = false;
        else
            $this->displayPaymentInstructions = true;

        if( isset( $query['ReferenceNumber'] ) )
            $this->referenceNumber = $query['ReferenceNumber'];

        if( isset( $query['AmountToPay'] ) )
            $this->amountToPay = $query['AmountToPay'];

        if( isset( $query['AccountHolder'] ) )
            $this->accountHolder = $query['AccountHolder'];

        if( isset( $query['BankName'] ) )
            $this->bankName = $query['BankName'];

        if( isset( $query['AccountNumber'] ) )
            $this->accountNumber = $query['AccountNumber'];

        if( isset( $query['SWIFT_BIC'] ) )
            $this->SWIFT_BIC = $query['SWIFT_BIC'];

        if( isset( $query['IBAN'] ) )
            $this->IBAN = $query['IBAN'];

        if( isset( $query['AccountCurrency'] ) )
            $this->accountCurrency = $query['AccountCurrency'];

        if( isset( $query['EntityNumber'] ) )
            $this->entityNumber = $query['EntityNumber'];
    }
}

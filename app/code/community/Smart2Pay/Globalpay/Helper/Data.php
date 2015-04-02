<?php

class Smart2Pay_Globalpay_Helper_Data extends Mage_Payment_Helper_Data
{
    public function s2p_mb_substr( $message, $start, $length )
    {
        if( function_exists( 'mb_substr' ) )
            return mb_substr( $message, $start, $length, 'UTF-8' );
		else
			return substr( $message, $start, $length );
	}

    public function computeSHA256Hash( $message )
    {
        if( function_exists( 'mb_strtolower' ) )
			return hash( 'sha256', mb_strtolower( $message, 'UTF-8' ) );
		else
			return hash( 'sha256', strtolower( $message ) );
	}
}


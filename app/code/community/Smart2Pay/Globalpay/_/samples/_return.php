<?php

    include( '../bootstrap.php' );


    //
    //
    // !!!!! THIS SCRIPT IS INTEDED TO GIVE YOU A STARTING POINT ON HOW YOU SHOULD HANDLE RETURNING PAGE
    // !!!!! PLEASE DON'T USE THIS SCRIPT AS RETURN SCRIPT, INSTEAD COPY IT IN YOUR ENVIRONMENT AND CHANGE IT. (THIS SCRIPT MIGHT CHANGE IN TIME)
    //
    // !!!!! DO NOT UPDATE TRANSACTION STATUSES IN THIS SCRIPT! WE WILL SEND TRANSACTION DETAILS TO YOUR NOTIFICATION SCIPRT (SETUP IN OUR DASHBOARD)
    // !!!!! AND YOU MUST UPDATE TRANSACTION AND ORDER STATUS IN YOUR NOTIFICATION SCRIPT
    // !!!!!  THIS SCRIPT IS INTENDED TO NOTIFY YOUR CLIENT ABOUT CURRENT STATUS OF TRANSACTION.
    //
    // Please note that transaction can be in Open or Processing status which means a final status notification will follow
    //
    //


    include_once( S2P_SDK_DIR_CLASSES . 's2p_sdk_return.inc.php' );

    define( 'S2P_SDK_RETURN_IDENTIFIER', microtime( true ) );

    S2P_SDK\S2P_SDK_Return::logging_enabled( true );

    S2P_SDK\S2P_SDK_Return::logf( '--- Return START --------------------', false );

    $return_params = array();
    $return_params['auto_extract_parameters'] = true;

    /** @var S2P_SDK\S2P_SDK_Return $return_obj */
    if( !($return_obj = S2P_SDK\S2P_SDK_Module::get_instance( 'S2P_SDK_Return', $return_params ))
     or $return_obj->has_error() )
    {
        if( (S2P_SDK\S2P_SDK_Module::st_has_error() and $error_arr = S2P_SDK\S2P_SDK_Module::st_get_error())
         or (!empty( $return_obj ) and $return_obj->has_error() and ($error_arr = $return_obj->get_error())) )
            S2P_SDK\S2P_SDK_Return::logf( 'Error ['.$error_arr['error_no'].']: '.$error_arr['display_error'] );
        else
            S2P_SDK\S2P_SDK_Return::logf( 'Error initiating return object.' );

        exit;
    }

    if( !($return_parameters = $return_obj->get_parameters()) )
    {
        S2P_SDK\S2P_SDK_Return::logf( 'Couldn\'t extract return parameters.' );
        exit;
    }

    if( empty( $return_parameters['MerchantTransactionID'] ) )
    {
        S2P_SDK\S2P_SDK_Return::logf( 'Unknown transaction' );
        exit;
    }

    S2P_SDK\S2P_SDK_Return::logf( 'Transaction ['.$return_parameters['MerchantTransactionID'].']: ' );

    switch( $return_parameters['data'] )
    {
        default:
            if( ($status_str = S2P_SDK\S2P_SDK_Meth_Payments::valid_status( $return_parameters['data'] )) )
                S2P_SDK\S2P_SDK_Return::logf( 'Transaction status ('.$return_parameters['data'].'): '.$status_str );
            else
                S2P_SDK\S2P_SDK_Return::logf( 'unknown transaction status ('.$return_parameters['data'].')' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_OPEN:
            S2P_SDK\S2P_SDK_Return::logf( 'Open (not finalized yet)' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_PENDING_CUSTOMER:
        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_PENDING_PROVIDER:
            S2P_SDK\S2P_SDK_Return::logf( 'Pending' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_SUCCESS:
            S2P_SDK\S2P_SDK_Return::logf( 'Success' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CANCELLED:
            S2P_SDK\S2P_SDK_Return::logf( 'Cancelled' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_FAILED:
            S2P_SDK\S2P_SDK_Return::logf( 'Failed' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_EXPIRED:
            S2P_SDK\S2P_SDK_Return::logf( 'Expired' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_PROCESSING:
            S2P_SDK\S2P_SDK_Return::logf( 'Processing (not finalized yet)' );
        break;

        case S2P_SDK\S2P_SDK_Meth_Payments::STATUS_AUTHORIZED:
            S2P_SDK\S2P_SDK_Return::logf( 'Authorized' );
        break;
    }

    if( !empty( $return_parameters['extra_info']['has_data'] ) )
    {
        echo '<br/><br/><hr/><br/><br/>'.
             'Some extra information about transaction which might help customer finalize the payment:';

        $extra_info = $return_parameters['extra_info'];

        if( isset( $extra_info['has_data'] ) )
            unset( $extra_info['has_data'] );

        echo '<pre>'; var_dump( $extra_info ); echo '</pre>';

        S2P_SDK\S2P_SDK_Return::logf( 'Extra data:' );

        ob_start();
        var_dump( $extra_info );
        S2P_SDK\S2P_SDK_Return::logf( ob_get_clean() );
    }

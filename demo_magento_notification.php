<?php

    // Magento demo site signature
    $signature = '8bf71f75-68d9';

    if( !($request = @file_get_contents( 'php://input' )) )
    {
        echo 'No input';
        exit;
    }
    
    $log_file = 'demo_magento_notification.log';

    @file_put_contents( $log_file, '['.date( 'd-m-Y H:i:s' ).'] Demo request ['.$request.']'."\r\n", FILE_APPEND );

    $request_vars = array();

    $recomposedHashString = '';
    $pairs = explode( '&', $request );
    foreach( $pairs as $pair )
    {
        $nv            = explode( '=', $pair );

        $name          = $nv[0];
        $request_vars[ $name ] = (!empty( $nv[1] )?$nv[1]:'');

        if( strtolower( $name ) != 'hash' )
            $recomposedHashString .= $name . $request_vars[ $name ];
    }


    //check hash
    $notificationType = $request_vars['NotificationType'];

    $recomposedHashString .= $signature;
    $receivedHash = $request_vars['Hash'];

    // Message is not authentic
    if( hash( 'sha256', mb_strtolower( $recomposedHashString ) ) != $receivedHash )
    {
        $rsp = 'Bad hash.';
    }

    elseif( strtolower( $notificationType ) == 'payment' )
    {

        $paymentID = (!empty( $request_vars['PaymentID'] )?(int)$request_vars['PaymentID']:0);

        $myhash    = hash( 'sha256', strtolower( 'NotificationTypePaymentPaymentID' . $paymentID . $signature ) );
        $rsp       = 'NotificationType=Payment&PaymentID=' . $paymentID . '&Hash=' . $myhash;
    } elseif( strtolower( $notificationType ) == 'preapproval' )
    {
        $preapprovalID = (!empty( $request_vars['PreapprovalID'] )?(int)$request_vars['PreapprovalID']:0);

        $myhash = hash( 'sha256', strtolower( 'NotificationTypePreapprovalPreapprovalID' . $preapprovalID . $signature ) );
        $rsp    = 'NotificationType=Preapproval&PreapprovalID=' . $preapprovalID . '&Hash=' . $myhash;
    } elseif( strtolower( $notificationType ) == 'refund' )
    {

        $refundID = (!empty( $request_vars['RefundID'] )?(int)$request_vars['RefundID']:0);

        $myhash = hash( 'sha256', strtolower( 'NotificationTypeRefundRefundID' . $refundID . $signature ) );
        $rsp    = 'NotificationType=Refund&RefundID=' . $refundID . '&Hash=' . $myhash;
    }

    echo $rsp;

    @file_put_contents( $log_file, '['.date( 'd-m-Y H:i:s' ).'] Response ['.$rsp.']'."\r\n", FILE_APPEND );

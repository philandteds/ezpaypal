<?php

eZDebug::writeDebug( 'notify_url.php START' );

$time = strftime( "%d-%m-%Y %H-%M" );
$sessionId = session_id();
$postDump = "";
print_r($_POST, $postDump);
eZDebug::writeError("## Paypal notify_url: \n## Time: $time \n## Session: $sessionId \n## Paypal POST: $postDump");


$checker = new eZPaypalChecker( );

if ( $checker->createDataFromPOST() )
{
//    eZDebug::writeDebug( 'createDataFromPOST success' );
    eZDebug::writeError( 'createDataFromPOST success' );
    unset( $_POST );
    if ( $checker->requestValidation() && $checker->checkPaymentStatus() )
    {
        eZDebug::writeDebug( "Validation success" );
        $data = unserialize( $checker->getFieldValue( 'custom' ) );
        $orderID = (int) $data['order_id'];
        if ( $checker->setupOrderAndPaymentObject( $orderID ) )
        {
            eZDebug::writeDebug( "setupOrderAndPaymentObject success" );
            $amount = $checker->getFieldValue( 'mc_gross' );
            $currency = $checker->getFieldValue( 'mc_currency' );
            if ( $checker->checkAmount( $amount ) )
            {
                $checker->approvePayment();
//                eZDebug::writeDebug( "approvePayment success" );
                eZDebug::writeError( "approvePayment success" );

            }
            else
            {
//                eZDebug::writeDebug( "approvePayment failed" );
                eZDebug::writeError( "approvePayment failed" );
            }
        }
        else
        {
            eZDebug::writeError( "setupOrderAndPaymentObject failed" );
        }
    }
    else
    {
        eZDebug::writeError( "Validation failed" );
    }
}
else
{
    eZDebug::writeError( "No post Data" );
}

eZDebug::writeDebug( 'notify_url.php END' );

?>
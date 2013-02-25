<?php
//
// Definition of eZPaypalGateway class
//
// Created on: <18-Jul-2004 14:18:58 dl>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Paypal Payment Gateway
// SOFTWARE RELEASE: 1.0
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezpaypalgateway.php
*/

/*!
  \class eZPaypalGateway ezpaypalgateway.php
  \brief The class eZPaypalGateway implements
  functions to perform redirection to the PayPal
  payment server.
*/

define( "EZ_PAYMENT_GATEWAY_TYPE_PAYPAL", "ezpaypal" );

class eZPaypalGateway extends eZRedirectGateway
{
    /*!
        Constructor.
    */
    function eZPaypalGateway()
    {
        //__DEBUG__
            $this->logger   = eZPaymentLogger::CreateForAdd( "var/log/eZPaypalType.log" );
            $this->logger->writeTimedString( 'eZPaypalGateway::eZPaypalGateway()' );
        //___end____
    }

    /*!
        Creates new eZPaypalGateway object.
    */
    function createPaymentObject( $processID, $orderID )
    {
        //__DEBUG__
            $this->logger->writeTimedString("createPaymentObject");
        //___end____

        return eZPaymentObject::createNew( $processID, $orderID, 'Paypal' );
    }

    /*!
        Creates redirectional url to paypal server.
    */
    function createRedirectionUrl( $process )
    {
        //__DEBUG__
            $this->logger->writeTimedString("createRedirectionUrl");
        //___end____

        $paypalINI      = eZINI::instance( 'paypal.ini' );

        $paypalServer   = $paypalINI->variable( 'ServerSettings', 'ServerName');
        $requestURI     = $paypalINI->variable( 'ServerSettings', 'RequestURI');
        $business       = $paypalINI->variable( 'PaypalSettings', 'Business' );
        $maxDescLen     = $paypalINI->variable( 'PaypalSettings', 'MaxDescriptionLength');

        $processParams  = $process->attribute( 'parameter_list' );
        $orderID        = $processParams['order_id'];

        $indexDir       = eZSys::indexDir();
        $localHost      = eZSys::serverURL();
        $localURI       = eZSys::serverVariable( 'REQUEST_URI' );

        $order          = eZOrder::fetch( $orderID );

        $locale         = eZLocale::instance();
        $accountInfo    = $order->attribute( 'account_information' );
        $productItems   = $order->productItems();
        
        $parameters = array();
        
        //shopping carts variables
        $parameters['cmd'] = '_cart';
        $parameters['upload'] = '1';
        $parameters['business'] = $business;
        $parameters['shopping_url'] = $localHost . $indexDir . "/shop/basket/";
        
        //paypal page style variables
        $parameters['page_style'] = $paypalINI->variable( 'PaypalSettings', 'PageStyle' );
        $parameters['image_url'] = $localHost . $paypalINI->variable( 'PaypalSettings', 'LogoURI' );
        $parameters['lc'] = $locale->countryCode();
        $parameters['no_note'] = $paypalINI->variable( 'PaypalSettings', 'NoNote' );
        $parameters['cn'] = ($noNote == 1) ? '' : $paypalINI->variable( 'PaypalSettings', 'NoteLabel' );
        $parameters['no_shipping'] = 1;
        $parameters['return'] = $localHost  . $indexDir . "/shop/checkout/";
        $parameters['rm'] = 2;
        $parameters['cancel_return'] = $localHost . $indexDir . "/shop/basket/";
        
        //transactions variables
        $parameters['custom'] = $orderID;
        $parameters['invoice'] = $orderID;
        $parameters['currency_code'] = $order->currencyCode();
        
        //special features variables
        //bn in the form <Company>_ShoppingCart_WPS_<Country>
        //$parameters['bn'] = ''; 
        $parameters['notify_url'] = $localHost . $indexDir . "/paypal/notify_url/";
        
        //filling variables, customer informations
        $parameters['address1'] = $accountInfo['street1'];
        $parameters['address2'] = $accountInfo['street2'];
        $parameters['city'] = $accountInfo['place'];
        $parameters['country'] = $accountInfo['country'];
        $parameters['email'] = $accountInfo['email'];
        $parameters['first_name'] = $accountInfo['first_name'];
        $parameters['last_name'] = $accountInfo['last_name'];
        $parameters['zip'] = $accountInfo['zip'];
        
        //individuals items variable
        for( $i = 0; $i < count( $productItems ); $i++ )
        {
            $j = $i + 1;
            $itemName = $productItems[$i]['object_name'];
            $itemNameLen = strlen( $itemName );
            
            if( ( $maxDescLen > 0 ) && ( $itemNameLen > $maxDescLen ) )
            {
                $itemName = substr( $itemName, 0, $maxDescLen - 3 ) ;
                $itemName .= '...';
            }
            
            $parameters['item_name_'.$j] = $itemName;
            $parameters['amount_'.$j] = $productItems[$i]['price_inc_vat'];
            $parameters['quantity_'.$j] = $productItems[$i]['item_count'];
        }
        
        $url = $paypalServer  . $requestURI . '?';
        $url .= http_build_query( $parameters );

        //__DEBUG__
            $this->logger->writeTimedString( $parameters );
        //___end____

        return $url;
    }
}

eZPaymentGatewayType::registerGateway( EZ_PAYMENT_GATEWAY_TYPE_PAYPAL, "ezpaypalgateway", "Paypal" );

?>

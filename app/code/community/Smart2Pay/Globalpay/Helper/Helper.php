<?php

class Smart2Pay_Globalpay_Helper_Helper extends Mage_Core_Helper_Abstract
{
    const SQL_DATETIME = 'Y-m-d H:i:s', EMPTY_DATETIME = '0000-00-00 00:00:00';
    const SQL_DATE = 'Y-m-d', EMPTY_DATE = '0000-00-00';

    public static function klarna_price( $amount )
    {
        return (float)number_format( $amount, 2, '.', '' );
    }

    public static function foobar( $str )
    {
        if( !($fil = @fopen( '/home/andy/magento.log', 'a' )) )
            return false;

        $file = 'N/A';
        $line = 0;
        $backtrace = debug_backtrace();
        if( !empty( $backtrace[0] ) )
        {
            $file = $backtrace[0]['file'];
            $line = $backtrace[0]['line'];
        }

        @fputs( $fil, date( 'Y-m-d H:i:s' ).' - '.$file.':'.$line.' - '.$str."\n" );
        @fflush( $fil );
        @fclose( $fil );

        return true;
    }

    public static function cart_products_to_string( $products_arr, $cart_original_amount, $params = false )
    {
        $return_arr = array();
        $return_arr['total_check'] = 0;
        $return_arr['total_to_pay'] = 0;
        $return_arr['total_before_difference_amount'] = 0;
        $return_arr['total_difference_amount'] = 0;
        $return_arr['surcharge_difference_amount'] = 0;
        $return_arr['surcharge_difference_index'] = 0;
        $return_arr['buffer'] = '';
        $return_arr['articles_arr'] = array();
        $return_arr['sdk_articles_arr'] = array();
        $return_arr['articles_meta_arr'] = array();
        $return_arr['transport_index'] = array();

        $cart_original_amount = floatval( $cart_original_amount );

        if( $cart_original_amount == 0
         or empty( $products_arr ) or !is_array( $products_arr ) )
            return $return_arr;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['transport_amount'] ) )
            $params['transport_amount'] = 0;
        if( empty( $params['total_surcharge'] ) )
            $params['total_surcharge'] = 0;
        if( empty( $params['amount_to_pay'] ) )
            $params['amount_to_pay'] = $cart_original_amount;

        $amount_to_pay = floatval( $params['amount_to_pay'] );

        $return_arr['total_to_pay'] = $amount_to_pay;

        $return_str = '';
        $articles_arr = array();
        $sdk_articles_arr = array();
        $articles_meta_arr = array();
        $articles_knti = 0;
        $items_total_amount = 0;
        $biggest_price = 0;
        $biggest_price_knti = 0;
        foreach( $products_arr as $product_arr )
        {
            if( empty( $product_arr ) or !is_array( $product_arr ) )
                continue;

            // If products are from quotes we should use qty
            if( !isset( $product_arr['qty_ordered'] )
            and isset( $product_arr['qty'] ) )
                $product_arr['qty_ordered'] = $product_arr['qty'];

            // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
            $article_arr = array();
            $article_arr['ID'] = $product_arr['product_id'];
            $article_arr['Name'] = $product_arr['name'];
            $article_arr['Quantity'] = intval( $product_arr['qty_ordered'] );
            $article_arr['Price'] = self::klarna_price( $product_arr['price_incl_tax'] );
            // VAT Percent
            $article_arr['VAT'] = self::klarna_price( $product_arr['tax_percent'] );
            // $article_arr['Discount'] = 0;
            $article_arr['Type'] = 1;

            if( $article_arr['Price'] > $biggest_price )
                $biggest_price_knti = $articles_knti;

            $articles_arr[$articles_knti] = $article_arr;

            $article_meta_arr = array();
            $article_meta_arr['total_price'] = (float)($article_arr['Price'] * $article_arr['Quantity']);
            $article_meta_arr['price_perc'] = ($article_meta_arr['total_price'] * 100) / $cart_original_amount;
            $article_meta_arr['surcharge_amount'] = 0;

            $articles_meta_arr[$articles_knti] = $article_meta_arr;

            $items_total_amount += $article_meta_arr['total_price'];

            $articles_knti++;
        }

        if( empty( $articles_arr ) )
            return $return_arr;

        $transport_index = 0;
        if( $params['transport_amount'] != 0 )
        {
            // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
            $article_arr = array();
            $article_arr['ID'] = 0;
            $article_arr['Name'] = 'Transport';
            $article_arr['Quantity'] = 1;
            $article_arr['Price'] = self::klarna_price( $params['transport_amount'] );
            $article_arr['VAT'] = 0;
            //$article_arr['Discount'] = 0;
            $article_arr['Type'] = 2;

            $articles_arr[$articles_knti] = $article_arr;

            $transport_index = $articles_knti;

            $article_meta_arr = array();
            $article_meta_arr['total_price'] = (float)($article_arr['Price'] * $article_arr['Quantity']);
            $article_meta_arr['price_perc'] = 0;
            $article_meta_arr['surcharge_amount'] = 0;

            $articles_meta_arr[$articles_knti] = $article_meta_arr;

            $items_total_amount += $article_meta_arr['total_price'];

            $articles_knti++;
        }

        // Apply surcharge (if required) depending on product price percentage of full amount
        $total_surcharge = 0;
        if( $params['total_surcharge'] != 0 )
        {
            $total_surcharge = $params['total_surcharge'];
            foreach( $articles_arr as $knti => $article_arr )
            {
                if( $articles_arr[$knti]['Type'] != 1 )
                    continue;

                $total_article_surcharge = (($articles_meta_arr[$knti]['price_perc'] * $params['total_surcharge'])/100);

                $article_unit_surcharge = self::klarna_price( $total_article_surcharge/$articles_arr[$knti]['Quantity'] );

                $articles_arr[$knti]['Price'] += $article_unit_surcharge;
                $articles_meta_arr[$knti]['surcharge_amount'] = $article_unit_surcharge;

                $items_total_amount += ($article_unit_surcharge * $articles_arr[$knti]['Quantity']);
                $total_surcharge -= ($article_unit_surcharge * $articles_arr[$knti]['Quantity']);
            }

            // If after applying all surcharge amounts as percentage of each product price we still have a difference, apply difference on product with biggest price
            if( $total_surcharge != 0 )
            {
                $article_unit_surcharge = self::klarna_price( $total_surcharge/$articles_arr[$biggest_price_knti]['Quantity'] );

                $articles_arr[$biggest_price_knti]['Price'] += $article_unit_surcharge;
                $articles_meta_arr[$biggest_price_knti]['surcharge_amount'] += $article_unit_surcharge;
                $items_total_amount += ($article_unit_surcharge * $articles_arr[$biggest_price_knti]['Quantity']);

                $return_arr['surcharge_difference_amount'] = $total_surcharge;
                $return_arr['surcharge_difference_index'] = $biggest_price_knti;
            }
        }

        $return_arr['total_before_difference_amount'] = $items_total_amount;

        if( self::klarna_price( $items_total_amount ) != self::klarna_price( $amount_to_pay ) )
        {
            // v1. If we still have a difference apply it on biggest price product
            //$amount_diff = self::klarna_price( ($amount_to_pay - $items_total_amount)/$articles_arr[$biggest_price_knti]['Quantity'] );
            //$articles_arr[$biggest_price_knti]['Price'] += $amount_diff;

            // v2. If we still have a difference apply it on transport as it has quantity of 1 and we can apply a difference of 1 cent
            $amount_diff = self::klarna_price( $amount_to_pay - $items_total_amount );
            if( $transport_index )
            {
                // we have transport in articles...
                $articles_arr[$transport_index]['Price'] += $amount_diff;
                $articles_meta_arr[$transport_index]['total_price'] += $amount_diff;
            } else
            {
                // we DON'T have transport in articles...
                // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
                $article_arr = array();
                $article_arr['ID'] = 0;
                $article_arr['Name'] = 'Transport';
                $article_arr['Quantity'] = 1;
                $article_arr['Price'] = $amount_diff;
                $article_arr['VAT'] = 0;
                //$article_arr['Discount'] = 0;
                $article_arr['Type'] = 2;

                $articles_arr[$articles_knti] = $article_arr;

                $transport_index = $articles_knti;

                $article_meta_arr = array();
                $article_meta_arr['total_price'] = $article_arr['Price'];
                $article_meta_arr['price_perc'] = 0;
                $article_meta_arr['surcharge_amount'] = 0;

                $articles_meta_arr[$articles_knti] = $article_meta_arr;

                $articles_knti++;
            }


            $return_arr['total_difference_amount'] = self::klarna_price( $amount_to_pay - $items_total_amount );
        }

        $return_arr['transport_index'] = $transport_index;

        $total_check = 0;
        $hpp_to_sdk_keys = array(
            'ID' => 'merchantarticleid',
            'Name' => 'name',
            'Quantity' => 'quantity',
            'Price' => 'price',
            'VAT' => 'vat',
            'Discount' => 'discount',
            'Type' => 'type',
        );
        foreach( $articles_arr as $knti => $article_arr )
        {
            $total_check += (float)($article_arr['Price'] * $article_arr['Quantity']);

            $article_arr['Price'] = $article_arr['Price'] * 100;
            $article_arr['VAT'] = $article_arr['VAT'] * 100;
            //$article_arr['Discount'] = $article_arr['Discount'] * 100;

            $article_buf = '';
            $sdk_article = array();
            foreach( $article_arr as $key => $val )
            {
                if( !empty( $hpp_to_sdk_keys[$key] ) )
                    $sdk_article[$hpp_to_sdk_keys[$key]] = $val;

                $article_buf .= ($article_buf!=''?'&':'').$key.'='.str_replace( array( '&', ';', '=' ), ' ', $val );
            }

            if( !empty( $sdk_article ) )
                $sdk_articles_arr[] = $sdk_article;

            $return_arr['buffer'] .= $article_buf.';';
        }

        $return_arr['buffer'] = substr( $return_arr['buffer'], 0, -1 );

        // $return_arr['buffer'] = rawurlencode( $return_arr['buffer'] );

        $return_arr['total_check'] = $total_check;
        $return_arr['articles_arr'] = $articles_arr;
        $return_arr['articles_meta_arr'] = $articles_meta_arr;

        $return_arr['sdk_articles_arr'] = $sdk_articles_arr;

        return $return_arr;
    }

    public static function logf( $data )
    {
        Mage::getModel('core/log_adapter', 'payment_smart2pay.log')
            ->log( $data );
    }

    public function mage_exception( $code, $messages_arr )
    {
        if( is_string( $messages_arr ) )
            $messages_arr = array( Mage_Core_Model_Message::ERROR => $messages_arr );

        if( empty( $code ) )
            $code = -1;

        if( empty( $messages_arr ) or !is_array( $messages_arr ) )
            return Mage::exception( 'Mage_Core', $this->__( 'Unknown error' ), $code );

        $exception_obj = new Mage_Core_Exception();

        $error_types = array( Mage_Core_Model_Message::NOTICE, Mage_Core_Model_Message::WARNING, Mage_Core_Model_Message::ERROR, Mage_Core_Model_Message::SUCCESS );
        $message_factory_obj = new Mage_Core_Model_Message();
        foreach( $error_types as $type )
        {
            if( empty( $messages_arr[$type] ) or !is_array( $messages_arr[$type] ) )
                continue;

            foreach( $messages_arr[$type] as $message_arr )
            {
                if( is_string( $message_arr ) )
                    $message_arr = array( 'message' => $message_arr );

                if( !is_array( $message_arr ) )
                    continue;

                if( empty( $message_arr['class'] ) )
                    $message_arr['class'] = '';
                if( empty( $message_arr['method'] ) )
                    $message_arr['method'] = '';

                $message_obj = false;
                switch( $type )
                {
                    case Mage_Core_Model_Message::NOTICE:
                        $message_obj = $message_factory_obj->notice( $message_arr['message'], $message_arr['class'], $message_arr['method'] );
                    break;
                    case Mage_Core_Model_Message::WARNING:
                        $message_obj = $message_factory_obj->warning( $message_arr['message'], $message_arr['class'], $message_arr['method'] );
                    break;
                    case Mage_Core_Model_Message::ERROR:
                        $message_obj = $message_factory_obj->error( $message_arr['message'], $message_arr['class'], $message_arr['method'] );
                        $exception_obj->setMessage( $message_arr['message'].'<br/>', true );
                    break;
                    case Mage_Core_Model_Message::SUCCESS:
                        $message_obj = $message_factory_obj->success( $message_arr['message'], $message_arr['class'], $message_arr['method'] );
                    break;
                }

                if( empty( $message_obj ) )
                    continue;

                $exception_obj->addMessage( $message_obj );
            }
        }

        return $exception_obj;
    }

    public function isAdmin()
    {
        if( Mage::app()->getStore()->isAdmin()
         or Mage::getDesign()->getArea() == 'adminhtml' )
            return true;

        return false;
    }

    public function s2p_mb_substr( $message, $start, $length )
    {
        if( function_exists( 'mb_substr' ) )
            return mb_substr( $message, $start, $length, 'UTF-8' );
        else
            return substr( $message, $start, $length );
    }

    public function format_surcharge_fixed_amount_label( $fixed_amount, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['use_translate'] ) )
            $params['use_translate'] = true;

        $label = 'Payment Method Fixed Fee';

        return (!empty( $params['use_translate'] )?$this->__( $label ):$label);
    }

    public function format_surcharge_fixed_amount_value( $fixed_amount, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['format_price'] ) )
            $params['format_price'] = false;

        if( !empty( $params['format_price'] )
            and (!isset( $params['format_currency'] ) or !($params['format_currency'] instanceof Mage_Directory_Model_Currency)) )
            $params['format_currency'] = Mage::app()->getStore()->getDefaultCurrency();
        else
            $params['format_currency'] = false;

        if( !isset( $params['include_container'] ) )
            $params['include_container'] = true;

        if( empty( $params['format_options'] ) or !is_array( $params['format_options'] ) )
            $params['format_options'] = array();
        if( !isset( $params['format_options']['precision'] ) )
            $params['format_options']['precision'] = 2;

        if( empty( $params['format_price'] )
            or empty( $params['format_currency'] ) )
            $amount_str = $fixed_amount;

        else
            $amount_str = $params['format_currency']->format( $fixed_amount, $params['format_options'], $params['include_container'] );

        return $amount_str;
    }

    public function format_surcharge_percent_label( $amount, $percent, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['use_translate'] ) )
            $params['use_translate'] = true;

        $label = 'Payment Method Percent';

        return (!empty( $params['use_translate'] )?$this->__( $label ):$label);
    }

    public function format_surcharge_percent_value( $amount, $percent )
    {
        return $percent.'%';
    }

    public function format_surcharge_label( $amount, $percent, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['include_percent'] ) )
            $params['include_percent'] = true;
        if( !isset( $params['use_translate'] ) )
            $params['use_translate'] = true;

        if( !empty( $params['use_translate'] ) )
            $label = $this->__( 'Payment Method Fee' );

        else
        {
            $label = 'Payment Method Fee';

            if( !empty( $params['include_percent'] ) )
                $label .= ' (' . $percent . '%)';
        }

        return $label;
    }

    public function format_surcharge_value( $amount, $percent, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['format_price'] ) )
            $params['format_price'] = false;

        if( !empty( $params['format_price'] )
        and (!isset( $params['format_currency'] ) or !($params['format_currency'] instanceof Mage_Directory_Model_Currency)) )
            $params['format_currency'] = Mage::app()->getStore()->getDefaultCurrency();
        else
            $params['format_currency'] = false;

        if( !isset( $params['include_container'] ) )
            $params['include_container'] = true;

        if( empty( $params['format_options'] ) or !is_array( $params['format_options'] ) )
            $params['format_options'] = array();
        if( !isset( $params['format_options']['precision'] ) )
            $params['format_options']['precision'] = 2;

        if( empty( $params['format_price'] )
         or empty( $params['format_currency'] ) )
            $amount_str = $amount;

        else
            $amount_str = $params['format_currency']->format( $amount, $params['format_options'], $params['include_container'] );

        return $amount_str;
    }

    public function computeSHA256Hash( $message )
    {
        if( function_exists( 'mb_strtolower' ) )
			return hash( 'sha256', mb_strtolower( $message, 'UTF-8' ) );
		else
			return hash( 'sha256', strtolower( $message ) );
	}

    static public function value_to_string( $val )
    {
        if( is_object( $val ) or is_resource( $val ) )
            return false;

        if( is_array( $val ) )
            return json_encode( $val );

        if( is_string( $val ) )
            return '\''.$val.'\'';

        if( is_bool( $val ) )
            return (!empty( $val )?'true':'false');

        if( is_null( $val ) )
            return 'null';

        if( is_numeric( $val ) )
            return $val;

        return false;
    }

    static public function string_to_value( $str )
    {
        if( !is_string( $str ) )
            return null;

        if( ($val = @json_decode( $str, true )) !== null )
            return $val;

        if( is_numeric( $str ) )
            return $str;

        if( ($tch = substr( $str, 0, 1 )) == '\'' or $tch = '"' )
            $str = substr( $str, 1 );
        if( ($tch = substr( $str, -1 )) == '\'' or $tch = '"' )
            $str = substr( $str, 0, -1 );

        $str_lower = strtolower( $str );
        if( $str_lower == 'null' )
            return null;

        if( $str_lower == 'false' )
            return false;

        if( $str_lower == 'true' )
            return true;

        return $str;
    }

    static public function to_string( $lines_data )
    {
        if( empty( $lines_data ) or !is_array( $lines_data ) )
            return '';

        $lines_str = '';
        $first_line = true;
        foreach( $lines_data as $key => $val )
        {
            if( !$first_line )
                $lines_str .= "\r\n";

            $first_line = false;

            // In normal cases there cannot be '=' char in key so we interpret that value should just be passed as-it-is
            if( substr( $key, 0, 1 ) == '=' )
            {
                $lines_str .= $val;
                continue;
            }

            // Don't save if error converting to string
            if( ($line_val = self::value_to_string( $val )) === false )
                continue;

            $lines_str .= $key.'='.$line_val;
        }

        return $lines_str;
    }

    static public function parse_string_line( $line_str, $comment_no = 0 )
    {
        if( !is_string( $line_str ) )
            $line_str = '';

        // allow empty lines (keeps file 'styling' same)
        if( trim( $line_str ) == '' )
            $line_str = '';

        $return_arr = array();
        $return_arr['key'] = '';
        $return_arr['val'] = '';
        $return_arr['comment_no'] = $comment_no;

        $first_char = substr( $line_str, 0, 1 );
        if( $line_str == '' or $first_char == '#' or $first_char == ';' )
        {
            $comment_no++;

            $return_arr['key'] = '='.$comment_no.'='; // comment count added to avoid comment key overwrite
            $return_arr['val'] = $line_str;
            $return_arr['comment_no'] = $comment_no;

            return $return_arr;
        }

        $line_details = explode( '=', $line_str, 2 );
        $key = trim( $line_details[0] );

        if( $key == '' )
            return false;

        if( !isset( $line_details[1] ) )
        {
            $return_arr['key'] = $key;
            $return_arr['val'] = '';

            return $return_arr;
        }

        $return_arr['key'] = $key;
        $return_arr['val'] = self::string_to_value( $line_details[1] );

        return $return_arr;
    }

    static public function parse_string( $string )
    {
        if( empty( $string )
            or (!is_array( $string ) and !is_string( $string )) )
            return array();

        if( is_array( $string ) )
            return $string;

        $string = str_replace( "\r", "\n", str_replace( array( "\r\n", "\n\r" ), "\n", $string ) );
        $lines_arr = explode( "\n", $string );

        $return_arr = array();
        $comment_no = 1;
        foreach( $lines_arr as $line_nr => $line_str )
        {
            if( !($line_data = self::parse_string_line( $line_str, $comment_no ))
                or !is_array( $line_data ) or !isset( $line_data['key'] ) or $line_data['key'] == '' )
                continue;

            $return_arr[$line_data['key']] = $line_data['val'];
            $comment_no = $line_data['comment_no'];
        }

        return $return_arr;
    }

    static public function update_line_params( $current_data, $append_data )
    {
        if( empty( $append_data ) or (!is_array( $append_data ) and !is_string( $append_data )) )
            $append_data = array();
        if( empty( $current_data ) or (!is_array( $current_data ) and !is_string( $current_data )) )
            $current_data = array();

        if( !is_array( $append_data ) )
            $append_arr = self::parse_string( $append_data );
        else
            $append_arr = $append_data;

        if( !is_array( $current_data ) )
            $current_arr = self::parse_string( $current_data );
        else
            $current_arr = $current_data;

        if( !empty( $append_arr ) )
        {
            foreach( $append_arr as $key => $val )
                $current_arr[$key] = $val;
        }

        return $current_arr;
    }

    static function prepare_data( $data )
    {
        $data = str_replace( '\'', '\\\'', str_replace( '\\\'', '\'', $data ) );

        return $data;
    }

    public static function validate_db_date( $str )
    {
        return date( self::SQL_DATE, self::parse_db_date( $str ) );
    }

    public static function validate_db_datetime( $str )
    {
        return date( self::SQL_DATETIME, self::parse_db_date( $str ) );
    }

    static function parse_db_date( $str )
    {
        $str = trim( $str );
        if( strstr( $str, ' ' ) )
        {
            $d = explode( ' ', $str );
            $date_ = explode( '-', $d[0] );
            $time_ = explode( ':', $d[1] );
        } else
            $date_ = explode( '-', $str );

        for( $i = 0; $i < 3; $i++ )
        {
            if( !isset( $date_[$i] ) )
                $date_[$i] = 0;
            if( isset( $time_ ) and !isset( $time_[$i] ) )
                $time_[$i] = 0;
        }

        if( !empty( $date_ ) and is_array( $date_ ) )
            foreach( $date_ as $key => $val )
                $date_[$key] = intval( $val );
        if( !empty( $time_ ) and is_array( $time_ ) )
            foreach( $time_ as $key => $val )
                $time_[$key] = intval( $val );

        if( isset( $time_ ) )
            return mktime( $time_[0], $time_[1], $time_[2], $date_[1], $date_[2], $date_[0] );
        else
            return mktime( 0, 0, 0, $date_[1], $date_[2], $date_[0] );
    }

    static function seconds_passed( $str )
    {
        return time() - self::parse_db_date( $str );
    }

}


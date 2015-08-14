<?php

class Smart2Pay_Globalpay_Helper_Helper extends Mage_Core_Helper_Abstract
{
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

}


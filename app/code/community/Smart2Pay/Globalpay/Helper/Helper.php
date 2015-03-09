<?php

class Smart2Pay_Globalpay_Helper_Helper extends Mage_Core_Helper_Abstract
{
    public function isAdmin()
    {
        if( Mage::app()->getStore()->isAdmin()
         or Mage::getDesign()->getArea() == 'adminhtml' )
            return true;

        return false;
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


<?php

defined( 'ABSPATH' ) or exit;

class IE_XML_Parser {
    private function __construct() {}
    
    public static function parse( $xmlstring ) {
        $xml = simplexml_load_string( $xmlstring, "SimpleXMLElement", LIBXML_NOCDATA );
        $json = json_encode( $xml );
        $array = json_decode( $json, true );

        if( empty( $array['ENTRY'] ) )
            return [];

        $products_data = [];

        foreach( $array['ENTRY'] as $row ) {
            $product = [];
            $product['title']       = self::_get( $row, 'WS_ART_NAME' );
            $product['origin_id']   = self::_get( $row, 'WS_ART_TEHNLDITIONNUMBER' );
            $product['barcode']     = self::_get( $row, 'WS_ART_BARCODE' );
            $product['description'] = self::_get( $row, 'WS_ART_COMMENT' );
            $product['volume']      = self::_get( $row, 'WS_ART_NETTOVOLUME' );
            $product['weight']      = self::_get( $row, 'WS_ART_NETTOWEIGHT' );
            $product['height']      = self::_get( $row, 'WS_ART_NETTOHEIGHT' );
            $product['width']       = self::_get( $row, 'WS_ART_NETTOWIDTH' );
            $product['length']      = self::_get( $row, 'WS_ART_NETTOLENGTH' );
            $product['is_new']      = self::_get( $row, 'WS_ART_NEW' ) == 'Да';
            $product['in_stock']    = self::_get( $row, 'WS_ART_AVL' ) == 'Да';
            $product['price']       = self::_get( $row, 'WS_ART_PRICE' );

            $products_data[] = $product;
        }

        return $products_data;
    }

    private static function _get($row, $key, $default = '') {
        return empty( $row[$key] ) ? $default : $row[$key];
    }
}
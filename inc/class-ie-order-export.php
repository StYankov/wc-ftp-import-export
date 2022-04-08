<?php

defined( 'ABSPATH' ) or exit;

class IE_Order_Export {
    private static $_instance;

    public static function instance() {
        if(!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }

    private function __construct() {
        add_action( 'woocommerce_checkout_order_processed', [self::class, 'export_order'] );
    }

    /**
     * @return string XML String with order data
     */
    public static function export_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if( ! $order )
            return;

        $order_data = [
            'ORDER' => $order_id,
            'DATE'  => $order->get_date_created()->format('Y-m-d'),
            'HEAD'  => [
                'CUSTOMER' => self::get_customer_data( $order ),
                'SHIPPING' => self::get_shipping_data( $order ),
                'PAYMENT'  => self::get_payment_data( $order ),
                'TOTALS'   => self::get_totals_data( $order )
            ]
        ];

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ORDER/>');
        self::to_xml( $xml, $order_data );

        $products = self::get_products_data( $order );
        // We have to add products separately because we cannot have duplicate keys in PHP arrays
        foreach($products as $product) {
            $head = $xml->xpath('//HEAD')[0];
            $p = $head->addChild('PRODUCTS');

            foreach( $product as $key => $value )
                $p->addChild($key, $value);
        }

        $ftp = new IE_FTP(
            IE_Settings::get_setting('ie_ftp_host_export'),
            IE_Settings::get_setting('ie_ftp_host_user'),
            IE_Settings::get_setting('ie_ftp_host_password')
        );

        $ftp->connect();

        $ftp->upload(
            IE_Settings::get_setting('ie_ftp_path_export'),
            $xml->asXML(),
            'order-' . $order->get_id() . '.xml'
        );

        $ftp->close();

        return $xml->asXML();
    }

    private static function get_customer_data( WC_Order $order) {
        return [
            'ID'    => $order->get_customer_id(),
            'NAME'  => $order->get_formatted_billing_full_name(),
            'EMAIL' => $order->get_billing_email(),
            'PHONE' => $order->get_billing_phone() 
        ];
    }

    private static function get_shipping_data( WC_Order $order ) {
        return [
            'PRICE'    => $order->get_shipping_total(),
            'ADDRESS'  => $order->get_formatted_billing_address(),
            'PROVIDER' => $order->get_shipping_method()
        ];
    }

    private static function get_payment_data( WC_Order $order ) {
        return [
            'STATUS'   => $order->get_status(),
            'PROVIDER' => $order->get_payment_method_title() 
        ];
    }

    private static function get_products_data( WC_Order $order ) { 
        $products = [];

        $index = 1;
        foreach( $order->get_items() as $i => $item ) {
            if( ! is_a( $item, 'WC_Order_Item_Product' ) )
                continue;

            $products[] = [
                'POSITIONNUMBER'  => $index++,
                'PRODUCT'         => $item->get_id(),
                'ORDEREDQUANTITY' => $item->get_quantity(),
                'OPTIONS'         => 0, // ?
                'PRICE'           => $item->get_total()
            ];
        }

        return $products;
    }

    private static function get_totals_data( WC_Order $order ) {
        return [
            'DISCOUNT' => $order->get_discount_total(),
            'SHIPPING' => $order->get_shipping_total(),
            'SUBTOTAL' => $order->get_subtotal(),
            'ORDERTOTAL' => $order->get_total()
        ];
    }

    private static function to_xml(SimpleXMLElement $object, array $data) {   
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                $new_object = $object->addChild($key);
                self::to_xml( $new_object, $value );
            } else {
                $object->addChild( $key, $value );
            }   
        }
    }   
}

IE_Order_Export::instance();
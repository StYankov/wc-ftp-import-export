<?php

defined( 'ABSPATH' ) or exit;

class IE_Importer {
    private function __construct() {}

    public static function import( $products_array ) {
        $updated = 0;
        $new     = 0;

        foreach( $products_array as $product_item ) {
            try {
                $result = self::import_product( $product_item );

                if($result === 'new')
                    $new += 1;
                elseif($result === 'update')
                    $updated += 1;
            } catch(WC_Data_Exception $e) {
                IE_LOG::write('Product with the same SKU already exists. - SKU: ' . $product_item['barcode'], 'ERROR' );
            } catch(Exception $e) {
                IE_LOG::write("Product Could Not Be Imported. Reason: " . $e->getMessage() . ' | Product Details: ' . json_encode( $product_item ), 'ERROR' );
            }
        }

        IE_LOG::write("Import Successfull. New Products: $new; Updated Products: $updated");
    }

    private static function import_product( $product_item ) {
        $type = 'update';

        $product = self::get_product_by_origin_id( $product_item['origin_id'] );

        if( is_null($product) ) {
            $type = 'new';
            $product = new WC_Product_Simple();
            $product->set_sku( $product_item['barcode'] ); // Cannot assign SKU on update because error is thrown
        }

        $product->set_name( $product_item['title'] );
        $product->set_description( $product_item['description'] ); // ?
        $product->set_weight( $product_item['weight'] );
        $product->set_height( $product_item['height'] );
        $product->set_width( $product_item['width'] );
        $product->set_length( $product_item['length'] );
        $product->set_stock_status( $product_item['in_stock'] ? 'instock' : 'outofstock' );
        $product->set_regular_price( $product_item['price'] );

        $product->update_meta_data( '_origin_id', $product_item['origin_id'] );
        $product->update_meta_data( '_is_new', $product_item['is_new'] ? 'yes' : 'no' );

        $vol_attribute = new WC_Product_Attribute();
        
        $vol_attribute->set_name( 'Обем' );
        $vol_attribute->set_options( [$product_item['volume']] );
        $vol_attribute->set_visible( true );

        $product->set_attributes( [$vol_attribute] );
        
        $product->save();

        // Free up some memory
        $product = NULL;

        return $type;
    }

    /**
     * Search product by original id (old id)
     * 
     * @return \WC_Product|null
     */
    private static function get_product_by_origin_id( $origin_id ) {
        global $wpdb;

        $product_id = $wpdb->get_var(
            $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_origin_id' AND meta_value = %s", $origin_id )
        );

        $wpdb->flush();

        if( ! $product_id )
            return null;

        return wc_get_product( $product_id );
    }
}
<?php

defined( 'ABSPATH' ) or exit;

class IE_Importer {
    private function __construct() {}

    public static function import( $products_array, $offset = 0, $limit = -1 ) {
        $updated = 0;
        $new     = 0;

        if( $limit == -1 )
            $limit = count( $products_array );
        else 
            $limit = $offset + $limit;

        $limit = min( $limit, count( $products_array ) );

        for($i = $offset; $i < $limit; $i++) {
            $product_item = $products_array[$i];

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
        
        IE_LOG::write("Processed products from $offset to $limit. New: $new | Updated: $updated", 'success');
    }

    private static function import_product( $product_item ) {
        $type = 'update';

        $product = self::get_product_by_sku( $product_item['origin_id'] );

        if( is_null($product) ) {
            $type = 'new';
            $product = new WC_Product_Simple();
            $product->set_sku( $product_item['origin_id'] ); // Cannot assign SKU on update because error is thrown
        }

        $product->set_name( $product_item['title'] );
        $product->set_description( $product_item['description'] ); // ?
        $product->set_weight( $product_item['weight'] );
        $product->set_height( $product_item['height'] );
        $product->set_width( $product_item['width'] );
        $product->set_length( $product_item['length'] );
        $product->set_stock_status( $product_item['in_stock'] ? 'instock' : 'outofstock' );
        $product->set_regular_price( $product_item['price'] );

        $product->update_meta_data( '_barcode', $product_item['barcode'] );
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

    private static function get_product_by_sku($sku) {
        $product_id = wc_get_product_id_by_sku( $sku );
        return $product_id ? wc_get_product( $product_id ) : null;
    }
}
<?php

class IE_LOG {
    const LOG_FILE = 'imports.log';

    public static function write( $string, $type = 'info' ) {
        $contents = '';
        if(self::log_exists())
            $contents = file_get_contents(self::log_path());

        $contents .= date( '[Y.m.d H:i]: ' ) . mb_strtoupper( $type ) . ' - ' . $string . "\n";

        file_put_contents(self::log_path(), $contents);
    }

    public static function read( $limit = 100 ) {
        if(!self::log_exists())
            return [];
        
        $contents = file_get_contents( self::log_path() );

        $lines = array_reverse( explode( "\n", $contents ) );

        return $limit !== -1 ? array_slice( $lines, 0, $limit ) : $lines; 
    }

    private static function log_exists() {
        return file_exists( self::log_path() );
    }

    private static function log_path() {
        return __DIR__ . '/../' . self::LOG_FILE;
    }
}
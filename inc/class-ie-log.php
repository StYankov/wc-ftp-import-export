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

    // If Log file has more than 100 lines, trim the rest starting from the top (beginning)
    public static function trim_log_file() {
        $lines = self::read(-1);
        $count = count($lines);
        
        if($count > 100)
            $lines = array_slice($lines, $count - 100);

        file_put_contents(self::log_path(), implode( "\n", $lines ));
    }

    private static function log_exists() {
        return file_exists( self::log_path() );
    }

    private static function log_path() {
        return __DIR__ . '/../' . self::LOG_FILE;
    }
}
<?php

class IE_Cron {
    private static $_instance;
    const HOOK_NAME = 'ie_do_import';

    public static function instance() {
        if(!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }

    private function __construct() {
        add_action( self::HOOK_NAME, [self::class, 'do_import'] );
        self::schedule();
    }

    public static function schedule($interval = '') {
        if(!$interval)
            $interval = IE_Settings::get_setting('ie_cron_interval', 'daily');

        if( ! wp_next_scheduled( self::HOOK_NAME ) )
            wp_schedule_event( time(), $interval, self::HOOK_NAME );
    }

    public static function do_import() {
        try {
            $ftp_host = IE_Settings::get_setting( 'ie_ftp_host' );
            $ftp_port = IE_Settings::get_setting( 'ie_ftp_port', 21 );
            $ftp_user = IE_Settings::get_setting( 'ie_ftp_user' );
            $ftp_password = IE_Settings::get_setting( 'ie_ftp_password' );

            $ftp = new IE_FTP( $ftp_host, $ftp_user, $ftp_password, $ftp_port );

            $ftp->connect();

            if( ! $ftp->is_connected() ) {
                IE_LOG::write('Import FAILED. Could not establish connection with FTP', 'error');
                return;
            }

            $ftp->download( IE_Settings::get_setting('ie_ftp_file_path') );

            $ftp->close();

            $xml_path = path_join( wp_get_upload_dir()['basedir'], 'products.xml' );

            if( ! file_exists( $xml_path ) ) {
                IE_LOG::write('Import FAILED. Could not download XML file', 'error');
                return;
            }

            $products = IE_XML_Parser::parse( file_get_contents( $xml_path ) );

            if( count($products) === 0 )
                IE_LOG::write('XML File is empty', 'warning');

            IE_Importer::import( $products );
        } catch(Exception $e) {
            IE_LOG::write('Unexpected Error: ' . $e->getMessage() );
        }
    }
    
    public static function is_scheduled() {
        return wp_next_scheduled( self::HOOK_NAME ) !== false;
    }

    public static function unschedule() {
        if(self::is_scheduled())
            wp_unschedule_event( wp_next_scheduled( self::HOOK_NAME ), self::HOOK_NAME );
    }

    public static function get_next_scheduled( $format = 'Y.m.d H:i' ) {
        date_default_timezone_set("Europe/Sofia");
        return self::is_scheduled() ? date( $format, wp_next_scheduled( self::HOOK_NAME ) ) : '';
    }
}

IE_Cron::instance();
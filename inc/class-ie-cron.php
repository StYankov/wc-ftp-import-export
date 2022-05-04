<?php

class IE_Cron {
    private static $_instance;
    const IMPORT_HOOK = 'ie_do_import';
    const FTP_DOWNLOAD_HOOK = 'ftp_download_hook';

    public static function instance() {
        if(!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }

    private function __construct() {
        add_filter( 'cron_schedules', [$this, 'add_shorter_cron_schedules'] );

        add_action( self::FTP_DOWNLOAD_HOOK, [self::class, 'download_xml_file'] );
        add_action( self::IMPORT_HOOK, [self::class, 'do_import'] );
        self::schedule();
    }

    public static function schedule($interval = '') {
        if(!$interval)
            $interval = IE_Settings::get_setting('ie_cron_interval', 'daily');

        if( ! wp_next_scheduled( self::FTP_DOWNLOAD_HOOK ) )
            wp_schedule_event( time(), $interval, self::FTP_DOWNLOAD_HOOK );

        if( ! wp_next_scheduled( self::IMPORT_HOOK ) )
            wp_schedule_event( time(), 'two-minutes', self::IMPORT_HOOK );
    }

    public static function download_xml_file() {
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

        $downloaded = $ftp->download( IE_Settings::get_setting('ie_ftp_file_path') );

        if( $downloaded )
            IE_LOG::write('XML File Downloaded', 'success');
        else IE_LOG::write('XML File not found', 'error');

        $ftp->close();

        $import_file_version = get_option( 'ie_ftp_file_version', 0 );
        $import_file_version += 1;

        update_option( 'ie_ftp_file_version', $import_file_version );
        update_option( 'ie_import_progress', []);
        IE_LOG::trim_log_file();
    }

    public static function do_import() {
        $ftp_file_version    = get_option( 'ie_ftp_file_version', 0 );
        $import_progress     = get_option( 'ie_import_progress', []);

        $import_file_version = isset( $import_progress['file_version'] ) ? $import_progress['file_version'] : null;
        $import_status       = isset( $import_progress['status'] ) ? $import_progress['status'] : 'pending';

        if($import_file_version == $ftp_file_version && $import_status === 'success') {
            return;
        }

        try {
            $xml_path = path_join( wp_get_upload_dir()['basedir'], 'products.xml' );

            if( ! file_exists( $xml_path ) ) {
                IE_LOG::write('Import FAILED. Could not locate XML file', 'error');
                return;
            }

            $limit = 50;
            $offset = isset( $import_progress['offset'] ) ? intval($import_progress['offset']) : 0; 

            $products = IE_XML_Parser::parse( file_get_contents( $xml_path ) );

            if( count($products) === 0 )
                IE_LOG::write('XML File is empty', 'warning');

            IE_Importer::import( $products, $offset, $limit );

            $import_progress['file_version'] = $ftp_file_version;
            if( $offset + $limit > count( $products ) ) {
                $import_progress['status'] = 'success';
                $import_progress['offset'] = count( $products );
            } else {
                $import_progress['status'] = 'pending';
                $import_progress['offset'] = $offset + $limit;
            }

            update_option( 'ie_import_progress', $import_progress );
        } catch(Exception $e) {
            IE_LOG::write('Unexpected Error: ' . $e->getMessage() );
        }
    }
    
    public static function is_scheduled( $hook_name ) {
        return wp_next_scheduled( $hook_name ) !== false;
    }

    public static function unschedule( $hook_name ) {
        if(self::is_scheduled( $hook_name ))
            wp_unschedule_event( wp_next_scheduled( $hook_name ), $hook_name );
    }

    public static function get_next_scheduled( $format = 'Y.m.d H:i:s' ) {
        date_default_timezone_set("Europe/Sofia");
        return self::is_scheduled( self::FTP_DOWNLOAD_HOOK ) ? date( $format, wp_next_scheduled( self::FTP_DOWNLOAD_HOOK ) ) : '';
    }

    public function add_shorter_cron_schedules( $schedules ) {
        $schedules['two-minutes'] = [
            'interval' => MINUTE_IN_SECONDS * 2,
            'display'  => 'Every Two Minutes'
        ];

        return $schedules;
    }
}

IE_Cron::instance();

// IE_Cron::unschedule( IE_Cron::IMPORT_HOOK );
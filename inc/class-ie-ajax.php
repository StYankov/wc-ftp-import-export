<?php

class IE_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_ftp_test', [$this, 'ftp_test'] );
        add_action( 'wp_ajax_manual_import', [$this, 'manual_import'] );
    }

    public function ftp_test() {
        $ftp_host = IE_Settings::get_setting( 'ie_ftp_host' );
        $ftp_port = IE_Settings::get_setting( 'ie_ftp_port', 21 );
        $ftp_user = IE_Settings::get_setting( 'ie_ftp_user' );
        $ftp_password = IE_Settings::get_setting( 'ie_ftp_password' );

        $ftp = new IE_FTP( $ftp_host, $ftp_user, $ftp_password, $ftp_port );

        $ftp->connect();

        wp_send_json_success([ 'connected' => $ftp->is_connected() ]);
    }

    public function manual_import() {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        do_action( IE_Cron::FTP_DOWNLOAD_HOOK );
        
        wp_send_json_success();
    }
}

new IE_Ajax;
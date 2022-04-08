<?php

defined( 'ABSPATH' ) or exit;

class IE_Settings {
    private static $_instance;

    const PAGE_SLUG = 'import-export';

    public static function instance() {
        if(!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [$this, 'addPluginMenuItem'], 100 );
        add_action( 'admin_init', [$this, 'registerPluginSettings'] );

        add_action( 'update_option_ftp_settings', [$this, 'resetCronOnUpdate'] );
    }

    public function addPluginMenuItem() {
        add_submenu_page(
            'woocommerce',
            'Import / Export',
            'Import / Export',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'adminPageRender']
        );
    }

    public function adminPageRender() {
        require __DIR__ . '/../templates/admin-settings.php';
    }

    public function registerPluginSettings() {
        register_setting( self::PAGE_SLUG, 'ftp_settings' );

        add_settings_section( 'ie_ftp_section', null, null, self::PAGE_SLUG );

        $settings = [
            [
                'id'    => 'ie_ftp_host',
                'title' => 'FTP Host',
                'subtype' => 'text' 
            ],
            [
                'id'      => 'ie_ftp_port',
                'title'   => 'FTP Port',
                'subtype' => 'text',
                'default' => 21
            ],
            [
                'id'      => 'ie_ftp_user',
                'title'   => 'FTP User',
                'subtype' => 'text'
            ],
            [
                'id'      => 'ie_ftp_password',
                'title'   => 'FTP Password',
                'type'    => 'input',
                'subtype' => 'password'
            ],
            [
                'id'      => 'ie_ftp_file_path',
                'title'   => 'FTP File Path',
                'subtype' => 'text'
            ],
            [
                'id'      => 'ie_ftp_test',
                'title'   => 'FTP Access Test',
                'type'    => 'ftp-test'
            ],
            [
                'id'      => 'ie_cron_interval',
                'title'   => 'Cron Job Interval',
                'type'    => 'select',
                'options' => [
                    'hourly'     => 'Hourly',
                    'twicedaily' => 'Twice a day',
                    'daily'      => 'Daily',
                    'weekly'     => 'Weekly'
                ]
            ],
            [
                'id'      => 'ie_manual_import',
                'title'   => 'Manual Import',
                'type'    => 'manual-import'
            ],
            [
                'id'       => 'ie_ftp_host_export',
                'title'    => 'Export FTP Host',
                'subtype'  => 'text',
                'default'  => 21
            ],
            [
                'id'       => 'ie_ftp_host_port',
                'title'    => 'Export FTP Port',
                'subtype'  => 'text'
            ],
            [
                'id'       => 'ie_ftp_host_user',
                'title'    => 'Export FTP User',
                'subtype'  => 'text'
            ],
            [
                'id'       => 'ie_ftp_host_password',
                'title'    => 'Export FTP Password',
                'subtype'  => 'password'
            ],
            [
                'id'       => 'ie_ftp_path_export',
                'title'    => 'Export FTP Path',
                'subtype'  => 'text'
            ]
        ];

        foreach( $settings as $setting ) {
            add_settings_field( 
                $setting['id'],
                $setting['title'],
                [$this, 'renderInputField'],
                self::PAGE_SLUG,
                'ie_ftp_section',
                [
                    'type'    => isset( $setting['type'] ) ? $setting['type'] : 'input',
                    'subtype' => isset( $setting['subtype'] ) ? $setting['subtype'] : '',
                    'name'    => $setting['id'],
                    'options' => isset( $setting['options'] ) ? $setting['options'] : []
                ]   
            );
        }
    }

    public function renderInputField( $args ) {
        if( $args['type'] === 'input' )
            require __DIR__ . '/../templates/fields/input.php';
        elseif( $args['type'] === 'ftp-test' )
            require __DIR__ . '/../templates/ftp-test.php';
        elseif( $args['type'] === 'select' )
            require __DIR__ . '/../templates/fields/select.php';
        elseif( $args['type'] === 'manual-import' )
            require __DIR__ . '/../templates/manual-import.php';
    }

    public static function get_setting( $key, $default = '' ) {
        $options = get_option( 'ftp_settings' );

        return isset( $options[$key] ) ? $options[$key] : $default;
    }

    public function resetCronOnUpdate() {
        IE_Cron::unschedule( IE_Cron::FTP_DOWNLOAD_HOOK );
        IE_Cron::schedule();
    }
}

IE_Settings::instance();
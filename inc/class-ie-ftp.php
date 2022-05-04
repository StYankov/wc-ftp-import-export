<?php

class IE_FTP {
    private $connection;

    public $host;
    public $user;
    public $password;
    public $port;

    public function __construct( $host, $user, $password, $port = 21 ) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = intval($port);
    }

    public function connect() {
        $this->connection = ftp_connect( $this->host, $this->port );

        if( is_resource($this->connection) ) {
            $logged_in = ftp_login( $this->connection, $this->user, $this->password );

            if(!$logged_in)
                $this->connection = null;
            else 
                ftp_pasv($this->connection, true);
        }
    }

    public function is_connected() {
        return is_resource( $this->connection );
    }

    public function download( $path, $filename = 'products.xml' ) {
        if( ! $this->is_connected() )
            return false;

        $size = ftp_size( $this->connection, $path );

        if( $size === -1 ) {
            IE_LOG::write("File $path does not exist on the FTP Server", 'warning');
            return false;
        }

        $local_file = path_join( wp_get_upload_dir()['basedir'], $filename );

        return ftp_get( $this->connection, $local_file, $path, FTP_ASCII );
    }

    public function upload( $path, $contents, $filename ) {
        if( ! $this->is_connected() )
            return false;

        try {
            ftp_mkdir( $this->connection, $path );
        } catch(Exception $e) {}
 
        $stream = tmpfile();
        fwrite( $stream, $contents );
        rewind( $stream );
        $tmp = stream_get_meta_data( $stream );

        $upload_result = ftp_put( $this->connection, $path . '/' . $filename, $tmp['uri'], FTP_ASCII );

        fclose( $stream );

        return $upload_result;
    }

    public function close() {
        if($this->is_connected())
            ftp_close( $this->connection );        
    }

    public function __destruct() {
        $this->close();
    }
}
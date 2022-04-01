<?php
    $options = get_option( 'ftp_settings' );
?>
<input type="<?= $args['subtype'] ?>" name="ftp_settings[<?= $args['name'] ?>]" value="<?= isset( $options[$args['name']] ) ? $options[$args['name']] : '' ?>" />
<?php
    $options  = get_option( 'ftp_settings' );
    $value    = $options[$args['name']];
?>
<select name="ftp_settings[<?= $args['name'] ?>]">
    <?php foreach( $args['options'] as $key => $label ) : ?>
        <option value="<?= $key ?>" <?php selected( $key, $value ); ?>><?= $label ?></option>
    <?php endforeach; ?>
</select>
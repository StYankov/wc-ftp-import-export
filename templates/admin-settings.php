<h2>Import/Export Settings</h2>
<?php settings_errors(); ?>
<form method="POST" action="options.php">
    <?php settings_fields( 'import-export' ) ?>
    <?php do_settings_sections( 'import-export' ); ?>

    <?php submit_button(); ?>
</form>
<p>Next Import: <?= IE_Cron::get_next_scheduled() ?></p>
<h4>Logs</h4>
<ul style="max-height: 500px; overflow: auto">
    <?php foreach( IE_LOG::read() as $line ): ?>
        <li><?= $line ?></li>
    <?php endforeach; ?>
</ul>
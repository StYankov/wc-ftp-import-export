<button type="button" class="button manual-import">Do Manual Import</button>
<p style="font-size: 12px">This may cause timeouts!</p>
<p>Your timeout limit is: <?= ini_get('max_execution_time'); ?>s</p>
<script>
    jQuery(document).ready(function() {
        jQuery('.manual-import').click(function(e) {
            e.preventDefault();

            jQuery(this).text('Processing..');

            jQuery.ajax({
                url: '<?= admin_url( 'admin-ajax.php' ) ?>',
                method: 'POST',
                data: {
                    action: 'manual_import'
                },
                success: function(res) {
                    window.location.reload();
                },
                error: function(err) {
                    jQuery(e.target).text('Do Manual Import');
                    alert('Unexpected error. Please see log output');
                }
            })
        });
    });
</script>
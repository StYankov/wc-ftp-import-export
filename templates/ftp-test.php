<button type="button" class="button ftp-test">Test FTP Access</button>
<script>
    jQuery(document).ready(function() {
        jQuery('.ftp-test').click(function(e) {
            e.preventDefault();

            jQuery.ajax({
                url: '<?= admin_url( 'admin-ajax.php' ) ?>',
                method: 'POST',
                data: {
                    action: 'ftp_test'
                },
                success: function(res) {
                    if(res.data.connected) {
                        alert('FTP Connection Success');
                    } else {
                        alert('FTP Connection Failed');
                    }
                }
            })
        });
    });
</script>
<div class="wrap">
    <h1>Contact Form 7: Auto Responder Sparrow SMS</h1>
    <?php settings_errors(); ?>

    <form action="options.php" method="POST">
        <?php settings_fields('wpcf7_arss_settings_group') ?>

        <?php do_settings_sections('wpcf7_auto_responder_sparrow_sms') ?>

        <?php submit_button(); ?>
    </form>
</div>
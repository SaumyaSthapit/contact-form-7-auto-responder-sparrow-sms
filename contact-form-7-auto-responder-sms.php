<?php
/*
 * Plugin Name: Contact Form 7 Auto Responder Sparrow SMS
 * Plugin URI: https://github.com/saumyasthapit
 * Description: Respond emails with SMS using Sparrow SMS service.
 * Author: Saumya Sthapit
 * Author URI: https://github.com/saumyasthapit
 * Version: 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('ContactForm7AutoResponderSparrowSms')) {

    class ContactForm7AutoResponderSparrowSms
    {
        public $plugin;

        function __construct()
        {
            $this->plugin = plugin_basename(__FILE__);
        }

        function register()
        {
            add_action('admin_menu', array($this, 'register_admin_pages'));

            add_filter('plugin_action_links_' . $this->plugin, array($this, 'generate_settings_links'));

            add_action('wpcf7_mail_sent', array($this, 'action_wpcf7_mail_sent'), 10, 1);
        }

        function register_admin_pages()
        {
            add_menu_page('Auto Responder', 'Auto Responder', 'manage_options', 'wpcf7_auto_responder_sparrow_sms', array($this, 'generate_admin_page_cb'), 'dashicons-megaphone', 110);

            add_action('admin_init', array($this, 'register_plugin_settings'));
        }

        function generate_admin_page_cb()
        {
            require_once plugin_dir_path(__FILE__) . 'templates/admin.php';
        }

        function register_plugin_settings()
        {
            // registering fields needed for the plugin
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_status');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_api_url');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_token');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_identity');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_message');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_respond_to');
            register_setting('wpcf7_arss_settings_group', 'wpcf7_arss_field_name');

            add_settings_section('wpcf7_arss_settings_section', 'General Settings', array($this, 'generate_wpcf7_arss_settings_section_cb'), 'wpcf7_auto_responder_sparrow_sms');

            add_settings_field('wpcf7_arss_status', 'Enable/Disable', array($this, 'generate_checkbox_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('label_for' => 'wpcf7_arss_status', 'name' => 'wpcf7_arss_status'));
            add_settings_field('wpcf7_arss_api_url', 'API URL', array($this, 'generate_text_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('name' => 'wpcf7_arss_api_url'));
            add_settings_field('wpcf7_arss_token', 'Token', array($this, 'generate_text_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('name' => 'wpcf7_arss_token'));
            add_settings_field('wpcf7_arss_identity', 'Identity', array($this, 'generate_text_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('name' => 'wpcf7_arss_identity'));
            add_settings_field('wpcf7_arss_message', 'Message', array($this, 'generate_text_area_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section',
                array('name' => 'wpcf7_arss_message', 'description' => 'Max 160 characters')
            );
            add_settings_field('wpcf7_arss_respond_to', 'Respond To', array($this, 'generate_text_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('name' => 'wpcf7_arss_respond_to', 'description' => 'Contact Form 7 IDs (comma separated).'));
            add_settings_field('wpcf7_arss_field_name', 'Field Name', array($this, 'generate_text_field'), 'wpcf7_auto_responder_sparrow_sms', 'wpcf7_arss_settings_section', array('name' => 'wpcf7_arss_field_name', 'description' => 'Field name of phone number'));
        }

        function generate_wpcf7_arss_settings_section_cb()
        {
        }

        function generate_text_field($args)
        {
            $name = $args['name'];
            $value = esc_attr(get_option($name));
            echo '<input type="text" class="regular-text" name="' . $name . '" id="' . $name . '" value="' . $value . '" required />';
            if (array_key_exists("description", $args)) {
                echo '<p class="description">' . $args['description'] . '</p>';
            }
        }

        function generate_text_area_field($args)
        {
            $name = $args['name'];
            $value = esc_attr(get_option($name));
            echo '<textarea class="regular-text" name="' . $name . '" id="' . $name . '" rows="5" cols="30">' . $value . '</textarea>';

            if (array_key_exists("description", $args)) {
                echo '<p class="description">' . $args['description'] . '</p>';
            }
        }

        function generate_checkbox_field($args)
        {
            $name = $args['name'];
            $value = esc_attr(get_option($name));
            $checked = $value ? "checked" : "";
            echo '<input type="checkbox" value="1" name="' . $name . '" ' . $checked . '/>';
            if (array_key_exists("description", $args)) {
                echo '<p class="description">' . $args['description'] . '</p>';
            }
        }

        function generate_settings_links($links)
        {
            $settingsLink = '<a href="admin.php?page=wpcf7_auto_responder_sparrow_sms">Settings</a>';
            array_push($links, $settingsLink);
            return $links;
        }

        /**
         *  function is called when the wpcf7_mail_sent hook fires
         */
        function action_wpcf7_mail_sent($contact_form)
        {
            if (get_option('wpcf7_arss_status', false)) {
                // create array from stored comma separated value
                $idList = explode(",", get_option('wpcf7_arss_respond_to', ''));
                // check if contact form 7 id is in allowed list
                if (in_array($contact_form->id(), $idList)) {
                    $title = $contact_form->title();
                    $submission = WPCF7_Submission::get_instance();
                    if ($submission) {
                        $posted_data = $submission->get_posted_data();
                        $field_name = get_option('wpcf7_arss_field_name', '');
                        if (array_key_exists($field_name, $posted_data)) {
                            $phone = strtolower($posted_data[$field_name]);
                            if ($phone) {
                                $this->sendSMS($phone, "This is a test message");
                            }
                        }
                    }
                }
            }
        }

        public function sendSMS($to)
        {
            $url = get_option('wpcf7_arss_api_url');
            $token = get_option('wpcf7_arss_token');
            $message = get_option('wpcf7_arss_message');

            $phone = preg_replace('/^\+?977|\|1|\D/', '', ($to)); // stripping county code
            $phone = preg_replace('/[^0-9]/', '', $phone); // stripping unwanted characters

            if (strlen($phone) === 10) {
                if ($url && $token) {
                    $response = wp_remote_get($url, array(
                            'body' => array(
                                'token' => $token,
                                'from' => 'InfoSMS',
                                'to' => $phone,
                                'text' => $message)
                        )
                    );
                }
            }
        }

        function write_log($log)
        {
            if (true === WP_DEBUG) {
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            }
        }

        function activate()
        {

        }

        function deactivate()
        {

        }

        function uninstall()
        {

        }
    }

    $contactForm7AutoResponderSparrowSms = new ContactForm7AutoResponderSparrowSms();
    $contactForm7AutoResponderSparrowSms->register();
}

// activation
register_activation_hook(__FILE__, array($contactForm7AutoResponderSparrowSms, 'activate'));

// deactivation
register_deactivation_hook(__FILE__, array($contactForm7AutoResponderSparrowSms, 'deactivate'));

// uninstall
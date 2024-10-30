<?php
if ( ! class_exists( 'KvpOptions' ) ) {
    class KvpOptions {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        /**
         * Start up
         */
        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
        }

        /**
         * Add options page
         */
        public function add_plugin_page() {
            // This page will be under "Settings"
            add_options_page(
                esc_html__( 'KVP settings', 'kvp' ), 
                esc_html__( 'KVP settings', 'kvp' ),
                'manage_options', 
                'kvp-setting', 
                array( $this, 'create_admin_page' )
            );
        }

        /**
         * Options page callback
         */
        public function create_admin_page()
        {
            // Set class property
            $this->options = get_option( 'kv_plugin' );
            
            ?>
            <div class="wrap">
                <form method="post" action="options.php">
                <?php
                    // This prints out all hidden setting fields
                    settings_fields( 'kv_plugin_settings_group' );
                    do_settings_sections( 'kvp-setting-admin' );
                    submit_button();
                ?>
                </form>
            </div>
            <?php
        }

        /**
         * Register and add settings
         */
        public function page_init()
        {        
            register_setting(
                'kv_plugin_settings_group', // Option group
                'kv_plugin' // Option name
            );

            add_settings_section(
                'kv_plugin_settings', // ID
                esc_html__('Kryptovergleich plugin settings', 'kvp'), // Title
                array( $this, 'print_section_info' ), // Callback
                'kvp-setting-admin' // Page
            );

            add_settings_field(
                'currency', 
                esc_html__('Currency', 'kvp'), 
                array( $this, 'currency_callback' ), 
                'kvp-setting-admin', 
                'kv_plugin_settings'
            );

            add_settings_field(
                'fixer_key', 
                esc_html__('fixer.io API key', 'kvp'), 
                array( $this, 'fixer_callback' ), 
                'kvp-setting-admin', 
                'kv_plugin_settings'
            );

            add_settings_field(
                'button_text', 
                esc_html__('Buy Button text', 'kvp'), 
                array( $this, 'button_text_option_callback' ), 
                'kvp-setting-admin', 
                'kv_plugin_settings'
            );

            add_settings_field(
                'button_url', 
                esc_html__('Buy Button URL', 'kvp'), 
                array( $this, 'button_url_option_callback' ), 
                'kvp-setting-admin', 
                'kv_plugin_settings'
            );

            add_settings_field(
                'data_grabing', 
                esc_html__('Data grabing', 'kvp'), 
                array( $this, 'data_grabing_callback' ), 
                'kvp-setting-admin', 
                'kv_plugin_settings'
            );
        }

        /** 
         * Print the Section text
         */
        public function print_section_info()
        {
            esc_html_e('Enter your settings below:', 'kvp');
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function currency_callback()
        {
            $current = isset( $this->options['currency'] ) ? $this->options['currency'] : '';
            ?>
            <select name="kv_plugin[currency]">
                <option value="usd" <?php selected('usd', $current); ?>><?php esc_html_e('US Dollar', 'kvp'); ?></option>
                <option value="eur" <?php selected('eur', $current); ?>><?php esc_html_e('Euro', 'kvp'); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'EUR currency requires usage of fixer.io API for currency conversion.', 'kvp' ) ?></p>
            <?php
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function data_grabing_callback()
        {
            $current = isset( $this->options['data_grabing'] ) ? $this->options['data_grabing'] : '';
            ?>
            <select name="kv_plugin[data_grabing]">
                <option value="automaticaly" <?php selected('automaticaly', $current); ?>><?php esc_html_e('Automatically', 'kvp'); ?></option>
                <option value="manual" <?php selected('manual', $current); ?>><?php esc_html_e('Manually', 'kvp'); ?></option>
            </select>
            <p class="description">
                <?php printf( esc_html__( 'Manually means you want to do this using a Cron Job or a similar service or tool. Please use these links: %s - To update crypto data, %s - To update exchange rates.' ), admin_url('admin-ajax.php') . '?action=update_rates', admin_url('admin-ajax.php') . '?action=update_exchange_rates' ); ?>
            </p>
            <?php
        }


        /** 
         * Get the settings option array and print one of its values
         */
        public function fixer_callback()
        {
            $fixer_key = isset( $this->options['fixer_key'] ) ? trim($this->options['fixer_key']) : '';
            printf(
                '<input type="text" id="title" name="kv_plugin[fixer_key]" value="%s" />',
                esc_attr( $fixer_key )
            );
            printf( '<p class="description">' . esc_html__( 'You can get it at %s. It is only required if you are using EUR currency.', 'kvp' ) . '</p>', '<a href="https://fixer.io/" target="_blank">fixer.io</a>');
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function button_text_option_callback()
        {
            $button_text = isset( $this->options['button_text'] ) ? $this->options['button_text'] : '';
            printf(
                '<input type="text" id="title" name="kv_plugin[button_text]" value="%s" />',
                esc_attr( $button_text )
            );
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function button_url_option_callback()
        {
            $button_url = isset( $this->options['button_url'] ) ? $this->options['button_url'] : '';
            printf(
                '<input type="text" id="title" name="kv_plugin[button_url]" value="%s" />',
                esc_attr( $button_url )
            );
        }
    }
}

if( is_admin() ) {
    $my_settings_page = new KvpOptions();
}

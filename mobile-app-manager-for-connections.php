<?php
/*
Plugin Name: Connections Mobile App Manager
Plugin URI: https://tinyscreenlabs.com
Description: Connect your Connections Business Directory content to a mobile app using the Mobile App Manager plugin.
Version: 1.3.3
Author: Tiny Screen Labs
Author URI: https://tinyscreenlabs.com
License: GPLv2+ or later
Text Domain: mobile-app-manager-for-connections
*/

//include_once 'mobile-app-manager-for-connections-class.php';
include_once 'mobile-app-manager-for-connections-tgm-class.php';

add_action( 'plugins_loaded', array( 'mobile_app_manager_for_connections', 'init' ));

if ( ! class_exists('mobile_app_manager_for_connections') ) {

    class mobile_app_manager_for_connections{

        private $text_domain = 'mobile-app-manager-for-connections';
        private $is_connections_installed = false;
        private $is_mobile_app_manager_installed = false;
        private $plugin;
        private $js_version = '1.0';

        public static function init(){
            $class = __CLASS__;
            new $class;
        }

        function __construct(){

            if ( !function_exists('get_plugins') ){
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            if( is_plugin_active( 'connections/connections.php' ) ) {
                $this->is_connections_installed = true;
            }

            if (! function_exists('Connections_Directory')) {
                $this->is_connections_installed = false;
            }

            if( is_plugin_active( 'wp-local-app/wp-local-app.php' ) ) {
                $this->is_mobile_app_manager_installed = true;
            }

            add_action('init', array( $this, 'load_textdomain' ));
            add_action( 'admin_menu', array( $this , 'settings_page' ) );
            add_action( 'admin_enqueue_scripts', array( $this , 'admin_enqueue_scripts' ) );

            $this->plugin = plugin_basename( __FILE__ );
            add_filter("plugin_action_links_".$this->plugin, array( $this , 'settings_link' ) );

            add_filter( 'cn_register_settings_tabs', array( $this , 'registerSettingsTabs' ), 50, 1 );
            add_filter( 'cn_register_settings_sections', array( $this , 'registerSettingsSections' ), 50, 1 );

        }

        public function registerSettingsTabs( $tabs ){

            $settings = 'connections_page_connections_settings';

            $tabs[] = array(
                'id'        => 'connections_mobile_app_manager_tab_settings' ,
                'position'  => 70 ,
                'title'     => __( 'Mobile App Manager' , 'mobile-app-manager-for-connections' ) ,
                'page_hook' => $settings
            );

            return $tabs;
        }

        public static function registerSettingsSections( $sections ) {

            $settings = 'connections_page_connections_settings';

            $sections[] = array(
                'tab'       => 'connections_mobile_app_manager_tab_settings',
                'id'        => 'connections_mobile_app_manager_tab_1',
                'position'  => 40,
                'title'     => __( 'Mobile App Manager for Connections', 'mobile-app-manager-for-connections' ),
                'callback'  => create_function('', 'echo mobile_app_manager_for_connections::create_admin_page();' ),
                'page_hook' => $settings
            );


            return $sections;
        }

        function admin_enqueue_scripts(){

            if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'connections_mobile_app_manager_tab_settings' ){
                wp_register_script('mobile-app-manager-for-connections',   plugins_url( 'mobile-app-manager-for-connections-admin.js', __FILE__ ), array(), $this->js_version, true);
                wp_enqueue_script(array(  'jquery' , 'mobile-app-manager-for-connections' ));
                wp_register_style( 'mobile-app-manager-for-connections', plugins_url( "css/mam_class.css", __FILE__ ), array(), '1.0', 'screen' );
                wp_enqueue_style(array( 'mobile-app-manager-for-connections' ));
            }
        }

        function settings_link($links) {

            if($this->is_mobile_app_manager_installed){
                $mylinks = array( '<a href="' . admin_url( 'admin.php?page=local_app_setup' ) . '">'.__('Settings', 'mobile-app-manager-for-connections').'</a>', );
            }else{
                $mylinks = array( '<a href="' . admin_url( 'admin.php?page=connections_settings&tab=connections_mobile_app_manager' ) . '">'.__('Settings', 'mobile-app-manager-for-connections').'</a>', );
            }

            return array_merge( $mylinks , $links );

        }

        function load_textdomain(){

            load_plugin_textdomain( $this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/languages');

        }

        function settings_page(){

            if ( is_admin() && ! $this->is_connections_installed ) { // admin actions
                add_options_page('Settings Admin', 'Mobile App Manager', 'manage_options', $this->text_domain . '-settings', array($this, 'create_admin_page'));
            }
        }

        public static function create_admin_page(){

            if ( !function_exists('get_plugins') ){
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            $html_line = '<div class="mam_content_holder">';

            if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'connections_mobile_app_manager_tab_settings' ) {
                //do nothing
            }else{
                $html_line .= '<h2>' . __('Mobile App Manager', 'mobile-app-manager-for-connections') . '</h2>';
            }

            $html_line .= '<div class="mam_section_holder">';
            $html_line .= '<p>'.__('Mobile App Manager for Connections enables you to use your ', 'mobile-app-manager-for-connections' ).'<a href="https://wordpress.org/plugins/connections/" target="_blank">'.__('Connections Business Directory', 'mobile-app-manager-for-connections' ).'</a>'.__(' plugin content to build a mobile app using the ', 'mobile-app-manager-for-connections' ).'<a href="https://tinyscreenlabs.com/?tslref=connections" target="_blank">'.__('Tiny Screen Labs', 'mobile-app-manager-for-connections' ).'</a>'.__(' Mobile App Manager service.', 'mobile-app-manager-for-connections' ) . '</p>';


            if( is_plugin_active( 'connections/connections.php' ) ) {
                $html_line .= '<p>'.__('You currently have the Connections Business Directory plugin activated. You can select Connections Business Directory categories to use as buttons on your app using the Mobile App Manager app setup page.', 'mobile-app-manager-for-connections' ).'</p>';
            }else{
                $html_line .= '<p>'.__('You currently DO NOT have the Connections Business Directory plugin activated. If you download and activate this plugin then you can configure the Connections Business Directory content in the Mobile App Manager.', 'mobile-app-manager-for-connections' ).'</p>';
            }

            if( is_plugin_active( 'wp-local-app/wp-local-app.php' ) ) {
                $html_line .= '<p>'.__('You currently have the Mobile App Manager plugin activated. The Connections Business Directory categories will now be available as content options on the Mobile App Manager app setup page.', 'mobile-app-manager-for-connections' ).'</p>';
                $html_line .= '<p>'.__('To edit your mobile app, go to the Mobile App Manager <a href="' . admin_url( 'admin.php?page=local_app_setup' ) . '">'.__('settings', 'mobile-app-manager-for-connections').'</a> page.', 'mobile-app-manager-for-connections' ).'</p>';
            }else{
                $html_line .= '<p>'.__('You currently DO NOT have the Mobile App Manager plugin activated. In order to use Connections Business Directory content as part of a mobile app, you will need to download and install the <a href="https://tinyscreenlabs.com/wp-content/plugins/tsl-traffic-manager/updates/connections/wp-local-app.zip" target="_blank">'.__('Mobile App Manager', 'mobile-app-manager-for-connections' ).'</a> plugin.', 'mobile-app-manager-for-connections' ).'</p>';
            }
            $html_line .= '</div>';

            $html_line .= '<h2>'.__('Place buttons for Connections Business Directory content on your app using the Mobile App Manager', 'mobile-app-manager-for-connections' ).'</h2>';
            $html_line .= '<div class="mam_section_holder">';
            $html_line .= '<p>'.__('The app allows for up to 12 buttons so you can include up to 12 Connections categories or sub-categories on the mobile app.', 'mobile-app-manager-for-connections' ).'</p>';
            $html_line .= '</div>';
            $html_line .= '<p><img src="'.plugin_dir_url(__FILE__).'/assets/mobile_app_image.png"></p>';


            $html_line .= '<h2>'.__('Need help or have questions?', 'mobile-app-manager-for-connections' ).'</h2>';
            $html_line .= '<div class="mam_section_holder">';
            $html_line .= '<p>'.__('Web site support: ', 'mobile-app-manager-for-connections' ).'<a href="https://tinyscreenlabs.com/help-center/?tslref=connections" target="_blank">tinyscreenlabs.com/help-center/</a>.</p>';
            $html_line .= '<p>'.__('Phone support: +1-847-497-8469.', 'mobile-app-manager-for-connections' ).'</p>';
            $html_line .= '<p>'.__('Email support: ', 'mobile-app-manager-for-connections' ).'<a href="mailto:info@tinyscreenlabs.com">info@tinyscreenlabs.com</a>.</p>';
            $html_line .= '</div>';

            $html_line .= '</div>';

            if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'connections_mobile_app_manager_tab_settings' ) {
                return $html_line;
            }else{
                echo $html_line;
            }

        }

    }
}


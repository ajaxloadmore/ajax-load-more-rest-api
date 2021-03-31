<?php
/*
Plugin Name: Ajax Load More for REST API
Plugin URI: https://connekthq.com/plugins/ajax-load-more/extensions/rest-api/
Description: An Ajax Load More extension for infinite scrolling with the WordPress REST API
Text Domain: ajax-load-more-rest-api
Author: Darren Cooney
Twitter: @KaptonKaos
Author URI: https://connekthq.com
Version: 1.2.1
License: GPL
Copyright: Darren Cooney & Connekt Media
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


define('ALM_RESTAPI_PATH', plugin_dir_path(__FILE__));
define('ALM_RESTAPI_URL', plugins_url('', __FILE__));
define('ALM_RESTAPI_VERSION', '1.2.1');
define('ALM_RESTAPI_RELEASE', 'January 20, 2021');


/*
*  alm_rest_api_install
*
*  Activation hook
*
*  @since 1.0
*/

register_activation_hook( __FILE__, 'alm_rest_api_install' );
function alm_rest_api_install() {

   //if Ajax Load More is activated
   if(!is_plugin_active('ajax-load-more/ajax-load-more.php')){
   	die('You must install and activate <a href="https://wordpress.org/plugins/ajax-load-more/">Ajax Load More</a> before installing the ALM Previous Post Add-on.');
	}

	// If ! REST API plugin and WP Core < 4.7
	global $wp_version;
	if(!is_plugin_active('rest-api/plugin.php') && !version_compare( get_bloginfo('version'), '4.7', '>=')){
   	die('You must install and activate <a href="https://wordpress.org/plugins/rest-api/">WordPress REST API (Version 2)</a> OR be running WordPress 4.7+ before installing the Ajax Load More REST API extension.');
   }

}



if( !class_exists('ALMRESTAPI') ):

   class ALMRESTAPI{

   	function __construct(){

   		add_action( 'alm_rest_api_installed', array(&$this, 'alm_rest_api_installed') );
   		add_action( 'alm_rest_api_settings', array(&$this, 'alm_rest_api_settings') );
   		add_action( 'wp_enqueue_scripts', array(&$this, 'alm_rest_api_enqueue_scripts' ));
         add_action( 'alm_get_rest_api_template', array(&$this, 'alm_get_rest_api_template'), 10, 2);
   		add_filter( 'alm_rest_api_shortcode', array(&$this, 'alm_rest_api_shortcode'), 10, 6 );
   		load_plugin_textdomain( 'ajax-load-more-rest-api', false, dirname(plugin_basename( __FILE__ )).'/lang/'); //load text domain


   		include_once( ALM_RESTAPI_PATH . 'endpoints.php'); // Include our /posts endpoint

   	}



   	/*
   	*  alm_rest_api_enqueue_scripts
   	*  Enqueue our scripts
   	*
   	*  @since 1.0
   	*/

   	function alm_rest_api_enqueue_scripts(){
   		wp_enqueue_script( 'wp-util'); // Load WP Utils for templates
   	}



   	/*
   	*  alm_get_rest_api_template
   	*  Add underscore template to page.
   	*
   	*  @since 1.0
   	*/

   	function alm_get_rest_api_template($repeater, $type){
      	$template = alm_get_current_repeater($repeater, $type);
   		require $template;
   	}





   	/*
   	*  alm_rest_api_installed
   	*  an empty function to determine if REST API is installed.
   	*
   	*  @since 1.0
   	*/

   	function alm_rest_api_installed(){
   	   //Empty return
   	}



   	/*
   	*  alm_rest_api_shortcode
   	*  Build REST API shortcode params and send back to core ALM
   	*
   	*  @since 1.0
   	*/

   	function alm_rest_api_shortcode($restapi, $restapi_base_url, $restapi_namespace, $restapi_endpoint, $restapi_template_id, $restapi_debug){
   		$return  = ' data-restapi="'.$restapi.'"';
   		$return .= ' data-restapi-base-url="'.$restapi_base_url.'"';
   		$return .= ' data-restapi-namespace="'.$restapi_namespace.'"';
   		$return .= ' data-restapi-endpoint="'.$restapi_endpoint.'"';
   		$return .= ' data-restapi-template-id="'.$restapi_template_id.'"';
   		$return .= ' data-restapi-debug="'.$restapi_debug.'"';
		   return $return;

   	}



   	/*
   	*  alm_rest_api_settings
   	*  Create the Previous Post settings panel.
   	*
   	*  @since 1.0
   	*/

   	function alm_rest_api_settings(){

      	register_setting(
      		'alm_rest_api_license',
      		'alm_rest_api_license_key',
      		'alm_rest_api_sanitize_license'
      	);

      	add_settings_section(
	   		'alm_rest_api_settings',
	   		'REST API Settings',
	   		'alm_rest_api_callback',
	   		'ajax-load-more'
	   	);

	   	add_settings_field(
	   		'_alm_rest_api_base_url',
	   		__('Base URL', 'ajax-load-more-rest-api' ),
	   		'alm_rest_api_base_url_callback',
	   		'ajax-load-more',
	   		'alm_rest_api_settings'
	   	);

	   	add_settings_field(
	   		'_alm_rest_api_namespace',
	   		__('Namespace', 'ajax-load-more-rest-api' ),
	   		'alm_rest_api_namespace_callback',
	   		'ajax-load-more',
	   		'alm_rest_api_settings'
	   	);

	   	add_settings_field(
	   		'_alm_rest_api_endpoint',
	   		__('Endpoint', 'ajax-load-more-rest-api' ),
	   		'alm_rest_api_endpoint_callback',
	   		'ajax-load-more',
	   		'alm_rest_api_settings'
	   	);


   	}

   }


   /* REST API Settings (Displayed in ALM Core) */


	/*
	*  alm_rest_api_callback
	*  REST API Setting Heading
	*
	*  @since 1.0
	*/

	function alm_rest_api_callback() {
	   $html = '<p>' . __('Set default parameters for your installation of the <a href="http://connekthq.com/plugins/ajax-load-more/add-ons/rest-api/">REST API</a> add-on.', 'ajax-load-more-rest-api') . '</p>';

	   echo $html;
	}



	/*
	*  alm_rest_api_base_url_callback
	*
	*  @since 1.0
	*/

	function alm_rest_api_base_url_callback() {

	   $options = get_option( 'alm_settings' );

	   if(!isset($options['_alm_rest_api_base_url']))
		   $options['_alm_rest_api_base_url'] = '/wp-json';

		$html = '<label for="alm_settings[_alm_rest_api_base_url]">'.__('Set default shortcode value for [<em>restapi_base</em>].', 'ajax-load-more-rest-api').'</label><br/>';
		$html .= '<input type="text" id="alm_settings[_alm_rest_api_base_url]" name="alm_settings[_alm_rest_api_base_url]" value="'.$options['_alm_rest_api_base_url'].'" placeholder="/wp-json" /> ';
		echo $html;

	}



	/*
	*  alm_rest_api_namespace_callback
	*
	*  @since 1.0
	*/

	function alm_rest_api_namespace_callback() {

	   $options = get_option( 'alm_settings' );

	   if(!isset($options['_alm_rest_api_namespace']))
		   $options['_alm_rest_api_namespace'] = 'ajaxloadmore';

		$html = '<label for="alm_settings[_alm_rest_api_namespace]">'.__('Set default shortcode value for [<em>restapi_namespace</em>].', 'ajax-load-more-rest-api').'</label><br/>';
		$html .= '<input type="text" id="alm_settings[_alm_rest_api_namespace]" name="alm_settings[_alm_rest_api_namespace]" value="'.$options['_alm_rest_api_namespace'].'" placeholder="ajaxloadmore" /> ';
		echo $html;

	}



	/*
	*  alm_rest_api_endpoint_callback
	*
	*  @since 1.0
	*/

	function alm_rest_api_endpoint_callback() {

	   $options = get_option( 'alm_settings' );

	   if(!isset($options['_alm_rest_api_endpoint']))
		   $options['_alm_rest_api_endpoint'] = 'posts';

		$html = '<label for="alm_settings[_alm_rest_api_endpoint]">'.__('Set default shortcode value for [<em>restapi_endpoint</em>].', 'ajax-load-more-rest-api').'</label><br/>';
		$html .= '<input type="text" id="alm_settings[_alm_rest_api_endpoint]" name="alm_settings[_alm_rest_api_endpoint]" value="'.$options['_alm_rest_api_endpoint'].'" placeholder="posts" /> ';
		echo $html;

	}


   /*
   *  ALMRESTAPI
   *  The main function responsible for returning Ajax Load More REST API.
   *
   *  @since 1.0
   */

   function ALMRESTAPI(){
   	global $ALMRESTAPI;

   	if( !isset($ALMRESTAPI) )
   	{
   		$ALMRESTAPI = new ALMRESTAPI();
   	}

   	return $ALMRESTAPI;
   }


   // initialize
   ALMRESTAPI();

endif; // class_exists check

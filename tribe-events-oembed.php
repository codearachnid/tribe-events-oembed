<?php

/**
 * Plugin Name: The Events Calendar: oEmbed
 * Plugin URI:
 * Description: An addon to enable oEmbed functionality on your WordPress The Events Calendar plugin.
 * Version: 0.1
 * Author: Timothy Wood (@codearachnid)
 * Author URI: http://www.codearachnid.com
 * Author Email: tim@imaginesimplicity.com
 * Text Domain: 'tribe-events-oembed'
 * License:
 *
 *     Copyright 2013 Imagine Simplicity (tim@imaginesimplicity.com)
 *     License: GNU General Public License v3.0
 *     License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author codearachnid
 *
 */

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( !class_exists( 'tribe_events_oembed' ) ) {

	class tribe_events_oembed {

		private static $_this;
		private $oembed;
		const MIN_WP_VERSION = '3.5';

		function __construct() {

			// register lazy autoloading
			spl_autoload_register( 'self::lazy_loader' );

			// load template tags
			include 'template-tags.php';

			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'generate_rewrite_rules', array( $this, 'add_endpoint' ) );
			add_filter( 'query_vars',  array( $this, 'attach_query_endpoint' ) );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_action( 'tribe_general_settings_tab_fields', array( $this, 'settings_fields' ) );

		}

		/**
		 * plugin init methods
		 *
		 * @return void
		 */
		public function init() {
			
			$tec = TribeEvents::instance();
			$service_url = $this->rewrite_service_url();
			wp_oembed_add_provider( TribeEvents::instance()->getRewriteSlugSingular() . '/*', $service_url );

			//If the custom post type's rewrite rules have not been generated yet, flush them. (This can happen on reactivations.)
			if ( is_array( get_option( 'rewrite_rules' ) ) && ! array_key_exists( $service_url . '/?$' , get_option( 'rewrite_rules' ) ) ) {
				TribeEvents::flushRewriteRules();
			}
		}

		/**
		 * make sure we recognize the oembed endpoint as a query var
		 *
		 * @param array   $query_vars
		 * @return array
		 */
		public function attach_query_endpoint( $query_vars ) {
			$query_vars[] = 'oembed';
			$query_vars[] = 'format';
			$query_vars[] = 'url';
			return $query_vars;
		}

		/**
		 * add the rewrite pattern for oembed fancy permalinks
		 *
		 * @param obj     $wp_rewrite
		 */
		public function add_endpoint( $wp_rewrite ) {

			$tribe_rules = array();

			// service endpoint
			$tribe_rules[ $this->rewrite_service_url() . '/?$' ] = 'index.php?post_type=' . TribeEvents::POSTTYPE . '&oembed=1';

			// singluar endpoint
			$tec_single = trailingslashit( TribeEvents::instance()->getRewriteSlugSingular() );
			// $tribe_rules[ $tec_single . '([^/]+)/(\d{4}-\d{2}-\d{2})/oembed/?$' ] = 'index.php?' . TribeEvents::POSTTYPE . '=' . $wp_rewrite->preg_index(1) . "&eventDate=" . $wp_rewrite->preg_index(2) . '&oembed=1';
			$tribe_rules[ $tec_single . '([^/]+)/(\d{4}-\d{2}-\d{2})/oembed/?$' ] = 'index.php?post_type=' . TribeEvents::POSTTYPE . '&name=' . $wp_rewrite->preg_index(1) . "&eventDate=" . $wp_rewrite->preg_index(2) . '&oembed=1';
			$tribe_rules[ $tec_single . '([^/]+)/oembed/?$' ] = 'index.php?post_type=' . TribeEvents::POSTTYPE . '&name=' . $wp_rewrite->preg_index( 1 ) . '&oembed=1';

			$wp_rewrite->rules = $tribe_rules + $wp_rewrite->rules;
		}

		/**
		 * create the rewrite pattern for oembed service
		 *
		 * @return text
		 */
		public function rewrite_service_url() {
			return apply_filters( 'tribe_events_oembed/rewrite_pattern', trailingslashit( TribeEvents::instance()->getRewriteSlug() ) . 'oembed' );
		}

		/**
		 * hook WordPress template redirects to commandeer the response handler
		 *
		 * @return void
		 */
		function template_redirect() {

			if ( get_query_var( 'oembed' ) && get_query_var( 'post_type' ) == TribeEvents::POSTTYPE ) {

				// craft the proper oembed object based on current page
				$this->set_oembed_object();
				$output_format = get_query_var( 'format' );
				$output_format = empty( $output_format ) ? tribe_get_option( 'oembed-output', 'json' ) : $output_format;

				switch ( $output_format ) {
					case 'xml': 
					
						// setup oembed as xml
						header( "Content-type: text/xml; charset=utf-8" );
						$xml = Array2XML::createXML( 'oembed', $this->oembed );
						$output = $xml->saveXML();

						break;
					case 'json':
					default:

						// setup oembed as json
						header( 'Content-Type: application/json' );
						// do we need to output utf-8?
						// header("Content-Type: text/javascript; charset=utf-8");
						$output = json_encode( $this->oembed );

						break;
				}

				// output the finalized format
				echo $output;

				// prevent any further output from corrupting the response
				exit();
			}
		}

		/**
		 * build the oembed rich display
		 * @return string
		 * @uses global $post
		 */
		function get_rich_html(){
			global $post;

			// include template file
			ob_start();
			include TribeEventsTemplates::getTemplateHierarchy( 'oembed', array( 'namespace' => 'oembed', 'plugin_path' => self::get_plugin_path() ) );
			$html = ob_get_clean();

			// condense the rich output by removing unneeded whitespace
			$html = preg_replace( array( '/\s{2,}/', '/[\t\n]/' ), '', $html);

			// return the request
			return apply_filters( 'tribe_events_oembed/get_oembed_display', $html );
		}

		function get_widget_js(){
			return apply_filters( 'tribe_events_oembed/get_widget_js', preg_replace('#^https?:#', '', trailingslashit( plugins_url() . '/' . basename( dirname( __FILE__ ) ) ) ) . 'oembed.js' );
		}

		/**
		 * get the event thumbnail
		 * @param  int $post_id
		 * @return array
		 */
		function get_thumbnail( $post_id = null ){
			$post_id = TribeEvents::postIdHelper( $post_id );
			$thumbnail = array();
			$featured_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
			if( ! empty( $featured_image ) ){
				$thumbnail = array(
					'thumbnail_url' => $featured_image[0],
					'thumbnail_width' => $featured_image[1],
					'thumbnail_height' => $featured_image[2]
					);
			}
			return $thumbnail;
		}

		/**
		 * build the oembed object for request
		 * @param string $post_name
		 */
		function set_oembed_object( $post_name = null ) {
			global $post, $wp_query;

			// detected service request
			// TODO add service parsing
			if ( $id = get_query_var( 'id' ) ){

			} elseif ( $url = get_query_var( 'url' ) ){

				$pseudo_wp = new tribe_ombed_service;
				$pseudo_wp->parse_request( $url );
				$query_vars = wp_parse_args( $pseudo_wp->matched_query );
				unset( $query_vars['page'] );
				print_r($query_vars);
				if( !empty( $query_vars['tribe_events'] ) ){
					$query_vars['post_type'] = TribeEvents::POSTTYPE;
					$query_vars['name'] = $query_vars['tribe_events'];
				}
				$wp_query = new WP_Query( $query_vars );
				print_r($wp_query);
				$post = $wp_query->post;

			}

			if ( ! is_null( $post_name ) ) {
				$event_posts = get_posts( array(
						'name' => $post_name,
						'post_type' => TribeEvents,
						'posts_per_page' => 1
					) );
				if ( count( $event_posts ) > 0 ) {
					$post = $event_posts[0];
					setup_postdata( $post );
				}
			}

			// $venue_id = tribe_get_venue_id();
			// $orgnaizer_id = tribe_get_organizer_id();

			$oembed = array(
				'title' => get_the_title(),
				'html' => $this->get_rich_html(),
				'width' => 550,
				// is height really required?
				// 'height' => 250,
				'type' => 'rich',
				'version' => '1.0',
				'cache_age' => tribe_get_option( 'oembed-cache-age', '3600' ),
				'provider_name' => get_bloginfo( 'name' ),
				'provider_url' => get_bloginfo( 'url' )
			);

			$oembed = wp_parse_args( $this->get_thumbnail(), $oembed );

			$this->oembed = $oembed;
		}

		/**
		 * Inserts oEmbed settings fields on Events > Settings > General
		 *
		 * @param array $fields List of fields
		 * @return array Modified list of fields.
		 */
		public function settings_fields( $fields ) {

			// we want to inject the following license settings at the end of the licenses tab
			$fields = self::array_insert_after_key( 'defaultCurrencySymbol', $fields, array(
					'oembed-heading' => array(
						'type' => 'heading',
						'label' => __( 'oEmbed' ),
					),
					'oembed-output' => array(
						'type' => 'dropdown',
					 	'label' => __( 'Output Format' ),
						'validation_type' => 'options',
						'size' => 'small',
						'default' => 'json',
						'options' => array( 'json' => 'json', 'xml' => 'xml' ),
						'tooltip' => __( 'By default when an event is requested by the oEmbed protocal it will be served in the following format if not specified by adding <code>format={format type}</code> as a query var.' ),
					),
					'oembed-cache-age' => array(
						'type' => 'text',
						'label' => __( 'Cache Age' ),
						'tooltip' => __( 'Set the age to cache the result on the end user' ),
						'validation_type' => 'textarea',
						'size' => 'small',
						'default' => '3600',
						)
				) );

			return $fields;
		}

		public static function lazy_loader( $class_name ) {

			$file = self::get_plugin_path() . 'lib/' . $class_name . '.php';

			if ( file_exists( $file ) )
				require_once $file;

		}

		public static function get_plugin_path() {
			return trailingslashit( dirname( __FILE__ ) );
		}

		/**
		 * Insert an array after a specified key within another array.
		 *
		 * @param unknown $key
		 * @param unknown $source_array
		 * @param unknown $insert_array
		 * @return array
		 */
		public static function array_insert_after_key( $key, $source_array, $insert_array ) {
			if ( array_key_exists( $key, $source_array ) ) {
				$position = array_search( $key, array_keys( $source_array ) ) + 1;
				$source_array = array_slice( $source_array, 0, $position, true ) + $insert_array + array_slice( $source_array, $position, NULL, true );
			} else {
				// If no key is found, then add it to the end of the array.
				$source_array += $insert_array;
			}
			return $source_array;
		}

		/**
		 * Check the minimum WP version
		 *
		 * @static
		 * @return bool Whether the test passed
		 */
		public static function prerequisites() {;
			$pass = TRUE;
			$pass = $pass && version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '>=' );
			return $pass;
		}

		/**
		 * Display fail notices
		 *
		 * @static
		 * @return void
		 */
		public static function fail_notices() {
			printf( '<div class="error"><p>%s</p></div>',
				sprintf( __( 'Gravity Forms: Force SSL requires WordPress v%s or higher.', 'wp-plugin-framework' ),
					self::MIN_WP_VERSION
				) );
		}

		/**
		 * Static Singleton Factory Method
		 *
		 * @return static $_this instance
		 * @readlink http://eamann.com/tech/the-case-for-singletons/
		 */
		public static function instance() {
			if ( !isset( self::$_this ) ) {
				$className = __CLASS__;
				self::$_this = new $className;
			}
			return self::$_this;
		}
	}

	/**
	 * Instantiate class and set up WordPress actions.
	 *
	 * @return void
	 */
	function load_tribe_events_oembed() {

		// we assume class_exists( 'WPPluginFramework' ) is true
		if ( apply_filters( 'load_tribe_events_oembed/pre_check', tribe_events_oembed::prerequisites() ) ) {

			// when plugin is activated let's load the instance to get the ball rolling
			add_action( 'init', array( 'tribe_events_oembed', 'instance' ), -100, 0 );

		} else {

			// let the user know prerequisites weren't met
			add_action( 'admin_head', array( 'tribe_events_oembed', 'fail_notices' ), 0, 0 );

		}
	}

	// high priority so that it's not too late for addon overrides
	add_action( 'plugins_loaded', 'load_tribe_events_oembed' );


	/**
	 * when the plugin is actiated/deactivated let's flush the rewrites
	 *
	 * @return void
	 */
	function tribe_events_oembed_flush_rewrites() {

		TribeEvents::flushRewriteRules();

	}

	// attach plugin activation|deactivation hooks
	register_activation_hook( __FILE__, 'tribe_events_oembed_flush_rewrites' );
	register_deactivation_hook( __FILE__, 'tribe_events_oembed_flush_rewrites' );

}

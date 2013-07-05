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
		public $path;
		const MIN_WP_VERSION = '3.5';

		function __construct() {

			$this->path = trailingslashit( dirname( __FILE__ ) );

			add_filter( 'generate_rewrite_rules', array( $this, 'add_endpoint' ) );
			add_filter( 'query_vars',  array( $this, 'attach_query_endpoint' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );



		}

		/**
		 * plugin init methods
		 * @return void
		 */
		public function init() {
			//If the custom post type's rewrite rules have not been generated yet, flush them. (This can happen on reactivations.)
			if ( is_array( get_option( 'rewrite_rules' ) ) && ! array_key_exists( $this->rewrite_pattern(), get_option( 'rewrite_rules' ) ) ) {
				TribeEvents::flushRewriteRules();
			}
		}

		/**
		 * make sure we recognize the oembed endpoint as a query var
		 * @param  array $query_vars
		 * @return array
		 */
		public function attach_query_endpoint( $query_vars ) {
			$query_vars[] = 'oembed';
			return $query_vars;
		}

		/**
		 * add the rewrite pattern for oembed endpoint for fancy permalinks
		 * @param obj $wp_rewrite
		 */
		public function add_endpoint( $wp_rewrite ) {

			$tribe_rules = array();
			$tribe_rules[ $this->rewrite_pattern() ] = 'index.php?post_type=' . TribeEvents::POSTTYPE . '&name=' . $wp_rewrite->preg_index( 1 ) . '&oembed=1';

			$wp_rewrite->rules = $tribe_rules + $wp_rewrite->rules;
		}

		/**
		 * create the rewrite pattern for oembed endpoints
		 * @return [type] [description]
		 */
		public function rewrite_pattern(){
			return apply_filters( 'tribe_events_oembed/rewrite_pattern', trailingslashit( TribeEvents::instance()->getRewriteSlugSingular() ) . '([^/]+)/oembed/?$' );
		}

		/**
		 * hook WordPress template redirects to commandeer the response handler
		 * @return void
		 */
		function template_redirect() {
			if ( get_query_var( 'oembed' ) && get_query_var( 'post_type' ) == TribeEvents::POSTTYPE ) {
				// event slug
				echo get_query_var( 'name' );
				exit();
			}
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
	 * @return void
	 */
	function tribe_events_oembed_flush_rewrites() {

		TribeEvents::flushRewriteRules();

	}
	
	// attach plugin activation|deactivation hooks
	register_activation_hook( __FILE__, 'tribe_events_oembed_flush_rewrites' );
	register_deactivation_hook( __FILE__, 'tribe_events_oembed_flush_rewrites' );

}

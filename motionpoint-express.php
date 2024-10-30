<?php
/*
Plugin Name: MotionPoint Express
Plugin URI: https://www.motionpointexpress.com/
Description: MotionPoint Express website translation solution.
Version: 1.5
Author: MotionPoint Express
Copyright: MotionPoint Express
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: motionpoint-express
Domain Path: /lang
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if ( ! class_exists( 'motionpointexpress' ) ) :

final class motionpointexpress {


  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;
  

  /**
   * Plugin settings
   *
   * @var array $settings
   */
  private $settings;


  /**
   * Default user config
   *
   * @var array $default_user_config
   */
  private $default_user_config = array(
    'status'                => 'enabled',
    'project_code'          => '',
    'location_host'         => 'app.motionpointexpress.com',
    'publishing_mode'       => 'js',
    'prerender_key'         => '',
    'redirect_system_pages' => 'on',
    'deployed'              => 'on',
    'translate_login_page'  => 'off',
  );


  /**
   * Available user config options
   *
   * @var array $default_user_config
   */
  private $user_config_options = array(
    'status'          => array( 'enabled', 'disabled' ),
    'location_host'   => array( 'app.motionpointexpress.com', 'custom' ),
    'publishing_mode' => array( 'js', 'proxy' ),
  );


  /**
   * User config
   *
   * @var array $user_config
   */
  private $user_config;

  
  /**
   *  Initialize motionpointexpress
   */
  private function __construct() {
    $this->settings = array(
      'version'  => '1.5',
      'path'     => plugin_dir_path( __FILE__ ),
      'url'      => plugin_dir_url( __FILE__ ),
      'basename' => plugin_basename( __FILE__ ),
    );

    // Hook after theme setup to allow penetrating the process from another plugin or theme
    add_action( 'after_setup_theme', array( $this, 'init' ), 0 );
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


  /**
   *  Retrieve project settings from the app
   *
   * @var string $project_code
   */
  public function get_project_settings( $ignore_cache = false ) {
    // Get from cache if not expired
    $project_settings = defined('ENABLE_CACHE') ? wp_cache_get( 'project_settings', 'motionpointexpress' ) : get_transient( 'motionpointexpress_project_settings' );
    if ( ! empty( $project_settings ) && ! $ignore_cache ) {
      // Settings are saved as encoded JSON string
      $project_settings = json_decode( $project_settings, true );
      return $project_settings;
    }

    try {
      $user_config = motionpointexpress()->get_user_config();
      $location_host = $user_config['location_host'];
      $project_code = $user_config['project_code'];
      $deployed = $user_config['deployed'] === 'on' ? 'true' : 'false';

      $project_settings = null;

      // No reason initiating remote request if there is no project code set
      if ( empty( $project_code ) ) throw new Exception("Missing project code", 1);

      // Load from server
      $url = "https://{$location_host}/client/{$project_code}/0/stub.json?deployed={$deployed}";
      $response = wp_remote_get( $url, array( 'timeout' => 180 ) );
      $response_body = wp_remote_retrieve_body( $response );

      $project_settings = json_decode( $response_body, true );
      if ( empty( $project_settings['languages'] ) ) {
        throw new Exception("Project settings cannot be loaded", 1);
      }
      
      $project_settings['subdir_locale_map'] = array_reduce( $project_settings['languages'], function( $acc, $item ) {
        $subdirectory = ! empty( $item['deployPath'] ) ? wp_parse_url( $item['deployPath'], PHP_URL_PATH ) : '';
        $subdirectory = $subdirectory ? $subdirectory : '';
        $subdirectory = trim( $subdirectory );
        $subdirectory = trim( $subdirectory, "/" );
        if ( ! empty( $subdirectory ) ) {
          $acc[ $subdirectory ] = $item['targetLanguage'];
        }
        return $acc;
      }, array() );
    } catch (Exception $e) {
      // Mute error intentionally
      $project_settings = null;
    }

    // Fallback on error or missing response, malformed JSON, missing data
    // Settings retrieved using fallback mechanism won't be saved
    $should_save = true;
    if ( is_wp_error( $response_body ) || empty( $response_body ) || empty( $project_settings ) ) {
      $project_settings = defined('ENABLE_CACHE') ? wp_cache_get( 'project_settings_fallback', 'motionpointexpress' ) : get_transient( 'motionpointexpress_project_settings_fallback' );
      $project_settings = json_decode( $project_settings, true );
      $should_save = false;
    }

    if ( $should_save && ! empty( $project_settings ) ) {
      $encoded_settings = wp_json_encode( $project_settings );

      // Fallback never expires, used to get the last available settings
      if ( defined('ENABLE_CACHE') ) {
        wp_cache_set( 'project_settings', $encoded_settings, 'motionpointexpress', 600 );
        wp_cache_set( 'project_settings_fallback', $encoded_settings, 'motionpointexpress' );
        wp_cache_set( 'raw_stub_json', $response_body, 'motionpointexpress', 600 );
      } else {
        set_transient( 'motionpointexpress_project_settings', $encoded_settings, 600 );
        set_transient( 'motionpointexpress_project_settings_fallback', $encoded_settings );
        set_transient( 'motionpointexpress_raw_stub_json', $response_body );
      }
    }

    return $project_settings;
  }


  /**
   *  Initialize plugin
   */
  public function init() {
    require_once( $this->settings[ 'path' ] . 'inc/admin.php' );
    require_once( $this->settings[ 'path' ] . 'inc/frontend.php' );

    require_once( $this->settings[ 'path' ] . 'inc/integration/shortcodes.php' );
    require_once( $this->settings[ 'path' ] . 'inc/integration/widgets.php' );
    require_once( $this->settings[ 'path' ] . 'inc/integration/blocks.php' );
    require_once( $this->settings[ 'path' ] . 'inc/integration/menus.php' );
  }

  
  /**
   *  Retrieve plugin's setting. Additionally allows 3rd party customization
   *
   *  @param string $name Setting name
   *  @param mixed $value Default value
   *  @return mixed
   */
  function get_setting( $name, $value = null ) {
    $value = isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : $value;
    return apply_filters( "motionpointexpress/get_setting/{$name}", $value );
  }


  /**
   *  Updates plugin's setting
   *
   *  @param string $name Setting name
   *  @param mixed $value Default value
   */
  function update_setting( $name, $value ) {
    $this->settings[ $name ] = apply_filters( "motionpointexpress/update_setting/{$name}", $value, $this->settings[ $name ] );
  }

  
  /**
   *  Retrieve user saved config
   *
   *  @param string $name Config name
   *  @return mixed
   */
  function get_user_config( $name = null ) {
    $config = $this->user_config;
    $config = ! empty( $config ) ? $config : get_option( 'motionpointexpress' );
    $config = is_array( $config ) ? $config : array();
    $config = array_merge( $this->default_user_config, $config );
    $config = apply_filters( "motionpointexpress/get_user_config", $config );

    // Ensure that config contains only available options or fallback to default
    foreach ( $this->user_config_options as $key => $values ) {
      $config[ $key ] = in_array( $config[ $key ], $values ) ? $config[ $key ] : $this->default_user_config[ $key ];
    }

    if ( ! empty( $name ) ) return isset( $config[ $name ] ) ? $config[ $name ] : null;
    else return $config;
  }


  /**
   *  Updates user config
   *
   *  @param string $name Config name
   *  @param mixed $value Default value
   *  @return (array)
   */
  function update_user_config( $name, $value ) {
    $config = $this->get_user_config();
    $this->user_config[ $name ] = apply_filters( "motionpointexpress/update_user_config/{$name}", $value, $config );
    return $this->user_config;
  }


  /**
   *  Save user config in DB
   *
   *  @return array
   */
  function save_user_config() {
    $config = apply_filters( "motionpointexpress/save_user_config", $this->get_user_config() );
    update_option( 'motionpointexpress', $config );
    return $config;
  }


}


/**
 *  The main function responsible for returning motionpointexpress plugin
 *
 *  @return object motionpointexpress instance
 */
function motionpointexpress() {
  return motionpointexpress::instance();
}


// initialize
motionpointexpress();

endif; // class_exists check

?>
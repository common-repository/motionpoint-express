<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'motionpointexpress_frontend' ) ) :

final class motionpointexpress_frontend {


  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;
  
  
  /**
   *  Initialize motionpointexpress_frontend
   */
  private function __construct() {
    $this->handle_request();
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_frontend instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


  /**
   *  Displays admin notices if any
   */
  public function handle_request() {
    $headers = $this->get_request_headers();

    $user_config = motionpointexpress()->get_user_config();
    if ( empty( $user_config['project_code'] ) ) {
      // Don't process if plugin is not configured yet
      return;
    }
    // Extracts: $status, $project_code, $location_host, $custom_location_host, $publishing_mode, $prerender_key, $redirect_system_pages, $translate_login_page, $deployed
    extract( $user_config );

    if ($status !== 'enabled') {
      // Don't process if user set to disabled status
      return;
    }

    $location_host = $location_host === 'custom' ? $custom_location_host : $location_host;

    $project_settings = motionpointexpress()->get_project_settings();
    if ( empty( $project_settings ) ) {
      // Don't process if project settings can't be retrieved
      return;
    }

    // Prerender signature in user agent header: +https://github.com/prerender/prerender
    // See details: https://docs.prerender.io/docs/33-overview-of-prerender-crawlers
    // Prerender User Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/89.0.4389.82 Safari/537.36 Prerender (+https://github.com/prerender/prerender)
    $is_prerender_app_request = FALSE !== strpos( $headers['User-Agent'], '+https://github.com/prerender/prerender' );

    // MotionPoint Express server passes just a header
    $is_motionpointexpress_app_request = ! empty( $headers['X-Translationproxy-Translating-To'] );

    $server_request_uri = sanitize_url( $_SERVER['REQUEST_URI'] );

    // Determine currently requested language by subdirectory prefix
    $subdir_locale_map = ! empty( $project_settings['subdir_locale_map'] ) ? $project_settings['subdir_locale_map'] : array();
    $language_subdirectories = implode( '|', array_keys( $subdir_locale_map ) );
    $has_language_prefix = ! empty( $language_subdirectories ) && preg_match( "#^/($language_subdirectories)(/|\?|$)#i", $server_request_uri, $language_subdirectory_matches );
    $language_subdirectory = $has_language_prefix && ! empty( $language_subdirectory_matches[1] ) ? $language_subdirectory_matches[1] : null;
    $locale = ! empty( $project_settings['subdir_locale_map'][ $language_subdirectory ] ) ? strtolower( $project_settings['subdir_locale_map'][ $language_subdirectory ] ) : '';

    if ( ! empty( $has_language_prefix ) && preg_match( "#/{$language_subdirectory}/wp-admin#i", $server_request_uri ) ) {
      // Always redirect WP Admin to original language
      $_SERVER['REQUEST_URI'] = preg_replace( "#^/($language_subdirectory)#i", '', $server_request_uri );
      $request_url = $this->get_request_url();
      wp_redirect(
        $request_url['raw'],
        302,
        'MotionPoint Express'
      );
      exit;
    } elseif ( is_admin() || wp_doing_cron() && defined('REST_REQUEST') ) {
      // Do not handle WP cron or wp admin or rest requests
      return;
    }

    // Redirect system pages
    if ( $has_language_prefix && $this->is_wp_login_page() && ( 'on' === $redirect_system_pages ) ) {
      $_SERVER['REQUEST_URI'] = preg_replace( "#^/($language_subdirectory)#i", '', $server_request_uri );
      $request_url = $this->get_request_url();
      wp_redirect(
        $request_url['raw'],
        302,
        'MotionPoint Express'
      );
      exit;
    }

    // Include frontend assets
    // Don't translate WP system pages unless user enabled
    if ( ! $this->is_wp_login_page() || ( 'on' === $translate_login_page ) ) {
      
      add_action( 'init', function() use ( $is_prerender_app_request, $publishing_mode, $project_code, $location_host, $deployed ) {
        if ( $is_prerender_app_request || ( 'js' === $publishing_mode ) ) {
          $deployed_value = $deployed === 'on' ? 'true' : 'false';
          wp_enqueue_script( 'motionpointexpress-stub', "https://{$location_host}/client/{$project_code}/0/stub.js?deployed={$deployed_value}", array(), null, true );
        } if ( 'proxy' === $publishing_mode ) {
          wp_enqueue_script( 'motionpointexpress-language-selector', "https://{$location_host}/_el/ext/js/languageSelector.js?code={$project_code}", array(), null, true );
        }
      } );

      // Defer enqueued frontend scripts
      $defer_script_handles = array( 'motionpointexpress-stub', 'motionpointexpress-language-selector' );
      add_filter( 'script_loader_tag', function( $tag, $handle, $src ) use ( $publishing_mode, $defer_script_handles ) {
        if ( in_array( $handle, $defer_script_handles ) ) {
          $tag = '<script id="'. $handle .'" defer="defer" data-cfasync="false" type="text/javascript" src="'. $src .'"></script>' . "\n";
        }
        return $tag;
      }, 10, 3 );

    }

    // Disable translations for particular page parts
    if ( $is_prerender_app_request || $is_motionpointexpress_app_request ) {

      // Admin bar should not be translated
      add_action( 'wp_before_admin_bar_render', array( $this, 'disable_wp_admin_bar_translation' ) );

      // WP login/register page shouldn't be translated unless translation explicitly enabled
      if ( $this->is_wp_login_page() && ( 'on' !== $translate_login_page ) ) {
        add_action( 'login_header', array( $this, 'disable_wp_login_page_translation' ) );
      }

    }


    if ( $is_motionpointexpress_app_request ) {
      // No more action needed if this request is made by MotionPoint Express server
      return;
    }


    if ( ! $has_language_prefix ) {
      // No more action needed if this request was not translatable
      return;
    }


    if ( ! $locale ) {
      // No more action needed if locale was not configured
      return;
    }


    if ( $is_prerender_app_request ) {
      // Implicitly route request to original language when request is made by prerender server
      // so it can retrieve result that will be translated on a later stage
      $_SERVER['REQUEST_URI'] = preg_replace( "#^/($language_subdirectory)#i", '', $server_request_uri );
      
      // No more action needed if request is made by prerender server
      return;
    }


    // From this point, request will be proxied

    $request_method = strtoupper( sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) );
    $request_method = in_array( $request_method, array( 'GET', 'POST', 'PUT', 'DELETE' ) ) ? $request_method : 'GET';

    // Prepare request data
    $request_params = null;
    if ( 'GET' == $request_method ) {
      $request_params = $_GET;
    } elseif ( 'POST' == $request_method ) {
      $request_params = $_POST;
      if ( empty( $request_params ) ) {
        $data = file_get_contents( 'php://input' );
        if ( ! empty( $data ) ) {
          $request_params = $data;
        }
      }
    } elseif ( ( 'PUT' === $request_method ) || ( 'DELETE' === $request_method ) ) {
      $request_params = file_get_contents( 'php://input' );
    }

    if ('POST' === $request_method) {
      $post_data = is_array( $request_params ) ? http_build_query( $request_params ) : $request_params;
      $request_body = $post_data;
    } elseif ( ( 'PUT' === $request_method ) || ( 'DELETE' === $request_method ) ) {
      $request_body = $request_params;
    } else {
      $request_body = null;
    }


    // Requests made by bots should be processed by prerender service if enabled
    // Page translation will be made on the fly by included frontend script
    $should_prerender = $this->should_prerender() && ! empty( $prerender_key ) && ( 'proxy' !== $publishing_mode );
    if ( $should_prerender ) {
      $request_url = $this->get_request_url();
      $request_url = $request_url['parsed'];
  
      $headers['x-prerender-token'] = $prerender_key;

      $request_args = array(
        'method'  => $request_method,
        'headers' => $headers,
        'timeout' => 180,
      );
      
      $path = "{$request_url['scheme']}://{$request_url['host']}";
      $path .= empty( $request_url['port'] ) || in_array( intval( $request_url['port'] ), array( 80, 443 ) ) ? '' : ":{$request_url['port']}";
      $path .= $request_url['path'];
      $path .= ! empty( $request_url['query'] ) ? "?{$request_url['query']}" : '';
      $path = rawurlencode( $path );
      $prerender_url = "https://service.prerender.io/{$path}";

      $response = wp_remote_request( $prerender_url, $request_args );
      $response_body = wp_remote_retrieve_body( $response );
      $response_body = is_wp_error( $response_body ) ? '' : $response_body;

      print( $response_body );
      exit;
    }


    // Non-bot request processing

    if ( 'js' === $publishing_mode ) {
      $proxy_host = "{$locale}-{$project_code}-j.app.motionpointexpress.com";
    } else {
      // Proxy mode
      $proxy_host = "{$locale}-{$project_code}.{$location_host}";
    }


    // Prepare headers
    $http_host = sanitize_text_field( $_SERVER['HTTP_HOST'] );
    $headers['Origin'] = $http_host;
    $headers['Host'] = $proxy_host;
    $headers['X-TranslationProxy-Cache-Info'] = 'disable';
    $headers['X-TranslationProxy-EnableDeepRoot'] = 'true';
    $headers['X-TranslationProxy-AllowRobots'] = 'true';
    $headers['X-TranslationProxy-ServingDomain'] = $http_host;

    // Prepare request args
    $proxy_request_args = array(
      'method'      => $request_method,
      'headers'     => $headers,
      'timeout'     => 180,
      'redirection' => 0,
    );

    if ( ! empty( $request_body ) ) {
      $proxy_request_args['body'] = $request_body;
    }

    $request_scheme = ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? strtolower( sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) : strtolower( sanitize_text_field( $_SERVER['REQUEST_SCHEME'] ) );
    $request_scheme = in_array( $request_scheme, array( 'http', 'https' ) ) ? $request_scheme : 'https';
    $proxy_url = "{$request_scheme}://{$proxy_host}{$server_request_uri}";

    // Proxy request
    $response = wp_remote_request( $proxy_url, $proxy_request_args );
    $response_body = ! is_wp_error( $response ) ? wp_remote_retrieve_body( $response ) : '';
    $response_body = is_wp_error( $response_body ) ? '' : $response_body;
    $proxy_response_headers = ! is_wp_error( $response ) && ! empty( $response['headers'] ) ? $response['headers']->getAll() : array();

    // Set response headers received from MotionPoint Express
    foreach ( $proxy_response_headers as $name => $raw_value ) {
      $values = is_array( $raw_value ) ? $raw_value : array( $raw_value );
  
      foreach ( $values as $value ) {
        // We should let the web server to decide how to encode the content
        if ( FALSE !== strpos( strtolower( $name ), 'content-encoding' ) ) continue;
        if ( FALSE !== strpos( strtolower( $name ), 'server' ) ) continue;

        header( "{$name}: {$value}", $replace_header = false );
      }
    }

    // Render response content
    print( $response_body );

    // Stop execution as we deliver translated content
    exit;
  }


  /**
   *  Wraps admin bar with no translate instruction
   */
  public function disable_wp_admin_bar_translation() {
    echo '<div translate="no">';

    add_action( 'wp_after_admin_bar_render', function() {
      echo '</div>';
    } );
  }


  /**
   *  Wraps login page with no translate instruction
   */
  public function disable_wp_login_page_translation() {
    echo '<div translate="no">';

    add_action( 'login_footer', function() {
      echo '</div>';
    } );
  }


  /**
   *  Checks if should prerender current request
   */
  public function should_prerender() {
    $request_url = $this->get_request_url();
    $request_url = $request_url['parsed'];

    $user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
    
    $bot_re = '/googlebot|adsbot\-google|Feedfetcher\-Google|bingbot|yandex|baiduspider|Facebot|facebookexternalhit|twitterbot|rogerbot|linkedinbot|embedly|quora link preview|showyoubot|outbrain|pinterest|slackbot|vkShare|W3C_Validator|redditbot|applebot|whatsapp|flipboard|tumblr|bitlybot|skypeuripreview|nuzzel|discordbot|google page speed|qwantify|pinterestbot|SemrushBot|SiteAuditBot|bitrix link preview|xing\-contenttabreceiver|chrome\-lighthouse|telegrambot/i';
    $is_bot = preg_match( $bot_re, $user_agent );

    $request_query = ! empty( $request_url['query'] ) ? $request_url['query'] : '';
    $is_escaped_fragment = preg_match( '/_escaped_fragment_/', $request_query );
    
    $static_asset_re = '/\.(js|css|xml|less|png|jpg|jpeg|gif|pdf|doc|txt|ico|rss|zip|mp3|rar|exe|wmv|doc|avi|ppt|mpg|mpeg|tif|wav|mov|psd|ai|xls|mp4|m4a|swf|dat|dmg|iso|flv|m4v|torrent|ttf|woff|svg|eot)$/i';
    $is_static_asset = preg_match( $static_asset_re, $request_url['path'] );

    $should_prerender = ! $is_static_asset && ( $is_bot || $is_escaped_fragment );

    return $should_prerender;
  }


  /**
   *  Parse all request headers
   *  Note: getallheaders works for apache only, but not nginx
   */
  public function get_request_headers() {
    $request_headers = array();
    foreach ( $_SERVER as $key => $value ) {
      if ( empty( $value ) ) continue;

      $is_http_key = strpos( $key, 'HTTP_' ) === 0;
      $is_content_key = strpos( $key, 'CONTENT_' ) === 0;
      if ( $is_http_key || $is_content_key ) {
        $header_name = strtolower( $key );
        $header_name = preg_replace( '#^(HTTP_|CONTENT_)#i', '', $header_name );
        $header_name = str_replace( '_', ' ', $header_name );
        $header_name = ucwords( $header_name );
        $header_name = str_replace( ' ', '-', $header_name );
        $request_headers[ $header_name ] = $value;
      }
    }

    return $request_headers;
  }


  /**
   *  Retrieve URL of the current request
   */
  public function get_request_url() {
    if ( empty( $this->request_url ) ) {

      if ( ( ! empty( $_SERVER['REQUEST_SCHEME'] ) && ( strtolower( $_SERVER['REQUEST_SCHEME'] ) === 'https' ) ) ||
           ( ! empty( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) === 'on') ) ||
           ( ! empty( $_SERVER['SERVER_PORT'] ) && ( intval( $_SERVER['SERVER_PORT'] ) === 443 ) ) ) {
          $request_scheme = 'https';
          $default_port = 443;
      } else {
        $request_scheme = 'http';
        $default_port = 80;
      }

      $request_host = ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : '';
      $request_host = empty( $request_host ) && ! empty( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( $_SERVER['SERVER_NAME'] ) : $request_host;

      $request_post = ! empty( $_SERVER['SERVER_PORT'] ) ? intval( $_SERVER['SERVER_PORT'] ) : $default_port;
      $default_ports = array( 80, 443 );
      $request_post = ! in_array( $request_post, $default_ports ) ? ":{$request_post}" : '';

      $request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_url( $_SERVER['REQUEST_URI'] ) : '/';

      $request_url = "{$request_scheme}://{$request_host}{$request_post}{$request_uri}";

      $this->request_url = array(
        'raw'    => $request_url,
        'parsed' => wp_parse_url( $request_url ),
      );
    }

    return $this->request_url;
  }


  /**
   *  Check if current page is login page
   * 
   *  @return boolean
   */
  public function is_wp_login_page() {
    return preg_match( '#^/([^/]+/)?wp\-login\.php#i', sanitize_url( $_SERVER['REQUEST_URI'] ) );
  }


  /**
   *  Check if current page is registration page
   * 
   *  @return boolean
   */
  public function is_wp_registration_page() {
    global $pagenow;

    $is_login_page = $this->is_wp_login_page();
    $is_register_action = ! empty( $_REQUEST['action'] ) && ( sanitize_text_field( $_REQUEST['action'] ) === 'register' );

    return $is_login_page && $is_register_action;
  }


}


/**
 *  The main function responsible for returning motionpointexpress_frontend plugin
 *
 *  @return (object) motionpointexpress_frontend instance
 */
function motionpointexpress_frontend() {
  return motionpointexpress_frontend::instance();
}


// initialize
motionpointexpress_frontend();

endif; // class_exists check

?>
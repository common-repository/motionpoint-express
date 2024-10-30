<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'motionpointexpress_admin' ) ) :

final class motionpointexpress_admin {


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
   *  Initialize motionpointexpress_admin
   */
  private function __construct() {
    if ( is_admin() && ! wp_doing_cron() && ! wp_doing_ajax() && ! defined('REST_REQUEST') ) {
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
      add_action( 'admin_menu', array( $this, 'setup_menu' ) );
      add_filter( 'plugin_action_links_' . motionpointexpress()->get_setting('basename'), array( $this, 'plugin_actions' ) );
      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_admin instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


  /**
   *  Display actions on plugins page
   * 
   *  @param array $actions An array of plugin action links.
   *  @return array
   */
  public function plugin_actions( $actions ) {
    $settings_action = [
      'settings' => sprintf(
        '<a href="%1$s" %2$s>%3$s</a>',
        menu_page_url( 'motionpoint-express', false ),
        'aria-label="' . __( 'Settings for MotionPoint Express', 'motionpoint-express' ) . '"',
        esc_html__( 'Settings', 'motionpoint-express' )
      ),
    ];

    $actions = ( $settings_action + $actions );
    return $actions;
  }


  /**
   *  Create admin page(s)
   */
  public function setup_menu() {
    add_menu_page(
      esc_html__( 'MotionPoint Express', 'motionpoint-express' ),
      esc_html__( 'MotionPoint Express', 'motionpoint-express' ),
      'manage_options',
      'motionpoint-express',
      array( $this, 'page' ),
      'dashicons-translation',
      76
    );
  }


  /**
   *  Render admin page
   */
  public function page() {
    $status_options = array(
      'enabled'  => __( 'Enabled', 'motionpoint-express' ),
      'disabled' => __( 'Disabled', 'motionpoint-express' ),
    );
    $publishing_mode_options = array(
      'js'    => __( 'JavaScript', 'motionpoint-express' ),
      'proxy' => __( 'Translation Proxy', 'motionpoint-express' ),
    );
    $location_host_options = array(
      'app.motionpointexpress.com' => __( 'app.motionpointexpress.com', 'motionpoint-express' ),
      'custom'                     => __( 'Other', 'motionpoint-express' ),
    );

    $should_save_user_config = ! empty( $_POST['motionpointexpress_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['motionpointexpress_nonce'] ) ), 'motionpointexpress_save_settings' );
    if ( $should_save_user_config ) {
      motionpointexpress()->update_user_config( 'status', sanitize_text_field( $_POST['status'] ) );
      motionpointexpress()->update_user_config( 'custom_location_host', sanitize_text_field( $_POST['custom_location_host'] ) );
      motionpointexpress()->update_user_config( 'project_code', sanitize_text_field( $_POST['project_code'] ) );
      motionpointexpress()->update_user_config( 'prerender_key', sanitize_text_field( $_POST['prerender_key'] ) );
      motionpointexpress()->update_user_config( 'redirect_system_pages', ! empty( $_POST['redirect_system_pages'] ) && ( 'on' === $_POST['redirect_system_pages'] ) ? 'on' : 'off' );
      motionpointexpress()->update_user_config( 'deployed', ! empty( $_POST['deployed'] ) && ( 'on' === $_POST['deployed'] ) ? 'on' : 'off' );
      motionpointexpress()->update_user_config( 'translate_login_page', ! empty( $_POST['translate_login_page'] ) && ( 'on' === $_POST['translate_login_page'] ) ? 'on' : 'off' );

      $location_host = sanitize_text_field( $_POST['location_host'] );
      if ( ! empty( $location_host_options[ $location_host ] ) ) {
        motionpointexpress()->update_user_config( 'location_host', $location_host );
      }

      $publishing_mode = sanitize_text_field( $_POST['publishing_mode'] );
      if ( ! empty( $publishing_mode_options[ $publishing_mode ] ) ) {
        motionpointexpress()->update_user_config( 'publishing_mode', $publishing_mode );
      }

      $config = motionpointexpress()->save_user_config();
    } else {
      $config = motionpointexpress()->get_user_config();
    }

    $project_settings = motionpointexpress()->get_project_settings( $ignore_cache = true );

    if ( ! empty( $project_settings['languages'] ) ) {
      foreach ( $project_settings['languages'] as $key => $item ) {
        if ( empty( $item['deployPath'] ) && ( $config['deployed'] === 'on' ) ) {
          $project_settings['languages'][$key]['status'] = 'error';
          $project_settings['languages'][$key]['status_tooltip'] = "Subdirectory is not set for the {$item['language']} [{$item['targetLanguage']}] language.";
          $project_settings['languages'][$key]['status_message'] = "Subdirectory is not set for the {$item['language']} [{$item['targetLanguage']}] language. Please go to MotionPoint Express dashboard and configure subdirectory for this language in order to enable this language.";
        } elseif ( isset( $item['published'] ) && empty( $item['published'] ) ) {
          $project_settings['languages'][$key]['status'] = 'error';
          $project_settings['languages'][$key]['status_tooltip'] = "{$item['language']} [{$item['targetLanguage']}] language has been configured, but not published.";
          $project_settings['languages'][$key]['status_message'] = "{$item['language']} [{$item['targetLanguage']}] language has been configured, but not published and therefore it's not enabled.";
        } else {
          $project_settings['languages'][$key]['status'] = 'success';
          $project_settings['languages'][$key]['status_tooltip'] = "{$item['language']} [{$item['targetLanguage']}] language has been properly configured and enabled.";
        }
      }      
    }


    $new_tab_link_icon = '<i aria-hidden="true" class="dashicons dashicons-external" style="text-decoration:none;"></i>';
    ?>
    <div class="wrap">
      <h1><?php esc_html_e( 'MotionPoint Express settings', 'motionpoint-express' ) ?></h1>
      <form id="MotionPointExpressSettingsForm" method="POST">
        <table class="form-table">
          <tbody>
            <tr>
              <th><?php esc_html_e( 'Status', 'motionpoint-express' ) ?></th>
              <td>
                <select name="status" id="MotionPointExpressStatus">
                  <?php foreach ( $status_options as $value => $name ) : ?>
                    <option value="<?php echo esc_attr( $value ) ?>" <?php selected( $config['status'], $value ); ?>><?php echo esc_html( $name ) ?></option>
                  <?php endforeach; ?>
                </select>
                <p><?php esc_html_e( 'Quickly activate or deactivate translation features.', 'motionpoint-express' ) ?></p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Project code', 'motionpoint-express' ) ?></th>
              <td>
                <input type="text" name="project_code" id="MotionPointExpressProjectCode" class="regular-text" value="<?php echo esc_attr( $config['project_code'] ) ?>" />
                <?php if ( empty( $config['project_code'] ) ) : ?>
                  <p class="motionpointexpress-error"><?php esc_html_e( 'MotionPoint Express project code is missing. Make sure to enter the project code to enable website translation.', 'motionpoint-express' ) ?></p>
                <?php elseif ( empty( $project_settings ) ) : ?>
                  <p class="motionpointexpress-error"><?php esc_html_e( 'The project with the specified project code does not exists. Please enter valid project code.', 'motionpoint-express' ) ?></p>
                <?php endif; ?>
                <p><?php esc_html_e( "You can find your project code in your MotionPoint Express account after it's been created.", 'motionpoint-express' ) ?> <a href="https://support.motionpointexpress.com/hc/en-us/articles/13043814905500-How-does-it-work" target="_blank">Learn more<i aria-hidden="true" class="dashicons dashicons-external" style="text-decoration:none"></i></a>.</p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Languages', 'motionpoint-express' ) ?></th>
              <td>
                <?php if ( ! empty( $project_settings['languages'] ) && ! empty( $project_settings['config'] ) ) : ?>
                  <?php
                    $flags_sprite_image_size = $project_settings['config']['atlasWidth'];
                    $flag_size = $project_settings['config']['flagWidth'];
                    $image_size = 16;
                    $resize_ratio = $flag_size / $image_size;
                    $background_image_size = $flags_sprite_image_size / $resize_ratio;
                  ?>
                  <div class="motionpointexpress-language-list">
                    <?php foreach ( $project_settings['languages'] as $item ) : ?>
                      <?php
                        $flag_style_data = array(
                          '{image_size}'       => $image_size,
                          '{flags_sprite_url}' => $project_settings['config']['sheet'],
                          '{flag_bg_x}'        => $item['flag']['x'] / $resize_ratio,
                          '{flag_bg_y}'        => $item['flag']['y'] / $resize_ratio,
                          '{bg_image_size}'    => $background_image_size,
                        );
                        $flag_style = strtr( implode( '', array(
                          "display:inline-block;",
                          "width: {image_size}px;",
                          "height: {image_size}px;",
                          "background-image: url('{flags_sprite_url}');",
                          "background-position: -{flag_bg_x}px -{flag_bg_y}px;",
                          "background-size: {bg_image_size}px {bg_image_size}px;",
                        )), $flag_style_data );
                      ?>
                      <a <?php if ( ! empty( $item['deployPath'] ) ) :?> href="<?php echo esc_attr( $item['deployPath'] ); ?>" target="_blank" <?php endif; ?> class="motionpointexpress-language-list-item motionpointexpress-status-<?php echo esc_attr( $item['status'] ); ?>" title="<?php echo esc_attr( $item['status_tooltip'] ); ?>">
                        <span class="motionpointexpress-language-flag" style="<?php echo esc_attr( $flag_style ) ?>"></span>
                        <span class="motionpointexpress-language-title"><?php echo esc_html( $item['language'] ) ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php foreach ( $project_settings['languages'] as $item ) : ?>
                    <?php if ( ! empty( $item['status_message'] ) ) : ?>
                      <p class="motionpointexpress-<?php echo esc_attr( $item['status'] ) ?>"><?php echo esc_html( $item['status_message'], 'motionpoint-express' ) ?></p>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  <p><?php esc_html_e( 'Translation target languages set in your MotionPoint Express account.', 'motionpoint-express' ) ?></p>
                <?php else : ?>
                  <p><?php esc_html_e( 'Please provide project code and save changes to see the languages configured in your project.', 'motionpoint-express' ) ?></p>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Location', 'motionpoint-express' ) ?></th>
              <td>
                <select name="location_host" id="MotionPointExpressLocation">
                  <?php foreach ( $location_host_options as $value => $name ) : ?>
                    <option value="<?php echo esc_attr( $value ) ?>" <?php selected( $config['location_host'], $value ); ?>><?php echo esc_html( $name ) ?></option>
                  <?php endforeach; ?>
                </select>
                <p id="MotionPointExpressCustomLocationWrapper">
                  <input type="text" name="custom_location_host" id="MotionPointExpressCustomLocation" class="regular-text" value="<?php echo esc_attr( $config['custom_location_host'] ) ?>" />
                </p>
                <p><a href="#" data-motionpointpress-login-link target="wp-motionpointexpress-domainname"><?php esc_html_e( 'Login', 'motionpoint-express' ) ?><?php echo wp_kses( $new_tab_link_icon, array( 'i' => array( 'aria-hidden' => array(), 'class' => array(), 'style' => array() ) ) ); ?></a></p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Publishing mode', 'motionpoint-express' ) ?></th>
              <td>
                <select name="publishing_mode">
                  <?php foreach ( $publishing_mode_options as $value => $name ) : ?>
                    <option value="<?php echo esc_attr( $value ) ?>" <?php selected( $config['publishing_mode'], $value ); ?>><?php echo esc_html( $name ) ?></option>
                  <?php endforeach; ?>
                </select>
                <p><?php esc_html_e( 'Set the publishing mode according to your MotionPoint Express settings.', 'motionpoint-express' ) ?></p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Redirect system pages', 'motionpoint-express' ) ?></th>
              <td>
                <label>
                  <input type="checkbox" name="redirect_system_pages" <?php checked( $config['redirect_system_pages'] === 'on' ) ?>>
                  <?php esc_html_e( 'Yes, redirect all system pages to the original site', 'motionpoint-express' ) ?>
                </label>
                <p><?php esc_html_e( 'If this is enabled and a user lands on a Login or Registration page in a language other than the default, the plugin will redirect the user to the corresponding page (Login or Registration) in the default language.', 'motionpoint-express' ) ?></p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Subdirectory publishing', 'motionpoint-express' ) ?></th>
              <td>
                <label>
                  <input type="checkbox" name="deployed" <?php checked( $config['deployed'] === 'on' ) ?>>
                  <?php esc_html_e( 'Publish into subdirectory, instead of using query parameter as a language selector', 'motionpoint-express' ) ?>
                </label>
                <p><?php esc_html_e( 'The actual names for the subdirectories can be set in', 'motionpoint-express' ) ?> <a href="https://www.motionpointexpress.com/" target="_blank" data-motionpointpress-login-link><?php esc_html_e( 'your MotionPoint Express account', 'motionpoint-express' ) ?><i aria-hidden="true" class="dashicons dashicons-external" style="text-decoration:none"></i></a>.</p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'Translate login page', 'motionpoint-express' ) ?></th>
              <td>
                <label>
                  <input type="checkbox" name="translate_login_page" <?php checked( $config['translate_login_page'] === 'on' ) ?>>
                  <?php esc_html_e( 'Yes, translate login page as well', 'motionpoint-express' ) ?>
                </label>
                <p><?php esc_html_e( 'By default, the MotionPoint Express plugin does not translate the WordPress login page. However, you can allow it to be translated.', 'motionpoint-express' ) ?></p>
              </td>
            </tr>
            <tr>
              <th><?php esc_html_e( 'prerender.io token', 'motionpoint-express' ) ?></th>
              <td>
                <input type="text" name="prerender_key" class="regular-text" value="<?php echo esc_attr( $config['prerender_key'] ) ?>" />
                <p><?php
                  echo " ";
                  echo wp_kses_post( strtr(
                    __( 'Unique API Key, which you can find on your {account_settings_page} after subscribing to {prerender_io_services}.', 'motionpoint-express' ),
                    array(
                      '{account_settings_page}' => sprintf(
                        '<a href="https://dashboard.prerender.io/settings?utm_source=motionpointexpress+WP+plugin&utm_medium=motionpointexpress+WP+plugin" target="_blank">%s%s</a>',
                        __( 'account settings page', 'motionpoint-express' ),
                        wp_kses( $new_tab_link_icon, array( 'i' => array() ) )
                      ),
                      '{prerender_io_services}' => sprintf(
                        '<a href="https://prerender.io?utm_source=motionpointexpress+WP+plugin&utm_medium=motionpointexpress+WP+plugin" target="_blank">%s%s</a>',
                        __( 'prerender.io services', 'motionpoint-express' ),
                        wp_kses( $new_tab_link_icon, array( 'i' => array() ) )
                      ),
                    )
                  ) );
                ?></p>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit">
          <?php wp_nonce_field( 'motionpointexpress_save_settings', 'motionpointexpress_nonce' ); ?>
          <input type="submit" name="submit_btn" id="submit_btn" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'motionpoint-express' ) ?>">
        </p>
      </form>

      <a id="MotionPointExpressDebugToggleBtn" class="button button-secondary" href="#"><span class="motionpointexpress-show">Show</span><span class="motionpointexpress-hide">Hide</span> debug information</a>
      <div id="MotionPointExpressDebugInfo">
        <h4>PROJECT SETTINGS</h4>
        <p><?php echo esc_html( wp_json_encode( $project_settings ) ); ?></p>
        <h4>PLUGIN SETTINGS</h4>
        <p><?php echo esc_html( wp_json_encode( $config ) ); ?></p>
        <h4>RAW STUB.JSON</h4>
        <p><?php
          $raw_stub_json = defined('ENABLE_CACHE') ? wp_cache_get( 'raw_stub_json', 'motionpointexpress' ) : get_transient( 'motionpointexpress_raw_stub_json' );
          echo esc_html( $raw_stub_json );
        ?></p>
      </div>
    </div>
    <?php
  }


  /**
   *  Enqueue admin page assets
   */
  public function enqueue_assets( $hook_suffix ) {
    if ( 'toplevel_page_motionpoint-express' === $hook_suffix ) {
      wp_enqueue_style( 'motionpointexpress-admin', motionpointexpress()->get_setting('url') . 'assets/css/admin.css', array(), motionpointexpress()->get_setting('version'), 'all' );
      wp_enqueue_script( 'motionpointexpress-admin', motionpointexpress()->get_setting('url') . 'assets/js/admin.js', array( 'jquery' ), motionpointexpress()->get_setting('version'), true );
    }
  }


  /**
   *  Displays admin notices if any
   *
   *  @param N/A
   *  @return N/A
   */
  public function admin_notices() {
    if ( $this->is_dashboard_page() || $this->is_plugins_page() || $this->is_plugin_page() ) {
      $mpe_plugin_path = 'motionpoint-express/motionpoint-express.php';
      $is_mpe_plugin_installed = $this->is_plugin_installed( $mpe_plugin_path );

      $config = motionpointexpress()->get_user_config();
      $project_settings = ! empty( $config['project_code'] ) ? motionpointexpress()->get_project_settings( $ignore_cache = true ) : null;

      if ( empty( $project_settings ) ) {
        $this->display_admin_notice(
          sprintf(
            '<p>%s <strong>%s</strong><p><p>%s %s</p><p>%s</p>',
            '<span class="dashicons-before dashicons-translation"></span>',
            __( 'Your MotionPoint Express plugin is not configured properly', 'motionpoint-express' ),
            strtr(
              __( 'You must enter a valid project code on your {MotionPoint Express plugin setting page} in order to start translating your website.', 'motionpoint-express' ),
              array(
                '{MotionPoint Express plugin setting page}' => sprintf(
                  '<a href="%s">%s</a>',
                  admin_url('admin.php?page=motionpoint-express'),
                  __( 'MotionPoint Express plugin setting page', 'motionpoint-express' )
                )
              )
            ),
            strtr(
              __( 'Register at {motionpoint.com/express} and login to your account to see your project code.', 'motionpoint-express' ),
              array(
                '{motionpoint.com/express}' => sprintf(
                  '<a href="https://www.motionpoint.com/express/?utm_source=wordpress&utm_medium=plugin&utm_content=notification" target="_blank">%s</a>',
                  __( 'motionpoint.com/express', 'motionpoint-express' )
                )
              )
            ),
            sprintf(
              '%s | %s | %s',
              sprintf(
                '<a href="https://www.motionpoint.com/express/?utm_source=wordpress&utm_medium=plugin&utm_content=notification" target="_blank">%s</a>',
                __( 'Register', 'motionpoint-express' )
              ),
              sprintf(
                '<a href="https://app.motionpointexpress.com/?utm_source=wordpress&utm_medium=plugin&utm_content=notification" target="_blank">%s</a>',
                __( 'Login', 'motionpoint-express' )
              ),
              sprintf(
                '<a href="https://support.motionpointexpress.com/hc/en-us/articles/13043814905500-How-does-it-work/?utm_source=wordpress&utm_medium=plugin&utm_content=notification" target="_blank">%s</a>',
                __( 'Help', 'motionpoint-express' )
              )
            )
          ),
          'warning',
          true
        );
        // $this->display_admin_notice(
        //   sprintf(
        //     '<p><strong>%s</strong><p><p>%s</p>',
        //     __( 'Your MotionPoint Express translation has not been configured', 'motionpoint-express' ),
        //     strtr(
        //       __( 'You must enter a valid project code from your {MotionPoint Express account} in order to start translating your website.', 'motionpoint-express' ),
        //       array(
        //         '{MotionPoint Express account}' => sprintf(
        //           '<a href="https://www.motionpoint.com/express/?utm_source=wordpress&utm_medium=plugin&utm_content=notification" target="_blank">%s</a>',
        //           __( 'MotionPoint Express account', 'motionpoint-express' )
        //         )
        //       )
        //     )
        //   ),
        //   'info',
        //   true
        // );
      }
    }
  }


  /**
   *  Formats and outputs notice HTML in admin
   *
   *  @param $message        (string) Message to display
   *  @param $type           (string) Notification type: error, warning, info
   *  @param $is_dismissible (boolean) Whether to allow user to dismiss it or not
   *  @return N/A
   */
  public function display_admin_notice( $message, $type = 'success', $is_dismissible = true ) {
    $custom_style = $type === 'info' ? 'border-left-color: #0061d5;' : '';
    printf(
      '<div class="notice notice-%s %s" style="%s">%s</div>',
      esc_attr( $type ),
      $is_dismissible ? 'is-dismissible' : '',
      $custom_style,
      substr( $message, 0, 1 ) === '<' ? $message : '<p>' . esc_html( $message ) . '</p>'
    );
  }


  /**
   *  Check if the current admin page is dashboard
   *
   *  @param N/A
   *  @return boolean
   */
  public function is_dashboard_page() {
    $current_screen = get_current_screen();
    return ! empty( $current_screen ) ? $current_screen->base === 'dashboard' : false;
  }


  /**
   *  Check if the current admin page is plugins
   *
   *  @param N/A
   *  @return boolean
   */
  public function is_plugins_page() {
    $current_screen = get_current_screen();
    return ! empty( $current_screen ) ? $current_screen->base === 'plugins' : false;
  }


  /**
   *  Check if the current admin page is MPE page
   *
   *  @param N/A
   *  @return boolean
   */
  public function is_plugin_page() {
    $current_screen = get_current_screen();
    return ! empty( $current_screen ) ? $current_screen->parent_base === 'motionpoint-express' : false;
  }


  /**
   *  Check if a given plugin is installed
   *
   *  @param $plugin (string) Path to the plugin file relative to the plugins directory.
   *  @return boolean
   */
  public function is_plugin_installed( $plugin ) {
    $installed_plugins = get_plugins();
    return ! empty( $installed_plugins[ $plugin ] );
    // printf('installed_plugins: <pre>%s</pre>', print_r( $installed_plugins, true ));
    // exit;
  }


}


/**
 *  The main function responsible for returning motionpointexpress_admin instance
 *
 *  @return object motionpointexpress_admin instance
 */
function motionpointexpress_admin() {
  return motionpointexpress_admin::instance();
}


// initialize
motionpointexpress_admin();

endif; // class_exists check

?>
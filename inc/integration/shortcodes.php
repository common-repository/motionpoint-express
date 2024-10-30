<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'motionpointexpress_shortcodes' ) ) :

final class motionpointexpress_shortcodes {

  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;


  /**
   *  Initialize motionpointexpress_shortcodes
   */
  private function __construct() {
		add_shortcode( 'motionpointexpress_language_selector', array( $this, 'language_selector' ) );
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_shortcodes instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


	/**
	 *  Renders language selector shortcode
	 */
	public function language_selector( $atts, $content = null ) {
    $atts = shortcode_atts( array(), $atts, 'motionpointexpress_language_selector' );

    ob_start();
		include( motionpointexpress()->get_setting('path') . 'inc/integration/shortcodes/language-selector.php' );
		$html = ob_get_clean();

	  // Allow 3rd parties to adjust
		$html = apply_filters( 'motionpointexpress_language_selector_shortcode_html', $html, compact( 'atts', 'content' ) );

		return $html;
	}

}


/**
 *  The main function responsible for returning motionpointexpress_shortcodes plugin
 *
 *  @return (object) motionpointexpress_shortcodes instance
 */
function motionpointexpress_shortcodes() {
  return motionpointexpress_shortcodes::instance();
}


// initialize
motionpointexpress_shortcodes();

endif; // class_exists check

?>

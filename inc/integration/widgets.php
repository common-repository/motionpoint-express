<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( motionpointexpress()->get_setting('path') . 'inc/integration/widgets/language-selector.php' );

if ( ! class_exists( 'motionpointexpress_widgets' ) ) :

final class motionpointexpress_widgets {

  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;
  
  
  /**
   *  Initialize motionpointexpress_widgets
   */
  private function __construct() {
		add_action( 'widgets_init', array( $this, 'init_widgets' ) );
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_widgets instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


	/**
	 *  Register widgets
	 */
	public function init_widgets() {
	  register_widget( 'motionpointexpress_language_selector' );
	}

}


/**
 *  The main function responsible for returning motionpointexpress_widgets plugin
 *
 *  @return (object) motionpointexpress_widgets instance
 */
function motionpointexpress_widgets() {
  return motionpointexpress_widgets::instance();
}


// initialize
motionpointexpress_widgets();

endif; // class_exists check

?>

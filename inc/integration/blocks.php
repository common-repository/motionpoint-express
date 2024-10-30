<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'motionpointexpress_blocks' ) ) :

final class motionpointexpress_blocks {

  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;
  
  
  /**
   *  Initialize motionpointexpress_blocks
   */
  private function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_assets' ) );
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_blocks instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


	/**
	 *  Enqueues block assets
	 */
	public function block_editor_assets() {
		require_once( motionpointexpress()->get_setting('path') . 'inc/integration/blocks/language-selector/block-editor-assets.php' );
	}

}


/**
 *  The main function responsible for returning motionpointexpress_blocks plugin
 *
 *  @return (object) motionpointexpress_blocks instance
 */
function motionpointexpress_blocks() {
  return motionpointexpress_blocks::instance();
}


// initialize
motionpointexpress_blocks();

endif; // class_exists check

?>

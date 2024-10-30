<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'motionpointexpress_menus' ) ) :

final class motionpointexpress_menus {

  /**
   * Plugin instance
   *
   * @var object $instance
   */
  protected static $instance;


  /**
   *  Initialize motionpointexpress_menus
   */
  private function __construct() {
		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'customize_nav_menu_available_item_types' ) );
		add_filter( 'customize_nav_menu_available_items', array( $this, 'customize_nav_menu_available_items' ), 10, 4 );

		add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'walker_nav_menu_start_el' ), 10, 4 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
  }


  /**
   *  Create or retrieve instance. Singleton pattern
   *
   *  @static
   *
   *  @return object motionpointexpress_menus instance
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self();
  }


	/**
	 * Add custom nav meta box.
	 */
	public function admin_enqueue_scripts() {
    global $pagenow;
    
    if ( in_array( $pagenow, array( 'nav-menus.php', 'customize.php' ) ) ) {
      wp_enqueue_script( 'motionpointexpress-admin', motionpointexpress()->get_setting('url') . 'assets/js/admin-menu.js', array( 'jquery' ), motionpointexpress()->get_setting('version'), true );
    }
	}


	/**
	 * Add custom nav meta box.
	 *
	 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
	 */
	public function add_nav_menu_meta_boxes() {
		add_meta_box(
			'motionpointexpress_endpoints_nav_link',
			__( 'MotionPoint Express', 'motionpoint-express' ),
			array( $this, 'nav_menu_links' ),
			'nav-menus',
			'side',
			'low'
		);
	}


	/**
	 * Output menu links.
	 */
	public function nav_menu_links() {
		?>
		<div id="posttype-motionpointexpress-menu-items" class="posttypediv">
			<div id="tabs-panel-motionpointexpress-menu-items" class="tabs-panel tabs-panel-active">
				<ul id="motionpointexpress-menu-items-checklist" class="categorychecklist form-no-clear">
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="-1" /> <?php echo esc_html( __('Language Selector', 'motionpoint-express') ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="custom" />
							<input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="<?php echo esc_attr( __('Language Selector', 'motionpoint-express') ); ?>" />
							<input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="#motionpointexpress-language-selector" />
							<input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" />
						</li>
				</ul>
			</div>
			<p class="button-controls">
				<span class="list-controls">
					<a href="<?php echo esc_url( admin_url( 'nav-menus.php?page-tab=all&selectall=1#posttype-motionpointexpress-menu-items' ) ); ?>" class="select-all"><?php esc_html_e( 'Select all', 'motionpoint-express' ); ?></a>
				</span>
				<span class="add-to-menu">
					<button type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'motionpoint-express' ); ?>" name="add-post-type-menu-item" id="submit-posttype-motionpointexpress-menu-items"><?php esc_html_e( 'Add to menu', 'motionpoint-express' ); ?></button>
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}


	/**
	 * Register motionpointexpress nav type in Customizer
	 *
	 * @param  array $item_types Menu item types.
	 * @return array
	 */
	public function customize_nav_menu_available_item_types( $item_types ) {
		$item_types[] = array(
			'title'      => __( 'MotionPoint Express', 'motionpoint-express' ),
			'type_label' => __( 'MotionPoint Express', 'motionpoint-express' ),
			'type'       => 'motionpointexpress_nav',
			'object'     => 'motionpointexpress_nav_item',
		);

		return $item_types;
	}


	/**
	 * Register language selector nav item in Customizer
	 *
	 * @param  array   $items  List of nav menu items.
	 * @param  string  $type   Nav menu type.
	 * @param  string  $object Nav menu object.
	 * @param  integer $page   Page number.
	 * @return array
	 */
	public function customize_nav_menu_available_items( $items = array(), $type = '', $object = '', $page = 0 ) {
		if ( 'motionpointexpress_nav_item' !== $object ) {
			return $items;
		}

		// Don't allow pagination since all items are loaded at once.
		if ( 0 < $page ) {
			return $items;
		}

		$items[] = array(
			'id'         => 'motionpointexpress_language_selector',
			'title'      => __('Language Selector', 'motionpoint-express'),
			'type'       => 'custom',
			'url'        => '#motionpointexpress-language-selector',
		);

		return $items;
	}


	/**
	 * Render language selector menu item
	 *
	 * @param  array $items Menu items list
	 * @param  array $args Menu arguments
	 * @return array
	 */
	public function walker_nav_menu_start_el( $item_output, $menu_item, $depth, $args ) {
		if ( '#motionpointexpress-language-selector' === $menu_item->url ) {
			$item_output = do_shortcode('[motionpointexpress_language_selector]');
		}

		return $item_output;
	}

}


/**
 *  The main function responsible for returning motionpointexpress_menus plugin
 *
 *  @return (object) motionpointexpress_menus instance
 */
function motionpointexpress_menus() {
  return motionpointexpress_menus::instance();
}


// initialize
motionpointexpress_menus();

endif; // class_exists check

?>

<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/**
 *  Language selector widget class
 */
class motionpointexpress_language_selector extends WP_Widget {

	function __construct() {
		parent::__construct(
			// Base ID
			'motionpointexpress_language_selector',
			 
			// Widget name
			__('MotionPoint Express Language Selector', 'motionpoint-express'),
			
			// Options
			array(
				'description' => __( 'Display language selector', 'motionpoint-express' ),
			)
		);
	}

	public function widget( $args, $instance ) {
    	ob_start();
		echo $args['before_widget'];
		echo do_shortcode('[motionpointexpress_language_selector]');
		echo $args['after_widget'];
		$html = ob_get_clean();

		// Allow 3rd parties to adjust
		$html = apply_filters( 'motionpointexpress_language_selector_widget_html', $html, compact( 'args', 'instance' ) );

		echo wp_kses( $html, array( 'span' => array() ) );
	}
	 
	public function form( $instance ) {}
	 
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}
}

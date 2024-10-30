<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

$block_path = 'inc/integration/blocks/language-selector/edit.js';

wp_enqueue_script(
  'motionpointexpress-language-selector', // Unique handle
  motionpointexpress()->get_setting('url') . $block_path, // Script URL
  array( 'wp-blocks', 'wp-i18n', 'wp-editor', 'wp-element' ), // Dependencies
  filemtime( motionpointexpress()->get_setting('path') . $block_path ), // Version
  true // Load in footer
);

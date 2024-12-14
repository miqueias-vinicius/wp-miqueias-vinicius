<?php

/* ==================================================================
Plugin Name: Metaboxs (Miqueias Vinicius)
Plugin URI: https://github.com/miqueias-vinicius/wp-mv-metaboxs
Description: Gerenciamento de metaboxs e tipos de posts
Version: 2.0.0
Requires at least: 5.0
Requires PHP: 5.2
Author: Miqueias Vinicius
Author URI: https://miqueiasvinicius.com/
License: software proprietário
Text Domain: wp_mv_metaboxs
================================================================== */

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

define('WP_MV_METABOXS_PLUGIN_URI', 'https://github.com/miqueias-vinicius/wp-mv-metaboxs');
define('WP_MV_METABOXS_DIR', plugin_dir_path(__FILE__));
define('WP_MV_METABOXS_URL', plugin_dir_url(__FILE__));
define('WP_MV_METABOXS_TEXT_DOMAIN', 'wp-mv-metaboxs');

require_once 'includes/class-metaboxs.php';

require_once 'includes/class-post-types.php';

require_once 'includes/update.php';

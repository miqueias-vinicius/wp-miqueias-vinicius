<?php

/* ==================================================================
Plugin Name: WP Miqueias Vinicius
Plugin URI: https://github.com/miqueias-vinicius/wp-miqueias-vinicius/
Description: Gerenciamento de tipos de posts, metaboxs, taxonomias e mais.
Version: 3.0.0
Requires at least: 5.0
Requires PHP: 5.2
Author: Miqueias Vinicius
Author URI: https://miqueiasvinicius.com/
License: software proprietário
Text Domain: wp-miqueias-vinicius
================================================================== */

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

define('WP_MV_DIR', plugin_dir_path(__FILE__));
define('WP_MV_URL', plugin_dir_url(__FILE__));
define('WP_MV_TEXT_DOMAIN', 'wp-miqueias-vinicius');
define('WP_MV_GITHUB_URL', 'https://github.com/miqueias-vinicius/wp-miqueias-vinicius');

require_once WP_MV_DIR . 'vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/miqueias-vinicius/wp-miqueias-vinicius/',
    __FILE__,
    'wp-miqueias-vinicius'
);

$updateChecker->setBranch('master');

require_once WP_MV_DIR . 'includes/class-metaboxs.php';

require_once WP_MV_DIR . 'includes/class-post-types.php';

require_once WP_MV_DIR . 'includes/class-taxonomies.php';

<?php

require_once WP_MV_METABOXS_DIR .  'includes/addons/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$update = PucFactory::buildUpdateChecker(
    WP_MV_METABOXS_URL . '/details.json',
    __FILE__,
    WP_MV_METABOXS_TEXT_DOMAIN
);

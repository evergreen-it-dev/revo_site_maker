<?php

$site_url = $modx->config['site_url'];
$core_path = $modx->config['core_path'];
$component_path = $core_path.'components/create_site/';
$includes = $component_path . 'includes/';
$images_url = $site_url . 'core/components/create_site/includes/images/';
$ajax = $site_url . 'core/components/create_site/includes/ajax/';

require_once __DIR__.'/includes/class/createsite.class.php';

ob_start();
$obj = new CreateSiteFunctions($modx);
include( $includes . 'bootstrap.php' );
include( $includes . 'functions.php' );
include( $includes . 'template.php' );
$output .= ob_get_contents();
ob_end_clean();

return $output;

?>
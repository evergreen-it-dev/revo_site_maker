<?php

$parse_uri = explode( 'core/components', $_SERVER['SCRIPT_FILENAME'] );
$site_url = $parse_uri[0];

require_once $site_url.'config.core.php';
require_once $site_url.'core/model/modx/modx.class.php';
require_once __DIR__.'/class/createsite.class.php';
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$obj = new CreateSiteFunctions($modx);

?>
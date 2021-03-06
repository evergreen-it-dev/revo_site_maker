<?php
/*
 * MODX Revolution
 *
 * Copyright 2006-2011 by MODX, LLC.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

$manager = stripos($_SERVER['REQUEST_URI'], '/manager/');
if($manager !== false){
    $count = count(explode('/',$_SERVER['REQUEST_URI']));
    if($count < 4){
        list($request,$params) = explode('?',$_SERVER['REQUEST_URI']);
        if(!empty($params))$params='?'.$params;
        header("Location: /manager/index.php".$params,TRUE,302);
        exit();
    }
}

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tstart= $mtime;

error_reporting(E_ALL | E_STRICT);

/* define this as true in another entry file, then include this file to simply access the API
 * without executing the MODX request handler */
if (!defined('MODX_API_MODE')) {
    define('MODX_API_MODE', false);
}

/* this can be used to disable caching in MODX absolutely */
$modx_cache_disabled= false;

/* include custom core config and define core path */
@include(dirname(__FILE__) . '/config.core.php');
if (!defined('MODX_CORE_PATH')) define('MODX_CORE_PATH', dirname(__FILE__) . '/core/');

/* include the modX class */
if (!@include_once (MODX_CORE_PATH . "model/modx/modx.class.php")) {
    $errorMessage = 'Site temporarily unavailable';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header('HTTP/1.1 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* start output buffering */
ob_start();

/* Create an instance of the modX class */
$modx= new modX();
if (!is_object($modx) || !($modx instanceof modX)) {
    @ob_end_flush();
    $errorMessage = '<a href="setup/">MODX not installed. Install now?</a>';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header('HTTP/1.1 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* Set the actual start time */
$modx->startTime= $tstart;

/* Set additional logging options including level and target: */
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

/* Set debugging mode (i.e. error_reporting): */
$modx->setDebug(E_ALL & ~E_NOTICE);

/* Initialize the default 'web' context */
$modx->initialize('example-ua');

/* execute the request handler */
if (!MODX_API_MODE) {
    $modx->handleRequest();
}

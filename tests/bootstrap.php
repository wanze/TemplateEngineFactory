<?php

if(!defined("PROCESSWIRE")) define("PROCESSWIRE", 300); // index version
$rootPath = __DIR__ . '../../../../../';
if(DIRECTORY_SEPARATOR != '/') $rootPath = str_replace(DIRECTORY_SEPARATOR, '/', $rootPath);
$composerAutoloader = $rootPath . '/vendor/autoload.php'; // composer autoloader
if(file_exists($composerAutoloader)) require_once($composerAutoloader);
if(!class_exists("ProcessWire", false)) require_once("$rootPath/wire/core/ProcessWire.php");

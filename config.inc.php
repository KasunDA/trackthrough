<?php
$_PATHS["install_root"] = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
$_PATHS["base"] = $_PATHS["install_root"] . basename(dirname(__FILE__)).DIRECTORY_SEPARATOR;
$_PATHS["includes"] = $_PATHS["base"] . "includes" . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR;
$_PATHS["FW"] = $_PATHS["base"] . "includes" . DIRECTORY_SEPARATOR . "FW" . DIRECTORY_SEPARATOR;
$_PATHS["templates"] = $_PATHS["base"] . "templates_c" . DIRECTORY_SEPARATOR;
$_PATHS["pear"] = $_PATHS["base"] . "pear" . DIRECTORY_SEPARATOR;
$_PATHS["model"] = $_PATHS["base"] . "includes" . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR;
$_PATHS["logs"] = $_PATHS["base"] . "logs" . DIRECTORY_SEPARATOR;
$_PATHS["batch"] = $_PATHS["base"] . "batch" . DIRECTORY_SEPARATOR;

/**
* Set include path
*/
ini_set("include_path", "$_PATHS[base]" . PATH_SEPARATOR . "$_PATHS[includes]" . PATH_SEPARATOR . "$_PATHS[FW]" . PATH_SEPARATOR . "$_PATHS[pear]" . PATH_SEPARATOR . "$_PATHS[templates]" . PATH_SEPARATOR . "$_PATHS[model]". PATH_SEPARATOR . "$_PATHS[batch]");

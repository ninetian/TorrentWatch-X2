<?php

if(file_exists("/etc/init_nmt"))
  $platform = "NMT";
else if(is_dir("/Library/WebServer"))
  $platform = "OSX";
else
  $platform = "Linux";

function platform_initialize() {
  global $platform;
}

function platform_getConfigFile() {
  return platform_getConfigDir() . "/torrentwatch.config";
}

?>
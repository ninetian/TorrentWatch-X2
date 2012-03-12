<?php
ini_set('include_path', '.:./php');
require_once('rss_dl_utils.php');

read_config_file();

foreach($config_values['Hidden'] as $key => $hidden) {
	unset($config_values['Hidden'][$key]);
	$config_values['Hidden'][strtolower($key)] = "hidden";
}

write_config_file();

?>

<?php
ini_set('include_path', '.:'.dirname(__FILE__).'/php');
require_once('rss_dl_utils.php');

read_config_file();

$database="/share/Apps/NMTDVR/protected/data/source.db";
$query="
SELECT tv.title tvtitle, fav.saveIn,fav.onlyNewer, quality.title qtitle
FROM favoriteTvShows fav, tvShow tv
left outer join favoriteTvShows_quality fq on fav.id = fq.favoriteTvShows_id
left outer join quality on fq.quality_id = quality.id
where tv.id = fav.tvShow_id;
";

try {
   $db = new PDO("sqlite:".$database);
   foreach ($db->query($query) as $row) {
		$name=$row['tvtitle'];
		$savein=$row['saveIn'] ? $row['saveIn'] : "Default";
		$quality=$row['qtitle'];
		print "Importing $name $quality .. ";
		if (find_match($name,$config_values['Favorites'])) {
			print "already present, skipping<br/>";
		} else {
			$changed=1;			
			print "adding<br/>";
			$config_values['Favorites'][]=array( 'Name'=> $name, 'Filter' => $name, 'Not'=> '', 'Save In' =>$savein,
					'Episodes' => '', 'Feed' => 'all', 'Quality' => $quality, 'seedRatio' => '-1', 'Season' => '', 'Episode' => '');
		}	
	    }
	print_r($config_values['Favorites']);
	if ($changed) {
	print "Writing config..<br>";
	write_config_file();
	}
	$db= null;
} 
catch (PDOException $e) {	
echo $e->getMessage();
}



function find_match ($needle, $haystack) {
	foreach ($haystack as $twig) {
		if ($twig['Name'] == $needle || $twig['Filter'] == $needle) {
			return (TRUE);
		}
	}
}
?>

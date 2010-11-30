<?php
// disable any kind of caching
header( "Expires: Mon, 20 Dec 2000 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

ini_set('include_path', '.:./php');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// error_reporting(E_ALL);
require_once('rss_dl_utils.php');
require_once('api/TMDb.php');
require_once('api/TVDB.php');
global $platform;

$tw_version[0] = "0.7.0";

if(file_exists(get_base_dir() . "/.hg")) {
    exec('hg id -i', $hgId, $return);
    if($return == 0) {
        $tw_version[1] = $hgId[0];
    } else {
        $tw_version[1] = "unknown";
    }
} else if($platform == 'NMT') {
    $tw_version[1] = 'NMT';
    
}

$test_run = 0;
$firstrun = 0;
$verbosity = 0;

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

if(!(file_exists('php/config.php'))) {
    $config = getcwd() . '/php/config.php';
        echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">Please copy $config.dist to $config and edit it to match your environment. Then click your browsers refresh button.</div>";
    return;
}

// This function parses commands sent from a PC browser
function parse_options() {
    global $html_out, $config_values;
    $filler = "<br>";

    array_keys($_GET);
    $commands = array_keys($_GET);
    if(empty($commands)) return FALSE;

    if(preg_match("/^\//", $commands[0])) {
        $commands[0] = preg_replace("/^\//", '', $commands[0]);
    }
    switch($commands[0]) {
        case 'firstRun':
            foreach($_GET['link'] as $link) {
                add_feed($link);
            }
            update_global_config();
            $config_values['Settings']['FirstRun'] = FALSE;
            write_config_file();
            break;
        case 'getClientData':
            if($_REQUEST['recent']) {
                $response = getClientData(1);
            } else {
                $response = getClientData(0);
            }
            echo $response;
            exit;
        case 'delTorrent':
            $response = delTorrent($_REQUEST['delTorrent'], $_REQUEST['trash']);
            echo "$response";
            exit;
        case 'stopTorrent':
            $response = stopTorrent($_REQUEST['stopTorrent']);
            echo "$response";
            exit;
        case 'startTorrent':
            $response = startTorrent($_REQUEST['startTorrent']);
            echo "$response";
            exit;
        case 'moveTo':
            $response = moveTorrent($_REQUEST['moveTo'], $_REQUEST['torHash']);
            echo "$response";
            exit;
        case 'updateFavorite':
            $response = update_favorite();
            if($response) echo "<div id=\"fav_error\" class=\"dialog_window\" style=\"display: block\">$response</div>";
            break;
        case 'updateFeed':
            update_feed();
            break;
        case 'clearCache':
            clear_cache();
            break;
        case 'setGlobals':
            update_global_config();
            $config_values['Settings']['FirstRun'] = FALSE;
            write_config_file();
            break;
        case 'matchTitle':
            $feedLink = $_GET['rss'];
            foreach($config_values['Feeds'] as $key => $feed) {
                if($feed['Link'] == "$feedLink") $idx = $key;
            }
            if($config_values['Feeds'][$idx]['seedRatio']) {
                $seedRatio = $config_values['Feeds'][$idx]['seedRatio'];
            } else {
                $seedRatio = $config_values['Settings']['Default Seed Ratio'];
            }
        
            if(!($seedRatio)) $seedRatio = -1;
            if(($tmp = guess_match(html_entity_decode($_GET['title']), TRUE))) {
                $_GET['name'] = trim(strtr($tmp['key'], "._", "  "));
                if($config_values['Settings']['MatchStyle'] == "glob") {
                    $_GET['filter'] = trim(strtr($tmp['key'], " ._", "???"));
                    $_GET['filter'] .= '*';
                } else {
                    $_GET['filter'] = trim($tmp['key']);
                }
                $_GET['quality'] = $tmp['data'];
                $_GET['feed'] = $_GET['rss'];
                $_GET['button'] = 'Add';
                $_GET['savein'] = 'Default';
                $_GET['seedratio'] = $seedRatio;
            } else {
                $_GET['name'] = $_GET['title'];
                $_GET['filter'] = $_GET['title'];
                $_GET['quality'] = 'All';
                $_GET['feed'] = $_GET['rss'];
                $_GET['button'] = 'Add';
                $_GET['savein'] = 'Default';
                $_GET['seedratio'] = $seedRatio;
            }
            if($config_values['Settings']['Default Feed All'] && 
                preg_match('/^(\d+)x(\d+)p?$|^(\d{8})$/i', $tmp['episode'])) $_GET['feed'] = 'All';
            $response = update_favorite();
            if($response) echo "<div id=\"fav_error\" class=\"dialog_window\" style=\"display: block\">$response</div>";
            break;
        case 'hide':
            $response = add_hidden(ucwords($_GET['hide']));
            if($response) echo "<div id=\"fav_error\" class=\"dialog_window\" style=\"display: block\">$response</div>";
            break;
        case 'delHidden':
            del_hidden($_GET['unhide']);
            break;
        case 'dlTorrent':
            // Loaded via ajax
            foreach($config_values['Favorites'] as $fav) {
                $guess = guess_match(html_entity_decode($_GET['title']));
                $name = trim(strtr($guess['key'], "._", "  "));
                if($name == $fav['Name']) {
                      $downloadDir = $fav['Save In'];
                } 
            }
            if((!isset($downloadDir) || $downloadDir == "Default" ) && 
		isset($config_values['Settings']['Download Dir'])) {
		    $downloadDir = $config_values['Settings']['Download Dir'];
	    }
            $r = client_add_torrent(preg_replace('/ /', '%20', trim($_GET['link'])),
                $downloadDir, $_GET['title'], $_GET['feed']);
            if($r == "Success") { 
                $torHash = get_torHash(add_cache($_GET['title'])); 
            }
            if(isset($torHash)) {
		echo $torHash;
	    } else {
		echo $r;
	    }
            exit(0);
            break;
        case 'clearHistory':
            // Loaded via ajax
            if(file_exists($config_values['Settings']['History']))
                unlink($config_values['Settings']['History']);
            display_history();
            close_html();
            exit(0);
            break;
        case 'get_client':
            global $config_values;
            echo $config_values['Settings']['Client'];
            exit;
        case 'version_check':
            echo version_check();
            exit;
        case 'post_bug':
            global $tw_version;
            $response = post_bug($_POST['Summary'], $_POST['Name'], $_POST['Email'], $_POST['Priority'], $_POST['Description']);
            echo $response;
            exit;
        case 'get_dialog_data':
            switch($_GET['get_dialog_data']) {
                case '#favorites':
                    display_favorites();
                    exit;
                case '#configuration':
                    display_global_config();
                    exit;
                case '#hidelist':
                    display_hidelist();
                    exit;
                case '#feeds':
                    display_feeds();
                    exit;
                case '#history': 
                    display_history();
                    exit;
                case '#show_legend':
                    display_legend();
                    exit;
                case '#report_bug':
                    report_bug();
                    exit;
                case '#clear_cache':
                    display_clearCache();
                    exit;
                case '#show_transmission':
                    display_transmission();
                    exit;
				case '#episode_info':
					episode_info(urldecode($_GET['episode_name']));
					exit;
                default:
                    exit;
            }
        default:
            $output = "<script type='text/javascript'>alert('Bad Paramaters passed to ".$_SERVER['PHP_SELF'].":  ".$_SERVER['REQUEST_URI']."');</script>";
    }

    if(isset($exec))
        exec($exec, $output);
    if (isset($output)) {
        if(is_array($output))
            $output = implode($filler, $output);
        $html_out .= str_replace("\n", "<br>", "<div class='execoutput'>$output</div>");
        echo $html_out;
        $html_out = "";
    }
    return;
}

function display_global_config() {
    global $config_values, $html_out;

    $savetorrent=$transmission="";
    $deepfull=$deeptitle=$deepTitleSeason=$deepoff=$verifyepisode="";
    $matchregexp=$matchglob=$matchsimple=$dishidelist=$hdiedonate=$mailonhit="";
    $favdefaultall=$onlynewer=$fetchproper=$folderclient=$combinefeeds=$require_epi_info="";

    switch($config_values['Settings']['Client']) {
        case 'Transmission':
            $transmission = 'selected="selected"';
            break;
                case 'folder':
                        $folderclient = 'selected="selected"';
                        break;
        default:
            // Shouldn't happen
            break;
    }
    if($config_values['Settings']['Combine Feeds'] == 1)
        $combinefeeds = 'checked=1';
    if($config_values['Settings']['Require Episode Info'] == 1)
        $require_epi_info = 'checked=1';
    if($config_values['Settings']['Disable Hide List'] == 1)
        $dishidelist = 'checked=1';    
    if($config_values['Settings']['Hide Donate Button'] == 1)
        $hidedonate = 'checked=1';    
    if($config_values['Settings']['Save Torrents'] == 1)
        $savetorrent = 'checked=1';
    if($config_values['Settings']['Email Notifications'] == 1)
        $mailonhit ='checked=1';

    switch($config_values['Settings']['Deep Directories']) {
        case 'Full': $deepfull = 'selected="selected"';break;
        case 'Title': $deeptitle = 'selected="selected"'; break;
        case 'Title_Season': $deepTitleSeason = 'selected="selected"'; break;
        default:$deepoff = 'selected="selected"';break;
    }

    if($config_values['Settings']['Verify Episode'] == 1)
        $verifyepisode = 'checked=1';
    if($config_values['Settings']['Only Newer'] == 1)
        $onlynewer = 'checked=1';
    if($config_values['Settings']['Download Proper'] == 1)
        $fetchproper = 'checked=1';
    if($config_values['Settings']['Default Feed All'] == 1)
        $favdefaultall = 'checked=1';

    switch($config_values['Settings']['MatchStyle']) {
        case 'glob': $matchglob="selected='selected'";break;
        case 'simple': $matchsimple="selected='selected'";break;
        case 'regexp': 
        default: $matchregexp="selected='selected'";break;
    }

    if(!($config_values['Settings']['FirstRun'])) {
        // Include the templates and append the results to html_out
        ob_start();
        require('templates/global_config.tpl');
        return ob_get_contents();
        ob_end_clean();
    }
}

function display_favorites_info($item, $key) {
    global $config_values, $html_out;
    $feed_options = '<option value="none">None</option>';
    $feed_options .= '<option value="all"';
    if (preg_match('/all/i', $item['Feed']) || $item['Name'] == "") {
        $feed_options .= ' selected="selected">All</option>';
    } else {
        $feed_options .= '>All</option>';
    }
    if(isset($config_values['Feeds'])) {
        foreach($config_values['Feeds'] as $feed) {
            $feed_options .= '<option value="'.urlencode($feed['Link']).'"';
            if($feed['Link'] == $item['Feed'] ) {
                $feed_options .= ' selected="selected"';
            } 
            $feed_options .= '>'.$feed['Name'].'</option>';
        }
    }

  // Dont handle with object buffer, is called inside display_favorites ob_start
  require('templates/favorites_info.tpl');
}

function display_favorites() {
    global $config_values, $html_out;

  ob_start();
  require('templates/favorites.tpl');
  return ob_get_contents();
  ob_end_clean();
}

function display_hidelist() {
    global $config_values, $html_out;

  ob_start();
  require('templates/hidelist.tpl');
  return ob_get_contents();
  ob_end_clean();
}

function display_feeds() {
    global $config_values, $html_out;
    
    ob_start();
    require('templates/feeds.tpl');
    return ob_get_contents();
    ob_end_clean();
}
    
function display_history() {
    global $html_out, $config_values;

    if(file_exists($config_values['Settings']['History'])) {
        $history = array_reverse(unserialize(file_get_contents($config_values['Settings']['History'])));
    } else {
        $history = array();
    }

    ob_start();
    require('templates/history.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function display_legend() {
    global $html_out;

    ob_start();
    require('templates/legend.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function display_transmission() {
    global $html_out;

    $host = get_tr_location();

    ob_start();
    require('templates/transmission.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function episode_info($title) {
	//Remove soft hyphens
	$title = str_replace("\xC2\xAD", "", $title);
    $episode_data = guess_match($title, true);
	
	if ( $episode_data===false ) {
		$isShow = false;
		$name = $title;
		$data = '';
	} else {
		$isShow = $episode_data['episode']=='noShow' ? false : true;
		$name = $episode_data['key'];
		$data = $episode_data['data'];
	}
	
	if ($isShow) {
		$episode_num = $episode_data['episode'];
		$show = TV_Shows::searchSingle($name);
		
		if ($show) {
			$temp = explode('x', $episode_num);
			$episode = $show->getEpisode($temp[0], $temp[1]);
			
			$name = $show->seriesName;
			$episode_name = $episode->name;
			$text = empty($episode->overview) ? $show->overview : $episode->overview;
			$image = empty($episode->filename)?'':cacheImage('http://thetvdb.com/banners/'.$episode->filename);
			$rating = $episode->rating;
			$actors = array();
			foreach ($episode->guestStars as $person_name) {
				$actors[] = $person_name;
			}
			foreach ($show->actors as $person_name) {
				$actors[] = $person_name;
			}
			$directors = array();
			foreach ($episode->directors as $person_name) {
				$directors[] = $person_name;
			}
			$writers = array();
			foreach ($episode->writers as $person_name) {
				$writers[] = $person_name;
			}
		}
	} else {
		$tmdb = new TMDb('fbfeef921665ac4649745ed210dd5baa');
		$movie = json_decode($tmdb->searchMovie($name));
		$movie = $movie[0];
		$name = $movie->original_name;
		$text = $movie->overview;
		$date = $movie->released;
		$rating = $movie->rating;
		$certification = $movie->certification;
		$image = "";
		if (is_array($movie->posters)) {
			foreach ($movie->posters as $poster) {
				if ($poster->image->size == 'cover') {
					$image = $poster->image->url;
				}
			}
		}
	}
	ob_start();
    require('templates/episode.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function cacheImage($url) {
	global $config_values;
	$path_parts = pathinfo($url);
	$filename = $path_parts['filename'] . "." . $path_parts['extension'];
	//TODO: Use non-harcoded cache path
	$img_url = 'rss_cache/'.$filename;
	$img_local = $config_values['Settings']['Cache Dir'] . $filename;
	if (!file_exists($img_local)) {
		$x =  file_put_contents($img_local, file_get_contents($url));
	}
	
	return $img_url;
}

function report_bug() {
    global $html_out;

    ob_start();
    require('templates/report_bug.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function display_clearCache() {
    global $html_out;

    ob_start();
    require('templates/clear_cache.tpl');
    return ob_get_contents();
    ob_end_clean();
}

function close_html() {
    global $html_out, $debug_output, $main_timer;
    echo $html_out;
    $html_out = "";
}

function check_requirements() {
    if(!(function_exists('json_encode'))) {
        echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">
            No json support found. Please make sure php is compiled with json support.<br> In some cases there is a package like php5-json that has to be installed.</div>";
        return 1;
    }
    if(!(function_exists('curl_init'))) {
        echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">
            No curl support found. Please make sure php5-curl is installed.</div>";
        return 1;
    }
}

function check_files() {
    global $config_values;

    $myuid = posix_getuid();
    $configDir = platform_getConfigDir() . '/';
    if(!is_writable($configDir)) {
    echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">Please create the directory $configDir and make sure it's readable and writeable for the user running the webserver (uid: $myuid). </div>";
    }
    $cwd = getcwd();
    if(!(get_base_dir() == $cwd)) {
        echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">Please edit the config.php file and set the basedir to:<br /> \"$cwd\".<br />Then click your browsers refresh button.</div>";
    return;
    }
    

    if($config_values['Settings']['FirstRun']) return 0;

    $toCheck['cache_dir'] = $config_values['Settings']['Cache Dir'];
    if(strtolower($config_values['Settings']['Transmission Host']) == 'localhost' ||
         $config_values['Settings']['Transmission Host'] == '127.0.0.1') {
            $toCheck['download_dir'] = $config_values['Settings']['Download Dir'];
    }
    
    $deepDir = $config_values['Settings']['Deep Directories'];
    
    $error = false;
    foreach ($toCheck as $key => $file) {
        if(!(file_exists($file))) $error .= "$key:&nbsp;<i>\"$file\"</i>&nbsp;&nbsp;does not exist <br />";
        if(!($deepDir) && $key == 'download_dir') break;
        if(!(is_writable($file))) $error .= "$key:&nbsp;<i>\"$file\"</i>&nbsp;&nbsp;is not writable for uid: $myuid <br />";
        if(!(is_readable($file))) $error .= "$key:&nbsp;<i>\"$file\"</i>&nbsp;&nbsp;is not readable for uid: $myuid <br />";
    }
    
    if($error) {
        echo "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">$error</div>";
    }
}

function version_check() {
    global $tw_version;
    if(!isset($_COOKIE['VERSION-CHECK'])) {
        $get = curl_init();
        $getOptions[CURLOPT_URL] = 'http://tw-version.vandalon.net/VERSION';
        $getOptions[CURLOPT_USERAGENT] = "TW-X/$tw_version[0] ($tw_version[1])";
        get_curl_defaults($getOptions);
        curl_setopt_array($get, $getOptions);
        $latest = curl_exec($get);
        curl_close($get);
    	$version = (int)str_replace('.', '', $tw_version[0]);
    	$tmplatest = (int)str_replace('.', '', $latest);
        if($tmplatest && $tmplatest > $version) {
            return "<div id=\"newVersion\" class=\"dialog_window\" style=\"display: block\">TorrentWatch-X $latest is available.
                   Click <a href=\"https://code.google.com/p/torrentwatch-x/\">here</a> for more information.</div>";
        }
    }
}

function post_bug($Summary, $Name, $Email, $Priority, $Description) {
    global $tw_version;
    $Version = "TW-X/$tw_version[0] ($tw_version[1])";
    
    $post = curl_init();
    $postOptions[CURLOPT_URL] = "http://tw-issues.vandalon.net/";
    $postOptions[CURLOPT_USERAGENT] = "TW-X/$tw_version[0] ($tw_version[1])";
    $postOptions[CURLOPT_POSTFIELDS] = "Summary=$Summary&Name=$Name&Email=$Email&Priority=$Priority&Description=$Description&Version=$Version";
    get_curl_defaults($postOptions);
    curl_setopt_array($post, $postOptions);
    $response = curl_exec($post);
    $http_code = curl_getinfo($post, CURLINFO_HTTP_CODE);
    curl_close($post);
    if($http_code && $http_code != 200) $response = "Error: $http_code <br> $response";
    return "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">$response</div>";
    
}

function get_tr_location() {
    global $config_values;
    $host = $config_values['Settings']['Transmission Host'];
    if(preg_match('/(localhost|127.0.0.1)/', $host))
        $host = preg_replace('/:.*/', "", $_SERVER['HTTP_HOST']);
    if(preg_match('/(localhost|127.0.0.1)/', $host))
        $host = preg_replace('/:.*/', "", $_SERVER['SERVER_NAME']);
    $host = $host . ':' . $config_values['Settings']['Transmission Port'] . "/transmission/web/";
    return $host;
}

function get_client() {
    global $config_values;
    echo "<div id='clientId' class='hidden'>";
    echo $config_values['Settings']['Client'];
    echo "</div>";
}

//
//
// MAIN Function
//
//
$main_timer = timer_init();
platform_initialize();
setup_default_config();
if(file_exists(platform_getConfigFile()))
    read_config_file();

$config_values['Global']['HTMLOutput'] = 1;
$html_out = "";
$debug_output = "Torrentwatch Debug:";
$verbosity = 0;

parse_options();
if(check_requirements()) return;
check_files();

echo $html_out;
$html_out = "";
flush();

// Feeds
if(isset($config_values['Feeds'])) {
    load_feeds($config_values['Feeds']);
    feeds_perform_matching($config_values['Feeds']);
}
get_client();
close_html();

$footer = "<div id=\"footer\">TorrentWatch-X version $tw_version[0]";
if($tw_version[1]) $footer.= " build $tw_version[1]";
echo "$footer</div>";

if (!$config_values['Settings']['Hide Donate Button']) {
    echo '<div id="donate">
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
	">
	<input type="image" src="images/paypal-icon.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
	</form>
    </div>';
}

unlink_temp_files();
exit(0);

?>

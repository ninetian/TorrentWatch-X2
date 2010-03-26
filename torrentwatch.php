<?php
// disable any kind of caching
header( "Expires: Mon, 20 Dec 2000 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

ini_set('include_path', '.:./php');
require_once('rss_dl_utils.php');
global $platform;

$tw_version[0] = "0.6.2";

if(file_exists(get_base_dir() . "/.hg")) {
    exec('hg id -i', $hgId, $return);
    if($return == 0) {
        $tw_version[1] = $hgId[0];
    } else if($platform = 'NMT') {
        $tw_version[1] = 'NMT';
    } else {
        $tw_version[1] = "unknown";
    }
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
    if(empty($commands))
        return FALSE;

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
        case 'getHash':
            $response = torInfo($_REQUEST['getHash']);
            echo json_encode($response);
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
            $response = add_hidden($_GET['hide']);
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
            if(!$downloadDir || $downloadDir == "Default" ) $downloadDir = $config_values['Settings']['Download Dir'];
            $r = client_add_torrent(trim($_GET['link']),
                $downloadDir, $_GET['title'], $_GET['feed']);
            if($r) { 
                $torHash = get_torHash(add_cache($_GET['title'])); 
            }
            echo $torHash;
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
        case 'get_tr_location':
            global $config_values;
            echo $config_values['Settings']['Transmission Host'] . ':' . $config_values['Settings']['Transmission Port'];
            exit;
        case 'get_client':
            global $config_values;
            echo $config_values['Settings']['Client'];
            exit;
        case 'version_check':
            echo version_check();
            exit;
        case 'show_footer':
            global $tw_version;
            $footer = "<div id=\"footer\">TorrentWatch-X version $tw_version[0]";
            if($tw_version[1]) $footer.= " build $tw_version[1]</div>";
            echo $footer;
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
    $deepfull=$deeptitle=$deepoff=$verifyepisode="";
    $matchregexp=$matchglob=$matchsimple=$dishidelist=$mailonhit="";
    $favdefaultall=$onlynewer=$folderclient=$combinefeeds=$require_epi_info="";

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
    if($config_values['Settings']['Save Torrents'] == 1)
        $savetorrent = 'checked=1';
    if($config_values['Settings']['Email Notifications'] == 1)
        $mailonhit ='checked=1';

    switch($config_values['Settings']['Deep Directories']) {
        case 'Full': $deepfull = 'selected="selected"';break;
        case 'Title': $deeptitle = 'selected="selected"'; break;
        default:$deepoff = 'selected="selected"';break;
    }

    if($config_values['Settings']['Verify Episode'] == 1)
        $verifyepisode = 'checked=1';
    if($config_values['Settings']['Only Newer'] == 1)
        $onlynewer = 'checked=1';
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

    $toCheck = array('cache_dir' => $config_values['Settings']['Cache Dir'],
                    'download_dir' => $config_values['Settings']['Download Dir'],
                    );
                    
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
        get_curl_defaults(&$getOptions);
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
    get_curl_defaults(&$postOptions);
    curl_setopt_array($post, $postOptions);
    $response = curl_exec($post);
    $http_code = curl_getinfo($post, CURLINFO_HTTP_CODE);
    curl_close($post);
    if($http_code != 200) $response = "Error: $http_code <br> $response";
    return "<div id=\"errorDialog\" class=\"dialog_window\" style=\"display: block\">$response</div>";
    
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
ob_flush();flush();

// Feeds
if(isset($config_values['Feeds'])) {
    load_feeds($config_values['Feeds']);
    feeds_perform_matching($config_values['Feeds']);
}

close_html();
unlink_temp_files();
exit(0);

?>

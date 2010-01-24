<?php
 function human_readable($n) {
 $scales = Array('bytes', 'KB', 'MB', 'GB', 'TB');
 $scale = $scales[0];
 for ($i=1; $i < count($scales); $i++)
 {
   if ($n / 1024 < 1) break;
   $n = $n / 1024;
   $scale =  $scales[$i];
 }
 return round($n,2) . " $scale";
}

function get_torrent_link($rs) {
  if(isset($rs['link'])) {
      $link = $rs['link'];
  } else if(isset($rs['id']) || stristr($rs['id'], 'http://')) { // Atom
    $link = $rs['id'];
  } else if(isset($rs['enclosure'])) { // RSS Enclosure
    $link = $rs['enclosure']['url'];
  } 

  if(strpos($link, 'newzleech.com') !== False) {
    // Special handling for newzleech
    $tmp = explode('=', $link);
    $link='http://newzleech.com/?m=gen&dl=1&post='.$tmp[1];
  }
  return html_entity_decode($link);
}

function episode_filter($item, $filter) {
  $filter = preg_replace('/\s/', '', $filter);

  list($itemS, $itemE) = explode('x', $item['episode']);

  if(preg_match('/^S\d*/i', $filter)) {
    $filter = preg_replace('/S/i', '', $filter);
      if(preg_match('/^\d*E\d*/i', $filter)) {
            $filter = preg_replace('/E/i', 'x', $filter);
    }
  }
  // Split the filter(ex. 3x4-4x15 into 3,3 4,15).  @ to suppress error when no seccond item
  list($start, $stop) = explode('-',  $filter, 2);
  @list($startSeason,$startEpisode) = explode('x', $start, 2);
  if(!($stop)) { $stop = "9999x9999"; }
  @list($stopSeason,$stopEpisode) = explode('x', $stop, 2);

  if(!($item['episode'])) {
    return False;
  }

 // Perform episode filter
  if(empty($filter)) {
    return true; // no filter, accept all    
  }

  // the following reg accepts the 1x1-2x27, 1-2x27, 1-3 or just 1
  $validateReg = '([0-9]+)(?:x([0-9]+))?';
  if(preg_match("/\dx\d-\dx\d/", $filter)) { 
   if(preg_match("/^{$validateReg}-{$validateReg}/", $filter) === 0) {
     _debug('bad episode filter: '.$filter);
     return True; // bad filter, just accept all
   } else if(preg_match("/^{$validateReg}/", $filter) === 0) {
     _debug('bad episode filter: '.$filter);
     return True; // bad filter, just accept all
   } 
  }

  if(!($stopSeason))
    $stopSeason = $startSeason;
  if(!($startEpisode))
    $startEpisode = 1;
  if(!($stopEpisode))
    $stopEpisode = $startEpisode-1;


  $startEpisodeLen=strlen($startEpisode);
  if($startEpisodeLen == 1) { $startEpisode = "0$startEpisode" ;}; 
  $stopEpisodeLen=strlen($stopEpisode);
  if($stopEpisodeLen == 1) { $stopEpisode = "0$stopEpisode" ;}; 
  
  if(!preg_match('/^\d\d$/', $startSeason)) $startSeason = 0 . $startSeason;
  if(!preg_match('/^\d\d$/', $startEpisode)) $startEpisode = 0 . $startEpisode;
  if(!preg_match('/^\d\d$/', $stopSeason)) $stopSeason = 0 . $stopSeason;
  if(!preg_match('/^\d\d$/', $stopEpisode)) $stopEpisode = 0 . $stopEpisode;
  if(!preg_match('/^\d\d$/', $itemS)) $itemS = 0 . $itemS;
  if(!preg_match('/^\d\d$/', $itemE)) $itemE = 0 . $itemE;
  

  // Season filter mis-match
  if(!("$itemS$itemE" >= "$startSeason$startEpisode" && "$itemS$itemE" <= "$stopSeason$stopEpisode")) {
    return False;
  }
  _debug("$itemS$itemE $startSeason$startEpisode - $itemS$itemE $stopSeason$stopEpisode\n");
  return True;
}

function check_for_torrent(&$item, $key, $opts) {
  global $matched, $test_run, $config_values;

  if(!(strtolower($item['Feed']) == 'all' || $item['Feed'] === '' || $item['Feed'] == $opts['URL'])) {
    return;
  }

  $rs = $opts['Obj'];
  $title = strtolower($rs['title']);
  switch(_isset($config_values['Settings'], 'MatchStyle')) {
    case 'simple':  
      $hit = (($item['Filter'] != '' && strpos(strtr($title, " .", "__") , strtr(strtolower($item['Filter']), " .", "__")) === 0) &&
       ($item['Not'] == '' OR my_strpos($title, strtolower($item['Not'])) === FALSE) &&
       ($item['Quality'] == 'All' OR $item['Quality'] == '' OR my_strpos($title, strtolower($item['Quality'])) !== FALSE));
      break;
    case 'glob':
      $hit = (($item['Filter'] != '' && fnmatch(strtolower($item['Filter']), $title)) &&
       ($item['Not'] == '' OR !fnmatch(strtolower($item['Not']), $title)) &&
       ($item['Quality'] == 'All' OR $item['Quality'] == '' OR strpos($title, strtolower($item['Quality'])) !== FALSE));
      break;
    case 'regexp':
    default:
      $hit = (($item['Filter'] != '' && preg_match('/'.strtolower($item['Filter']).'/', $title)) &&
       ($item['Not'] == '' OR !preg_match('/'.strtolower($item['Not']).'/', $title)) &&
       ($item['Quality'] == 'All' OR $item['Quality'] == '' OR preg_match('/'.strtolower($item['Quality']).'/', $title)));
      break;
  }
  if($hit)
    $guess = guess_match($title, TRUE);
   
  if($hit && episode_filter($guess, $item['Episodes']) == true) {
    $matched = 'match';
    if(check_cache($rs['title'])) {
      if(_isset($config_values['Settings'], 'Only Newer') == 1) {
        if(!empty($guess['episode']) && preg_match('/(\d+)x(\d+)/i',$guess['episode'],$regs)) {
          if($item['Season'] > $regs[1]) {
            _debug($item['Season'] .' > '.$regs[1] . "; ", 1);
            $matched = "old";
            return FALSE;
          } else if($item['Season'] == $regs[1] && $item['Episode'] >= $regs[2] && (!(preg_match('/proper|repack/i', $rs['title'])))) {
            _debug($item['Episode'] .' >= '.$regs[2] . "; ", 1);
            $matched = "old";
            return FALSE;
          }
        } else if($guess['episode'] == 'fullSeason'){
            $matched = "season";
            return FALSE;
        } else if($guess['episode'] != 'noShow') {
            $matched = "nomatch";
            return FALSE;
        }
      }
      _debug('Match found for '.$rs['title']."\n");
      if($test_run) {
        $matched = 'test';
        return;
      }
      if($link = get_torrent_link($rs)) {
        if(client_add_torrent($link, NULL, $rs['title'], $opts['URL'], $item)) {
            add_cache($rs['title']);
        } else {
            _debug("Failed adding torrent $link\n", -1);
            return FALSE;
        }

      } else {                     
        _debug("Unable to find URL for ".$rs['title']."\n", -1);
        $matched = "nourl";
      }
    }
  }
}

function parse_one_rss($feed) {
  global $config_values;
  $rss = new lastRSS;
  $rss->stripHTML = True;
  $rss->CDATA = content; 
  $rss->cache_time = (15*60)-20;
  $rss->date_format = 'M d, H:i';

  if(isset($config_values['Settings']['Cache Dir']))
    $rss->cache_dir = $config_values['Settings']['Cache Dir'];
  if(!$config_values['Global']['Feeds'][$feed['Link']] = $rss->get($feed['Link']))
    _debug("Error creating rss parser for ".$feed['Link']."\n",-1);
  else {
    if($config_values['Global']['Feeds'][$feed['Link']]['items_count'] == 0) {
      unset($config_values['Global']['Feeds'][$feed['Link']]);
      return False;
    }
    $config_values['Global']['Feeds'][$feed['Link']]['URL'] = $feed['Link'];
    $config_values['Global']['Feeds'][$feed['Link']]['Feed Type'] = 'RSS';
  }
  return;
}

function parse_one_atom($feed) {
  global $config_values;
  if(isset($config_values['Settings']['Cache Dir']))
    $atom_parser = new myAtomParser($feed['Link'], $config_values['Settings']['Cache Dir']);
  else
    $atom_parser = new myAtomParser($feed['Link']);

  if(!$config_values['Global']['Feeds'][$feed['Link']] = $atom_parser->getRawOutput())
    _debug("Error creating atom parser for ".$feed['Link']."\n",-1);
  else {
    $config_values['Global']['Feeds'][$feed['Link']]['URL'] = $feed['Link'];
    $config_values['Global']['Feeds'][$feed['Link']]['Feed Type'] = 'Atom';
  }
  return;
}

function get_torHash($cache_file) {
  $handle = fopen($cache_file, "r");
  if(filesize($cache_file)) {
    $torHash = fread($handle, filesize($cache_file));
    return $torHash;
  }
}

function rss_perform_matching($rs, $idx, $feedName) {
  global $config_values, $matched;

  if(count($rs['items']) == 0)
    return;
  $percPerFeed = 80/count($config_values['Feeds']);
  $percPerItem = $percPerFeed/count($rs['items']);
  if(isset($config_values['Global']['HTMLOutput']) && $config_values['Settings']['Combine Feeds'] == 0) {
    show_feed_html($idx);
  }
  $alt = 'alt';
  foreach($rs['items'] as $item) {
    if($filter = get_item_filter()) $item['title'] = preg_replace($filter, '', $item['title']);
    if(preg_match('/\b(720p|1080p|1080i)\b/i', $item['title'])) {
        $item['title'] = preg_replace('/( -)?[_. ]HDTV/', '', $item['title']);
    }
    $percentage = '';
    $torHash = '';
    $matched = "nomatch";
    if(isset($config_values['Favorites'])) {
      array_walk($config_values['Favorites'], 'check_for_torrent', 
                 array('Obj' => $item, 'URL' => $rs['URL']));
    }
    _debug("$matched: $item[title]\n", 1);
    $client = $config_values['Settings']['Client'];
    $cache_file = $config_values['Settings']['Cache Dir'].'rss_dl_'.filename_encode($item['title']);
    if(file_exists($cache_file)) {
      $torHash = get_torHash($cache_file);
      if($matched != "match" && $matched != 'cachehit' && file_exists($cache_file)) {
          $matched = 'downloaded';
          _debug("matched: " . $item . "\n", 1);
      }
    }
    if(isset($config_values['Global']['HTMLOutput'])) {
      if(!($rsnr)) { $rsnr = 1; } else { $rsnr ++; };
      if(strlen($rsnr) <= 1) $rsnr = 0 . $rsnr;
      $id = $idx . $rsnr;
      show_torrent_html($item, $rs['URL'], $feedName, $alt, $torHash, $matched, $id);
    }
    
    if($alt=='alt') {
      $alt='';
    } else {
      $alt='alt';
    }
  }
  
  if(isset($config_values['Global']['HTMLOutput']) && $config_values['Settings']['Combine Feeds'] == 0) {
    close_feed_html($idx, 0);
  }
  unset($item);
}

function atom_perform_matching($atom, $idx, $feedName) {
  global $config_values, $matched;
  
  $atom  = array_change_key_case_ext($atom, ARRAY_KEY_LOWERCASE);
  if(count($atom['feed']) == 0)
    return;
  
  $percentage = '';
  $torHash = '';
  $matched = "nomatch";
  
  if(isset($config_values['Global']['HTMLOutput']) && $config_values['Settings']['Combine Feeds'] == 0) {
    show_feed_html($idx);
  }
  $alt='alt';
  
  foreach($atom['feed']['entry'] as $item) {
    if($filter = get_item_filter()) $item['title'] = preg_replace($filter, '', $item['title']);
    if(preg_match('/\b(720p|1080p|1080i)\b/i', $item['title'])) {
        $item['title'] = preg_replace('/( -)?[_. ]HDTV/', '', $item['title']);
    }
    $matched = "nomatch";
    array_walk($config_values['Favorites'], 'check_for_torrent', 
               array('Obj' =>$item, 'URL' => $atom['URL']));
    if($matched == "nomatch") {
      _debug("No match for ".$item['title']."\n");
    }
    $client = $config_values['Settings']['Client'];
    $cache_file = $config_values['Settings']['Cache Dir'].'rss_dl_'.filename_encode($item['title']);
    if(file_exists($cache_file)) {
      $torHash = get_torHash($cache_file);
      if($matched != "match" && $matched != 'cachehit' && file_exists($cache_file)) {
          $matched = 'downloaded';
          _debug("matched: " . $item . "\n", 1);
      }
    }
    if(isset($config_values['Global']['HTMLOutput'])) {
     if(!($rsnr)) { $rsnr = 1; } else { $rsnr ++; };
     if(strlen($rsnr) <= 1) $rsnr = 0 . $rsnr;
     $id = $idx . $rsnr;
     show_torrent_html($item, $atom['feed']['link'], $feedName, $alt, $torHash, $matched, $id);
    }

    if($alt=='alt') {
      $alt='';
    } else {
        $alt='alt';
    }
    unset($item);
  }
}


function feeds_perform_matching($feeds) {
  global $config_values;
  
  if(isset($config_values['Global']['HTMLOutput'])) {
    echo('<div class="progressBarUpdates">');
    setup_rss_list_html();
  }
  
  if(isset($config_values['Global']['HTMLOutput']) && $config_values['Settings']['Combine Feeds'] == 1) {
    show_feed_html($rs, combined);
  }
  
  cache_setup();
  foreach($feeds as $key => $feed) {
    switch($feed['Type']) {
      case 'RSS':
        rss_perform_matching($config_values['Global']['Feeds'][$feed['Link']], $key, $feed['Name']);
        break;
      case 'Atom':
        atom_perform_matching($config_values['Global']['Feeds'][$feed['Link']], $key, $feed['Name']);
        break;
      default:
        _debug("Unknown Feed. Feed: ".$feed['Link']."Type: ".$feed['Type']."\n",-1);
        break;
    }
  }
  
  if(isset($config_values['Global']['HTMLOutput']) && $config_values['Settings']['Combine Feeds'] == 1) {
    close_feed_html();
  }
  

  if($config_values['Settings']['Client'] == "Transmission") {
    show_transmission_div();
  }

  if(isset($config_values['Global']['HTMLOutput'])) {
    echo('</div>');
    finish_rss_list_html();
  }
}

function load_feeds($feeds) {
  global $config_values;
  $count = count($feeds);
  foreach($feeds as $feed) {
    switch($feed['Type']){
      case 'RSS':
        parse_one_rss($feed);
        break;
      case 'Atom':
        parse_one_atom($feed);
        break;
      default:
        _debug("Unknown Feed. Feed: ".$feed['Link']."Type: ".$feed['Type']."\n",-1);
        break;
    }
  }
}

?>

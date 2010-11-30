<?php
if(isset($item['description'])) {
    $description = $item['description'];
} else {
    $description = '';
}
if(isset($item['pubDate'])) {
    $pubDate = $item['pubDate'];
    $unixTime = strtotime($item['pubDate']);
} else {
    $pubDate = '';
}
if(!($torHash)) $torHash = '###torHash###';

if($config_values['Settings']['Combine Feeds'] == 1) {
    $feedItem = "<span class=\"feed_name\">$feedName - </span>";
    $combined = "combined";
}

if(isset($torInfo)) {
    $stats = $torInfo['stats'];
    $clientId = $torInfo['clientId'];
    $infoDiv = "<div id='tor_$id' class='torInfo tor_$torHash'>$stats</div>";
    $etaDiv = "<div class='torEta'>$eta</div>";
    if($torInfo['status'] == 4) $matched = "downloading";
} else if((!$config_values['Settings']['Disable Hide List']) && ($matched == "nomatch"))  {
    $hideTD = "<td class='hideTD'><span class=\"hide_item\"><a href=\"#\"
    title=\"Hide this show from the list\" onclick='$.hideItem(\"$utitle\")'>
    <img src=\"images/hide.png\" /></a></span></td>";
}

if($config_values['Settings']['Client'] != 'folder') $progressBar = "<div class='progressBarContainer init'><div class='progressDiv' style='width: 0.07%; height: 3px; '></div></div>";

if($matched == "downloading" || $matched == "downloaded" || $matched == "cachehit" || $matched == "match" ||  $torInfo['dlStatus'] == "to_check") { 
  $hidden = ""; 
  $dlTorrent = "dlTorrent hidden";
  if ($torInfo['status'] == 16) {
    $torStart = "torStart";
    $torStop= "torStop hidden";
  } else {
    $torStart = "torStart hidden";
    $torStop= "torStop";
  }
} else {
  $dlTorrent = "dlTorrent";
  $torStart = "torStart hidden";
  $torStop = "torStop hidden";
  $hidden = "hidden";
} 

if(!isset($infoDiv)) $infoDiv = '';
if(!isset($etaDiv)) $etaDiv = '';
if(!isset($feedItem)) $feedItem = '';
if(!isset($torInfo)) $torInfo = '';
if(!isset($unixTime)) $unixTime = '';
if(!isset($hideTD)) $hideTD = "<td class='hideTD'></td>";
if(!isset($pubDateClass)) $pubDateClass = '';

print <<< EOH

<li id=id_$id name=$id class="torrent match_$matched $alt item_$torHash" title="$description">
<table width="100%" cellspacing="0"><tr>

$hideTD
<td class="torrent_name">
  <div class='torrent_name' onclick='$("#divContext_$id").slideToggle("fast");'>$title</div>
  <div class='torrent_pubDate'>$feedItem $pubDate</div>
  $progressBar
  $infoDiv
  $etaDiv
  <span class='hidden' id=unixTime>$unixTime</span>
</td></tr></table>
<div id="divContext_$id" class="contextMenu">

<div><p class='favorite'>
<a href="#" title="Add this show to favorites" onclick='javascript:$.addFavorite("torrentwatch.php?matchTitle=1&rss=$feed&title=$utitle")'>
<img height=15 src="images/tor_fav.png">Add to favorites</a></p></div>

<div><p class='$dlTorrent'>
<a href="#" title="Download this torrent" onclick='javascript:$.dlTorrent("torrentwatch.php?dlTorrent=1&title=$utitle&link=$ulink&feed=$feed","$id")'>
<img height=15 src="images/tor_start.png">Download</a></p></div>

<div><p class="activeTorrent $torStart">
<a href="#" title="Resume download" onclick='javascript:$.stopStartTorrent("start", "$torHash")'>
<img height=15 src="images/tor_start.png">Resume</a></p></div>

<div><p class="activeTorrent $torStop">
<a href="#" title="Pause download" onclick='javascript:$.stopStartTorrent("stop", "$torHash")'>
<img height=15 src="images/tor_pause.png">Pause</a></p></div>

<div><p class="activeTorrent delete $hidden">
<a href="#" title="Delete torrent but keep data" onclick='javascript:$.delTorrent("$torHash", "false")'>
<img height=15 src="images/tor_stop.png">Delete</a></p></div>

<div><p class="activeTorrent trash $hidden">
<a href="#" title="Delete torrent and its data" onclick='javascript:$.delTorrent("$torHash", "true")'>
<img height=15 src="images/tor_trash.png">Delete data</a></p></div>

<div><p class="episodeInfo">
<a href="#" title="Delete torrent and its data" onclick='javascript:$.episodeInfo("$utitle")'>
<img height=15 src="images/tv_icon.png">Episode Info</a></p></div>
</div>
</li>
EOH;
?>

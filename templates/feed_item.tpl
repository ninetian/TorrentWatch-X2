<?php
if($item['description']) $description = $item['description'];
if($item['pubDate']) {
    $pubDate = $item['pubDate'];
    $unixTime = strtotime($item['pubDate']);
}
if(!($torHash)) $torHash = '###torHash###';

if($config_values['Settings']['Combine Feeds'] == 1) {
    $feedItem = "<p class=\"feed_name\">$feedName</p>";
}

if(isset($torInfo)) {
  $stats = $torInfo['stats'];
  $clientId = $torInfo['clientId'];
  $infoDiv = "<div id='tor_$id' class='torInfo tor_$torHash'>$stats</div>";
  if($torInfo['status'] == 4) $matched = "downloading";
} else if(!$config_values['Settings']['Disable Hide List']) {
    $hideSpan = "<span class=\"hide_item\"><a href=\"#\" 
    title=\"Hide this show from the list\" onclick='$.hideItem(\"$utitle\")'>
    <img src=\"images/hide.png\" /></a></span>";
}

if($matched == "downloading" || $matched == "downloaded" || $matched == "cachehit" || $matched == "match") { 
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
  $hidden = "hidden";
  $dlTorrent = "dlTorrent";
  $torStart = "torStart hidden";
  $torStop = "torStop hidden";
} 

if(!isset($infoDiv)) $infoDiv = '';
if(!isset($feedItem)) $feedItem = '';
if(!isset($torInfo)) $torInfo = '';
if(!isset($unixTime)) $unixTime = '';
if(!isset($pubDate)) $pubDate = '';
if(!isset($hideSpan)) $hideSpan = '';

print <<< EOH

<li id=$id name=$id class="torrent match_$matched $alt $torHash" title="$description">
<table width="100%" cellspacing="0"><tr><td class="buttons left match_$matched">

<p class='$dlTorrent'>
<a href="#" title="Download this torrent" onclick='javascript:$.dlTorrent("torrentwatch.php?dlTorrent=1&title=$utitle&link=$ulink&feed=$feed","$id")'>
<img height=10 src="images/tor_start.png"></a></p>

<p class="activeTorrent $torStart">
<a href="#" title="Resume download" onclick='javascript:$.stopStartTorrent("start", "$torHash")'>
<img height=10 src="images/tor_start.png"></a></p>

<p class="activeTorrent $torStop">
<a href="#" title="Pause download" onclick='javascript:$.stopStartTorrent("stop", "$torHash")'>
<img height=10 src="images/tor_pause.png"></a></p>

<p class="activeTorrent delete $hidden">
<a href="#" title="Delete torrent but keep data" onclick='javascript:$.delTorrent("$torHash", "false")'>
<img height=10 src="images/tor_stop.png"></a></p>

</td><td class="buttons right match_$matched ">

<p class='favorite'>
<a href="#" title="Add this show to favorites" onclick='javascript:$.addFavorite("torrentwatch.php?matchTitle=1&rss=$feed&title=$utitle")'>
<img height=10 src="images/tor_fav.png"></a></p>

<p class="activeTorrent trash $hidden">
<a href="#" title="Delete torrent and its data" onclick='javascript:$.delTorrent("$torHash", "true")'>
<img height=10 src="images/tor_trash.png"></a></p>


</td><td class="torrent_name">
$hideSpan
<span class='torrent_name'>$title</span>

$infoDiv
</td><td class='feed_info'>
<p class='torrent_pubDate'>$pubDate</p>
<span class='hidden' id=unixTime>$unixTime</span>
$feedItem
</td></tr></table></li>

EOH;
?>

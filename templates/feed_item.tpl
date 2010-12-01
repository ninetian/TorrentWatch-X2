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
    $infoDiv = "<span id='tor_$id' class='torInfo tor_$torHash'>$stats</span>";
    $etaDiv = "<span class='torEta'>$eta</span>";
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

<li id=id_$id name=$id class="torrent match_$matched $alt item_$torHash">

<table width="100%" cellspacing="0">
 <tr>
  <td class="identifier"></td>
  <td class="torrent_name">
   <div class='torrent_name'>
	<a id="contextButton_$id" class="contextButton" onclick='$.toggleContextMenu("#divContext_$id", "$id");'></a>
	<span title="$description">$title</span>
   </div>
   <div id="divContext_$id" class="contextMenu">
	<div><p class='favorite' onclick='javascript:$.addFavorite("torrentwatch.php?matchTitle=1&rss=$feed&title=$utitle")' title="Add this show to favorites">Add to favorites</p></div>
	<div><p class='$dlTorrent' onclick='javascript:$.dlTorrent("torrentwatch.php?dlTorrent=1&title=$utitle&link=$ulink&feed=$feed","$id")' title="Download this torrent">Download</p></div>
	<div><p class="activeTorrent $torStart" onclick='javascript:$.stopStartTorrent("start", "$torHash")' title="Resume download">Resume transfer</p></div>
	<div><p class="activeTorrent $torStop" onclick='javascript:$.stopStartTorrent("stop", "$torHash")' title="Pause download">Pause transfer</p></div>
	<div><p class="activeTorrent delete $hidden" onclick='javascript:$.delTorrent("$torHash", "false")' title="Delete torrent but keep data">Remove from client</p></div>
	<div><p class="activeTorrent trash $hidden" onclick='javascript:$.delTorrent("$torHash", "true")' title="Delete torrent and its data">Remove & Trash data</p></div>
	<div><p class="episodeInfo" onclick='javascript:$.episodeInfo("$utitle")' title="Delete torrent and its data">Episode Info</p></div>
   </div>
   <div class='torrent_pubDate'>$feedItem $pubDate</div>
   $progressBar
   $infoDiv
   $etaDiv
   <span class='hidden' id=unixTime>$unixTime</span>
  </td>
 </tr>
</table>

</li>
EOH;
?>

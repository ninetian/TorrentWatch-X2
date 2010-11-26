<div id="episodeDialog" class="dialog">
 <div class="title">
    <a class="toggleDialog button titleClose" href="#"></a>
    <?=$title?>
 </div>
  <div class="dialog_window" id="show_episode">
	<? if($isShow):?>
    <h1><?=$name?>: <?=$episode_name?></h1>
	<table><tr>
	<?if(!empty($image)):?>
	<td width="350">
	<img style="margin-right:5px;margin-bottom:5px;" width="350" src="<?=$image?>" />
	</td>
	<?endif;?>
	<td valign="top">
	<strong>Episode:</strong> <?=$episode_num?>
	<br />
	<?if(!empty($directors)):?>
	<strong>Director(s):</strong> <?=implode(', ', $directors)?>
	<br />
	<? endif; ?>
	<?if(!empty($writers)):?>
	<strong>Writer(s):</strong> <?=implode(', ', $writers)?>
	<br />
	<? endif; ?>
	<?if(!empty($actors)):?>
	<strong>Actors:</strong> <?=implode(', ', $actors)?>
	<br /><br />
	<? endif; ?>
	</td>
	</tr></table>
	<?if(!empty($text)):?>
	<span class="text"><span class="firstletter"><?=substr($text, 0, 1)?></span><?=substr($text, 1)?></span>
	<br />
	<?endif;?>
	<? else: ?>
	<h1><?=$name?></h1>
	<strong>Released:</string> <?=$date?>. <strong>Rating: <?=$rating?>. <strong>Certification:</string> <?=$certification?>
	<br /><br />
	<?if(!empty($image)):?>
	<img style="float:left;margin-right:5px;margin-bottom:5px;" width="350" src="<?=$image?>" />
	<?endif;?>
	<?if(!empty($text)):?>
	<span class="text"><span class="firstletter"><?=substr($text, 0, 1)?></span><?=substr($text, 1)?></span>
	<br />
	<?endif;?>
	<?endif;?>
    <a class="toggleDialog button close" href="#">Close</a>
  </div>
</div>

<?php

foreach ($pla->logArray as $queueId => $message){
	?><div class="queue"><b><?=$queueId ?> - <?=$message["process"]?></b><br/><?php

	echo $message["time"]."<br/>".$message["message"]."<br/>\n";

	?></div><?php
}


//Статистическая инфа
?>
<div>Total: <?=count($pla->logArray) ?></div>
<div>Limit: <?=$pla->filter["limit"] ?></div>
<div>Log period: <?=date("d.M H:i",$pla->startPeriod)?> - <?=date("d.M H:i",$pla->endPeriod)?></div>
<div><?php printf("LogFile Size: %.2f Kb",filesize($pla->pathToLog)/1024); ?></div>
<div><?php printf('Time %.3f s',microtime(true)-$starttime);?></div>
<div>Memory usage: <?=convert(memory_get_usage(true)) ?></div>
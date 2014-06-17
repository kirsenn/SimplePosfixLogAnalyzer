<?php
/**
 * Simple Postfix Log Analyzer
 * Created Jun 2014
 * @author kirSeNN (kirsenn@ya.ru)
 */
$starttime = microtime(true);
include 'PostfixLogAnalyzer/PostfixLogAnalyzer.php';

$pla = new \PostfixLogAnalyzer();
$pla->setUserFilters($_GET);

include 'PostfixLogAnalyzer/view/header.php';

$pla->parse();
if(count($pla->errors)===0){
	include 'PostfixLogAnalyzer/view/result.php';
}
else{
	foreach($pla->errors as $errorText){
		?><b><?=$errorText?></b><?php
	}
}

include 'PostfixLogAnalyzer/view/footer.php';
<?php
/**
* Simple Postfix loganalyzer
* Created 07 Apr 2011
* Fixed 20.02.2014
* @author kirSeNN (kirsenn@ya.ru)
*/

$starttime = microtime(true);
$start=0;  //Счетчик записей
$ndstatus = "all";
$ndmailaddr = "";
$ndqueueid = "";

##################
#SETTINGS
	//Путь до лога
	$default_log = "D:/xampp/htdocs/mail.log";
	// Максимум записей
	$default_limit = 200;
#END SETTINGS
##################

if(isset($_GET["limit"]))
	$end = $_GET["limit"];
else
	$end = $default_limit;

if(isset($_GET["logfilename"]))
	$logfilename = $_GET["logfilename"];
else
	$logfilename = $default_log;

if(isset($_GET["queue"])) $ndqueueid = $_GET["queue"];
if(isset($_GET["ndmailaddr"])) $ndmailaddr = $_GET["ndmailaddr"];
if(isset($_GET["status"])) $ndstatus = $_GET["status"];

//Лимитируем кол-во итераций если нужно вывести все записи
$readlimit = false;
if($ndstatus!=="errors" && $ndmailaddr=="")	$readlimit = true;

$first=true;

//Временные ограничения
$monthfrom = date("m");
$monthto = date("m");
$dayfrom = date("d");
$dayto = date("d");
$ndtimefrom = mktime(0,0,0);
$ndtimeto = time();

//Указан ли месяц
if(isset($_GET["monthfrom"])) $monthfrom = $_GET["monthfrom"];
if(isset($_GET["monthto"])) $monthto = $_GET["monthto"];


//Указана ли дата
if(isset($_GET["dayfrom"])) $dayfrom = $_GET["dayfrom"];
if(isset($_GET["dayto"])) $dayto = $_GET["dayto"];


//Сгенерируем unixtime для периода сортировки
if(isset($_GET["timefrom"]))
{
	if(strlen($_GET["timefrom"])>0)
		$ndtimefrom = mktime($_GET["timefrom"],0,0,$_GET["monthfrom"],$_GET["dayfrom"]);
}

if(isset($_GET["timeto"]))
{
	if(strlen($_GET["timeto"])>0)
		$ndtimeto = mktime($_GET["timeto"],0,0,$_GET["monthto"],$_GET["dayto"]);
}
elseif(isset($_GET["monthto"]))
{
	if(strlen($_GET["monthto"])>0)
		$ndtimeto = mktime(23,59,59,$_GET["monthto"],$_GET["dayto"]);
}

?>
<html>

<head>
<meta http-equiv="content-type" content="text/html;  charset=windows-1251" />
<title>Simple Postfix LogAnalyzer</title>
<style>
	body, td
	{
		font-family:Tahoma,Verdana,Sans serif;
		font-size:13px;
	}
	.queue
	{
		border:1px #ccc solid;
		margin:5px;
		padding:5px;
	}
	small
	{
		color:#999;
	}
	a.email:link, a.email:visited
	{
		cursor:pointer;
		border-bottom:1px #000 dotted;
		text-decoration:none;
		color:#000;
	}
</style>
</head>
<body>

<form name="filterform">
	<table border="0" cellpadding="5" cellspacing="5">
		<tr>
			<td colspan="2">
				<a name="begin" /></a>
				<h3>Simple Postfix LogAnalyzer</h3>
			</td>
		</tr>
		<tr>
			<td width="200px">
				Log FILENAME
				<br/><small>Full path or filename</small>
			</td>
			<td>
				<input type="text" name="logfilename" value="<?=$logfilename; ?>" size="12" />
			</td>
		</tr>
		<tr>
			<td width="200px">
				Enter QUEUE ID
				<br/><small>Full or part of ID.<br/> You can search NOQUEUE</small>
			</td>
			<td>
				<input type="text" name="queue" value="<?=$ndqueueid; ?>" size="12" />
				<input type="button" value="NOQUEUE" OnClick="document.filterform.queue.value='NOQUEUE'" />
			</td>
		</tr>
		<tr>
			<td>
				Enter DATE FROM/TO
			</td>
			<td>
				<input type="text" name="dayfrom" value="<?=$dayfrom; ?>" size="3" />
				<input type="text" name="monthfrom" value="<?=$monthfrom; ?>" size="3" />
				-
				<input type="text" name="dayto" value="<?=$dayto;?>" size="3" />
				<input type="text" name="monthto" value="<?=$monthto; ?>" size="3" />
			</td>
		</tr>
		<tr>
			<td>
				Enter HOUR FROM/TO
				<br/><small>Like hh</small>
			</td>
			<td>
				<input type="text" name="timefrom" value="<?php if(!isset($_GET["timefrom"])){echo "00";}else{echo $_GET["timefrom"];} ?>" size="3" />
				-
				<input type="text" name="timeto" value="<?php if(isset($_GET["timeto"])) echo $_GET["timeto"]; ?>" size="3" />
			</td>
		</tr>
		<tr>
			<td>
				GREP
				<br/><small>Works like Unix GREP</small>
			</td>
			<td>
				<input type="text" name="ndmailaddr" value="<?=$ndmailaddr; ?>" size="12" />
			</td>
		</tr>
		<tr>
			<td>
				Select STATUS
			</td>
			<td>
				<select name="status">
					<option value="all">ALL</option>
					<option value="errors" <?php if($ndstatus=="errors"){echo "selected";} ?>>ERRORS</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				LIMIT
			</td>
			<td>
				<input type="text" name="limit" value="<?php echo $end; ?>" size="4" />
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" value="View" />
				<input type="button" value="Default" OnClick="window.location.href='maillog.php'" />
			</td>
		</tr>
	</table>
</form>

<?php
if($ndtimefrom>$ndtimeto){die("Error: Invalid time period");}

$filearray = @file($logfilename);
if(!$filearray){die("Error: Can't open file. Check permissions.");}

//Отсортируем массив в обратном порядке
krsort($filearray);
reset($filearray);
$array = array();

//Перебираем строчки с конца
foreach($filearray as $string)
{
	//Выбирать только с QUEUEID
	$regexp = "'.+: ([0-9A-F]*): (.+)$'";
	
	//Поиск по QUEUEID
	if(strlen($ndqueueid)>0){$regexp = "'^(.+): (".$ndqueueid."[0-9A-F]*): (.+)$'";}
	
	//Создание массива
	if(preg_match($regexp,$string))
	{
		$time = trim(preg_replace("'^(\w*)\s*(\d*) (\d\d:\d\d:\d\d).+$'","$1 $2 $3",$string));
		$unixtime = strtotime($time);
		//По времени
		if($unixtime<$ndtimefrom)break; //Чтобы не молотить все строчки
		if($unixtime>$ndtimeto)continue;
		
		$queueid = trim(preg_replace("'^(.+): ([0-9A-FNOQUEUE]*): (.+)$'","$2",$string));
		$mess = htmlspecialchars(preg_replace("'(.+)($queueid):(.+)'","$3",$string));
		
		if(!isset($array["$queueid"]["message"])) $array["$queueid"]["message"] ="";
		$array["$queueid"]["time"]= $unixtime;
		$array["$queueid"]["message"]= $time.$mess."<br/>".$array["$queueid"]["message"];
		
		//Время лога
		if($first==true){$endperiod = $unixtime; $first=false;}
		$startperiod = $unixtime;
		
		//Лимит не должен быть превышен
		if($readlimit){if(count($array)>=$end){break;}}
	}
}

//Ничего не нашел
if(count($array)==0){die("There is no match for your query. Try another.");}

//Отсортируем массив по времени в обратном порядке
arsort($array);
reset($array);

//Статистическая инфа
echo "<b>Total: ".count($array)."</b><br/>";
echo "<b>Limit: ".$end."</b><br/>";
printf("<b>LogFile Size: %.2f Kb</b><br/>",filesize($logfilename)/1024);
echo "<b>Log period: ".date("d.M H:i",$startperiod)." - ".date("d.M H:i",$endperiod)."</b><br/>";

//Вывод
foreach($array as $k => $sarray)
{
	$process = "Postfix SMTP";
	
	//Поиск по e-mail
	if(strlen($ndmailaddr)>0)
	{
		if(!stripos($array[$k]["message"],$ndmailaddr)){continue;}
		else $array[$k]["message"] = str_ireplace($ndmailaddr,"<font color=\"#DB8040\">$ndmailaddr</font>",$array[$k]["message"]);
	}
	
	if($ndstatus=="errors"){if(!preg_match("'undeliverable|bounced|deferred'",$array[$k]["message"])){ continue;}}
	
	if(strstr($array[$k]["message"],"spamassassin")){$process = "(Spamassassin)";}
	if(strstr($array[$k]["message"],"10025")){$process = "(Send to antivirus)";}
	if(strstr($array[$k]["message"],"accepted connection")){$process = "(Antivirus Check)";}
	
	
	//Подсветка статусов
	$array[$k]["message"] = preg_replace("'status=sent'","<font color=\"green\">status=sent</font>",$array[$k]["message"]);
	$array[$k]["message"] = preg_replace("'status=CLEAN'","<font color=\"green\">status=CLEAN</font>",$array[$k]["message"]);
	$array[$k]["message"] = preg_replace("'status=VIRUS'","<font color=\"red\">status=VIRUS</font>",$array[$k]["message"]);
	$array[$k]["message"] = preg_replace("'status=deliverable'","<font color=\"#DBBE00\">status=deliverable</font>",$array[$k]["message"]);
	$array[$k]["message"] = preg_replace("'status=(undeliverable|bounced|deferred)'","<font color=\"red\">status=$1</font>",$array[$k]["message"]);

	$start++;
	echo "<div class=\"queue\"><b>".$k." - #$start $process</b><br/>\n";
	
	//Подсветка to и from
	$array[$k]["message"] = preg_replace("'(to|from)=&lt;(.*?)&gt;'","$1=&lt;<a href=\"#begin\" class=\"email\" onClick=\"document.filterform.ndmailaddr.value=this.innerHTML\">$2</a>&gt;",$array[$k]["message"]);
	$array[$k]["time"] = date("<\i>d.m.Y</\i>",$array[$k]["time"]);
	
	//Вывод всего этого ужоса
	echo $array[$k]["time"]."<br/>".$array[$k]["message"]."<br/>\n";

	echo "</div>";
	
	if($start>=$end){break;}
}

printf("<script>document.title='Time %.2f s'</script>",microtime(true)-$starttime);
?>
</body>
</html>
<?php

class PostfixLogAnalyzer{
	
	//Путь до лога
	public $pathToLog = "D:/xampp/htdocs/mail.log";
	public $filter = array();
	public $errors = array();

	public $logArray = array();
	public $startPeriod;
	public $endPeriod;


	public function __construct() {
		$this->filter = array(
			"monthfrom"=>date("m"),
			"monthto"=>date("m"),
			"dayfrom"=>date("d"),
			"dayto"=>date("d"),
			"timefrom"=>mktime(0,0,0),
			"timeto"=>mktime(23,59,59),

			"limit"=>200,
			"queueId"=>"",
			"email"=>"",
			"status"=>"all",
		);
	}
	
	public function setUserFilters(array $filters){
		
		foreach ($filters as $filterKey => $filterValue){
			if($filterKey=="pathToLog"){
				$this->pathToLog = $filterValue;
			}
			else{
				$this->setFilterValuebyKey($filterKey, $filterValue);
			}
		}
		
		if(isset($filters["timefrom"])){
			if(strlen($filters["timefrom"])>0){
				$this->filter["timefrom"] = mktime($filters["timefrom"],0,0,$filters["monthfrom"],$filters["dayfrom"]);
			}
		}
		
		if(isset($filters["timeto"])){
			if(strlen($filters["timeto"])>0){
				$this->filter["timeto"] = mktime($filters["timeto"],0,0,$filters["monthto"],$filters["dayto"]);
			}
		}
		elseif(isset($filters["monthto"])){
			if(strlen($filters["monthto"])>0){
				$this->filter["timeto"] = mktime(23,59,59,$filters["monthto"],$filters["dayto"]);
			}
		}
		
		if($this->filter["timefrom"] > $this->filter["timeto"]){
			echo $this->filter["timefrom"]." > ";
			echo $this->filter["timeto"];
			
			$this->errors[] = "Error: Invalid time period";
			return false;
		}
		
		return true;
	}
	
	public function parse(){
		$fileArray = $this->getFile();
		
		if(count($this->errors)===0){
			$array = array(); //Массив для лога
			$first = true; //Первый ли элемент лога
			
			//Перебираем строчки с конца
			foreach($fileArray as $string){
				
				//Выбирать только с QUEUEID
				$regexp = "'.+: ([0-9A-F]*): (.+)$'";

				//Поиск по QUEUEID
				if(strlen($this->filter["queueId"])>0){$regexp = "'^(.+): (".$this->filter["queueId"]."[0-9A-F]*): (.+)$'";}

				//Создание массива
				if(preg_match($regexp,$string))	{
					$time = trim(preg_replace("'^(\w*)\s*(\d*) (\d\d:\d\d:\d\d).+$'","$1 $2 $3",$string));
					$unixtime = strtotime($time);
					
					//По времени
					if($unixtime<$this->filter["timefrom"])break; //Чтобы не молотить все строчки
					if($unixtime>$this->filter["timeto"])continue;

					$queueid = trim(preg_replace("'^(.+): ([0-9A-FNOQUEUE]*): (.+)$'","$2",$string));
					$mess = htmlspecialchars(preg_replace("'(.+)($queueid):(.+)'","$3",$string));

					if(!isset($array[$queueid]["message"])) $array[$queueid]["message"] ="";
					$array[$queueid]["time"]= $unixtime;
					$array[$queueid]["message"]= $time.$mess."<br/>".$array[$queueid]["message"];

					//Время лога
					if($first==true){$this->endPeriod = $unixtime; $first=false;}
					$this->startPeriod = $unixtime;

					//Лимит не должен быть превышен
					if($this->filter["limit"]>0){
						if(count($array)>=$this->filter["limit"]){break;}
					}
				}
			}

			//Отсортируем массив по времени в обратном порядке
			arsort($array);
			reset($array);
			
			$this->logArray = $array;
			unset($array);
			
			$this->prepareView();
		}
		else{
			return false;
		}
	}
	
	protected function prepareView(){
		foreach($this->logArray as $k => $sarray){
			$this->logArray[$k]["process"] = "Postfix SMTP";

			//Поиск по e-mail
			if(strlen($this->filter["email"])>0){
				if(!stripos($this->logArray[$k]["message"],$this->filter["email"])){continue;}
				else $this->logArray[$k]["message"] = str_ireplace($this->filter["email"],"<font color=\"#DB8040\">".$this->filter["email"]."</font>",$this->logArray[$k]["message"]);
			}

			if($this->filter["status"]==="errors"){
				if(!preg_match("'undeliverable|bounced|deferred'",$this->logArray[$k]["message"])){
					continue;
				}
			}
			
			if(strstr($this->logArray[$k]["message"],"spamassassin")){$this->logArray[$k]["process"] = "(Spamassassin)";}
			if(strstr($this->logArray[$k]["message"],"10025")){$this->logArray[$k]["process"] = "(Send to antivirus)";}
			if(strstr($this->logArray[$k]["message"],"accepted connection")){$this->logArray[$k]["process"] = "(Antivirus Check)";}
			

			//Подсветка статусов
			$this->logArray[$k]["message"] = preg_replace("'status=sent'","<font color=\"green\">status=sent</font>",$this->logArray[$k]["message"]);
			$this->logArray[$k]["message"] = preg_replace("'status=CLEAN'","<font color=\"green\">status=CLEAN</font>",$this->logArray[$k]["message"]);
			$this->logArray[$k]["message"] = preg_replace("'status=VIRUS'","<font color=\"red\">status=VIRUS</font>",$this->logArray[$k]["message"]);
			$this->logArray[$k]["message"] = preg_replace("'status=deliverable'","<font color=\"#DBBE00\">status=deliverable</font>",$this->logArray[$k]["message"]);
			$this->logArray[$k]["message"] = preg_replace("'status=(undeliverable|bounced|deferred)'","<font color=\"red\">status=$1</font>",$this->logArray[$k]["message"]);

			//Подсветка to и from
			$this->logArray[$k]["message"] = preg_replace("'(to|from)=&lt;(.*?)&gt;'","$1=&lt;<a href=\"#begin\" class=\"email\" onClick=\"document.filterform.email.value=this.innerHTML\">$2</a>&gt;",$this->logArray[$k]["message"]);
			$this->logArray[$k]["time"] = date("<\i>d.m.Y</\i>",$this->logArray[$k]["time"]);
		}
	}

	protected function getFile(){
		$fileContent = file($this->pathToLog);
		if(!$fileContent){
			$this->errors[] = "Error: Can't open file. Check permissions.";
			return false;
		}
		
		krsort($fileContent);
		reset($fileContent);
		
		return $fileContent;
	}

	protected function setFilterValuebyKey($filterKey,$filterValue){
		if(isset($this->filter[$filterKey]) && $filterValue<>""){
			$this->filter[$filterKey] = $filterValue;
		}
	}
}


function convert($size) {
	$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
	return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

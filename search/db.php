<?php
	$get_city_array = ["Бобруйск","Минск",];
	$start = $_GET["start"];
	$end = $_GET["end"];
	$date = $_GET["date"];
	$timestart = $_GET["timestart"];
	$timeend = $_GET["timeend"];

	class Db {
		function __construct ($host, $user, $pass, $dbname){
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;
			$this->dbname = $dbname;
		}
		function Connect() {
			$this->mysqli = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
			if ($this->mysqli->connect_error) {
			  die("Connection failed: " . $this->mysqli->connect_error);
			} 
			return $this->mysqli;
		}
		function query($query) {
			$result = $this->mysqli->query($query);
			return $result;
		}

	}

	$db = new Db("localhost", "root", "root", "bus");
	$connect = $db->Connect();

	if(isset($_GET["delete"])){
		$get_search = $db->query("SELECT * FROM `search` WHERE `id`='".$_GET["delete"]."'");
		while($result = $get_search->fetch_array()){
			$query = $db->query("DELETE FROM `race` WHERE `date`='".$result["date"]."' AND `fromcity`='".$result["fromcity"]."' AND `tocity`='".$result["tocity"]."'");
		}
		$query = $db->query("DELETE FROM `search` WHERE `id`='".$_GET["delete"]."'");
	}

	if(isset($start)){
		$get_search = $db->query("SELECT * FROM `search` WHERE `date`='".$date."' AND `timestart`='".$timestart."' AND `timeend`='".$timeend."' AND `fromcity`='".$start."' AND `tocity`='".$end."'");
		if ($get_search->num_rows == 0) {
			$query = $db->query("INSERT INTO `search` (`date`, `timestart`,`timeend`,`fromcity`,`tocity`) VALUES ('".$date."','".$timestart."','".$timeend."','".$start."','".$end."')");
		}
	}

	$get_search = $db->query("SELECT * FROM `search`");
	while($result = $get_search->fetch_array()){
		echo '
			<div class="results-item">
				<div class="results-way">
					<span class="title">Маршрут:</span>
					<span class="text">'.$get_city_array[$result["fromcity"]-1].' - '.$get_city_array[$result["tocity"]-1].'</span>
				</div>
				<div class="results-date">
					<span class="title">Дата:</span>
					<span class="text">'.$result["date"].'</span>
				</div>
				<div class="results-timestart">
					<span class="title">Время от:</span>
					<span class="text">'.$result["timestart"].'</span>
				</div>
				<div class="results-timeend">
					<span class="title">Время до:</span>
					<span class="text">'.$result["timeend"].'</span>
				</div>
				<div class="button-del">
					<span class="text delete" data-id="'.$result["id"].'">Удалить</span>
				</div>
			</div>
		';
	}
	$db->close;
?>
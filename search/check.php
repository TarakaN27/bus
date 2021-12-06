<?php
	$get_city_array = ["Бобруйск","Минск",];
	include( 'simple_html_dom.php' );

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

	class Trans {
		function __construct ($date) {
			$this->date = $date;
		} 

		function getCountryId ($city, $id="value", $name="text", $args = null) {
			$arrContextOptions=array(
			    "ssl"=>array(
			        "verify_peer"=>false,
			        "verify_peer_name"=>false,
			    ),
			); 
			$get_data = file_get_contents($this->getUrl()["url_city"].urlencode($args), false, stream_context_create($arrContextOptions));
	 		$data_result = json_decode($get_data, true);
	 		foreach ($data_result as $item) {
	 			if($item[$name] == $city) {
	 				$result = $item[$id];
	 				return $result;
	 			}
	 		}
		}

		function getUrl(){
			$company_url = "http://minsk-bobruisk.by/cp/api/v1/ap/reis?citya=".$this->citya."&cityb=".$this->cityb."&date=".$this->date."";
			$company_url_city = "http://minsk-bobruisk.by/cp/api/v1/ap/citya";
			$company_url_home = "http://minsk-bobruisk.by/";
			$company_urls = ["url"=>$company_url, "url_city"=>$company_url_city, "home_url"=>$company_url_home];
			return $company_urls;
		}

		function getData() {
			$get_data = file_get_contents($this->getUrl()["url"]);
 			$result = json_decode($get_data, true);
 			return $result;
		}
	}

	class Autojet extends Trans {
		function getUrl(){
			$company_url = "https://routebysaas.saas.carbus.io/%D0%9C%D0%B0%D1%80%D1%88%D1%80%D1%83%D1%82%D1%8B/".urlencode($this->citya_name)."/".urlencode($this->cityb_name)."?date=".$this->date."&passengers=1&from=".$this->citya."&to=".$this->cityb;
			$company_url_city = "https://routebysaas.saas.carbus.io/api/search/suggest?user_input=";
			$company_urls = ["url"=>$company_url, "url_city"=>$company_url_city];
			return $company_urls;
		}
		function getData() {
			$result = file_get_html($this->getUrl()["url"]);
			foreach($result->find('div.MuiContainer-root div.jss5') as $element){
				$item['date_reis']     = $this->date;
       			$item['time_reis']     = $element->find("div.MuiGrid-container",0)->find("div.MuiGrid-grid-md-3", 0)->children(0)->children(0)->children(0)->plaintext;
       			$item['place_reis']     = $element->find("div.MuiGrid-container",0)->find("button",0)->find("div.MuiBox-root", 1)->plaintext;
			    $articles[] = $item;
			}
			return $articles;
		}
	}

	class Autolev extends Trans {
		function getCountryId ($city, $id="value", $name="text", $args = null) {
			$arrContextOptions=array(
			    "ssl"=>array(
			        "verify_peer"=>false,
			        "verify_peer_name"=>false,
			    ),
			); 
			$get_data = file_get_contents($this->getUrl()["url_city"], false, stream_context_create($arrContextOptions));
	 		$data_result = json_decode($get_data, true);
	 		$key = array_search($city, array_column($data_result, 'cityDestination'));
	 		return $data_result[$key]["cityDestinationId"];
		}
		function getUrl(){
			$company_url = "https://buspro.by/api/trip?s[company_id]=2&s[city_departure_id]=".$this->citya."&s[city_destination_id]=".$this->cityb."&s[date_departure]=".$this->date."&actual=1";
			$company_url_city = "https://buspro.by/api/route?s[company_id]=2";
			$company_url_home = "https://bobruysk-minsk.by/reservation/";
			$company_urls = ["url"=>$company_url, "url_city"=>$company_url_city, "home_url"=>$company_url_home];
			return $company_urls;
		}
	}

	$bus_race = [];

	$db = new Db("localhost", "root", "root", "bus");
	$connect = $db->Connect();
	$get_search = $db->query("SELECT * FROM `search`");
	$profit_race = [];
	while($result = $get_search->fetch_array()){
		$date = $result["date"];
		$timestart = $result["timestart"];
		$timeend = $result["timeend"];
		$fromcity = $result["fromcity"];
		$tocity = $result["tocity"];

		#=================MinskLine=========================================
		$company = "MinskLine";
		$trans = new Trans($date);
		$get_citya = $trans->getCountryId($get_city_array[$fromcity-1]);
		$get_cityb = $trans->getCountryId($get_city_array[$tocity-1]);
		$trans->citya = $get_citya;
		$trans->cityb = $get_cityb;

		foreach ($trans->getData() as $item) {
			if($item["FreePlace"] > 0) {
				$bus_race[] = [
					"company"=>$company,
					"date_reis"=>$item["date_reis"],
					"time_reis"=>$item["time_reis"],
					"place_reis"=>$item["FreePlace"],
					"href_reis"=> $trans->getUrl()["home_url"]
				];
			}
		}
		#===================================================================

		#=================AutoJet=========================================
		$company = "AutoJet";
		$autojet = new Autojet($date);
		$get_citya_name = $get_city_array[$fromcity-1];
		$get_cityb_name = $get_city_array[$tocity-1];
		$get_citya = $autojet->getCountryId($get_citya_name, "id", "name", $get_citya_name);
		$get_cityb = $autojet->getCountryId($get_cityb_name, "id", "name", $get_cityb_name);
		$autojet->citya = $get_citya;
		$autojet->cityb = $get_cityb;
		$autojet->citya_name = $get_citya_name;
		$autojet->cityb_name = $get_cityb_name;

		foreach ($autojet->getData() as $item) {
			if($item["place_reis"] != false) {
				$bus_race[] = [
					"company"=>$company,
					"date_reis"=>$item["date_reis"],
					"time_reis"=>$item['time_reis'],
					"place_reis"=>$item["place_reis"],
					"href_reis"=> $autojet->getUrl()["url"]
				];
			}
		}
		#===================================================================

		#=================AutoLev=========================================
		$company = "AutoLev";
		$autolev = new Autolev($date);
		$get_citya = $autolev->getCountryId($get_city_array[$fromcity-1]);
		$get_cityb = $autolev->getCountryId($get_city_array[$tocity-1]);
		$autolev->citya = $get_citya;
		$autolev->cityb = $get_cityb;

		foreach ($autolev->getData() as $item) {
			if($item["freePlaces"] > 0) {
				$bus_race[] = [
					"company"=>$company,
					"date_reis"=>$date,
					"time_reis"=>$item["timeDeparture"],
					"place_reis"=>$item["freePlaces"],
					"href_reis"=> $autolev->getUrl()["home_url"]
				];
			}
		}
		#===================================================================

		usort($bus_race, function($a, $b){
	    	return (intval(str_replace(":", "",$a['time_reis'])) - intval(str_replace(":", "",$b['time_reis'])));
		});

		foreach($bus_race as $race){
			if(intval(str_replace(":", "",$race['time_reis']))>=intval(str_replace(":", "",$timestart)) && intval(str_replace(":", "",$race['time_reis']))<=intval(str_replace(":", "",$timeend))) {
				$get_search = $db->query("SELECT * FROM `race` WHERE `company`='".$race["company"]."' AND `date`='".$race["date_reis"]."' AND `time`='".$race["time_reis"]."' AND `fromcity`='".$fromcity."' AND `tocity`='".$tocity."'");
				if ($get_search->num_rows == 0) {
					$query = $db->query("INSERT INTO `race` (`company`, `date`,`time`,`fromcity`,`tocity`) VALUES ('".$race["company"]."','".$race["date_reis"]."','".$race["time_reis"]."','".$fromcity."','".$tocity."')");
					$race["race"] = $get_city_array[$fromcity-1]."-".$get_city_array[$tocity-1];
					$profit_race[] = $race;
				}
			}
		}
	}

	if(count($profit_race) >= 1){
		foreach($profit_race as $race) {
			$message = $race["race"]." ".$race["company"]." ".$race["date_reis"]." ".$race["time_reis"]." ".$race["place_reis"];
			$ch = curl_init();
			$vars = json_encode([
					"channel" => 'C02Q8QUU6E4',
					"text" => $message,
				]);
			curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/chat.postMessage");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$vars);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$headers = [
				"Authorization: Bearer ",
				"Content-type: application/json; charset=utf-8"
			];
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$server_output = curl_exec ($ch);
			var_dump($server_output);
			curl_close ($ch);
		}
	}
	
?>


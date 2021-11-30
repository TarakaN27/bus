<?php
	$get_city_array = ["Бобруйск","Минск",];
	include( 'simple_html_dom.php' );

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
       			$item['place_reis']     = substr(strval($element->find("div.MuiGrid-container",0)->find("button",0)->find("div.MuiBox-root", 1)->plaintext), 17, 2);
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

	$get_date = $_GET["date"];
	$bus_race = [];

	#=================MinskLine=========================================
	$company = "MinskLine";
	$trans = new Trans($get_date);
	$get_citya = $trans->getCountryId($get_city_array[$_GET["start"]-1]);
	$get_cityb = $trans->getCountryId($get_city_array[$_GET["end"]-1]);
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
	$autojet = new Autojet($get_date);
	$get_citya_name = $get_city_array[$_GET["start"]-1];
	$get_cityb_name = $get_city_array[$_GET["end"]-1];
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
	$autolev = new Autolev($get_date);
	$get_citya = $autolev->getCountryId($get_city_array[$_GET["start"]-1]);
	$get_cityb = $autolev->getCountryId($get_city_array[$_GET["end"]-1]);
	$autolev->citya = $get_citya;
	$autolev->cityb = $get_cityb;

	foreach ($autolev->getData() as $item) {
		if($item["freePlaces"] > 0) {
			$bus_race[] = [
				"company"=>$company,
				"date_reis"=>$get_date,
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
	$result_race = "";

	foreach ($bus_race as $item) {
			$result_race .= '
				<div class="results-item">
					<a href="'.$item["href_reis"].'" target="_blank"></a>
					<div class="results-way">
						<span class="title">Маршрут:</span>
						<span class="text">'.$get_city_array[$_GET["start"]-1].' - '.$get_city_array[$_GET["end"]-1].'</span>
					</div>
					<div class="results-company">
						<span class="title">Компания:</span>
						<span class="text">'.$item["company"].'</span>
					</div>
					<div class="results-date">
						<span class="title">Дата:</span>
						<span class="text">'.$item["date_reis"].'</span>
					</div>
					<div class="results-time">
						<span class="title">Время:</span>
						<span class="text">'.$item["time_reis"].'</span>
					</div>
					<div class="results-placecount">
						<span class="title">Места:</span>
						<span class="text">'.$item["place_reis"].'</span>
					</div>
					<div class="results-price">
						<span class="title">Цена:</span>
						<span class="text">10 руб</span>
					</div>
				</div>
			';
	}

	echo $result_race;
?>
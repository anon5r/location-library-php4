<?php

define('AZFEX_LOCATION_MOBILE_IAREA_DIR', '/root/path/to/iarea');

require_once ('Location.php');

/**
 * Location Class
 *
 * @package AZFEX
 * @version 0.19
 * @access public
 * @author anon <anon@anoncom.net>
 * @copyright 2007-2009 anon
 * @since Fri.16, Mar. 2007
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link http://labs.anoncom.net/
 */
class Location_Mobile extends Location {

	/**
	 * <strong>get Vender</strong><br />
	 * 接続元ベンダー判別
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function getVender(){

		$ip = $_SERVER['REMOTE_ADDR'];
		// ip じゃない場合のエラー回避
		if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)){
			$host = gethostbyaddr($ip);	// IPの場合はget Host By Addrする
		}else{
			$host = $ip;
		}

		if(Location_Mobile::endsWith($host, '.docomo.ne.jp')){
			return 'd';	// DoCoMo
		}elseif(Location_Mobile::endsWith($host, '.ezweb.ne.jp')){
			return 'a';	// au
		}elseif(Location_Mobile::endsWith($host, '.jp-c.ne.jp')){
			return 's';	// SoftBank
		}elseif(Location_Mobile::endsWith($host, '.jp-t.ne.jp')){
			return 's';	// SoftBank
		}elseif(Location_Mobile::endsWith($host, '.jp-k.ne.jp')){
			return 's';	// SoftBank
		}elseif(Location_Mobile::endsWith($host, '.jp-q.ne.jp')){
			return 's';	// SoftBank
		}else{
			return 'x';	// others
		}

	}


	/**
	 * Get Model Name Function
	 * 端末名を取得
	 *
	 * @param	string | void
	 * @return	string
	 * @access	public
	 * @author	anon
	 */
	function getModelName($ua = NULL){
		if(empty($ua)){ $ua = $_SERVER['HTTP_USER_AGENT']; }
		
		/*************************
		 * NTT DoCoMo
		**************************/
		if(preg_match('/^DoCoMo\/[1-9]\.\d\/[A-Z]{1,}\d{3}(i|is|iS|it|i2|iC|iWM|iGPS)+/', $ua)){
		// get terminal name
			$tmp_ary = explode('/', $ua);
			if(count($tmp_ary) >= 2){
				$devname = $tmp_ary[2];
			}else{
				$devname = null;
			}
		}elseif(preg_match('/^DoCoMo\/[2-9]\.\d\s[A-Z]{1,2}(\d{3}i[A-Z2-9]{1,4}|\d{3}i|\d{4}V|\d{4})+/', $ua)){
		// get terminal name
			$tmp_ary = explode('/', $ua);
			if(count($tmp_ary) >= 1){
				$tmp = $tmp_ary[1];
				$tmp = str_replace('2.0 ','',$tmp);
				$tmp_num = strpos($tmp, '(', 1) ? strpos($tmp, '(', 1) + 1 : 0;
				$tmp = substr($tmp, 0, $tmp_num - 1);
				$devname = $tmp;
			}else{
				$devname = null;
			}
		}elseif(preg_match('/^DoCoMo\/2\.0\sMST_v_SH2101V+/', $ua)){
			$devname = 'SH2101V';
		}elseif(preg_match('/^(J\-PHONE|Vodafone|SoftBank|MOT\-[VC]9\d{2})\//', $ua)){

		/*************************
		 *	SoftBank
		**************************/
			$devname = $_SERVER['HTTP_X_JPHONE_MSNAME'];
			if($devname == ''){
				$devname = null;
			}

		/*************************
		 *	au by KDDI
		**************************/
		}elseif(preg_match('/^UP\.Browser\/\w+/', $ua)){
			// old type terminal
			$tmp_ary = explode('/', $ua);
			if(count($tmp_ary) >= 2){
				$tmp = $tmp_ary[1];
				$tmp_num = (strpos(1, $tmp, '-', 1) ? strpos(1, $tmp, '-', 1) + 1 : 0);
				$tmp1 = substr($tmp, 0, $tmp_num);
				$tmp = str_replace($tmp1, '', $tmp);
				$tmp = str_replace(' UP.Link', '', $tmp);
				//$devname = $this->auDevName($tmp);
				$devname = $tmp;
			}else{
				$devname = null;
			}
		}elseif(preg_match('/^KDDI\-[A-Z]{1,2}\d{2}\sUP\.Browser\/+/', $ua)){
			//au EZweb WAP2.0
			$tmp_ary = explode('/', $ua);
			if(count($tmp_ary) >= 0){
				$tmp = $tmp_ary[0];
				$tmp = str_replace('KDDI-', '', $tmp);
				$tmp = str_replace(' UP.Browser', '', $tmp);
				//$devname = $this->auDevName($tmp);
				$devname = $tmp;
			}else{
				$devname = null;
			}
		}elseif(preg_match('/L\-mode\/\/[1-9]\.\d\/AT\/+/', $ua)){
			// L-mode
			$devname = 'L-mode';
		}elseif(preg_match('/Mozilla\/[1-9]\.\d\((DDIPOCKET|WILLCOM);[A-Z]{2,}\/([\w\-]*)\//i', $ua, $tmp)){
				$devname = $tmp[2];
		}else{
			// other UserAgents
			$devname = 'Pc';
		}

		return $devname;
	}


	/**
	 * get Model Type
	 * キャリアごとの端末の種類（ただし判別はあくまでも位置情報の取得用）
	 *
	 * @access public
	 * @param void
	 * @return string / null
	 * @author anon <anon@anoncom.net>
	 */
	function getModelType(){
		$ua = $_SERVER['HTTP_USER_AGENT'];
		switch($this->getVender()){
			case 'd':	// DoCoMo
				if(preg_match("/^DoCoMo\/\d\.\d\/.*/", $ua)){
					// mova
					if($this->getModelName == 'F505iGPS' || $this->getModelName == 'F661i'){
						return 'igps';
					}else{
						return 'iarea';
					}
				}elseif(preg_match("/^DoCoMo\/\d\.\d\s.*/", $ua)){
					// FOMA
					$gps = $this->getArrayMap('docomo.gps.devicelist.txt');
					for($i = 0; $i < count($gps); $i++){
						if($this->getModelName() == $gps[$i]){
							return 'igps';
							break;
						}
					}
					return 'iarea';
				}else{
					return null;
				}
				break;
			case 'a':	// AU
				if(isset($_SERVER['HTTP_X_UP_DEVCAP_MULTIMEDIA']) && preg_match("/^[0-9A-F](\d)/", $_SERVER['HTTP_X_UP_DEVCAP_MULTIMEDIA'], $tmp)){
					$ary = chunk_split($tmp[1], 1, ',');
					$ary = explode(',', $ary);
					if($ary[0] >= 2){
						return 'gpsone';	// gpsOne
					}else{
						return 'location';	// 簡易位置情報
					}
				}else{
					return null;
				}
			case 's':	// SoftBank
				if(preg_match("/^J\-PHONE\/.*/", $ua)){
					$deny = array('J-N03S','J-SH05S','J-SH04BS','J-SH04S','J-PE03S',
						'J-D03S','J-K03S','J-P03','J-T04','J-SH03','J-SA02',
						'J-P02','J-DN02','J-SH02');
					for($i = 0; $i < count($deny); $i++){
						if($this->getModelName() == $deny[$i]){
							return null;
							break;
						}
					}
					return '2g';	// 2G

				}elseif(preg_match("/^(Vodafone|SoftBank|MOT).*/", $ua)){
					$deny = array('V801SA','V801SH','V702MO','V702sMO','V702NK',
						'V802N','VC701SI');
					for($i = 0; $i < count($deny); $i++){
						if($this->getModelName() == $deny[$i]){
							return null;
							break;
						}
					}
					return '3g';	// 3G
				}else{
					return null;
				}
				break;
			default:
				return null;
		}

	}


	/*
	 * get Array Map
	 * 外部マップファイルを読み込み、配列で返す
	 *
	 * @access private
	 * @param string
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	function getArrayMap($filename){
		if(file_exists($filename)){
			$tmp = @file($filename, '1');
			$max = count($tmp);
			while($i < $max){
				if(!preg_match('/^\s*#+.*/i', $tmp[$i])){	// コメント行無視
					$ary[] = rtrim($tmp[$i]);	// 改行除去
				}
				$i++;
			}
			return $ary;
		}else{
			return array();	// ERROR: null array
		}
	}


	/**
	 * is location
	 * 位置情報が送出されているか
	 *
	 * @access public
	 * @param void
	 * @return boolean
	 * @author anon <anon@anoncom.net>
	 */
	function isLocation(){
		switch($this->getVender()){
			case 'd':
				if(isset($_REQUEST['AREACODE'])){
					return true;
				}
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon']) && isset($_REQUEST['geo'])){
					return true;
				}else{
					return false;
				}
				break;
			case 'a':
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon']) && isset($_REQUEST['datum'])){
					return true;
				}else{
					return false;
				}
				break;
			case 's':
				if(isset($_REQUEST['pos']) && isset($_REQUEST['geo'])){
					return true;
				}else{
					return false;
				}
				break;
			default:
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon'])){
					return true;
				}else{
					return false;
				}
				break;
		}
	}
	
	/**
	 * get Location
	 * 位置情報を取得し、クラス内変数に格納
	 *
	 * @access public
	 * @param void
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	public function getLocation($type = 'deg'){
		switch($this->getVender()){
			case 'd': $this->docomo(); break;
			case 'a': $this->au(); break;
			case 's': $this->softbank(); break;
			default:  $this->pc(); break;
		}
		if($type == 'dms'){
			$array = array(
					$this->getLat(),
					$this->getLon()
				);
		}else{
			$array = array(
					$this->dms2deg($this->getLat()),
					$this->dms2deg($this->getLon())
				);
		}
		return $array;
	}
	
	
	/**
	 * 前方一致
	 * $hystackが$needleから始まるか判定します。
	 * @param string $hystack
	 * @param string $needle
	 * @return boolean
	 */
	function startsWith($hystack, $needle){
		return strpos($hystack, $needle, 0) === 0;
	}
	/**
	 * 後方一致
	 * $hystackが$needleで終わるか判定します。
	 * @param string $hystack
	 * @param string $needle
	 * @return boolean
	 */
	function endsWith($hystack, $needle){
		$length = (strlen($hystack) - strlen($needle));
		// 文字列長が足りていない場合はFALSEを返します。
		if($length <0) return FALSE;
		return strpos($hystack, $needle, $length) !== FALSE;
	}
	/**
	 * 部分一致
	 * $hystackの中に$needleが含まれているか判定します。
	 * @param string $hystack
	 * @param string $needle
	 * @return boolean
	 */
	function matchesIn($hystack, $needle){
		return strpos($hystack, $needle) !== FALSE;
	}
	
	
	/**
	 * <strong>DoCoMo</strong><br />
	 * DoCoMoからの位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return void
	 * @author anon <anon@anoncom.net>
	 */
	function docomo(){
		if(!isset($_REQUEST["AREACODE"]) && ((!isset($_GET["lat"]) && !isset($_GET["lon"])) && !isset($_GET['pos']))){
			$lat = "35.0.0.0";
			$lon = "139.0.0.0";
			$geo = "wgs84";
			$acr = 1;
		}else{
			if((isset($_POST["LAT"]) && !empty($_POST["LAT"])) && (isset($_POST["LON"]) && !empty($_POST["LON"])) && (isset($_POST["XACC"]) && !empty($_POST["XACC"])) && (isset($_POST["GEO"]) && !empty($_POST["GEO"]))){
				// posinfo 処理
				$lat = ereg_replace("[+-]", '', $_POST["LAT"]);
				$lon = ereg_replace("[+-]", '', $_POST["LON"]);
				$geo = strtolower($_POST["GEO"]);	// wgs84
				$acr = $_POST["XACC"];
				
			}elseif(isset($_POST["AREACODE"])){
				// Open i area処理
				$ary = $this->getFrom_iArea($_POST["AREACODE"]);
				$lat = $ary[0];
				$lon = $ary[1];
				$geo = "tokyo";
				$acr = 0;
			}elseif(isset($_GET['pos'])){
				// mova(GPS)処理
				if(isset($_GET['pos'])){
	
					// 測地系
					$geo = $_GET['geo'];
	
					if(preg_match("/^[NS]\-?([0-9]{2}\.[0-9]{2}\.[0-9]{2}\.[0-9]{2})[EW]\-?([0-9]{3}\.[0-9]{2}\.[0-9]{1,2}\.[0-9]{1,2})/", $_GET["pos"], $matches)){
						$lat = $matches[1];	// 北緯
						$lon = $matches[2];	// 東経
						$acr = $_GET["X-acc"];	// 精度
					}
				}
			}else{
				// FOMA(GPS)処理
				$lat = ereg_replace("%2B", '', $_GET['lat']);
				$lon = ereg_replace("%2B", '', $_GET['lon']);
				$geo = $_GET['geo'];
				$acr = $_GET['x-acc'];
			}
		}

		$this->setPoint($lat, $lon);
		$this->setLat($lat);
		$this->setLon($lon);
		$this->setGeo($geo);
		$this->setAcr($acr);
	}



	/**
	 * <strong>au</strong><br />
	 * auからの位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return void
	 * @author anon <anon@anoncom.net>
	 */
	function au(){
		if(!isset($_GET["lat"]) && !isset($_GET["lon"])){
			$lat = "35.0.0.0";
			$lon = "139.0.0.0";
			$geo = "wgs84";
			$acr = 1;
		}else{
			if(empty($_GET["smaj"]) && empty($_GET["smin"]) && empty($_GET["vert"])){
				// 簡易位置情報処理
				$lat = $_GET['lat'];
				$lon = $_GET['lon'];
				//$geo = $_GET['datum'];
				$geo = 'wgs84';
				$acr = 3;	// 取れないので3にしておく
				
			}else{
				// gpsOne処理
				$lat = ereg_replace("%2B", '', $_GET['lat']);
				$lon = ereg_replace("%2B", '', $_GET['lon']);
				$smaj = $_GET["smaj"];
				//$geo = $_GET['datum'];
				$geo = 'wgs84';
				if ($smaj < 50){
					$acr = 3;
				}elseif($smaj < 300){
					$acr = 2;
				}else{
					$acr = 1;
				}
			}
		}
		$this->setPoint($lat, $lon);
		$this->setLat($lat);
		$this->setLon($lon);
		$this->setGeo($geo);
		$this->setAcr($acr);
	}


	/**
	 * <strong>SoftBank</strong><br />
	 * ソフトバンク携帯からの位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return void
	 * @author anon <anon@anoncom.net>
	 */
	function softbank(){

		if(!isset($_SERVER["HTTP_X_JPHONE_GEOCODE"]) && !isset($_GET["pos"])){
			$lat = "35.0.0.0";
			$lon = "139.0.0.0";
			$acr = 1;
		}

		if(isset($_GET['pos'])){

			// 測地系
			$geo = $_GET['geo'];

			if(preg_match("/^[NS][+-]?([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})[EW][+-]?([0-9]{1,3}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})/", $_GET["pos"], $matches)){
				$lat = $matches[1];	// 北緯
				$lon = $matches[2];	// 東経
				$acr = $_GET["x-acr"];
			}
		}else{
			if($_SERVER["HTTP_X_JPHONE_GEOCODE"] == "0000000%1A0000000%1A%88%CA%92%75%8F%EE%95%F1%82%C8%82%B5"){
				$this->setLat("35.0.0.0");
				$this->setLon("139.0.0.0");
			}

			list($lat, $lon, $address) = split("\%1A", $geocode);

			// 緯度の整形
			$lat = substr($latitude, 0, 2).".".
				substr($latitude, 2, 2).".".
				substr($latitude, 4, 2);
			// 経度の整形
			$lon = substr($longitude, 0, 3).".".
				substr($longitude, 3, 2).".".
				substr($longitude, 5, 2);
			// 住所のデコード
			$address = urldecode($address);
			// 誤差精度
			//$accuracy = 0;
			$acr = 1;
			// 測地系
			$geo = 'tokyo';
		}


		$this->setPoint($lat, $lon);
		$this->setLat($lat);
		$this->setLon($lon);
		$this->setGeo($geo);
		$this->setAcr($acr);
	}


	/**
	 * <strong>Pc</strong><br />
	 * PCでの位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return void
	 * @author anon <anon@anoncom.net>
	 */
	function pc(){

		if(isset($_GET['pos'])){
			preg_match("/^[NS]\-?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[EW]\-?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/", $_GET["pos"], $matches);
			$lat = $matches[1];	// 北緯
			$lon = $matches[2];	// 東経
		}else{
			if(isset($_GET["lat"])){
				$lat = $_GET["lat"];
			}
			if(isset($_GET["lon"])){
				$lon = $_GET["lon"];
			}
		}
		$geo = isset($_GET['geo']) ? $_GET['geo'] : 'wgs84';
		$acr = isset($_GET['acr']) ? $_GET['acr'] : 2;

		if(is_float($lat)){
			$lat = $this->deg2dms($lat);
		}
		if(is_float($lon)){
			$lon = $this->deg2dms($lon);
		}

		$this->setPoint($lat, $lon);
		$this->setLat($lat);
		$this->setLon($lon);
		$this->setGeo($geo);
		$this->setAcr($acr);
	}


	/**
	 * <strong>get from iArea</strong><br />
	 * DoCoMoのOpen i areaからdms形式の位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	 function getFrom_iArea($areacode){
		$area = file_get_contents(__I_AREA_DIR__ . "/iarea" . $areacode . ".txt");
		/*
		 * iエリアでの位置情報の求め方
		 * iエリアでは、特定エリアの範囲をメッシュ方式で区切り、
		 * メッシュで区切ったうちのエリア範囲内の
		 * 最西端の経度
		 * 最南端の緯度
		 * 最東端の経度
		 * 最北北端の緯度
		 * の情報を持っている。今回はメッシュ情報を用いずに、
		 * この情報からエリアの中間地点を求め、
		 * そこの地点情報を返す仕様とする。
		 */
		//list($id_area, $id_subarea, $area_name, $lon_w, $lat_s, $lon_e, $lat_n, $mesh) = explode(',', $area, 8);
		$ary = explode(',', $area, 8);
		//$lat = $lat_n + $lat_s;
		//$lon = $lon_e + $lon_w;
		$lat = ($ary[6] + $ary[4]) / 7200000;
		$lon = ($ary[5] + $ary[3]) / 7200000;
		$lat = $this->deg2dms($lat);
		$lon = $this->deg2dms($lon);
		return array($lat, $lon);
	 }



	/**
	 * <strong>print Anchor</strong><br />
	 * 各端末ごとにあわせたリンク用インタフェースを出力する。<br />
	 *
	 * @access public
	 * @param string url, string anchor_string
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function printAnchor($uri, $str = 'getLocation'){
		switch($this->getModelType()){
			case 'igps':
				$ret = "<a href=\"$uri\" lcs>$str</a>"; break;
			case 'iarea':
				$ret = "<a href=\"http://w1m.docomo.ne.jp/cp/iarea?ecode=OPENAREACODE&msn=OPENAREAKEY&posinfo=1&nl=$uri\">$str</a>"; break;
			case 'gpsone':
				$ret = "<a href=\"device:gpsone?url=$uri&ver=1&datum=0&unit=0&acry=0&number=0\">$str</a>"; break;
			case 'location':
				$ret = "<a href=\"device:location?url=$uri\">$str</a>"; break;
			case '2g':
				$ret = "<a href=\"$uri\" z>$str</a>"; break;
			case '3g':
				$ret = "<a href=\"location:auto?url=$uri\">$str</a>"; break;
			default:
				$ret = "<a href=\"$uri\">$str</a>"; break;
		}
		return $ret;
	}
	
}

?>

<?php
define('__I_AREA_DIR__', '/root/path/to/iarea');

/*
 * [memo]
 * Longitude 経度	本初子午線を中心に0～180度まである。本初子午線から東を東経（＋）、西側を西経（－）とする。
 * Latitude  緯度	赤道が0度、南北にそれぞれ90度まで
 */

/**
 * Location Class
 *
 * @package my
 * @version 1.0.0
 * @access public
 * @author anon <anon@anoncom.net>
 * @copyright anon (anoncom.net)
 * @since Fri.16, Mar. 2007
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link http://labs.anoncom.net/
 * @link http://blog.anoncom.net/
 */
class Location{
	
	const NETWORK_PROVIDER_DOCOMO	= 1;
	const NETWORK_PROVIDER_KDDI		= 2;
	const NETWORK_PROVIDER_SOFTBANK = 3;
	const NETWORK_PROVIDER_OTHER	= 9;
	
	const VERSION = '1.0.0';
	
	/**
	 * 測地方法：簡易位置情報（基地局情報）
	 * 
	 * @var int
	 */
	const GEODETIC_SIMPLE		= 1;
	
	/**
	 * 測地方法：アシストGPS
	 * 
	 * @var int
	 */
	const GEODETIC_AGPS			= 2;
	
	
	const TOKYO		= 'tokyo';
	const WGA84		= 'wgs84';
	
	/**
	 * point (format: degree, data: wgs84)
	 * 
	 * @var array(double, double)
	 */
	private $point = array();
	private $accuracy = null;
	
	private $geometory = null;
	
	
	/**
	 * constructor
	 * 
	 */
	function __construct(){}
	
	

	/**
	 * get Provider
	 * 接続元プロバイダ判別
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public static function getProvider($host = null){
		
		if($host == NULL || strlen($host)){
			$host= $_SERVER['REMOTE_ADDR'];
		}
		
		// IPv4 であった場合
		if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $host)){
			$host = gethostbyaddr($host);	// IPの場合はget Host By Addrする
		}
		
		if(self::endsWith($host, '.docomo.ne.jp')){
			return self::NETWORK_PROVIDER_DOCOMO;
		}elseif(self::endsWith($host, '.ezweb.ne.jp')){
			return self::NETWORK_PROVIDER_KDDI;
		}elseif(self::endsWith($host, '.jp-c.ne.jp')){
			return self::NETWORK_PROVIDER_SOFTBANK;
		}elseif(self::endsWith($host, '.jp-t.ne.jp')){
			return self::NETWORK_PROVIDER_SOFTBANK;
		}elseif(self::endsWith($host, '.jp-k.ne.jp')){
			return self::NETWORK_PROVIDER_SOFTBANK;
		}elseif(self::endsWith($host, '.jp-q.ne.jp')){
			return self::NETWORK_PROVIDER_SOFTBANK;
		}else{
			return self::NETWORK_PROVIDER_OTHER;
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
	public function getModelName($ua = NULL){
		if(empty($ua)){ $ua = $_SERVER['HTTP_USER_AGENT']; }
		
		// DoCoMo
		if(self::startsWith($ua, 'DoCoMo/1.0')){
			// get model name
			$tmp = explode('/', $ua);
			if(count($tmp) < 2){
				return null;
			}
			return $tmp[2];
			
		}elseif(self::startsWith($ua, 'DoCoMo/2.')){
			
			if(self::startsWith($ua, 'DoCoMo/2.0 MST_v_SH2101V')){
				return 'SH2101V';
			}
			
			// get model name
			$tmp_ary = explode('/', $ua);
			if(count($tmp) < 1){
				return null;
			}
			$tmp = $tmp[1];
			$tmp_str = str_replace('2.0 ', '', $tmp);
			$tmp_num = strpos($tmp_str, '(', 1) ? strpos($tmp_str, '(', 1) + 1 : 0;
			$tmp_str = substr($tmp_str, 0, $tmp_num - 1);
			return $tmp_str;
			
		}elseif(self::startsWit($ua, 'UP.Browser/')){
			// old type model
			$tmp = explode('/', $ua);
			if(count($tmp) < 2){
				return null;
			}
			
			$tmp_str = $tmp[1];
			$tmp_num = (strpos(1, $tmp_str, '-', 1) ? strpos(1, $tmp_str, '-', 1) + 1 : 0);
			$tmp_str1 = substr($tmp_str, 0, $tmp_num);
			$tmp_str = str_replace($tmp_str1, '', $tmp_str);
			$tmp_str = str_replace(' UP.Link', '', $tmp_str);
			return $tmp_str;
			
		}elseif(self::startsWith($ua, 'KDDI-')){
			//au EZweb WAP2.0
			$tmp = explode('/', $ua);
			if(count($tmp) < 1){
				return null;
			}
			$tmp_str = $tmp[0];
			$tmp_str = str_replace('KDDI-', '', $tmp_str);
			$tmp_str = str_replace(' UP.Browser', '', $tmp_str);
			return $tmp_str;
			
		}else{
			if(
				self::startsWith($ua, 'J-PHONE/')
			 || self::startsWith($ua, 'Vodafone/')
			 || self::startsWith($ua, 'SoftBank/')
			 || self::startsWith($ua, 'MOT-')
			){
				// SoftBank
				if(
					isset($_SERVER['HTTP_X_JPHONE_MSNAME']) === FALSE
				&& strlen($_SERVER['HTTP_X_JPHONE_MSNAME']) == 0){
					return null;
				}
				return $_SERVER['HTTP_X_JPHONE_MSNAME'];
			}
			
		}
			
		// other UserAgents
		return 'Pc';
		
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
	public function getModelType(){
		$ua = $_SERVER['HTTP_USER_AGENT'];
		
		
		switch(self::getProvider()){
			
			case self::NETWORK_PROVIDER_DOCOMO:	// DoCoMo
				if(self::startsWith($ua, 'DoCoMo/1.0/')){
					// mova
					if($this->getModelName() == 'F505iGPS' || $this->getModelName() == 'F661i'){
						return self::GEODETIC_SIMPLE;
					}
					return self::GEODETIC_AGPS;
				}elseif(self::startsWith($ua, 'DoCoMo/2.')){
					// FOMA
					$gps = $this->getArrayMap('docomo.gps.devicelist.txt');
					$max = count($gps);
					for($i = 0; $i < $max; $i++){
						if($this->getModelName() === $gps[$i]){
							return self::GEODETIC_AGPS;
						}
					}
					return self::GEODETIC_SIMPLE;
				}
				
				return null;
				
			case self::NETWORK_PROVIDER_KDDI:	// AU
				if(
						isset($_SERVER['HTTP_X_UP_DEVCAP_MULTIMEDIA']) === TRUE
					&& preg_match('/^[0-9A-F](\d)/', $_SERVER['HTTP_X_UP_DEVCAP_MULTIMEDIA'], $tmp)
				){
					$ary = chunk_split($tmp[1], 1, ',');
					$ary = explode(',', $ary);
					if($ary[0] >= 2){
						return self::GEODETIC_AGPS;	// gpsOne
					}
					return self::GEODETIC_SIMPLE;	// 簡易位置情報
				}
				return null;
				
			case self::NETWORK_PROVIDER_SOFTBANK:	// SoftBank
				if(self::startsWith($ua, 'J\-PHONE/')){
					$deny = array(
						'J-N03S',  'J-SH05S', 'J-SH04BS', 'J-SH04S',
						'J-PE03S', 'J-D03S',  'J-K03S',   'J-P03',
						'J-T04',   'J-SH03',  'J-SA02',   'J-P02',
						'J-DN02',  'J-SH02'
					);
					$max = count($deny);
					for($i = 0; $i < $max; $i++){
						if($this->getModelName() === $deny[$i]){
							return null;
						}
					}
					return self::GEODETIC_SIMPLE;	// 2G
					
				}elseif(
					   self::startsWith($ua, 'Vodafone')
					|| self::startsWith($ua, 'SoftBank')
					|| self::startsWith($ua, 'MOT-)')
				){
					$deny = array(
						'V801SA','V801SH','V702MO','V702sMO','V702NK',
						'V802N','VC701SI'
					);
					$max = count($deny);
					for($i = 0; $i < $max; $i++){
						if($this->getModelName() === $deny[$i]){
							return null;
						}
					}
					return self::GEODETIC_AGPS;		// 3G
				}
				return null;
				
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
			$line = @file($filename, 1);
			$max = count($line);
			while($i < $max){
				if(!preg_match('/^\s*#+.*/i', $line[$i])){	// コメント行無視
					$map[] = rtrim($line[$i]);	// 改行除去
				}
				$i++;
			}
			return $map;
		}
		
		return array();	// ERROR: null array
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
			case self::NETWORK_PROVIDER_DOCOMO:
				if(isset($_REQUEST['AREACODE'])){
					return true;
				}
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon']) && isset($_REQUEST['geo'])){
					return true;
				}
				break;
			case self::NETWORK_PROVIDER_KDDI:
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon']) && isset($_REQUEST['datum'])){
					return true;
				}
				break;
			case self::NETWORK_PROVIDER_SOFTBANK:
				if(isset($_REQUEST['pos']) && isset($_REQUEST['geo'])){
					return true;
				}
				break;
			default:
				if(isset($_REQUEST['lat']) && isset($_REQUEST['lon'])){
					return true;
				}
				break;
		}
		
		return false;
	}
	
	/**
	 * get Location
	 * 位置情報を取得
	 *
	 * @access public
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	function getLocation(){
		switch($this->getVender()){
			case self::NETWORK_PROVIDER_DOCOMO: $this->docomo(); break;
			case self::NETWORK_PROVIDER_KDDI: $this->au(); break;
			case self::NETWORK_PROVIDER_SOFTBANK: $this->softbank(); break;
			default:  $this->pc(); break;
		}
		
		return $this->point;
	}
	
	
	public function setPoint($latitude, $longitude = null){
		if($longitude === NULL){
			$point = $latitude; unset($latitude);
			if($point instanceof Location_Point == TRUE){
				$this->point = $point;
			}elseif(is_array($point) == TRUE){
				$this->point = new Location_Point($point);
			}
		}
		$this->point	= new Location_Point($latitude, $longitude);
	}
	private function setGeometory($geometory){ $this->geometory		= $geo; }
	private function setAccuracy($accuracy){ $this->accuracy		= $acr; }
	
	
	/**
	 * get Point
	 * 位置情報を配列で取得
	 *
	 * @access public
	 * @param void
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	public function getPoint(){
		return $this->point;
	}
	
	/**
	 * get Geometry
	 * 測地系を取得
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	public function getGeometory(){
		if($this->geometory == null){
			$this->getLocation();
		}
		return $this->geometory;
	}
	
	/**
	 * <strong>get Accuracy</strong><br />
	 * 測定精度を取得
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	public function getAccuracy(){
		if($this->accuracy == null){
			$this->getLocation();
		}
		return $this->accuracy;
	}
	
	
	/**
	 * DoCoMo
	 * DoCoMoからの位置情報を取得
	 *
	 * @access privarte
	 * @param void
	 * @return void
	 * @author anon <anon@anoncom.net>
	 */
	function docomo(){
		if(!isset($_REQUEST['AREACODE']) && ((!isset($_GET['lat']) && !isset($_GET['lon'])) && !isset($_GET['pos']))){
			$point = array(35.000000, 139.000000);
			$geo = self::WGA84;
			$acr = 1;
		}else{
			if((isset($_POST['LAT']) && !empty($_POST['LAT'])) && (isset($_POST['LON']) && !empty($_POST['LON'])) && (isset($_POST['XACC']) && !empty($_POST['XACC'])) && (isset($_POST['GEO']) && !empty($_POST['GEO']))){
				// posinfo 処理
				$latitude = ereg_replace('[+-]', '', $_POST['LAT']);
				$longitude = ereg_replace('[+-]', '', $_POST['LON']);
				$latitude = Location_Converter::convertDms2Degree($latitude);
				$longitude = Location_Converter::convertDms2Degree($longitude);
				$point = array($latitude, $longitude);
				$geo = strtolower($_POST['GEO']);	// wgs84
				$acr = $_POST['XACC'];
				
			}elseif(isset($_POST['AREACODE'])){
				// Open i area処理
				$point = $this->getFrom_iArea($_POST['AREACODE']);
				$point[0] = Location_Converter::convertDms2Degree($point[0]);
				$point[1] = Location_Converter::convertDms2Degree($point[1]);
				$point = Location_Converter::convertTokyo2Wgs84($point);
				//$geo = self::TOKYO;
				$gep = self::WGA84;
				$acr = 0;
			}elseif(isset($_GET['pos'])){
				// mova(GPS)処理
				if(isset($_GET['pos'])){
	
					// 測地系
					$geo = $_GET['geo'];
	
					if(preg_match('/^[NS][+\-]?([0-9]{2}\.[0-9]{2}\.[0-9]{2}\.[0-9]{2})[EW]\-?([0-9]{3}\.[0-9]{2}\.[0-9]{1,2}\.[0-9]{1,2})/', $_GET['pos'], $matches)){
						$latitude = $matches[1];	// 北緯
						$longitude = $matches[2];	// 東経
						$point = array($latitude, $longitude);
						$acr = $_GET['X-acc'];	// 精度
					}
				}
			}else{
				// FOMA(GPS)処理
				$latitude = ereg_replace('%2B', '', $_GET['lat']);
				$longitude = ereg_replace('%2B', '', $_GET['lon']);
				$point = array($latitude, $longitude);
				$geo = $_GET['geo'];
				$acr = $_GET['x-acc'];
			}
		}
		$this->setPoint($point);
		$this->setGeometory($geo);
		$this->setAccuracy($acr);
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
		if(!isset($_GET['lat']) && !isset($_GET['lon'])){
			$point = array(35.000000, 139.000000);
			$geo = self::WGA84;
			$acr = 1;
		}else{
			if(empty($_GET['smaj']) && empty($_GET['smin']) && empty($_GET['vert'])){
				// 簡易位置情報処理
				$point = array($_GET['lat'], $_GET['lon']);
				//$geo = $_GET['datum'];
				$geo = self::WGA84;
				$acr = 3;	// 取れないので3にしておく
				
			}else{
				// gpsOne処理
				$latitude = ereg_replace('%2B', '', $_GET['lat']);
				$longitude = ereg_replace('%2B', '', $_GET['lon']);
				
				$smaj = $_GET['smaj'];
				//$geo = $_GET['datum'];
				$geo = self::WGA84;
				
				if ($smaj < 50){
					$acr = 3;
				}elseif($smaj < 300){
					$acr = 2;
				}else{
					$acr = 1;
				}
			}
			$latitude = Location_Converter::convertDms2Degree($latitude);
			$longitude = Location_Converter::convertDms2Degree($longitude);
			$point = array($latitude, $longitude);
		}
		$this->setPoint($point);
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

		if(!isset($_SERVER['HTTP_X_JPHONE_GEOCODE']) && !isset($_GET['pos'])){
			$point = array(35.000000, 139.000000);
			$acr = 1;
		}
		
		if(isset($_GET['pos'])){

			// 測地系
			$geo = $_GET['geo'];

			if(preg_match('/^[NS][+\-]?([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})[EW][+-]?([0-9]{1,3}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})/', $_GET['pos'], $matches)){
				$latitude = $matches[1];	// 北緯
				$longitude = $matches[2];	// 東経
				$acr = $_GET['x-acr'];
				$latitude = Location_Converter::convertDms2Degree($latitude);
				$longitude = Location_Converter::convertDms2Degree($longitude);
				$point = array($latitude, $longitude);
			}
		}else{
			if($_SERVER['HTTP_X_JPHONE_GEOCODE'] == '0000000%1A0000000%1A%88%CA%92%75%8F%EE%95%F1%82%C8%82%B5'){
				$geocode = '3500000%1A1390000%1A%88%CA%92%75%8F%EE%95%F1%82%C8%82%B5';
			}

			list($latitude, $longitude, $address) = split('\%1A', $geocode);

			// 緯度の整形
			$latitude = substr($latitude, 0, 2).'.'.
				substr($latitude, 2, 2).'.'.
				substr($latitude, 4, 2);
			// 経度の整形
			$longitude = substr($longitude, 0, 3).'.'.
				substr($longitude, 3, 2).'.'.
				substr($longitude, 5, 2);
			// 住所のデコード
			$address = urldecode($address);
			// 誤差精度
			//$accuracy = 0;
			$acr = 1;
			// 測地系
			$geo = self::TOKYO;
			$point = array($latitude, $longitude);
			$point = Location_Converter::convertDms2Degree($point);
			$point = Location_Converter::convertTokyo2Wgs84($point);
		}
		
		
		$this->setPoint($point);
		$this->setGeometory($geo);
		$this->setAccuracy($acr);
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
			preg_match('/^[NS][+\-]?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[EW][+\-]?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $_GET['pos'], $matches);
			$latitude = $matches[1];	// 北緯
			$longitude = $matches[2];	// 東経
		}else{
			if(isset($_GET['lat'])){
				$latitude = $_GET['lat'];
			}
			if(isset($_GET['lon'])){
				$longitude = $_GET['lon'];
			}
		}
		$geo = isset($_GET['geo']) ? $_GET['geo'] : self::WGA84;
		$acr = isset($_GET['acr']) ? $_GET['acr'] : 2;

		if(is_float($latitude) == FALSE){
			$latitude = Location_Converter::convertDms2Degree($latitude);
		}
		if(is_float($longitude) == FALSE){
			$longitude = Location_Converter::convertDms2Degree($longitude);
		}
		
		$point = array($latitude, $longitude);
		$this->setPoint($point);
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
		$area = file_get_contents(__I_AREA_DIR__ . '/iarea' . $areacode . '.txt');
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
		$ary = explode(',', $area, 8);
		$latitude = ($ary[6] + $ary[4]) / 7200000;
		$longitude = ($ary[5] + $ary[3]) / 7200000;
		//$latitude = Location_Converter::convertDegree2Dms($latitude);
		//$longitude = Location_Converter::convertDegree2Dms($longitude);
		return array($latitude, $longitude);
	 }



	/**
	 * createLocationLink
	 * 各端末ごとにあわせた位置情報取得用HTMLリンクを出力する。
	 * 
	 * @access public
	 * @param string url, string anchor_string
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function createLocationLink($uri, $str = 'getLocation'){
		switch($this->getModelType()){
			case 'igps':
				$ret = '<a href="' . $uri . '" lcs>' . $str . '</a>'; break;
			case 'iarea':
				$uri = 'http://w1m.docomo.ne.jp/cp/iarea?ecode=OPENAREACODE&msn=OPENAREAKEY&posinfo=1&nl=' . urlencode($uri);
				$ret = '<a href="' . $uri . '">' . $str . '</a>'; break;
			case 'gpsone':
				$uri = 'device:gpsone?url=' . urlencode($uri) . '&ver=1&datum=0&unit=0&acry=0&number=0';
				$ret = '<a href="' . $uri . '">' . $str . '</a>'; break;
			case 'location':
				$uri = 'device:location?url=' . urlencode($uri);
				$ret = '<a href="' . $uri . '">' . $str . '</a>'; break;
			case '2g':
				$ret = '<a href="' . $uri . '" z>' . $str . '</a>'; break;
			case '3g':
				$uri = 'location:auto?url=' . urlencode($url);
				$ret = '<a href="' . $uri . '">' . $str . '</a>'; break;
			default:
				$ret = '<a href="' . $uri . '">' . $str . '</a>'; break;
		}
		return $ret;
	}


	/**
	 * Get Class Version Function
	 * バージョン情報を返す
	 *
	 * @param	void
	 * @return	string version
	 * @access	public
	 */
	public function getVersion(){
		return self::VERSION;
	}


	/**
	 * 
	 *
	 * @param	string $path
	 * @return	bool
	 * @access	private
	 */
	function _file_exists_ex($path, $use_include_path = true){
	    if($use_include_path == false){
	        return file_exists($path);
	    }

	    // check if absolute
	    if(preg_match('/^\//', $path)){
	        return file_exists($path);
	    }

	    $include_path_list = explode(PATH_SEPARATOR, get_include_path());
	    if(is_array($include_path_list) == false){
	        return file_exists($path);
	    }

	    foreach($include_path_list as $include_path){
	        if(file_exists($include_path . DIRECTORY_SEPARATOR . $path)){
	            return true;
	        }
	    }
	    return false;
	}
	
	
	/**
	 * 前方一致
	 * $haystackが$needleから始まるか判定します。
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private static function startsWith($haystack, $needle){
		return strpos($haystack, $needle, 0) === 0;
	}
	
	/**
	 * 後方一致
	 * $haystackが$needleで終わるか判定します。
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private static function endsWith($haystack, $needle){
		$length = (strlen($haystack) - strlen($needle));
		// 文字列長が足りていない場合はFALSEを返します。
		if($length <0) return FALSE;
		return strpos($haystack, $needle, $length) !== FALSE;
	}
	
	/**
	 * 部分一致
	 * $haystackの中に$needleが含まれているか判定します。
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private static function matchesIn($haystack, $needle){
		return strpos($haystack, $needle) !== FALSE;
	}
}


/**
 * 地点情報オブジェクト
 * (内部地点情報は世界測地系(wgs84)のDegree形式で保存されます)
 */
class Location_Point{
	
	private $point;
	
	/**
	 * Constructor
	 *
	 * @param float|string $latitude Latitude
	 * @param float|string $longitude Longitude
	 */
	function __construct($latitude, $longitude = null){
		
		if($longitude === NULL){
			$point = $latitude; unset($latitude);
			if($point instanceof self){
				$this->point = $point->toArray();
				return;
			}elseif(Location_Converter::isValid($point, Location_Converter::FORMAT_LOCAPOINT)){
				list($latitude, $longitude) = Location_Converter::decodeLocaPoint($point);
			}
		}
		
		$this->point = array($latitude, $longitude);
	}
	
	public function toArray(){
		return $this->point;
	}
	
	public function getLatitude(){
		return $this->point[0];
	}

	public function getLongitude(){
		return $this->point[1];
	}
	
	
	
	public function toDegree(){
		return $this->toArray();
	}
	
	public function toDms(){
		$latitude = Location_Converter::convertDegree2Dms($this->pont[0]);
		$longitude = Location_Converter::convertDegree2Dms($this->point[1]);
		return array($latitude, $longitude);
	}
	
	public function toTokyo(){
		return Location_Converter::convertWgs842Tokyo($this->point);
	}
	
	public function toWgs84(){
		return $this->toArray();
	}
	
	public function toLocaPoint(){
		return array(Location_Converter::encodeLocaPoint($this->point[0], $this->point[1]));
	}
}


class Location_Converter{
	
	const FORMAT_DEGREE		= 1;
	const FORMAT_DMS		= 2;
	const FORMAT_LOCAPOINT	= 4;
	
	const __REGEX_PATTERN_DMS = '/^[+\-]?\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/';
	const __REGEX_PATTERN_DEGREE = '/^[+\-]?\d{1,3}\.\d+$/';
	const __REGEX_PATTERN_LOCAPOINT = '/^[A-Z]{2}\d\.[A-Z]{2}\d\.[A-Z]{2}\d\.[A-Z]{2}\d$/';
	
	const WGS84 = 1;
	const TOKYO	= 2;
	
	/**
	 * 入力された位置情報の形式の整合性をチェックします
	 *
	 * @param string|double $value
	 * @param in $flag
	 * @return bool
	 */
	public static function isValid($value, $flag){
		
		switch($flag){
			case self::FORMAT_DEGREE:
				if(preg_match(self::__REGEX_PATTERN_DEGREE, strval($value)) == FALSE){
					return false;
				}
				return is_float($value);
				
			case self::FORMAT_DMS:
				return preg_match(self::__REGEX_PATTERN_DMS, strval($value));
				
			case self::FORMAT_LOCAPOINT:
				return preg_match(self::__REGEX_PATTERN_LOCAPOINT, strval($value));
		}
		return false;
	}

	/**
	 * convert DMS to Degree
	 * DMS形式(dd.mm.ss.sss)の位置情報をDegree形式(dd.mmsss)に変換する
	 * 
	 * @access public
	 * @param string $value
	 * @return float
	 * @author anon <anon@anoncom.net>
	 */
	public static function convertDms2Degree($value){
		if(self::isValid($value, self::FORMAT_DMS) == FALSE){
			return $value;
		}
		
		list($dd, $mm, $ss, $sss) = explode('.', $value);
		// dms = ±dd.mm.ss.sss
		// degree = dd + (mm / 60) + (ss / 3600) + (sss / 360000)
		
		$ret = $dd + ($mm / 60) + ($ss / 3600) + ($sss / 360000);
		$ret = sprintf('%0.6f', $ret);
		return $ret;
	}
	
	
	/**
	 * convert Degree to DMS
	 * Degree形式(dd.mmsss)の位置情報をDMS形式(dd.mm.ss.sss)に変換する
	 * 
	 * @access public
	 * @param float $value
	 * @return string
	 * @author PHPスクリプト無料配布所 :: PHP.TO
	 * @link http://php.to/tips/9/
	 */
	public static function convertDegree2Dms($value){
		if(self::isValid($value, self::FORMAT_DEGREE) == FALSE){
			return $value;
		}
		
		$d = floor($value);
		$m = floor(($value - $d) * 60);
		$s = floor(($value - $d - $m / 60) * 3600);
		$u = floor(($value - $d - $m / 60 - $s / 3600) * 360000);
		$ret = implode('.', array($d, $m, $s, $u));
		return $ret;
	}
	
	
	/**
	 * convert to wgs84 from Tokyo
	 * 日本測地系の座標から世界測地系の座標に変換する
	 *
	 * @access public
	 * @param array $point
	 * @return array($latitude, $longitude)
	 * @author PHPスクリプト無料配布所 :: PHP.TO
	 * @link http://php.to/tips/9/
	 */
	public static function convertTokyo2Wgs84($point) {
		$isDMS = false;
		list($latitude, $longitude) = $point;
		
		if(self::isValid($latitude, self::FORMAT_DMS)){
			$latitude = self::convertDms2Degree($latitude);
			$isDMS = true;
		}
		if(self::isValid($longitude, self::FORMAT_DMS)){
			$longitude = self::convertDms2Degree($longitude);
			$isDMS = true;
		}
		
		$point = array(
			$latitude -
			 $latitude * 0.00010695
			 + $longitude * 0.000017464
			 + 0.0046017,
			$longitude - 
			 $latitude * 0.000046038
			 - $longitude * 0.000083043
			 + 0.010040
		);
		
		if($isDMS == TRUE){
			$point = self::convertDegree2Dms($point);
		}
		return $point;
	}
	
	
	/**
	 * convert to tokyo from wgs84
	 * 世界測地系から日本測地系の座標に変換します
	 *
	 * @access public
	 * @param array $point
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	public static function convertWgs842Tokyo($point) {
		$isDMS = false;
		list($latitude, $longitude) = $point;
		
		if(self::isValid($latitude, self::FORMAT_DMS)){
			$latitude = self::convertDms2Degree($latitude);
			$isDMS = true;
		}
		if(self::isValid($longitude, self::FORMAT_DMS)){
			$longitude = $this->convertDms2Degree($longitude);
			$isDMS = true;
		}
		
		$latitude = 
			$latitude + 
				($latitude * 0.00010695 - $longitude * 0.000017464 - 0.0046017);
		$longitude =  
			$longitude + 
				($latitude * 0.000046038 + $longitude * 0.000083043 - 0.010040);
		
		
		if($isDMS == TRUE){
			return array(self::convertDegree2Dms($latitude), self::convertDegree2Dms($longitude));
		}
		
		return array(sprintf("%0.6f", $latitude), sprintf("%0.6f", $longitude));
	}
	
	
	/**
	 * encode LocaPoint
	 * LocaPoint形式にエンコードする
	 *
	 * @access public
	 * @param array $point
	 * @return string locapoint
	 * @author anon <anon@anoncom.net>
	 * @link http://www.locapoint.com/en/spec.html
	 */
	public static function encodeLocaPoint($point){
		
		// already formated locapoint
		if(self::isValid($point, self::FORMAT_LOCAPOINT)){
			return $point;
		}
		// not support without array
		if(is_array($point) == FALSE) return false;
		
		list($latitude, $longitude) = $point;
		
		if(self::isValid($latitude, self::FORMAT_DMS)){
			$latitude = self::convertDms2Degree($latitude);
		}
		if(self::isValid($longitude, self::FORMAT_DMS)){
			$longitude = self::convertDms2Degree($longitude);
		}
		
		
		
		$latitude_step  = ($latitude + 90)  / 180 * 45697600;
		$longitude_step = ($longitude + 180) / 360 * 45697600;
		
		$locapoint = 
			  chr($latitude_step / 1757600 % 26 + 65)
			. chr($latitude_step / 67600 % 26 + 65)
			. chr($latitude_step / 6760 % 10 + 48)
			. '.'
			. chr($longitude_step / 1757600 % 26 + 65)
			. chr($longitude_step / 67600 % 26 + 65)
			. chr($longitude_step / 6760 % 10 + 48)
			. '.'
			. chr($latitude_step / 260 % 26 + 65)
			. chr($latitude_step / 10 % 26 + 65)
			. chr($latitude_step / 1 % 10 + 48)
			. '.'
			. chr($longitude_step / 260 % 26 + 65)
			. chr($longitude_step / 10 % 26 + 65)
			. chr($longitude_step / 1 % 10 + 48)
			;
		
		return $locapoint;
	}
	
	
	/**
	 * decode LocaPoint
	 * LocaPoint形式からデコードする
	 *
	 * @access public
	 * @param string $value
	 * @return array(double latitude, double longitude)
	 * @author anon <anon@anoncom.net>
	 * @link http://www.locapoint.com/en/spec.html
	 */
	public static function decodeLocaPoint($value){
		
		if(self::isValid($locapoint, self::FORMAT_LOCAPOINT) == FALSE){
			return $value;
		}
		
		$i = 0; $max = strlen($value);
		while($i < $max){
			$char[] = ord(substr($value, $i, 1));
			$i++;
		}
		
		$latitude = floatval((
			(
				  ($char[0] - 65) * 1757600
				+ ($char[1] - 65) * 67600
				+ ($char[2] - 48) * 6760
				+ ($char[8] - 65) * 260
				+ ($char[9] - 65) * 10
				+ ($char[10] - 48)
			) * 180 / 45697600 - 90)
		);
		$longitude = floatval((
			(
				  ($char[4] - 65)  * 1757600
				+ ($char[5] - 65)  * 67600
				+ ($char[6] - 48)  * 6760
				+ ($char[12] - 65) * 260
				+ ($char[13] - 65) * 10
				+ ($char[14] - 48)
			) * 360 / 45697600 - 180)
		);
		
		return array($latitude, $longitude);
	}
	

	/**
	 * <strong>From point of source to point  of distance</strong><br />
	 * 二つの地点の直線距離を返す<br />
	 * (ヒュベニの距離計算式を使用)
	 *
	 * @param array $source point of source
	 * @param array $distance point of distance
	 * @return double unit of value is "meter"
	 * @author anon <anon@anoncom.net>
	 * @link http://www.sandeinc.com/~eguchi/diary/20050403.html
	 * @link http://h2caster.net/home/gpsandpda/hybeny.php
	 */
	public static function distance($source, $distance){
		list($src_latitude, $src_longitude) = $source;
		list($dist_latitude, $dist_longitude) = $distance;
		
		$dp = deg2rad(abs($src_latitude - $dist_latitude));
		$dr = deg2rad(abs($src_longitude - $dist_longitude));
		$p =  deg2rad($lat1 + (($dist_latitude - $src_latitude) / 2));
		$m = 6335439 / sqrt(pow((1 - 0.006694 * pow(sin($p), 2)), 3));
		$n = 6378137 / sqrt(1 - 0.006694 * pow(sin($p), 2));
		//$d = round(sqrt(pow(($m * $dp), 2) + pow(($n * cos($p) * $dr), 2)), 4);
		$d = sqrt(pow(($m * $dp), 2) + pow(($n * cos($p) * $dr), 2));
		
		return $d;
	}
	

	/**
	 * Leave Point
	 * 指定した地点からnメートルはなれた地点の緯度と経度を返す。
	 * 現在地点を中心とし、北へは +$dist_latitude_m 、南へは -$dist_latitude_m 、
	 * 東へは +$dist_longitude_m 、西へは -$dist_longitude_m を指定する。
	 *
	 * @param array(float, float)$degree, double $metre[m]
	 * @return array(float, float)
	 * @author anon <anon@anoncom.net>
	 * @link http://blog.fkoji.com/2006/09142346.html
	 */
	public static function leavePoint($point, $dist_latitude_m = null, $dist_longitude_m = null){
		list($latitude, $longitude) = $point;
		$equator = 6378137;	// 赤道半径
		
		$point[1] = ($dist_longitude_m / ($equator * cos($longitude * (pi() / 180))) + $longitude * (pi() / 180)) * (180 / pi());	// 経度
		$point[0] = ($dist_latitude_m / $equator + $longitude * (pi() / 180)) * (180 / pi());	// 緯度
		
		if($dist_latitude_m == null){
			$point[0] = $latitude;
		}
		if($dist_longitude_m == null){
			$point[1] = $longitude;
		}
		
		if($point[0] < 0){
			$point[0] = $point[0] * -1;
		}
		if($point[1] < 0){
			$point[1] = $point[1] * -1;
		}
		
		return $point;
	}
}


switch($_GET['__loc_']){
	case 'ver':
		$__this = new Location();
		print $__this->getVersion();
		unset($__this);
		exit;
		break;
	case 'info':
		$__this = new Location();
		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		print "<html>\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=EUC-JP;\" />\n<title>Class DeviceType</title>\n</head>\n";
		print "<body bgcolor=\"#ceedfc\" text=\"#000000\" link=\"#000088\">";
		print '<h1>Class Location ver.' . $__this->getVersion() . "</h1><br />\n";
		print "<small><strong>Author:</strong> anon <anon@anoncom.net></small><br />\n";
		print "<small>Copyright(C) ".date('Y')." anon <a href=\"http://anoncom.net/\">http://anoncom.net/</a></small><br />\n";
		print "<br />\n";
		print "<strong>files:</strong><br />\n";
		print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n<tr>\n";
		print "<td width=\"240\" align=\"left\">iArea files (only exists path):</td>\n<td><strong>\n";
		print ($__this->_file_exists_ex(__I_AREA_DIR__)) ? '<font color="#008800">ok</font><br />' : '<font color="#ff0000">ng</font> confirm path: <strong>' . __I_AREA_DIR__ . '</strong> or <a href="http://www.nttdocomo.co.jp/service/imode/make/content/iarea/index.html">download</a><br />';
		print "</strong>\n</td>\n</tr><tr>\n";
		print "<td width=\"240\" align=\"left\">docomo.gps.devicelist.txt:</td>\n<td><strong>\n";
		print ($__this->_file_exists_ex("docomo.gps.devicelist.txt")) ? '<font color="#008800">ok</font><br />' : '<font color="#ff0000">ng</font> <a href="http://labs.anoncom.net/php_dl.php?file=docomo.gps.devicelist.zip&ver=new">download</a><br />';
		print "</strong>\n</td>\n</tr></table>\n";
		print "</body>\n</html>";
		unset($__this);
		exit;
		break;
}
?>
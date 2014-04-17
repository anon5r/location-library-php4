<?php
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
class Location{
	/*
	 * [hint]
	 * Longitude 経度	本初子午線を中心に0～180度まである。本初子午線から東を東経（＋）、西側を西経（－）とする。
	 * Latitude  緯度	赤道が0度、南北にそれぞれ90度まで
	 */

	private $latitude;
	private $longitude;
	private $point;
	private $accure;
	private $geo;


	function Location(){
		$this->longitude = null;
		$this->latitude = null;
		$this->point= null;
	}
	
	
	/**
	 * DMS to Degree
	 * DMS形式(dd.mm.ss.sss)の位置情報をDegree形式(dd.mmsss)に変換する
	 *
	 * @access public
	 * @param string $dms DMS point information
	 * @return float
	 * @author anon <anon@anoncom.net>
	 */
	public static function dms2deg($dms){
		if(empty($dms)){ return null; }
		$ary = explode('.', $dms);
		// dms = ±dd.mm.ss.sss
		// degree = dd + (mm / 60) + (ss / 3600) + (sss / 360000)
		$dd = $ary[0];
		$mm = $ary[1];
		//$ss = (float)($ary[2].'.'.$ary[3]);
		$ss = $ary[2];
		$sss = $ary[3];
		//$deg = $dd + ($mm + ($ss / 60) / 60);
		$deg = $dd + ($mm / 60) + ($ss / 3600) + ($sss / 360000);
		$deg = sprintf("%0.6f", $deg);
		return $deg;
	}


	/**
	 * Degree to DMS
	 * Degree形式(dd.mmsss)の位置情報をDMS形式(dd.mm.ss.sss)に変換する
	 *
	 * @access public
	 * @param float $deg Degree point information
	 * @return string
	 * @author PHPスクリプト無料配布所 :: PHP.TO
	 * @link http://php.to/tips/9/
	 */
	public static function deg2dms($deg){
		if(empty($deg)){ return null; }
		$d = floor($deg);
		$m = floor(($deg - $d) * 60);
		$s = floor(($deg - $d - $m / 60) * 3600);
		$u = floor(($deg - $d - $m / 60 - $s / 3600) * 360000);
		$dms = "$d.$m.$s.$u";
		return $dms;
	}


	/**
	 * Tokyo to wgs84
	 * 日本測地系の座標から世界測地系の座標に変換する
	 *
	 * @access public
	 * @param string $lat 緯度
	 * @param string $lon 経度
	 * @return array
	 * @author PHPスクリプト無料配布所 :: PHP.TO
	 * @link http://php.to/tips/9/
	 */
	public static function tokyo2wgs84($lat, $lon) {
		$dmsPttr = '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/i';
		$lat_dms = 0;	$lon_dms = 0;
		if(preg_match($dmsPttr, $lat)){
			$lat = $this->dms2deg($lat);
			$lat_dms = 1;
		}
		if(preg_match($dmsPttr, $lon)){
			$lon = $this->dms2deg($lon);
			$lon_dms = 1;
		}
		$ary = array($lat - $lat * 0.00010695 + $lon * 0.000017464 + 0.0046017, $lon - $lat * 0.000046038 - $lon * 0.000083043 + 0.010040);
		if($lat_dms == 1){
			$ary[0] = $this->deg2dms($ary[0]);
		}
		if($lon_dms == 1){
			$ary[1] = $this->deg2dms($ary[1]);
		}
		return $ary;
	}


	/**
	 * wgs84 to tokyo
	 * 世界測地系の座標から日本測地系の座標に変換する
	 *
	 * @access public
	 * @param string $latitude, string $longitude
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	public static function wgs842tokyo($lat, $lon) {
		
		$dmsPttr = '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/i';
		$lat_dms = 0;	$lon_dms = 0;
		if(preg_match($dmsPttr, $lat)){
			$lat = $this->dms2deg($lat);
			$lat_dms = 1;
		}
		if(preg_match($dmsPttr, $lon)){
			$lon = $this->dms2deg($lon);
			$lon_dms = 1;
		}
		$ary = array($lat + ($lat * 0.00010695 - $lon * 0.000017464 - 0.0046017), $lon + ($lat * 0.000046038 + $lon * 0.000083043 - 0.010040));
		if($lat_dms == 1){
			$ary[0] = $this->deg2dms($ary[0]);
		}
		if($lon_dms == 1){
			$ary[1] = $this->deg2dms($ary[1]);
		}
		$ary[0] = sprintf("%0.6f", $ary[0]);
		$ary[1] = sprintf("%0.6f", $ary[1]);
		return $ary;
	}



	/**
	 * Leave Point
	 * 指定した地点からnメートルはなれた地点の
	 * 緯度と経度を返す。
	 * 現在地点を中心とし、北へは+$leaveLatitude、南へは-$leaveLatitude、
	 * 東へは+$leaveLongitude、西へは-$leaveLongitudeを指定する。
	 *
	 * @param array(float, float)$degree, double $metre[m]
	 * @return array(float, float)
	 * @author anon <anon@anoncom.net>
	 * @link http://blog.fkoji.com/2006/09142346.html
	 */
	public static function LeavePoint($location, $leaveLatitude = null, $leaveLongitude = null){
		list($lat, $lon) = $location;
		$equator = 6378137;	// 赤道半径

		$point[1] = ($leaveLongitude / ($equator * cos($lat * (pi() / 180))) + $lon * (pi() / 180)) * (180 / pi());	// 経度
		$point[0] = ($leaveLatitude / $equator + $lat * (pi() / 180)) * (180 / pi());	// 緯度

		if($leaveLatitude == null){
			$point[0] = $lat;
		}
		if($leaveLongitude == null){
			$point[1] = $lon;
		}

		if($point[0] < 0){
			$point[0] = $point[0] * -1;
		}
		if($point[1] < 0){
			$point[1] = $point[1] * -1;
		}

		return $point;
	}


	/**
	 * from Point to Point Distance
	 * 二つの地点の直線距離を返す
	 * (ヒュベニの距離計算式を使用)
	 *
	 * @param array
	 * @return array
	 * @author anon <anon@anoncom.net>
	 * @link http://www.sandeinc.com/~eguchi/diary/20050403.html
	 * @link http://h2caster.net/home/gpsandpda/hybeny.php
	 */
	function Distance($location1, $location2){
		list($lat1, $lon1) = $location1;
		list($lat2, $lon2) = $location2;
		$dp = deg2rad(abs($lat1 - $lat2));
		$dr = deg2rad(abs($lon1 - $lon2));
		$p =  deg2rad($lat1 + (($lat2 - $lat1) / 2));
		$m = 6335439 / sqrt(pow((1 - 0.006694 * pow(sin($p), 2)), 3));
		$n = 6378137 / sqrt(1 - 0.006694 * pow(sin($p), 2));
		//$d = round(sqrt(pow(($m * $dp), 2) + pow(($n * cos($p) * $dr), 2)), 4);
		$d = sqrt(pow(($m * $dp), 2) + pow(($n * cos($p) * $dr), 2));
		return $d;
	}



	/**
	 * <strong>encode LocaPoint</strong><br />
	 * LocaPoint形式にエンコードする<br />
	 *
	 * @access public
	 * @param double latitude, double longitude
	 * @return string locapoint
	 * @author anon <anon@anoncom.net>
	 * reference http://www.locapoint.com/en/spec.html
	 */
	function encLocaPoint($latitude, $longitude){

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
	 * <strong>decode LocaPoint</strong><br />
	 * LocaPoint形式からデコードする<br />
	 *
	 * @access public
	 * @param string locapoint
	 * @return array(double latitude, double longitude)
	 * @author anon <anon@anoncom.net>
	 * reference http://www.locapoint.com/en/spec.html
	 */
	function decLocaPoint($locapoint){

		$i = 0;
		$max = strlen($locapoint);
		while($i < $max){
			$ary[] = substr($locapoint, $i, 1);
			$ary[$i] = ord($ary[$i]);
			$i++;
		}

		$latitude = (double)(
			(
				  ($ary[0] - 65) * 1757600
				+ ($ary[1] - 65) * 67600
				+ ($ary[2] - 48) * 6760
				+ ($ary[8] - 65) * 260
				+ ($ary[9] - 65) * 10
				+ ($ary[10] - 48)
			) * 180 / 45697600 - 90);
		$longitude = (double)(
			(
				  ($ary[4]  - 65) * 1757600
				+ ($ary[5]  - 65) * 67600
				+ ($ary[6]  - 48) * 6760
				+ ($ary[12] - 65) * 260
				+ ($ary[13] - 65) * 10
				+ ($ary[14] - 48)
			) * 360 / 45697600 - 180);

		return array($latitude, $longitude);
	}


	/**
	 * <strong>LocaPoint to LatitudeLongitude</strong><br />
	 * wrapper
	 */
	function locapoint2latlon($locapoint){
		return $this->decLocaPoint($locapoint);
	}


	/**
	 * <strong>LocaPoint to LatitudeLongitude</strong><br />
	 * wrapper
	 */
	function latlon2locapoint($latitude, $longitude){
		return $this->encLocaPoint($latitude, $longitude);
	}



	function setPoint($lat, $lon){ $this->_point = array($lat, $lon); }
	function setLat($lat){ $this->_lat = $lat; }
	function setLon($lon){ $this->_lon = $lon; }
	function setGeo($geo){ $this->_geo = $geo; }
	function setAcr($acr){ $this->_acr = $acr; }


	/**
	 * <strong>get Lat</strong><br />
	 * Wrapper
	 *
	 * reference to: getLatitude
	 */
	function getLat(){
		return $this->getLatitude();
	}
	/**
	 * <strong>get Latitude</strong><br />
	 * 緯度を取得
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function getLatitude(){
		if(is_null($this->_lat)){
			$this->getLocation();
		}
		return $this->_lat;
	}

	/**
	 * <strong>get Lon</strong><br />
	 * Wrapper
	 *
	 * reference to: getLongitude
	 */
	function getLon(){
		return $this->getLongitude();
	}
	/**
	 * <strong>get Longitude</strong><br />
	 * 経度を取得
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function getLongitude(){
		if(is_null($this->_lon)){
			$this->getLocation();
		}
		return $this->_lon;
	}

	/**
	 * <strong>get Point</strong><br />
	 * 位置情報を配列で取得
	 *
	 * @access public
	 * @param void
	 * @return array
	 * @author anon <anon@anoncom.net>
	 */
	function getPoint(){
		if(is_null($this->_point)){
			$this->getLocation();
		}
		return $this->_point;
	}

	/**
	 * <strong>get Geometry</strong><br />
	 * 測地系を取得
	 *
	 * @access public
	 * @param void
	 * @return string
	 * @author anon <anon@anoncom.net>
	 */
	function getGeo(){
		$this->getLocation();
		return $this->_geo;
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
	function getAcr(){
		$this->getLocation();
		return $this->_acr;
	}



	/**
	 * <b>Get Class Version Function</b><br />
	 * バージョン情報を返す
	 *
	 * @param	void
	 * @return	string version
	 * @access	public
	 */
	function getVersion(){
		return $this->_version;
	}


	/**
	 * <b></b><br />
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
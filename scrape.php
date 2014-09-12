<?php
error_reporting(E_ALL ^ E_NOTICE);

//phpinfo(); exit;


/**
 * Curl based HTTP Client 
 * Simple but effective OOP wrapper around Curl php lib.
 * Contains common methods needed 
 * for getting data from url, setting referrer, credentials, 
 * sending post data, managing cookies, etc.
 * 
 * Samle usage:
 * $curl = &new Curl_HTTP_Client();
 * $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
 * $curl->set_user_agent($useragent);
 * $curl->store_cookies("/tmp/cookies.txt");
 * $post_data = array('login' => 'pera', 'password' => 'joe');
 * $html_data = $curl->send_post_data(http://www.foo.com/login.php, $post_data);
 */
class Curl_HTTP_Client
{
	/**
	 * Curl handler
	 * @access private
	 * @var resource
	 */
	var $ch ;

	/**
	 * set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	var $debug = false;

	/**
	 * Contain last error message if error occured
	 * @access private
	 * @var string
	 */
	var $error_msg;


	/**
	 * Curl_HTTP_Client constructor
	 * @param boolean debug
	 * @access public
	 */
	function Curl_HTTP_Client($debug = false)
	{
		$this->debug = $debug;
		$this->init();
	}
	
	/**
	 * Init Curl session	 
	 * @access public
	 */
	function init()
	{
		// initialize curl handle
		$this->ch = curl_init();

		//set various options

		//set error in case http return code bigger than 300
		curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

		// allow redirects
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		
		// use gzip if possible
		curl_setopt($this->ch,CURLOPT_ENCODING , 'gzip, deflate');

		// do not veryfy ssl
		// this is important for windows
		// as well for being able to access pages with non valid cert
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	}

	/**
	 * Set username/pass for basic http auth
	 * @param string user
	 * @param string pass
	 * @access public
	 */
	function set_credentials($username,$password)
	{
		curl_setopt($this->ch, CURLOPT_USERPWD, "$username:$password");
	}

	/**
	 * Set referrer
	 * @param string referrer url 
	 * @access public
	 */
	function set_referrer($referrer_url)
	{
		curl_setopt($this->ch, CURLOPT_REFERER, $referrer_url);
	}

	/**
	 * Set client's useragent
	 * @param string user agent
	 * @access public
	 */
	function set_user_agent($useragent)
	{
		curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
	}

	/**
	 * Set to receive output headers in all output functions
	 * @param boolean true to include all response headers with output, false otherwise
	 * @access public
	 */
	function include_response_headers($value)
	{
		curl_setopt($this->ch, CURLOPT_HEADER, $value);
	}

	function include_response_headers_only()
	{
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_NOBODY, true);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1); 
	}


	/**
	 * Set proxy to use for each curl request
	 * @param string proxy
	 * @access public
	 */
	function set_proxy($proxy)
	{
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
	}
	
	function set_headers($headers)
	{
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 * Send post data to target URL	 
	 * return data returned from url or false if error occured
	 * @param string url
	 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 10)
	 * @return string data
	 * @access public
	 */
	function send_post_data($url, $postdata, $ip=null, $timeout=10)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);


		//generate post string
		$post_array = array();
		if(is_array($postdata))
		{		
			foreach($postdata as $key=>$value)
			{
				$post_array[] = urlencode($key) . "=" . urlencode($value);
			}

			$post_string = implode("&",$post_array);

			if($this->debug)
			{
				echo "Url: $url\nPost String: $post_string\n";
			}
		}
		else 
		{
			$post_string = $postdata;
		}

		// set post string
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * fetch data from target URL	 
	 * return data returned from url or false if error occured
	 * @param string url	 
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return string data
	 * @access public
	 */
	function fetch_url($url, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET,true);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Fetch data from target URL
	 * and store it directly to file	 	 
	 * @param string url	 
	 * @param resource value stream resource(ie. fopen)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return boolean true on success false othervise
	 * @access public
	 */
	function fetch_into_file($url, $fp, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);

		// store data into file rather than displaying it
		curl_setopt($this->ch, CURLOPT_FILE, $fp);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Send multipart post data to the target URL	 
	 * return data returned from url or false if error occured
	 * (contribution by vule nikolic, vule@dinke.net)
	 * @param string url
	 * @param array assoc post data array ie. $foo['post_var_name'] = $value
	 * @param array assoc $file_field_array, contains file_field name = value - path pairs
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 30 sec)
	 * @return string data
	 * @access public
	 */
	function send_multipart_post_data($url, $postdata, $file_field_array=array(), $ip=null, $timeout=30)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL, $url);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		// disable Expect header
		// hack to make it working
		$headers = array("Expect: ");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

		// initialize result post array
		$result_post = array();

		//generate post string
		$post_array = array();
		$post_string_array = array();
		if(!is_array($postdata))
		{
			return false;
		}

		foreach($postdata as $key=>$value)
		{
			$post_array[$key] = $value;
			$post_string_array[] = urlencode($key)."=".urlencode($value);
		}

		$post_string = implode("&",$post_string_array);


		if($this->debug)
		{
			echo "Post String: $post_string\n";
		}

		// set post string
		//curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		// set multipart form data - file array field-value pairs
		if(!empty($file_field_array))
		{
			foreach($file_field_array as $var_name => $var_value)
			{
				if(strpos(PHP_OS, "WIN") !== false) $var_value = str_replace("/", "\\", $var_value); // win hack
				$file_field_array[$var_name] = "@".$var_value;
			}
		}

		// set post data
		$result_post = array_merge($post_array, $file_field_array);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $result_post);


		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Set file location where cookie data will be stored and send on each new request
	 * @param string absolute path to cookie file (must be in writable dir)
	 * @access public
	 */
	function store_cookies($cookie_file)
	{
		// use cookies on each request (cookies stored in $cookie_file)
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
	}
	
	/**
	 * Set custom cookie
	 * @param string cookie
	 * @access public
	 */
	function set_cookie($cookie)
	{		
		curl_setopt ($this->ch, CURLOPT_COOKIE, $cookie);
	}

	/**
	 * Get last URL info 
	 * usefull when original url was redirected to other location	
	 * @access public
	 * @return string url
	 */
	function get_effective_url()
	{
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

	/**
	 * Get http response code	 
	 * @access public
	 * @return int
	 */
	function get_http_response_code()
	{
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

	/**
	 * Return last error message and error number
	 * @return string error msg
	 * @access public
	 */
	function get_error_msg()
	{
		$err = "Error number: " .curl_errno($this->ch) ."\n";
		$err .="Error message: " .curl_error($this->ch)."\n";

		return $err;
	}
	
	/**
	 * Close curl session and free resource
	 * Usually no need to call this function directly
	 * in case you do you have to call init() to recreate curl
	 * @access public
	 */
	function close()
	{
		//close curl session and free up resources
		curl_close($this->ch);
	}
}



// EXAMPLE...
/** 
 * @version $Id$ 
 * @package dinke.net 
 * @copyright &copy; 2005 Dinke.net 
 * @author Dragan Dinic <dragan@dinke.net> 
 */ 
/*
require_once("curl_client.php"); 

$curl = &new Curl_HTTP_Client(); 

//pretend to be IE6 on windows 
$useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"; 
$curl->set_user_agent($useragent); 

//uncomment next two lines if you want to manage cookies 
//$cookies_file = "/tmp/cookies.txt"; 
//$curl->store_cookies($cookies_file); 

//Uncomment next line if you want to set credentials 
//$curl->set_credentials($username, $password); 

//Uncomment next line if you want to set specific referrer 
//$curl->set_referrer("http://my.referrer.url"); 

//if you want to send some post data 
//form post data array like this one 
$post_data = array('login' => 'pera', 'password' => 'joe', 'other_foo_field' => 'foo_value'); 
//and send request to http://www.foo.com/login.php. Result page is stored in $html_data string 
$html_data = $curl->send_post_data("http://www.foo.com/login.php", $post_data); 
  
//You can also fetch data from somewhere using get method! 
//Fetch html from url  
$html_data = $curl->fetch_url("http://www.foo.com/foobar.php?login=pera&password=joe&other_foo_field=foo_value"); 

//if you have more than one IP on your server,  
//you can also bind to specific IP address like ... 
//$bind_ip = "192.168.0.1"; 
//$curl->fetch_url("http://www.foo.com/login.php", $bind_ip); 
//$html_data = $curl->send_post_data("http://www.foo.com/login.php", $post_data, $bind_ip);
*/






function curl($url,$referrer=0,$post=0,$cookies=0,$auth=0,$headersOnly=0,$debug=0){
	
	$curl = new Curl_HTTP_Client();
	
	if ($debug) $curl->debug = 0;
	
	//$url = 'http://web-sniffer.net/?url='.urlencode($url).'&rawhtml=yes&submit=Submit&http=1.1&gzip=yes&type=GET&uak=1';
	
	//print '<br><br>URL:'.$url.'<br>';
	
	//pretend to be IE6 on windows 
	$useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"; 
	$curl->set_user_agent($useragent); 
	
	//uncomment next two lines if you want to manage cookies 
	if ($cookies!=0){
		$cookies_file = "cookies/cookies.txt"; 
		$curl->store_cookies($cookies_file); 
	}
	
	//Uncomment next line if you want to set credentials 
	if ($auth!=0){
		$username = $auth[0];
		$password = $auth[1];
		$curl->set_credentials($username, $password); 
	}
	
	if ($headersOnly){
		$curl->include_response_headers_only();
	}
	
	//Uncomment next line if you want to set specific referrer 
	if ($referrer!=0){
		
		$curl->set_referrer($referrer); 
		$referrerStr='Referer: '.$str;
		
	} else {
		
		//$referrer='Referer: ';
		$referrerStr='';
	
	}
	
	$host = explode('/',$url);
	$host = $host[2];
	
	//Set Headers
	$headers=array('GET / HTTP/1.1','Host: '.$host,'Connection: close','User-Agent: '.$useragent,'Accept-Encoding: gzip','Accept-Charset: ISO-8859-1,UTF-8;q=0.7,*;q=0.7','Cache-Control: no','Accept-Language: de,en;q=0.7,en-us;q=0.3',$referrerStr);
	
	$curl->set_headers($headers);
	
	
	//if you want to send some post data 
	//form post data array like this one 
	if ($post!=0){
		$post_data = $post; // $post = array('login' => 'pera', 'password' => 'joe', 'other_foo_field' => 'foo_value'); 
		//and send request to http://www.foo.com/login.php. Result page is stored in $html_data string 
		$html_data = $curl->send_post_data($url, $post_data); 
	} else {
		$html_data = $curl->fetch_url($url);
	}
	  
	//You can also fetch data from somewhere using get method! 
	//Fetch html from url  
	//$html_data = $curl->fetch_url("http://www.foo.com/foobar.php?login=pera&password=joe&other_foo_field=foo_value"); 
	
	//if you have more than one IP on your server,  
	//you can also bind to specific IP address like ... 
	//$bind_ip = "192.168.0.1"; 
	//$curl->fetch_url("http://www.foo.com/login.php", $bind_ip); 
	//$html_data = $curl->send_post_data("http://www.foo.com/login.php", $post_data, $bind_ip);
	
	$curl->close();
	
	if ($curl->debug || $_REQUEST['debug']==1) { 
		print '<textarea>'.$html_data.'</textarea>'; //exit; 
	}
	
	return $html_data;

}

function parseData($startString,$endString,$data,$stripTags='0'){
	//print $line;
	$parsedData=explode(html_entity_decode($startString),html_entity_decode($data),2);
	$parsedData=explode(html_entity_decode($endString),$parsedData[1],2);
	if ($stripTags) return trim(strip_tags($parsedData[0]));
	return trim($parsedData[0]);
}

function parseXML($element,$data){
	$startString='<'.$element.'>';
	$endString='</'.$element.'>';
	$parsedData=explode($startString,$data);
	$parsedData=explode($endString,$parsedData[1]);
	return $parsedData[0];
}

// collectData: Parses Data and Explodes it into an array, which is returned
function collectData($pre,$post,$split,$data){

	$list = parseData($pre,$post,$data);
	$list = explode($split,$list);
	return $list;
	
}

// returns array(link url,contents between <a> and </a>)
function getLink($input) {
	$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>"; 
	if(preg_match_all("/$regexp/siU", $input, $matches)) { 
		# $matches[2] = array of link addresses 
		# $matches[3] = array of link text - including HTML code 
		return(array($matches[2][0],$matches[3][0]));
	}
}

// scrape logic

$cities = Array("Alameda",
"Albany",
"Berkeley",
"Castro Valley",
"Dublin",
"Emeryville",
"Fremont",
"Hayward",
"Livermore",
"Newark",
"Oakland",
"Pleasanton",
"San Leandro",
"San Lorenzo",
"Union City",
"Antioch",
"Brentwood",
"Concord",
"Danville",
"El Cerrito",
"El Sobrante",
"Hercules",
"Lafayette",
"Martinez",
"Pinole",
"Pittsburg",
"Pleasant Hill",
"San Pablo",
"San Ramon",
"Walnut Creek",
"Corte Madera",
"Larkspur",
"Mill Valley",
"Novato",
"San Rafael",
"Sausalito",
"Tiburon",
"Carmichael",
"Citrus Heights",
"Elk Grove",
"Fair Oaks",
"Folsom",
"North Highlands",
"Orangevale",
"Rancho Cordova",
"Sacramento",
"San Francisco",
"Belmont",
"Burlingame",
"Daly City",
"Menlo Park",
"Millbrae",
"Pacifica",
"Palo Alto",
"Redwood City",
"San Bruno",
"San Mateo",
"South San Francisco",
"Campbell",
"Cupertino",
"Gilroy",
"Los Altos",
"Los Gatos",
"Milpitas",
"Morgan Hill",
"Mountain View",
"Palo Alto",
"San Jose",
"Santa Clara",
"Sunnyvale",
"Benicia",
"Dixon",
"Fairfield",
"Suisun City",
"Vacaville",
"Vallejo",
"Glen Ellen",
"Petaluma",
"Rohnert Park",
"Santa Rosa",
"Sonoma");
$i = 0;
$city_data = [];
?>
<p>Result CSV:</p>
<textarea rows="20" cols="40"><?
foreach ($cities as $city) {
	$city = str_replace(' ', '-', $city);
	$url = 'http://www.city-data.com/city/'.$city.'-California.html';
	$html = curl($url,$referrer=0,$post=0,$cookies=0,$auth=0,$headersOnly=0,$debug=0);
	$hi = parseData('household income in 2012:</b> ',' (',$html);
	$pci = parseData('per capita income in 2012:</b> ',' (',$html);
	$population = parseData('Population in 2012:</b> ',' (',$html);
	$city_data[$i] = Array();
	$city_data[$i] = ["city" => $city, "pcincome" => $pci, "pop" => $population];
	?><?='"'.$city.'","'.$pcincome."\"\n"?><?
	$i++; 
	// if ($i > 1) break;
	// Takes longer but helps ensure IP isn't 
	// black listed for too many requests
	usleep(rand( 200000 , 500000)); // .2-.5 seconds per request
}
?>
</textarea>
<p>Sanity Check:</p>
<p>
<?

foreach ($city_data as $data) {
	$city = str_replace(' ', '-', $data['city']);
	$url = 'http://www.city-data.com/city/'.$city.'-California.html';
	// $html = curl($url,$referrer=0,$post=0,$cookies=0,$auth=0,$headersOnly=0,$debug=0);
	// $hi = parseData('household income in 2012:</b> ',' (',$html);
	?>
	<?=$data['city'].' ('.$data['pcincome'].')  - <a target="_blank" href="'.$url.'">'.$url.'</a> <br>'?>
	<?
}

?>
</p>

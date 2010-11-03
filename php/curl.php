<?php

define ("CURLOPT_URL",1);
define ("CURLOPT_USERPWD",2);
define ("CURLOPT_HTTPHEADER",3);
define ("CURLOPT_POSTFIELDS",4);
define ("CURLOPT_CONNECTTIMEOUT",5);
define ("CURLOPT_TIMEOUT",6);
define ("CURLOPT_RETURNTRANSFER",7);
define ("CURLOPT_COOKIE",8);
define ("CURLOPT_NOBODY",9);
define ("CURLOPT_USERAGENT",10);
define ("CURLOPT_HEADER",11);
define ("CURLINFO_HTTP_CODE",12);
define ("CURLOPT_FOLLOWLOCATION",13);
define ("CURLOPT_MAXREDIRS",14);

define ("CURLINFO_CONTENT_TYPE", "content-type");

global $headers_raw;

function curl_init() {    
	global $curl_stuff;
	$id=time().rand(100,100000);
	$curl_stuff[$id]=array();
	return($id);
}

function curl_setopt_array($sess, $options) {
	global $curl_stuff;
	$curl_stuff[$sess]=$options;
}

function curl_exec ($sess) {
	global $curl_stuff;
	global $headers_raw;
	$url=$curl_stuff[$sess][CURLOPT_URL];

	$header="";

	if (isset($curl_stuff[$sess][CURLOPT_POSTFIELDS])) {
		$method="POST";
		$content=$curl_stuff[$sess][CURLOPT_POSTFIELDS];
	} else {
		$method="GET";
	}
	if (isset($curl_stuff[$sess][CURLOPT_NOBODY]) && $curl_stuff[$sess][CURLOPT_NOBODY]) {
		$method = "HEAD";
	}

	if (isset($curl_stuff[$sess][CURLOPT_HTTPHEADER]) && (is_array($curl_stuff[$sess][CURLOPT_HTTPHEADER]))) {
     	    foreach ($curl_stuff[$sess][CURLOPT_HTTPHEADER] as $value) {
		if (!preg_match("/POST/", $value) && !preg_match("/Content-Length:/", $value) && !preg_match("/Python/", $value)) {	
            	    $header.="$value\r\n";
		}	
      	    }
	}
	if (isset($curl_stuff[$sess][CURLOPT_USERPWD]) && ($curl_stuff[$sess][CURLOPT_USERPWD] == ":")) {
		$curl_stuff[$sess][CURLOPT_USERPWD]="";
	}

	if (isset($curl_stuff[$sess][CURLOPT_USERPWD])) {
		$header.='Authorization: Basic '.base64_encode($curl_stuff[$sess][CURLOPT_USERPWD])."\r\n";
	}
	
	if(isset($curl_stuff[$sess][CURLOPT_COOKIE])) {
	    $header .= 'Cookie: ' . $curl_stuff[$sess][CURLOPT_COOKIE] . "\r\n";
	}

        if(isset($curl_stuff[$sess][CURLOPT_USERAGENT])) {
	    $http['user_agent'] = $curl_stuff[$sess][CURLOPT_USERAGENT];
        }

	if(isset($curl_stuff[$sess][CURLOPT_TIMEOUT])) {
		$http['timeout'] = $curl_stuff[$sess][CURLOPT_TIMEOUT];
    	}

	if(!isset($curl_stuff[$sess][CURLOPT_FOLLOWLOCATION])) {
		$curl_stuff[$sess][CURLOPT_FOLLOWLOCATION] = false;
    }
	
	if(!isset($curl_stuff[$sess][CURLOPT_MAXREDIRS])) {
		$curl_stuff[$sess][CURLOPT_MAXREDIRS] = 99;
    }	
	
	$http=array('method' => $method);
	if (isset($header)) {
		$http['header']=$header;
	}
	if (isset($content)) {
		$http['content']=$content;
	}
	$params=array('http' => $http);
	$context=stream_context_create($params);
	if (!$result=file_get_contents_follow($url,false,$context, $curl_stuff[$sess][CURLOPT_FOLLOWLOCATION], $curl_stuff[$sess][CURLOPT_MAXREDIRS])) {
		if (isset($headers_raw[0])) {
			$result=$headers_raw[0];
		}
	}
	
	$curl_stuff[$sess]['headers'] = $headers_raw;
	
	if (isset($curl_stuff[$sess][CURLOPT_HEADER])) {
		  $data = '';
          foreach ($headers_raw as $value) {
                  $data.="$value\r\n";
          }
          $result=$data;
   	}
	//_debug("BLA: " . $header . "\n");
	//$out.=$url."\n".$header."\n".$content."\n".$method."\n".$result."\n";;
	return $result;
}

function file_get_contents_follow($filename, $use_include_path = false, $context = null, $follow = false , $maxredirs = 99 ) {
	global $headers_raw;
	$content = @file_get_contents($filename, $use_include_path, $context);

	$headers_raw = $http_response_header;
	
	//Shouldwe follow header redirects?
	if ($follow && $maxredirs>0) {
		$headers = array();
		//Translate raw headers to associative array of header key/calue pairs
		foreach ($headers_raw as $header) {
			$temp = split(':', $header, 2);
			if (count($temp)==2) {
				$headers[trim($temp[0])] = trim($temp[1]);
			}
		}
		//Any header redirects left?
		if (isset($headers['Location'])) {
			$url = $headers['Location'];
			if (!preg_match('/^https?:\/\//i', $url)) {
				if (substr ($url, 0, 1) != '/') {
					$url = '/' . $url;
				}
				preg_match('/^(https?:\/\/[^\/]+)/i', $filename, $match);
				$url = $match[1] . $url;
			}
			$content = file_get_contents_follow($filename, $use_include_path, $context, true, $maxredirs--);
		}
	}
	return $content;
}

function curl_getinfo($sess, $ch) {
	global $curl_stuff;
	$value = null;
	if($ch === 12 ) {
	    $values = explode(" ", $curl_stuff[$sess]['headers'][0]);
	    $value = $values[1];
	} else {
	    foreach ($curl_stuff[$sess]['headers'] as $header) {
		$split = explode(":", $header);
		if (count($split)==2 && strtolower($split[0])==$ch) {
			$value = strtolower(trim($split[1]));
			break;
		}
	    }
	}
	return $value;
}

function curl_close($sess) {
	global $curl_stuff;
	$curl_stuff[$sess]=array();
}

?>

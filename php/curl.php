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

	$url=$curl_stuff[$sess][CURLOPT_URL];

	$header="";

	if ($curl_stuff[$sess][CURLOPT_POSTFIELDS]) {
		$method="POST";
		$content=$curl_stuff[$sess][CURLOPT_POSTFIELDS];
	} else {
		$method="GET";
	}

	if (is_array($curl_stuff[$sess][CURLOPT_HTTPHEADER])) {
     foreach ($curl_stuff[$sess][CURLOPT_HTTPHEADER] as $value) {
		  if (!preg_match("/POST/", $value) && !preg_match("/Content-Length:/", $value) && !preg_match("/Python/", $value)) {	
            $header.="$value\r\n";
		  }	
      }
	}
	if ($curl_stuff[$sess][CURLOPT_USERPWD] == ":") {
		$curl_stuff[$sess][CURLOPT_USERPWD]="";
	}

	if ($curl_stuff[$sess][CURLOPT_USERPWD]) {
		$header.='Authorization: Basic '.base64_encode($curl_stuff[$sess][CURLOPT_USERPWD])."\r\n";
	}
	
	if($curl_stuff[$sess][CURLOPT_COOKIE]) {
	    $header .= 'Cookie: ' . $curl_stuff[$sess][CURLOPT_COOKIE] . "\r\n";
    }

    if($curl_stuff[$sess][CURLOPT_USERAGENT]) {
	    $http['user_agent'] = $curl_stuff[$sess][CURLOPT_USERAGENT];
    }

	if($curl_stuff[$sess][CURLOPT_TIMEOUT]) {
		$http['timeout'] = $curl_stuff[$sess][CURLOPT_TIMEOUT];
    }


	if ($header || $content) {
		$http=array('method' => $method);
		if ($header) {
			$http['header']=$header;
		}
		if ($content) {
    		$http['content']=$content;
    	}
		$params=array('http' => $http);
		$context=stream_context_create($params);
		if (!$result=file_get_contents($url,false,$context)) {
			$result=$http_response_header[0];
		}
	} else {
	    $params=array('http' => $http);
		$context=stream_context_create($params);
        if (!$result=file_get_contents($url,false,$context)) {
                $result=$http_response_header[0];
        }
	}
    if ($curl_stuff[$sess][CURLOPT_HEADER] === TRUE) {			
          foreach ($http_response_header as $value) {
                  $data.="$value\r\n";
          }
          $result=$data;
   }
	$out.=$url."\n".$header."\n".$content."\n".$method."\n".$result."\n";;
	return ($result);
}

function curl_close($sess) {
	$curl_stuff[$sess]="";
}

?>

<?php
function sendmail($msg, $subject) {
    global $config_values;

    $emailAddress = $config_values['Settings']['Email Address'];

	if(!empty($emailAddress)) {
		$email = new PHPMailer();
		
		$email->From     = "$emailAddress";
		$email->FromName = "TorrentWatch-X";
		$email->AddAddress("$emailAddress");
		$email->Subject  = $subject;

		$email->Host     = $config_values['Settings']['SMTP Server'];
		$email->Mailer   = "smtp";
		
		$mail = @file_get_contents("templates/email.tpl");
		$mail = str_replace('[MSG]', $msg, $mail);
		if (empty($mail)) {
			$mail = $msg;
		}
		$email->Body = $mail;
		$email->Send();
    }
}

function run_script($param, $torrent, $error = "") {
    global $config_values;
    $torrent = escapeshellarg($torrent);
    $error = escapeshellarg($error);
    $script = $config_values['Settings']['Script'];
    if($script) {
        if(!is_file($script)) {
            $msg = "The configured script is not a single file. Parameters are not allowed because of security reasons.";
            $subject = "TorrentWatch-X: security error";
            sendmail($msg, $subject);
            return;
        }
        _debug("Running $script $param $torrent $error \n", -1);
        exec("$script $param $torrent $error 2>&1", $response, $return);
        if($return && $config_values['Settings']['Email Address']) {

            $msg = "Something went wrong while running $script:\n";
            foreach($response as $line) {
              $msg .= $line . "\n";
            }
            $msg.= "\n";
            $msg.= "Please read 'https://code.google.com/p/torrentwatch-x/wiki/Script' for more info about how to make a compatible script."; 
            
            _debug("$msg\n");
            $subject = "TorrentWatch-X: $script returned error.";
            sendmail($msg, $subject);
        }
    }
}



function check_for_cookies($url) {
    if($cookies = stristr($url, ':COOKIE:')) {
      $url = rtrim(substr($url, 0, -strlen($cookies)), '&');
      $cookies = strtr(substr($cookies, 8), '&', ';');
      return array('url' => $url, 'cookies' => $cookies);
    } 
}

function torInfo($torHash) {
    global $config_values;

    switch($config_values['Settings']['Client']) {
        case 'Transmission':
		$request = array('arguments' => array('fields' => array('id', 'leftUntilDone', 'hashString',
                    'totalSize', 'uploadedEver', 'downloadedEver', 'status', 'peersSendingToUs',
                    'peersGettingFromUs', 'peersConnected', 'recheckProgress'),
                    'ids' => $torHash), 'method' => 'torrent-get');
                $response = transmission_rpc($request);
		if (!isset($response['arguments']['torrents']['0'])) {
		    return array(
		    'stats' => '',
		    'clientId' => '',
		    'status' => 0,
		    'bytesDone' => 0
		    );
		}
                $totalSize = $response['arguments']['torrents']['0']['totalSize'];
                $leftUntilDone = $response['arguments']['torrents']['0']['leftUntilDone'];
                $Uploaded = $response['arguments']['torrents']['0']['uploadedEver'];
                $Downloaded = $response['arguments']['torrents']['0']['downloadedEver'];
                $validProgress = 100 * $response['arguments']['torrents']['0']['recheckProgress'];
                if($totalSize) { 
                  $percentage = round((($totalSize-$leftUntilDone)/$totalSize)*100,2);
                }
                if($percentage < 100) { $dlStatus = "downloading"; }
                if(!($totalSize)) {
                  return array( 'dlStatus' => 'old_download' );
                } else {
                  if(!($Downloaded) || !($Uploaded)) {
                    $ratio = 0;
                } else {
                    $ratio = $Uploaded/$Downloaded;
                    $ratio = round($ratio, 2);
                }
                $bytesDone = $totalSize-$leftUntilDone;
                $sizeDone = human_readable($totalSize-$leftUntilDone);
                $totalSize = human_readable($totalSize);
                $clientId = $response['arguments']['torrents']['0']['id'];
                $status = $response['arguments']['torrents']['0']['status'];
                if(isset($response['arguments']['torrents']['0']['seedRatioLimit']))
		  $seedRatioLimit = round($response['arguments']['torrents']['0']['seedRatioLimit'],2);
                $peersSendingToUs = $response['arguments']['torrents']['0']['peersSendingToUs'];
                $peersGettingFromUs = $response['arguments']['torrents']['0']['peersGettingFromUs'];
                $peersConnected = $response['arguments']['torrents']['0']['peersConnected'];
                if($status == 1) {
                    $stats = "Waiting to verify";
                } else if($status == 2) {
                    $stats = "Verifying files ($validProgress%)";
                } else if($status == 4) {
                    $stats = "Downloading from $peersSendingToUs of $peersConnected peers:
                      $sizeDone of $totalSize ($percentage%)  -  Ratio: $ratio";
                } else if($status == 8) {
                    $stats = "Seeding to $peersGettingFromUs of $peersConnected peers  -  Ratio: $ratio";
                } else if($status == 16) {
                    if($ratio >= $seedRatioLimit && $percentage == 100) {
                        $stats = "Downloaded and seed ratio met. This torrent can be removed.";
                    } else {
                        $stats = "Paused";
                    }
                }
                return array( 
                    'stats' => $stats,
                    'clientId' => $clientId,
                    'status' => $status,
                    'bytesDone' => $bytesDone
                );
            }
            exit;
    }
}

function getClientData($recent) {
    global $config_values;

    switch($config_values['Settings']['Client']) {  
        case 'Transmission':
            if($recent) {
              $request = array('arguments' => array('fields' => array('id', 'name', 'status', 'errorString', 'hashString',
               'leftUntilDone', 'downloadDir', 'totalSize', 'uploadedEver', 'downloadedEver', 'addedDate', 'status',
               'peersSendingToUs', 'peersGettingFromUs', 'peersConnected', 'seedRatioLimit', 'recheckProgress', 'rateDownload', 'rateUpload'),
               'ids' => 'recently-active'), 'method' => 'torrent-get');
            } else {
              $request = array('arguments' => array('fields' => array('id', 'name', 'status', 'errorString', 'hashString',
               'leftUntilDone', 'downloadDir','totalSize', 'uploadedEver', 'downloadedEver', 'addedDate', 'status',
               'peersSendingToUs', 'peersGettingFromUs', 'peersConnected', 'seedRatioLimit', 'recheckProgress', 'rateDownload', 'rateUpload')),
               'method' => 'torrent-get');
            }
            $response = transmission_rpc($request);
            return json_encode($response);
        break;
    }
}

function delTorrent($torHash, $trash) {
    global $config_values;

    switch($config_values['Settings']['Client']) {  
        case 'Transmission':
            $request = array('arguments' => array('delete-local-data' => $trash, 'ids' => $torHash), 'method' => 'torrent-remove');
            $response = transmission_rpc($request);
            return json_encode($response);
        break;
    }
}

function stopTorrent($torHash) {
    global $config_values;

    switch($config_values['Settings']['Client']) {  
        case 'Transmission':
            $request = array('arguments' => array('ids' => $torHash), 'method' => 'torrent-stop');
            $response = transmission_rpc($request);
            return json_encode($response);
        break;
    }
}

function startTorrent($torHash) {
    global $config_values;

    switch($config_values['Settings']['Client']) {  
        case 'Transmission':
            $request = array('arguments' => array('ids' => $torHash), 'method' => 'torrent-start');
            $response = transmission_rpc($request);
            return json_encode($response);
        break;
    }
}

function moveTorrent($location, $torHash) {
    global $config_values;

    switch($config_values['Settings']['Client']) {  
        case 'Transmission':
            $torInfo = torInfo($torHash);
            if($torInfo['bytesDone'] > 0) {
                $move = true;
            } else {
                $move = false;
            }
            $request = array('arguments' => array('location' => $location, 'move' => $move, 'ids' => $torHash), 'method' => 'torrent-set-location');
            $response = transmission_rpc($request);
            return json_encode($response);
        break;
    }
}

?>

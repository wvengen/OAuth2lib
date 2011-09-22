<?php
/**
 * Class that permits to load the keys file.
 */
class AuthServerList {
    public $keys;   
    private $authzID;
    private $authzURL;
    public function __construct($dir = "") {
        $this->keys=array();
       if ($dir == '') {
            $file = dirname(dirname(__FILE__)) . "/config/asKeys.xml";
        } else {
            $file = $dir . "asKeys.xml";
        }
        $this->loadASs($file);
    }
    public function checkAuthzKey($authzID) {
        $dev = false;
        foreach($this->keys as $k){
            $crypt = hash_hmac('sha256', $this->authzID, $k);            
            if(0==strcmp($crypt, $authzID)){
                $dev = true;
            }
        }
        return $dev;
    }
    
    private function loadASs($file) {
        $xml = simplexml_load_file($file);
        if(strcmp($xml->getName(),"AuthServers")==0) {
            foreach( $xml->children() as $child) {
                $this->authzID = (string)$child['id'];
                $this->authzURL = (string)$child['url'];
                $this->keys[(string)$child['id']]=(string)$child->Key;
            }
        }else {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of authServers.xml"));
        }
    }

    public function getAuthzID(){
        return $this->authzID;
    }

    public function getAuthzURL(){
        return $this->authzURL;
    }
}

//$var = new AuthServerList();
//
//echo '<pre>';
//print_r($var->keys);
//echo '</pre>';
?>
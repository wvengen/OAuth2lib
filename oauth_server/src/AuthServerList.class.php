<?php
/**
 * Class that permits to load the keys file.
 */
class AuthServerList {
    private $keys;
    
    public function __construct($dir = "") {
        $this->keys=array();
       if ($dir == '') {
            $file = dirname(dirname(__FILE__)) . "/config/asKeys.xml";
        } else {
            $file = $dir . "asKeys.xml";
        }
        $this->loadASs($file);
    }
    public function checkTokenKey($token,$digest) {
        $dev = false;
        foreach($this->keys as $k){
            $decrypt = hash_hmac('sha256', $token, $k);
            if(0==strcmp($digest,$decrypt)){
                $dev = true;
            }
        }
        return $dev;
    }
    
    private function loadASs($file) {
        $xml = simplexml_load_file($file);
        if(strcmp($xml->getName(),"AuthServers")==0) {
            foreach( $xml->children() as $child) {
                $this->keys[]=(string)$child->Key;
            }
        }else {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of authServers.xml"));
        }
    }
}
?>
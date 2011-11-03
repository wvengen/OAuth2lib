<?php
/**
 * Class that permits to load the errors list.
 */
class ServerKeys {
    protected $keys;
    protected $debug_active;

    public function __construct($dir = '') {
        $this->debug_active=false;
        $this->keys=array();
        if ($dir == '') {
             $file = dirname(dirname(__FILE__)) . "/config/serverKeys.xml";
        }else{
            $file = $dir."serverKeys.xml";
        }
        $this->loadKeys($file);
    }

    private function error($string) {
        if($this->debug_active) {
            error_log("ServerKeys: ".$string);
        }
    }
    public function getKey($scope) {
        return $this->keys[$scope];
    }
    public function hasScope($scope) {
        return array_key_exists($scope, $this->keys)  && ($this->keys[$scope]!=null) && ($this->keys[$scope]!="") ;
    }
    
    private function loadKeys($file) {
        $this->error("load keys");
        $xml = simplexml_load_file($file);
        if(strcmp($xml->getName(),"ResourceServers")==0) {
            foreach($xml->children() as $child) {
                $key = $child->Key;               
                if(null!=$child->Scopes->Scope){                     
                    foreach($child->Scopes as $ch){                     
                         foreach($ch->Scope as $scope){
                             $this->setKey($scope, $key);
                         }
                    }
                }            
            }
        }else {
            error_log("ServerKeys: bad format of serverKeys.xml");
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of serverKeys.xml"));
        }
    }

    private function setKey($scope, $key){
        $this->keys[(String)$scope] = (String)$key;
    }
}
?>
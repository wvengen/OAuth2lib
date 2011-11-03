<?php
/**
 * Class that permits to load the keys file.
 */
class ClientList {
    private $clients;
    public function __construct($dir = "") {
        if ($dir == '') {
             $file = dirname(dirname(__FILE__)) . "/config/clientKeys.xml";
        }else{
            $file = $dir."clientKeys.xml";
        }
        $this->loadClients($file);
    }
    public function getSecret($client_id) {
        return $this->clients[$client_id];
    }
    public function isClient($client_id) {
        return (array_key_exists($client_id, $this->clients));
    }
    private function loadClients($file) {
        $xml = simplexml_load_file($file);
        $aux = array();
        if(strcmp($xml->getName(),"Keys")==0) {
            $arr = $xml->children();
            foreach($arr as $child) {
                $id=$child['id'];
                $val = $child['value'];
                $aux[(string)$id] = (string)$val;
            }
        }else {
            error_log("ClientList: bad format of clientKeys.xml");
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of clientKeys.xml"));
        }
        $this->clients = $aux;
    }
}
?>
<?php
/**
 * Class that permits to load the Clients Configuration file.
 */
class ClientConfiguration {
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
        return $this->clients[$client_id]["Key"];
    }
    public function isClient($client_id) {
        return (array_key_exists($client_id, $this->clients));
    }
    /* // not yet used (see comment below)
    public function getAllowedScopes($client_id){
    	return $this->clients[$client_id]["Scopes"];
    }
    */
    private function loadClients($file) {
        $xml = simplexml_load_file($file);
        if(strcmp($xml->getName(),"Clients")==0){
          $arr = $xml->children();
          $aux = array();
          foreach($arr as $child){
            $id = (string)$child['id'];
            $key = (string)$child->Key;
            /* // Scopes are not being enforced, so ignore them for now.
               // Besides, this can be configured in policies.xml.
            $scopes = array();
            foreach($child->AllowedScopes->children() as $scope){
              $scope_id = (string)$scope['id'];
              $attributes = array();
              $scopes[$scope_id] = array();
              foreach($scope->AllowedAttributes->children() as $attrs){
                $attr_name = (string)$attrs['name'];
                $values = array();
                foreach($attrs->children() as $val){
                  $val_check = (string)$val['check'];
                  $elems = array();
                  foreach($val->children() as $el){
                    $elems[]=(string)$el;
                  }
                  $values[$val_check] = $elems;
                }
                $attributes[$attr_name] = $values;
              }
              $scopes[$scope_id] = $attributes;
            }
            $aux[$id] = array("Key"=>$key, "Scopes"=>$scopes);
            */
            $aux[$id] = array("Key"=>$key);
          }
        }else {
            error_log("ClientList: bad format of clientKeys.xml");
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of errors.xml"));
        }
        $this->clients = $aux;
    }
}
?>
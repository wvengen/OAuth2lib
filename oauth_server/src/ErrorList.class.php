<?php
/**
 * Class that permits to load the errors list.
 */
class ErrorList {
    protected $descriptions;
    protected $uris;
    protected $errors;
    protected $debug_active;

    public function __construct($file = "config/errors.xml") {
        $this->debug_active=false;
        $this->error("");
        $this->descriptions=array();
        $this->uris=array();
        $this->loadErrors($file);
    }

    private function error($string) {
        if($this->debug_active) {
            error_log("ErrorList: ".$string);
        }
    }
    public function getDescription($error) {
        return $this->descriptions[$error];
    }
    public function hasDescription($error) {
        return array_key_exists($error, $this->descriptions)  && ($this->descriptions[$error]!=null) && ($this->descriptions[$error]!="") ;
    }
    public function hasURI($error) {
        return array_key_exists($error, $this->uris)  && ($this->uris[$error]!=null) && ($this->uris[$error]!="") ;
    }
    public function getURI($error) {
        return $this->uris[$error];
    }
    public function hasError($error) {
        return false===array_search($error, $this->errors);
    }
    private function loadErrors($file) {
        $this->error("load errors");
        $xml = simplexml_load_file($file);
        $descs = array();
        $uris = array();
        if(strcmp($xml->getName(),"errors")==0) {
            $arr = $xml->children();
            $aux =array();
            foreach($arr as $child) {
                $id=(String)$child['id'];
                $this->errors[]=$id;
                foreach($child->children() as $elementName => $elem) {
                    if(0==strcmp($elementName,"error_description")) {
                        $this->descriptions[$id] = (String)$elem;
                    }
                    if(0==strcmp($elementName,"error_uri")) {
                        $this->uris[$id] =(String)$elem;
                    }
                }
            }
        }else {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => "Bad format of errors.xml"));
        }
    }
}
?>
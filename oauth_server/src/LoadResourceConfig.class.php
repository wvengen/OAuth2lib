<?php

/**
 * Class that permits to load the errors list.
 */
class LoadResourceConfig {
    protected $classes;
    protected $archive_names;

    public function __construct($dir="") {
        $this->classes = array();
        $this->archive_names = array();
        if ($dir == '') {
            $file = dirname(dirname(__FILE__)) . "/config/resourceClasses.xml";
        } else {
            $file = $dir . "resourceClasses.xml";
        }    
        $this->loadFile($file);
    }

    public function loadFile($file) {
        $xml = simplexml_load_file($file);
        try {
           foreach ($xml as $child) {
                           $id = (String) $child['id'];
                        if(isset($child->ResourceClass))
                             $this->classes[$id] = (String) $child->ResourceClass;
                        if(isset($child->ResourceFile))
                              $this->archive_names[$id] = (String) $child->ResourceFile;
                   }
        } catch (Exception $exc) {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $exc->getMessage()));
        }
    }

    public function getClass($scope) {
        return $this->classes[$scope];
    }

    public function getArchiveName($scope) {
        return $this->archive_names[$scope];
    }

     public function hasClass($scope) {
        return array_key_exists($scope,$this->classes);
    }

    public function hasArchiveName($scope) {
        return array_key_exists($scope, $this->archive_names);
    }



}
?>
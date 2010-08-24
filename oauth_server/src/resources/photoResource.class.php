<?php

/**
 * Resource example class
 */
require_once('src/resources/IServerResource.interface.php');
class photoResource implements IServerResource {
    const SC = "http://oauth-server/photos/";
    protected $scopes;
    protected $person_id;
    protected $header;

    public function __construct($file="photos.txt") {
        $this->loadScopesFile(dirname(__FILE__) . "/photos/" . $file);
        $this->header = null;
    }

    /**
     * Function that gets the resource requested by the scope
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    public function getResource($scope, $extra=null) {     
        $this->person_id = $extra[0];
        $filename = dirname(__FILE__) . "/photos/imgs/" . $this->person_id . ".png";
        $mode = "r";
        $handle = fopen($filename, $mode);
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        $this->header = array("Content-type: image/png");
        return base64_encode($contents);
    }

    /**
     * Function that checks if the scope is available for the person_id
     * @param <type> $scope
     * @param <type> $person_id
     * @return <type>
     */
    public function checkScope($scope, $person_id=null) {
        $dev = false;
        if ($person_id == null) {
            $person_id = $this->person_id;
        }
        if ($this->hasScope($person_id)) {
            $dev = true;
        }
        return $dev;
    }

    private function loadScopesFile($file) {
        $arrayScopes = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
        foreach ($arrayScopes as $line) {
            if (!strstr($line, "##")) {
                $arrayAux = explode("=", $line);
                if (count($arrayAux) > 1) {
                    $this->scopes[trim($arrayAux[0])] = trim($arrayAux[1]);
                }
            }
        }
    }

    private function hasScope($id) {
        $dev = false;
        if (array_key_exists($id, $this->scopes)) {
            $dev = true;
        }
        return $dev;
    }

    public function hasHeader() {
        $dev = false;
        if (null!=$this->header) {
            $dev = true;
        }
        return $dev;
    }
    public function getHeader() {
        return $this->header;
    }



}
?>
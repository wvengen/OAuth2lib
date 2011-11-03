<?php
/**
 * Resource example class
 */
include_once('IServerResource.interface.php');
include_once('common.php');

class IdPGraphResource implements IServerResource {

    /**
     * Function that gets the resource requested by the scope
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    protected $header;
    private   $GEOM;
    private $BASE_URL;

    public function __construct(){
        $this->GEOM = "&geom=650x150";
        $this->BASE_URL = "http://magyar.rediris.es/priv/nagios/cgi-bin/showgraph.cgi";
    }

    public function getResource($scope, $extra=null) {
    	// set default period
        if (isset($extra['period'])) {
            $period = $extra['period'];
        } else {
            $period = "daily";	
        }

        // set default geometry
        if (isset($extra['geom'])) {
            $geom = $extra['geom'];
        } else {
            $geom = "650x150";
        }

        // set host
        $host = sho2acronym($extra['sho']);

        // modify host if idp set
        if (@$extra['idp'] == "AESIR") {
            $host = "AESIR";
        }
	 
        switch ($period) {
            case "daily":
            $rrdopts = "&rrdopts=+-snow-118800+-enow-0";
            break;
        case "weekly":
            $rrdopts = "&rrdopts=+-snow-777600+-enow-0";
            break;
        case "monthly":
            $rrdopts = "&rrdopts=+-snow-3024000+-enow-0";
            break;
        case "yearly":
            $rrdopts = "&rrdopts=+-snow-34560000+-enow-0";
            break;
        }

        // get the image
        $image = file_get_contents($this->BASE_URL."?host=".$host."&service=SIR%20IdP&db=IdP&geom=".$geom.$rrdopts);

        // load response data
        $date = '';
        $mime = '';
        foreach ($http_response_header as $header) {
            if (preg_match("/Date: (.+)/", $header, $matches)) {
                $date = $matches[1];
            }
            if (preg_match("/Content-Type: (.+)/", $header, $matches)) {
                $mime = $matches[1];
            }
        }

        // parse geometry
        preg_match("/(\d+)x(\d+)/", $geom, $matches);
        $width = $matches[1];
        $height = $matches[2];

        // build array
        $json['type'] = "image";
        $json['data']['encoding'] = "base64";
        $json['data']['mime'] = $mime;
        $json['data']['date'] = $date;
        $json['data']['width'] = $width;
        $json['data']['height'] = $height;
        $json['data']['image'] = base64_encode($image);

        // encode and return
        return json_encode($json);
    }

    /**
     * Function that checks if the scope is available for the person_id
     * @param <type> $scope
     * @param <type> $person_id
     * @return <type>
     */
    public function checkScope($scope, $person_id=null) {
        return true;
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


  private function curlConnect($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            return( 'Getting Resource Error' . curl_error($ch) );
        }
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200) {
            return( 'Getting Resource Error' . $output);
        }
        curl_close($ch);
        return $output;
    }


}
?>

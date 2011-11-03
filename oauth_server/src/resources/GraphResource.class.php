<?php
/**
 * Resource example class
 */
include_once('IServerResource.interface.php');
include_once('common.php');

class GraphResource implements IServerResource {

    /**
     * Function that gets the resource requested by the scope
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    protected $header;


    public function __construct(){
    }

    public function getResource($scope, $extra=null) {

        // set default geometry
        if (isset($extra['geom'])) {
            $geom = $extra['geom'];
        } else {
            $geom = "650x150";
        }

        // get the image
	$image = file_get_contents("http://farm3.static.flickr.com/2760/4168750582_bc1e06bda9.jpg");
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

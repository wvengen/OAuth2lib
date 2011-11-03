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
	return true;
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



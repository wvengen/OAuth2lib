<?php
/**
 * Resource example class
 */
include_once('src/resources/IServerResource.interface.php');

class helloResource implements IServerResource {
    const SC = "scope-hello_world";
    protected $scopes;
    protected $person_id;
    protected $header;

    public function __construct() {
        $this->header = null;
    }

    /**
     * Function that gets the resource requested by the scope
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    public function getResource($scope, $extra=null) {
        return "Hello, this is the protected resource; the scope is $scope.";
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

}
?>

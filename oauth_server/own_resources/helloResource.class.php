<?php
/**
 * Resource example class
 */
include_once('oauth_server/src/resources/IServerResource.interface.php');

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
        $this->person_id = $extra['person_id'];
	$this->person_name = $extra['person_name'];
	$string = "Hello, this is the protected resource; you are ";
	if (!empty($this->person_name)) $string .= $this->person_name;
	else if (!empty($this->person_id)) $string .= $this->person_id;
	else $string .= "(unknown, something's wrong!)";
	$string .= ". The scope is $scope.";
        return $string;
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

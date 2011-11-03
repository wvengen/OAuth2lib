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
        $who = @$extra['name'];
        if (empty($who)) $who = @$extra['eppn'];
        if (empty($who)) $who = @$extra['mail'];
        if (empty($who)) $who = '(unknown person)';
        return "Hello ".$who.", this is the protected resource; the scope is $scope.\n";
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

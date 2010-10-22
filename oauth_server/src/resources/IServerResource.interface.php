<?php
/**
 * Interface that must implement the class that obtains the resource.
 */
interface IServerResource {
    public function getResource($scope, $extra=null);
    /*
    Ojo!! el person_id podrá ser:
    	- uid
    	- Si la aserción no tenía uid:
    		-sPUC
    		- Si la aserción no tenía sPUC:
    			- ePTI@sHO
    */
    public function checkScope($scope, $person_id=null);
    public function hasHeader();
    public function getHeader();
}
?>
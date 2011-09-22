<?php
/**
 * Interface that must implement the class that obtains the resource.
 */
interface IServerResource {
    public function getResource($scope, $extra=null);
    public function hasHeader();
    public function getHeader();
}
?>

<?php
interface IAssertionChecking {
    public function checkAssertion($assertion);
    public function getPersonId();
    public function getError();
}
?>
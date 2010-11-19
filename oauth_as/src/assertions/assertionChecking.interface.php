<?php
interface IAssertionChecking {
    public function checkAssertion($assertion);
    public function getTokenInfo();
    public function getError();
}
?>
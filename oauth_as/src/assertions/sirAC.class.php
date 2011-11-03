<?php

require_once("assertionChecking.interface.php");
require_once 'policy.class.php';

/**
 * Class that checks if a given sir assertion is valid.
 */
class sirAssertionChecking implements IAssertionChecking {

    private $assertion;
    public $policy;
    private $tokenInfo;
    private $error;
    private $scope;
    private $tokenFormat;

    /**
     * Load the policies from a  given file.
     * @param <type> $rulesf The archive file with the policy.
     */
    public function __construct($scope, $dir = "") {
        $this->error = false;
        $this->scope = $this->cleanScope($scope);
        $this->policy = array();
        $this->tokenFormat = "%sho%";
        $this->tokenInfo = array();
        if ($dir == '') {
             $file = dirname(dirname(__FILE__)) . "/config/policies.xml";
        }else{
            $file = $dir."policies.xml";
        }

        if (0 == sizeof($this->getPolicy($file))) {
            error_log("sirAssertionChecking: empty policy file");
            $this->error = true;
        }
    }


    private function cleanScope($scope){
        if(strpos($scope,"?")==0){
            $res = $scope;
        }else{
            $res =  substr($scope, 0, strpos($scope,"?"));
        }
        return $res;
    }

    /**
     * Function that checks if the assertion is authorized
     * @param array $assertion Assertion to check
     * @return bool. True if the policy matches the assertion.
     */
    public function checkAssertion($userAssertion) {
        /*$userAssertion = explode(",",$userAssertion);
	$userAttrs = array();
	foreach($userAssertion as $elem){
            $aux = explode("=",$elem);
            $userAttrs[$aux[0]] = $aux[1];
	}*/
        // hago uso del método del PoA getAttributes para obtener la aserción
        // mandando la aserción como array en vez de como cadena
        $this->assertion = $userAssertion;
        $dev = false;
        if ($this->matchRules() != false) {
            $this->tokenInfo = $this->generateTokenInfo();
            if($this->tokenInfo!=null){
                $dev = true;
			}
        }
        return $dev;
    }
    //TODO: mover esta a función a policies
    private function generateTokenInfo(){
//		$string_ret = null;
//		foreach($this->tokenFormat->children() as $attribute){
//			$att = trim($attribute,'%');
//			if(array_key_exists($att, $this->assertion)){
//                            if($string_ret != null)
//                                $string_ret .= "&&";
//                            $string_ret .= $this->assertion[$att];
//                        }else if(0==strcmp($att,"scope")){
//                            if($string_ret != null)
//                                $string_ret .= "&&";
//                            $string_ret .= $this->scope;
//                        }
//		}
//        return $string_ret;
        $array_ret = array();
        foreach($this->tokenFormat->children() as $attribute){
            $att = trim($attribute, '%');
            if(array_key_exists($att, $this->assertion))
                    $array_ret[$att] = $this->assertion[$att];
            elseif(strcmp($att, 'scope') == 0)
                    $array_ret['scope'] = $this->scope;
        }
        return $array_ret;
    }

    /**
     * Function that decides if the assertion matches the policy,
     * @param <type> $assertion
     * @return bool. True if the policy matches the assertion.
     */
    protected function matchRules() {
        $dev = true;
        if (sizeof($this->policy) > 0) {
            foreach ($this->policy as $pol) {
                $dev = $dev && $pol->checkPolicy($this->assertion, $this->assertion_type);
            }
        } else {
            $dev = false;
        }
        return $dev;
    }

    /**
     * Function that loads the Policy from a given file
     * @param <type> $rulesfile File of the policy
     * @return AssertionPolicy. The Policy.
     */
    public function getPolicy($rulesfile) {
        $xml = simplexml_load_file($rulesfile);
        if (strcmp($xml->getName(), "AssertionList") == 0){
            foreach ($xml->children() as $policy) {
                if (0 == strcmp("papi", $policy['type'])){
                    foreach ($policy->children() as $pol) {
                        if (0 == strcmp($this->scope, $pol['scope'])){
                            $this->tokenFormat = $pol->TokenFormat;
                            foreach ($pol->Policy as $p) {
                                $this->policy[] = new AssertionPolicy($p);
                            }
                    }
            }
				}
            }
		}
        return $this->policy;
    }

    /**
     * Person Id getter
     * @return String: The scope.
     */
    public function  getTokenInfo() {
        return $this->tokenInfo;
    }

    public function getError() {
        return $this->error;
    }

}
?>
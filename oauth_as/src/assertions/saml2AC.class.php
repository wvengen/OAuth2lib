<?php
require_once("assertionChecking.interface.php");
require_once 'policy.class.php';

/**
 * Class that checks if a given saml2 assertion is valid.
 */
class saml2AssertionChecking implements IAssertionChecking {
    private $assertion;
    private $policy;
    private $tokenInfo;
    private $tokenFormat;
    private $error;
    private $scope;
    /**
     * Load the policies from a  given file.
     * @param <type> $rulesf The archive file with the policy.
     */
    public function __construct($scope,$dir = "") {
        $this->error = false;
        $this->scope = $this->cleanScope($scope);
        $this->policy = array();
        $this->tokenFormat = '%urn:mace:dir:attribute-def:eduPersonScopedAffiliation%';
        $this->tokenInfo = array();
        if ($dir == '') {
             $file = dirname(dirname(__FILE__)) . "/config/policies.xml";
        }else{
            $file = $dir."policies.xml";
        }
        if(0==sizeof($this->getPolicy($file))) {
            error_log("saml2AssertionChecking: empty policy file");
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
    public function checkAssertion($assertion) {
        //error_log("saml2AssertionChecking.checkAssertion");
        //$this->assertion = $this->transformAssertion($assertion);
        $this->assertion=$assertion;
        $dev = false;
        if($this->matchRules()!=false) {
            $this->tokenInfo = $this->generateTokenInfo();
            if(!is_null($this->tokenInfo))
            $dev = true;
        }
        return $dev;
    }

    private function generateTokenInfo(){
       $string_ret = null;
       foreach($this->tokenFormat->children() as $attribute){
           $att = trim($attribute,'%');
           if($string_ret != NULL) $string_ret .= '&&';
           if(array_key_exists($att, $this->assertion)){
               if (is_array($this->assertion[$att]))
                 //$string_ret .= implode(', ', $this->assertion[$att]);
                 $string_ret .= $this->assertion[$att][0];
               else
                 $string_ret .= $this->assertion[$att];
           } elseif(strcmp($att, 'scope')==0){
               $string_ret .= $this->scope;
           }
       }
       return $string_ret;
/*
        $array_ret = array();
        foreach($this->tokenFormat->children() as $attribute){
            $att = trim($attribute, '%');
            if(array_key_exists($att, $this->assertion)){
                $array_ret[$att] = $this->assertion[$att];
            }elseif(strcmp($att, 'scope') == 0){
                $array_ret['scope'] = $this->scope;
            }
        }
        return $array_ret;
        */
    }
    /**
     * Function that decides if the assertion matches the policy,
     * @param <type> $assertion
     * @return bool. True if the policy matches the assertion.
     */
    protected function matchRules() {
    //    error_log("saml2AssertionChecking.matchRules");
        $dev = true;
        if(sizeof($this->policy)>0) {
            foreach ($this->policy as $pol) {
                $dev = $dev && $pol->checkPolicy($this->assertion);
            }
        }else {
            error_log("denying empty policy");
            $dev = false;
        }
        return $dev;
    }

    /**
     * Function that loads the Policy from a given file
     * @param <type> $rulesfile File of the policy
     * @return AssertionPolicy. The Policy.
     */
      protected function getPolicy($rulesfile) {
        $xml = simplexml_load_file($rulesfile);
        if (strcmp($xml->getName(), "AssertionList") == 0){
            foreach ($xml->children() as $policy) {
                if (0 == strcmp("saml2", $policy['type'])){
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
     * @return String: The personid.
     */
    public function getTokenInfo() {
   //     error_log("saml2AssertionChecking.getTokenInfo");
        return $this->tokenInfo;
    }  

    public function getError() {
        return $this->error;
    }
   

}
?>
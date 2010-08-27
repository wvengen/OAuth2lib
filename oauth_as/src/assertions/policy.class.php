<?php
class AssertionPolicy {
    private $conditionAttrs;
    private $attrs;

    public function __construct($xmlElem = "") {
        $this->conditionAttrs="none";
        $this->attrs=array();
        if (!is_string($xmlElem)) {
            $this->loadFromXML($xmlElem);
        }
    }

    private function loadFromXML($xmlElem) {
        if (count($xmlElem->Attributes)>0) {
            $this->conditionAttrs = $xmlElem->Attributes["check"];
            $i=0;
            $children = $xmlElem->Attributes->children();
            while($i<count($children)) {
                $attrXML = $children[$i];
                $this->addAttribute((string) $attrXML["name"],(string) $attrXML["value"]);
                $i++;
            }
        }
    }

    private function addAttribute($name,$value) {
        $this->attrs[] = array($name,$value);
    }

    private function getAttributes() {
        return $this->attrs;
    }

    public function checkPolicy($userAttrs) {
        $matched = 0;
        foreach ($this->getAttributes() as $attr) {
            $key = $attr[0];
            $value = $attr[1];
            //Si existe ese param en las keys del array de la aserción
            if (in_array($key, array_keys($userAttrs))) {
                if(is_array($userAttrs[$key])) {
                    $matched += $this->checkMultipleAttributes($userAttrs[$key], $value);
                }else{
                    if (strcmp($value, $userAttrs[$key])==0) {
                        $matched++;
                    }
                }
            }
        }
        if (strcmp($this->conditionAttrs,"none")==0&&$matched==0) {
            return true;
        }
        else if (strcmp($this->conditionAttrs,"any")==0&&$matched>0) {
            return true;
        }
        else if (strcmp($this->conditionAttrs,"all")==0&&$matched==count($this->getAttributes())) {
            return true;
        }
        else if(sizeof($this->getAttributes())==0){
            return true;
        }else{
        	return false;
        }

    }

    private function checkMultipleAttributes($userAttrs, $value) {
        $matched=0;
        $m = false;
        foreach ($userAttrs as $elem) {
            if(is_array($elem)) {
                $matched+=$this->checkMultivaluatedAttributtes($elem,$value);
            }else {
                $m = $m || (strcmp($value, $elem)==0);
            }
        }
        if($m)
            $matched++;
        return $matched;
    }

    private function checkMultivaluatedAttributtes($elem, $value) {
        $matched=0;
        $m = false;
        foreach ($elem as $e) {
            $m = $m || (strcmp($value, $e)==0);
        }
        if($m)
            $matched++;
        return $matched;
    }
}
?>
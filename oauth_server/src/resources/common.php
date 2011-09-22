<?php 
// Common functions for resources
// Jaime Perez 20101022

// Configuracion de la base de datos de monitorizacion del SIR
define("SIRMON_DB_HOST", "bbdd.rediris.es");
define("SIRMON_DB_NAME", "nagios_sir");
define("SIRMON_DB_USER", "sirmon");
define("SIRMON_DB_PASS", "kaiHei7eaisheb6XIw6vaeth");
define("SIRMON_DB_PREFIX", "nagios_");

/*
 * Convierte de sHO (un dominio) a un identificador de AS en el SIR.
 */
function sho2idp($sho) {
    $id = null;

    // cogemos la lista de IdPs
    $idps = file_get_contents("http://www.rediris.es/sir/api/idps_available.php");
    $xml = new SimpleXMLElement($idps);

    // iteramos sobre el XML buscando el sHO
    foreach ($xml->IdentityProvider as $idp) {
        foreach ($idp->Scopes as $scope) {
            $attrs = $scope->Scope->attributes();
            $value = $attrs['value'];
            if (strcmp($sho, $value) == 0) {
                // el sHO coincide, devolvemos el PAPI_ID asociado
                $papi_id = $idp->PAPI_ID->attributes();
                $id = $papi_id['value'];
                break 2;
            }
        }
    }

    return $id;
}

/*
 * Convierte de sHO (un dominio) a siglas.
 */
function sho2acronym($sho) {
    // TODO: cambiar esta chapuza
    $domain = explode(".", $sho);
    $tld = current($domain);

    // chapuza para RedIRIS
    if ($tld == "rediris") {
        $tld = "RedIRIS";
    } else {
        $tld = strtoupper($tld);
    }

    //array_pop($domain);
    //return strtoupper(implode(".", $domain));
    return $tld;
}

/*
 * Funcion que calcula la diferencia en dias, horas, minutos y segundos
 * entre dos fechas.
 */
function dateDiff($start, $end) {
    $diff = $end - $start;
    $days = intval($diff / (60 * 60 * 24));
    $hours = intval(($diff % (60 * 60 * 24)) / (60 * 60));
    $minutes = intval(($diff % (60 * 60)) / (60));
    $seconds = intval($diff % 60);

    $r = array();
    if ($days != 0) $r['days'] = $days;
    if ($hours != 0) $r['hours'] = $hours;
    if ($minutes != 0) $r['minutes'] = $minutes;
    if ($seconds != 0) $r['seconds'] = $seconds;
    return $r;
}

/**
 * Indents a flat JSON string to make it more human-readable
 *
 * @param string $json The original JSON string to process
 * @return string Indented version of the original JSON string
 */
function json_indent($json) {

    $result    = '';
    $pos       = 0;
    $strLen    = strlen($json);
    $indentStr = "\t";
    $newLine   = "\n";

    for($i = 0; $i <= $strLen; $i++) {
        
        // Grab the next character in the string
        $char = substr($json, $i, 1);
        
        // If this character is the end of an element, 
        // output a new line and indent the next line
        if($char == '}' || $char == ']') {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line
        if ($char == ',' || $char == '{' || $char == '[') {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
    }

    return $result;
}

?>

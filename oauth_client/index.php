<?php
/**
 * @package oauth_client
 */
include_once 'src/utils/papiUtils.php';

$assertion = $_SESSION['userdata'];
if($assertion['PAPIAuthValue']==0) {
        $content="<div class='error'>Usuario no autorizado.</div>";
}else{
        $sho =$assertion['sHO'];
        $name =$assertion['cn'];
        $content = "<p>";
        if($name!=null && $name!=""){
                $content.="Hola <b>".$name."</b>,  p";
        }else{
                $content.="P";
        }
        $content .="or pertenecer a <b>'".$sho."'</b> puedes acceder a los siguientes servicios:";
            $content = '
<form action="index_phppoa.php" method="post">
    <select id="selector" name="selector_name">     
        <option value="2">SPs disponibles</option>
    </select>
    <input type="submit" value="Acceder a servicio"/>
</form>';  
}
include 'html/template.php';
?>
<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
include_once 'IFormattingResource.interface.php';

class DefaultFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $services XML with the services.
     * @return String The html response
     */
    public function formatResource($services) {
        //var_dump($services);
        $xml = new SimpleXMLElement($services);
        $string = "<h2>Proveedores de Servicios Disponibles</h2><p>Como miembro de su entidad puede acceder a los siguientes servicios:</p>";
        if (isset($xml->Error)) {
            $string .= "<div style='padding-left:10px;'>No existen servicios asociados a tu organización o entidad.</div>";
        } else {
            $string .= "<div>";
            foreach ($xml->ServiceProvider as $sp) {
               // $string .= "<div  style='padding:20px;margin-bottom:10px;border:1px black solid;'>";
                  $string .= "<div class='codigo_scroll'>";
                $nombre = $sp->Name;
                $desc = $sp->Description;
                $url = $sp->URL;
                $techinfo = $sp->TechnicalInfo;
                $wayfl = $sp->Wayfless;
                $video = $sp->VideoTutorial;
                $string .= '<h3/><a href="' . $url . '">' . $nombre . '</a></h3>';
                $string .= '<div style="padding-left:10px;"><p>' . $desc . '</p>';
                $string .= '<p><a href="' . $techinfo . '">Detalles técnicos en la integración del recurso en SIR</a></p>';
                if ($wayfl != "") {
                    $string .= '<p><a href="' . $wayfl . '">Acceso sin indicar de qué institución procedes.</a></p>';
                }
                if ($video != "") {
                    $string .= '<p><a href="' . $video . '">Video tutorial del servicio.</a></p>';
                }
                $string.="</div></div>";
            }
            $string.="</div>";
        }
        return $string;
    }
}

?>
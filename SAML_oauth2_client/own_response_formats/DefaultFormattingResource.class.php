<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
include_once ('oauth_client/src/response_formats/IFormattingResource.interface.php');

class DefaultFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $services XML with the services.
     * @return String The html response
     */
    public function formatResource($services) {
        $xml = new SimpleXMLElement($services);
        $string = "";
        if (isset($xml->Error)) {
            $string = "No existen servicios asociados a tu organización o entidad.";
        } else {
            $string = "<div class='services'>";
            foreach ($xml->ServiceProvider as $sp) {
                $string .= "<div class='service'>";
                $nombre = $sp->Name;
                $desc = $sp->Description;
                $url = $sp->URL;
                $techinfo = $sp->TechnicalInfo;
                $wayfl = $sp->Wayfless;
                $video = $sp->VideoTutorial;
                $string .= '<div class="sp_name"><p><a href="' . $url . '">' . $nombre . '</a></p></div>';
                $string .= '<div class="sp_desc"><p>' . $desc . '</p></div>';
                $string .= '<div class="sp_techinfo"><p><a href="' . $techinfo . '">Detalles técnicos en la integración del recurso en SIR</a></p></div>';
                if ($wayfl != "") {
                    $string .= '<div class="sp_waifl"><p><a href="' . $wayfl . '">Acceso sin indicar de qué institución procedes.</a></p></div>';
                }
                if ($video != "") {
                    $string .= '<div class="sp_video"><p><a href="' . $video . '">Video tutorial del servicio.</a></p></div>';
                }
                $string.="</div>";
            }
            $string.="</div>";
        }
        return $string;
    }
}
?>
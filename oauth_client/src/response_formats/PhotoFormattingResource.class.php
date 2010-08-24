<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
include_once 'IFormattingResource.interface.php';

class PhotoFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $services XML with the services.
     * @return String The html response
     */
    public function formatResource($image) {
        $im = imagecreatefromstring(base64_decode($image));
        $dir = "./tmp/".sha1($image).".txt";
        $e = imagepng($im,$dir);
        imagedestroy($im);
        $string="<p>Your registry photo is:</p>";
        $string.="<img src='./tmp/".sha1($image).".txt'/>";
        $string.="</div>";
        return $string;
    }
}
?>
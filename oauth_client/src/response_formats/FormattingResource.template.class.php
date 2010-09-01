<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
require_once ('oauth_client/src/response_formats/IFormattingResource.interface.php');

class FormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $string The Resource.
     * @return String The response
     */
    public function formatResource($string) {
        return $string;
    }
}
?>
<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
include_once 'IFormattingResource.interface.php';

class IdpStatusFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $services XML with the services.
     * @return String The html response
     */
    public function formatResource($services) {
        return $services;
    }
}

?>

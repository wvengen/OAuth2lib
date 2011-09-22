<?php
/**
 * DefaultFormattingResource
 * Example class of a formating resource class. It cpuld exist different classes, depending on the scope of the request.
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
include_once 'IFormattingResource.interface.php';

class GraphFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $services XML with the services.
     * @return String The html response
     */
    public function formatResource($services) {
         //header("Content-Type: image");
         //$json = json_decode($services);
         //$result = json_decode($content, true);
         //$image = $result['data']['image'];
         //header("Content-Type: image/gif");
         return $services;

    }
}
?>

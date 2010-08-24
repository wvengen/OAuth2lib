<?php
/**
 * IFormattingResource
 * Interface that models the resource depending on the response
 * @author Elena Lozano <elena.lozano@rediris.es>
 * @package oauth_client
 */
interface IFormattingResource {
    public function formatResource($res);
}


?>
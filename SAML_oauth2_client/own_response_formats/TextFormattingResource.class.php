<?php
/**
 * TextFormattingResource
 * Example class of a formatting resource class for simple text.
 * @author W. van Engen <wvengen@nikhef.nl>
 * @package oauth_client
 */
include_once ('oauth_client/src/response_formats/IFormattingResource.interface.php');

class PhotoFormattingResource implements IFormattingResource {

    /**
     * Function that formats a Resource
     * @param <String> $text Text to show
     * @return String The html response
     */
    public function formatResource($text) {
	return "<p>".htmlentities($base64_decode($text))."</p>";
    }
}
?>

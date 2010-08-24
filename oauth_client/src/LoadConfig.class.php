<?php

/**
 * Class that permits to load the errors list.
 */
class LoadConfig {

    //Assertion Types
    protected $error_type;
    protected $debug_active_client;
    // Client Credentials
    protected $client_id;
    protected $client_secret;
    //Access Token request Data
    protected $assertion_type;
    protected $scope;
    protected $as;
    protected $grant_type;
    //Resource Token request Data
    protected $request_type;
    protected $rs;
    protected $format_classes;
    protected $format_archive_names;



    public function __construct($file) {
        $this->format_classes = array();
        $this->format_archive_names = array();
        $this->loadFile($file);
    }

    public function loadFile($file) {
        $xml = simplexml_load_file($file);
        try {
            $this->assertion_type = (String) $xml->AuthServerConfig->AssertionType;
            $this->grant_type = (String) $xml->AuthServerConfig->GrantType;
            $this->as = (String) $xml->AuthServerConfig->AuthServerURL;

            $this->request_type = (String) $xml->ResServerConfig->RequestType;
            $this->rs = (String) $xml->ResServerConfig->ResServerURL;
            foreach ($xml->ResServerConfig->ResponseFormats as $child1) {
                foreach ($child1->children() as $child) {
                        $id = (String) $child['id'];
                        if(isset($child->FormatClass))
                        $this->format_classes[$id] = (String) $child->FormatClass;
                        if(isset($child->FormatClassArchiveName))
                        $this->format_archive_names[$id] = (String) $child->FormatClassArchiveName;
                }
            }
            $this->scope = (String) $xml->ClientConfig->DefaultScope;
            $this->client_id = (String) $xml->ClientConfig->ClientID;
            $this->client_secret = (String) $xml->ClientConfig->ClientSecret;
            $this->error_type = (String) $xml->ClientConfig->ErrorResponseType;
            $this->debug_active_client = (String) $xml->ClientConfig->DebugActive;
        } catch (Exception $exc) {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: application/json");
            header("Cache-control:no-store");
            echo json_encode(array("error" => $exc->getMessage()));
        }
    }

    public function getFormatClass($scope) {
        return $this->format_classes[$scope];
    }

    public function getFormatArchiveName($scope) {
        return $this->format_archive_names[$scope];
    }

    public function hasFormatClass($scope) {
        return array_key_exists($scope, $this->format_classes);
    }

    public function hasFormatArchiveName($scope) {
        return array_key_exists($scope, $this->format_archive_names);
    }

    public function get_error_type() {
        return $this->error_type;
    }

    public function get_debug_active_client() {
        return $this->debug_active_client;
    }

    public function get_client_id() {
        return $this->client_id;
    }

    public function get_client_secret() {
        return $this->client_secret;
    }

    public function get_assertion_type() {
        return $this->assertion_type;
    }

    public function get_scope() {
        return $this->scope;
    }

    public function get_as() {
        return $this->as;
    }

    public function get_grant_type() {
        return $this->grant_type;
    }

    public function get_request_type() {
        return $this->request_type;
    }

    public function get_rs() {
        return $this->rs;
    }

}
?>
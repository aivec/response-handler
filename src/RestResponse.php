<?php
namespace Aivec\ResponseHandler;

/**
 * Simple model for a REST response object
 */
class RestResponse {

    /**
     * HTTP response code
     *
     * @var int
     */
    private $statusCode;

    /**
     * Response object
     *
     * @var mixed
     */
    private $response;

    /**
     * Constructs REST response object
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $response
     * @param int   $statusCode
     * @return void
     */
    public function __construct($response, $statusCode) {
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    /**
     * Getter for response value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Getter for status code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
}

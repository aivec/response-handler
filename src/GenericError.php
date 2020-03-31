<?php
namespace Aivec\ResponseHandler;

/**
 * Represents a generic error object
 */
class GenericError {

    /**
     * Error code. May be a string or integer
     *
     * @var int|string
     */
    public $errorcode;

    /**
     * Name of the error
     *
     * @var string
     */
    public $errorname;

    /**
     * HTTP code of the error
     *
     * @var int
     */
    public $httpcode;

    /**
     * A string message, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string
     */
    public $debugmsg;

    /**
     * A string message, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string
     */
    public $message;

    /**
     * Creates a new error object with the given properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int|string      $errorcode Any number|string representing an error code.
     * @param string          $errorname Name of the error.
     * @param int             $httpcode The HTTP code of the error
     * @param callable|string $debugmsg A string message, or callable that constructs a message and takes
     *                                  any number of arguments. The debug message is for developers and
     *                                  should not be shown to end users.
     * @param callable|string $message A string message, or callable that constructs a message and takes
     *                                 any number of arguments. This message should be a user facing
     *                                 message and should not contain debug information.
     * @return void
     */
    public function __construct(
        $errorcode,
        $errorname,
        $httpcode,
        $debugmsg,
        $message
    ) {
        $this->errorcode = $errorcode;
        $this->errorname = $errorname;
        $this->httpcode = $httpcode;
        $this->debugmsg = $debugmsg;
        $this->message = $message;
    }
}

<?php
namespace Aivec\ResponseHandler;

use JsonSerializable;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Represents a generic error object
 */
class GenericError implements JsonSerializable, LoggerAwareInterface {

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
     * A string message, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string
     */
    public $adminmsg;

    /**
     * Setting this to a `LoggerInterface` instance implies that this `GenericError`
     * instance should be logged
     *
     * @var LoggerInterface|null
     */
    public $logger = null;

    /**
     * Creates a new error object with the given properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int|string           $errorcode Any number|string representing an error code.
     * @param string               $errorname Name of the error.
     * @param int                  $httpcode The HTTP code of the error
     * @param callable|string      $debugmsg A string message, or callable that constructs a message and takes
     *                                       any number of arguments. The debug message is for developers and
     *                                       should not be shown to end users.
     * @param callable|string      $message A string message, or callable that constructs a message and takes
     *                                      any number of arguments. This message should be a user facing
     *                                      message and should not contain debug information.
     * @param callable|string      $adminmsg A string message, or callable that constructs a message and takes
     *                                       any number of arguments. This message should be an admin facing
     *                                       message. Default: empty string
     * @param LoggerInterface|null $logger Logger object. Default: `null`
     * @return void
     */
    public function __construct(
        $errorcode,
        $errorname,
        $httpcode,
        $debugmsg,
        $message,
        $adminmsg = '',
        LoggerInterface $logger = null
    ) {
        $this->errorcode = $errorcode;
        $this->errorname = $errorname;
        $this->httpcode = $httpcode;
        $this->debugmsg = $debugmsg;
        $this->message = $message;
        $this->adminmsg = $adminmsg;
        $this->logger = $logger;
    }

    /**
     * Sets `$logger` to an implementation specific instance of `LoggerInstance`
     *
     * Returns `$this` for easy chaining of other methods for setting optional properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param LoggerInterface $logger
     * @return GenericError
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Sets `$adminmsg`.
     *
     * Returns `$this` for easy chaining of other methods for setting optional properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param callable|string $message
     * @return GenericError
     */
    public function setAdminMessage($message) {
        $this->adminmsg = $message;
        return $this;
    }

    /**
     * JSON serializes `GenericError` object for consumption by front-end
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function jsonSerialize() {
        return [
            'errorcode' => $this->errorcode,
            'errorname' => $this->errorname,
            'debug' => is_callable($this->debugmsg) ? '' : $this->debugmsg,
            'message' => is_callable($this->message) ? '' : $this->message,
            'adminmsg' => is_callable($this->adminmsg) ? '' : $this->adminmsg,
        ];
    }

    /**
     * Returns stringified representation of the `GenericError` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function __toString() {
        $debugmsg = is_callable($this->debugmsg) ? '' : $this->debugmsg;
        $message = is_callable($this->message) ? '' : $this->message;
        $adminmsg = is_callable($this->adminmsg) ? '' : $this->adminmsg;

        $s = '(Code: ' . $this->errorcode . ') (Name: ' . $this->errorname . ') [DebugMessage]: ' . $debugmsg;
        $s .= ' [UserMessage]: ' . $message;
        if (!empty($adminmsg)) {
            $s .= ' [AdminMessage]: ' . $adminmsg;
        }
        return $s;
    }
}

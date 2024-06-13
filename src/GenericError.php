<?php

namespace Aivec\ResponseHandler;

use JsonSerializable;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Represents a generic error object
 */
class GenericError implements JsonSerializable, LoggerAwareInterface
{
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
     * Optional data associated with the error
     *
     * @var mixed|null
     */
    public $data = null;

    /**
     * A string message, array of string messages, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string|string[]
     */
    public $debugmsg;

    /**
     * A string message, array of string messages, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string|string[]
     */
    public $message;

    /**
     * A string message, array of string messages, or callable that constructs a message and takes
     * any number of arguments
     *
     * @var callable|string|string[]
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
     * @param int|string               $errorcode Any number|string representing an error code.
     * @param string                   $errorname Name of the error.
     * @param int                      $httpcode The HTTP code of the error
     * @param callable|string|string[] $debugmsg A string message, array of string messages, or callable that constructs
     *                                           a message and takes any number of arguments. The debug message is for
     *                                           developers and should not be shown to end users.
     * @param callable|string|string[] $message A string message, array of string messages, or callable that constructs
     *                                          a message and takes any number of arguments. This message should be a
     *                                          user facing message and should not contain debug information.
     * @param callable|string|string[] $adminmsg A string message, array of string messages, or callable that constructs
     *                                           a message and takes any number of arguments. This message should be an
     *                                           admin facing message. Default: ''
     * @param LoggerInterface|null     $logger Logger object. Default: `null`
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
     * @param callable|string|string[] $message
     * @return GenericError
     */
    public function setAdminMessage($message) {
        $this->adminmsg = $message;
        return $this;
    }

    /**
     * Sets `$data`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $data
     * @return GenericError
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * JSON serializes `GenericError` object for consumption by front-end
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array {
        return [
            'errorcode' => $this->errorcode,
            'errorname' => $this->errorname,
            'debug' => is_callable($this->debugmsg) ? '' : $this->debugmsg,
            'message' => is_callable($this->message) ? '' : $this->message,
            'adminmsg' => is_callable($this->adminmsg) ? '' : $this->adminmsg,
            'data' => $this->data,
        ];
    }

    /**
     * Returns stringified representation of the `GenericError` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function __toString() {
        $debugmsg = $this->debugmsg;
        if (is_callable($debugmsg)) {
            $debugmsg = '';
        } elseif (is_array($debugmsg)) {
            $debugmsg = join(',', $debugmsg);
        }

        $message = $this->message;
        if (is_callable($message)) {
            $message = '';
        } elseif (is_array($message)) {
            $message = join(',', $message);
        }

        $adminmsg = $this->adminmsg;
        if (is_callable($adminmsg)) {
            $adminmsg = '';
        } elseif (is_array($adminmsg)) {
            $adminmsg = join(',', $adminmsg);
        }

        $s = '(Code: ' . $this->errorcode . ') (Name: ' . $this->errorname . ') [DebugMessage]: ' . $debugmsg;
        $s .= ' [UserMessage]: ' . $message;
        if (!empty($adminmsg)) {
            $s .= ' [AdminMessage]: ' . $adminmsg;
        }
        return $s;
    }
}

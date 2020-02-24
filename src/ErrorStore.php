<?php
namespace Aivec\ResponseHandler;

use ReflectionClass;
use InvalidArgumentException;

/**
 * Error codes and corresponding messages
 */
class ErrorStore {

    const UNKNOWN_ERROR = 9999;

    /**
     * Array of error objects in the shape of code => object
     *
     * @var array
     */
    private $codemap = [];

    /**
     * When true, PHP's `http_response_code()` is invoked when creating an error message
     *
     * @var boolean
     */
    private $setHttpResponseCode = true;


    /**
     * When toggled off, PHP's `http_response_code` function is not called
     * when creating an error response object
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function httpResponseCodeToggleOff() {
        $this->setHttpResponseCode = false;
    }

    /**
     * Returns default error
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $code
     * @return array
     */
    public function getDefaultErrorObject($code) {
        return [
            'errorcode' => 9999,
            'errorname' => 'UNKNOWN_ERROR',
            'debug' => sprintf(
                // translators: the invalid error code
                __('An error with the code %d does not exist.', 'aivec-err'),
                $code
            ),
            'message' => __('An internal error occurred', 'aivec-err'),
        ];
    }

    /**
     * Returns array of all constants defined in the child class.
     *
     * Useful for passing to scripts so we don't have to duplicate logic.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $errobject Error class instance
     * @return array
     */
    private function getChildConstants($errobject) {
        return (new ReflectionClass(get_class($errobject)))->getConstants();
    }

    /**
     * Returns array of all constants defined in this class.
     *
     * Useful for passing to scripts so we don't have to duplicate logic.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    private function getGenericConstants() {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }

    /**
     * Returns error code const string for a given code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $errobject Error class instance
     * @param int   $code
     * @return string
     */
    protected function getConstantNameByValue($errobject, $code) {
        $codestring = 'UNKNOWN_ERROR';
        $constants = array_merge(
            $this->getGenericConstants(),
            $this->getChildConstants($errobject)
        );
        foreach ($constants as $name => $value) {
            if ($value === (int)$code) {
                $codestring = $name;
                break;
            }
        }

        return $codestring;
    }

    /**
     * Map of errors with corresponding meta data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getErrorCodeMap() {
        return $this->codemap;
    }

    /**
     * Returns error response from code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $code the error code
     * @param mixed ...$msgvars optional parameters for generating context specific
     *                          error messages at runtime
     * @return array
     */
    public function getErrorResponse($code, ...$msgvars) {
        if (!isset($this->codemap[$code])) {
            return $this->getDefaultErrorObject($code);
        }

        $meta = $this->codemap[$code];
        if ($this->setHttpResponseCode === true) {
            http_response_code($meta['httpcode']);
        }
        $this->setHttpResponseCode = true;
        return [
            'errorcode' => $code,
            'errorname' => $meta['errorname'],
            'debug' => is_callable($meta['debug']) ? $meta['debug'](...$msgvars) : $meta['debug'],
            'message' => is_callable($meta['message']) ? $meta['message'](...$msgvars) : $meta['message'],
        ];
    }

    /**
     * Adds a new error object with the given properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed           $caller An instance of the calling class. Caller should be a child class whose
     *                                constant values represent error codes. Constants of the calling class,
     *                                combined with the default error codes provided by constants of this class,
     *                                make up the final list of all error codes and associated messages. In
     *                                practice, this parameter should always be `$this`
     * @param int             $code Any number representing an error code. Note that this method will throw
     *                              an exception if the error code provided already exists in $codemap (a map
     *                              containing all errors). This behavior is to make sure errors are not
     *                              overwritten.
     * @param int             $httpcode The HTTP code of the error
     * @param callable|string $debugmsg A string message, or callable that constructs a message and takes
     *                                  any number of arguments. The debug message is for developers and
     *                                  should not be shown to end users.
     * @param callable|string $message A string message, or callable that constructs a message and takes
     *                                 any number of arguments. This message should be a user facing
     *                                 message and should not contain debug information.
     * @return void
     * @throws InvalidArgumentException Thrown if `$code` already exists in codemap.
     */
    public function addError(
        $caller,
        $code,
        $httpcode,
        $debugmsg,
        $message
    ) {
        if (isset($this->codemap[$code])) {
            throw new InvalidArgumentException($code . ' already exists in codemap');
        }
        $this->codemap[$code] = [
            'errorname' => $this->getConstantNameByValue($caller, $code),
            'httpcode' => $httpcode,
            'debug' => $debugmsg,
            'message' => $message,
        ];
    }
}

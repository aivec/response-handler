<?php
namespace Aivec\ResponseHandler;

use ReflectionClass;
use InvalidArgumentException;

/**
 * Error codes and corresponding messages
 */
abstract class ErrorStore {

    const INTERNAL_SERVER_ERROR = 9998;
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
     * Instantiates error store with generic errors
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     * @throws InvalidArgumentException Thrown by `$this->addError()`.
     */
    public function __construct() {
        load_textdomain('aivec-err', __DIR__ . '/languages/aivec-err-en.mo');
        load_textdomain('aivec-err', __DIR__ . '/languages/aivec-err-ja.mo');
        $this->addError(
            self::UNKNOWN_ERROR,
            500,
            __('An unknown error occured.', 'aivec-err'),
            __('An unknown error occured.', 'aivec-err')
        );
        $this->addError(
            self::INTERNAL_SERVER_ERROR,
            500,
            __('An internal error occurred', 'aivec-err'),
            __('An internal error occurred', 'aivec-err')
        );
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
     * @return array
     */
    private function getChildConstants() {
        return (new ReflectionClass(get_class()))->getConstants();
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
        return (new ReflectionClass(get_class($this)))->getConstants();
    }

    /**
     * Returns error code const string for a given code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $code
     * @return string
     */
    protected function getConstantNameByValue($code) {
        $codestring = 'UNKNOWN_ERROR';
        $constants = array_merge(
            $this->getGenericConstants(),
            $this->getChildConstants()
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
     * @param array $debugvars optional parameters for generating context specific
     *                         debug error messages at runtime
     * @param array $msgvars optional parameters for generating context specific
     *                       user facing error messages at runtime
     * @return array
     */
    public function getErrorResponse($code, $debugvars = [], $msgvars = []) {
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
            'debug' => is_callable($meta['debug']) ? $meta['debug'](...$debugvars) : $meta['debug'],
            'message' => is_callable($meta['message']) ? $meta['message'](...$msgvars) : $meta['message'],
        ];
    }

    /**
     * Adds a new error object with the given properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
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
        $code,
        $httpcode,
        $debugmsg,
        $message
    ) {
        if (isset($this->codemap[$code])) {
            throw new InvalidArgumentException($code . ' already exists in codemap');
        }
        $this->codemap[$code] = [
            'errorname' => $this->getConstantNameByValue($code),
            'httpcode' => $httpcode,
            'debug' => $debugmsg,
            'message' => $message,
        ];
    }

    /**
     * Returns array with `errormetamap` and `errorcodes`.
     *
     * This method can be used in conjunction with our JavaScript error handling
     * [library](https://github.com/aivec/reqres-utils). As such, the shape of the array
     * is not arbitrary and should be passed as-is to any script that needs it.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getScriptInjectionVariables() {
        $codemap = $this->getErrorCodeMap();
        $codes = [];
        foreach ($codemap as $code => $meta) {
            $codes[$meta['errorname']] = $code;
        }

        return [
            'errormetamap' => $codemap,
            'errorcodes' => $codes,
        ];
    }

    /**
     * This method must be implemented by the child class
     *
     * All project specific errors should be added via this method.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    abstract public function populate();
}

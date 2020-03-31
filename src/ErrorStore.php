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
     * Array of error objects in the shape of code => `GenericError`
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
            new GenericError(
                self::UNKNOWN_ERROR,
                $this->getConstantNameByValue(self::UNKNOWN_ERROR),
                500,
                __('An unknown error occured.', 'aivec-err'),
                __('An unknown error occured.', 'aivec-err')
            )
        );
        $this->addError(
            new GenericError(
                self::INTERNAL_SERVER_ERROR,
                $this->getConstantNameByValue(self::INTERNAL_SERVER_ERROR),
                500,
                __('An internal error occurred', 'aivec-err'),
                __('An internal error occurred', 'aivec-err')
            )
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
     * Returns array of all constants defined in this class.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    private function getConstants() {
        return (new ReflectionClass(get_class($this)))->getConstants();
    }

    /**
     * This class parses all constants in the **parent and child** class and returns
     * the corresponding constant name for a given constant value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string|int $code
     * @return string
     * @throws InvalidArgumentException Thrown if no such constant exists.
     */
    protected function getConstantNameByValue($code) {
        $constantname = '';
        $constants = $this->getConstants();
        foreach ($constants as $name => $value) {
            if ($value === $code) {
                $constantname = $name;
                break;
            }
        }

        if (empty($constantname)) {
            throw new InvalidArgumentException('A constant with the value ' . (string)$code . ' does not exist.');
        }

        return $constantname;
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
            http_response_code($meta->httpcode);
        }
        $this->setHttpResponseCode = true;
        return [
            'errorcode' => $code,
            'errorname' => $meta->errorname,
            'debug' => is_callable($meta->debugmsg) ? $meta->debugmsg(...$debugvars) : $meta->debugmsg,
            'message' => is_callable($meta->message) ? $meta->message(...$msgvars) : $meta->message,
        ];
    }

    /**
     * Adds a new error object with the given properties
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param GenericError $errorobj
     * @return void
     * @throws InvalidArgumentException Thrown if `$code` already exists in codemap.
     */
    public function addError(GenericError $errorobj) {
        if (isset($this->codemap[$errorobj->errorcode])) {
            throw new InvalidArgumentException($errorobj->errorcode . ' already exists in codemap');
        }
        $this->codemap[$errorobj->errorcode] = $errorobj;
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
            $codes[$meta->errorname] = $code;
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

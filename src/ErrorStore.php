<?php

namespace Aivec\ResponseHandler;

use ReflectionClass;
use InvalidArgumentException;

/**
 * Error codes and corresponding messages
 */
abstract class ErrorStore
{
    const FORBIDDEN = 9996;
    const UNAUTHORIZED = 9997;
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
        $mopath = __DIR__ . '/languages/aivec-err-' . get_locale() . '.mo';
        if (file_exists($mopath)) {
            load_textdomain('aivec-err', $mopath);
        } else {
            load_textdomain('aivec-err', __DIR__ . '/languages/aivec-err-en.mo');
        }

        $forbiddenError = __('Sorry, you are not allowed to do that.');
        $this->addError(
            new GenericError(
                self::FORBIDDEN,
                $this->getConstantNameByValue(self::FORBIDDEN),
                403,
                $forbiddenError,
                $forbiddenError
            )
        );
        $this->addError(
            new GenericError(
                self::UNAUTHORIZED,
                $this->getConstantNameByValue(self::UNAUTHORIZED),
                401,
                $forbiddenError,
                $forbiddenError
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
        $this->addError(
            new GenericError(
                self::UNKNOWN_ERROR,
                $this->getConstantNameByValue(self::UNKNOWN_ERROR),
                500,
                __('An unknown error occured.', 'aivec-err'),
                __('An unknown error occured.', 'aivec-err')
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
        $debug = '';
        if (is_string($code)) {
            $debug = sprintf(
                // translators: the invalid error code
                __('An error with the code %s does not exist.', 'aivec-err'),
                $code
            );
        } elseif (is_int($code)) {
            $debug = sprintf(
                // translators: the invalid error code
                __('An error with the code %d does not exist.', 'aivec-err'),
                $code
            );
        }
        return new GenericError(
            9999,
            'UNKNOWN_ERROR',
            500,
            $debug,
            __('An internal error occurred', 'aivec-err')
        );
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
     * @param array $adminvars optional parameters for generating context specific
     *                         admin facing error messages at runtime
     * @return GenericError
     */
    public function getErrorResponse($code, array $debugvars = [], array $msgvars = [], array $adminvars = []) {
        if (!isset($this->codemap[$code])) {
            return $this->getDefaultErrorObject($code);
        }

        $meta = $this->codemap[$code];
        if ($this->setHttpResponseCode === true) {
            http_response_code($meta->httpcode);
        }
        $this->setHttpResponseCode = true;

        $e = new GenericError(
            $meta->errorcode,
            $meta->errorname,
            $meta->httpcode,
            is_callable($meta->debugmsg) ? call_user_func($meta->debugmsg, ...$debugvars) : $meta->debugmsg,
            is_callable($meta->message) ? call_user_func($meta->message, ...$msgvars) : $meta->message,
            is_callable($meta->adminmsg) ? call_user_func($meta->adminmsg, ...$adminvars) : $meta->adminmsg,
            $meta->logger
        );
        if ($meta->logger !== null) {
            $meta->logger->error($e);
        }

        return $e;
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
     * Merges a separate instance of an `ErrorStore` `codemap` into this instance's
     * `codemap`
     *
     * Note that unless `$disallowDuplicates` is `true`, the passed in `ErrorStore`
     * instance will overwrite errors that have the same code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param ErrorStore $estore
     * @param bool       $disallowDuplicates default: `true`
     * @throws InvalidArgumentException Thrown if a code from `$estore` already exists in codemap.
     * @return void
     */
    public function mergeErrorStoreInstance(ErrorStore $estore, $disallowDuplicates = true) {
        if ($disallowDuplicates === false) {
            $this->codemap = array_merge($this->codemap, $estore->getErrorCodeMap());
            return;
        }

        $whitelisted = [self::INTERNAL_SERVER_ERROR, self::UNKNOWN_ERROR];
        $emap = $estore->getErrorCodeMap();
        foreach ($emap as $code => $gerror) {
            if (isset($this->codemap[$code]) && !in_array($code, $whitelisted, true)) {
                throw new InvalidArgumentException($code . ' already exists in codemap. Aborting merge.');
            }
        }
        $this->codemap = array_merge($this->codemap, $estore->getErrorCodeMap());
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

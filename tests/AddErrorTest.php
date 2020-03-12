<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Aivec\ResponseHandler\ErrorStore;

function __($string, $domain) {
    return $string;
}

function load_textdomain($arg1, $arg2) {
}

final class AddErrorTest extends TestCase
{

    public function testCantAddDuplicateErrorCode(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ErrorStore::UNKNOWN_ERROR . ' already exists in codemap');
        $estore = new ErrorStore();
        $estore->addError($this, ErrorStore::UNKNOWN_ERROR, 500, 'dummy', 'dummy');
    }
}


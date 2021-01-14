<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Aivec\ResponseHandler\ErrorStore;

final class AddErrorTest extends TestCase
{
    public function testCantAddDuplicateErrorCode(): void {
        require_once('mocks.php');
        require_once('Implementation.php');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ErrorStore::UNKNOWN_ERROR . ' already exists in codemap');
        $estore = new Implementation();
        $estore->populate();
    }
}

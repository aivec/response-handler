<?php

use Aivec\ResponseHandler\ErrorStore;
use Aivec\ResponseHandler\GenericError;

class Implementation extends ErrorStore
{
    public function populate() {
        require_once('mocks.php');
        $this->addError(new GenericError(ErrorStore::UNKNOWN_ERROR, 'UNKNOWN_ERROR', 500, 'dummy', 'dummy'));
    }
}

<?php

namespace App\Jobs\Deps;

use App\Events\Toast\ToastDanger;
use Exception;

trait ToastsExceptions
{
    public function toastException(Exception $e): void {
        ToastDanger::dispatch($e->getMessage(), 'Failed Job');
    }

}

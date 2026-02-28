<?php

namespace App\Facades;

use App\Services\OpenAI\Intelligence;
use Illuminate\Support\Facades\Facade;

class Brain extends Facade
{
    protected static function getFacadeAccessor(): string {
        return Intelligence::class;
    }
}

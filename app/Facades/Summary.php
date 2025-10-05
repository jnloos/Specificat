<?php
namespace App\Facades;

use App\Services\AssistantSummary;
use Illuminate\Support\Facades\Facade;

class Summary extends Facade
{
    protected static function getFacadeAccessor(): string {
        return AssistantSummary::class;
    }
}

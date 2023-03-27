<?php
namespace Fabian\Pesapal\Facades;

use Illuminate\Support\Facades\Facade;

class Pesapal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Fabian\Pesapal\Pesapal::class;
    }
}

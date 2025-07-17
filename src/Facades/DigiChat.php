<?php

namespace Digiworld\DigiChat\Facades;

use Illuminate\Support\Facades\Facade;

class DigiChat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'digichat';
    }
}

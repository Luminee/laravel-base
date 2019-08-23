<?php

namespace Luminee\Base\Services;

use Route;

class BaseService
{
    public function _GetRoutes()
    {
        return Route::getRoutes();
    }
}
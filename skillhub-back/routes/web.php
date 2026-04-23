<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('SkillHub backend', 200);
});

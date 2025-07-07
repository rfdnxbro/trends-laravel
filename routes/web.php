<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/{path}', function () {
    return view('app');
})->where('path', '.*');

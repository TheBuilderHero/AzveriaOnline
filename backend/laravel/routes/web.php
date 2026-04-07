<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login');
});

Route::get('/app', function () {
    return view('app');
});

Route::get('/docs/player', function () {
    return redirect('https://github.com/TheBuilderHero/AzveriaOnline/blob/master/README');
});

Route::get('/docs/admin', function () {
    return redirect('https://github.com/TheBuilderHero/AzveriaOnline/blob/master/READMEADMIN');
});

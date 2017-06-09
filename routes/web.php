<?php

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
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/verify/{token}','Auth\RegisterController@verify')->name('verify.register');

Route::resource('project','ProjectController');

Route::patch('task/{task}/check',['as'=>'task.check','uses'=>'TaskController@check']);

Route::resource('task','TaskController');

Route::get('chart','ChartController@index')->name('chart.index');

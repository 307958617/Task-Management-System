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

Route::resource('task.step','StepController');//注意这里是用了双重resource路由'task.step'，rul的格式就是：task/{task}/step/{step}
Route::patch('task/{task}/step/{step}/toggleComplete','StepController@toggleComplete');//给完成步骤和取消完成步骤添加路由，因为resource路由没有这个方法
Route::post('task/{task}/step/complete','StepController@completeAll');//给完成所有步骤添加路由，因为resource路由没有这个方法
Route::post('task/{task}/step/clear','StepController@clearCompleted');//给清除所有已完成的步骤添加路由，因为resource路由没有这个方法

Route::get('chart','ChartController@index')->name('chart.index');

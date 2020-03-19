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

Route::get('/', 'HomeController@home');

Route::get('/about',function(){
    return view('about');
})->name('about');

Route::post('/post','HomeController@post');
Route::get('/attachment/{file_id}','HomeController@attachment')->name('attachment');
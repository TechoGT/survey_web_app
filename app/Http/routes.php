<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', 'WelcomeController@index');

Route::get('home', 'HomeController@index');

Route::group(array('prefix' => 'api/v1'), function()
{

    // First Route
    Route::resource('/sync', 'SyncController');
    // List groups
    Route::resource('/questions/list', 'GroupOfQuestions\GroupOfQuestionsController');

});

Route::get('vinicio', function(){
    return 'hola';
});


Route::controllers([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);

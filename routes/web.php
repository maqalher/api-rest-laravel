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

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', 'PruebasControler@index');
Route::get('/test', 'PruebasControler@test');

/*
    Metodos Http comunes

        -Get: Conseguir datos o recursos
        -Post: Guardar datos o recursos o hacer la logica desde un formulario
        -Put: Actualizar datos o recursos
        -Delete: Eliminar datos o recursos

        API REST usa get y post
        API RESTFUL utliza todos los metodos
*/

// Rutas del controlador de usuarios
Route::post('api/register', 'UserController@register');
Route::post('api/login', 'UserController@login');
Route::put('api/user/update', 'UserController@update');
Route::post('api/user/upload', 'UserController@upload')->middleware('api.auth');
Route::get('api/user/avatar/{filename}', 'UserController@getImage');
Route::get('api/user/detail/{id}', 'UserController@detail');

// Rutas del controlador de categorias
Route::resource('/api/category', 'CategoryController');

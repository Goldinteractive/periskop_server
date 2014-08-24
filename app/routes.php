<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
    $images = App::make('Periskop\ImageRepository');
    $all = $images->get('/Users/sidneywidmer/Development/www/periskop_server/public/uploads/test.png');

    var_dump($all);
});

Event::listen('file.created', 'Periskop\DirectoryObserver@newFile');

Latchet::connection('Connection');
Latchet::topic('stream', 'ImageStream');
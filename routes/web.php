<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('docs.postman', function () {
    $disk = Storage::disk('local');
    $path = $disk->exists('scribe/collection.postman.json')
        ? 'scribe/collection.postman.json'
        : 'scribe/collection.json';

    return new JsonResponse($disk->get($path), json: true);
});

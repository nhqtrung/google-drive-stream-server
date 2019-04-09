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

Route::get('test', function () {
    Storage::cloud()->put('test.txt', 'Hello World');
    return 'File was saved to Google Drive';
});


Route::get('hls_file', 'convertController@getFile');

Route::get('list', function() {
    $dir = '/';
    $recursive = false; // Get subdirectories also?
    $contents = collect(Storage::cloud()->listContents($dir, $recursive));

    //return $contents->where('type', '=', 'dir'); // directories
    return $contents->where('type', '=', 'file'); // files
});

Route::get('list-folder-contents', 'convertController@getFileInFolder');

Route::get('put', function() {
    Storage::cloud()->put('test.txt', 'Hello World');
    return 'File was saved to Google Drive';
});

Route::get('put-existing', function() {
    $filename = '02_500_00001.ts';
    $filePath = public_path($filename);
    $fileData = File::get($filePath);

    Storage::cloud()->put('Entycrypted_500_0001.txt', $fileData);
    return 'File was saved to Google Drive';
});

Route::get('get', function() {
    $filename = 'Entycrypted_500_0001.txt';

    $dir = '/';
    $recursive = false; // Get subdirectories also?
    $contents = collect(Storage::cloud()->listContents($dir, $recursive));

    $file = $contents
        ->where('type', '=', 'file')
        ->where('filename', '=', pathinfo($filename, PATHINFO_FILENAME))
        ->where('extension', '=', pathinfo($filename, PATHINFO_EXTENSION))
        ->first(); // there can be duplicate file names!

    //return $file; // array with file info

    $rawData = Storage::cloud()->get($file['path']);

    return response($rawData, 200)
        ->header('ContentType', $file['mimetype'])
        ->header('Content-Disposition', "attachment; filename=$filename");
});

Route::get('hls/{fileId}', 'HLSController@redirectToStreamLink');

Route::get('/', 'convertController@showFormInfo');

Route::post('/', 'convertController@exportVideoForHls');


// Route::post('/', 'VideoController@store');

Route::get('file-upload', 'FileController@fileUpload');
Route::post('file-upload', 'FileController@fileUploadPost')->name('fileUploadPost');
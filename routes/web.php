<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Authentication
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');

Route::post('logout', 'Auth\LoginController@logout');

// Registration
Route::group(['middleware' => ['guest']], function () {
    Route::get('register', 'Auth\RegisterController@showRegistrationForm');
    Route::post('register', 'Auth\RegisterController@register');
    Route::get('/redirect/{network}', 'SocialMediaController@redirect');
    Route::get('/callback/{network}', 'SocialMediaController@callback');
});

Route::get('register/step2', 'Auth\RegisterController@showRegistrationFormStep2')->name('register.step2.get');
Route::post('register/step2', 'Auth\RegisterController@registerStep2')->name('register.step2.post');

// Password Reset
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

// Control Panel

// Partner
Route::get('/home', 'HomeController@index');
Route::get('/partner_list', 'PartnerController@showAction');
Route::get('/partner_search/{term}', 'PartnerApiController@search');

// Partner API
Route::get('/partner_search/popup/{term}', '\App\Http\Controllers\Api\PartnerApiController@search');
Route::get('/partner_add/{id}', '\App\Http\Controllers\Api\PartnerApiController@add');
Route::get('/partner_revoke/{id}', '\App\Http\Controllers\Api\PartnerApiController@revoke');
Route::get('/partner_delete/{id}', '\App\Http\Controllers\Api\PartnerApiController@delete');
Route::get('/partner_accept/{id}', '\App\Http\Controllers\Api\PartnerApiController@accept');
Route::get('/partner_reject/{id}', '\App\Http\Controllers\Api\PartnerApiController@reject');
Route::get('/partner_requests/', '\App\Http\Controllers\Api\PartnerApiController@requests');

// Document
Route::get('/docs', 'DocumentController@indexAction');

// Document API
Route::get('/docs_search/{term}', '\App\Http\Controllers\Api\DocumentApiController@search');
Route::get('/docs_view/{id}', '\App\Http\Controllers\Api\DocumentApiController@show');
Route::get('/document/category/children/{id}', '\App\Http\Controllers\Api\DocumentApiController@getCategoryChildren');
Route::post('/document_data/', '\App\Http\Controllers\Api\DocumentApiController@dataById');

// Userdocument
Route::get('/userdocs', '\App\Http\Controllers\Api\UserDocumentApiController@indexAction');
Route::get('/userdocs/view/{id}', 'UserDocumentController@viewAction');

// Userdocument API
Route::post('/userdoc_upsert/{id}', '\App\Http\Controllers\Api\UserDocumentApiController@upsert');
Route::get('/userdocs/status/{id}', '\App\Http\Controllers\Api\UserDocumentApiController@status');
Route::post('/document_sign', '\App\Http\Controllers\Api\UserDocumentApiController@sign');

// Notifications API
Route::get('/notifications','NotificationController@getUnread');
Route::post('/notifications/mark_read','NotificationController@markRead');

// Lawyer
Route::group(['middleware' => ['lawyer']], function () {
    Route::get('/lawyer','LawyerController@index');
    Route::get('/lawyer/add_document','LawyerController@addDocument');

    // Lawyer API
    Route::get('/lawyer/video/{id}','\App\Http\Controllers\Api\LawyerApiController@getVideo');
    Route::get('/lawyer/scans/{id}','\App\Http\Controllers\Api\LawyerApiController@getScanUris');
    Route::get('/lawyer/scan/{code}','\App\Http\Controllers\Api\LawyerApiController@getScan');
    Route::get('/lawyer/profile/{id}','\App\Http\Controllers\Api\LawyerApiController@getProfile');
    Route::post('/lawyer/verify','\App\Http\Controllers\Api\LawyerApiController@verifyUser');
});

// Verification
Route::get('verification','VerificationController@showVerify');
Route::post('verification','VerificationController@saveVerify');
Route::get('video_verification', 'VerificationController@showVideo');
Route::post('/video_verification/upload', 'VerificationController@uploadVideo');
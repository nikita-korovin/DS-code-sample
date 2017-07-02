<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Socialite;

class SocialMediaController extends Controller
{
    public function redirect($sm)
    {
        return \Socialite::driver($sm)->redirect();
    }

    public function callback($sm)
    {
        $providerUser = \Socialite::driver($sm)->user();
        var_dump($providerUser);die();
    }
}
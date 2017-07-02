<?php

namespace App\Http\Controllers;

use App\Countries;
use App\UserDocument;
use Illuminate\View\View;

class VerificationController extends Controller
{


    public function saveVerify()
    {
        $user = \App\User::find(\Auth::user()->id);
        $files = \Input::file('scans');
        $fullpaths = [];
        foreach ($files as $file) {
            // Validate each file
            $rules = ['scans' => 'required|image|max:2048'];
            $validator = \Validator::make(array('scans' => $file), $rules);

            if ($validator->passes()) {
                $destinationPath = base_path('storage/userdata/scans');
                $filename = date('Y-m-d-H-i-s') . $file->getClientOriginalName();
                $fullpaths[] = $destinationPath . '/' . $filename;
                $upload_success = $file->move($destinationPath, $filename);

                // return the user back
            } else {
                // redirect back with errors.
                return \Redirect::to('verifications')->withInput()->withErrors($validator);
            }
        }

        $rules = [
            'country' => 'required|integer',
            'passport' => 'required',
            'phone' => 'required|regex:/^\+[0-9]{8,16}$/'
        ];
        $validator = \Validator::make([
            'country' => \Input::get('country'),
            'passport' => \Input::get('passport'),
            'phone' => \Input::get('phone'),
        ], $rules);

        if ($validator->passes()) {
            $user->country = \Input::get('country');
            $user->scans = implode("\n", $fullpaths);
            $user->phone = \Input::get('phone');
            $user->passport = \Input::get('passport');
            $user->save();


            return \Redirect::to('video_verification');
        } else {
            // redirect back with errors.
            return \Redirect::to('verification')->withInput()->withErrors($validator);
        }
    }

    public function showVerify()
    {

        // if user already did this step
        $user = \App\User::find(\Auth::user()->id);
        if ($user->country && $user->phone && $user->scans) {
            return \Redirect::to('video_verification');
        }

        return view('auth.register-step2', ['countries' => Countries::all()]);
    }

    public function showVideo()
    {
        $detect = new \Detection\MobileDetect();

        $secret_number = rand(1000, 9999);
        $user = \App\User::find(\Auth::user()->id);
        $user->secret_pin = $secret_number;
        $user->save();

        return view('auth.register-step3',
            ['pin' => $secret_number, 'mobile' => $detect->isMobile(), 'safari' => $detect->isSafari()]);

    }

    public function uploadVideo(\Illuminate\Http\Request $request)
    {
        if ($request->hasFile('data')) {
            if ($request->file('data')->isValid()) {
                if (\Auth::user()->id) {
                    $file = \Auth::user()->id . '-' . date('Y-m-d-H-i-s') . '.webm';
                    $path = storage_path('app/videos');
                    $ret = $request->data->storeAs('videos', ($file), 'local');
                    if (!empty($ret)) {
                        $user = \App\User::find(\Auth::user()->id);
                        $user->verified = \App\User::VERIFICATION_STATUS_PENDING;
                        $user->video = $path . '/' . $file;
                        $user->save();
                        $result = ['status' => 1];
                        if ($action = \Session::get('action_after_verify', false)) {
                            if (isset($action['sign'])) {
                                $userDoc = UserDocument::find($action['sign']);
                                $result['redirect'] = '/userdocs/view/' . $userDoc->id;
                                $userDoc->sign();
                            }
                        }
                        return json_encode($result);
                    }
                }
            }
        }
        return json_encode(['status' => 0]);
    }
}
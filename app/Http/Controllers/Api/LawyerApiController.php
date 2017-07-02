<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class LawyerApiController extends Controller{

    /**
     * Get video by id
     *
     * @param $id
     * @return mixed
     */
    public function getVideo($id)
    {
        $path = \App\User::find($id)->video;

        if (!$path) {
            return \App::abort(404);
        }

        if (!\File::exists($path)) {
            return \App::abort(500);
        }

        $headers = [
            'Content-Type' => 'video/webm',
            'Content-Length' => \File::size($path),
            'Content-Disposition' => 'attachment; filename="' . 'video' . '.webm"'
        ];

        return response()->stream(function () use ($path) {
            try {
                $stream = fopen($path, 'r');
                fpassthru($stream);
            } catch (\Exception $e) {
                Log::error($e);
            }
        }, 200, $headers);
    }

    /**
     * Get scan uris by id
     *
     * @param $id
     * @return string
     */
    public function getScanUris($id)
    {
        $scans = \App\User::find($id)->getScans();
        $return = [];
        foreach ($scans as $scan) {
            $return[] = $this->_encode($scan);
        }
        return response()->json($return);
    }

    /**
     * Get san by code
     *
     * @param $code
     * @return mixed
     */
    public function getScan($code)
    {
        $file = $this->_decode($code);
        if (\File::exists($file)) {
            return response()->download($file);
        }
        return \App::abort(404);
    }

    /**
     * Get user profile by id
     *
     * @param $id
     */
    public function getProfile($id)
    {
        if ($user = \App\User::find($id)) {
            return response()->json([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'country' => $user->country,
                'passport' => $user->passport,
                'email' => $user->email,
                'phone' => $user->phone,
                'secret_pin' => $user->secret_pin,
                'created_at' => $user->created_at->format('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Verify given user
     */
    public function verifyUser()
    {
        if ($user = \App\User::find(request()->id)) {
            $user->verified = \App\User::VERIFICATION_STATUS_DONE;
            $user->save();
            return response()->json(['verified' => 1]);
        }
    }

    /**
     * Encrypt path
     *
     * @param string $file
     * @return string
     */
    protected function _encode($file)
    {
        return \Crypt::encrypt($file);
    }

    /**
     * Decrypt path
     *
     * @param string $file
     * @return string
     */
    protected function _decode($file)
    {
        return \Crypt::decrypt($file);
    }
    
}
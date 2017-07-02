<?php

namespace App\Http\Controllers;

class LawyerController extends Controller
{

    /**
     * Display index page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('controlpanel.lawyer.home', [
            'users' => \App\User::where('verified', '=', 1)->get()
        ]);
    }

    /**
     * Show add new document page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showAddDocument()
    {
        return view('controlpanel.lawyer.edit_document', [
            'categories'
        ]);
    }
}

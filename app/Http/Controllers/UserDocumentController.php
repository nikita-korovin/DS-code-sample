<?php

namespace App\Http\Controllers;

use App\Document;
use App\DocumentType;
use App\User;
use App\UserDocument;
use App\UserDocumentSigned;
use Symfony\Component\DomCrawler\Crawler;

class UserDocumentController extends Controller
{

    /**
     * Shows list of userDocuments
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAction()
    {

        $userDocuments = UserDocument::where(['user_id' => \Auth::user()->id])->get();
        $documentMentioned = \Auth::user()->agreements()->where('permission', '!=',
            UserDocument::PERMISSION_OWNER)->get();

        return view('controlpanel.userdocument.index', [
            'documents' => $userDocuments,
            'documentsMentioned' => $documentMentioned
        ]);
    }

    /**
     * Shows a userDocument
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewAction($id)
    {
        $userDoc = UserDocument::find($id);

        return view('controlpanel.userdocument.view', [
            'document' => $userDoc,
            'text' => $userDoc->getHTML(),
            'me' => \Auth::user(),
        ]);
    }

}
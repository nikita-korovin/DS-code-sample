<?php

namespace App\Http\Controllers;

use App\Category;
use App\Document;
use App\DocumentType;
use App\User;
use App\UserDocument;

class DocumentController extends Controller
{
    /**
     * Show main document page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAction()
    {
//
//        foreach (Document::all() as $model) {
//            $qins = \SphinxQL::query()->insert()->into('document');
//            $qins->set(['gid' => $model->id, 'id' => $model->id, 'title' => $model->title, 'text' => $model->text])->execute();
//        }
        return view('controlpanel.document.index', [
            "categories" => Category::getRoot()->children
        ]);
    }

    /**
     * Creates and shows a userDocument
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showAction($id)
    {
        $userDoc = new UserDocument();
        $userDoc->doc_id = $id;
        $userDoc->user_id = \Auth::user()->id;
        $userDoc->save();
        $userDoc->setUsers([
            $userDoc->user_id
        ]);
        return redirect()->to('userdocs/view/' . $userDoc->id);
    }

    
}
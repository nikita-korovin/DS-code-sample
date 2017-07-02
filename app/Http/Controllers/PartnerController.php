<?php

namespace App\Http\Controllers;

use App\Document;
use App\Notifications\PartnerRequest;
use App\Notifications\PartnerRequestAccepted;
use App\User;
use Hamcrest\Core\CombinableMatcher;

class PartnerController extends \App\Http\Controllers\Controller
{

    public function showAction()
    {

        $users = User::all();
//        foreach ($users as $model){
//            $qins = \SphinxQL::query()->insert()->into('user');
//            $qins->set(['gid' => $model->id, 'id' => $model->id, 'first_name' => $model->first_name, 'last_name' => $model->last_name, 'email' => $model->email])->execute();
//        }
//        foreach (Document::all() as $model) {
//            $qins = \SphinxQL::query()->insert()->into('document');
//            $qins->set(['gid' => $model->id, 'id' => $model->id, 'title' => $model->title, 'text' => $model->text])->execute();
//        }

        return view('controlpanel.partner.show');
    }

}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class UserDocumentApiController extends Controller
{
    /**
     * Creates / updates userDocument
     * @param $id
     */
    public function upsert($id)
    {

        $userDocument = UserDocument::find($id);
        if (!$userDocument) {
            $userDocument = new UserDocument();
        }

        $userDocument->user_id = \Auth::user()->id;
        $userDocument->doc_id = \Request::get('doc_id');

        $changes = [
            'paragraph' => [],
            'variables' => []
        ];

        foreach (\Request::get('data') as $mode => $t) {
            foreach ($t as $type => $items) {
                foreach ($items as $k => $item) {
                    if (!empty($item)) {
                        $changes[$mode][$type][$k] = trim($item);
                    }
                }
            }
        }
        $userDocument->setChanges($changes);
        $userDocument->save();
        
        return response()->json(['id' => $userDocument->id]);
    }



    /**
     * Signs a userDocument
     */
    public function sign()
    {
        $id = \Request::get('document_id');
        $document = UserDocument::find($id);

        return response()->json($document->sign());
    }

    /**
     * @param $id
     *
     * Checks most important document variables
     */
    public function status($id)
    {
        $userDoc = UserDocument::find($id);
        $status = $userDoc->status();

        $messages = [];

        // ERRORS
        if (!\Auth::user()->partners_from->toArray() && !\Auth::user()->partners_to->toArray() && count($userDoc->document->getParties() > 1)) {
            $messages[] = [
                'status' => 'danger',
                'message' => 'You have no partners to sign this document with! Please, add a partner <a href="/partner_list">here</a>'
            ];
        }
        if (\Auth::user()->verified !== User::VERIFICATION_STATUS_DONE) {
            $messages[] = [
                'status' => 'danger',
                'message' => 'In order to sign any document your account must be verified! Please, verify your account <a href="/verification">here</a>'
            ];
        }


        // WARNINGS
        if (!$status['isFilled']) {
            $messages[] = ['status' => 'warning', 'message' => 'This document is not yet filled!'];
        }

        if (($status['relation'] instanceof \App\UserDocumentSigned) && $status['relation']->permission <= 1 && !$status['isSignedByMe']) {
            $messages[] = [
                'status' => 'warning',
                'message' => 'You only have read permission for this document. If you need more permissions, please contact your partner <a href="/partner_list">here</a>'
            ];
        }

        // SUCCESSES
        if ($status['isSignedByMe']) {
            if (!$status['isSigned']) {
                $messages[] = ['status' => 'success', 'message' => 'This document is already signed by you'];
            } else {
                $messages[] = ['status' => 'success', 'message' => 'This document is already signed by every party'];
            }
        } else {
            if ($status['isFilled']) {
                $messages[] = ['status' => 'success', 'message' => 'This document is ready to be signed!'];
            }
        }

        $status['messages'] = $messages;

        return response()->json($status);
    }

}
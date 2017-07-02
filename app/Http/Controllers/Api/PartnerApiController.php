<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class PartnerApiController extends Controller{

    /**
     * Search user by first name, last name, email
     *
     * @param $term
     * @param string $filter
     */
    public function searchDocPartner($term, $filter = '')
    {
        $q = \SphinxQL::query()->select()->from('user')->match(['first_name', 'last_name', 'email'], '*' . $term . '*', true)->execute();

        $users = \SphinxQL::with($q)->get('App\User');
        $data = [];

        foreach ($users as $user) {
            $status = $user->partnerStatus();

            if ($status['status'] == User::STATUS_PARTNER_ACCEPTED) {
                $data[] = [
                    'title' => $user->first_name . ' ' . $user->last_name,
                    'data' => [
                        'id' => $user->id
                    ]
                ];
            }
        }

        echo json_encode($data);
    }

    /**
     * Search user by first name, last name or email
     *
     * @param $term
     * @param string $filter
     */
    public function search($term, $filter = '')
    {
        $q = \SphinxQL::query()->select()->from('user')->match(['first_name', 'last_name', 'email'], '*' . $term . '*', true)->execute();

        $users = \SphinxQL::with($q)->get('App\User');
        $data = [];
        foreach ($users as $user) {
            $status = $user->partnerStatus();
            if ($user->id === \Auth::user()->id) {
                continue;
            }
            $data[] = [
                'title' => $user->first_name . ' ' . $user->last_name,
                'buttons' => [
                    'Add' => [
                        'title' => 'Add',
                        'enabled' => 1,
                        'action' => '/partner_add/' . $user->id,
                        'visible' => (!$status || ($status['status'] === User::STATUS_PARTNER_REJECTED && $status['direction'] === 'right'))
                    ],
                    'Revoke' => [
                        'title' => 'Cancel',
                        'enabled' => 1,
                        'action' => '/partner_revoke/' . $user->id,
                        'visible' => $status['direction'] === 'left' && $status['status'] == User::STATUS_PARTNER_PENDING
                    ],
                    'Delete' => [
                        'title' => 'Delete',
                        'enabled' => 1,
                        'action' => '/partner_delete/' . $user->id,
                        'visible' => $status['status'] == User::STATUS_PARTNER_ACCEPTED
                    ],
                    'Accept' => [
                        'title' => 'Accept',
                        'enabled' => 1,
                        'action' => '/partner_accept/' . $user->id,
                        'visible' => $status['direction'] === 'right' && $status['status'] == User::STATUS_PARTNER_PENDING
                    ],
                    'Reject' => [
                        'title' => 'Reject',
                        'enabled' => 1,
                        'action' => '/partner_reject/' . $user->id,
                        'visible' => $status['direction'] === 'right' && $status['status'] == User::STATUS_PARTNER_PENDING
                    ],
                ],
                'url' => [

                ]
            ];
        }
        echo json_encode($data);
    }

    /**
     * Create a partner request
     *
     * @param $id
     */
    public function add($id)
    {

        // in case relation exists
        if ($relation = \Auth::user()->partners_to()->find($id)) {
            echo json_encode([
                'status' => 0,
                'message' => 'Partner already exists!'
            ]);
        }

        // in case relation was rejected by current user before
        if ($relation = \Auth::user()->partners_to()->find($id)) {
            \Auth::user()->partners_to()->detach($id);
        }

        // add user
        \Auth::user()->partners_from()->sync([$id => ['status' => User::STATUS_PARTNER_PENDING]], false);

        // Notify user
        User::find($id)->notify(new PartnerRequest());

        echo json_encode([
            'status' => 1,
            'message' => 'Request sent!'
        ]);
    }

    /**
     * Cancel a partner request
     *
     * @param $id
     */
    public function revoke($id)
    {
        \Auth::user()->partners_from()->detach($id);

        echo json_encode([
            'status' => 1,
            'message' => 'Request cancelled!'
        ]);
    }

    /**
     * Delete existing partner
     *
     * @param $id
     */
    public function delete($id)
    {
        \Auth::user()->partners_from()->detach($id);
        \Auth::user()->partners_to()->detach($id);

        echo json_encode([
            'status' => 1,
            'message' => 'Partner deleted!'
        ]);
    }

    /**
     * Accepts partner request
     *
     * @param $id
     */
    public function accept($id)
    {

        //check if there ever was a request
        if (!$user = \Auth::user()->partners_to()->find($id)) {
            echo json_encode([
                'status' => 0,
                'message' => 'No request to accept!'
            ]);
        }

        \Auth::user()->partners_to()->sync([$id => ['status' => User::STATUS_PARTNER_ACCEPTED]], false);
        $user->notify(new PartnerRequestAccepted());

        return response()->json([
            'status' => 1,
            'message' => 'Request accepted!'
        ]);
    }

    /**
     * Rejects a partner request
     *
     * @param $id
     */
    public function reject($id)
    {
        \Auth::user()->partners_to()->sync([$id => ['status' => User::STATUS_PARTNER_REJECTED]], false);

        echo json_encode([
            'status' => 1,
            'message' => 'Partner deleted!'
        ]);
    }

    /**
     * Shows all current requests
     */
    public function requests()
    {
        $data = [
            'requests_to' => [],
            'partners' => [],
            'requests_from' => []

        ];
        $list_requests_to = [];
        $list_requests_from = [];
        $list_partners = [];

        foreach (\Auth::user()->partners_to()->get() as $result) {
            if ($result->pivot->status === User::STATUS_PARTNER_ACCEPTED) {
                $list_partners[] = $result;
            } elseif ($result->pivot->status === User::STATUS_PARTNER_PENDING) {
                $list_requests_to[] = $result;
            }
        }

        foreach (\Auth::user()->partners_from()->get() as $result) {
            if ($result->pivot->status === User::STATUS_PARTNER_ACCEPTED) {
                $list_partners[] = $result;
            } elseif ($result->pivot->status === User::STATUS_PARTNER_PENDING) {
                $list_requests_from[] = $result;
            }
        }


        foreach ($list_requests_to as $user) {
            $status = $user->partnerStatus();
            $data['requests_to'][] = [
                'title' => $user->first_name . ' ' . $user->last_name,
                'buttons' => [
                    'Reject' => [
                        'title' => 'Reject',
                        'enabled' => 1,
                        'action' => '/partner_reject/' . $user->id,
                        'visible' => true
                    ],
                    'Accept' => [
                        'title' => 'Accept',
                        'enabled' => 1,
                        'action' => '/partner_accept/' . $user->id,
                        'visible' => true
                    ],
                ],
                'expand' => [

                ]
            ];
        }
        foreach ($list_partners as $user) {
            $data['partners'][] = [
                'title' => $user->first_name . ' ' . $user->last_name,
                'buttons' => [
                    'Delete' => [
                        'title' => 'Delete',
                        'enabled' => 1,
                        'action' => '/partner_delete/' . $user->id,
                        'visible' => true
                    ],
                ],
                'expand' => [

                ]
            ];
        }
        foreach ($list_requests_from as $user) {
            $data['requests_from'][] = [
                'title' => $user->first_name . ' ' . $user->last_name,
                'buttons' => [
                    'Revoke' => [
                        'title' => 'Cancel',
                        'enabled' => 1,
                        'action' => '/partner_revoke/' . $user->id,
                        'visible' => true
                    ],
                ],
                'expand' => [
                    // check later
                    'signature' => [
                        'prepared' => [
                            'encoding' => 'type1',
                            'type' => CombinableMatcher::class
                        ]
                    ]
                ]
            ];
        }

        echo json_encode($data);
    }
    
}
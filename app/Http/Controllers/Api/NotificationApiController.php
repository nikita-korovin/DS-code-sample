<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Http\Controllers\Controller;

class NotificationApiController extends Controller
{

    /**
     * Get all unread notifications
     */
    public function getUnread()
    {
        if (\Auth::check()) {
            $notifications = [];
            $limit = 5;
            foreach (\Auth::user()->unreadNotifications as $k => $notification) {
                if ($k >= $limit) {
                    break;
                }
                $message = '';
                $url = '';
                switch ($notification->type) {
                    case 'App\Notifications\PartnerRequest':
                        if (!$user = User::find($notification->data['from'])) {
                            $notification->delete();
                            continue;
                        }
                        $message = 'You have a new partner request from ' . $user->first_name . ' ' . $user->last_name;
                        $url = '/partner_list/#partners_to';
                        break;
                    case 'App\Notifications\PartnerRequestAccepted':
                        if (!$user = User::find($notification->data['from'])) {
                            $notification->delete();
                            continue;
                        }
                        $message = $user->first_name . ' ' . $user->last_name . ' has accepted your partner request';
                        $url = '/partner_list/#partners';
                        break;
                    case 'App\Notifications\DocumentParty':
                        if (!$user = User::find($notification->data['from'])) {
                            $notification->delete();
                            continue;
                        }
                        $message = 'You were mentioned as a document party by ' . $user->first_name . ' ' . $user->last_name;
                        $url = '/partner_list/#partners_to';
                        break;
                }
                $notifications[] = [
                    'type' => $notification->type,
                    'message' => $message,
                    'created' => $notification->created_at,
                    'id' => $notification->id,
                    'url' => $url
                ];
            }
            echo json_encode($notifications);
        } else {
            echo json_encode(['not authorized']);
        }
    }

    /**
     * Mark notification as read
     */
    public function markRead()
    {
        $ids = \Request::get('ids');
        foreach ($ids as $id) {
            $notification = \Auth::user()->notifications->find($id);
            $notification->read_at = date('Y-m-d H:i:s');
            $notification->save();
        }
        echo json_encode('ok');
    }

}
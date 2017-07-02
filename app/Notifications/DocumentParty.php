<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentParty extends Notification
{
    use Queueable;
    
    private $doc_id;
    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($doc_id)
    {
        $this->doc_id = $doc_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database','broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(_('DocSwift'))
            ->subject(_('New document proposal'))
            ->line(_('You have a new document proposal from your partner ' .  \Auth::user()->first_name))
            ->action(_('You can see it here'), 'https://laravel.com')
            ->line(_('Thank you for using our application!'));
    }

    /**
     * Get DB representation of the notification
     *
     * @param mixed $notifiable
     */
    public function toDatabase($notifiable){
        return [
            'from' => \Auth::user()->id,
            'doc_id' => $this->doc_id
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return [
            'to' => [
                'email' => $notifiable->email
            ]
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PartnerRequestAccepted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
                    ->from('DocSwift')
                    ->subject(_('Partner request accepted'))
                    ->line(_('Your partner request has been accepted'))
                    ->action(_('You have a new partner request'), 'https://laravel.com')
                    ->line('Thank you for using our application!');
    }

    /**
     * Get DB representation of the notification
     * 
     * @param mixed $notifiable
     */
    public function toDatabase($notifiable){
        return [
            'from' => \Auth::user()->id,
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

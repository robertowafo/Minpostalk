<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMeetingConfirmation extends Notification
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Meeting Reservation Confirmation')
                    ->greeting('Hello ' . $this->data['name'] . ',')
                    ->line('Your meeting reservation has been confirmed with the following details:')
                    ->line('Meeting Title: ' . $this->data['intitule_reunion'])
                    ->line('Moderator: ' . $this->data['animateur'])
                    ->line('Meeting Date: ' . $this->data['date_reunion'])
                    ->line('Start Time: ' . $this->data['heure_debut'])
                    ->line('Number of Participants: ' . $this->data['number_participant'])
                    ->line('Meeting Room: ' . $this->data['meeting_room'])
                    ->line('Thank you for using our service!');
    }
}

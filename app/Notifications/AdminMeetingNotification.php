<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminMeetingNotification extends Notification
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
                    ->subject('New Meeting Reservation')
                    ->greeting('Hello Admin,')
                    ->line('A new meeting has been reserved with the following details:')
                    ->line('Name: ' . $this->data['name'])
                    ->line('Email: ' . $this->data['email'])
                    ->line('Matricule: ' . $this->data['matricule'])
                    ->line('Meeting Title: ' . $this->data['intitule_reunion'])
                    ->line('Moderator: ' . $this->data['animateur'])
                    ->line('Meeting Date: ' . $this->data['date_reunion'])
                    ->line('Start Time: ' . $this->data['heure_debut'])
                    ->line('Number of Participants: ' . $this->data['number_participant'])
                    ->line('Meeting Room: ' . $this->data['meeting_room'])
                    ->line('Please take any necessary actions.');
    }
}

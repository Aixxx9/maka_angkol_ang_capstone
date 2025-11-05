<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PendingAthleteSubmitted extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'pending_athlete',
            'message' => 'A moderator submitted a player for approval.',
            'url' => url('/admin/athletes/pending'),
        ];
    }
}


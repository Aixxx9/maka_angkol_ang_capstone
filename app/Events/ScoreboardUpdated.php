<?php

namespace App\Events;

use App\Models\Scoreboard;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $scoreboard;

    public function __construct(Scoreboard $scoreboard)
    {
        $this->scoreboard = [
            'id' => $scoreboard->id,
            'left_school' => [
                'id' => $scoreboard->left_school_id,
                'name' => optional($scoreboard->leftSchool)->name,
                'logo_path' => optional($scoreboard->leftSchool)->logo_path,
            ],
            'right_school' => [
                'id' => $scoreboard->right_school_id,
                'name' => optional($scoreboard->rightSchool)->name,
                'logo_path' => optional($scoreboard->rightSchool)->logo_path,
            ],
            'sport' => [
                'id' => $scoreboard->sport_id,
                'name' => optional($scoreboard->sport)->name,
                'slug' => optional($scoreboard->sport)->slug,
            ],
            'match_label' => $scoreboard->match_label,
            'left_score' => (int) $scoreboard->left_score,
            'right_score' => (int) $scoreboard->right_score,
            'is_active' => (bool) $scoreboard->is_active,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('scoreboard'); // public channel
    }

    public function broadcastAs(): string
    {
        return 'ScoreboardUpdated';
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AthleteApprovalController extends Controller
{
    public function pending()
    {
        // Mark pending-athlete notifications as read for this admin
        if (auth()->check()) {
            $user = auth()->user();
            $user->unreadNotifications()
                ->where('type', \App\Notifications\PendingAthleteSubmitted::class)
                ->update(['read_at' => now()]);
        }

        $athletes = Athlete::with(['sport:id,name','school:id,name'])
            ->where('status', 'pending')
            ->latest('created_at')
            ->get(['id','first_name','last_name','sport_id','school_id','submitted_by','created_at']);

        return Inertia::render('Admin/Athletes/Pending', [
            'athletes' => $athletes,
        ]);
    }

    public function approve(Athlete $athlete)
    {
        abort_unless($athlete->status === 'pending', 404);
        $athlete->update(['status' => 'approved']);
        return back()->with('success', 'Player approved.');
    }

    public function reject(Athlete $athlete)
    {
        abort_unless($athlete->status === 'pending', 404);
        $athlete->update(['status' => 'rejected']);
        return back()->with('success', 'Player rejected.');
    }
}

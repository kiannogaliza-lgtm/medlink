<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display announcements page for Nurse portal.
     */
    public function nurseIndex()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        $activeCount = Announcement::where('status', 'active')->count();
        $archivedCount = Announcement::where('status', 'archived')->count();

        return view('dashboards.nurse-announcements', compact('announcements', 'activeCount', 'archivedCount'));
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'date'    => ['nullable', 'date'],
            'status'  => ['nullable', 'in:active,archived'],
        ]);

        $user = auth()->user();

        $announcement = Announcement::create([
            'user_id'     => $user->id,
            'author_name' => $user->first_name . ' ' . $user->last_name,
            'author_role' => $user->role,
            'title'       => $validated['title'],
            'content'     => $validated['content'],
            'date'        => $validated['date'] ?? now()->toDateString(),
            'status'      => $validated['status'] ?? 'active',
        ]);

        // Notify active residents and the other staff role when an active announcement is posted
        if ($announcement->status === 'active') {
            $targetRoles = $user->role === 'nurse' ? ['resident', 'bhw'] : ['resident', 'nurse'];
            $recipients = \App\Models\User::whereIn('role', $targetRoles)
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->get();

            $snippet = \Illuminate\Support\Str::limit($announcement->content, 120);
            foreach ($recipients as $recipient) {
                \App\Models\AppNotification::create([
                    'user_id' => $recipient->id,
                    'type'    => 'info',
                    'title'   => 'New Announcement: ' . $announcement->title,
                    'message' => $snippet,
                ]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Announcement posted successfully!']);
        }

        return redirect()->back()->with('success', 'Announcement posted successfully!');
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'date'    => ['nullable', 'date'],
            'status'  => ['nullable', 'in:active,archived'],
        ]);

        $announcement->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Announcement updated successfully!']);
        }

        return redirect()->back()->with('success', 'Announcement updated successfully!');
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Request $request, Announcement $announcement)
    {
        $announcement->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Announcement deleted successfully!']);
        }

        return redirect()->back()->with('success', 'Announcement deleted successfully!');
    }
}

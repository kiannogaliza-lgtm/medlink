<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Services\SmsService;
use App\Support\PhoneNumber;
use App\Support\SmsTemplates;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display announcements page for Nurse portal.
     */
    public function nurseIndex(SmsService $smsService)
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        $activeCount = Announcement::where('status', 'active')->count();
        $archivedCount = Announcement::where('status', 'archived')->count();
        $smsConfigured = $smsService->isConfigured();

        return view('dashboards.nurse-announcements', compact('announcements', 'activeCount', 'archivedCount', 'smsConfigured'));
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request, SmsService $smsService)
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'content'  => ['required', 'string'],
            'date'     => ['nullable', 'date'],
            'status'   => ['nullable', 'in:active,archived'],
            'send_sms' => ['nullable', 'boolean'],
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
            $recipients = User::whereIn('role', $targetRoles)
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

        $successMessage = 'Announcement posted successfully!';

        // Optional SMS broadcast to active verified residents with valid mobile numbers
        if ($request->boolean('send_sms') && $announcement->status === 'active' && $smsService->isConfigured()) {
            $residentCandidates = User::where('role', 'resident')
                ->where('status', 'active')
                ->where('registration_unverified', false)
                ->get(['id', 'phone_number']);

            $seenPhones = [];
            $uniqueRecipients = [];
            foreach ($residentCandidates as $resident) {
                if (!PhoneNumber::isSendable($resident->phone_number)) {
                    continue;
                }
                $normalized = PhoneNumber::normalize($resident->phone_number);
                if ($normalized === null || isset($seenPhones[$normalized])) {
                    continue;
                }
                $seenPhones[$normalized] = true;
                $uniqueRecipients[] = [
                    'user_id' => $resident->id,
                    'phone'   => $normalized,
                ];
            }

            $recipientCount = count($uniqueRecipients);
            $announcement->update([
                'sms_sent_at'         => now(),
                'sms_recipient_count' => $recipientCount,
            ]);

            if ($recipientCount > 0) {
                $announcementId = $announcement->id;
                $smsMessage = SmsTemplates::announcement($announcement->title, $announcement->content);

                dispatch(function () use ($announcementId, $uniqueRecipients, $smsMessage) {
                    @set_time_limit(0);
                    $sms = app(SmsService::class);
                    $ann = Announcement::find($announcementId);

                    foreach ($uniqueRecipients as $index => $entry) {
                        if ($index > 0) {
                            usleep(200_000);
                        }
                        $recipientUser = User::find($entry['user_id']);
                        $sms->send($entry['phone'], $smsMessage, 'announcement', $recipientUser, $ann);
                    }
                })->afterResponse();
            }

            $successMessage .= " SMS is being sent to {$recipientCount} residents.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'message'      => $successMessage,
                'announcement' => $announcement->fresh(),
            ]);
        }

        return redirect()->back()->with('success', $successMessage);
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

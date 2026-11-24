@extends('layouts.nurse')

@section('title', 'Schedule Announcements')
@section('page-title', 'Schedule Announcements')

@section('styles')
<style>
    .announcement-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 24px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .announcement-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
    }
    .badge-active {
        color: #059669;
    }
    .badge-archived {
        color: #64748b;
    }
</style>
@endsection

@section('content')
    {{-- Header with New Announcement button --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-7 bg-white p-6 rounded-2xl border border-gray-200">
        <div>
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span class="material-icons-outlined text-blue-600">campaign</span>
                Schedule Announcements
            </h2>
            <p class="text-xs text-gray-500 mt-1">Create, edit, and publish schedule announcements synced live to all Resident and BHW dashboards.</p>
        </div>
        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/25 transition-all cursor-pointer">
            <span class="material-icons-outlined" style="font-size:18px;">add</span>
            Post Schedule Announcement
        </button>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-7">
        <div class="bg-white p-5 rounded-2xl border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Announcements</p>
                <p class="text-2xl font-black text-gray-900">{{ $announcements->count() }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-icons-outlined">campaign</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Active on Portals</p>
                <p class="text-2xl font-black text-emerald-600">{{ $activeCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-icons-outlined">check_circle</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Archived</p>
                <p class="text-2xl font-black text-gray-600">{{ $archivedCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center">
                <span class="material-icons-outlined">archive</span>
            </div>
        </div>
    </div>

    {{-- Announcements Feed --}}
    <div class="space-y-4">
        @forelse($announcements as $ann)
            <div class="announcement-card flex flex-col md:flex-row md:items-start justify-between gap-5">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2.5 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider {{ $ann->status === 'active' ? 'badge-active' : 'badge-archived' }}">
                            {{ $ann->status }}
                        </span>
                        <span class="text-xs font-semibold text-blue-600 flex items-center gap-1">
                            <span class="material-icons-outlined" style="font-size:13px;">schedule</span>
                            {{ $ann->date ? $ann->date->format('M d, Y') : $ann->created_at->format('M d, Y') }}
                        </span>
                        <span class="text-xs text-gray-400">
                            Posted by <strong class="text-gray-700 font-semibold">{{ $ann->author_name ?? 'Health Staff' }}</strong> ({{ strtoupper($ann->author_role ?? 'Staff') }})
                        </span>
                        @if($ann->sms_sent_at)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="material-icons-outlined" style="font-size:13px;">sms</span>
                                SMS sent to {{ $ann->sms_recipient_count ?? 0 }} residents - {{ $ann->sms_sent_at->format('M d, Y') }}
                            </span>
                        @endif
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $ann->title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $ann->content }}</p>
                </div>

                <div class="flex items-center gap-2 shrink-0 border-t md:border-t-0 md:border-l border-gray-100 pt-3 md:pt-0 md:pl-5">
                    <button 
                        type="button"
                        onclick="openEditModal({{ json_encode($ann) }})"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 hover:border-blue-200 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span class="material-icons-outlined" style="font-size:16px;">edit</span>
                        Edit
                    </button>

                    <form method="POST" action="{{ route('announcements.destroy', $ann->id) }}" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-50 hover:bg-red-50 text-gray-700 hover:text-red-700 border border-gray-200 hover:border-red-200 transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="material-icons-outlined" style="font-size:16px;">delete</span>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
                <span class="material-icons-outlined mb-2 text-blue-300" style="font-size:52px;">campaign</span>
                <p class="text-base font-bold text-gray-700">No Announcements Yet</p>
                <p class="text-xs text-gray-500 mt-1">Click the button above to publish the first health announcement for residents.</p>
            </div>
        @endforelse
    </div>

    {{-- Create / Edit Announcement Modal --}}
    <div id="announcementModal" class="modal-overlay">
        <div class="modal-content !max-w-xl">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                <h3 id="modalTitle" class="text-lg font-bold text-[#0c2d48] flex items-center gap-2">
                    <span class="material-icons-outlined text-blue-600">campaign</span>
                    <span>Post New Announcement</span>
                </h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <span class="material-icons-outlined">close</span>
                </button>
            </div>

            <form id="announcementForm" method="POST" action="{{ route('announcements.store') }}">
                @csrf
                <div id="methodField"></div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Announcement Title *</label>
                        <input 
                            type="text" 
                            id="annTitle" 
                            name="title" 
                            required 
                            placeholder="e.g. Free Medical Check-up, Dengue Prevention Drive" 
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Event / Schedule Date</label>
                            <input 
                                type="date" 
                                id="annDate" 
                                name="date" 
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Publish Status</label>
                            <select 
                                id="annStatus" 
                                name="status" 
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all"
                            >
                                <option value="active">Active (Visible to Residents)</option>
                                <option value="archived">Archived (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Content / Description *</label>
                        <textarea 
                            id="annContent" 
                            name="content" 
                            rows="4" 
                            required 
                            placeholder="Write the full announcement details, instructions for residents, requirements to bring, location, etc." 
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all resize-none"
                        ></textarea>
                    </div>

                    {{-- Optional SMS Broadcast Toggle (Create only) --}}
                    <div id="smsToggleWrap"
                         class="p-3.5 rounded-xl border border-gray-200 bg-gray-50/80 {{ !$smsConfigured ? 'opacity-60' : '' }}"
                         @if(!$smsConfigured) title="SMS is not configured" @endif>
                        <label class="flex items-start justify-between gap-3 {{ $smsConfigured ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                            <div>
                                <span class="block text-xs font-bold text-gray-800">Also send via SMS to all registered residents</span>
                                <span class="block text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                                    Sends a text message to every active resident with a valid mobile number. Uses the health center phone's SMS load.
                                </span>
                                @if(!$smsConfigured)
                                    <span class="block text-[11px] text-amber-700 font-semibold mt-1">SMS is not configured</span>
                                @endif
                            </div>
                            <span class="relative inline-flex items-center shrink-0 mt-0.5">
                                <input type="checkbox"
                                       id="annSendSms"
                                       name="send_sms"
                                       value="1"
                                       {{ !$smsConfigured ? 'disabled' : '' }}
                                       class="sr-only peer">
                                <span class="w-10 h-6 bg-gray-300 rounded-full peer peer-checked:bg-blue-600 peer-focus:ring-2 peer-focus:ring-blue-500/30 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:shadow after:transition-transform peer-checked:after:translate-x-4"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button 
                        type="button" 
                        onclick="closeModal()" 
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/25 transition-all cursor-pointer"
                    >
                        Publish Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('announcementModal');
    const form = document.getElementById('announcementForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const methodField = document.getElementById('methodField');
    const annTitle = document.getElementById('annTitle');
    const annDate = document.getElementById('annDate');
    const annStatus = document.getElementById('annStatus');
    const annContent = document.getElementById('annContent');
    const smsToggleWrap = document.getElementById('smsToggleWrap');
    const annSendSms = document.getElementById('annSendSms');

    function openCreateModal() {
        modalTitle.innerHTML = '<span class="material-icons-outlined text-blue-600">campaign</span><span>Post New Announcement</span>';
        submitBtn.innerText = 'Publish Announcement';
        form.action = "{{ route('announcements.store') }}";
        methodField.innerHTML = '';
        annTitle.value = '';
        annDate.value = new Date().toISOString().split('T')[0];
        annStatus.value = 'active';
        annContent.value = '';
        if (annSendSms) annSendSms.checked = false;
        if (smsToggleWrap) smsToggleWrap.classList.remove('hidden');
        modal.classList.add('active');
    }

    function openEditModal(ann) {
        modalTitle.innerHTML = '<span class="material-icons-outlined text-blue-600">edit_note</span><span>Edit Announcement</span>';
        submitBtn.innerText = 'Update Announcement';
        form.action = `/announcements/${ann.id}`;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        annTitle.value = ann.title || '';
        if (annSendSms) annSendSms.checked = false;
        if (smsToggleWrap) smsToggleWrap.classList.add('hidden');
        if (ann.date) {
            annDate.value = ann.date.split('T')[0];
        } else {
            annDate.value = '';
        }
        annStatus.value = ann.status || 'active';
        annContent.value = ann.content || '';
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    // Close on overlay backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>
@endsection

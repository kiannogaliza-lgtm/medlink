/**
 * MedLink Real-Time Auto-Sync Engine
 * Periodically polls the server for transaction version changes across all modules.
 * When a transaction occurs (medicine requested, accepted, approved, walk-in added,
 * home service booked, consultation recorded, user registered, etc.), all connected
 * portals automatically and seamlessly sync without manual page reloads.
 */

class MedLinkAutoSync {
    constructor(intervalMs = 2500) {
        this.intervalMs = intervalMs;
        this.currentVersion = null;
        this.lastNotifId = null;
        this.timer = null;
        this.isChecking = false;
        this.isTypingOrInteracting = false;
        this.pendingReload = false;
        this.audioContext = null;
        this.setupInteractionListeners();
    }

    setupInteractionListeners() {
        // Avoid refreshing while user is actively filling out a form or typing
        const setInteracting = () => {
            this.isTypingOrInteracting = true;
            clearTimeout(this._typingTimeout);
            this._typingTimeout = setTimeout(() => {
                this.isTypingOrInteracting = false;
                // If a server update arrived while the user was interacting, sync now
                if (this.pendingReload) {
                    this.check();
                }
            }, 4000);
        };

        window.addEventListener('input', setInteracting, { passive: true });
        window.addEventListener('keydown', setInteracting, { passive: true });
    }

    start() {
        if (this.timer) return;

        // Show any notification toast that triggered the reload so it isn't lost on refresh
        try {
            const pendingToastRaw = sessionStorage.getItem('medlink_pending_toast');
            if (pendingToastRaw) {
                sessionStorage.removeItem('medlink_pending_toast');
                const pt = JSON.parse(pendingToastRaw);
                if (pt && pt.title) {
                    this.showToast(pt.title, pt.message, pt.type);
                }
            }
        } catch (e) {}

        // Immediate initial check to capture the baseline hash
        this.check();
        this.timer = setInterval(() => this.check(), this.intervalMs);
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    playChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            if (!this.audioContext) {
                this.audioContext = new AudioCtx();
            }
            if (this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }
            const osc = this.audioContext.createOscillator();
            const gain = this.audioContext.createGain();
            osc.connect(gain);
            gain.connect(this.audioContext.destination);

            osc.type = 'sine';
            const now = this.audioContext.currentTime;
            osc.frequency.setValueAtTime(587.33, now); // D5
            osc.frequency.exponentialRampToValueAtTime(880, now + 0.15); // A5

            gain.gain.setValueAtTime(0, now);
            gain.gain.linearRampToValueAtTime(0.2, now + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);

            osc.start(now);
            osc.stop(now + 0.45);
        } catch (e) {
            // Audio context blocked or unsupported
        }
    }

    showToast(title, message, type = 'info') {
        const toastId = 'medlink-realtime-toast';
        let toast = document.getElementById(toastId);
        if (!toast) {
            toast = document.createElement('div');
            toast.id = toastId;
            toast.style.position = 'fixed';
            toast.style.bottom = '24px';
            toast.style.right = '24px';
            toast.style.zIndex = '99999';
            toast.style.maxWidth = '360px';
            toast.style.backgroundColor = '#0c2d48';
            toast.style.color = '#ffffff';
            toast.style.padding = '14px 18px';
            toast.style.borderRadius = '14px';
            toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.35)';
            toast.style.display = 'flex';
            toast.style.alignItems = 'flex-start';
            toast.style.gap = '12px';
            toast.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
            document.body.appendChild(toast);
        }

        const iconName = type === 'success' ? 'check_circle' : (type === 'error' ? 'cancel' : 'notifications_active');
        const iconColor = type === 'success' ? '#22c55e' : (type === 'error' ? '#ef4444' : '#38bdf8');

        toast.innerHTML = `
            <span class="material-icons-outlined" style="color: ${iconColor}; font-size: 24px; flex-shrink: 0; margin-top: 1px;">${iconName}</span>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 13px; line-height: 1.3; color: #ffffff;">${escapeHtml(title)}</div>
                <div style="font-size: 12px; color: #cbd5e1; margin-top: 3px; line-height: 1.4;">${escapeHtml(message)}</div>
            </div>
        `;

        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        // Try playing audio chime
        this.playChime();

        clearTimeout(this._toastTimeout);
        this._toastTimeout = setTimeout(() => {
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
        }, 5000);
    }

    async check() {
        if (this.isChecking) return;
        this.isChecking = true;

        try {
            const res = await fetch('/api/sync-version?_t=' + Date.now(), {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });

            if (res.ok) {
                const data = await res.json();
                if (!data.authenticated) {
                    this.stop();
                    if (data.reason === 'deactivated' || data.reason === 'deleted') {
                        window.location.href = '/login';
                    }
                    return;
                }

                // If on pending approval screen and status changed to active
                if (data.status === 'active' && window.location.pathname.includes('pending-approval')) {
                    window.location.href = '/dashboard';
                    return;
                }

                // Track if a new notification arrived on this poll
                let newNotifForToast = null;
                if (data.latest_notif) {
                    if (this.lastNotifId === null) {
                        this.lastNotifId = data.latest_notif.id;
                    } else if (this.lastNotifId !== data.latest_notif.id) {
                        this.lastNotifId = data.latest_notif.id;
                        newNotifForToast = data.latest_notif;
                    }
                }

                // Update Nurse Portal Notification Badge if present on page
                if (data.role === 'nurse') {
                    const nurseBadge = document.getElementById('nurse-notif-badge');
                    const nurseSidebarBadge = document.getElementById('nurse-sidebar-notif-badge');
                    const nurseCount = document.getElementById('nurse-notif-count');
                    const totalPending = data.pending_total || 0;
                    if (nurseBadge) {
                        if (totalPending > 0) {
                            nurseBadge.innerText = totalPending > 9 ? '9+' : String(totalPending);
                            nurseBadge.style.display = 'flex';
                        } else {
                            nurseBadge.style.display = 'none';
                        }
                    }
                    if (nurseSidebarBadge) {
                        if (totalPending > 0) {
                            nurseSidebarBadge.innerText = totalPending > 99 ? '99+' : String(totalPending);
                            nurseSidebarBadge.classList.remove('hidden');
                        } else {
                            nurseSidebarBadge.classList.add('hidden');
                        }
                    }
                    if (nurseCount) {
                        nurseCount.innerText = `${totalPending} New`;
                    }
                }

                let willReloadNow = false;

                if (this.currentVersion === null) {
                    // First heartbeat - store baseline
                    this.currentVersion = data.version;
                } else if (this.currentVersion !== data.version || this.pendingReload) {
                    // State has changed on the server!
                    // IMPORTANT: Do NOT overwrite this.currentVersion until we actually reload,
                    // otherwise deferred reloads get permanently lost.

                    // Check if an actually visible modal is open (never match child elements like *ModalTitle / *ModalSub)
                    const activeModals = document.querySelectorAll('.hs-modal-overlay.active, .pv-modal-overlay.active, .modal-overlay.active, .modal-open, [id$="Modal"]:not(.hidden)');
                    const hasOpenModal = Array.from(activeModals).some(m => {
                        if (m.closest('.hs-modal-overlay:not(.active), .pv-modal-overlay:not(.active)')) {
                            return false;
                        }
                        const style = window.getComputedStyle(m);
                        return style.display !== 'none'
                            && style.visibility !== 'hidden'
                            && style.pointerEvents !== 'none'
                            && parseFloat(style.opacity) > 0;
                    });

                    if (this.isTypingOrInteracting || hasOpenModal) {
                        this.pendingReload = true;
                    } else {
                        willReloadNow = true;
                    }
                }

                if (willReloadNow) {
                    // Persist toast ONCE across reload so it doesn't fire twice (before + after reload)
                    if (newNotifForToast) {
                        try {
                            sessionStorage.setItem('medlink_pending_toast', JSON.stringify({
                                title: newNotifForToast.title,
                                message: newNotifForToast.message,
                                type: newNotifForToast.type
                            }));
                        } catch (e) {}
                    }

                    this.currentVersion = data.version;
                    this.pendingReload = false;

                    // Perform seamless live sync
                    window.location.reload();
                    return;
                }

                // If not reloading immediately, show toast + dispatch reactive event now
                if (newNotifForToast) {
                    this.showToast(newNotifForToast.title, newNotifForToast.message, newNotifForToast.type);
                    window.dispatchEvent(new CustomEvent('medlink-new-notification', { detail: newNotifForToast }));
                }
            }
        } catch (e) {
            // Silently swallow network jitter
        } finally {
            this.isChecking = false;
        }
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Auto-instantiate globally for all pages
if (typeof window !== 'undefined') {
    window.MedLinkSync = new MedLinkAutoSync(2500); // Poll every 2.5 seconds for real-time response
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.MedLinkSync.start());
    } else {
        window.MedLinkSync.start();
    }
}

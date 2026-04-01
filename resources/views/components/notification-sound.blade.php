{{-- Alpine.js Notification Sound Player --}}
{{-- Injected via PanelsRenderHook::BODY_END in AdminPanelProvider --}}
<div
    x-data="notificationSoundPlayer()"
    x-init="init()"
    x-on:notification-sent.window="handleNotification($event.detail)"
    x-on:filament-notification-sent.window="handleNotification($event.detail)"
    class="hidden"
>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationSoundPlayer', () => ({
        sounds: {},
        muted: false,
        volume: 0.5,

        soundMap: {
            'crud': '{{ asset('sounds/crud.mp3') }}',
            'delivery': '{{ asset('sounds/delivery.mp3') }}',
            'shipped': '{{ asset('sounds/shipped.mp3') }}',
            'sync': '{{ asset('sounds/sync.mp3') }}',
            'error': '{{ asset('sounds/error.mp3') }}',
            'log': '{{ asset('sounds/log.mp3') }}',
            'alert': '{{ asset('sounds/alert.mp3') }}',
        },

        init() {
            this.preloadSounds();
            this.listenEchoNotifications();
        },

        preloadSounds() {
            for (const [key, src] of Object.entries(this.soundMap)) {
                try {
                    const audio = new Audio(src);
                    audio.preload = 'auto';
                    audio.volume = this.volume;
                    this.sounds[key] = audio;
                } catch (e) {
                    console.warn(`[NotificationSound] Could not preload: ${key}`, e);
                }
            }
        },

        listenEchoNotifications() {
            if (typeof window.Echo === 'undefined') {
                console.warn('[NotificationSound] Echo not available, skipping websocket listener');
                return;
            }

            const userId = document.head.querySelector('meta[name="user-id"]')?.content;
            if (!userId) {
                return;
            }

            window.Echo.private(`App.Models.Auth.User.${userId}`)
                .notification((notification) => {
                    const sound = notification?.viewData?.sound
                        || notification?.data?.viewData?.sound
                        || notification?.data?.sound
                        || notification?.sound
                        || 'crud';
                    this.play(sound);
                });
        },

        handleNotification(detail) {
            const sound = detail?.sound || detail?.data?.sound || 'crud';
            this.play(sound);
        },

        play(soundKey) {
            if (this.muted) return;

            const audio = this.sounds[soundKey] || this.sounds['crud'];
            if (!audio) return;

            audio.currentTime = 0;
            audio.volume = this.volume;
            audio.play().catch(e => {
                console.warn(`[NotificationSound] Autoplay blocked for: ${soundKey}`, e);
            });
        },

        toggleMute() {
            this.muted = !this.muted;
        },

        setVolume(level) {
            this.volume = Math.max(0, Math.min(1, level));
            for (const audio of Object.values(this.sounds)) {
                audio.volume = this.volume;
            }
        },
    }));
});
</script>

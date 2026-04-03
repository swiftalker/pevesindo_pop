{{-- Alpine.js Notification Sound Player with Looping Support --}}
{{-- Injected via PanelsRenderHook::BODY_END in AdminPanelProvider --}}
<div
    x-data="notificationSoundPlayer()"
    x-init="init()"
    x-on:notificationSent.camel.window="handleNotification($event.detail)"
    x-on:echo:notification.window="handleNotification($event.detail)"
    x-on:stop-notification-sound.window="stopLoop($event.detail?.sound)"
    x-on:mark-as-read-all.window="stopAllLoops()"
    class="hidden"
>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationSoundPlayer', () => ({
        sounds: {},
        muted: false,
        volume: 0.5,
        loopIntervals: {},
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
                    console.warn('[NotificationSound] Could not preload: ' + key, e);
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

            const self = this;

            window.Echo.private(`App.Models.Auth.User.${userId}`)
                .notification((notification) => {
                    const data = notification?.viewData || notification?.data?.viewData || notification;
                    const sound = data?.sound || 'crud';
                    const loop = data?.loop || false;
                    self.play(sound);
                    if (loop) {
                        self.startLoop(sound);
                    }
                });
        },

        handleNotification(detail) {
            const data = detail?.viewData || detail?.data?.viewData || {};
            const sound = data?.sound || detail?.sound || 'crud';
            const loop = data?.loop || false;
            this.play(sound);
            if (loop) {
                this.startLoop(sound);
            }
        },

        play(soundKey) {
            if (this.muted) return;
            const audio = this.sounds[soundKey] || this.sounds['crud'];
            if (!audio) return;
            audio.currentTime = 0;
            audio.volume = this.volume;
            audio.play().catch(() => {});
        },

        startLoop(soundKey) {
            const self = this;

            if (this.loopIntervals[soundKey]) {
                clearInterval(this.loopIntervals[soundKey]);
            }

            this.loopIntervals[soundKey] = setInterval(() => {
                self.play(soundKey);
            }, 2000);
        },

        stopLoop(soundKey) {
            if (this.loopIntervals[soundKey]) {
                clearInterval(this.loopIntervals[soundKey]);
                delete this.loopIntervals[soundKey];
            }
        },

        stopAllLoops() {
            for (const key in this.loopIntervals) {
                clearInterval(this.loopIntervals[key]);
            }
            this.loopIntervals = {};
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

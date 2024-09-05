import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    cluster: import.meta.env.VITE_REVERB_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_REVERB_HOST
        ? import.meta.env.VITE_REVERB_HOST
        : `ws-${import.meta.env.VITE_REVERB_APP_CLUSTER}.REVERB.com`,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

const video = document.querySelector('video');
const button = document.querySelector('button');
const pulse = document.querySelector('#pulse');

button.addEventListener('click', () => {
    button.classList.add('hidden');
    pulse.classList.remove('hidden');
});

let hasSynced = false;

window.Echo.channel(`laracon-us.${window.channelId}`)
    .listen('VolumeChanged', (e) => {
        const diff = e.diff * 0.1;
        video.volume = Math.min(1, Math.max(0, video.volume + diff));
    })
    .listen('PlayPause', (e) => {
        if (e.playing) {
            video.play();
            pulse.classList.add('animate-pulse', 'size-24');
            pulse.classList.remove('opacity-25', 'size-12');
        } else {
            pulse.classList.remove('animate-pulse', 'size-24');
            pulse.classList.add('opacity-25', 'size-12');
            video.pause();
        }
    })
    .listen('Seek', (e) => {
        video.currentTime = e.newTime;
    })
    .listen('Sync', (e) => {
        if (!hasSynced) {
            video.currentTime = e.newTime;
            pulse.classList.add('animate-pulse', 'size-24');
            pulse.classList.remove('opacity-25', 'size-12');
            video.play();
        }

        hasSynced = true;
    });

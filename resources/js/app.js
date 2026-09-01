import './layout.js';
import 'laravel-datatables-vite';
import TomSelect from 'tom-select';
import { Fancybox } from '@fancyapps/ui';
import SignaturePad from 'signature_pad';
import { Chart, registerables } from 'chart.js';
import '/node_modules/flatpickr/dist/flatpickr.css';
import '/node_modules/flatpickr/dist/plugins/monthSelect/style.css';
import flatpickr from 'flatpickr';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import $ from 'jquery';
import introJs from 'intro.js';

Chart.register(...registerables);

window.Chart = Chart;
window.TomSelect = TomSelect;
window.Fancybox = Fancybox;
window.SignaturePad = SignaturePad;
window.flatpickr = flatpickr;
window.monthSelectPlugin = monthSelectPlugin;
window.Pusher = Pusher;
window.$ = $;
window.introJs = introJs;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const defaultWsPort = window.location.port
  ? parseInt(window.location.port)
  : (window.location.protocol === 'https:' ? 443 : 80);

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
  wsPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : defaultWsPort,
  wssPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : defaultWsPort,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? import.meta.env.VITE_PUSHER_SCHEME ?? window.location.protocol.replace(':', '')) === 'https',
  enabledTransports: ['ws', 'wss'],
  disabledStats: true,
});

// Multi-Tab Notification Synchronization
if (typeof window !== 'undefined' && 'BroadcastChannel' in window) {
  window.__notifChannel = new BroadcastChannel('app_notifications');
  window.__notifChannel.onmessage = (event) => {
    if (event.data?.type === 'MARK_ALL_READ') {
      window.Livewire?.dispatch('reset-unread-count');
    } else if (event.data?.type === 'NEW_NOTIFICATION') {
      window.Livewire?.dispatch('increment-unread-count');
    }
  };
}

// Enterprise WebSocket Connection Monitor & Graceful Fallback Polling
if (window.Echo?.connector?.pusher) {
  let fallbackInterval = null;

  const startFallback = () => {
    if (fallbackInterval) return;
    fallbackInterval = setInterval(async () => {
      try {
        const res = await window.axios.get('/notifications/unread-count');
        if (typeof res.data?.unread === 'number') {
          window.Livewire?.dispatch('set-unread-count', { count: res.data.unread });
        }
      } catch (e) {
        // ignore network error during background fallback
      }
    }, 60000);
  };

  const stopFallback = () => {
    if (fallbackInterval) {
      clearInterval(fallbackInterval);
      fallbackInterval = null;
    }
  };

  window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    if (['unavailable', 'disconnected', 'failed'].includes(states.current)) {
      startFallback();
    } else if (states.current === 'connected') {
      stopFallback();
    }
  });
}

// Pusher.logToConsole = true;


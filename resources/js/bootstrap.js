import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Optional: Laravel Echo (Pusher) if env is configured
try {
  const driver = import.meta?.env?.VITE_BROADCAST_DRIVER || ''
  if (driver.toLowerCase() === 'pusher') {
    const Echo = (await import('laravel-echo')).default
    const Pusher = (await import('pusher-js')).default
    window.Pusher = Pusher
    window.Echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
      wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
      wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
      forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    })
  }
} catch (e) {
  // ignore if Echo cannot be initialized
}

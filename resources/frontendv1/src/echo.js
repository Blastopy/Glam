import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

/**
 * Lazily creates a single Echo instance. Called after login (once we have a
 * token) rather than at app boot, since private channels need Authorization
 * on the /broadcasting/auth handshake.
 */
export function initEcho() {
  if (echoInstance) return echoInstance

  const token = localStorage.getItem('auth_token')

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })

  return echoInstance
}

export function getEcho() {
  return echoInstance
}

/** Call on logout so the next login gets a fresh, correctly-authed connection. */
export function disconnectEcho() {
  echoInstance?.disconnect()
  echoInstance = null
}

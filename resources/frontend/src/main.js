import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { initEcho } from './echo'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#app')

// Reconnect the websocket on a hard page refresh if a session already exists
if (localStorage.getItem('auth_token')) {
  initEcho()
}

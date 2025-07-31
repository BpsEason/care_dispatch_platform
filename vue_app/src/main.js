import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import axios from 'axios'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

# 設定 Axios 每次請求都帶上 withCredentials，用於處理 Laravel Sanctum 的 Cookie
axios.defaults.withCredentials = true;

app.mount('#app')

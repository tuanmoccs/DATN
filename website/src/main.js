import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import './assets/main.css'
import router from './router'
import axiosPlugin from './plugins/axios'
import apiPlugin from './plugins/api'

const app = createApp(App)

app.use(router)
app.use(axiosPlugin)  // Axios phải được cài đặt trước
app.use(apiPlugin)    // API plugin sử dụng axios
app.mount('#app')

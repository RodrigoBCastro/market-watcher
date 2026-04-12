import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'

document.documentElement.setAttribute('data-theme', 'dark-blue')

createApp(App).use(router).mount('#app')

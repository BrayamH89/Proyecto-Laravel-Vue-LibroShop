import { createApp } from 'vue'
import AppRoot from './AppRoot.vue'  // 👈 el nuevo archivo
import router from './router'
import 'bootstrap/dist/css/bootstrap.min.css'
import './testApi'


import 'bootstrap'

createApp(AppRoot).use(router).mount('#app')

import './bootstrap';

import { createApp } from 'vue'
import App from './components/App.vue'
import router from './router';

// Importa Bootstrap JS y CSS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'bootstrap-icons/font/bootstrap-icons.css'

import 'vue-multiselect/dist/vue-multiselect.css'

const app = createApp(App);
app.use(router);
app.mount('#app');

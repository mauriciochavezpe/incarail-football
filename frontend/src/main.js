import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { session } from './session'
import './styles.css'

// Antes de montar, refresca usuario y permisos si hay un token guardado.
session.restore().finally(() => {
  createApp(App).use(router).mount('#app')
})

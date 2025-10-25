<template>
  <div class="page-wrapper">
    <div class="contenedor__todo">
      <!-- Panel de fondo -->
      <div class="caja__trasera">
        <!-- Lado izquierdo: REGISTRARSE -->
        <div class="caja__trasera-register" :class="{ active: !isLogin }">
          <h3>¿Ya tienes cuenta?</h3>
          <p>Inicia sesión para entrar en la página</p><br>
          <button id="btn__iniciar-sesion" @click="showLogin" :disabled="loading">Iniciar Sesión</button>
        </div>

        <!-- Lado derecho: LOGIN -->
        <div class="caja__trasera-login" :class="{ active: isLogin }">
          <h3>¿Aún no tienes cuenta?</h3>
          <p>Regístrate para que puedas iniciar sesión</p><br>
          <button id="btn__registrarse" @click="showRegister" :disabled="loading">Registrarse</button>
        </div>
      </div>

      <!-- Contenedor blanco (formularios) -->
      <div
        class="contenedor__login-register"
        :class="{
          'shift-left': !isLogin,
          'shift-right': isLogin
        }"
      >
        <!-- Formulario Login -->
        <form
          @submit.prevent="handleLogin"
          class="formulario__login"
          :class="{ active: isLogin }"
        >
          <h2>Iniciar Sesión</h2>
          <div class="input-container">
            <input
              type="email"
              v-model="loginData.email"
              placeholder="Correo Electrónico"
              required
              class="input"
              :disabled="loading"
            >
            <i class="bi bi-person-circle"></i>
          </div>

          <div class="password-container">
            <input
              v-model="loginData.password"
              placeholder="Contraseña"
              required
              class="input"
              :type="showPasswordLogin ? 'text' : 'password'"
              :disabled="loading"
            >
            <i
              :class="showPasswordLogin ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill'"
              @click="togglePasswordLogin"
              class="toggle-eye"
            ></i>
          </div>

          <button type="submit" class="submit" :disabled="loading">
            <span v-if="loading">Cargando...</span>
            <span v-else>Entrar</span>
          </button>
        </form>

        <!-- Formulario Registro -->
        <form
          @submit.prevent="handleRegister"
          class="formulario__register"
          :class="{ active: !isLogin }"
        >
          <h2>Registrarse</h2>
          <input 
            type="text" 
            v-model="registerData.name" 
            placeholder="Nombre Completo" 
            required 
            :disabled="loading"
            class="input"
          >
          <input 
            type="email" 
            v-model="registerData.email" 
            placeholder="Correo Electrónico" 
            required 
            :disabled="loading"
            class="input"
          >
          <div class="password-container">
            <input
              v-model="registerData.password"
              placeholder="Contraseña"
              required
              class="input"
              :type="showPasswordRegister ? 'text' : 'password'"
              :disabled="loading"
            >
            <i
              :class="showPasswordRegister ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
              @click="togglePasswordRegister"
              class="toggle-eye"
            ></i>
          </div>
          <input 
            type="password" 
            v-model="registerData.password_confirmation" 
            placeholder="Confirmar contraseña" 
            required 
            :disabled="loading"
            class="input"
          >
          <button type="submit" class="submit" :disabled="loading">
            <span v-if="loading">Cargando...</span>
            <span v-else>Registrarse</span>
          </button>
        </form>
      </div>

      <!-- Mensajes de error/éxito -->
      <transition name="fade">
        <div v-if="errorMessage" class="alert alert-error">
          <i class="fa-solid fa-triangle-exclamation"></i> {{ errorMessage }}
        </div>
      </transition>

      <transition name="fade">
        <div v-if="successMessage" class="alert alert-success">
          <i class="fa-solid fa-circle-check"></i> {{ successMessage }}
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import axios from '@/api/axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const isLogin = ref(true)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showPasswordLogin = ref(false)
const showPasswordRegister = ref(false)

const loginData = ref({ email: '', password: '' })
const registerData = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

async function showLogin() {
  if (!isLogin.value) {
    isLogin.value = true
    errorMessage.value = ''
    successMessage.value = ''
    await nextTick()
  }
}

async function showRegister() {
  if (isLogin.value) {
    isLogin.value = false
    errorMessage.value = ''
    successMessage.value = ''
    await nextTick()
  }
}

function togglePasswordLogin() { showPasswordLogin.value = !showPasswordLogin.value }
function togglePasswordRegister() { showPasswordRegister.value = !showPasswordRegister.value }

async function handleLogin() {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const res = await axios.post('/login', loginData.value)
    
    // Guardar token y usuario en localStorage
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    
    successMessage.value = 'Inicio de sesión exitoso. Redirigiendo...'
    
    setTimeout(() => {
      // Redirigir según el rol del usuario
      if (res.data.user.role === 'admin') {
        router.push('/admin/dashboard')
      } else {
        router.push('/users/home')
      }
    }, 1500)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Credenciales inválidas. Verifica tu correo y contraseña.'
  } finally {
    loading.value = false
  }
}

async function handleRegister() {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  
  // Validar que las contraseñas coincidan
  if (registerData.value.password !== registerData.value.password_confirmation) {
    errorMessage.value = 'Las contraseñas no coinciden'
    loading.value = false
    return
  }
  
  try {
    // El backend asigna automáticamente role_id = 2 (user)
    const res = await axios.post('/register', registerData.value)
    successMessage.value = 'Registro exitoso. Redirigiendo al inicio de sesión...'
    
    // Limpiar formulario
    registerData.value = {
      name: '',
      email: '',
      password: '',
      password_confirmation: ''
    }
    
    setTimeout(() => {
      showLogin()
    }, 2000)
  } catch (error) {
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      errorMessage.value = Object.values(errors).flat().join('. ')
    } else {
      errorMessage.value = error.response?.data?.message || 'Error en registro. Verifica los datos.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.page-wrapper {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f5f5f5;
  padding: 20px;
}

/* Contenedor principal */
.contenedor__todo {
  position: relative;
  width: 900px;
  height: 520px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Fondo rojo */
.caja__trasera {
  position: absolute;
  width: 100%;
  height: 100%;
  background-color: #e8617b;
  border-radius: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 50px;
  z-index: 1;
  transition: all 0.6s ease-in-out;
}

/* Paneles dentro del fondo */
.caja__trasera div {
  color: white;
  width: 45%;
  text-align: center;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.6s ease;
}

.caja__trasera div.active {
  opacity: 1;
  transform: translateY(0);
}

.caja__trasera h3 {
  font-size: 22px;
  margin-bottom: 10px;
}

.caja__trasera button {
  background: transparent;
  border: 2px solid #fff;
  color: #fff;
  border-radius: 10px;
  padding: 10px 30px;
  cursor: pointer;
  transition: all 0.3s ease;
}
.caja__trasera button:hover:not(:disabled) {
  background: #fff;
  color: #e61b43;
  transform: scale(1.05);
}

.caja__trasera button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Contenedor blanco (formularios) */
.contenedor__login-register {
  position: absolute;
  width: 420px;
  height: 460px;
  background: white;
  border-radius: 20px;
  z-index: 2;
  box-shadow: 0 10px 25px rgba(0,0,0,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.8s ease;
}

/* AHORA el login a la IZQUIERDA y registro a la DERECHA */
.contenedor__login-register.shift-right {
  transform: translateX(-230px); /* Login: mueve a la izquierda */
}
.contenedor__login-register.shift-left {
  transform: translateX(230px); /* Registro: mueve a la derecha */
}

/* Formularios */
.formulario__login,
.formulario__register {
  position: absolute;
  width: 100%;
  padding: 40px;
  opacity: 0;
  transform: translateX(100px);
  transition: all 0.6s ease;
  pointer-events: none;
}

.formulario__login.active,
.formulario__register.active {
  opacity: 1;
  transform: translateX(0);
  position: relative;
  pointer-events: auto;
}

h2 {
  color: #e61b43;
  text-align: center;
  margin-bottom: 20px;
}

input, select {
  width: 100%;
  padding: 10px;
  margin-top: 12px;
  background: #f2f2f2;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: background 0.3s ease;
}

.input {
  width: 100%;
  padding: 10px;
  margin-top: 12px;
  background: #f2f2f2;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: background 0.3s ease;
}

input:focus, select:focus, .input:focus {
  background: #e9ecef;
}

input:disabled, .input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

button.submit, button[type="submit"] {
  width: 100%;
  background: #e61b43;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px;
  margin-top: 25px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s ease;
}

button.submit:hover:not(:disabled), button[type="submit"]:hover:not(:disabled) {
  background: #c2183f;
  transform: translateY(-2px);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.password-container {
  position: relative;
  width: 100%;
}

.input-container {
  position: relative;
  width: 100%;
}

.input-container i {
  position: absolute;
  right: 12px;
  top: 26px;
  color: #555;
}

.toggle-eye {
  position: absolute;
  right: 12px;
  top: 26px;
  color: #555;
  cursor: pointer;
  transition: color 0.3s ease;
}

.toggle-eye:hover {
  color: #e61b43;
}

/* Mensajes */
.alert {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 15px 25px;
  border-radius: 10px;
  font-weight: 500;
  z-index: 1000;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  gap: 10px;
}

.alert-error { 
  background: #fee;
  color: #b91c1c;
  border: 1px solid #f5c6cb;
}

.alert-success { 
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

/* Fade mensajes */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 850px) {
  .contenedor__todo { 
    width: 100%;
    height: auto;
    flex-direction: column;
  }
  .caja__trasera {
    flex-direction: column;
    height: 300px;
    padding: 20px;
  }
  .caja__trasera div {
    width: 100%;
    margin-bottom: 15px;
  }
  .contenedor__login-register {
    transform: translateX(0) !important;
    width: 90%;
    max-width: 420px;
  }
}
</style>
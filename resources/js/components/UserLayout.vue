<template>
  <div class="user-layout">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="header-left">
          <!-- Botón toggle sidebar (móviles) -->
          <button
            class="menu-btn"
            @click="toggleSidebar"
            aria-label="Abrir menú de filtros"
          >
            <i class="fas fa-bars"></i>
          </button>

          <!-- Logo -->
          <router-link to="/users/home" class="logo">
            <i class="bi bi-book-half"></i>
            <span class="logo-text">Librería Digital</span>
          </router-link>
        </div>

        <!-- Navegación -->
        <nav class="nav-desktop">
          <router-link to="/users/home" class="nav-link">
            <i class="bi bi-house-fill"></i>
            <span>Catálogo</span>
          </router-link>
          <router-link to="/users/compras" class="nav-link">
            <i class="bi bi-cart4"></i>
            <span>Mis Compras</span>
          </router-link>
          <button @click="logout" class="btn-logout">
            <i class="bi bi-door-open"></i>
            <span>Salir</span>
          </button>
        </nav>
      </div>
    </header>

    <!-- Layout principal -->
    <div class="layout-content">
      <!-- Sidebar de filtros -->
      <aside :class="['sidebar', { 'sidebar-open': sidebarOpen }]">
        <div class="sidebar-header">
          <h2>Filtros</h2>
          <button class="close-btn" @click="closeSidebar" aria-label="Cerrar">
            <i class="bi bi-funnel-fill"></i>
          </button>
        </div>

        <div class="sidebar-body">
          <form @submit.prevent="applyFilters" class="filters-form">
            <!-- Título -->
            <h3 class="section-title">Buscar libros</h3>
            
            <!-- Categoría -->
            <div class="form-group">
              <label for="categoria">Categoría</label>
              <select v-model="filters.categoria" id="categoria" class="form-control">
                <option value="">Todas las categorías</option>
                <option v-for="cat in categorias" :key="cat.id" :value="cat.slug">
                  {{ cat.nombre }}
                </option>
              </select>
            </div>

            <!-- Rango de precio -->
            <div class="price-group">
              <div class="form-group">
                <label for="min">Precio mín</label>
                <input
                  type="number"
                  v-model.number="filters.min"
                  id="min"
                  placeholder="0"
                  min="0"
                  class="form-control"
                />
              </div>
              <div class="form-group">
                <label for="max">Precio máx</label>
                <input
                  type="number"
                  v-model.number="filters.max"
                  id="max"
                  placeholder="999999"
                  min="0"
                  class="form-control"
                />
              </div>
            </div>

            <!-- Botón limpiar -->
            <button type="button" @click="clearFilters" class="btn-clear">
              Limpiar
            </button>
          </form>
        </div>
      </aside>

      <!-- Contenido principal -->
      <main class="main-content">
        <router-view />
      </main>
    </div>

    <!-- Overlay para móviles -->
    <div
      :class="['overlay', { active: sidebarOpen }]"
      @click="closeSidebar"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from '@/api/axios'

const router = useRouter()
const route = useRoute()

// Estados
const sidebarOpen = ref(false)
const categorias = ref([])
const loadingCategorias = ref(false)
const filters = ref({
  categoria: '',
  min: '',
  max: '',
})

// Métodos
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
  sidebarOpen.value = false
}

const fetchCategorias = async () => {
  loadingCategorias.value = true
  try {
    const response = await axios.get('/categorias')
    categorias.value = response.data
  } catch (error) {
    console.error('Error al cargar categorías:', error)
  } finally {
    loadingCategorias.value = false
  }
}

const applyFilters = () => {
  if (filters.value.min && filters.value.max && parseFloat(filters.value.min) > parseFloat(filters.value.max)) {
    alert('El precio mínimo no puede ser mayor al máximo.')
    return
  }
  
  const query = {}
  if (filters.value.categoria) query.categoria = filters.value.categoria
  if (filters.value.min) query.min = filters.value.min
  if (filters.value.max) query.max = filters.value.max
  
  router.push({ path: '/users/home', query })
  closeSidebar()
}

const clearFilters = () => {
  filters.value = { categoria: '', min: '', max: '' }
  router.push({ path: '/users/home' })
  closeSidebar()
}

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

// Watch para sincronizar filtros con query params
watch(() => route.query, (newQuery) => {
  filters.value.categoria = newQuery.categoria || ''
  filters.value.min = newQuery.min || ''
  filters.value.max = newQuery.max || ''
}, { immediate: true })

// Aplicar filtros automáticamente cuando cambian
watch(filters, () => {
  applyFilters()
}, { deep: true })

// Inicialización
onMounted(() => {
  fetchCategorias()
})
</script>

<style scoped>
/* Layout principal */
.user-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #f3f4f6;
}

/* Header */
.header {
  background: #FF6B6B;
  color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1920px;
  margin: 0 auto;
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.menu-btn {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.5rem;
  transition: background 0.3s ease;
}

.menu-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: white;
  text-decoration: none;
  font-size: 1.25rem;
  font-weight: 700;
  transition: opacity 0.3s ease;
}

.logo:hover {
  opacity: 0.9;
}

.logo i {
  font-size: 1.5rem;
}

.nav-desktop {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  color: white;
  text-decoration: none;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background 0.3s ease;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.2);
}

.nav-link.router-link-active {
  background: rgba(255, 255, 255, 0.3);
}

.btn-logout {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s ease;
}

.btn-logout:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Layout de contenido */
.layout-content {
  display: flex;
  flex: 1;
  max-width: 1920px;
  margin: 0 auto;
  width: 100%;
}

/* Sidebar */
.sidebar {
  width: 300px;
  background: white;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.sidebar-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.close-btn {
  display: none;
  background: none;
  border: none;
  color: #6b7280;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.375rem;
  transition: background 0.3s ease;
}

.close-btn:hover {
  background: #f3f4f6;
}

.sidebar-body {
  padding: 1.5rem;
  overflow-y: auto;
}

/* Formulario de filtros */
.filters-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.price-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Botón limpiar */
.btn-clear {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 0.5rem;
}

.btn-clear:hover {
  background: #4b5563;
}

/* Contenido principal */
.main-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  min-height: calc(100vh - 70px);
  background: white;
}

/* Overlay */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.overlay.active {
  opacity: 1;
  visibility: visible;
}

/* Responsive */
@media (max-width: 1024px) {
  .sidebar {
    width: 280px;
  }

  .main-content {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .menu-btn {
    display: block;
  }

  .nav-desktop {
    display: none;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 320px;
    z-index: 100;
    transform: translateX(-100%);
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  }

  .sidebar-open {
    transform: translateX(0);
  }

  .close-btn {
    display: block;
  }

  .main-content {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .header-content {
    padding: 0.75rem 1rem;
  }

  .logo-text {
    display: none;
  }

  .sidebar {
    width: 100%;
  }

  .main-content {
    padding: 1rem 0.5rem;
  }
}
</style>
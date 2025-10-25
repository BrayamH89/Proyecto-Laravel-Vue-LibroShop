<template>
  <div class="book-catalog">
    <!-- Header con título -->
    <div class="catalog-header">
      <h1 class="catalog-title">
        <i class="bi bi-journals"></i>
        Catálogo de Libros
      </h1>
      <p class="catalog-subtitle">Encuentra tu próxima lectura</p>
    </div>

    <!-- Mensaje de éxito -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>

    <!-- Mensaje de error -->
    <div v-if="errorMessage" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ errorMessage }}
    </div>

    <!-- Loading -->
    <div v-if="loading && !libros.length" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando libros...</p>
    </div>

    <!-- Grid de libros -->
    <div v-else-if="libros.length" class="books-grid">
      <div v-for="libro in libros" :key="libro.id" class="book-card">
        <!-- Imagen del libro -->
        <div class="book-image-container">
          <img
            v-if="libro.imagen_path"
            :src="getImageUrl(libro.imagen_path)"
            :alt="libro.titulo"
            class="book-image"
          >
          <div v-else class="book-no-image">
            <i class="fas fa-book"></i>
          </div>
          
          <!-- Badge de categoría -->
          <div v-if="libro.categorias && libro.categorias.length" class="book-category-badge">
            {{ libro.categorias[0].nombre }}
          </div>
        </div>

        <!-- Info del libro -->
        <div class="book-info">
          <h3 class="book-title">{{ libro.titulo }}</h3>
          <p class="book-author">
            <i class="fas fa-user"></i>
            {{ libro.autor || 'Autor desconocido' }}
          </p>

          <!-- Categorías adicionales -->
          <div v-if="libro.categorias && libro.categorias.length > 1" class="book-categories">
            <span 
              v-for="cat in libro.categorias.slice(1, 3)" 
              :key="cat.id"
              class="category-tag"
            >
              {{ cat.nombre }}
            </span>
          </div>

          <!-- Precio -->
          <div class="book-price">
            <span class="price-amount">${{ formatPrice(libro.precio_cents) }}</span>
            <span class="price-currency">{{ libro.moneda || 'COP' }}</span>
          </div>

          <!-- Botón de compra - MODIFICADO para redirigir -->
          <button 
            @click="irAComprar(libro.id)" 
            class="btn-buy"
          >
            <i class="fas fa-shopping-cart"></i>
            <span>Comprar ahora</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Sin resultados -->
    <div v-else-if="!loading" class="no-results">
      <i class="fas fa-book-open"></i>
      <h3>No se encontraron libros</h3>
      <p>Intenta ajustar los filtros de búsqueda</p>
    </div>

    <!-- Paginación -->
    <div v-if="pagination.total > pagination.per_page" class="pagination">
      <button 
        @click="changePage(pagination.current_page - 1)" 
        :disabled="pagination.current_page === 1"
        class="btn-page"
      >
        <i class="fas fa-chevron-left"></i>
        Anterior
      </button>
      
      <div class="page-numbers">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="changePage(page)"
          :class="['btn-page-number', { active: page === pagination.current_page }]"
        >
          {{ page }}
        </button>
      </div>
      
      <button 
        @click="changePage(pagination.current_page + 1)" 
        :disabled="pagination.current_page === pagination.last_page"
        class="btn-page"
      >
        Siguiente
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/api/axios'

const route = useRoute()
const router = useRouter()

// Estados
const libros = ref([])
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0
})

// Páginas visibles en la paginación
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  let start = Math.max(1, current - 2)
  let end = Math.min(last, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Función para formatear precio
const formatPrice = (cents) => {
  if (!cents) return '0.00'
  return (cents / 100).toFixed(2)
}

// Método para obtener URL de imagen
const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${path}`
}

// Método para cargar libros
const loadLibros = async (page = 1) => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const params = {
      page,
      ...route.query
    }

    const response = await axios.get('/libros', { params })
    
    if (response.data.data) {
      libros.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        per_page: response.data.per_page,
        total: response.data.total
      }
    } else {
      libros.value = response.data
    }
  } catch (error) {
    console.error('Error cargando libros:', error)
    errorMessage.value = 'Error al cargar los libros. Por favor intenta de nuevo.'
    setTimeout(() => errorMessage.value = '', 5000)
  } finally {
    loading.value = false
  }
}

// Método para cambiar de página
const changePage = (page) => {
  loadLibros(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ✅ NUEVO MÉTODO - Redirigir a la vista de compra
const irAComprar = (libroId) => {
  router.push({
    name: 'UserCheckout',
    params: { id: libroId }
  })
}

// Watch para recargar cuando cambien los filtros
watch(() => route.query, () => {
  loadLibros(1)
}, { deep: true })

// Inicialización
onMounted(() => {
  loadLibros()
  
  // Verificar si hay mensaje de éxito desde URL
  if (route.query.success) {
    successMessage.value = route.query.success
    setTimeout(() => {
      successMessage.value = ''
      // Limpiar query params
      router.replace({ query: {} })
    }, 5000)
  }
})
</script>

<style scoped>
.book-catalog {
  max-width: 100%;
  padding: 0;
}

/* Header */
.catalog-header {
  margin-bottom: 2rem;
}

.catalog-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.catalog-title i {
  color: #667eea;
}

.catalog-subtitle {
  color: #6b7280;
  margin: 0;
  font-size: 1rem;
}

/* Alertas */
.alert {
  padding: 1rem 1.25rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border-left: 4px solid #10b981;
}

.alert-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #ef4444;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Grid de libros */
.books-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

/* Card del libro */
.book-card {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.book-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.book-image-container {
  position: relative;
  width: 100%;
  height: 350px;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.book-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.book-card:hover .book-image {
  transform: scale(1.05);
}

.book-no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 4rem;
  opacity: 0.5;
}

.book-category-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.95);
  color: #667eea;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
}

/* Info del libro */
.book-info {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex: 1;
}

.book-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #f12997;
  margin: 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2; /* Chrome, Safari, Edge */
  line-clamp: 2;         /* Firefox y navegadores modernos */
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.book-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.book-author i {
  color: #9ca3af;
}

.book-categories {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.category-tag {
  padding: 0.25rem 0.625rem;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.book-price {
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
}

.price-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #10b981;
}

.price-currency {
  font-size: 0.875rem;
  color: #6b7280;
  margin-left: 0.25rem;
}

/* Botón de compra */
.btn-buy {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.btn-buy:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-buy:active {
  transform: translateY(0);
}

/* Sin resultados */
.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #9ca3af;
  text-align: center;
}

.no-results i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-results h3 {
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
  color: #6b7280;
}

.no-results p {
  margin: 0;
  color: #9ca3af;
}

/* Paginación */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.btn-page,
.btn-page-number {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.875rem;
}

.btn-page:hover:not(:disabled),
.btn-page-number:hover:not(.active) {
  background: #667eea;
  color: white;
}

.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-page-number.active {
  background: #667eea;
  color: white;
}

.page-numbers {
  display: flex;
  gap: 0.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .books-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .catalog-title {
    font-size: 1.5rem;
  }

  .books-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }

  .book-image-container {
    height: 280px;
  }

  .book-info {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .books-grid {
    grid-template-columns: 1fr;
  }

  .book-image-container {
    height: 320px;
  }

  .pagination {
    flex-direction: column;
    gap: 0.75rem;
  }

  .btn-page {
    width: 100%;
    justify-content: center;
  }

  .page-numbers {
    width: 100%;
    justify-content: center;
  }
}
</style>
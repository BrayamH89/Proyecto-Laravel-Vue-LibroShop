<template>
  <div class="compras-container">
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-shopping-bag"></i>
        Mis Compras
      </h1>
      <p class="page-subtitle">Historial de tus pedidos</p>
    </div>

    <!-- Estadísticas -->
    <div v-if="estadisticas" class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
          <p class="stat-label">Total Compras</p>
          <p class="stat-value">{{ estadisticas.total_compras }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <p class="stat-label">Completadas</p>
          <p class="stat-value">{{ estadisticas.compras_completadas }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
          <p class="stat-label">Pendientes</p>
          <p class="stat-value">{{ estadisticas.compras_pendientes }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
          <p class="stat-label">Total Gastado</p>
          <p class="stat-value">${{ formatPrice(estadisticas.total_gastado_cents) }}</p>
        </div>
      </div>
    </div>

    <!-- Mensajes -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ errorMessage }}
    </div>

    <!-- Loading -->
    <div v-if="loading && !compras.length" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando tus compras...</p>
    </div>

    <!-- Lista de compras -->
    <div v-else-if="compras.length" class="compras-list">
      <div v-for="compra in compras" v-bind:key="compra.id" class="compra-card">
        <!-- Header de la compra -->
        <div class="compra-header">
          <div class="compra-date">
            <i class="fas fa-calendar-alt"></i>
            {{ formatDate(compra.created_at) }}
          </div>
          <div v-bind:class="['compra-badge', compra.estado]">
            {{ formatEstado(compra.estado) }}
          </div>
        </div>

        <!-- Contenido de la compra -->
        <div class="compra-content">
          <!-- Imagen del libro -->
          <div class="compra-image">
            <img
              v-if="compra.libro.imagen_url"
              v-bind:src="compra.libro.imagen_url"
              v-bind:alt="compra.libro.titulo"
            />
            <div v-else class="no-image">
              <i class="fas fa-book"></i>
            </div>
          </div>

          <!-- Info del libro -->
          <div class="compra-info">
            <h3 class="compra-titulo">{{ compra.libro.titulo }}</h3>
            <p class="compra-autor">
              <i class="fas fa-user"></i>
              {{ compra.libro.autor }}
            </p>

            <div v-if="compra.libro.categorias && compra.libro.categorias.length" class="compra-categorias">
              <span 
                v-for="(categoria, index) in compra.libro.categorias" 
                v-bind:key="index"
                class="categoria-tag"
              >
                {{ categoria }}
              </span>
            </div>

            <div class="compra-detalles">
              <div class="detalle-item">
                <i class="fas fa-box"></i>
                <span>Cantidad: {{ compra.cantidad }}</span>
              </div>
              <div class="detalle-item">
                <i class="fas fa-wallet"></i>
                <span>{{ formatMetodoPago(compra.metodo_pago) }}</span>
              </div>
            </div>
          </div>

          <!-- Precio y acciones -->
          <div class="compra-actions">
            <div class="compra-precio">
              <span class="precio-label">Total</span>
              <span class="precio-valor">${{ formatPrice(compra.total_cents) }}</span>
              <span class="precio-moneda">{{ compra.moneda }}</span>
            </div>

            <div class="action-buttons">
              <button 
                class="btn btn-secondary btn-sm"
                v-on:click="verDetalle(compra.id)"
              >
                <i class="fas fa-eye"></i>
                Ver detalle
              </button>

              <button 
                v-if="compra.estado === 'pendiente'"
                class="btn btn-danger btn-sm"
                v-on:click="cancelarCompra(compra.id)"
                v-bind:disabled="cancelando === compra.id"
              >
                <i v-if="cancelando === compra.id" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-times"></i>
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sin compras -->
    <div v-else-if="!loading" class="no-compras">
      <i class="fas fa-shopping-bag"></i>
      <h3>Aún no has realizado compras</h3>
      <p>Explora nuestro catálogo y encuentra tu próximo libro favorito</p>
      <button class="btn btn-primary" v-on:click="irAlCatalogo">
        <i class="fas fa-book"></i>
        Ver catálogo
      </button>
    </div>

    <!-- Paginación -->
    <div v-if="pagination.total > pagination.per_page" class="pagination">
      <button 
        v-on:click="cambiarPagina(pagination.current_page - 1)" 
        v-bind:disabled="pagination.current_page === 1"
        class="btn-page"
      >
        <i class="fas fa-chevron-left"></i>
        Anterior
      </button>
      
      <div class="page-numbers">
        <button
          v-for="page in paginasVisibles"
          v-bind:key="page"
          v-on:click="cambiarPagina(page)"
          v-bind:class="['btn-page-number', { active: page === pagination.current_page }]"
        >
          {{ page }}
        </button>
      </div>
      
      <button 
        v-on:click="cambiarPagina(pagination.current_page + 1)" 
        v-bind:disabled="pagination.current_page === pagination.last_page"
        class="btn-page"
      >
        Siguiente
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <!-- Modal de detalle -->
    <div v-if="compraDetalle" class="modal-overlay" v-on:click="cerrarDetalle">
      <div class="modal-content" v-on:click.stop>
        <div class="modal-header">
          <h3>Detalle de Compra #{{ compraDetalle.id }}</h3>
          <button class="btn-close" v-on:click="cerrarDetalle">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="detalle-grid">
            <div class="detalle-section">
              <h4>Información del Libro</h4>
              <div class="detalle-libro-info">
                <img 
                  v-if="compraDetalle.libro.imagen_url"
                  v-bind:src="compraDetalle.libro.imagen_url"
                  v-bind:alt="compraDetalle.libro.titulo"
                  class="detalle-imagen"
                />
                <div>
                  <p class="detalle-titulo">{{ compraDetalle.libro.titulo }}</p>
                  <p class="detalle-autor">{{ compraDetalle.libro.autor }}</p>
                  <p v-if="compraDetalle.libro.descripcion" class="detalle-descripcion">
                    {{ compraDetalle.libro.descripcion }}
                  </p>
                </div>
              </div>
            </div>

            <div class="detalle-section">
              <h4>Información de la Compra</h4>
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label">Fecha:</span>
                  <span>{{ formatDate(compraDetalle.created_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Cantidad:</span>
                  <span>{{ compraDetalle.cantidad }} unidad(es)</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Precio unitario:</span>
                  <span>${{ formatPrice(compraDetalle.precio_unitario_cents) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Total:</span>
                  <span class="info-total">${{ formatPrice(compraDetalle.total_cents) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Método de pago:</span>
                  <span>{{ formatMetodoPago(compraDetalle.metodo_pago) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Estado:</span>
                  <span v-bind:class="['info-badge', compraDetalle.estado]">
                    {{ formatEstado(compraDetalle.estado) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" v-on:click="cerrarDetalle">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserCompras',
  data() {
    return {
      compras: [],
      estadisticas: null,
      loading: false,
      cancelando: null,
      successMessage: '',
      errorMessage: '',
      compraDetalle: null,
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0
      }
    };
  },
  computed: {
    paginasVisibles() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const pages = [];
      
      const start = Math.max(1, current - 2);
      const end = Math.min(last, current + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  mounted() {
    this.cargarCompras();
    this.cargarEstadisticas();
  },
  methods: {
    cargarCompras(page) {
      if (!page) page = 1;
      this.loading = true;
      this.errorMessage = '';

      const token = localStorage.getItem('token');
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: this.pagination.per_page.toString()
      });

      fetch('/api/compras?' + params.toString(), {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Error al cargar compras');
        }
        return response.json();
      })
      .then(data => {
        this.compras = data.data;
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total
        };
        this.loading = false;
      })
      .catch(error => {
        console.error('Error al cargar compras:', error);
        this.errorMessage = 'Error al cargar tus compras';
        this.loading = false;
      });
    },

    cargarEstadisticas() {
      const token = localStorage.getItem('token');

      fetch('/api/compras/estadisticas', {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        this.estadisticas = data;
      })
      .catch(error => {
        console.error('Error al cargar estadísticas:', error);
      });
    },

    verDetalle(id) {
      const token = localStorage.getItem('token');

      fetch('/api/compras/' + id, {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        this.compraDetalle = data;
      })
      .catch(error => {
        console.error('Error al cargar detalle:', error);
        this.errorMessage = 'Error al cargar el detalle de la compra';
      });
    },

    cerrarDetalle() {
      this.compraDetalle = null;
    },

    cancelarCompra(id) {
      const self = this;
      if (!confirm('¿Estás seguro de que deseas cancelar esta compra?')) {
        return;
      }

      this.cancelando = id;
      const token = localStorage.getItem('token');

      fetch('/api/compras/' + id + '/cancelar', {
        method: 'PATCH',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.message || 'Error al cancelar');
          });
        }
        return response.json();
      })
      .then(function() {
        self.successMessage = 'Compra cancelada exitosamente';
        self.cargarCompras(self.pagination.current_page);
        self.cargarEstadisticas();
        
        setTimeout(function() {
          self.successMessage = '';
        }, 5000);
      })
      .catch(function(error) {
        console.error('Error al cancelar:', error);
        self.errorMessage = error.message || 'Error al cancelar la compra';
        
        setTimeout(function() {
          self.errorMessage = '';
        }, 5000);
      })
      .finally(function() {
        self.cancelando = null;
      });
    },

    cambiarPagina(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.cargarCompras(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    },

    irAlCatalogo() {
      this.$router.push({ name: 'UserHome' });
    },

    formatPrice(cents) {
      if (!cents) return '0.00';
      return (cents / 100).toFixed(2);
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatEstado(estado) {
      const estados = {
        'completada': 'Completada',
        'pendiente': 'Pendiente',
        'cancelada': 'Cancelada'
      };
      return estados[estado] || estado;
    },

    formatMetodoPago(metodo) {
      const metodos = {
        'transferencia': 'Transferencia Bancaria',
        'tarjeta': 'Tarjeta de Crédito/Débito',
        'paypal': 'PayPal',
        'efectivo': 'Efectivo',
        'no_especificado': 'No especificado'
      };
      return metodos[metodo] || metodo;
    }
  }
};
</script>
<template>
  <div class="checkout-container">
    <!-- Loading inicial -->
    <div v-if="loadingLibro" class="loading-page">
      <div class="spinner"></div>
      <p>Cargando información del libro...</p>
    </div>

    <!-- Contenido principal -->
    <div v-else-if="libro.id" class="checkout-content">
      <!-- Título de la página -->
      <div class="page-header">
        <button class="btn-back" v-on:click="volverAtras">
          <i class="fas fa-arrow-left"></i>
          Volver al catálogo
        </button>
        <h1 class="page-title">
          <i class="fas fa-shopping-cart"></i>
          Finalizar Compra
        </h1>
      </div>

      <!-- Detalle del libro -->
      <div class="book-detail-card">
        <div class="book-detail-content">
          <div class="book-image-section">
            <img
              v-if="libro.imagen_path"
              v-bind:src="getImageUrl(libro.imagen_path)"
              v-bind:alt="libro.titulo"
              class="book-detail-image"
            />
            <div v-else class="book-no-image-large">
              <i class="fas fa-book"></i>
            </div>
          </div>

          <div class="book-info-section">
            <h2 class="book-detail-title">{{ libro.titulo }}</h2>
            <p class="book-detail-author">
              <i class="fas fa-user"></i>
              {{ libro.autor || 'Autor desconocido' }}
            </p>
            
            <div v-if="libro.descripcion" class="book-description">
              <h4>Descripción</h4>
              <p>{{ libro.descripcion }}</p>
            </div>

            <div v-if="libro.categorias && libro.categorias.length" class="book-categories">
              <span 
                v-for="cat in libro.categorias" 
                v-bind:key="cat.id"
                class="category-badge"
              >
                {{ cat.nombre }}
              </span>
            </div>

            <div class="price-section">
              <span class="price-label">Precio:</span>
              <span class="price-value">${{ formatPrice(libro.precio_cents) }}</span>
              <span class="price-currency">{{ libro.moneda || 'COP' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulario de compra -->
      <div v-if="!compraRealizada" class="checkout-form-card">
        <h3 class="form-title">
          <i class="fas fa-credit-card"></i>
          Información de pago
        </h3>

        <!-- Mensajes de error -->
        <div v-if="errorMessage" class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i>
          {{ errorMessage }}
        </div>

        <form v-on:submit.prevent="realizarCompra" class="payment-form">
          <!-- Método de pago -->
          <div class="form-group">
            <label for="metodo_pago" class="form-label">
              <i class="fas fa-wallet"></i>
              Método de pago
            </label>
            <select
              id="metodo_pago"
              v-model="form.metodo_pago"
              class="form-control"
              required
              v-bind:disabled="loading"
            >
              <option value="">Selecciona un método</option>
              <option value="transferencia">
                <i class="fas fa-exchange-alt"></i> Transferencia Bancaria
              </option>
              <option value="tarjeta">
                <i class="fas fa-credit-card"></i> Tarjeta de Crédito/Débito
              </option>
              <option value="paypal">
                <i class="fab fa-paypal"></i> PayPal
              </option>
            </select>
          </div>

          <!-- Total a pagar -->
          <div class="total-section">
            <div class="total-row total-final">
              <span>Total a pagar:</span>
              <span class="total-amount-final">${{ formatPrice(libro.precio_cents * form.cantidad) }}</span>
            </div>
          </div>

          <!-- Botones -->
          <div class="form-actions">
            <button
              type="button"
              class="btn btn-secondary"
              v-on:click="volverAtras"
              v-bind:disabled="loading"
            >
              <i class="fas fa-times"></i>
              Cancelar
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              v-bind:disabled="loading || !form.metodo_pago"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-check-circle"></i>
              <span v-if="loading">Procesando...</span>
              <span v-else>Confirmar Compra</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Resumen de compra exitosa -->
      <div v-else class="success-card">
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="success-title">¡Compra realizada con éxito!</h2>
        <p class="success-message">Tu pedido ha sido procesado correctamente</p>

        <div class="purchase-summary">
          <h4>Resumen de tu compra</h4>
          <div class="summary-item">
            <span class="summary-label">Libro:</span>
            <span class="summary-value">{{ compra.libro.titulo }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Cantidad:</span>
            <span class="summary-value">{{ compra.cantidad }} unidad(es)</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Método de pago:</span>
            <span class="summary-value">{{ formatMetodoPago(compra.metodo_pago) }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Total pagado:</span>
            <span class="summary-value price">${{ formatPrice(compra.total_cents) }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Estado:</span>
            <span v-bind:class="['summary-badge', compra.estado]">
              {{ formatEstado(compra.estado) }}
            </span>
          </div>
        </div>

        <div class="success-actions">
          <button class="btn btn-secondary" v-on:click="volverAlCatalogo">
            <i class="fas fa-books"></i>
            Ver más libros
          </button>
          <button class="btn btn-primary" v-on:click="verMisCompras">
            <i class="fas fa-shopping-bag"></i>
            Ver mis compras
          </button>
        </div>
      </div>
    </div>

    <!-- Error: libro no encontrado -->
    <div v-else class="error-page">
      <i class="fas fa-exclamation-circle"></i>
      <h2>Libro no encontrado</h2>
      <p>El libro que intentas comprar no existe o no está disponible</p>
      <button class="btn btn-primary" v-on:click="volverAlCatalogo">
        <i class="fas fa-arrow-left"></i>
        Volver al catálogo
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserCheckout',
  data() {
    return {
      libro: {},
      loadingLibro: false,
      loading: false,
      compraRealizada: false,
      compra: null,
      errorMessage: '',
      form: {
        metodo_pago: '',
        cantidad: 1
      }
    };
  },
  mounted() {
    this.cargarLibro();
  },
  methods: {
    cargarLibro() {
      const libroId = this.$route.params.id;
      this.loadingLibro = true;
      this.errorMessage = '';

      const token = localStorage.getItem('token');

      fetch('/api/libros/' + libroId, {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Libro no encontrado');
        }
        return response.json();
      })
      .then(data => {
        this.libro = data;
        this.loadingLibro = false;
      })
      .catch(error => {
        console.error('Error al cargar el libro:', error);
        this.errorMessage = 'No se pudo cargar la información del libro';
        this.loadingLibro = false;
      });
    },

    realizarCompra() {
      const self = this;
      this.loading = true;
      this.errorMessage = '';

      const token = localStorage.getItem('token');

      fetch('/api/compras', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          libro_id: this.libro.id,
          cantidad: this.form.cantidad
        })
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.message || 'Error al procesar la compra');
          });
        }
        return response.json();
      })
      .then(data => {
        self.compra = data.compra;
        self.compraRealizada = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      })
      .catch(error => {
        console.error('Error en compra:', error);
        
        if (error.message.includes('401') || error.message.includes('Unauthenticated')) {
          self.errorMessage = 'Sesión expirada. Por favor inicia sesión nuevamente.';
          setTimeout(function() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            self.$router.push('/login');
          }, 2000);
        } else {
          self.errorMessage = error.message || 'Error al procesar la compra. Por favor intenta de nuevo.';
        }
      })
      .finally(function() {
        self.loading = false;
      });
    },

    formatPrice(cents) {
      if (!cents) return '0.00';
      return (cents / 100).toFixed(2);
    },

    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
      return baseUrl + '/storage/' + path;
    },

    formatMetodoPago(metodo) {
      const metodos = {
        'transferencia': 'Transferencia Bancaria',
        'tarjeta': 'Tarjeta de Crédito/Débito',
        'paypal': 'PayPal',
        'efectivo': 'Efectivo'
      };
      return metodos[metodo] || metodo;
    },

    formatEstado(estado) {
      const estados = {
        'completada': 'Completada',
        'pendiente': 'Pendiente',
        'cancelada': 'Cancelada'
      };
      return estados[estado] || estado;
    },

    volverAtras() {
      this.$router.back();
    },

    volverAlCatalogo() {
      this.$router.push({ name: 'UserHome' });
    },

    verMisCompras() {
      this.$router.push({ name: 'UserCompras' });
    }
  }
};
</script>

<style scoped>
.checkout-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

/* Loading */
.loading-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
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

/* Header */
.page-header {
  margin-bottom: 2rem;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 1rem;
}

.btn-back:hover {
  border-color: #667eea;
  color: #667eea;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.page-title i {
  color: #667eea;
}

/* Book Detail Card */
.book-detail-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.book-detail-content {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
}

.book-image-section {
  width: 100%;
}

.book-detail-image {
  width: 100%;
  height: auto;
  border-radius: 0.75rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.book-no-image-large {
  width: 100%;
  aspect-ratio: 2/3;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 5rem;
  opacity: 0.6;
}

.book-info-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.book-detail-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.book-detail-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 1rem;
  margin: 0;
}

.book-description h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.book-description p {
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}

.book-categories {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.category-badge {
  padding: 0.375rem 0.875rem;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.price-section {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 2px solid #e5e7eb;
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.price-label {
  font-size: 1rem;
  color: #6b7280;
}

.price-value {
  font-size: 2rem;
  font-weight: 700;
  color: #10b981;
}

.price-currency {
  font-size: 1rem;
  color: #6b7280;
}

/* Checkout Form */
.checkout-form-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 1.5rem 0;
}

.form-title i {
  color: #667eea;
}

.alert {
  padding: 1rem 1.25rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.alert-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #ef4444;
}

.payment-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
}

.form-label i {
  color: #667eea;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #f9fafb;
}

.form-text {
  font-size: 0.875rem;
  color: #6b7280;
}

.total-section {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1rem;
  color: #374151;
}

.total-final {
  padding-top: 0.75rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.25rem;
  font-weight: 700;
}

.total-amount {
  font-weight: 600;
  color: #10b981;
}

.total-amount-final {
  font-size: 1.5rem;
  font-weight: 700;
  color: #10b981;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: white;
  color: #6b7280;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover:not(:disabled) {
  border-color: #d1d5db;
  background: #f9fafb;
}

/* Success Card */
.success-card {
  background: white;
  border-radius: 1rem;
  padding: 3rem 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.success-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 3rem;
  box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
}

.success-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.success-message {
  font-size: 1.125rem;
  color: #6b7280;
  margin: 0 0 2rem 0;
}

.purchase-summary {
  background: #f9fafb;
  padding: 2rem;
  border-radius: 0.75rem;
  margin-bottom: 2rem;
  text-align: left;
}

.purchase-summary h4 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #374151;
  margin: 0 0 1.5rem 0;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-label {
  font-weight: 500;
  color: #6b7280;
}

.summary-value {
  font-weight: 600;
  color: #1f2937;
}

.summary-value.price {
  font-size: 1.25rem;
  color: #10b981;
}

.summary-badge {
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
}

.summary-badge.completada {
  background: #d1fae5;
  color: #065f46;
}

.summary-badge.pendiente {
  background: #fef3c7;
  color: #92400e;
}

.success-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

/* Error Page */
.error-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  color: #ef4444;
  text-align: center;
}

.error-page i {
  font-size: 5rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.error-page h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.error-page p {
  color: #6b7280;
  margin: 0 0 2rem 0;
}

/* Responsive */
@media (max-width: 768px) {
  .checkout-container {
    padding: 1rem 0.5rem;
  }

  .book-detail-content {
    grid-template-columns: 1fr;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .book-detail-title {
    font-size: 1.5rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }

  .success-actions {
    flex-direction: column;
  }

  .success-icon {
    width: 60px;
    height: 60px;
    font-size: 2rem;
  }

  .success-title {
    font-size: 1.5rem;
  }

  .purchase-summary {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .book-detail-card {
    padding: 1.5rem;
  }

  .checkout-form-card {
    padding: 1.5rem;
  }

  .success-card {
    padding: 2rem 1.5rem;
  }

  .page-title {
    font-size: 1.25rem;
  }

  .book-detail-title {
    font-size: 1.25rem;
  }

  .form-title {
    font-size: 1.25rem;
  }
}
</style>
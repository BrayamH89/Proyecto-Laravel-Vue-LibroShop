<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="ventas-container">
      <h1 class="ventas-title">
        <i class="bi bi-cart-check-fill"></i> Gestión de Ventas
      </h1>

      <!-- Mensajes -->
      <div v-if="successMessage" class="alert alert-success mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ successMessage }}
      </div>
      <div v-if="errorMessages.length && !loading" class="alert alert-danger mb-4" role="alert">
        <ul class="list-disc pl-5 mb-0">
          <li v-for="(error, index) in errorMessages" :key="index">⚠️ {{ error }}</li>
        </ul>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-container">
        <i class="bi bi-arrow-clockwise spin-animation"></i>
        <p>Cargando ventas...</p>
      </div>

      <!-- Tabla de ventas -->
      <div v-else class="ventas-section">
        <div class="table-container">
          <table class="ventas-table" v-if="compras.length > 0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Email</th>
                <th>Libro</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="compra in compras" :key="compra.id">
                <td><strong>#{{ compra.id }}</strong></td>
                <td>{{ compra.nombre }}</td>
                <td class="email-col">{{ compra.email }}</td>
                <td class="libro-col">{{ compra.libro }}</td>
                <td>
                  <span class="metodo-badge">
                    <i :class="getMetodoIcon(compra.metodo_pago)"></i>
                    {{ compra.metodo_pago }}
                  </span>
                </td>
                <td>
                  <select
                    :value="compra.estado_pago"
                    @change="updateEstado(compra.id, $event.target.value)"
                    :class="['form-select-estado', `estado-${compra.estado_pago}`]"
                    :disabled="loadingEstado === compra.id"
                  >
                    <option value="pendiente">⏳ Pendiente</option>
                    <option value="pagado">✅ Pagado</option>
                    <option value="rechazado">❌ Rechazado</option>
                  </select>
                </td>
                <td class="precio-col">{{ formatPrice(compra.total_cents) }}</td>
                <td class="fecha-col">{{ formatDate(compra.created_at) }}</td>
              </tr>
            </tbody>
          </table>

          <div v-else class="sin-ventas">
            <i class="bi bi-bag-x-fill fa-3x"></i>
            <p>No hay ventas registradas</p>
            <small>Las ventas aparecerán aquí cuando los usuarios compren libros</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminVentas',
  data() {
    return {
      compras: [],
      successMessage: '',
      errorMessages: [],
      loading: false,
      loadingEstado: null,
      backgroundImage: '/storage/logos/Fondo.png',
    };
  },
  mounted() {
    this.fetchCompras();
    
    // Mensaje de éxito desde URL
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    if (success) {
      this.successMessage = success;
      setTimeout(() => this.successMessage = '', 5000);
    }
  },
  methods: {
    async fetchCompras() {
      this.loading = true;
      this.errorMessages = [];
      
      try {
        console.log('🔄 Iniciando carga de ventas...');
        console.log('📍 URL:', api.defaults.baseURL + '/admin/ventas');
        console.log('🔑 Token:', localStorage.getItem('token') ? '✅ Presente' : '❌ Ausente');
        
        const response = await api.get('/admin/ventas');
        
        console.log('✅ Respuesta recibida:', response);
        console.log('📦 Datos:', response.data);
        console.log('📊 Total ventas:', Array.isArray(response.data) ? response.data.length : 0);
        
        if (Array.isArray(response.data)) {
          this.compras = response.data;
          console.log('✅ Ventas cargadas correctamente:', this.compras.length);
        } else {
          console.warn('⚠️ La respuesta no es un array:', response.data);
          this.compras = [];
          this.errorMessages = ['La respuesta del servidor no tiene el formato esperado'];
        }
        
      } catch (error) {
        console.error('❌ Error completo:', error);
        console.error('📄 Respuesta del servidor:', error.response);
        console.error('📊 Status:', error.response?.status);
        console.error('📝 Data:', error.response?.data);
        console.error('🔧 Config:', error.config);
        
        this.compras = [];
        
        if (error.response?.status === 401) {
          this.errorMessages = ['⛔ Sesión expirada. Redirigiendo al login...'];
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else if (error.response?.status === 403) {
          this.errorMessages = ['⛔ No tienes permisos de administrador'];
        } else if (error.response?.status === 500) {
          this.errorMessages = [
            '🔴 Error interno del servidor (500)',
            'Revisa los logs de Laravel en storage/logs/laravel.log',
            error.response?.data?.message || 'Error desconocido'
          ];
        } else if (error.response) {
          this.errorMessages = [
            `Error ${error.response.status}: ${error.response.statusText}`,
            error.response.data?.message || 'Error al cargar las ventas'
          ];
        } else if (error.request) {
          this.errorMessages = [
            '🌐 No se pudo conectar con el servidor',
            'Verifica que Laravel esté corriendo en http://127.0.0.1:8000'
          ];
        } else {
          this.errorMessages = ['❌ Error inesperado: ' + error.message];
        }
      } finally {
        this.loading = false;
      }
    },
    
    async updateEstado(id, estado) {
      this.loadingEstado = id;
      this.successMessage = '';
      this.errorMessages = [];
      
      try {
        console.log(`🔄 Actualizando estado de venta #${id} a: ${estado}`);
        
        const response = await api.patch(`/admin/ventas/${id}/estado`, { 
          estado_pago: estado 
        });
        
        console.log('✅ Estado actualizado:', response.data);
        
        this.successMessage = '✅ Estado actualizado exitosamente.';
        
        // Actualizar el estado en la lista local
        const compra = this.compras.find(c => c.id === id);
        if (compra) {
          compra.estado_pago = estado;
        }
        
        setTimeout(() => this.successMessage = '', 3000);
        
      } catch (error) {
        console.error('❌ Error al actualizar estado:', error);
        console.error('📄 Respuesta:', error.response);
        
        if (error.response?.status === 422) {
          this.errorMessages = [
            'Datos inválidos',
            ...Object.values(error.response.data.errors || {}).flat()
          ];
        } else {
          this.errorMessages = [
            'Error al actualizar el estado',
            error.response?.data?.message || error.message
          ];
        }
        
        // Recargar para mostrar el estado correcto
        await this.fetchCompras();
      } finally {
        this.loadingEstado = null;
      }
    },
    
    verDetalle(id) {
      console.log(`👁️ Ver detalle de venta #${id}`);
      // Puedes implementar un modal o redirigir a una vista de detalle
      this.$router.push(`/admin/ventas/${id}`);
      // O usar un modal:
      // alert(`Ver detalle de la venta #${id}\n(Próximamente: Vista detallada)`);
    },
    
    getMetodoIcon(metodo) {
      const iconos = {
        'tarjeta': 'bi bi-credit-card-fill',
        'paypal': 'bi bi-paypal',
        'transferencia': 'bi bi-bank',
      };
      return iconos[metodo?.toLowerCase()] || 'bi bi-cash-coin';
    },
    
    formatPrice(cents) {
      const value = (cents || 0) / 100;
      return `$${value.toLocaleString('es-CO', { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
      })} COP`;
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      
      try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-CO', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch (error) {
        console.error('Error al formatear fecha:', error);
        return dateString;
      }
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS VENTAS (IGUAL QUE DASHBOARD)
=========================== */

/* Fondo general */
.formulario {
  background-size: cover;
  background-position: center;
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  padding: 30px;
}

/* Contenedor principal */
.ventas-container {
  max-width: 1600px;
  margin: 0 auto;
}

.ventas-title {
  font-size: 2rem;
  color: #2c3e50;
  font-weight: bold;
  margin-bottom: 2rem;
  text-align: center;
}

/* Mensajes */
.alert {
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  margin-bottom: 1rem;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.alert-success {
  background-color: #d1fae5;
  border-left: 5px solid #10b981;
  color: #065f46;
}

.alert-danger {
  background-color: #fee2e2;
  border-left: 5px solid #ef4444;
  color: #991b1b;
}

.list-disc {
  list-style-type: disc;
  margin: 0;
  padding-left: 1.5rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.pl-5 {
  padding-left: 1.25rem;
}

.mb-0 {
  margin-bottom: 0;
}

.me-2 {
  margin-right: 0.5rem;
}

/* Loading */
.loading-container {
  text-align: center;
  padding: 4rem 2rem;
  color: #6c757d;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

.loading-container i {
  color: #15AEBF;
  margin-bottom: 1rem;
  font-size: 3rem;
}

.spin-animation {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Sección de ventas (igual que dashboard) */
.ventas-section {
  background: white;
  border-radius: 0.75rem;
  padding: 2rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

/* Tabla (MISMO ESTILO QUE DASHBOARD) */
.table-container {
  overflow-x: auto;
}

.ventas-table {
  width: 100%;
  border-collapse: collapse;
}

.ventas-table thead {
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

.ventas-table th {
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ventas-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.3s ease;
}

.ventas-table tbody tr:hover {
  background-color: #f8f9fa;
}

.ventas-table td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  color: #374151;
  font-size: 0.9rem;
}

/* Columnas específicas */
.email-col {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.libro-col {
  max-width: 250px;
  font-weight: 500;
}

.fecha-col {
  white-space: nowrap;
  font-size: 0.85rem;
  color: #6c757d;
}

.precio-col {
  font-weight: 600;
  color: #059669;
  white-space: nowrap;
}

/* Badge de método de pago */
.metodo-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  background-color: #e0f2fe;
  color: #0c4a6e;
  border-radius: 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: capitalize;
}

/* Select de estado (mejorado) */
.form-select-estado {
  padding: 0.5rem 0.75rem;
  border: 2px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  background-color: white;
  min-width: 140px;
}

.form-select-estado:focus {
  border-color: #15AEBF;
  box-shadow: 0 0 0 0.25rem rgba(21, 174, 191, 0.15);
  outline: none;
}

.form-select-estado:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select-estado.estado-pendiente {
  border-color: #fbbf24;
  background-color: #fef3c7;
  color: #92400e;
}

.form-select-estado.estado-pagado {
  border-color: #10b981;
  background-color: #d1fae5;
  color: #065f46;
}

.form-select-estado.estado-rechazado {
  border-color: #ef4444;
  background-color: #fee2e2;
  color: #991b1b;
}

/* Botón ver */
.btn-ver {
  background-color: #15AEBF;
  color: white;
  border: none;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-ver:hover {
  background-color: #032840;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(3, 40, 64, 0.25);
}

.btn-ver i {
  font-size: 1.1rem;
}

/* Sin ventas */
.sin-ventas {
  text-align: center;
  padding: 4rem 2rem;
  color: #6c757d;
}

.sin-ventas i {
  color: #d1d5db;
  margin-bottom: 1rem;
  font-size: 3rem;
}

.sin-ventas p {
  font-size: 1.2rem;
  margin: 1rem 0 0.5rem;
  font-weight: 600;
  color: #374151;
}

.sin-ventas small {
  font-size: 0.9rem;
  color: #9ca3af;
}

/* Responsive */
@media (max-width: 1200px) {
  .ventas-table {
    font-size: 0.85rem;
  }
  
  .ventas-table th,
  .ventas-table td {
    padding: 0.75rem 0.5rem;
  }
}

@media (max-width: 768px) {
  .formulario {
    padding: 1rem;
  }

  .ventas-container {
    padding: 0;
  }

  .ventas-title {
    font-size: 1.5rem;
  }

  .ventas-section {
    padding: 1rem;
  }

  .ventas-table {
    font-size: 0.75rem;
  }

  .ventas-table th,
  .ventas-table td {
    padding: 0.5rem 0.25rem;
  }

  .libro-col {
    max-width: 150px;
  }

  .email-col {
    max-width: 120px;
  }

  .form-select-estado {
    min-width: 100px;
    font-size: 0.75rem;
    padding: 0.35rem 0.5rem;
  }

  .btn-ver {
    padding: 0.4rem 0.6rem;
    font-size: 0.9rem;
  }

  .metodo-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
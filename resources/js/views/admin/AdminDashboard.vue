<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="dashboard-container">
      <!-- Título -->
      <h1 class="dashboard-title">
        <i class="bi bi-bar-chart-line-fill"></i> Panel de Control
      </h1>

      <!-- Loading -->
      <div v-if="loading" class="loading-container">
        <i class="bi bi-arrow-clockwise"></i>        <p>Cargando datos...</p>
      </div>

      <!-- Tarjetas de estadísticas -->
      <div v-else class="stats-grid">
        <div class="stat-card stat-card-primary">
          <div class="stat-icon">
            <i class="bi bi-coin"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Ventas de Hoy</div>
            <div class="stat-value">{{ formatCurrency(ventasHoy) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total de Ventas</div>
            <div class="stat-value">{{ formatCurrency(totalVentas) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-info">
          <div class="stat-icon">
            <i class="bi bi-clipboard-data-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Libros Publicados</div>
            <div class="stat-value">{{ totalLibros }}</div>
          </div>
        </div>
      </div>

      <!-- Tabla de ventas recientes -->
      <div v-if="!loading" class="ventas-section">
        <h2 class="section-title">
          <i class="bi bi-basket3-fill"></i> Ventas Recientes
        </h2>

        <div class="table-container">
          <table class="ventas-table" v-if="ventas.length > 0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Libro</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="venta in ventas" :key="venta.id">
                <td>{{ venta.id }}</td>
                <td>{{ venta.cliente }}</td>
                <td>{{ venta.libro }}</td>
                <td class="precio-col">{{ formatCurrency(venta.total) }}</td>
                <td>
                  <span :class="['estado-badge', `estado-${venta.estado}`]">
                    {{ venta.estado }}
                  </span>
                </td>
                <td>{{ venta.fecha }}</td>
              </tr>
            </tbody>
          </table>

          <div v-else class="sin-ventas">
            <i class="fas fa-inbox fa-3x"></i>
            <p>No hay ventas registradas</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminDashboard',
  data() {
    return {
      ventasHoy: 0,
      totalVentas: 0,
      totalLibros: 0,
      ventas: [],
      loading: false,
      backgroundImage: '/storage/logos/Fondo.png',
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        console.log('Cargando datos del dashboard...');
        const response = await api.get('/admin/dashboard');
        console.log('Datos recibidos:', response.data);
        
        this.ventasHoy = response.data.ventasHoy || 0;
        this.totalVentas = response.data.totalVentas || 0;
        this.totalLibros = response.data.totalLibros || 0;
        this.ventas = response.data.ventas || [];
      } catch (error) {
        console.error('Error al cargar los datos del dashboard:', error.response || error);
      } finally {
        this.loading = false;
      }
    },
    
    formatCurrency(amount) {
      const value = (amount || 0) / 100;
      return `$${value.toLocaleString('es-CO', { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
      })} COP`;
    },
  },
};
</script>

<style>
/* ===========================
   ESTILOS DASHBOARD
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
.dashboard-container {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-title {
  font-size: 2rem;
  color: #2c3e50;
  font-weight: bold;
  margin-bottom: 2rem;
  text-align: center;
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
}

/* Grid de estadísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  color: white;
}

.stat-card-primary .stat-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card-success .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card-info .stat-icon {
  background: linear-gradient(135deg, #15AEBF 0%, #032840 100%);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: bold;
  color: #2c3e50;
}

/* Sección de ventas */
.ventas-section {
  background: white;
  border-radius: 0.75rem;
  padding: 2rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

.section-title {
  font-size: 1.5rem;
  color: #2c3e50;
  font-weight: bold;
  margin-bottom: 1.5rem;
}

/* Tabla */
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
}

.precio-col {
  font-weight: 600;
  color: #059669;
}

/* Estado badges */
.estado-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: capitalize;
}

.estado-pendiente {
  background-color: #fef3c7;
  color: #92400e;
}

.estado-pagado {
  background-color: #d1fae5;
  color: #065f46;
}

.estado-fallido {
  background-color: #fee2e2;
  color: #991b1b;
}

/* Sin ventas */
.sin-ventas {
  text-align: center;
  padding: 3rem 2rem;
  color: #6c757d;
}

.sin-ventas i {
  color: #d1d5db;
  margin-bottom: 1rem;
}

.sin-ventas p {
  font-size: 1.1rem;
  margin: 0;
}

.me-2 {
  margin-right: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .formulario {
    padding: 1rem;
  }

  .dashboard-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stat-card {
    padding: 1rem;
  }

  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .ventas-section {
    padding: 1rem;
  }

  .section-title {
    font-size: 1.25rem;
  }

  .ventas-table {
    font-size: 0.85rem;
  }

  .ventas-table th,
  .ventas-table td {
    padding: 0.5rem;
  }
}
</style>
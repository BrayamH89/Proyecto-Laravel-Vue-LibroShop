<template>
  <div class="admin-listar-usuarios container py-4">
    <h3 class="mb-4">
      <i class="bi bi-people-fill me-2"></i>Lista de Usuarios
    </h3>

    <!-- Mensajes -->
    <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
    <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

    <!-- Tabla -->
    <div class="card shadow-sm">
      <div class="card-body">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Creado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(usuario, index) in usuarios" :key="usuario.id">
              <td>{{ index + 1 }}</td>
              <td>{{ usuario.name }}</td>
              <td>{{ usuario.email }}</td>
              <td>
                <span
                  :class="usuario.role_id === 1 ? 'badge bg-danger' : 'badge bg-secondary'"
                >
                  {{ usuario.role_id === 1 ? 'Administrador' : 'Usuario' }}
                </span>
              </td>
              <td>{{ formatDate(usuario.created_at) }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" @click="eliminarUsuario(usuario.id)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="usuarios.length === 0">
              <td colspan="6" class="text-center text-muted">No hay usuarios registrados</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminListarUsuarios',
  data() {
    return {
      usuarios: [],
      loading: false,
      errorMessage: '',
      successMessage: '',
    };
  },
  methods: {
    async obtenerUsuarios() {
      this.loading = true;
      this.errorMessage = '';
      try {
        const response = await api.get('/admin/listar/usuarios');
        this.usuarios = response.data;
      } catch (error) {
        console.error(error);
        this.errorMessage = 'Error al cargar los usuarios';
      } finally {
        this.loading = false;
      }
    },
    async eliminarUsuario(id) {
      if (!confirm('¿Seguro que deseas eliminar este usuario?')) return;
      try {
        await api.delete(`/admin/listar/usuarios/${id}`);
        this.successMessage = 'Usuario eliminado correctamente';
        this.obtenerUsuarios();
      } catch (error) {
        console.error(error);
        this.errorMessage = 'Error al eliminar el usuario';
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('es-ES');
    },
  },
  mounted() {
    this.obtenerUsuarios();
  },
};
</script>

<style scoped>
.container {
  max-width: 1000px;
}
.badge {
  font-size: 0.9rem;
}
</style>

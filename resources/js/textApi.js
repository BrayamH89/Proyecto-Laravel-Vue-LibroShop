import api from './axios'

api.get('/libros')
  .then(res => console.log('✅ Conexión OK', res.data))
  .catch(err => console.error('❌ Error conexión', err.response || err))

import panelApi from './panelApi'

export const sesionService = {
  login: (email, password) => panelApi.post('/auth/login', { email, password }).then(r => r.data.user),
  logout: () => panelApi.post('/auth/logout'),
  yo: () => panelApi.get('/auth/me').then(r => r.data),
  // Sin sesión: «he olvidado mi contraseña» y crearla desde el enlace del correo
  olvide: email => panelApi.post('/auth/olvide-contrasena', { email }).then(r => r.data),
  crearContrasena: datos => panelApi.post('/auth/crear-contrasena', datos).then(r => r.data),
}

import { defineStore } from 'pinia'
import { sesionService } from '@/services/admin/sesionService'
import { ROLES_PANEL } from '@/admin/roles'

// Sesión del panel de gestión web. La autorización real está en el backend
// (Gates entrar-panel / gestionar-web / atender-contactos / administrar-web);
// esto solo decide qué se enseña.

export const usePanelSesion = defineStore('panelSesion', {
  state: () => ({ usuario: null, iniciada: false }),
  getters: {
    conSesion: s => !!s.usuario,
    puedeEntrar: s => ROLES_PANEL.includes(s.usuario?.role),
    esAdmin: s => s.usuario?.role === 'admin',
    rol: s => s.usuario?.role ?? null,
    // Sin lista de roles = cualquiera del panel
    puede: s => roles => !roles || roles.includes(s.usuario?.role),
    inicio: () => ({ name: 'panel-escritorio' }),
  },
  actions: {
    async init() {
      try {
        this.usuario = await sesionService.yo()
      } catch {
        this.usuario = null
      } finally {
        this.iniciada = true
      }
    },
    async entrar(email, password) {
      this.usuario = await sesionService.login(email, password)
      this.iniciada = true
      return this.usuario
    },
    async salir() {
      try { await sesionService.logout() } finally { this.usuario = null }
    },
  },
})

<?php

namespace App\Policies;

use App\Models\Entrada;
use App\Models\User;

/**
 * Quién puede hacer qué con las entradas del blog (además del Gate gestionar-web
 * de todas las rutas /api/admin):
 * - Editor: crea, edita y borra sus borradores y los envía a revisión. Una entrada
 *   publicada ya no la puede tocar (lo que se ve en la web pasa siempre por un admin).
 * - Administrador: todo, incluido aprobar, devolver y retirar de la web.
 */
class EntradaPolicy
{
    public function update(User $user, Entrada $entrada): bool
    {
        return $this->esAdmin($user) || $entrada->estado !== 'publicada';
    }

    public function delete(User $user, Entrada $entrada): bool
    {
        return $this->update($user, $entrada);
    }

    public function aprobar(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function eliminarDefinitivamente(User $user): bool
    {
        return $this->esAdmin($user);
    }

    private function esAdmin(User $user): bool
    {
        return in_array($user->role, config('cms.roles_admin'), true);
    }
}

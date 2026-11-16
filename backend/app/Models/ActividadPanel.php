<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/** Una acción en el panel de gestión web (ver ActividadService). Se borra a los 2 años. */
class ActividadPanel extends Model
{
    use Prunable;

    protected $table = 'actividad_panel';

    public const UPDATED_AT = null;

    // Todo lo pone el servidor (ActividadService), nunca el cliente
    protected $fillable = ['user_id', 'usuario_nombre', 'seccion', 'accion', 'descripcion', 'objeto_tipo', 'objeto_ref'];

    public const SECCIONES = [
        'paginas' => 'Páginas', 'entradas' => 'Entradas', 'categorias' => 'Categorías', 'medios' => 'Medios',
        'apariencia' => 'Apariencia', 'idiomas' => 'Idiomas', 'seo' => 'SEO', 'analitica' => 'Analítica',
        'formularios' => 'Formularios', 'integraciones' => 'Integraciones', 'usuarios' => 'Usuarios', 'perfil' => 'Perfil',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYears(2));
    }
}

<?php

namespace App\Services;

use App\Models\Medio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Biblioteca de medios. Con Cloudinary configurado (services.cloudinary) el archivo
 * se guarda allí y la BD solo guarda su URL y metadatos; sin él, en el disco local
 * con nombre aleatorio. El borrado quita el archivo junto al registro.
 */
class MedioService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    /** Tipo de medio según la extensión real del archivo (lista blanca de config/cms.php). */
    public static function tipoDe(UploadedFile $archivo): ?string
    {
        $ext = strtolower($archivo->guessExtension() ?? '');

        foreach (config('cms.medios.tipos') as $tipo => $def) {
            if (in_array($ext, $def['mimes'], true)) {
                return $tipo;
            }
        }

        return null;
    }

    public function subir(UploadedFile $archivo, User $usuario, ?string $alt = null): Medio
    {
        $tipo  = self::tipoDe($archivo);
        $ext   = strtolower($archivo->guessExtension());
        $disco = config('cms.medios.disco');

        if ($tipo === 'imagen') {
            $this->reducirSiHaceFalta($archivo->getRealPath(), $ext);
        }
        clearstatcache(true, $archivo->getRealPath());

        [$ancho, $alto] = $tipo === 'imagen' ? (@getimagesize($archivo->getRealPath()) ?: [null, null]) : [null, null];
        $tamano = filesize($archivo->getRealPath());

        if ($this->cloudinary->configurado()) {
            $subido = $this->cloudinary->subir($archivo->getRealPath(), Str::uuid().'.'.$ext, CloudinaryService::resourceTypeDe($tipo));
            $ubicacion = [
                'proveedor' => 'cloudinary', 'ruta' => $subido['public_id'], 'url_externa' => $subido['url'],
                'resource_type' => $subido['resource_type'],
            ];
            $tamano = $subido['bytes'] ?: $tamano;
        } else {
            // Nombre aleatorio: el original solo se guarda como dato, nunca en la ruta.
            $ubicacion = ['proveedor' => 'local', 'ruta' => $archivo->storeAs('medios/'.now()->format('Y/m'), Str::uuid().'.'.$ext, $disco)];
        }

        $medio = new Medio([
            'titulo' => Str::limit(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME), 250, ''),
            'alt'    => $alt,
        ]);
        $medio->forceFill([
            ...$ubicacion,
            'nombre_original' => Str::limit($archivo->getClientOriginalName(), 250, ''),
            'mime'            => $archivo->getMimeType(),
            'tipo'            => $tipo,
            'tamano'          => $tamano,
            'ancho'           => $ancho,
            'alto'            => $alto,
            'subido_por'      => $usuario->id,
        ])->save();

        return $medio;
    }

    /** Bytes ocupados por la biblioteca y cuota configurada. */
    public static function uso(): array
    {
        return [
            'usado' => (int) Medio::sum('tamano'),
            'cuota' => config('cms.medios.cuota_mb') * 1024 * 1024,
            'almacenamiento' => app(CloudinaryService::class)->configurado() ? 'cloudinary' : 'local',
        ];
    }

    /**
     * Reduce in situ las fotos JPG/PNG/WebP con un lado mayor que `lado_max_px`.
     * GIF no se toca (puede ser animado). Si GD falla, se guarda el original.
     */
    private function reducirSiHaceFalta(string $ruta, string $ext): void
    {
        $max = config('cms.medios.lado_max_px');
        [$ancho, $alto] = @getimagesize($ruta) ?: [0, 0];
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || max($ancho, $alto) <= $max || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $origen = match ($ext) {
            'png'  => @imagecreatefrompng($ruta),
            'webp' => @imagecreatefromwebp($ruta),
            default => @imagecreatefromjpeg($ruta),
        };
        if (! $origen) {
            return;
        }

        $escala = $max / max($ancho, $alto);
        [$w, $h] = [(int) round($ancho * $escala), (int) round($alto * $escala)];
        $nuevo   = imagecreatetruecolor($w, $h);
        if ($ext !== 'jpg' && $ext !== 'jpeg') {
            imagealphablending($nuevo, false);
            imagesavealpha($nuevo, true); // conserva la transparencia de PNG/WebP
        }
        if (! imagecopyresampled($nuevo, $origen, 0, 0, 0, 0, $w, $h, $ancho, $alto)) {
            return;
        }

        match ($ext) {
            'png'  => imagepng($nuevo, $ruta, 8),
            'webp' => imagewebp($nuevo, $ruta, config('cms.medios.calidad_jpg')),
            default => imagejpeg($nuevo, $ruta, config('cms.medios.calidad_jpg')),
        };
        imagedestroy($origen);
        imagedestroy($nuevo);
    }

    public function eliminar(Medio $medio): void
    {
        if ($medio->proveedor === 'cloudinary') {
            $this->cloudinary->eliminar($medio->ruta, $medio->resource_type ?? 'image');
        } else {
            Storage::disk(config('cms.medios.disco'))->delete($medio->ruta);
        }
        $medio->delete();
    }
}

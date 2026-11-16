<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Subida y borrado en Cloudinary con firma (misma lógica que UploadController de la
 * app DuaLab). Cloudinary guarda el archivo; aquí solo se devuelve su URL y metadatos.
 */
class CloudinaryService
{
    public function configurado(): bool
    {
        return (bool) (config('services.cloudinary.cloud_name') && config('services.cloudinary.api_key') && config('services.cloudinary.api_secret'));
    }

    /** @return array{url: string, public_id: string, resource_type: string, bytes: int, width: ?int, height: ?int} */
    public function subir(string $rutaLocal, string $nombre, string $resourceType): array
    {
        $folder    = config('services.cloudinary.folder');
        $timestamp = time();
        // Firma: parámetros firmados en orden alfabético + secreto, SHA-1
        $firma = sha1("folder={$folder}&timestamp={$timestamp}".config('services.cloudinary.api_secret'));

        $respuesta = Http::timeout(120)
            ->attach('file', fopen($rutaLocal, 'r'), $nombre)
            ->post($this->url($resourceType, 'upload'), [
                'api_key'   => config('services.cloudinary.api_key'),
                'timestamp' => $timestamp,
                'signature' => $firma,
                'folder'    => $folder,
            ]);

        if ($respuesta->failed()) {
            Log::warning('Cloudinary: subida fallida', ['status' => $respuesta->status(), 'error' => $respuesta->json('error.message')]);
            throw new RuntimeException('No se ha podido subir el archivo a Cloudinary.');
        }

        return [
            'url'           => $respuesta->json('secure_url'),
            'public_id'     => $respuesta->json('public_id'),
            'resource_type' => $resourceType,
            'bytes'         => (int) $respuesta->json('bytes'),
            'width'         => $respuesta->json('width'),
            'height'        => $respuesta->json('height'),
        ];
    }

    /** Si falla, se registra y no se bloquea el borrado del registro local. */
    public function eliminar(string $publicId, string $resourceType): void
    {
        $timestamp = time();
        $firma     = sha1("public_id={$publicId}&timestamp={$timestamp}".config('services.cloudinary.api_secret'));

        $respuesta = Http::timeout(20)->asForm()->post($this->url($resourceType, 'destroy'), [
            'public_id' => $publicId,
            'api_key'   => config('services.cloudinary.api_key'),
            'timestamp' => $timestamp,
            'signature' => $firma,
        ]);

        if ($respuesta->failed() || $respuesta->json('result') !== 'ok') {
            Log::warning('Cloudinary: no se pudo borrar un archivo', ['public_id' => $publicId, 'result' => $respuesta->json('result')]);
        }
    }

    /**
     * URL de imagen de Cloudinary optimizada para la web pública: formato según el navegador
     * (WebP/AVIF), calidad automática y como mucho `$ancho` px (nunca amplía). Otras URL no cambian.
     */
    public static function optimizar(?string $url, int $ancho = 1920): ?string
    {
        if (! $url || ! preg_match('#^https://res\.cloudinary\.com/[^/]+/image/upload/#', $url) || str_contains($url, '/upload/f_auto')) {
            return $url;
        }

        return preg_replace('#/image/upload/#', "/image/upload/f_auto,q_auto,c_limit,w_{$ancho}/", $url, 1);
    }

    /** Lo mismo para las imágenes dentro del HTML de una entrada. */
    public static function optimizarHtml(?string $html, int $ancho = 1200): ?string
    {
        if (! $html || ! str_contains($html, 'res.cloudinary.com')) {
            return $html;
        }

        return preg_replace_callback('#https://res\.cloudinary\.com/[^/"\s]+/image/upload/[^"\s<>]+#',
            fn ($m) => self::optimizar($m[0], $ancho), $html);
    }

    /**
     * Consumo de la cuenta (plan, créditos, espacio, tráfico) y datos de conexión no secretos.
     * La API Admin de Cloudinary limita las llamadas: se guarda 1 h (o 5 min si falla).
     * La cuenta es la misma que la de la app DuaLab: el consumo es de las dos.
     */
    public function estado(bool $refrescar = false): array
    {
        $conexion = [
            'configurado' => $this->configurado(),
            'cloud_name'  => config('services.cloudinary.cloud_name'), // es público: va en cada URL
            'carpeta'     => config('services.cloudinary.folder'),
        ];
        if (! $this->configurado()) {
            return ['conexion' => $conexion + ['ok' => false], 'consumo' => null];
        }

        if ($refrescar) {
            Cache::forget('cloudinary.consumo');
        }
        $consumo = Cache::get('cloudinary.consumo');
        if ($consumo === null) {
            $consumo = $this->pedirConsumo();
            Cache::put('cloudinary.consumo', $consumo, $consumo['ok'] ? now()->addHour() : now()->addMinutes(5));
        }

        return ['conexion' => $conexion + ['ok' => $consumo['ok']], 'consumo' => $consumo['ok'] ? $consumo : null];
    }

    private function pedirConsumo(): array
    {
        try {
            $r = Http::timeout(10)
                ->withBasicAuth(config('services.cloudinary.api_key'), config('services.cloudinary.api_secret'))
                ->get('https://api.cloudinary.com/v1_1/'.config('services.cloudinary.cloud_name').'/usage');
        } catch (\Throwable $e) {
            Log::warning('Cloudinary: no se pudo consultar el consumo', ['error' => class_basename($e)]);

            return ['ok' => false];
        }
        if ($r->failed()) {
            Log::warning('Cloudinary: consulta de consumo rechazada', ['status' => $r->status()]);

            return ['ok' => false];
        }

        return [
            'ok'                => true,
            'plan'              => (string) $r->json('plan'),
            'creditos_usados'   => (float) $r->json('credits.usage'),
            'creditos_limite'   => (float) $r->json('credits.limit'),
            'espacio_bytes'     => (int) $r->json('storage.usage'),
            'trafico_bytes'     => (int) $r->json('bandwidth.usage'),
            'transformaciones'  => (int) $r->json('transformations.usage'),
            'archivos'          => (int) $r->json('resources'),
            'max_imagen_bytes'  => $r->json('media_limits.image_max_size_bytes'),
            'max_video_bytes'   => $r->json('media_limits.video_max_size_bytes'),
            'consultado_en'     => now()->toIso8601String(),
        ];
    }

    /** image | video | raw según el tipo de la biblioteca */
    public static function resourceTypeDe(string $tipo): string
    {
        return match ($tipo) {
            'imagen' => 'image',
            'video'  => 'video',
            default  => 'raw',
        };
    }

    private function url(string $resourceType, string $accion): string
    {
        return 'https://api.cloudinary.com/v1_1/'.config('services.cloudinary.cloud_name')."/{$resourceType}/{$accion}";
    }
}

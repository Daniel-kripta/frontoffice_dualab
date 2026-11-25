<?php

namespace Tests;

use App\Http\Middleware\VerificarFirmaCentral;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

/**
 * Peticiones a la API firmada de DuaLab Central (integraciones/central), como las hace el backend de
 * la app: JSON firmado con HMAC y los adjuntos en base64. Uso: `$this->central()->getJson(...)`.
 */
class ClienteCentral
{
    public const SECRETO = 'secreto-de-prueba-con-mas-de-32-caracteres';

    public function __construct(private TestCase $test, private string $usuario = 'Cynthia')
    {
        config(['services.central.secreto' => self::SECRETO]);
    }

    public function getJson(string $uri): TestResponse { return $this->pedir('GET', $uri); }
    public function get(string $uri): TestResponse { return $this->pedir('GET', $uri); }
    public function postJson(string $uri, array $datos = []): TestResponse { return $this->pedir('POST', $uri, $datos); }
    public function post(string $uri, array $datos = []): TestResponse { return $this->pedir('POST', $uri, $datos); }
    public function patchJson(string $uri, array $datos = []): TestResponse { return $this->pedir('PATCH', $uri, $datos); }
    public function deleteJson(string $uri): TestResponse { return $this->pedir('DELETE', $uri); }

    private function pedir(string $metodo, string $uri, array $datos = []): TestResponse
    {
        // Archivos subidos → base64 (así los manda el backend de la app)
        if (isset($datos['adjuntos'])) {
            $datos['adjuntos_b64'] = array_map(fn (UploadedFile $f) => [
                'nombre' => $f->getClientOriginalName(), 'contenido' => base64_encode((string) file_get_contents($f->getRealPath())),
            ], $datos['adjuntos']);
            unset($datos['adjuntos']);
        }
        $json = $datos ? json_encode($datos, JSON_UNESCAPED_UNICODE) : '';
        $fecha = (string) time();
        $req = Request::create($uri, $metodo, [], [], [], ['HTTP_X_DUALAB_USUARIO' => $this->usuario], $json);
        $firma = hash_hmac('sha256', VerificarFirmaCentral::cadena($req, $fecha), self::SECRETO);

        return $this->test->call($metodo, $uri, [], [], [], $this->test->cabecerasServidor([
            'X-DuaLab-Fecha' => $fecha, 'X-DuaLab-Firma' => $firma, 'X-DuaLab-Usuario' => $this->usuario,
            'Accept' => 'application/json', 'Content-Type' => 'application/json',
        ]), $json);
    }
}

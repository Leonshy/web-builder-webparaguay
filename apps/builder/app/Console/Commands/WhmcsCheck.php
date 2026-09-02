<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Verifica que las credenciales de la API de WHMCS funcionen y muestra los
 * productos de hosting (para confirmar el pid). No cambia nada en WHMCS.
 *
 *   php artisan builder:whmcs-check
 */
class WhmcsCheck extends Command
{
    protected $signature = 'builder:whmcs-check';

    protected $description = 'Prueba las credenciales de la API de WHMCS (solo lectura)';

    public function handle(): int
    {
        $url = (string) config('publishing.whmcs.url');
        $identifier = (string) config('publishing.whmcs.identifier');
        $secret = (string) config('publishing.whmcs.secret');

        if ($url === '' || $identifier === '' || $secret === '') {
            $this->components->error('Faltan WHMCS_URL / WHMCS_IDENTIFIER / WHMCS_SECRET en el .env.');

            return self::FAILURE;
        }

        $this->components->info("Consultando {$url} …");

        $res = Http::asForm()
            ->timeout(30)
            ->post(rtrim($url, '/').'/includes/api.php', [
                'identifier' => $identifier,
                'secret' => $secret,
                'action' => 'GetProducts',
                'responsetype' => 'json',
            ]);

        $data = $res->json() ?? [];

        if (($data['result'] ?? null) !== 'success') {
            if ($data === []) {
                $this->components->error("HTTP {$res->status()} sin cuerpo JSON — ¿la URL es correcta?");
                $this->line($res->body());

                return self::FAILURE;
            }
            $msg = $data['message'] ?? 'respuesta inesperada';
            $this->components->error("WHMCS rechazó la llamada: {$msg}");
            $this->newLine();
            $this->line(match (true) {
                str_contains($msg, 'IP') => '→ Agregá la IP de este servidor en System Settings → General Settings → Security → API IP Access Restriction.',
                str_contains($msg, 'Authentication') => '→ Revisá el identifier/secret y que el admin tenga "API Access" en su rol.',
                str_contains($msg, 'disabled') || str_contains($msg, 'permission') => '→ Al API Role le falta el permiso GetProducts (o el que reclame). Agregalo en API Roles.',
                default => '→ Revisá el API Role y los permisos.',
            });

            return self::FAILURE;
        }

        $products = $data['products']['product'] ?? [];
        $this->components->info(count($products).' productos visibles. Credenciales OK.');
        $this->newLine();

        $rows = [];
        foreach ($products as $p) {
            $rows[] = [$p['pid'] ?? '', $p['name'] ?? '', $p['type'] ?? '', $p['group'] ?? '', $p['paytype'] ?? ''];
        }
        $this->table(['pid', 'Nombre', 'Tipo', 'Grupo', 'Cobro'], $rows);
        $this->line('→ Confirmá el pid del producto de hosting que vamos a usar (esperábamos 130 / "AI web").');

        return self::SUCCESS;
    }
}

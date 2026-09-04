<?php

namespace Webparaguay\Provisioning\Plesk;

use GuzzleHttp\Client;
use Webparaguay\Provisioning\InstanceConfigurator;
use Webparaguay\Provisioning\ProvisioningException;

/**
 * Configura una suscripción de Plesk YA creada (por el módulo WHMCS) para que
 * sirva el CMS: base de datos, document root, repositorio git en el tag del
 * site-runtime, `.env` y certificado Let's Encrypt.
 *
 * Todo se hace por la API REST de Plesk (pasarela de CLI `/api/v2/cli/*`), así
 * la plataforma sólo toca las suscripciones que creó — el servidor puede alojar
 * otros clientes sin que esto los afecte.
 *
 * La API key va por env, con lista blanca de IP. No se ejercita en CI.
 */
final class PleskInstanceConfigurator implements InstanceConfigurator
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private string $repoUrl,
        private string $branch,
        private string $sharedToken,
        private string $letsencryptEmail,
        private string $dbServer = 'localhost',
        private string $phpBin = '/opt/plesk/php/8.4/bin/php',
        private bool $verifyTls = true,
        private ?Client $http = null,
    ) {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            throw new ProvisioningException('Plesk no está configurado (PLESK_URL / PLESK_API_KEY).');
        }

        $this->http ??= new Client([
            'base_uri' => rtrim($baseUrl, '/').'/api/v2/',
            'timeout' => 120,
            'http_errors' => false,
            // El panel suele responder en la IP con un cert que no matchea.
            'verify' => $verifyTls,
            'headers' => [
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Deja la instancia lista para recibir el sitio. Idempotente en lo posible:
     * si algo ya existe, Plesk devuelve error y se ignora sólo cuando es seguro.
     *
     * @return array{db: string, db_user: string} datos creados (para el back-office)
     */
    public function configure(string $fqdn): array
    {
        $db = $this->slug($fqdn);
        $dbUser = $db;
        $dbPass = bin2hex(random_bytes(16));
        $appKey = 'base64:'.base64_encode(random_bytes(32));

        $this->createDatabase($fqdn, $db, $dbUser, $dbPass);
        $this->setDocumentRoot($fqdn);
        $this->configureGit($fqdn, $this->envBlock($fqdn, $appKey, $db, $dbUser, $dbPass));
        $this->deployGit($fqdn);
        $this->issueLetsEncrypt($fqdn);

        return ['db' => $db, 'db_user' => $dbUser];
    }

    private function createDatabase(string $fqdn, string $db, string $user, string $pass): void
    {
        $res = $this->cli('database', [
            '--create', $db,
            '-domain', $fqdn,
            '-type', 'mysql',
            '-server', $this->dbServer,
            '-add-user', $user,
            '-passwd', $pass,
        ]);

        // "already exists" no es fatal en un reintento.
        if ($res['code'] !== 0 && ! $this->mentions($res, 'already exists')) {
            throw $this->fail('crear la base de datos', $res);
        }
    }

    private function setDocumentRoot(string $fqdn): void
    {
        $res = $this->cli('site', ['--update', $fqdn, '-www-root', '/httpdocs/public']);

        if ($res['code'] !== 0) {
            throw $this->fail('fijar el document root', $res);
        }
    }

    private function configureGit(string $fqdn, string $envBlock): void
    {
        // Acciones de despliegue: escribir el .env (con los secretos ya
        // resueltos) y preparar Laravel. El servidor NO corre composer/npm: el
        // tag site-runtime-v* ya trae vendor/ y public/build/.
        $actions = implode(' && ', [
            "cat > .env <<'ENVEOF'\n{$envBlock}\nENVEOF",
            "{$this->phpBin} artisan migrate --force",
            "{$this->phpBin} artisan config:cache",
        ]);

        $res = $this->cli('plesk', [
            'ext', 'git', '--create',
            '-domain', $fqdn,
            '-name', 'site-runtime',
            '-repository', $this->repoUrl,
            '-deployment-path', '/httpdocs',
            '-deployment-mode', 'auto',
            '-branch', $this->branch,
            '-actions', $actions,
        ]);

        if ($res['code'] !== 0 && ! $this->mentions($res, 'already exists')) {
            throw $this->fail('configurar el repositorio git', $res);
        }
    }

    private function deployGit(string $fqdn): void
    {
        $res = $this->cli('plesk', [
            'ext', 'git', '--deploy',
            '-domain', $fqdn,
            '-name', 'site-runtime',
        ]);

        if ($res['code'] !== 0) {
            throw $this->fail('desplegar el repositorio git', $res);
        }
    }

    private function issueLetsEncrypt(string $fqdn): void
    {
        $res = $this->cli('plesk', [
            'ext', 'letsencrypt', '--certificate',
            '-d', $fqdn,
            '-m', $this->letsencryptEmail,
        ]);

        if ($res['code'] !== 0) {
            throw $this->fail('emitir el certificado Let\'s Encrypt', $res);
        }
    }

    /** @return array{code:int,stdout:string,stderr:string} */
    private function cli(string $utility, array $params): array
    {
        $response = $this->http->post("cli/{$utility}/call", ['json' => ['params' => $params]]);
        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?? [];

        if ($status >= 400 && ! isset($body['code'])) {
            throw new ProvisioningException("Plesk API {$utility} devolvió HTTP {$status}: ".json_encode($body));
        }

        return [
            'code' => (int) ($body['code'] ?? 1),
            'stdout' => (string) ($body['stdout'] ?? ''),
            'stderr' => (string) ($body['stderr'] ?? ''),
        ];
    }

    private function envBlock(string $fqdn, string $appKey, string $db, string $dbUser, string $dbPass): string
    {
        return implode("\n", [
            'APP_ENV=production',
            'APP_DEBUG=false',
            "APP_KEY={$appKey}",
            "APP_URL=https://{$fqdn}",
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            "DB_DATABASE={$db}",
            "DB_USERNAME={$dbUser}",
            "DB_PASSWORD={$dbPass}",
            "SITE_RUNTIME_INTERNAL_TOKEN={$this->sharedToken}",
            'SESSION_DRIVER=file',
            'CACHE_STORE=file',
        ]);
    }

    private function slug(string $fqdn): string
    {
        $label = strtok($fqdn, '.');

        return substr(preg_replace('/[^a-z0-9]/', '', strtolower($label)) ?: 'site', 0, 24);
    }

    /** @param array{stdout:string,stderr:string} $res */
    private function mentions(array $res, string $needle): bool
    {
        return stripos($res['stdout'].$res['stderr'], $needle) !== false;
    }

    /** @param array{code:int,stdout:string,stderr:string} $res */
    private function fail(string $step, array $res): ProvisioningException
    {
        $msg = trim($res['stderr'] ?: $res['stdout']) ?: "código {$res['code']}";

        return new ProvisioningException("Plesk: no se pudo {$step}. {$msg}");
    }
}

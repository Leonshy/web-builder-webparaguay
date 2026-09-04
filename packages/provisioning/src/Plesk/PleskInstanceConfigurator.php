<?php

namespace Webparaguay\Provisioning\Plesk;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use Webparaguay\Provisioning\InstanceConfigurator;
use Webparaguay\Provisioning\ProvisioningException;

/**
 * Configura una suscripción de Plesk YA creada (por el módulo WHMCS) para que
 * sirva el CMS: base de datos, document root, repositorio git en el tag del
 * site-runtime, `.env` y certificado Let's Encrypt.
 *
 * Se hace por SSH corriendo los utilitarios `plesk bin` / `plesk ext` — la API
 * REST de Plesk no cubre git ni Let's Encrypt. La plataforma sólo toca las
 * suscripciones que nombra; el servidor puede alojar otros clientes.
 *
 * Credenciales SSH por env, con lista blanca de IP. No se ejercita en CI.
 */
final class PleskInstanceConfigurator implements InstanceConfigurator
{
    /** @var array<int,string> registro de cada comando, para diagnóstico */
    public array $transcript = [];

    /** @var callable(string):array{code:int,output:string} */
    private $runner;

    public function __construct(
        private string $sshHost,
        private string $repoUrl,
        private string $branch,
        private string $sharedToken,
        private string $letsencryptEmail,
        private int $sshPort = 22,
        private string $sshUser = 'root',
        private string $sshPrivateKey = '',   // PEM (contenido) o ruta a la llave
        private string $sshPassword = '',
        private string $dbServer = 'localhost',
        private string $phpBin = '/opt/plesk/php/8.4/bin/php',
        ?callable $runner = null,
    ) {
        if ($this->sshHost === '' || ($this->sshPrivateKey === '' && $this->sshPassword === '')) {
            throw new ProvisioningException('Plesk SSH no está configurado (PLESK_SSH_HOST + llave o contraseña).');
        }

        $this->runner = $runner ?? $this->sshRunner();
    }

    /**
     * @return array{db: string, db_user: string}
     */
    public function configure(string $fqdn): array
    {
        $db = $this->slug($fqdn);
        $user = $db;
        $pass = bin2hex(random_bytes(16));
        $appKey = 'base64:'.base64_encode(random_bytes(32));

        $this->createDatabase($fqdn, $db, $user, $pass);
        $this->run(
            "plesk bin site --update {$this->arg($fqdn)} -www-root /httpdocs/public",
            'fijar el document root',
        );
        $this->configureGit($fqdn, $this->envBlock($fqdn, $appKey, $db, $user, $pass));
        $this->run(
            "plesk ext git --deploy -domain {$this->arg($fqdn)} -name site-runtime",
            'desplegar el repositorio git',
        );
        $this->run(
            "plesk bin extension --exec letsencrypt cli.php -d {$this->arg($fqdn)} -m {$this->arg($this->letsencryptEmail)}",
            'emitir el certificado Let\'s Encrypt',
        );

        return ['db' => $db, 'db_user' => $user];
    }

    private function createDatabase(string $fqdn, string $db, string $user, string $pass): void
    {
        $create = "plesk bin database --create {$this->arg($db)} -domain {$this->arg($fqdn)}"
            ." -type mysql -server {$this->arg($this->dbServer)}"
            ." -add-user {$this->arg($user)} -passwd {$this->arg($pass)}";

        $res = $this->exec($create);

        // En un reintento la BD ya existe (con otra contraseña). Un sitio recién
        // publicado la tiene vacía: se borra y se recrea limpia.
        if ($res['code'] !== 0 && $this->mentions($res, 'exist')) {
            $this->exec("plesk bin database --remove {$this->arg($db)} -domain {$this->arg($fqdn)}");
            $res = $this->exec($create);
        }

        if ($res['code'] !== 0) {
            throw $this->fail('crear la base de datos', $res);
        }
    }

    private function configureGit(string $fqdn, string $envBlock): void
    {
        $actions = implode("\n", [
            "cat > .env <<'ENVEOF'",
            $envBlock,
            'ENVEOF',
            // Plesk deja un index.html placeholder en el docroot al crear la
            // suscripción; tapa el index.php de Laravel si no se borra.
            'rm -f public/index.html',
            "{$this->phpBin} artisan migrate --force",
            "{$this->phpBin} artisan config:cache",
        ]);

        $cmd = "plesk ext git --create -domain {$this->arg($fqdn)} -name site-runtime"
            ." -remote-url {$this->arg($this->repoUrl)}"
            ." -active-branch {$this->arg($this->branch)}"
            .' -deployment-path /httpdocs -deployment-mode auto'
            ." -run-actions true -actions {$this->arg($actions)}";

        $res = $this->exec($cmd);

        if ($res['code'] !== 0 && ! $this->mentions($res, 'exist')) {
            throw $this->fail('configurar el repositorio git', $res);
        }

        // `--create` ignora `-active-branch` (clona la rama por defecto del
        // repo). Se fija explícitamente con `--update`, siempre, exista o no.
        $this->run(
            "plesk ext git --update -domain {$this->arg($fqdn)} -name site-runtime"
            ." -active-branch {$this->arg($this->branch)} -run-actions true -actions {$this->arg($actions)}",
            'fijar la rama del repositorio git',
        );
    }

    /** Corre un comando y aborta si falla. */
    private function run(string $cmd, string $step): void
    {
        $res = $this->exec($cmd);
        if ($res['code'] !== 0) {
            throw $this->fail($step, $res);
        }
    }

    /** @return array{code:int,output:string} */
    private function exec(string $cmd): array
    {
        $res = ($this->runner)($cmd);
        $out = trim((string) ($res['output'] ?? ''));
        $this->transcript[] = sprintf(
            '%s -> code=%d %s',
            preg_replace('/\s+/', ' ', substr($cmd, 0, 120)),
            $res['code'] ?? -1,
            substr($out, 0, 300),
        );

        return ['code' => (int) ($res['code'] ?? -1), 'output' => $out];
    }

    private function envBlock(string $fqdn, string $appKey, string $db, string $user, string $pass): string
    {
        return implode("\n", [
            'APP_ENV=production',
            'APP_DEBUG=false',
            "APP_KEY={$appKey}",
            "APP_URL=https://{$fqdn}",
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            "DB_DATABASE={$db}",
            "DB_USERNAME={$user}",
            "DB_PASSWORD={$pass}",
            "SITE_RUNTIME_INTERNAL_TOKEN={$this->sharedToken}",
            'SESSION_DRIVER=file',
            'CACHE_STORE=file',
        ]);
    }

    /** @return callable(string):array{code:int,output:string} */
    private function sshRunner(): callable
    {
        return function (string $cmd): array {
            $ssh = new SSH2($this->sshHost, $this->sshPort);
            $auth = $this->sshPrivateKey !== ''
                ? PublicKeyLoader::load($this->keyMaterial())
                : $this->sshPassword;

            if (! $ssh->login($this->sshUser, $auth)) {
                throw new ProvisioningException("SSH: no se pudo autenticar en {$this->sshHost} como {$this->sshUser}.");
            }

            $output = $ssh->exec($cmd);

            return ['code' => $ssh->getExitStatus() ?: 0, 'output' => (string) $output];
        };
    }

    private function keyMaterial(): string
    {
        return is_file($this->sshPrivateKey) ? (string) file_get_contents($this->sshPrivateKey) : $this->sshPrivateKey;
    }

    private function slug(string $fqdn): string
    {
        $label = strtok($fqdn, '.');

        return substr(preg_replace('/[^a-z0-9]/', '', strtolower($label)) ?: 'site', 0, 24);
    }

    private function arg(string $value): string
    {
        return escapeshellarg($value);
    }

    /** @param array{output:string} $res */
    private function mentions(array $res, string $needle): bool
    {
        return stripos($res['output'], $needle) !== false;
    }

    /** @param array{code:int,output:string} $res */
    private function fail(string $step, array $res): ProvisioningException
    {
        $msg = trim($res['output']) ?: "código {$res['code']}";

        return new ProvisioningException("Plesk: no se pudo {$step}. {$msg}");
    }
}

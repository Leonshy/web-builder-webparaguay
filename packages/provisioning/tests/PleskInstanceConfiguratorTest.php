<?php

namespace Webparaguay\Provisioning\Tests;

use PHPUnit\Framework\TestCase;
use Webparaguay\Provisioning\Plesk\PleskInstanceConfigurator;
use Webparaguay\Provisioning\ProvisioningException;

class PleskInstanceConfiguratorTest extends TestCase
{
    /** @var array<int,string> */
    private array $ran = [];

    /**
     * @param  array<int,array{code:int,output:string}>|callable  $responses
     */
    private function configurator(array|callable $responses): PleskInstanceConfigurator
    {
        $queue = is_array($responses) ? $responses : null;

        $runner = function (string $cmd) use (&$queue, $responses): array {
            $this->ran[] = $cmd;

            if (str_starts_with($cmd, "stat -c '%U'")) {
                return ['code' => 0, 'output' => 'siteuser'];
            }

            if (is_callable($responses)) {
                return $responses($cmd);
            }

            return array_shift($queue) ?? ['code' => 0, 'output' => ''];
        };

        return new PleskInstanceConfigurator(
            sshHost: 'plesk.test',
            repoUrl: 'https://github.com/x/y.git',
            branch: 'site-runtime-v0.1.0',
            sharedToken: 'tok-shared',
            letsencryptEmail: 'ssl@x.com',
            sshPassword: 'pw',
            runner: $runner,
        );
    }

    public function test_recorre_los_pasos_de_aprovisionamiento(): void
    {
        $out = $this->configurator([])->configure('panaderia7.sites.naranja.com.py');

        $this->assertSame('panaderia7', $out['db']);
        $this->assertCount(8, $this->ran);

        $this->assertStringContainsString('plesk bin database --create', $this->ran[0]);
        $this->assertStringContainsString('-www-root /httpdocs/public', $this->ran[1]);
        $this->assertStringContainsString('plesk ext git --create', $this->ran[2]);
        $this->assertStringContainsString('plesk ext git --update', $this->ran[3]);
        $this->assertStringContainsString("-active-branch 'site-runtime-v0.1.0'", $this->ran[3]);
        $this->assertStringContainsString('plesk ext git --deploy', $this->ran[4]);
        $this->assertStringStartsWith("stat -c '%U'", $this->ran[5]);
        $this->assertStringContainsString("su -s /bin/bash 'siteuser' -c", $this->ran[6]);
        $this->assertStringContainsString('extension --exec letsencrypt cli.php', $this->ran[7]);

        // El .env va en base64, en una sola línea (no un heredoc: las
        // "post-deploy actions" de Plesk no se ejecutan de forma confiable),
        // corrido directo como el usuario del sitio.
        $this->assertStringNotContainsString('<<', $this->ran[6]);
        $this->assertStringContainsString('base64 -d > .env', $this->ran[6]);
        preg_match('/([A-Za-z0-9+\/=]{40,})/', $this->ran[6], $m);
        $envBlock = base64_decode($m[1]);
        $this->assertStringContainsString('SITE_RUNTIME_INTERNAL_TOKEN=tok-shared', $envBlock);
        $this->assertStringContainsString('APP_URL=https://panaderia7.sites.naranja.com.py', $envBlock);
    }

    public function test_una_base_de_datos_ya_existente_se_recrea(): void
    {
        $createCalls = 0;
        $responses = function (string $cmd) use (&$createCalls): array {
            if (str_contains($cmd, 'database --create')) {
                $createCalls++;

                return $createCalls === 1
                    ? ['code' => 1, 'output' => 'Database with such name is already exist.']
                    : ['code' => 0, 'output' => ''];
            }

            return ['code' => 0, 'output' => ''];
        };

        $this->configurator($responses)->configure('x9.sites.naranja.com.py');

        $this->assertSame(2, $createCalls);
        $this->assertContains("plesk bin database --remove 'x9' -domain 'x9.sites.naranja.com.py'", $this->ran);
    }

    public function test_un_error_real_aborta(): void
    {
        $this->expectException(ProvisioningException::class);
        $this->expectExceptionMessageMatches('/document root|quota/i');

        $this->configurator([
            ['code' => 0, 'output' => ''],                 // db create
            ['code' => 1, 'output' => 'disk quota exceeded'], // www-root
        ])->configure('x9.sites.naranja.com.py');
    }

    public function test_exige_configuracion_ssh(): void
    {
        $this->expectException(ProvisioningException::class);

        new PleskInstanceConfigurator(
            sshHost: '', repoUrl: 'r', branch: 'b', sharedToken: 't', letsencryptEmail: 'e',
        );
    }
}

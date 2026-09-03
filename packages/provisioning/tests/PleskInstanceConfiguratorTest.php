<?php

namespace Webparaguay\Provisioning\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Webparaguay\Provisioning\Plesk\PleskInstanceConfigurator;
use Webparaguay\Provisioning\ProvisioningException;

class PleskInstanceConfiguratorTest extends TestCase
{
    /** @var array<int,Request> */
    private array $sent = [];

    private function configurator(MockHandler $mock): PleskInstanceConfigurator
    {
        $stack = HandlerStack::create($mock);
        $stack->push(function (callable $handler) {
            return function (Request $request, array $options) use ($handler) {
                $this->sent[] = $request;

                return $handler($request, $options);
            };
        });

        return new PleskInstanceConfigurator(
            baseUrl: 'https://plesk.test:8443',
            apiKey: 'key-123',
            repoUrl: 'https://github.com/x/y.git',
            branch: 'site-runtime-v0.1.0',
            sharedToken: 'tok-shared',
            letsencryptEmail: 'ssl@x.com',
            http: new Client(['handler' => $stack, 'base_uri' => 'https://plesk.test:8443/api/v2/', 'http_errors' => false]),
        );
    }

    private function ok(): Response
    {
        return new Response(200, [], json_encode(['code' => 0, 'stdout' => 'ok', 'stderr' => '']));
    }

    public function test_recorre_los_pasos_de_aprovisionamiento(): void
    {
        $out = $this->configurator(new MockHandler([$this->ok(), $this->ok(), $this->ok(), $this->ok(), $this->ok()]))
            ->configure('panaderia7.sites.naranja.com.py');

        $this->assertSame('panaderia7', $out['db']);
        $this->assertCount(5, $this->sent);

        $paths = array_map(fn (Request $r) => $r->getUri()->getPath(), $this->sent);
        $this->assertSame([
            '/api/v2/cli/database/call',
            '/api/v2/cli/site/call',
            '/api/v2/cli/extension/call',
            '/api/v2/cli/extension/call',
            '/api/v2/cli/extension/call',
        ], $paths);

        // El .env va embebido en las acciones de deploy, con el token compartido.
        $gitBody = json_decode((string) $this->sent[2]->getBody(), true);
        $actions = end($gitBody['params']);
        $this->assertStringContainsString('SITE_RUNTIME_INTERNAL_TOKEN=tok-shared', $actions);
        $this->assertStringContainsString('APP_URL=https://panaderia7.sites.naranja.com.py', $actions);
        $this->assertStringContainsString('artisan migrate --force', $actions);
    }

    public function test_una_base_de_datos_ya_existente_no_es_fatal(): void
    {
        $exists = new Response(200, [], json_encode(['code' => 1, 'stdout' => '', 'stderr' => 'Database already exists']));

        $out = $this->configurator(new MockHandler([$exists, $this->ok(), $this->ok(), $this->ok(), $this->ok()]))
            ->configure('x9.sites.naranja.com.py');

        $this->assertSame('x9', $out['db']);
    }

    public function test_un_error_real_de_plesk_aborta(): void
    {
        $boom = new Response(200, [], json_encode(['code' => 1, 'stdout' => '', 'stderr' => 'disk quota exceeded']));

        $this->expectException(ProvisioningException::class);
        $this->expectExceptionMessageMatches('/document root|disk quota/i');

        $this->configurator(new MockHandler([$this->ok(), $boom]))
            ->configure('x9.sites.naranja.com.py');
    }

    public function test_exige_configuracion(): void
    {
        $this->expectException(ProvisioningException::class);

        new PleskInstanceConfigurator(
            baseUrl: '', apiKey: '', repoUrl: 'r', branch: 'b', sharedToken: 't', letsencryptEmail: 'e',
        );
    }
}

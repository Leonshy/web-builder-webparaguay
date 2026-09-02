<?php

namespace App\Providers;

use App\Generation\ClaudeGenerator;
use App\Generation\Generator;
use App\Generation\HttpSiteRuntimeClient;
use App\Generation\SiteRuntimeClient;
use App\Generation\TemplateGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Webparaguay\Provisioning\Fake\FakeBillingGateway;
use Webparaguay\Provisioning\Fake\FakeDomainRegistrar;
use Webparaguay\Provisioning\Fake\FakeHostingProvisioner;
use Webparaguay\Provisioning\Provisioner;
use Webparaguay\Provisioning\Whmcs\WhmcsBillingGateway;
use Webparaguay\Provisioning\Whmcs\WhmcsDomainRegistrar;
use Webparaguay\Provisioning\Plesk\PleskHostingProvisioner;
use Webparaguay\Schema\SchemaValidator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchemaValidator::class, fn () => new SchemaValidator());

        $this->app->bind(Generator::class, function ($app) {
            return match (config('generation.driver')) {
                'claude' => $app->make(ClaudeGenerator::class),
                default => $app->make(TemplateGenerator::class),
            };
        });

        $this->app->bind(SiteRuntimeClient::class, HttpSiteRuntimeClient::class);

        $this->app->singleton(Provisioner::class, function () {
            $wp = config('publishing.whmcs');

            $billing = config('publishing.billing_driver') === 'whmcs'
                ? new WhmcsBillingGateway($wp['url'], $wp['identifier'], $wp['secret'])
                : new FakeBillingGateway();

            $hosting = config('publishing.hosting_driver') === 'plesk'
                ? new PleskHostingProvisioner(
                    config('publishing.plesk.url'),
                    config('publishing.plesk.api_key'),
                    config('publishing.plesk.service_plan'),
                    config('publishing.subdomain_base'),
                    config('publishing.git_repo_url'),
                )
                : new FakeHostingProvisioner(config('publishing.subdomain_base'));

            $domains = config('publishing.billing_driver') === 'whmcs'
                ? new WhmcsDomainRegistrar($wp['url'], $wp['identifier'], $wp['secret'])
                : new FakeDomainRegistrar();

            return new Provisioner($billing, $hosting, $domains, config('publishing.runtime_version'));
        });
    }

    public function boot(): void
    {
        // Control de abuso: tope de generaciones por cuenta (§5.9).
        RateLimiter::for('generation', function ($request) {
            $orgId = $request->user()?->organization_id ?: $request->ip();

            return [
                Limit::perMinute(3)->by("gen:min:{$orgId}"),
                Limit::perDay((int) config('generation.daily_limit', 20))->by("gen:day:{$orgId}"),
            ];
        });
    }
}

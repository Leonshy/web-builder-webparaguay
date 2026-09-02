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
use Webparaguay\Provisioning\BillingGateway;
use Webparaguay\Provisioning\DomainRegistrar;
use Webparaguay\Provisioning\Fake\FakeBillingGateway;
use Webparaguay\Provisioning\Fake\FakeDomainRegistrar;
use Webparaguay\Provisioning\Fake\FakeHostingProvisioner;
use Webparaguay\Provisioning\HostingProvisioner;
use Webparaguay\Provisioning\Plesk\PleskHostingProvisioner;
use Webparaguay\Provisioning\Provisioner;
use Webparaguay\Provisioning\SitePublisher;
use Webparaguay\Provisioning\Whmcs\WhmcsBillingGateway;
use Webparaguay\Provisioning\Whmcs\WhmcsDomainRegistrar;
use Webparaguay\Provisioning\Whmcs\WhmcsProvisioner;
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

        $this->app->singleton(BillingGateway::class, function () {
            $wp = config('publishing.whmcs');

            return config('publishing.billing_driver') === 'whmcs'
                ? new WhmcsBillingGateway($wp['url'], $wp['identifier'], $wp['secret'])
                : new FakeBillingGateway();
        });

        $this->app->singleton(HostingProvisioner::class, function () {
            return config('publishing.hosting_driver') === 'plesk'
                ? new PleskHostingProvisioner(
                    config('publishing.plesk.url'),
                    config('publishing.plesk.api_key'),
                    config('publishing.plesk.service_plan'),
                    config('publishing.subdomain_base'),
                    config('publishing.git_repo_url'),
                )
                : new FakeHostingProvisioner(config('publishing.subdomain_base'));
        });

        $this->app->singleton(DomainRegistrar::class, function () {
            $wp = config('publishing.whmcs');

            return config('publishing.billing_driver') === 'whmcs'
                ? new WhmcsDomainRegistrar($wp['url'], $wp['identifier'], $wp['secret'])
                : new FakeDomainRegistrar();
        });

        $this->app->singleton(Provisioner::class, fn ($app) => new Provisioner(
            $app->make(BillingGateway::class),
            $app->make(HostingProvisioner::class),
            $app->make(DomainRegistrar::class),
            config('publishing.runtime_version'),
        ));

        $this->app->singleton(WhmcsProvisioner::class, function () {
            $wp = config('publishing.whmcs');

            return new WhmcsProvisioner(
                baseUrl: $wp['url'],
                identifier: $wp['identifier'],
                secret: $wp['secret'],
                productId: (int) config('publishing.whmcs_product_id'),
                subdomainBase: config('publishing.subdomain_base'),
                runtimeVersion: config('publishing.runtime_version'),
                paymentMode: config('publishing.payment_mode', 'manual'),
                billingCycle: config('publishing.whmcs_billing_cycle', 'monthly'),
                paymentMethod: config('publishing.whmcs_payment_method', 'banktransfer'),
                taxIdFieldId: (int) config('publishing.whmcs_cf_tax_id'),
                companyFieldId: (int) config('publishing.whmcs_cf_company'),
            );
        });

        // El publicador: WHMCS nativo, o el compuesto (fakes en dev).
        $this->app->singleton(SitePublisher::class, fn ($app) => config('publishing.hosting_driver') === 'whmcs'
            ? $app->make(WhmcsProvisioner::class)
            : $app->make(Provisioner::class));
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

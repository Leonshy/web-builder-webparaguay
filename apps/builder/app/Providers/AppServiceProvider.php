<?php

namespace App\Providers;

use App\Generation\ClaudeGenerator;
use App\Generation\Generator;
use App\Generation\HttpSiteRuntimeClient;
use App\Generation\SiteRuntimeClient;
use App\Generation\TemplateGenerator;
use Illuminate\Support\ServiceProvider;
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
    }

    public function boot(): void
    {
        //
    }
}

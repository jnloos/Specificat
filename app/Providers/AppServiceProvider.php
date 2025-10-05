<?php

namespace App\Providers;

use App\Facades\Markdown;
use App\Services\Dependencies\LLMClient;
use App\Services\Dependencies\SpecificationService;
use App\Services\GeminiClient;
use App\Services\MarkdownParser;
use App\Services\OpenAIClient;
use App\Services\AssistantSummary;
use App\Services\PythonVenv;
use App\Services\UnifiedPrompting;
use Illuminate\Support\ServiceProvider;
use Parsedown;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        // Register PythonVenv service
        $this->app->singleton(PythonVenv::class, function () {
            return new PythonVenv();
        });

        // Register MarkdownParser service
        $this->app->singleton(Markdown::class, function (Parsedown $parser) {
            return new MarkdownParser($parser);
        });

        $this->app->singleton(LLMClient::class, function () {
            // TODO: AI providers can be changed here
            // IMPLEMENTS INTERFACE LLMClient
            // return new GeminiClient();
            return new OpenAIClient();
        });

        // Register Specification service
        $this->app->singleton(SpecificationService::class, function ($app) {
            // TODO: Prompting strategies can be changed here
            // EXTENDS ABSTRACT SpecificationService
            $client = $app->make(LLMClient::class);
            return new UnifiedPrompting($client);
        });

        // Register Summary service
        $this->app->singleton(AssistantSummary::class, function ($app) {
            $client = $app->make(LLMClient::class);
            return new AssistantSummary($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        //
    }
}

<?php

namespace CrixuAMG\QueryAnalyzer;

use Illuminate\Support\ServiceProvider;

/**
 * Class QueryAnalyzerServiceProvider
 *
 * @package CrixuAMG
 */
class QueryAnalyzerServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        // Allow the user to get the config file
        $this->registerConfiguration();
    }

    /**
     * Register the config file
     */
    private function registerConfiguration()
    {
        $this->publishes([
            __DIR__ . '/config/query-analyzer.php' => config_path('query-analyzer.php'),
        ]);
    }
}

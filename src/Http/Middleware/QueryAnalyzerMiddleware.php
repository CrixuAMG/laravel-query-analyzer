<?php

namespace CrixuAMG\QueryAnalyzer\Http\Middleware;

use CrixuAMG\QueryAnalyzer\Analyzer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

/**
 * Class QueryAnalyzerMiddleware
 *
 * @package CrixuAMG\QueryAnalyzer\Http\Middleware
 */
class QueryAnalyzerMiddleware
{
    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    private $strict;

    /**
     * RouteLoggerMiddleware constructor.
     */
    public function __construct()
    {
        if (App::environment((array)config('query-analyzer.environments'))) {
            DB::enableQueryLog();
        }

        $this->strict = config('query-analyzer.strict', false);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = null)
    {
        // Prepare the data
        $response = $next($request);

        if (App::environment((array)config('query-analyzer.environments'))) {
            Analyzer::analyzeQueries(DB::getQueryLog());
        }

        // Return the response
        return $response;
    }
}

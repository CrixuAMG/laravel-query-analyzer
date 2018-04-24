<?php

namespace CrixuAMG\QueryAnalyzer\Http\Middleware;

use Illuminate\Support\Collection;
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
            $this->analyzeQueries(DB::getQueryLog());
        }

        // Return the response
        return $response;
    }

    /**
     * @param array $queries
     */
    private function analyzeQueries(array $queries)
    {
        $queries = collect($queries);
        $uniqueQueries = $this->getUniqueQueries($queries);
        $longQueries =  $this->getLongQueries($queries);
        $duplicates = $queries->groupBy('query')->all();

        dump($queries->count());
        dump(count($uniqueQueries), $uniqueQueries);
        dump($longQueries);
        dump($duplicates);

        $selectQueries = $queries->filter(function ($query) {
            return stripos($query['query'], 'select') !== false;
        });

        $insertQueries = $queries->filter(function ($query) {
            return stripos($query['query'], 'insert') !== false;
        });

        dd($selectQueries, $insertQueries);
    }

    /**
     * @param Collection $queries
     *
     * @return array
     */
    private function getUniqueQueries(Collection $queries)
    {
        return array_unique($queries->pluck('query')->all());
    }

    /**
     * @param $queries
     *
     * @return mixed
     */
    private function getLongQueries(Collection $queries)
    {
        return $queries->sortByDesc('time')->take(5);
    }
}
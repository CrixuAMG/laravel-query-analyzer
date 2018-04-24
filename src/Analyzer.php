<?php

namespace CrixuAMG\QueryAnalyzer;

use Illuminate\Support\Collection;

/**
 * Class Analyzer
 *
 * @package CrixuAMG\QueryAnalyzer
 */
class Analyzer
{
    /**
     * @var
     */
    private static $queries;

    /**
     * @param Collection $queries
     */
    public static function setQueries(Collection $queries)
    {
        self::$queries = $queries;
    }

    /**
     * @param string $queryType
     *
     * @return mixed
     */
    private static function getQueriesByType(string $queryType)
    {
        return self::$queries->filter(function ($query) use ($queryType) {
            return stripos($query['query'], $queryType) !== false;
        });
    }

    /**
     * @param array $queries
     */
    public static function analyzeQueries(array $queries)
    {
        self::setQueries(collect($queries));

        $uniqueQueries = self::getUniqueQueries();
        $longQueries = self::getLongQueries();
        $duplicates = $queries->groupBy('query')->all();

        $queryTypes = [
            'select',
            'insert',
            'update',
            'delete',
        ];
        $groupedByType = [];
        foreach ($queryTypes as $queryType) {
            $groupedByType[$queryType] = self::getQueriesByType($queryType);
        }

        $returnData = [
            'by_type'            => $groupedByType,
            'total_count'        => self::$queries->count(),
            'duplicate_queries'  => $duplicates,
            'unique_queries'     => $uniqueQueries,
            'unique_query_count' => count($uniqueQueries),
            'long_queries'       => $longQueries,

        ];

        dd($returnData);
    }

    /**
     * @return array
     */
    private static function getUniqueQueries(): array
    {
        return array_unique(self::$queries->pluck('query')->all());
    }

    /**
     * @return array
     */
    private static function getLongQueries(): array
    {
        return self::$queries->sortByDesc('time')->take(5)->all();
    }
}
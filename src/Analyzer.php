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
     * @param array $queries
     *
     * @return array
     */
    public static function analyzeQueries(array $queries): array
    {
        self::setQueries(collect($queries));

        $uniqueQueries = self::getUniqueQueries();

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

        return self::checkData([
            'by_type'            => $groupedByType,
            'total_count'        => self::$queries->count(),
            'duplicate_queries'  => self::getDuplicates(),
            'unique_queries'     => $uniqueQueries,
            'unique_query_count' => count($uniqueQueries),
            'long_queries'       => self::getLongQueries(),
        ]);
    }

    /**
     * @param string $queryType
     *
     * @return array
     */
    private static function getQueriesByType(string $queryType): array
    {
        return self::$queries->filter(
            function ($query) use ($queryType) {
                // Use the queryType variable in combination with a space
                // to prevent matching with for example `deleted_at`
                return stripos($query['query'], sprintf('%s ', $queryType)) !== false;
            }
        )
            ->all();
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

    /**
     * @return array
     */
    private static function getDuplicates(): array
    {
        return self::$queries->groupBy('query')->all();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private static function checkData(array $data): array
    {
        dd($data);
    }
}
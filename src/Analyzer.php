<?php

namespace CrixuAMG\QueryAnalyzer;

use CrixuAMG\QueryAnalyzer\Exceptions\QueryAnalyzerException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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
     * @throws QueryAnalyzerException
     */
    public static function analyzeQueries(array $queries): array
    {
        self::setQueries(collect($queries));

        $uniqueQueries = self::getUniqueQueries();
        $duplicateQueries = self::getDuplicates();

        $groupedByType = [];
        $queryTypes = (array)config('query-analyzer.query_types');
        foreach ($queryTypes as $queryType) {
            $groupedByType[$queryType] = self::getQueriesByType($queryType);
        }

        return self::checkData([
            'by_type'               => $groupedByType,
            'total_count'           => self::$queries->count(),
            'duplicate_queries'     => $duplicateQueries,
            'duplicate_query_count' => self::getDuplicateCount($duplicateQueries),
            'unique_queries'        => $uniqueQueries,
            'unique_query_count'    => \count($uniqueQueries),
            'longest_queries'       => self::getLongQueries(),
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
        $queries = self::$queries->all();

        $uniques = [];
        $duplicates = [];
        foreach ($queries as $index => $query) {
            if (!isset($uniques[$query['query']])) {
                $uniques[$query['query']] = $query;
            } elseif (!isset($duplicates[$query['query']])) {
                $duplicates[$query['query']][$index] = $uniques[$query['query']];
            } else {
                $duplicates[$query['query']][$index] = $query;
            }
        }

        return $duplicates;
    }

    /**
     * @param array $duplicates
     *
     * @return int
     */
    public static function getDuplicateCount(array $duplicates): int
    {
        $count = 0;

        foreach ($duplicates as $duplicate) {
            $count += \count($duplicate);
        }

        return $count;
    }

    /**
     * @param array $data
     */
    public static function saveFile(array $data)
    {
        Storage::disk('local')->put(
            sprintf(
                'query_log/query_data.%s.json',
                now()->toDateTimeString()
            ),
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws QueryAnalyzerException
     */
    private static function checkData(array $data): array
    {
        $highQueryCount = (int)config('query-logger.high_query_count');
        if ($data['total_count'] > $highQueryCount) {
            $data['warnings'][] = sprintf(
                'total_count is %u too high, try to lower it!',
                $data['total_count'] - $highQueryCount
            );
        }
        $highDuplicateQueryCount = (int)config('query-logger.high_duplicates_query_count');
        if ($data['duplicate_query_count'] > $highDuplicateQueryCount) {
            $data['warnings'][] = sprintf(
                'duplicate_query_count is %u too high, try to lower it!',
                $data['duplicate_query_count'] - $highDuplicateQueryCount
            );
        }

        if (isset($data['warnings'])) {
            self::saveFile($data);

            if ((bool)config('query-analyzer.strict')) {
                throw new QueryAnalyzerException(
                    'Query Analyzer identified several issues, please check the data.',
                    500
                );
            }
        }

        return $data;
    }
}

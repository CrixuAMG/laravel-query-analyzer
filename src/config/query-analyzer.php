<?php

return [
    'environments'                => [
        'development',
        'local',
        'testing',
    ],
    'strict'                      => true,
    'query_types'                 => [
        'select',
        'insert',
        'update',
        'delete',
    ],
    'high_query_count'            => 20,
    'high_duplicates_query_count' => 5,
];

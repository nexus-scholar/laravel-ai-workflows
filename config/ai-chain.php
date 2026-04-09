<?php

return [
    'graph' => [
        // Safety rail for cyclic graphs.
        'max_iterations' => 50,
        'checkpoint' => [
            'store' => 'file',
            'ttl' => 86400,
        ],
    ],
    'memory' => [
        'store' => 'file',
        'ttl' => 3600,
        'max_messages' => 20,
        'summary_after' => 10,
    ],
    'retrieval' => [
        'top_k' => 5,
        'hybrid_rrf_k' => 60,
        'rerank_fetch_k' => 20,
        'provider' => null,
        'model' => null,
    ],
];


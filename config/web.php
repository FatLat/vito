<?php

use App\Http\Controllers\ServerController;

return [
    'pagination_size' => 10,

    'pagination_sizes' => [10, 25, 50],

    'controllers' => [
        'servers' => ServerController::class,
    ],
];

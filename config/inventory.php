<?php

return [
    'target' => env('INVENTORY_GRPC_TARGET', 'inventory-service:50051'),
    'timeout_us' => (int) env('INVENTORY_GRPC_TIMEOUT_US', 3_000_000),
];

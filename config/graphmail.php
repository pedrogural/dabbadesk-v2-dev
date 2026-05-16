<?php

return [
    'tenant_id' => env('MS_GRAPH_TENANT_ID'),
    'client_id' => env('MS_GRAPH_CLIENT_ID'),
    'client_secret' => env('MS_GRAPH_CLIENT_SECRET'),

    'from_address' => env('MS_GRAPH_FROM_ADDRESS', 'noreply@dabbadirect.com'),
    'from_name' => env('MS_GRAPH_FROM_NAME', 'Dabba Direct'),

    'internal_notification_email' => env('DABBA_INTERNAL_NOTIFICATION_EMAIL', 'info@dabbadirect.com'),
];

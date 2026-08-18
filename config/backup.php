<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'retention_days' => env('BACKUP_RETENTION_DAYS', 7),
    'prefix' => env('BACKUP_PREFIX', 'backups/'),
];

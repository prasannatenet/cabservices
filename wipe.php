<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

Schema::disableForeignKeyConstraints();

$tables = [
    'bookings',
    'drivers',
    'vehicles',
    'vehicle_categories',
    'service_types',
    'cities',
    'city_city' // Assuming this is the pivot table, or we can just ignore pivot if cascading
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
        echo "Truncated: $table\n";
    }
}

// Any other table ending with _city or city_
$allTables = DB::select('SHOW TABLES');
foreach ($allTables as $t) {
    $tableName = (array) $t;
    $tableName = array_values($tableName)[0];
    
    if (in_array($tableName, ['users', 'migrations', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens', 'sessions'])) {
        continue;
    }
    
    if (!in_array($tableName, $tables)) {
        DB::table($tableName)->truncate();
        echo "Truncated: $tableName\n";
    }
}

Schema::enableForeignKeyConstraints();

echo "All app data wiped successfully. User accounts preserved.\n";

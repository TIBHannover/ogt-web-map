<?php

// This file is loaded early in the Laravel bootstrap process
// and ensures that the SQLite database has the correct absolute path

// Only run in the Docker container
if (file_exists('/var/www/html')) {
    // Get current .env settings
    $env_file = __DIR__ . '/../.env';
    
    if (file_exists($env_file)) {
        $env_content = file_get_contents($env_file);
        
        // Check if the database path is relative
        if (strpos($env_content, 'DB_DATABASE=storage/app/database.sqlite') !== false) {
            // Create the absolute path
            $db_path = '/var/www/html/storage/app/database.sqlite';
            
            // Ensure the directory exists
            if (!file_exists(dirname($db_path))) {
                mkdir(dirname($db_path), 0755, true);
            }
            
            // Create the database file if it doesn't exist
            if (!file_exists($db_path)) {
                touch($db_path);
                chmod($db_path, 0664);
            }
            
            // No need to update .env, we'll use the absolute path in the database config
        }
    }
}
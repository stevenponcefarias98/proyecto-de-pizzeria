<?php
declare(strict_types=1);

// Ajusta estas variables según la instalación local o el hospedaje.
return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_name' => getenv('DB_NAME') ?: 'pizzeria34',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
    // En XAMPP el proyecto se publica dentro de htdocs/pizzeria34.
    'base_url' => rtrim(getenv('BASE_URL') ?: '/pizzeria34', '/'),
];

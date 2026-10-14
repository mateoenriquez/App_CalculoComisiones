<?php
declare(strict_types=1);

use function Cake\Core\env;

/**
 * Configuración de Producción — Sistema de Comisiones de Ventas
 *
 * Instrucciones:
 * 1. En el servidor Alwaysdata, copiar este archivo como: config/app_local.php
 * 2. Completar los valores de conexión de la base de datos remota y el SECURITY_SALT.
 * 3. Alternativamente, definir estas claves como variables de entorno del servidor.
 *
 * NUNCA incluir contraseñas reales en repositorios públicos.
 */
return [
    /*
     * Modo debug: estrictamente false en producción para evitar fugas de trazas
     * y detalles internos del sistema.
     */
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    /*
     * Salt de seguridad único para producción.
     * Generar un token aleatorio seguro de 64 caracteres hex:
     *   php bin/cake.php security generate_token
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', 'CAMBIAR_POR_TOKEN_ALEATORIO_SEGURO_DE_PRODUCCION_64_CHARS'),
    ],

    /*
     * URL base completa para generación de URLs absolutas y redirecciones HTTPS.
     */
    'App' => [
        'fullBaseUrl' => env('APP_FULL_BASE_URL', 'https://tu-cuenta.alwaysdata.net'),
    ],

    /*
     * Configuración de la base de datos MySQL / MariaDB en Alwaysdata.
     */
    'Datasources' => [
        'default' => [
            'host'          => env('DB_HOST', 'mysql-tu-cuenta.alwaysdata.net'),
            'port'          => env('DB_PORT', '3306'),
            'username'      => env('DB_USERNAME', 'tu-cuenta_comisiones'),
            'password'      => env('DB_PASSWORD', 'tu_contraseña_aqui'),
            'database'      => env('DB_DATABASE', 'tu-cuenta_comisiones'),
            'driver'        => \Cake\Database\Driver\Mysql::class,
            'encoding'      => 'utf8mb4',
            'timezone'      => 'UTC',
            'flags'         => [],
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log'           => false,
        ],

        /*
         * Desactivar datasource de tests en producción.
         */
        'test' => [
            'className' => \Cake\Database\Connection::class,
            'driver'    => \Cake\Database\Driver\Mysql::class,
            'persistent'=> false,
        ],
    ],
];

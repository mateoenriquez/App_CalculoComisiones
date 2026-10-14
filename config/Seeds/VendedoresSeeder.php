<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Seeder de vendedores demo.
 * Idempotente: usa insertOrSkip (ON DUPLICATE KEY IGNORE) sobre el campo nombre
 * que tiene restriccion UNIQUE creada por este seeder.
 * Ejecutar con: bin/cake migrations seed --seed VendedoresSeeder
 */
class VendedoresSeeder extends BaseSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $vendedores = [
            ['nombre' => 'Ana López',    'created' => $now, 'modified' => $now],
            ['nombre' => 'Bruno Mendoza','created' => $now, 'modified' => $now],
            ['nombre' => 'Carla Torres', 'created' => $now, 'modified' => $now],
        ];

        foreach ($vendedores as $vendedor) {
            // Verificar si ya existe antes de insertar (idempotencia sin unique constraint en nombre)
            $existing = $this->fetchRow(
                "SELECT id FROM vendedores WHERE nombre = '" . str_replace("'", "''", $vendedor['nombre']) . "' LIMIT 1"
            );
            if ($existing === false) {
                $this->insert('vendedores', $vendedor);
            }
        }
    }
}

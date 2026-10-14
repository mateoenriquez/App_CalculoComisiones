<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Seeder de reglas de comision.
 * Idempotente: verifica existencia por meta_minima (campo UNIQUE).
 * Ejecutar con: bin/cake migrations seed --seed ReglasSeeder
 */
class ReglasSeeder extends BaseSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $reglas = [
            ['meta_minima' => '0.00',    'porcentaje_comision' => '0.00',  'created' => $now, 'modified' => $now],
            ['meta_minima' => '1000.00', 'porcentaje_comision' => '5.00',  'created' => $now, 'modified' => $now],
            ['meta_minima' => '2000.00', 'porcentaje_comision' => '10.00', 'created' => $now, 'modified' => $now],
        ];

        foreach ($reglas as $regla) {
            // meta_minima es UNIQUE: insertOrSkip es seguro aqui
            $this->insertOrSkip('reglas', $regla);
        }
    }
}

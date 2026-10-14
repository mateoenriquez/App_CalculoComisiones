<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Fixture de Reglas para el entorno de pruebas.
 */
class ReglasFixture extends TestFixture
{
    public string $table = 'reglas';

    public function init(): void
    {
        $this->records = [
            [
                'id'                  => 1,
                'meta_minima'         => '0.00',
                'porcentaje_comision' => '0.00',
                'created'             => '2026-01-01 00:00:00',
                'modified'            => '2026-01-01 00:00:00',
            ],
            [
                'id'                  => 2,
                'meta_minima'         => '1000.00',
                'porcentaje_comision' => '5.00',
                'created'             => '2026-01-01 00:00:00',
                'modified'            => '2026-01-01 00:00:00',
            ],
            [
                'id'                  => 3,
                'meta_minima'         => '2000.00',
                'porcentaje_comision' => '10.00',
                'created'             => '2026-01-01 00:00:00',
                'modified'            => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}

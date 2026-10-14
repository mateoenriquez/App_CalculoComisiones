<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Fixture de Ventas para el entorno de pruebas.
 */
class VentasFixture extends TestFixture
{
    public string $table = 'ventas';

    public function init(): void
    {
        $this->records = [
            // Ana López (vendedor_id = 1) -> Ventas: 100 (2026-01-05), 150 (2026-01-12), 200 (2026-01-19), 350 (2026-01-26)
            [
                'id'          => 1,
                'vendedor_id' => 1,
                'monto'       => '100.00',
                'fecha'       => '2026-01-05',
                'created'     => '2026-01-05 00:00:00',
                'modified'    => '2026-01-05 00:00:00',
            ],
            [
                'id'          => 2,
                'vendedor_id' => 1,
                'monto'       => '150.00',
                'fecha'       => '2026-01-12',
                'created'     => '2026-01-12 00:00:00',
                'modified'    => '2026-01-12 00:00:00',
            ],
            [
                'id'          => 3,
                'vendedor_id' => 1,
                'monto'       => '200.00',
                'fecha'       => '2026-01-19',
                'created'     => '2026-01-19 00:00:00',
                'modified'    => '2026-01-19 00:00:00',
            ],
            [
                'id'          => 4,
                'vendedor_id' => 1,
                'monto'       => '350.00',
                'fecha'       => '2026-01-26',
                'created'     => '2026-01-26 00:00:00',
                'modified'    => '2026-01-26 00:00:00',
            ],

            // Bruno Mendoza (vendedor_id = 2) -> Ventas: 250 (2026-01-06), 350 (2026-01-13), 400 (2026-01-20), 500 (2026-01-27)
            [
                'id'          => 5,
                'vendedor_id' => 2,
                'monto'       => '250.00',
                'fecha'       => '2026-01-06',
                'created'     => '2026-01-06 00:00:00',
                'modified'    => '2026-01-06 00:00:00',
            ],
            [
                'id'          => 6,
                'vendedor_id' => 2,
                'monto'       => '350.00',
                'fecha'       => '2026-01-13',
                'created'     => '2026-01-13 00:00:00',
                'modified'    => '2026-01-13 00:00:00',
            ],
            [
                'id'          => 7,
                'vendedor_id' => 2,
                'monto'       => '400.00',
                'fecha'       => '2026-01-20',
                'created'     => '2026-01-20 00:00:00',
                'modified'    => '2026-01-20 00:00:00',
            ],
            [
                'id'          => 8,
                'vendedor_id' => 2,
                'monto'       => '500.00',
                'fecha'       => '2026-01-27',
                'created'     => '2026-01-27 00:00:00',
                'modified'    => '2026-01-27 00:00:00',
            ],

            // Carla Torres (vendedor_id = 3) -> Ventas: 400 (2026-01-07), 600 (2026-01-14), 700 (2026-01-21), 800 (2026-01-28)
            [
                'id'          => 9,
                'vendedor_id' => 3,
                'monto'       => '400.00',
                'fecha'       => '2026-01-07',
                'created'     => '2026-01-07 00:00:00',
                'modified'    => '2026-01-07 00:00:00',
            ],
            [
                'id'          => 10,
                'vendedor_id' => 3,
                'monto'       => '600.00',
                'fecha'       => '2026-01-14',
                'created'     => '2026-01-14 00:00:00',
                'modified'    => '2026-01-14 00:00:00',
            ],
            [
                'id'          => 11,
                'vendedor_id' => 3,
                'monto'       => '700.00',
                'fecha'       => '2026-01-21',
                'created'     => '2026-01-21 00:00:00',
                'modified'    => '2026-01-21 00:00:00',
            ],
            [
                'id'          => 12,
                'vendedor_id' => 3,
                'monto'       => '800.00',
                'fecha'       => '2026-01-28',
                'created'     => '2026-01-28 00:00:00',
                'modified'    => '2026-01-28 00:00:00',
            ],
        ];
        parent::init();
    }
}

<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Fixture de Vendedores para el entorno de pruebas.
 */
class VendedoresFixture extends TestFixture
{
    public string $table = 'vendedores';

    public function init(): void
    {
        $this->records = [
            [
                'id'       => 1,
                'nombre'   => 'Ana López',
                'created'  => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
            [
                'id'       => 2,
                'nombre'   => 'Bruno Mendoza',
                'created'  => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
            [
                'id'       => 3,
                'nombre'   => 'Carla Torres',
                'created'  => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}

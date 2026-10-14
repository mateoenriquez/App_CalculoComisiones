<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Seeder de ventas demo.
 * Idempotente: verifica existencia por (vendedor_id, fecha, monto) antes de insertar.
 * Totales esperados:
 *   Ana Lopez    (id=1): 100+150+200+350 = 800.00  -> 0%  -> 0.00
 *   Bruno Mendoza(id=2): 250+350+400+500 = 1500.00 -> 5%  -> 75.00
 *   Carla Torres (id=3): 400+600+700+800 = 2500.00 -> 10% -> 250.00
 * Total ventas: 4800.00 | Total comisiones: 325.00
 * Ejecutar con: bin/cake migrations seed --seed VentasSeeder
 */
class VentasSeeder extends BaseSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Obtener IDs reales de los vendedores por nombre
        $vendedoresData = [
            'Ana López'     => null,
            'Bruno Mendoza' => null,
            'Carla Torres'  => null,
        ];

        foreach (array_keys($vendedoresData) as $nombre) {
            $row = $this->fetchRow(
                "SELECT id FROM vendedores WHERE nombre = '" . str_replace("'", "''", $nombre) . "' LIMIT 1"
            );
            if ($row === false) {
                throw new \RuntimeException(
                    "Vendedor '$nombre' no encontrado. Ejecute primero VendedoresSeeder."
                );
            }
            $vendedoresData[$nombre] = (int)$row['id'];
        }

        $ventas = [
            // Ana Lopez: total 800.00
            ['vendedor_id' => $vendedoresData['Ana López'],     'monto' => '100.00', 'fecha' => '2026-01-05'],
            ['vendedor_id' => $vendedoresData['Ana López'],     'monto' => '150.00', 'fecha' => '2026-01-12'],
            ['vendedor_id' => $vendedoresData['Ana López'],     'monto' => '200.00', 'fecha' => '2026-01-19'],
            ['vendedor_id' => $vendedoresData['Ana López'],     'monto' => '350.00', 'fecha' => '2026-01-26'],
            // Bruno Mendoza: total 1500.00
            ['vendedor_id' => $vendedoresData['Bruno Mendoza'], 'monto' => '250.00', 'fecha' => '2026-01-06'],
            ['vendedor_id' => $vendedoresData['Bruno Mendoza'], 'monto' => '350.00', 'fecha' => '2026-01-13'],
            ['vendedor_id' => $vendedoresData['Bruno Mendoza'], 'monto' => '400.00', 'fecha' => '2026-01-20'],
            ['vendedor_id' => $vendedoresData['Bruno Mendoza'], 'monto' => '500.00', 'fecha' => '2026-01-27'],
            // Carla Torres: total 2500.00
            ['vendedor_id' => $vendedoresData['Carla Torres'],  'monto' => '400.00', 'fecha' => '2026-01-07'],
            ['vendedor_id' => $vendedoresData['Carla Torres'],  'monto' => '600.00', 'fecha' => '2026-01-14'],
            ['vendedor_id' => $vendedoresData['Carla Torres'],  'monto' => '700.00', 'fecha' => '2026-01-21'],
            ['vendedor_id' => $vendedoresData['Carla Torres'],  'monto' => '800.00', 'fecha' => '2026-01-28'],
        ];

        foreach ($ventas as $venta) {
            $existing = $this->fetchRow(
                "SELECT id FROM ventas WHERE vendedor_id = {$venta['vendedor_id']}
                 AND fecha = '{$venta['fecha']}'
                 AND monto = '{$venta['monto']}' LIMIT 1"
            );
            if ($existing === false) {
                $this->insert('ventas', array_merge($venta, [
                    'created'  => $now,
                    'modified' => $now,
                ]));
            }
        }
    }
}

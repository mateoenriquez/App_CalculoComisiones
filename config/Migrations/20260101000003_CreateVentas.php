<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateVentas extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('ventas', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Ventas realizadas por cada vendedor',
        ]);
        $table
            ->addColumn('vendedor_id', 'integer', [
                'null' => false,
                'signed' => true,
                'comment' => 'FK a vendedores.id',
            ])
            ->addColumn('monto', 'decimal', [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'comment' => 'Monto de la venta en USD',
            ])
            ->addColumn('fecha', 'date', [
                'null' => false,
                'comment' => 'Fecha de la venta',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['vendedor_id'], ['name' => 'idx_ventas_vendedor_id'])
            ->addForeignKey('vendedor_id', 'vendedores', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_ventas_vendedor_id',
            ])
            ->create();
    }
}

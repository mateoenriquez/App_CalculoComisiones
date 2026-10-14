<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateReglas extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('reglas', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Reglas de comision por meta minima',
        ]);
        $table
            ->addColumn('meta_minima', 'decimal', [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'comment' => 'Umbral minimo de ventas para aplicar esta regla',
            ])
            ->addColumn('porcentaje_comision', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'comment' => 'Porcentaje de comision a aplicar (0-100)',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['meta_minima'], ['unique' => true, 'name' => 'uq_reglas_meta_minima'])
            ->create();
    }
}

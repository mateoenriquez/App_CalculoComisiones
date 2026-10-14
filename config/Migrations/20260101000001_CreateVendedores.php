<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateVendedores extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('vendedores', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Tabla de vendedores',
        ]);
        $table
            ->addColumn('nombre', 'string', [
                'limit' => 120,
                'null' => false,
                'comment' => 'Nombre completo del vendedor',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->create();
    }
}

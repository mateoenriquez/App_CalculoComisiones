<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Entidad Vendedor
 *
 * Representa un vendedor en el sistema de comisiones.
 *
 * @property int $id
 * @property string $nombre
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\Venta[] $ventas
 */
class Vendedor extends Entity
{
    /**
     * Campos accesibles para asignación masiva.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'nombre'   => true,
        'created'  => true,
        'modified' => true,
        'ventas'   => true,
    ];
}

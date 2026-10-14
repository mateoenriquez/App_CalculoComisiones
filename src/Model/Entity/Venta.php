<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Entidad Venta
 *
 * Representa una venta asociada a un vendedor.
 *
 * @property int $id
 * @property int $vendedor_id
 * @property string $monto
 * @property \Cake\I18n\Date $fecha
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\Vendedor $vendedor
 */
class Venta extends Entity
{
    /**
     * Campos accesibles para asignación masiva.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'vendedor_id' => true,
        'monto'       => true,
        'fecha'       => true,
        'created'     => true,
        'modified'    => true,
        'vendedor'    => true,
    ];
}

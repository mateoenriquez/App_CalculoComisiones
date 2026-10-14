<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Entidad Regla
 *
 * Representa una regla de comisión por meta mínima de ventas.
 *
 * @property int $id
 * @property string $meta_minima
 * @property string $porcentaje_comision
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Regla extends Entity
{
    /**
     * Campos accesibles para asignación masiva.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'meta_minima'          => true,
        'porcentaje_comision'  => true,
        'created'              => true,
        'modified'             => true,
    ];
}

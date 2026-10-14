<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * VentasTable
 *
 * Tabla ORM para la gestión de ventas.
 * belongsTo Vendedores (foreign key: vendedor_id).
 */
class VentasTable extends Table
{
    /**
     * Inicializa la tabla.
     *
     * @param array<string, mixed> $config Configuración.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ventas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // Nombre de entidad explícito
        $this->setEntityClass('App\Model\Entity\Venta');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Vendedores', [
            'foreignKey' => 'vendedor_id',
            'className'  => 'App.Vendedores',
        ]);
    }

    /**
     * Reglas de validación de campos.
     *
     * @param \Cake\Validation\Validator $validator Validador.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('vendedor_id')
            ->requirePresence('vendedor_id', 'create', 'El vendedor es obligatorio.')
            ->notEmptyString('vendedor_id', 'El vendedor es obligatorio.');

        $validator
            ->decimal('monto')
            ->greaterThan('monto', 0, 'El monto debe ser mayor que cero.')
            ->requirePresence('monto', 'create', 'El monto es obligatorio.')
            ->notEmptyString('monto', 'El monto es obligatorio.');

        $validator
            ->date('fecha')
            ->requirePresence('fecha', 'create', 'La fecha es obligatoria.')
            ->notEmptyDate('fecha', 'La fecha es obligatoria.');

        return $validator;
    }

    /**
     * Reglas de aplicación.
     *
     * @param \Cake\ORM\RulesChecker $rules Verificador de reglas.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('vendedor_id', 'Vendedores'), [
            'errorField' => 'vendedor_id',
            'message'    => 'El vendedor especificado no existe.',
        ]);

        return $rules;
    }
}

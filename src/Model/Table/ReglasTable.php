<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ReglasTable
 *
 * Tabla ORM para las reglas de comisión por escalón.
 * Cada regla define una meta_minima (umbral) y un porcentaje_comision.
 */
class ReglasTable extends Table
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

        $this->setTable('reglas');
        $this->setDisplayField('meta_minima');
        $this->setPrimaryKey('id');

        // Nombre de entidad explícito
        $this->setEntityClass('App\Model\Entity\Regla');

        $this->addBehavior('Timestamp');
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
            ->decimal('meta_minima')
            ->greaterThanOrEqual('meta_minima', 0, 'La meta mínima debe ser igual o mayor a cero.')
            ->requirePresence('meta_minima', 'create', 'La meta mínima es obligatoria.')
            ->notEmptyString('meta_minima', 'La meta mínima es obligatoria.');

        $validator
            ->decimal('porcentaje_comision')
            ->greaterThanOrEqual('porcentaje_comision', 0, 'El porcentaje debe ser mayor o igual a cero.')
            ->lessThanOrEqual('porcentaje_comision', 100, 'El porcentaje no puede superar 100.')
            ->requirePresence('porcentaje_comision', 'create', 'El porcentaje de comisión es obligatorio.')
            ->notEmptyString('porcentaje_comision', 'El porcentaje de comisión es obligatorio.');

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
        $rules->add($rules->isUnique(['meta_minima']), [
            'errorField' => 'meta_minima',
            'message'    => 'Ya existe una regla con esta meta mínima.',
        ]);

        return $rules;
    }
}

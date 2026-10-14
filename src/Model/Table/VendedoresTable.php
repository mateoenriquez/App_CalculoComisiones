<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * VendedoresTable
 *
 * Tabla ORM para la gestión de vendedores.
 * hasMany Ventas (foreign key: vendedor_id).
 */
class VendedoresTable extends Table
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

        $this->setTable('vendedores');
        $this->setDisplayField('nombre');
        $this->setPrimaryKey('id');

        // Nombre de entidad explícito para evitar problemas de inflección en español
        $this->setEntityClass('App\Model\Entity\Vendedor');

        $this->addBehavior('Timestamp');

        $this->hasMany('Ventas', [
            'foreignKey' => 'vendedor_id',
            'className'  => 'App.Ventas',
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
            ->scalar('nombre')
            ->maxLength('nombre', 120, 'El nombre no puede superar 120 caracteres.')
            ->requirePresence('nombre', 'create', 'El nombre es obligatorio.')
            ->notEmptyString('nombre', 'El nombre no puede estar vacío.');

        return $validator;
    }

    /**
     * Reglas de aplicación (integridad referencial, unicidad, etc.).
     *
     * @param \Cake\ORM\RulesChecker $rules Verificador de reglas.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }
}

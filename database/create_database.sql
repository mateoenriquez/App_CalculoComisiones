-- Script de creación de la base de datos para el Sistema de Comisiones.
-- Ejecutar en MySQL Workbench o con el cliente mysql antes de correr las migraciones.
--
-- NOTA: Las tablas se crean mediante migraciones de CakePHP, NO con este script.
-- Este script solo crea la base de datos vacía con el charset correcto.
--
-- Uso en MySQL Workbench:
--   1. Abrir MySQL Workbench y conectarse al servidor local.
--   2. File > Open SQL Script > seleccionar este archivo.
--   3. Ejecutar (rayo ⚡ o Ctrl+Shift+Enter).
--
-- Uso con cliente CLI (PowerShell):
--   mysql -u root -p < database\create_database.sql

CREATE DATABASE IF NOT EXISTS sistema_comisiones
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

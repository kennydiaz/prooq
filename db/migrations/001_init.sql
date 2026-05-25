-- Migration 001: inicialización de la base.
-- En Hostinger la DB ya existe (con prefijo tipo u123_prooq_v2) — saltar el CREATE.
-- En local (XAMPP) este script crea la DB desde cero.

CREATE DATABASE IF NOT EXISTS prooq_v2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE prooq_v2;

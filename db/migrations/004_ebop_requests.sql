-- Tabla ebop_requests RETIRADA en rebrand SuiteHub (2026-05-25).
-- E-BOP ya no es un producto destacado; el lead-form de E-BOP fue eliminado.
-- Esta migración ahora hace DROP para sincronizar setups nuevos con el estado real.
-- Si necesitás volver a tener el endpoint /api/ebop en el futuro, esta migración
-- en versiones previas del repo (git log) tiene la CREATE TABLE original.

DROP TABLE IF EXISTS ebop_requests;

CREATE TABLE bitacora_log (
  id_bitacora_log INT AUTO_INCREMENT PRIMARY KEY
    COMMENT 'Identificador único del registro de log',
  id_bitacora INT NOT NULL
    COMMENT 'FK: referencia a bitacora.id_bitacora',
  descripcion_paso VARCHAR(255) NOT NULL
    COMMENT 'Descripción del paso (p.ej.: Inicio, Procesando, Termina, Error, etc.)',
  momento_de_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    COMMENT 'Fecha y hora del registro del paso',

  CONSTRAINT fk_bitacora_log__bitacora
    FOREIGN KEY (id_bitacora)
    REFERENCES bitacora (id_bitacora)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  INDEX idx_bitacora_log__bitacora_momento (id_bitacora, momento_de_registro)
) COMMENT 'Log detallado de pasos para cada ejecución registrada en bitacora';

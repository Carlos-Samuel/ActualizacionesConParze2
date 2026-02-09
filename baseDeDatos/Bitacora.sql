DROP TABLE IF EXISTS bitacora;

CREATE TABLE bitacora (
		id_bitacora INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador unico de la bitacora',
		tipo_de_cargue ENUM('FULL', 'DELTA') COMMENT 'tipo de cargue del envio realizado',
		fecha_ejecucion DATE COMMENT 'Fecha en que se realiza el proceso',
		hora_ejecucion TIME COMMENT 'Hora en que se realiza el proceso',
		origen_del_proceso ENUM('Manual', 'Automatico', 'Reenvio') COMMENT'Origen del proceso de envio',
		reintento INT DEFAULT 0 COMMENT 'Numero de reintento en caso de fallo',
		cantidad_registros_enviados INTEGER COMMENT 'Cantidad de registros que se enviaron en el proceso',
		tamaño_del_archivo VARCHAR(255) COMMENT 'Tamaño del archivo enviado en el proceso', 
        resultado_del_envio ENUM('Exitoso', 'Fallido') COMMENT 'resultado del envio exitoso o fallido',
        descripcion_error VARCHAR(255) COMMENT 'Descripcion del error en caso de fallo',
        parametros_usados VARCHAR(1024) COMMENT 'Parametros usados en el proceso',
		fecha_hora_de_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que inicia la ejecucion', 
		fecha_hora_de_fin TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de finalización de la ejecucion', 
        satisfactorio BOOLEAN COMMENT 'TRUE = ejecucion satisfactoria, FALSE = ejecucion con errores',
		ruta_archivo VARCHAR(255) COMMENT 'Ruta donde se almacena el archivo generado',
		archivo_borrado BOOLEAN DEFAULT FALSE comment 'TRUE = archivo borrado despues de enviarse, FALSE = archivo no borrado'
)COMMENT 'Tabla que almacena la bitacora de ejecuciones del sistema';
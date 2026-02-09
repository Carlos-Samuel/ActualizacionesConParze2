DROP TABLE IF EXISTS reportes;

CREATE TABLE reportes (
		id_reporte INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador unico del reporte',
		id_bitacora INTEGER COMMENT 'REFERENCIA ID Bitacora de ejecuciones',
		id_interno VARCHAR(255) COMMENT 'Identificador unico del producto en AGIL',
		cantidad INTEGER COMMENT 'Cantidad de productos en el reporte',
		precio_venta INTEGER COMMENT 'Precio de venta total del producto',
		descuento INTEGER COMMENT 'Descuento aplicado al producto',
		Fecha_hora_de_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se registro el reporte'
)COMMENT='Tabla que almacena los reportes realizados por el sistema';
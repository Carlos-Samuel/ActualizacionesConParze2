<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
    <?php include('partes/head.php'); ?>
    <style>
        <?php include('styles/bitacoras.css'); ?>
    </style>
</head>
<body>
    <div class="layout has-sidebar fixed-sidebar fixed-header">
        <?php include('partes/sidebar.php'); ?>  
        <div id="overlay" class="overlay">
        </div>
        <div class="layout">
            <div class="banner-titulo">
                Consulta de Bitacoras
            </div>
            <main class="content">
                <div class="card p-4 shadow mt-1">
                    <form id="formFechas">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="fechaInicio" class="form-label">Fecha inicio</label>
                                <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" required>
                            </div>
                            <div class="col-md-3">
                                <label for="fechaFin" class="form-label">Fecha fin</label>
                                <input type="date" class="form-control" id="fechaFin" name="fechaFin" required>
                            </div>
                        </div>    
                        <button type="submit" class="btn btn-primary">Procesar</button>
                        <button type="button" id="btnBorrarRango" class="btn btn-danger ms-2">
                            Borrar archivos en este rango
                        </button>
                    </form>    
                    <br>
                    <div id="contenedorTabla" class="mt-1" style="display: none;">
                        <table id="tabla-bitacoras" class="table table-bordered table-striped" style="width: 100%;">                                                
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Origen</th>
                                    <th>Reintento</th>
                                    <th>No.Reg.</th>
                                    <th>Tamaño</th>
                                    <th>Resultado</th>
                                    <th>Mensaje de Error</th>
                                    <th>Parametros</th>
                                    <th>Inicio</th>
                                    <th>Final</th>
                                    <th>Archivo</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include('partes/foot.php'); ?>  
    <script src="scripts/bitacoras.js"></script>
</body>
</html>

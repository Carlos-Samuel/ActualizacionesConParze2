<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
    <?php include('partes/head.php'); ?>
    <style>
        <?php include('styles/descuentos.css'); ?>
    </style>
</head>
<body>
    <div class="layout has-sidebar fixed-sidebar fixed-header">
        <?php include('partes/sidebar.php'); ?>  
        <div id="overlay" class="overlay">
        </div>
        <div class="layout">
            <div class="banner-titulo">
                Descuentos
            </div>
            <main class="content">
                <div class="card p-4 shadow mt-1">
                    <div class="mb-2">
                    <div class="mb-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold m-0">Sub Grupos disponibles</label>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="tabla-subgrupos">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:280px;">Empresa</th>   
                                        <th style="width:320px;">Grupo</th>
                                        <th>Subgrupo</th>
                                        <th style="width:160px;">Descuento (0-100)</th>
                                        <th style="width:160px;">Fecha inicio</th>
                                        <th style="width:160px;">Fecha fin</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>

                        </div>

                        <div class="text-end mt-2">
                            <button id="btn-guardar-descuentos" class="btn btn-primary" disabled>Guardar descuento de subgrupo</button>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php include('partes/foot.php'); ?>  
    <script src="scripts/descuentos.js"></script>
</body>
</html>

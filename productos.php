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
                Productos
            </div>
            <main class="content">
                <div class="card p-4 shadow mt-1">
                    <div class="mb-2">
                    <div class="mb-2">

                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="tabla-productos">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:280px;">Producto</th>   
                                        <th style="width:320px;">Codigo</th>
                                        <th style="width:320px;">Codigo Parze</th>
                                        <th style="width:160px;">Precio</th>
                                        <th style="width:160px;">Unitaria</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php include('partes/foot.php'); ?>  
    <script src="scripts/productos.js"></script>
</body>
</html>

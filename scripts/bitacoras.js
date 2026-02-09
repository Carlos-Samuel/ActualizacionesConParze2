

let tablaInicializada = false;

function esc(s) {
    return String(s)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;');
}

function cargarTabla(datosFiltro) {
    if (tablaInicializada) {
        $('#tabla-bitacoras').DataTable().clear().destroy();
    }

    $('#contenedorTabla').show();

    $('#tabla-bitacoras').DataTable({
        scrollX: true,
        scrollY: '50vh',
        scrollCollapse: true,
        fixedHeader: true,
        deferRender: true,
        order: [[0, 'desc']],
        ajax: {
            url: 'controladores/listarBitacoras.php',
            type: 'POST',
            data: datosFiltro,
            dataSrc: function (json) {
                if (Array.isArray(json)) return json;
                if (json && Array.isArray(json.data)) return json.data;
                    console.error('Formato JSON inesperado:', json);
                    return [];
                },
            error: function (xhr, status, err) {
                console.error('Error AJAX:', status, err, xhr?.responseText);
            }
        },
        columns: [
            { data: 'id_bitacora',              render: $.fn.dataTable.render.text() },
            { data: 'tipo_de_cargue',           render: $.fn.dataTable.render.text() },
            { data: 'fecha_ejecucion',          render: $.fn.dataTable.render.text() },
            { data: 'hora_ejecucion',           render: $.fn.dataTable.render.text() },
            { data: 'origen_del_proceso',       render: $.fn.dataTable.render.text() },
            { data: 'reintento',                 render: $.fn.dataTable.render.text() },
            { data: 'cantidad_registros_enviados', render: $.fn.dataTable.render.text() },
            { data: 'tamaño_del_archivo',       render: $.fn.dataTable.render.text() },
            { data: 'resultado_del_envio',      render: $.fn.dataTable.render.text() },
            { data: 'descripcion_error',        render: $.fn.dataTable.render.text() },
            { data: 'parametros_usados',        render: $.fn.dataTable.render.text() },
            { data: 'fecha_hora_de_inicio',     render: $.fn.dataTable.render.text() },
            { data: 'fecha_hora_de_fin',        render: $.fn.dataTable.render.text() },
            {
            data: null,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                const ruta    = (row.ruta_archivo ?? '').trim();
                const borrado = Number(row.archivo_borrado) === 1;

                if (borrado) {
                return '<span class="text-danger" title="Archivo borrado"><i class="fa fa-trash"></i></span>';
                }
                if (ruta) {
                const safeUrl = 'exports/' + encodeURIComponent(ruta);
                return `<a class="btn btn-sm btn-outline-primary"
                            href="${esc(safeUrl)}"
                            title="Descargar" target="_blank" rel="noopener">
                            <i class="fa fa-download"></i>
                        </a>`;
                }
                return '<span class="text-muted" title="Sin archivo"></span>';
            }
            }
        ],
        //language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
        order: [[0, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' } 
        });


    tablaInicializada = true;

};

$('#btnBorrarRango').on('click', function () {
  const fechaInicio = $('#fechaInicio').val();
  const fechaFin    = $('#fechaFin').val();

  if (!fechaInicio || !fechaFin) {
    alert('Por favor selecciona el rango de fechas.');
    return;
  }

  if (!confirm(`Se eliminarán los archivos físicos (si existen) con fecha entre ${fechaInicio} y ${fechaFin}, y se marcarán como borrados en la bitácora. ¿Continuar?`)) {
    return;
  }

  const $btn = $(this);
  $btn.prop('disabled', true).text('Borrando...');

  $.ajax({
    url: 'controladores/borrarArchivos.php',
    type: 'POST',
    dataType: 'json',
    data: { fechaInicio, fechaFin },
  })
  .done(function (resp) {
    if (resp && resp.ok) {
      const msg = [
        `Total en rango: ${resp.total ?? 0}`,
        `Borrados físicamente: ${resp.borrados ?? 0}`,
        `No encontrados: ${resp.no_encontrados ?? 0}`,
        `Errores: ${resp.errores ?? 0}`
      ].join('\n');
      alert('Proceso completado:\n' + msg);

      // recargar el DataTable si ya está inicializado
      if ($.fn.DataTable.isDataTable('#tabla-bitacoras')) {
        $('#tabla-bitacoras').DataTable().ajax.reload(null, false);
      }
    } else {
      console.error('Respuesta inesperada:', resp);
      alert('Ocurrió un problema al borrar. Revisa la consola.');
    }
  })
  .fail(function (xhr, status, err) {
    console.error('Error al borrar:', status, err, xhr?.responseText);
    alert('Fallo la operación de borrado. Revisa la consola para más detalles.');
  })
  .always(function () {
    $btn.prop('disabled', false).text('Borrar archivos en este rango');
  });
});


$('#formFechas').on('submit', function(e) {
    e.preventDefault();
    cargarTabla({
        fechaInicio: $('#fechaInicio').val(),
        fechaFin: $('#fechaFin').val()
    });

});


$(document).ready(function () {
    const fechaActual = new Date();
    const primerDiaMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);

    function formatear(fecha) {
        const anio = fecha.getFullYear();
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const dia = fecha.getDate().toString().padStart(2, '0');
        return `${anio}-${mes}-${dia}`;
    }

    $('#fechaInicio').val(formatear(primerDiaMes));
    $('#fechaFin').val(formatear(fechaActual));
});
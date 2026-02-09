// scripts/productos.js
$(function () {
  const $tabla = $("#tabla-productos");

  $.ajax({
    url: "controladores/productos_dt.php",
    method: "GET",
    dataType: "json",
  })
    .done(function (res) {
      if (!res || res.ok !== true) {
        console.error("Respuesta inválida:", res);
        return;
      }

      $tabla.DataTable({
        data: res.data || [],
        deferRender: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, "asc"]],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        columns: [
          { data: "pronom" },
          { data: "presentacion" },
          { data: "codigo_parze" },
          {
            data: "precio",
            className: "text-end",
            render: function (data, type) {
              if (type === "display" || type === "filter") {
                if (data === null || data === undefined || data === "") return "";
                const n = Number(data);
                if (Number.isNaN(n)) return data;
                return n.toLocaleString("es-CO", { maximumFractionDigits: 2 });
              }
              return data;
            },
          },
        ],
      });
    })
    .fail(function (xhr) {
      console.error("Error llamando productos_dt.php:", xhr.responseText);
    });
});

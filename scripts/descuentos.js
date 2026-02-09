// scripts/parametrizacion.js
// Requiere jQuery y opcionalmente SweetAlert2 (Swal)

const ENDPOINT_GET  = 'controladores/obtenerParametro.php';
const ENDPOINT_SAVE = 'controladores/guardarParametro.php';
const PARAM_GRUPOS = 'GRUPOS_SELECCIONADOS';
//const ENDPOINT_LISTAR_GRUPOS = 'controladores/listarGrupos.php';
const PARAM_SUBGRUPOS = 'SUBGRUPOS_CON_DESCUENTO';
const ENDPOINT_LISTAR_SUBGRUPOS = 'controladores/listarSubGrupos.php';

// Definición de parámetros y reglas  SE QUITAN TODOS 
const PARAMS = [];

let seleccionInicialNorm = '';
let gruposCache = []; 
let subGruposSeleccionInicialNorm = '';
let $tablaSubGruposBody = null;

$(document).ready(function () {
    PARAMS.forEach(setupParam);
    $tablaSubGruposBody = $('#tabla-subgrupos tbody');

    cargarSubGruposYSeleccion();

    $('#btn-guardar-descuentos').on('click', function () {
        guardarSubGruposSeleccionadas();
    });

});

function setupParam(def) {
  const $input = $(def.input);
  const $btn   = $(def.button);

  let initial = '';

  // Carga
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: def.code }
  })
  .done(function (resp) {
    if (resp.statusCode === 200 && resp.parametro) {
      const val = def.loadTransform(resp.parametro.valor);
      $input.val(val);
      initial = val;
      $btn.prop('disabled', true);
    } else if (resp.statusCode === 404) {
      // no vigente encontrado: queda vacío
      initial = '';
      $btn.prop('disabled', true);
      // opcional: info(`No hay valor vigente para ${def.code}, puedes registrarlo.`);
    } else if (resp.statusCode === 409) {
      error(`Existen múltiples vigentes para ${def.code}. Corrige la inconsistencia.`);
    } else {
      error(resp.mensaje || `No se pudo cargar el parámetro ${def.code}.`);
    }
  })
  .fail(function () {
    error(`Fallo al obtener el parámetro ${def.code}.`);
  });

  // Habilitar botón solo si hay cambios
  $input.on('input change', function () {
    const current = $input.val().trim();
    $btn.prop('disabled', current === initial);
  });

  // Guardar
  $btn.on('click', function () {
    const raw = $input.val();
    const msg = def.validate(raw);
    if (msg) { warn(msg); return; }

    const toSave = def.saveTransform(raw);
    console.log(`Guardando ${def.code} = ${toSave}`);
    $btn.prop('disabled', true); // prevenir múltiples clicks

    $.ajax({
      url: ENDPOINT_SAVE,
      method: 'POST',
      dataType: 'json',
      data: {
        codigo: def.code,
        descripcion: def.desc,
        valor: toSave
      }
    })
    .done(function (resp) {
      if (resp.statusCode === 200) {
        ok('Parámetro guardado correctamente.');
        initial = (def.loadTransform === undefined) ? toSave : def.loadTransform(toSave);
        $btn.prop('disabled', true);
      } else {
        error(resp.mensaje || `No se pudo guardar ${def.code}.`);
      }
    })
    .fail(function () {
      error(`Fallo al guardar el parámetro ${def.code}.`);
    });
  });
}

function cargarGruposYSeleccion() {
  // 1) Obtener parámetro vigente (lista emprcod separados por ;)
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: PARAM_GRUPOS }
  })
  .done(function (respParam) {
    let seleccionSet = new Set();
    if (respParam.statusCode === 200 && respParam.parametro && respParam.parametro.valor) {
      seleccionSet = parseSeleccionToSet(respParam.parametro.valor);
      seleccionInicialNorm = normalizeSeleccion(seleccionSet);
    } else if (respParam.statusCode === 404) {
      // No hay selección vigente
      seleccionSet = new Set();
      seleccionInicialNorm = '';
    } else if (respParam.statusCode === 409) {
      error('Existen múltiples parámetros vigentes para GRUPOS_SELECCIONADAS. Corrige la inconsistencia.');
      return;
    } else if (respParam.statusCode && respParam.statusCode !== 200) {
      error(respParam.mensaje || 'No se pudo cargar la selección de grupos.');
      return;
    }

    // 2) Listar empresas
    $.ajax({
      url: ENDPOINT_LISTAR_GRUPOS,
      method: 'POST',
      dataType: 'json'
    })
    .done(function (respGrp) {
      if (respGrp.statusCode === 200 && Array.isArray(respGrp.grupos)) {
        gruposCache = respGrp.grupos ; // [{grpcod, grpnom, emprcod, emprnom}]
        renderTablaGrupos(gruposCache, seleccionSet);
        $('#btn-guardar-grupos').prop('disabled', true);
      } else {
        error(respGrp.mensaje || 'No se pudieron cargar las grupos.');
      }
    })
    .fail(function () {
      error('Fallo al comunicarse con el servidor al listar grupos.');
    });

  })
  .fail(function () {
    error('Fallo al obtener el parámetro GRUPOS_SELECCIONADAS.');
  });
}

function renderTablaGrupos(grupos, seleccionSet) {
  $tablaGruposBody.empty();

  grupos.forEach(e => {
    const selected = seleccionSet.has(String(e.grpcod));
    const row = $(`
      <tr data-cod="${escapeHtml(String(e.grpcod))}">
        <td class="text-monospace">${escapeHtml(String(e.grpcod))}</td>
        <td>${escapeHtml(String(e.grpnom))}</td>
        <td>
          <select class="form-select form-select-sm grp-sel">
            <option value="NO">No</option>
            <option value="SI">Sí</option>
          </select>
        </td>
      </tr>
    `);
    row.find('select.grp-sel').val(selected ? 'SI' : 'NO');

    // Escuchar cambios para habilitar botón guardar si difiere de la selección inicial
    row.find('select.grp-sel').on('change', function () {
      const norm = normalizeSeleccion(getSeleccionActualComoSet());
      $('#btn-guardar-grupos').prop('disabled', norm === seleccionInicialNorm);
    });

    $tablaGruposBody.append(row);
  });
}

function getSeleccionActualComoSet() {
  const set = new Set();
  $tablaGruposBody.find('tr').each(function () {
    const cod = $(this).data('cod');
    const val = $(this).find('select.grp-sel').val();
    if (val === 'SI') set.add(String(cod));
  });
  return set;
}

function guardarGruposSeleccionadas() {
  const seleccionSet = getSeleccionActualComoSet();
  // Convertir a string "cod1;cod2;cod3"
  const valor = Array.from(seleccionSet).sort().join(';');

  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: {
      codigo: PARAM_GRUPOS,
      descripcion: 'Grupos seleccioano(grpcod separados por ;)',
      valor: valor
    }
  })
  .done(function (resp) {
    if (resp.statusCode === 200) {
      ok('Selección de grupos guardada correctamente.');
      // Actualizar baseline
      seleccionInicialNorm = normalizeSeleccion(seleccionSet);
      $('#btn-guardar-grupos').prop('disabled', true); 
       cargarSubGruposYSeleccion();
    } else {
      error(resp.mensaje || 'No se pudo guardar la selección de grupos.');
    }
  })
  .fail(function () {
    error('Fallo al comunicarse con el servidor al guardar la selección de grupos.');
  });
}

// ===== utilidades selección =====
function parseSeleccionToSet(valorStr) {
  return new Set(
    String(valorStr)
      .split(';')
      .map(s => s.trim())
      .filter(s => s.length > 0)
  );
}
function normalizeSeleccion(set) {
  
  return Array.from(set).sort().join(';'); // normaliza para comparar cambios
}
function escapeHtml(s) {
  return s.replace(/[&<>"'`=\/]/g, function (c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c];
  });
}


function cargarSubGruposYSeleccion() {
  // 1) Obtener selección vigente de parámetro
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: PARAM_SUBGRUPOS }
  })
  .done(function (respParam) {
    let seleccionSetS = new Set();
    console.log('Respuesta del parámetro de subgrupos:', respParam);
    if (respParam.statusCode === 200 && respParam.parametro && respParam.parametro.valor) {
      seleccionSetS = parseSeleccionToSet(respParam.parametro.valor);
      subGruposSeleccionInicialNorm = normalizeSeleccion(seleccionSetS);
    } else if (respParam.statusCode === 404) {
      seleccionSetS = new Set();
      subGruposSeleccionInicialNorm = '';
    } else if (respParam.statusCode === 409) {
      error('Existen múltiples parámetros vigentes para SUBRUPOS_SELECCIONADAS. Corrige la inconsistencia.');
      return;
    } else if (respParam.statusCode && respParam.statusCode !== 200) {
      error(respParam.mensaje || 'No se pudo cargar la selección de subrupos.');
      return;
    }

    // 2) Listar bodegas asignadas a empresa
    $.ajax({
      url: ENDPOINT_LISTAR_SUBGRUPOS,
      method: 'POST',
      dataType: 'json'
      
    })
    .done(function (respSub) {
      // borrar console
      console.log('Respuesta del listado de subgrupos:', respSub);
      if (respSub.statusCode === 200 && Array.isArray(respSub.subgrupos)) {

        subgruposCache = respSub.subgrupos; // [{subcod, subnom, grpcod, grpnom}]
        renderTablaSubGrupos(subgruposCache, seleccionSetS);
        $('#btn-guardar-descuentos').prop('disabled', true);
      } else {
        error(respSub.mensaje || 'No se pudieron cargar los subgrupos.');
      }
    })
    .fail(function () {
      error('Fallo al comunicarse con el servidor al listar subgrupos.');
    });

  })
  .fail(function () {
    error('Fallo al obtener el parámetro SUBGRUPOS_SELECCIONADOS.');
  });
}

function renderTablaSubGrupos(subgrupos, seleccionSetS) {
  $tablaSubGruposBody.empty();

  subgrupos.forEach(s => {
    let descuento   = 0;
    let fechaInicio = '';
    let fechaFin    = '';

    const subgrupo  = String(s.subid);
    const resultado = Array.from(seleccionSetS).find(item => item.startsWith(subgrupo + ":"));

    if (resultado) {
      const partes = resultado.split(':');
      // Formato viejo: subid:descuento
      // Formato nuevo: subid:descuento:fechaInicio:fechaFin
      descuento   = partes[1] ?? 0;
      fechaInicio = partes[2] ?? '';
      fechaFin    = partes[3] ?? '';
    }

    const row = $(`
      <tr data-subid="${escapeHtml(String(s.subid))}">
        <td>${escapeHtml(String(s.empnom))}</td>
        <td class="text-monospace">${escapeHtml(String(s.grpnom))}</td>
        <td>${escapeHtml(String(s.subnom))}</td>
        <td>
          <input type="number" class="form-control form-control-sm sub-des"
                 min="0" max="100" placeholder="0–100"
                 value="${escapeHtml(String(descuento))}">
        </td>
        <td>
          <input type="date" class="form-control form-control-sm sub-fi"
                 value="${escapeHtml(String(fechaInicio))}">
        </td>
        <td>
          <input type="date" class="form-control form-control-sm sub-ff"
                 value="${escapeHtml(String(fechaFin))}">
        </td>
      </tr>
    `);

    // Cuando cambie cualquier campo, verificamos si hubo cambios
    row.find('input.sub-des, input.sub-fi, input.sub-ff').on('change', function () {
      const norm = normalizeSeleccion(getSeleccionSubGruposActualComoSet());
      $('#btn-guardar-descuentos').prop('disabled', norm === subGruposSeleccionInicialNorm);
    });

    $tablaSubGruposBody.append(row);
  });
}


function getSeleccionSubGruposActualComoSet() {
  const set = new Set();

  $tablaSubGruposBody.find('tr').each(function () {
    const cod   = $(this).data('subid');
    const val   = $(this).find('input.sub-des').val();
    const fi    = $(this).find('input.sub-fi').val();
    const ff    = $(this).find('input.sub-ff').val();

    const numVal = parseFloat(val) || 0;

    if (numVal > 0 && numVal <= 100) {
      // Aunque luego validemos fechas, aquí las incluimos para que cuenten como cambio
      set.add(`${cod}:${numVal}:${fi || ''}:${ff || ''}`);
    }
  });

  return set;
}


function guardarSubGruposSeleccionadas() {
  const seleccionSetS = new Set();
  let errorMsg = '';

  $tablaSubGruposBody.find('tr').each(function () {
    const cod   = $(this).data('subid');
    const val   = $(this).find('input.sub-des').val();
    const fi    = $(this).find('input.sub-fi').val();
    const ff    = $(this).find('input.sub-ff').val();

    const numVal = parseFloat(val) || 0;
    const tieneFechas = (fi && fi.trim().length > 0) || (ff && ff.trim().length > 0);

    if (numVal > 0 && numVal <= 100) {
      // Descuento > 0 ⇒ ambas fechas obligatorias
      if (!fi || !ff) {
        errorMsg = 'Para todos los subgrupos con descuento mayor a 0 es obligatorio diligenciar la fecha de inicio y la fecha de fin.';
        return false; // rompe el each
      }
      seleccionSetS.add(`${cod}:${numVal}:${fi}:${ff}`);
    } else if (numVal === 0 && tieneFechas) {
      // Opcional: si descuento 0 pero puso fechas, lo consideramos error
      errorMsg = 'Si el descuento es 0, no debes diligenciar fechas de vigencia.';
      return false;
    }
  });

  if (errorMsg) {
    error(errorMsg);
    return;
  }

  const valor = Array.from(seleccionSetS).sort().join(';');

  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: {
      codigo: PARAM_SUBGRUPOS,
      descripcion: 'Subgrupos con descuentos (subid:descuento:fechaInicio:fechaFin separados por ;)',
      valor: valor
    }
  })
  .done(function (resp) {
    if (resp.statusCode === 200) {
      ok('Descuentos de subgrupos guardados correctamente.');
      subGruposSeleccionInicialNorm = normalizeSeleccion(seleccionSetS);
      $('#btn-guardar-descuentos').prop('disabled', true);
    } else {
      error(resp.mensaje || 'No se pudo guardar los subgrupos con descuento.');
    }
  })
  .fail(function () {
    error('Fallo al comunicarse con el servidor al guardar los subgrupos con descuento.');
  });
}


// Notificaciones
function ok(msg)   { if (window.Swal) Swal.fire('Éxito',       msg, 'success'); else alert(msg); }
function info(msg) { if (window.Swal) Swal.fire('Información', msg, 'info');    else alert(msg); }
function warn(msg) { if (window.Swal) Swal.fire('Atención',    msg, 'warning'); else alert(msg); }
function error(msg){ if (window.Swal) Swal.fire('Error',       msg, 'error');   else alert(msg); }

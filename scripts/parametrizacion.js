// scripts/parametrizacion.js
// Requiere jQuery y opcionalmente SweetAlert2 (Swal)

const ENDPOINT_GET  = 'controladores/obtenerParametro.php';
const ENDPOINT_SAVE = 'controladores/guardarParametro.php';

const PARAM_EMPRESAS = 'EMPRESA'; // ahora será 1 sola, pero mantenemos el código
const ENDPOINT_LISTAR_EMPRESAS = 'controladores/listarEmpresas.php';

const PARAM_BODEGAS = 'BODEGA';   // ahora 1 sola, mantenemos el código
const ENDPOINT_LISTAR_BODEGAS = 'controladores/listarBodegas.php';

const PARAM_PRECIOS = 'PRECIOS';                 // nuevo parámetro
const ENDPOINT_LISTAR_PRECIOS = 'controladores/listarPrecios.php';

// Definición de parámetros y reglas
const PARAMS = [
  {
    code: 'URL',
    input: '#param-url',
    button: '#btn-guardar-url',
    desc: 'URL del servicio',
    loadTransform: v => (v ?? '').trim(),
    saveTransform: v => v.trim(),
    validate: (val) => {
      if (!val) return 'Debes ingresar una URL.';
      try {
        const u = new URL(val);
        if (!/^https?:$/.test(u.protocol)) return 'La URL debe iniciar con http:// o https://';
      } catch (e) {
        return 'La URL no es válida.';
      }
      return null;
    }
  },
  {
    code: 'HORA_CARGUE_FULL',
    input: '#param-hora-full',
    button: '#btn-guardar-hora-full',
    desc: 'Hora del cargue diario FULL',
    loadTransform: v => {
      // Normaliza a HH:MM para <input type="time">
      const raw = (v ?? '').trim();
      if (!raw) return '';
      // Acepta HH:MM o HH:MM:SS
      const m = raw.match(/^(\d{2}):(\d{2})(?::\d{2})?$/);
      return m ? `${m[1]}:${m[2]}` : '';
    },
    saveTransform: v => {
      // Guarda en HH:MM:00
      const t = v.trim();
      if (!t) return '';
      const m = t.match(/^(\d{2}):(\d{2})$/);
      return m ? `${m[1]}:${m[2]}:00` : t;
    },
    validate: (val) => {
      if (!val) return 'Debes seleccionar una hora.';
      if (!/^\d{2}:\d{2}$/.test(val)) return 'Formato de hora inválido.';
      return null;
    }
  },
  {
    code: 'FRECUENCIA_CARGUE_HORAS',
    input: '#param-frecuencia',
    button: '#btn-guardar-frecuencia',
    desc: 'Frecuencia del cargue periódico en horas',
    loadTransform: v => (v ?? '').toString().trim(),
    saveTransform: v => v.trim(),
    validate: (val) => {
      if (val === '') return 'Debes ingresar la frecuencia en horas.';
      const n = Number(val);
      if (!Number.isInteger(n)) return 'La frecuencia debe ser un entero.';
      if (n < 3) return 'La frecuencia mínima es de 3 horas.';
      return null;
    }
  },
  {
    code: 'APIKEY',
    input: '#param-apikey',
    button: '#btn-guardar-apikey',
    desc: 'API Key',
    loadTransform: v => (v ?? '').trim(),
    saveTransform: v => v.trim(),
    validate: (val) => {
      if (!val) return 'Debes ingresar la API Key.';
      return null;
    }
  },
  {
    code: 'REINTENTOS_API',
    input: '#param-reintentos',
    button: '#btn-guardar-reintentos',
    desc: 'Número de reintentos automáticos',
    loadTransform: v => (v ?? '').toString().trim(),
    saveTransform: v => v.trim(),
    validate: (val) => {
      if (val === '') return 'Debes ingresar el número de reintentos.';
      const n = Number(val);
      if (!Number.isInteger(n) || n < 0) return 'Los reintentos deben ser un entero ≥ 0.';
      // recomendado <= 10 (no bloquea)
      return null;
    }
  },
  {
    code: 'TIEMPO_ENTRE_REINTENTOS',
    input: '#param-tiempo-reintentos',
    button: '#btn-guardar-tiempo-reintentos',
    desc: 'Tiempo entre reintentos automáticos (minutos)',
    loadTransform: v => (v ?? '').toString().trim(),
    saveTransform: v => v.trim(),
    validate: (val) => {
      if (val === '') return 'Debes ingresar el tiempo entre reintentos.';
      const n = Number(val);
      if (!Number.isInteger(n)) return 'El tiempo entre reintentos debe ser un entero.';
      if (n < 1 || n > 60) return 'El tiempo entre reintentos debe estar entre 1 y 60 minutos.';
      return null;
    }
  },

];

let empresasCache = [];
let empresaSeleccionInicial = '';
let $tablaEmpresasBody = null;

let bodegasCache = [];
let bodegaSeleccionInicial = '';
let $tablaBodegasBody = null;

let preciosCache = [];
let precioSeleccionInicial = '';
let $tablaPreciosBody = null;

$(document).ready(function () {
  PARAMS.forEach(setupParam);

  $tablaEmpresasBody = $('#tabla-empresas tbody');
  $tablaBodegasBody = $('#tabla-bodegas tbody');
  $tablaPreciosBody  = $('#tabla-precios tbody');

  cargarEmpresasYSeleccion();

  $('#btn-guardar-empresas').on('click', guardarEmpresaSeleccionada);
  $('#btn-guardar-bodegas').on('click', guardarBodegaSeleccionada);
  $('#btn-guardar-precios').on('click', guardarPrecioSeleccionado);

});

function firstOfParamValue(valor) {
  if (!valor) return '';
  const parts = String(valor).split(';').map(s => s.trim()).filter(Boolean);
  return parts.length ? parts[0] : '';
}



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

function cargarEmpresasYSeleccion() {
  // Obtener empresa vigente
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: PARAM_EMPRESAS }
  })
  .done(function (respParam) {
    if (respParam.statusCode === 200 && respParam.parametro) {
      empresaSeleccionInicial = firstOfParamValue(respParam.parametro.valor);
    } else if (respParam.statusCode === 404) {
      empresaSeleccionInicial = '';
    } else if (respParam.statusCode === 409) {
      error('Existen múltiples parámetros vigentes para EMPRESA. Corrige la inconsistencia.');
      return;
    } else if (respParam.statusCode && respParam.statusCode !== 200) {
      error(respParam.mensaje || 'No se pudo cargar la empresa parametrizada.');
      return;
    }

    // Listar empresas
    $.ajax({
      url: ENDPOINT_LISTAR_EMPRESAS,
      method: 'POST',
      dataType: 'json'
    })
    .done(function (respEmp) {
      if (respEmp.statusCode === 200 && Array.isArray(respEmp.empresas)) {
        empresasCache = respEmp.empresas; // [{emprcod, emprnom}]
        renderTablaEmpresas(empresasCache, empresaSeleccionInicial);
        $('#btn-guardar-empresas').prop('disabled', true);

        // Con empresa parametrizada, cargamos bodegas y precios filtrados
        cargarBodegasYSeleccion();
        cargarPreciosYSeleccion();
      } else {
        error(respEmp.mensaje || 'No se pudieron cargar las empresas.');
      }
    })
    .fail(function () {
      error('Fallo al comunicarse con el servidor al listar empresas.');
    });

  })
  .fail(function () {
    error('Fallo al obtener el parámetro EMPRESA.');
  });
}

function renderTablaEmpresas(empresas, selectedEmpr) {
  $tablaEmpresasBody.empty();

  empresas.forEach(e => {
    const code = String(e.emprcod);
    const row = $(`
      <tr data-cod="${escapeHtml(code)}">
        <td class="text-monospace">${escapeHtml(code)}</td>
        <td>${escapeHtml(String(e.emprnom))}</td>
        <td>
          <input type="radio" name="empresa-sel" class="form-check-input empresa-radio">
        </td>
      </tr>
    `);
    row.find('input.empresa-radio').prop('checked', code === selectedEmpr);

    row.find('input.empresa-radio').on('change', function () {
      const current = getEmpresaSeleccionada();
      $('#btn-guardar-empresas').prop('disabled', current === empresaSeleccionInicial);
      $('#btn-guardar-bodegas').prop('disabled', true);
      $tablaPreciosBody.empty();
      $('#btn-guardar-precios').prop('disabled', true);
      info('Guarda la empresa para cargar sus bodegas y listas de precios.');
    });

    $tablaEmpresasBody.append(row);
  });
}

function getEmpresaSeleccionada() {
  let sel = '';
  $tablaEmpresasBody.find('tr').each(function () {
    const checked = $(this).find('input.empresa-radio').is(':checked');
    if (checked) {
      sel = String($(this).data('cod'));
      return false;
    }
  });
  return sel;
}

function guardarEmpresaSeleccionada() {
  const sel = getEmpresaSeleccionada();
  if (!sel) { warn('Debes seleccionar una empresa.'); return; }

  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: {
      codigo: PARAM_EMPRESAS,
      descripcion: 'Empresa habilitada (emprcod único)',
      valor: sel
    }
  })
  .done(function (resp) {
    if (resp.statusCode === 200) {
      ok('Empresa guardada correctamente.');
      empresaSeleccionInicial = sel;
      $('#btn-guardar-empresas').prop('disabled', true);

      // Al guardar empresa, recargamos bodegas y precios filtrados
      cargarBodegasYSeleccion();
      cargarPreciosYSeleccion();
    } else {
      error(resp.mensaje || 'No se pudo guardar la empresa.');
    }
  })
  .fail(function () {
    error('Fallo al comunicarse con el servidor al guardar la empresa.');
  });
}

/* ===============================
   Bodegas (selección ÚNICA, filtradas por empresa)
   =============================== */
function cargarBodegasYSeleccion() {
  // Primero obtenemos la empresa guardada (por si el usuario no ha dado guardar pero marcó otra)
  const empresaParam = empresaSeleccionInicial;

  // Obtener bodega vigente
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: PARAM_BODEGAS }
  })
  .done(function (respParam) {
    if (respParam.statusCode === 200 && respParam.parametro) {
      bodegaSeleccionInicial = firstOfParamValue(respParam.parametro.valor);
    } else if (respParam.statusCode === 404) {
      bodegaSeleccionInicial = '';
    } else if (respParam.statusCode === 409) {
      error('Existen múltiples parámetros vigentes para BODEGAS_SELECCIONADAS. Corrige la inconsistencia.');
      return;
    } else if (respParam.statusCode && respParam.statusCode !== 200) {
      error(respParam.mensaje || 'No se pudo cargar la bodega parametrizada.');
      return;
    }

    // Listar bodegas filtradas por empresa
    $.ajax({
      url: ENDPOINT_LISTAR_BODEGAS,
      method: 'POST',
      dataType: 'json',
      data: { emprcod: empresaParam } // <-- IMPORTANTE: filtra por empresa parametrizada
    })
    .done(function (respBod) {
      if (respBod.statusCode === 200 && Array.isArray(respBod.bodegas)) {
        bodegasCache = respBod.bodegas; // [{bodcod, bodnom, emprcod, emprnom}]
        renderTablaBodegas(bodegasCache, bodegaSeleccionInicial);
        $('#btn-guardar-bodegas').prop('disabled', true);
      } else {
        error(respBod.mensaje || 'No se pudieron cargar las bodegas.');
      }
    })
    .fail(function () {
      error('Fallo al comunicarse con el servidor al listar bodegas.');
    });

  })
  .fail(function () {
    error('Fallo al obtener el parámetro BODEGAS_SELECCIONADAS.');
  });
}

function renderTablaBodegas(bodegas, selectedBod) {
  $tablaBodegasBody.empty();

  bodegas.forEach(b => {
    const bod = String(b.bodcod);
    const row = $(`
      <tr data-bod="${escapeHtml(bod)}">
        <td>${escapeHtml(String(b.emprnom))}</td>
        <td class="text-monospace">${escapeHtml(bod)}</td>
        <td>${escapeHtml(String(b.bodnom))}</td>
        <td>
          <input type="radio" name="bodega-sel" class="form-check-input bodega-radio">
        </td>
      </tr>
    `);
    row.find('input.bodega-radio').prop('checked', bod === selectedBod);

    row.find('input.bodega-radio').on('change', function () {
      const current = getBodegaSeleccionada();
      $('#btn-guardar-bodegas').prop('disabled', current === bodegaSeleccionInicial);
    });

    $tablaBodegasBody.append(row);
  });
}

function getBodegaSeleccionada() {
  let sel = '';
  $tablaBodegasBody.find('tr').each(function () {
    const checked = $(this).find('input.bodega-radio').is(':checked');
    if (checked) {
      sel = String($(this).data('bod'));
      return false;
    }
  });
  return sel;
}

function guardarBodegaSeleccionada() {
  const sel = getBodegaSeleccionada();
  if (!sel) { warn('Debes seleccionar una bodega.'); return; }

  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: {
      codigo: PARAM_BODEGAS,
      descripcion: 'Bodega habilitada (bodcod único)',
      valor: sel
    }
  })
  .done(function (resp) {
    if (resp.statusCode === 200) {
      ok('Bodega guardada correctamente.');
      bodegaSeleccionInicial = sel;
      $('#btn-guardar-bodegas').prop('disabled', true);
    } else {
      error(resp.mensaje || 'No se pudo guardar la bodega.');
    }
  })
  .fail(function () {
    error('Fallo al comunicarse con el servidor al guardar la bodega.');
  });
}

/* ===============================
   PRECIOS (selección ÚNICA, filtrados por empresa)
   =============================== */
function cargarPreciosYSeleccion() {
  const empresaParam = empresaSeleccionInicial;

  // Obtener precio vigente
  $.ajax({
    url: ENDPOINT_GET,
    method: 'POST',
    dataType: 'json',
    data: { codigo: PARAM_PRECIOS }
  })
  .done(function (respParam) {
    if (respParam.statusCode === 200 && respParam.parametro) {
      precioSeleccionInicial = firstOfParamValue(respParam.parametro.valor);
    } else if (respParam.statusCode === 404) {
      precioSeleccionInicial = '';
    } else if (respParam.statusCode === 409) {
      error('Existen múltiples parámetros vigentes para PRECIOS. Corrige la inconsistencia.');
      return;
    } else if (respParam.statusCode && respParam.statusCode !== 200) {
      error(respParam.mensaje || 'No se pudo cargar el PRECIOS parametrizado.');
      return;
    }

    // Listar precios filtrados por empresa
    $.ajax({
      url: ENDPOINT_LISTAR_PRECIOS,
      method: 'POST',
      dataType: 'json',
      data: { emprcod: empresaParam } // <-- IMPORTANTE: filtra por empresa
    })
    .done(function (resp) {
      if (resp.statusCode === 200 && Array.isArray(resp.precios)) {
        // Estructura esperada: [{preid, prenom (o nombre)}, emprcod, emprnom]
        preciosCache = resp.precios;
        renderTablaPrecios(preciosCache, precioSeleccionInicial);
        $('#btn-guardar-precios').prop('disabled', true);
      } else {
        error(resp.mensaje || 'No se pudieron cargar los PRECIOS.');
      }
    })
    .fail(function () {
      error('Fallo al comunicarse con el servidor al listar PRECIOS.');
    });

  })
  .fail(function () {
    error('Fallo al obtener el parámetro PRECIOS.');
  });
}

function renderTablaPrecios(precios, selectedPre) {
  $tablaPreciosBody.empty();

  precios.forEach(p => {
    const tabpreid = String(p.tabpreid);
    const nombre = p.tabprenom || `Precio ${tabpreid}`;
    const row = $(`
      <tr data-pre="${escapeHtml(tabpreid)}">
        <td>${escapeHtml(String(p.emprnom || ''))}</td>
        <td class="text-monospace">${escapeHtml(tabpreid)}</td>
        <td>${escapeHtml(String(nombre))}</td>
        <td>
          <input type="radio" name="precio-sel" class="form-check-input precio-radio">
        </td>
      </tr>
    `);
    row.find('input.precio-radio').prop('checked', tabpreid === selectedPre);

    row.find('input.precio-radio').on('change', function () {
      const current = getPrecioSeleccionado();
      $('#btn-guardar-precios').prop('disabled', current === precioSeleccionInicial);
    });

    $tablaPreciosBody.append(row);
  });
}

function getPrecioSeleccionado() {
  let sel = '';
  $tablaPreciosBody.find('tr').each(function () {
    const checked = $(this).find('input.precio-radio').is(':checked');
    if (checked) {
      sel = String($(this).data('pre'));
      return false;
    }
  });
  return sel;
}

function guardarPrecioSeleccionado() {
  const sel = getPrecioSeleccionado();
  if (!sel) { warn('Debes seleccionar una lista de PRECIOS.'); return; }

  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: {
      codigo: PARAM_PRECIOS,
      descripcion: 'Lista de PRECIOS habilitada (preid único)',
      valor: sel
    }
  })
  .done(function (resp) {
    if (resp.statusCode === 200) {
      ok('PRECIOS guardado correctamente.');
      precioSeleccionInicial = sel;
      $('#btn-guardar-precios').prop('disabled', true);
    } else {
      error(resp.mensaje || 'No se pudo guardar PRECIOS.');
    }
  })
  .fail(function () {
    error('Fallo al comunicarse con el servidor al guardar PRECIOS.');
  });
}

// Notificaciones
function ok(msg)   { if (window.Swal) Swal.fire('Éxito',       msg, 'success'); else alert(msg); }
function info(msg) { if (window.Swal) Swal.fire('Información', msg, 'info');    else alert(msg); }
function warn(msg) { if (window.Swal) Swal.fire('Atención',    msg, 'warning'); else alert(msg); }
function error(msg){ if (window.Swal) Swal.fire('Error',       msg, 'error');   else alert(msg); }

function escapeHtml(s) {
  return String(s).replace(/[&<>"'`=\/]/g, function (c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c];
  });
}
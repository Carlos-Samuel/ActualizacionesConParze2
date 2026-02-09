let pollTimer = null;
let currentId = null;

function renderLogs(rows){
  const $body = $('#logsBody').empty();
  if(rows.length === 0){
    $body.append('<tr><td colspan="3" class="text-muted">Sin registros…</td></tr>');
    return;
  }
  rows.forEach((r, idx) => {
    const fecha = new Date(r.momento_de_registro.replace(' ', 'T'));
    $body.append(
      `<tr>
         <td>${idx + 1}</td>
         <td>${r.descripcion_paso}</td>
         <td class="mono">${isNaN(fecha) ? r.momento_de_registro : fecha.toLocaleString()}</td>
       </tr>`
    );
  });
}

function startPolling($status, $btn, bitacoraId) {
  if (pollTimer) clearInterval(pollTimer);

  pollTimer = setInterval(async () => {
    // Si cambió el id (p.ej. el usuario disparó otro proceso), detén este polling
    if (!bitacoraId || bitacoraId !== currentId) {
      clearInterval(pollTimer);
      return;
    }

    try {
      const res = await fetch(
        'controladores/getLogsBitacora.php?id_bitacora=' + encodeURIComponent(bitacoraId),
        { cache: 'no-store' }
      );
      const data = await res.json();

      if (data.ok) {
        renderLogs(data.rows);

        const done = data.rows.some(
          (r) => (r.descripcion_paso || '').toLowerCase() === 'termina'
        );

        if (done) {
          clearInterval(pollTimer);
          $status.text('Proceso finalizado.');
          $btn.prop('disabled', false);
          // Opcional: liberar el id activo
          // currentId = null;
        }
      }
    } catch (e) {
      console.error(e);
      // No reactivamos el botón aquí para evitar estados inconsistentes;
      // si quieres, puedes mostrar el error:
      // $status.text('Error al consultar el estado.');
    }
  }, 2000);
}

// === Click handler para FULL / DELTA ===
$(document).on('click', '.btn-enviar', async function () {
  const $btn    = $(this);
  const mode    = String($btn.data('mode') || '').toUpperCase(); // FULL | DELTA
  const $status = $btn.nextAll('.status').first();

  if (mode !== 'FULL' && mode !== 'DELTA') {
    console.error('Modo inválido:', mode);
    return;
  }

  $btn.prop('disabled', true);
  $status.text('Creando bitácora…');

  try {
    // 1) Crear registro inicial
    const res = await fetch('controladores/registroInicialBitacora.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ tipo_de_cargue: mode })
    });

    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Error desconocido');

    // Guardar id global y mostrarlo
    currentId = data.id_bitacora;
    $('#idBitacora').text(currentId);
    $status.text('Ejecutando…');

    // 2) Disparar el proceso largo (fire-and-forget)
    fetch('controladores/gestorGeneracionInforme.php', {
      method: 'POST',
      body: new URLSearchParams({
        id_bitacora: currentId,
        tipo_de_cargue: mode
      })
    });

    // 3) Iniciar polling para este botón/estado e id
    startPolling($status, $btn, currentId);

  } catch (e) {
    $status.text('Error: ' + e.message);
    $btn.prop('disabled', false);
  }
});
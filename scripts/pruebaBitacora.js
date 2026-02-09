// scripts/parametrizacion.js
// Requiere jQuery y opcionalmente SweetAlert2 (Swal)

const ENDPOINT_UPDATE  = 'controladores/actualizaBitacora.php';
const ENDPOINT_SAVE = 'controladores/registraBitacora.php';


// Definición de parámetros y reglas  SE QUITAN TODOS 
const PARAMS = [];

let id_bitacora_aux

setupBitacora();

//$(document).ready(function () {
//  PARAMS.forEach(setupBitacora);
    // setupBitacora();
//});

function setupBitacora(def) {
  
  //let initial = '';
   
  // Carga
  $.ajax({
    url: ENDPOINT_SAVE,
    method: 'POST',
    dataType: 'json',
    data: { 
            tipo_de_cargue: 'FULL' ,
            fecha_ejecucion: "2025-09-01",
            hora_ejecucion: "12:00:00",
            origen_del_proceso: 'Manual',
            cantidad_registros_enviados: 15,
            tamaño_del_archivo: '5M',
            resultado_del_envio: '',
            descripcion_error: '',
            parametros_usados: '',   
            satisfactorio: 0,
            ruta_archivo: 'via/pruebas.txt',
            archivo_borrado: 0 
          }
  })
  .done(function (resp) {
     
    if (resp.statusCode === 200 && resp.id_bitacora) {
      //const val = def.loadTransform(resp.parametro.valor);
      //$input.val(val);
      //initial = val;
      id_bitacora_aux = resp.id_bitacora;
      console.log("Registro exitoso de la bitacora. id_bitacora: "+id_bitacora_aux);
   

        // Actualización
                $.ajax({
                  url: ENDPOINT_UPDATE,
                  method: 'POST',
                  dataType: 'json',
                  data: { 
                          id_bitacora: id_bitacora_aux, 
                          tipo_actualizacion:'Archivo',
                          cantidad_registros_enviados: 25,
                          tamaño_del_archivo: '5M',
                          resultado_del_envio: 'Exitoso',
                          descripcion_error: '',
                          parametros_usados: '',   
                          satisfactorio: 1,
                          ruta_archivo: 'datafile/pruebas.csv',
                          archivo_borrado: 0 
                        }
              })
            .done(function (resp) {
                
                if (resp.statusCode === 200 && resp.mensaje) {
                  
                  console.log("Actualizacion exitoso de la bitacora. id_bitacora: "+id_bitacora_aux);
                
                  
                } else {
                  console.log(resp.mensaje || `No se pudo Actualizar la bitacora ID: ${id_bitacora_aux}.`);
                }
              })
              .fail(function () {
                console.log("Fallo al actualizar la bitacora.ID:"+id_bitacora_aux);
              });  

    } else if (resp.statusCode === 404) {
      // no vigente encontrado: queda vacío
      
      console.log(`Existen múltiples vigentes para ${def.tipo_de_cargue}. Corrige la inconsistencia.`);
    } else {
      console.log(resp.mensaje || `No se pudo cargar el parámetro ${def.tipo_de_cargue}.`);
    }
  })
  .fail(function () {
    console.log(`Fallo al registrar la bitacora.`);
  });

  
}
// Notificaciones
function ok(msg)   { if (window.Swal) Swal.fire('Éxito',       msg, 'success'); else alert(msg); }
function info(msg) { if (window.Swal) Swal.fire('Información', msg, 'info');    else alert(msg); }
function warn(msg) { if (window.Swal) Swal.fire('Atención',    msg, 'warning'); else alert(msg); }
function error(msg){ if (window.Swal) Swal.fire('Error',       msg, 'error');   else alert(msg); }

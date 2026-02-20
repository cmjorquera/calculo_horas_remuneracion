function descargarExcel() {
  window.location.href = "descarga/empleados_excel.php";
}


function descargarPDF(){
  alert("Descargando PDF...");
  // window.location.href = "exportar_pdf.php?id_empleado=...";
}

function copiarHoras(){
  const horas = document.getElementById("sumJornadaCro")?.textContent || "00:00";
  navigator.clipboard.writeText("Horas totales: " + horas);
  alert("Horas copiadas al portapapeles");
}



function confirmarSalir() {     
    Swal.fire({
        title: '¿Salir del sistema?',
        html: `
      <div style="color:#475569; font-size:14px; line-height:1.4;">
        Se cerrará tu sesión por seguridad.<br>
        ¿Deseas continuar?
      </div>
    `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: '#0B5E8A', // azul institucional
        cancelButtonColor: '#94A3B8', // gris elegante
        backdrop: 'rgba(15, 23, 42, .35)',
        customClass: {
            popup: 'swal2-seduc-popup',
            title: 'swal2-seduc-title',
            confirmButton: 'swal2-seduc-confirm',
            cancelButton: 'swal2-seduc-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../logout.php';
        }
    });
}
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
      <div>
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
        backdrop: 'rgba(15, 23, 42, .35)',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            cancelButton: 'btn-seduc btn-seduc-ghost'
        }
    }).then((r) => {
        if (r.isConfirmed) window.location.href = 'logout.php';
    });
}
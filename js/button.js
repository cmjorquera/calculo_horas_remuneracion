function descargarExcel() {
  const colegioFilter = document.getElementById("empColegioFilter");
  const visibleRows = Array.from(document.querySelectorAll("#empTable tbody tr:not(.emp-empty-row)"))
    .filter((tr) => tr.style.display !== "none");

  if (visibleRows.length === 0) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: "info",
        title: "Sin funcionarios para descargar",
        text: "El filtro actual no tiene funcionarios disponibles.",
        showCloseButton: true,
        customClass: {
          popup: "swal-seduc"
        }
      });
    } else {
      alert("Sin funcionarios para descargar.");
    }
    return;
  }

  const params = new URLSearchParams();
  if (colegioFilter?.value) {
    params.set("id_colegio", colegioFilter.value);
  }

  const query = params.toString();
  window.location.href = "descarga/empleados_excell.php" + (query ? `?${query}` : "");
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
        showCloseButton: true,
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

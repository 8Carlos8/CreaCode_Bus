document.addEventListener('DOMContentLoaded', cargarCorridas);

async function cargarCorridas() {
  const contenedor = document.getElementById('lista-corridas');
  contenedor.innerHTML = '<p>Cargando corridas...</p>';

  try {
    const respuesta = await fetch('http://localhost:8000/api/corridas'); // Cambia la URL si tu backend Laravel está en otro puerto o dominio
    const corridas = await respuesta.json();

    if (corridas.length === 0) {
      contenedor.innerHTML = '<p>No hay corridas programadas.</p>';
      return;
    }

    contenedor.innerHTML = '';

    corridas.forEach(corrida => {
      const item = document.createElement('div');
      item.style.padding = '10px';
      item.style.border = '1px solid #ccc';
      item.style.marginBottom = '10px';
      item.style.borderRadius = '5px';
      item.innerHTML = `
        <strong>${corrida.origen} → ${corrida.destino}</strong><br>
        Fecha: ${corrida.fecha} <br>
        Hora: ${corrida.hora} <br>
        Autobús: ${corrida.autobus}
      `;
      contenedor.appendChild(item);
    });
  } catch (error) {
    contenedor.innerHTML = '<p>Error al cargar las corridas.</p>';
    console.error(error);
  }
}

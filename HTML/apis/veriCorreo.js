const apiUrl = "http://127.0.0.1:8000"; // URL base de la API

document.querySelector("form").addEventListener("submit", function (event) {
    event.preventDefault();

    const codigoVerificacion = document.getElementById("codigo_verificacion").value.trim(); // Captura el código de verificación
    const idU = localStorage.getItem("idU"); // Recupera el ID del usuario almacenado en localStorage

    if (!codigoVerificacion || !idU) {
        alert("Por favor, completa todos los campos.");
        return;
    }

    verificarCorreo(idU, codigoVerificacion); // Pasamos el ID de usuario y el código de verificación
});

function verificarCorreo(idU, codigoVerificacion) { // Acepta ambos parámetros
    fetch(apiUrl + "/api/verificarCorreo", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            id: idU, // Utiliza el parámetro idU
            codigo_verificacion: codigoVerificacion, // Utiliza el parámetro codigoVerificacion
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            alert(data.message); // Muestra el mensaje recibido
        })
        .catch((error) => {
            console.error("Error al realizar la solicitud:", error);
            alert("Ocurrió un error inesperado. Intenta nuevamente más tarde.");
        });
}

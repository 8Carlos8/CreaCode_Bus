const apiUrl = "http://127.0.0.1:8000"; // URL base de la API
// Evento de envío del formulario
document.querySelector("form").addEventListener("submit", function (event) {
    event.preventDefault();

    // Capturar datos del formulario
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    if (!email || !password) {
        alert("Por favor, completa todos los campos.");
        return;
    }

    // Llamar a la función de inicio de sesión
    login(email, password);
});

function login(email, password) {
    // Solicitud al endpoint de inicio de sesión
    fetch(apiUrl + "/api/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, password }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.token) {
                localStorage.setItem("token", data.token);
                const rol = parseInt(data.rol);
                localStorage.setItem("idU", data.id);
                if (rol === 2) {
                    window.location.href = "vistas/admin/index1.html";
                } else if (rol === 3) {
                    window.location.href = "vistas/admin/indexO.html";
                } else {
                    window.location.href = "../usuario/inicio.html";
                }
            } else {
                console.error("Error al iniciar sesión:", data.message);
                alert("Error: " + data.message);
            }
        })
        .catch((error) => {
            console.error("Error al realizar la solicitud:", error);
            alert("Ocurrió un error inesperado. Intenta nuevamente más tarde.");
        });
}
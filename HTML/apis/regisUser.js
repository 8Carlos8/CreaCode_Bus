document.addEventListener("DOMContentLoaded", () => {
    // Cambiar el selector para que apunte al formulario con id "corridaForm"
    const form = document.getElementById("corridaForm");

    form.addEventListener("submit", async (event) => {
        event.preventDefault(); // Evitar recarga del formulario

        // Obtener los valores del formulario
        const name = document.getElementById("nombre").value;
        const apellido_p = document.getElementById("apellido-paterno").value;
        const apellido_m = document.getElementById("apellido-materno").value;
        const telefono = document.getElementById("telefono").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const rol = document.getElementById("rol").value;

        // Validar que no estén vacíos
        if (!name || !apellido_p || !apellido_m || !telefono || !email || !password) {
            alert("Por favor, completa todos los campos.");
            return;
        }

        try {
            // Configurar los datos en formato URL encoded
            const data = new URLSearchParams();
            data.append("name", name);
            data.append("apellido_p", apellido_p);
            data.append("apellido_m", apellido_m);
            data.append("telefono", telefono);
            data.append("email", email);
            data.append("password", password);
            data.append("rol", rol);

            // Hacer la solicitud fetch
            const response = await fetch("http://127.0.0.1:8000/api/registerU", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: data.toString(),
            });

            if (response.ok) {
                const result = await response.json();
                alert(result.message);
                form.reset(); // Limpiar el formulario
            } else {
                const errorData = await response.json();
                alert("Error: " + JSON.stringify(errorData));
            }
        } catch (error) {
            console.error("Error en el registro:", error);
            alert("Hubo un error al registrar el usuario. Intenta de nuevo.");
        }
    });
});
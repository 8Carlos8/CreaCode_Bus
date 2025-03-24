document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".form-container form");
    const passwordInput = document.getElementById("password");
    const passwordErrorList = document.getElementById("password-error-list");

    // Función para mostrar errores en formato de lista
    const showErrors = (errors) => {
        // Limpiar la lista de errores
        passwordErrorList.innerHTML = "";

        // Agregar cada error como un elemento de lista
        errors.forEach(error => {
            const li = document.createElement("li");
            li.textContent = error;
            passwordErrorList.appendChild(li);
        });
    };

    // Validación en tiempo real de la contraseña
    passwordInput.addEventListener("input", () => {
        const password = passwordInput.value;
        const errors = [];

        // Validar longitud
        if (password.length < 12) {
            errors.push("La contraseña debe tener al menos 12 caracteres.");
        }

        // Validar números consecutivos
        if (/(\d)\1/.test(password)) {
            errors.push("La contraseña no debe contener números consecutivos.");
        }

        // Validar caracteres especiales
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            errors.push("La contraseña debe contener al menos un carácter especial.");
        }

        // Validar mayúsculas
        if (!/[A-Z]/.test(password)) {
            errors.push("La contraseña debe contener al menos una letra mayúscula.");
        }

        // Mostrar errores o limpiar mensajes
        if (errors.length > 0) {
            showErrors(errors);
        } else {
            passwordErrorList.innerHTML = ""; // Limpiar la lista si no hay errores
        }
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault(); // Evitar recarga del formulario

        // Obtener los valores del formulario
        const name = document.getElementById("nombre").value;
        const apellido_p = document.getElementById("apellido-paterno").value;
        const apellido_m = document.getElementById("apellido-materno").value;
        const telefono = document.getElementById("telefono").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        // Validar que no estén vacíos
        if (!name || !apellido_p || !apellido_m || !telefono || !email || !password) {
            alert("Por favor, completa todos los campos.");
            return;
        }

        // Validar la contraseña antes de enviar el formulario
        if (passwordErrorList.children.length > 0) {
            alert("Por favor, corrige los errores en la contraseña antes de continuar.");
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

            // Hacer la solicitud fetch
            const response = await fetch("http://127.0.0.1:8000/api/register", {
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
                window.location.href = "../sesion/login.html";
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

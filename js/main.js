document.getElementById('check-btn').addEventListener('click', () => {
    const userId = document.getElementById('userId').value;
    const checkButton = document.getElementById('check-btn');

    // Validar si el campo está vacío o contiene caracteres no válidos
    if (!userId || isNaN(userId)) {
        alert("Por favor, ingrese un ID válido.");
        return;
    }

    // Deshabilitar el botón mientras se procesa la solicitud
    checkButton.disabled = true;

    fetch('../php/check.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId })
    })
        .then(response => response.text())
        .then(data => {
            // Mostrar el mensaje retornado por el servidor en un elemento
            displayMessage(data);
        })
        .catch(error => {
            console.error('Error:', error);
            displayMessage('Ocurrió un error al procesar la solicitud.');
        })
        .finally(() => {
            // Volver a habilitar el botón
            checkButton.disabled = false;
        });
});

// Función para mostrar el mensaje en la página
function displayMessage(message) {
    let messageElement = document.getElementById('message');
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.id = 'message';
        messageElement.style.marginTop = '10px';
        messageElement.style.padding = '10px';
        messageElement.style.borderRadius = '5px';
        messageElement.style.backgroundColor = '#f0f0f0';
        messageElement.style.color = '#333';
        document.querySelector('.container').appendChild(messageElement);
    }
    messageElement.textContent = message;
}

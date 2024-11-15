function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
}

setInterval(updateClock, 1000);

// Función para cargar la lista de entradas
function loadEntradas() {
    fetch('../php/get_entradas.php')
        .then(response => response.json())
        .then(data => {
            const entradaList = document.getElementById('entrada-list');
            entradaList.innerHTML = '';
            data.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `ID: ${item.user_id} - Entrada: ${item.hora_entrada}`;
                entradaList.appendChild(li);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Función para cargar la lista de salidas
function loadSalidas() {
    fetch('../php/get_salidas.php')
        .then(response => response.json())
        .then(data => {
            const salidaList = document.getElementById('salida-list');
            salidaList.innerHTML = '';
            data.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `ID: ${item.user_id} - Salida: ${item.hora_salida}`;
                salidaList.appendChild(li);
            });
        })
        .catch(error => console.error('Error:', error));
}

document.getElementById('check-in-btn').addEventListener('click', () => {
    const userId = document.getElementById('userId').value;
    if (userId === "") {
        alert("Por favor, ingrese su ID");
        return;
    }

    fetch('../php/check_in.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId })
    })
        .then(response => response.text())
        .then(data => {
            alert(data);
            loadEntradas();  // Actualizar la lista de entradas después de registrar la entrada.
        })
        .catch(error => console.error('Error:', error));
});

document.getElementById('check-out-btn').addEventListener('click', () => {
    const userId = document.getElementById('userId').value;
    if (userId === "") {
        alert("Por favor, ingrese su ID");
        return;
    }

    fetch('../php/check_out.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId })
    })
        .then(response => response.text())
        .then(data => {
            alert(data);
            loadSalidas();  // Actualizar la lista de salidas después de registrar la salida.
        })
        .catch(error => console.error('Error:', error));
});

// Cargar las listas al cargar la página
loadEntradas();
loadSalidas();

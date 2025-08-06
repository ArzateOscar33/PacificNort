const modalPuesto = new bootstrap.Modal(document.getElementById('modalRegistrarPuesto'));
const formPuesto = document.querySelector('#formPuesto');
const nombrePuesto = document.querySelector('#nombrePuesto');
const tabla = document.querySelector('#tablaPuestos tbody');
let idEditar = null;
// Cargar lista al cargar
window.addEventListener('DOMContentLoaded', listarPuestos);
// Evento para abrir modal
document.querySelector('#btnAgregarPuesto').addEventListener('click', () => {
    idEditar = null;
    formPuesto.reset();
    modalPuesto.show(); 
}); 


formPuesto.addEventListener('submit', function (e) {
    e.preventDefault();

    const id = document.getElementById('idPuesto').value;
    const nombre = document.getElementById('nombrePuesto').value.trim();

    if (nombre === '') {
        Swal.fire('Campo requerido', 'El nombre es obligatorio', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombrePuesto', nombre);

    const url = base_url + (id === '' ? 'Puestos/registrar' : 'Puestos/actualizar');

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        //imprimimos el estado de la respuesta
        const texto = await res.text(); 
        console.log("Respuesta cruda del servidor:", texto);  

        try {
            const data = JSON.parse(texto);

            Swal.fire({
                icon: data.status ? 'success' : 'error',
                title: data.msg
            });

            if (data.status) {
                // Restablecer estado visual
                formPuesto.reset();
                document.getElementById('idPuesto').value = '';
                document.activeElement.blur();
                modalPuesto.hide();
                listarPuestos();

                // Restaurar etiquetas del modal
                document.getElementById('modalRegistrarPuestoLabel').textContent = 'Agregar Puesto';
                document.getElementById('btnSubmit').textContent = 'Agregar';
            }

        } catch (error) {
            console.error(" Error al parsear JSON:\n", texto);
            Swal.fire("Error", "La respuesta del servidor no es válida", "error");
        }
    })
    .catch(err => {
        console.error(' Error en la solicitud:', err);
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    });
});
// Funciones para manejar CRUD de puestos
function listarPuestos() {
    fetch(base_url + 'puestos/listar')
        .then(res => res.json())
        .then(data => {
                        console.log("Respuesta del servidor:", data); // 👈 Aquí lo ves en consola
            tabla.innerHTML = '';
            data.forEach(dep => {
                const tr = document.createElement('tr');
                tr.classList.add('text-center');
                tr.innerHTML = `
                    <td >${dep.nombre}</td> 
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarPuesto(${dep.id_puesto})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarPuesto(${dep.id_puesto})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
                tabla.appendChild(tr);
            });
        });
}

function editarPuesto(id) {
    fetch(base_url + 'Puestos/editar/' + id)
        .then(res => res.json())
        .then(data => {
            // Asignar datos al formulario
            document.getElementById('idPuesto').value = data.id_puesto;
            document.getElementById('nombrePuesto').value = data.nombre;

            // Cambiar título del modal y botón
            document.getElementById('modalRegistrarPuestoLabel').textContent = 'Editar Puesto';
            document.getElementById('btnSubmit').textContent = 'Actualizar';

            // Mostrar 
            document.activeElement.blur();
            modalPuesto.show();
        })
        .catch(err => {
            console.error(" Error al obtener datos del puesto:", err);
            Swal.fire("Error", "No se pudo cargar el puesto", "error");
        });
}

function eliminarPuesto(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(base_url + 'Puestos/eliminar/' + id)
                .then(async res => {
                    const texto = await res.text(); // texto crudo de respuesta
                    console.log(" Respuesta cruda al eliminar:", texto);

                    try {
                        const data = JSON.parse(texto);

                        Swal.fire({
                            icon: data.status ? 'success' : 'error',
                            title: data.msg
                        });

                        if (data.status) {
                            listarPuestos(); // recarga tabla
                        }
                    } catch (err) {
                        console.error(" Error al parsear JSON:\n", texto);
                        Swal.fire('Error', 'La respuesta del servidor no es válida', 'error');
                    }
                })
                .catch(err => {
                    console.error(' Error en la solicitud:', err);
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                });
        }
    });
}


document.getElementById('buscarPuesto').addEventListener('keyup', function () {
    const termino = this.value.trim();

    // Si está vacío, cargar todo
    if (termino === '') {
        listarPuestos();
        return;
    }

    fetch(base_url + 'Puestos/buscar?term=' + encodeURIComponent(termino))
        .then(res => res.json())
        .then(data => {
            tabla.innerHTML = ''; // Limpiar tabla
            data.forEach(dep => {
                const tr = document.createElement('tr');
                tr.classList.add('text-center');
                tr.innerHTML = `
                    <td>${dep.nombre}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarPuesto(${dep.id_puesto})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarPuesto(${dep.id_puesto})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
                tabla.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error en búsqueda:', err);
        });
});


const inputBuscar = document.getElementById('buscarPuesto');
const sugerenciasPuestos = document.getElementById('sugerenciasPuestos');

inputBuscar.addEventListener('keyup', function () {
    const termino = this.value.trim();

    // Ocultar si está vacío
    if (termino === '') {
        sugerenciasPuestos.innerHTML = '';
        sugerenciasPuestos.style.display = 'none';
        return;
    }

    fetch(base_url + 'Puestos/buscar?term=' + encodeURIComponent(termino))
        .then(res => res.json())
        .then(data => {
            console.log("Respuesta de búsqueda:", data);
            sugerenciasPuestos.innerHTML = '';
            if (data.length === 0) {
                sugerenciasPuestos.style.display = 'none';
                return;
            }

            data.forEach(dep => {
                const item = document.createElement('button');
                item.classList.add('list-group-item', 'list-group-item-action');
                item.textContent = dep.nombre;
                item.type = 'button';
                item.onclick = () => {
                    inputBuscar.value = dep.nombre;
                    sugerenciasPuestos.innerHTML = '';
                    sugerenciasPuestos.style.display = 'none';
                    
                    // Opcional: cargar tabla con ese resultado directamente
                    fetch(base_url + 'Puestos/buscar?term=' + encodeURIComponent(dep.nombre))
                        .then(res => res.json())
                        .then(depData => {
                            tabla.innerHTML = '';
                            depData.forEach(dep => {
                                const tr = document.createElement('tr');
                                tr.classList.add('text-center');
                                tr.innerHTML = `
                                    <td>${dep.nombre}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editarPuesto(${dep.id_puesto})"><i class="fas fa-edit"></i> Editar</button>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarPuesto(${dep.id_puesto})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </td>
                                `;
                                tabla.appendChild(tr);
                            });
                        });
                };
                sugerenciasPuestos.appendChild(item);
            });

            sugerenciasPuestos.style.display = 'block';
        })
        .catch(err => {
            console.error('Error al buscar sugerenciasPuestos:', err);
        });
});

// Ocultar sugerenciasPuestos si haces clic fuera
document.addEventListener('click', function (e) {
    if (!inputBuscar.contains(e.target) && !sugerenciasPuestos.contains(e.target)) {
        sugerenciasPuestos.innerHTML = '';
        sugerenciasPuestos.style.display = 'none';
    }
});

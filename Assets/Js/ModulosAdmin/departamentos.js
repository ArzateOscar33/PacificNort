// public/js/departamentos.js
 
const modalDepartamento = new bootstrap.Modal(document.getElementById('staticBackdrop'));
const formDepartamento = document.querySelector('#formDepartamento');
const nombreDepartamento = document.querySelector('#nombreDepartamento');
const tabla = document.querySelector('#tablaDepartamentos tbody');
let idEditar = null;

 
// Cargar lista al cargar
window.addEventListener('DOMContentLoaded', listarDepartamentos);



formDepartamento.addEventListener('submit', function (e) {
    e.preventDefault();

    const id = document.getElementById('idDepartamento').value;
    const nombre = document.getElementById('nombreDepartamento').value.trim();

    if (nombre === '') {
        Swal.fire('Campo requerido', 'El nombre es obligatorio', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombreDepartamento', nombre);

    const url = base_url + (id === '' ? 'Departamentos/registrar' : 'Departamentos/actualizar');

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
                formDepartamento.reset();
                document.getElementById('idDepartamento').value = '';
                document.activeElement.blur();
                modalDepartamento.hide();
                listarDepartamentos();

                // Restaurar etiquetas del modal
                document.getElementById('staticBackdropLabel').textContent = 'Agregar Departamento';
                const btnSubmit = document.getElementById("btnSubmit");

                // Establecer el HTML del botón con ícono y texto
                btnSubmit.innerHTML =
                    '<i data-feather="check-circle" class="me-1"></i> Agregar';
                feather.replace();
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




// Funciones para manejar CRUD de departamentos
function listarDepartamentos() {
    fetch(base_url + 'Departamentos/listar')
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
                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
                tabla.appendChild(tr);
            });
        });
}

function editarDepartamento(id) {
    fetch(base_url + 'Departamentos/editar/' + id)
        .then(res => res.json())
        .then(data => {
            // Asignar datos al formulario
            document.getElementById('idDepartamento').value = data.id_departamento;
            document.getElementById('nombreDepartamento').value = data.nombre;

            // Cambiar título del modal y botón
            document.getElementById('staticBackdropLabel').textContent = 'Editar Departamento';
           const btnSubmit = document.getElementById("btnSubmit");

            // Establecer el HTML del botón con ícono y texto
            btnSubmit.innerHTML =
                '<i data-feather="check-circle" class="me-1"></i> Actualizar';
            feather.replace();

            // Mostrar 
            document.activeElement.blur();
            modalDepartamento.show();
        })
        .catch(err => {
            console.error(" Error al obtener datos del departamento:", err);
            Swal.fire("Error", "No se pudo cargar el departamento", "error");
        });
}

function eliminarDepartamento(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(base_url + 'Departamentos/eliminar/' + id)
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
                            listarDepartamentos(); // recarga tabla
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
document.getElementById('buscarDepartamento').addEventListener('keyup', function () {
    const termino = this.value.trim();

    // Si está vacío, cargar todo
    if (termino === '') {
        listarDepartamentos();
        return;
    }

    fetch(base_url + 'Departamentos/buscar?term=' + encodeURIComponent(termino))
        .then(res => res.json())
        .then(data => {
            tabla.innerHTML = ''; // Limpiar tabla
            data.forEach(dep => {
                const tr = document.createElement('tr');
                tr.classList.add('text-center');
                tr.innerHTML = `
                    <td>${dep.nombre}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                    </td>
                `;
                tabla.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error en búsqueda:', err);
        });
});
const inputBuscar = document.getElementById('buscarDepartamento');
const sugerencias = document.getElementById('sugerencias');

inputBuscar.addEventListener('keyup', function () {
    const termino = this.value.trim();

    // Ocultar si está vacío
    if (termino === '') {
        sugerencias.innerHTML = '';
        sugerencias.style.display = 'none';
        return;
    }

    fetch(base_url + 'Departamentos/buscar?term=' + encodeURIComponent(termino))
        .then(res => res.json())
        .then(data => {
            sugerencias.innerHTML = '';
            if (data.length === 0) {
                sugerencias.style.display = 'none';
                return;
            }

            data.forEach(dep => {
                const item = document.createElement('button');
                item.classList.add('list-group-item', 'list-group-item-action');
                item.textContent = dep.nombre;
                item.type = 'button';
                item.onclick = () => {
                    inputBuscar.value = dep.nombre;
                    sugerencias.innerHTML = '';
                    sugerencias.style.display = 'none';
                    
                    // Opcional: cargar tabla con ese resultado directamente
                    fetch(base_url + 'Departamentos/buscar?term=' + encodeURIComponent(dep.nombre))
                        .then(res => res.json())
                        .then(depData => {
                            tabla.innerHTML = '';
                            depData.forEach(dep => {
                                const tr = document.createElement('tr');
                                tr.classList.add('text-center');
                                tr.innerHTML = `
                                    <td>${dep.nombre}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editarDepartamento(${dep.id_departamento})"><i class="fas fa-edit"></i> Editar</button>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${dep.id_departamento})"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </td>
                                `;
                                tabla.appendChild(tr);
                            });
                        });
                };
                sugerencias.appendChild(item);
            });

            sugerencias.style.display = 'block';
        })
        .catch(err => {
            console.error('Error al buscar sugerencias:', err);
        });
});

// Ocultar sugerencias si haces clic fuera
document.addEventListener('click', function (e) {
    if (!inputBuscar.contains(e.target) && !sugerencias.contains(e.target)) {
        sugerencias.innerHTML = '';
        sugerencias.style.display = 'none';
    }
});


 
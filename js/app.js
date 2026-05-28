// ============================================
// app.js - PawSpa Sistema Integral
// ============================================

const API_URL = '/pawspa/api/';

// Variables globales
let IS_ADMIN = false;
let IS_CLIENT = false;
let IS_RECEP = false;
let IS_GROOMER = false;
let currentUser = null;
let mascotaActual = null;
let mascotasCliente = [];

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.className = 'show ' + type;
    setTimeout(() => toast.className = '', 3000);
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('hidden');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('hidden');
}

// ============================================
// LOGIN / LOGOUT
// ============================================

function mostrarError(msg) {
    const div = document.getElementById('loginError');
    if (!div) return;
    div.innerHTML = msg;
    div.style.display = 'block';
    setTimeout(() => div.style.display = 'none', 5000);
}

function mostrarMensaje(msg, tipo) {
    const div = document.getElementById('loginError');
    if (!div) return;
    div.innerHTML = msg;
    div.style.display = 'block';
    div.style.background = tipo === 'success' ? '#E8F8F0' : '#FFEDED';
    div.style.color = tipo === 'success' ? '#2EA87A' : '#C4532A';
    setTimeout(() => div.style.display = 'none', 3000);
}

async function doLogin() {
    console.log('🔵 Iniciando login...');
    
    const email = document.getElementById('loginUser').value;
    const password = document.getElementById('loginPass').value;
    const role = document.getElementById('loginRole').value;
    const captchaToken = grecaptcha ? grecaptcha.getResponse() : '';
    
    const errorDiv = document.getElementById('loginError');
    if (errorDiv) errorDiv.style.display = 'none';
    
    if (!email || !password) {
        mostrarError('Por favor ingresa email y contraseña');
        return;
    }
    
    if (!captchaToken) {
        mostrarError('Por favor completa el reCAPTCHA');
        return;
    }
    
    const btn = document.querySelector('.btn-primary');
    const originalText = btn ? btn.textContent : 'Ingresar';
    if (btn) {
        btn.textContent = '⏳ Verificando...';
        btn.disabled = true;
    }
    
    try {
        const response = await fetch(API_URL + 'login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, role, captcha_token: captchaToken })
        });
        
        const data = await response.json();
        console.log('📦 Respuesta login:', data);
        
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            if (data.token) {
                localStorage.setItem('jwt_token', data.token);
            }
            mostrarMensaje(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            mostrarError(data.error || 'Error de autenticación');
            if (grecaptcha) grecaptcha.reset();
        }
    } catch (error) {
        console.error('❌ Error:', error);
        mostrarError('Error de conexión. ¿XAMPP está corriendo?');
        if (grecaptcha) grecaptcha.reset();
    } finally {
        if (btn) {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }
}

function quickLogin(role) {
    const creds = {
        admin: { email: 'admin@pawspa.com', pass: 'admin123' },
        recep: { email: 'recep@pawspa.com', pass: 'recep123' },
        groo: { email: 'maria@pawspa.com', pass: 'groomer123' },
        client: { email: 'ana@email.com', pass: 'cliente123' }
    };
    const userInput = document.getElementById('loginUser');
    const passInput = document.getElementById('loginPass');
    const roleSelect = document.getElementById('loginRole');
    if (userInput) userInput.value = creds[role].email;
    if (passInput) passInput.value = creds[role].pass;
    if (roleSelect) roleSelect.value = role;
    doLogin();
}

function doLogout() {
    localStorage.removeItem('user');
    localStorage.removeItem('jwt_token');
    
    fetch(API_URL + 'logout.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).finally(() => {
        window.location.href = 'index.php';
    });
}

// ============================================
// TOKEN MANAGEMENT
// ============================================

function obtenerToken() {
    return localStorage.getItem('jwt_token');
}

async function fetchAuth(url, options = {}) {
    const token = obtenerToken();
    
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await fetch(url, {
        ...options,
        headers: headers
    });
    
    if (response.status === 401) {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user');
        window.location.href = 'index.php';
        return null;
    }
    
    return response;
}

// ============================================
// MASCOTAS DEL CLIENTE
// ============================================

async function cargarMascotasCliente(clienteId) {
    const container = document.getElementById('mascotasClienteContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading">Cargando mascotas...</div>';
    
    try {
        const response = await fetch(`${API_URL}clientes.php?action=mascotas_cliente&cliente_id=${clienteId}`);
        const data = await response.json();
        
        if (data.success && data.mascotas && data.mascotas.length > 0) {
            mascotasCliente = data.mascotas;
            
            let html = '';
            data.mascotas.forEach(m => {
                const especieIcon = m.especie === 'perro' ? '🐕' : (m.especie === 'gato' ? '🐈' : '🐾');
                html += `
                    <div onclick="seleccionarMascota(${m.id})" style="
                        padding: 12px; 
                        margin-bottom: 8px; 
                        background: white; 
                        border: 1px solid #e0e0e0; 
                        border-radius: 10px; 
                        cursor: pointer;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div>
                            <div style="font-weight: bold; font-size: 1rem;">${escapeHtml(m.nombre)}</div>
                            <div style="font-size: 0.75rem; color: #666;">${escapeHtml(m.raza || 'Sin raza')} · ${m.edad || '?'} años</div>
                        </div>
                        <div style="font-size: 1.8rem;">${especieIcon}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
            if (data.mascotas[0]) {
                seleccionarMascota(data.mascotas[0].id);
            }
        } else {
            container.innerHTML = `
                <div style="text-align:center; padding:30px; color:#666;">
                    🐾 No tienes mascotas registradas.<br>
                    <button class="btn btn-teal btn-sm" style="margin-top:15px;" onclick="abrirModalMascotaCliente()">➕ Agregar tu primera mascota</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando mascotas:', error);
        container.innerHTML = '<div class="error">Error de conexión al cargar mascotas</div>';
    }
}

function seleccionarMascota(mascotaId) {
    const mascota = mascotasCliente?.find(m => parseInt(m.id) === parseInt(mascotaId));
    
    if (!mascota) return;
    
    mascotaActual = mascotaId;
    
    const tituloMascota = document.getElementById('mascotaSeleccionadaTituloCliente');
    if (tituloMascota) {
        const especieIcon = mascota.especie === 'perro' ? '🐕' : '🐈';
        tituloMascota.innerHTML = `${especieIcon} ${escapeHtml(mascota.nombre)} - ${mascota.raza || 'Sin raza'}`;
    }
    
    if (typeof cargarCartillaVacunas === 'function') {
        cargarCartillaVacunas(mascotaId);
    }
    
    showToast(`🐾 Mascota seleccionada: ${mascota.nombre}`, 'success');
}

async function guardarMascotaCliente() {
    const usuarioId = currentUser?.id || JSON.parse(localStorage.getItem('user') || '{}')?.id;
    
    if (!usuarioId) {
        showToast('Debes iniciar sesión para agregar una mascota', 'error');
        return;
    }
    
    const nombre = document.getElementById('mascotaClienteNombre')?.value.trim();
    const especie = document.getElementById('mascotaClienteEspecie')?.value || 'perro';
    const raza = document.getElementById('mascotaClienteRaza')?.value.trim() || '';
    const edad = parseInt(document.getElementById('mascotaClienteEdad')?.value) || 0;
    const peso = parseFloat(document.getElementById('mascotaClientePeso')?.value) || 0;
    const alergias = document.getElementById('mascotaClienteAlergias')?.value || '';
    
    if (!nombre) {
        showToast('El nombre de la mascota es requerido', 'error');
        return;
    }
    
    try {
        const response = await fetch(API_URL + 'clientes.php?action=mascota_cliente', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cliente_id: usuarioId,
                nombre: nombre,
                especie: especie,
                raza: raza,
                edad: edad,
                peso: peso,
                alergias: alergias,
                tamanio: 'mediano',
                temperamento: 'tranquilo'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Mascota agregada', 'success');
            closeModal('modalMascotaCliente');
            await cargarMascotasCliente(usuarioId);
            
            document.getElementById('mascotaClienteNombre').value = '';
            document.getElementById('mascotaClienteRaza').value = '';
            document.getElementById('mascotaClienteEdad').value = '';
            document.getElementById('mascotaClientePeso').value = '';
            document.getElementById('mascotaClienteAlergias').value = '';
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function abrirModalMascotaCliente() {
    const modalTitle = document.getElementById('modalMascotaClienteTitle');
    if (modalTitle) modalTitle.textContent = '➕ Nueva Mascota';
    
    const inputs = ['mascotaClienteId', 'mascotaClienteNombre', 'mascotaClienteRaza', 'mascotaClienteEdad', 'mascotaClientePeso', 'mascotaClienteAlergias'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    
    const especieSelect = document.getElementById('mascotaClienteEspecie');
    if (especieSelect) especieSelect.value = 'perro';
    
    openModal('modalMascotaCliente');
}

// ============================================
// CITAS - FUNCIONES PARA EL MODAL
// ============================================

async function abrirModalNuevaCita(fecha, hora) {
    console.log('🔵 ABRIENDO MODAL NUEVA CITA');
    console.log('IS_CLIENT:', IS_CLIENT);
    console.log('Usuario actual:', currentUser);
    
    const fechaInput = document.getElementById('citaFecha');
    const horaInput = document.getElementById('citaHora');
    const obsInput = document.getElementById('citaObs');
    
    if (fechaInput && fecha) fechaInput.value = fecha;
    if (horaInput && hora) horaInput.value = hora;
    if (obsInput) obsInput.value = '';
    
    // CASO 1: ES CLIENTE
    if (IS_CLIENT && currentUser && currentUser.id) {
        console.log('👤 Modo Cliente - ID:', currentUser.id);
        
        // Crear campo oculto con el ID del cliente
        let hiddenInput = document.getElementById('hiddenClienteId');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'hiddenClienteId';
            hiddenInput.value = currentUser.id;
            const selectCliente = document.getElementById('citaClienteSelect');
            if (selectCliente && selectCliente.parentNode) {
                selectCliente.parentNode.appendChild(hiddenInput);
            }
        } else {
            hiddenInput.value = currentUser.id;
        }
        
        // Ocultar el select de cliente
        const selectCliente = document.getElementById('citaClienteSelect');
        if (selectCliente) {
            selectCliente.style.display = 'none';
            const label = selectCliente.closest('.input-group')?.querySelector('label');
            if (label) label.style.display = 'none';
            const row = selectCliente.closest('.form-row');
            if (row) row.style.display = 'none';
        }
        
        // Cargar mascotas del cliente actual
        await cargarMascotasPorCliente(currentUser.id);
        
    } 
    // CASO 2: ADMIN O RECEPCIONISTA
    else {
        console.log('👤 Modo Admin/Recepcionista');
        
        const selectCliente = document.getElementById('citaClienteSelect');
        if (selectCliente) {
            selectCliente.style.display = '';
            await cargarClientesEnSelect();
        }
        
        const selectMascota = document.getElementById('citaMascotaSelect');
        if (selectMascota) {
            selectMascota.innerHTML = '<option value="">🔍 Primero seleccione un cliente</option>';
            selectMascota.disabled = true;
        }
    }
    
    // Cargar servicios y groomers
    await cargarServiciosEnSelect();
    await cargarGroomersEnSelect();
    
    openModal('modalNuevaCita');
}

async function cargarClientesEnSelect() {
    // Si es cliente, NO hacer nada
    if (IS_CLIENT) {
        console.log('Cliente no puede cargar lista de clientes');
        return;
    }
    
    const selectCliente = document.getElementById('citaClienteSelect');
    if (!selectCliente) return;
    
    try {
        const response = await fetch(API_URL + 'clientes.php');
        const data = await response.json();
        
        if (data.success && data.clientes) {
            selectCliente.innerHTML = '<option value="">🔍 Seleccione un cliente...</option>';
            data.clientes.forEach(cliente => {
                selectCliente.innerHTML += `<option value="${cliente.id}">🐾 ${escapeHtml(cliente.nombre)} - 📱 ${cliente.telefono || 'sin teléfono'}</option>`;
            });
            
            selectCliente.onchange = function() {
                const clienteId = this.value;
                if (clienteId) {
                    cargarMascotasPorCliente(clienteId);
                }
            };
        } else {
            selectCliente.innerHTML = '<option value="">❌ Error cargando clientes</option>';
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
        selectCliente.innerHTML = '<option value="">❌ Error de conexión</option>';
    }
}

async function cargarMascotasPorCliente(clienteId) {
    const selectMascota = document.getElementById('citaMascotaSelect');
    if (!selectMascota) return;
    
    console.log('🔍 Cargando mascotas para cliente:', clienteId);
    
    selectMascota.innerHTML = '<option value="">⏳ Cargando mascotas...</option>';
    selectMascota.disabled = false;
    
    try {
        const response = await fetch(`${API_URL}clientes.php?action=mascotas_cliente&cliente_id=${clienteId}`);
        const data = await response.json();
        
        console.log('📦 Respuesta mascotas:', data);
        
        if (data.success && data.mascotas && data.mascotas.length > 0) {
            selectMascota.innerHTML = '<option value="">🐕 Seleccione una mascota...</option>';
            data.mascotas.forEach(mascota => {
                const especieIcon = mascota.especie === 'perro' ? '🐕' : (mascota.especie === 'gato' ? '🐈' : '🐾');
                selectMascota.innerHTML += `<option value="${mascota.id}">${especieIcon} ${escapeHtml(mascota.nombre)} - ${escapeHtml(mascota.raza || 'Sin raza')} (${mascota.edad || '?'} años)</option>`;
            });
            selectMascota.disabled = false;
        } else {
            selectMascota.innerHTML = '<option value="">⚠️ No tiene mascotas registradas</option>';
            selectMascota.disabled = false;
        }
    } catch (error) {
        console.error('❌ Error cargando mascotas:', error);
        selectMascota.innerHTML = '<option value="">❌ Error cargando mascotas</option>';
        selectMascota.disabled = false;
    }
}

async function cargarServiciosEnSelect() {
    const selectServicio = document.getElementById('citaServicio');
    if (!selectServicio) return;
    
    try {
        const response = await fetch(API_URL + 'get_all_data.php');
        const data = await response.json();
        
        if (data.success && data.data && data.data.servicios) {
            selectServicio.innerHTML = '';
            data.data.servicios.forEach(servicio => {
                if (servicio.activo == 1 || servicio.activo === undefined) {
                    selectServicio.innerHTML += `<option value="${servicio.id}">✂️ ${escapeHtml(servicio.nombre)} (${servicio.duracion_min || 45} min · Bs.${servicio.precio_base || 0})</option>`;
                }
            });
        } else {
            selectServicio.innerHTML = `
                <option value="1">✂️ Baño Rápido (30 min · Bs.45)</option>
                <option value="2">✂️ Baño + Corte (60 min · Bs.85)</option>
                <option value="3">✂️ Servicio Completo (90 min · Bs.120)</option>
            `;
        }
    } catch (error) {
        console.error('Error cargando servicios:', error);
        selectServicio.innerHTML = `
            <option value="1">✂️ Baño Rápido (30 min · Bs.45)</option>
            <option value="2">✂️ Baño + Corte (60 min · Bs.85)</option>
            <option value="3">✂️ Servicio Completo (90 min · Bs.120)</option>
        `;
    }
}

async function cargarGroomersEnSelect() {
    const selectGroomer = document.getElementById('citaGroomer');
    if (!selectGroomer) return;
    
    try {
        const response = await fetch(API_URL + 'get_all_data.php');
        const data = await response.json();
        
        if (data.success && data.data && data.data.groomers) {
            selectGroomer.innerHTML = '<option value="">👤 Sin asignar</option>';
            data.data.groomers.forEach(groomer => {
                selectGroomer.innerHTML += `<option value="${groomer.id}">✂️ ${escapeHtml(groomer.nombre)}</option>`;
            });
        } else {
            selectGroomer.innerHTML = `
                <option value="">👤 Sin asignar</option>
                <option value="1">✂️ María González</option>
                <option value="2">✂️ Carlos Ríos</option>
            `;
        }
    } catch (error) {
        console.error('Error cargando groomers:', error);
        selectGroomer.innerHTML = `
            <option value="">👤 Sin asignar</option>
            <option value="1">✂️ María González</option>
            <option value="2">✂️ Carlos Ríos</option>
        `;
    }
}

async function guardarCitaBD() {
    console.log('🚨 GUARDAR CITA - INICIO');
    
    let cliente_id = document.getElementById('citaClienteSelect')?.value;
    const hiddenClienteId = document.getElementById('hiddenClienteId');
    
    if (hiddenClienteId && hiddenClienteId.value) {
        cliente_id = hiddenClienteId.value;
        console.log('📌 Usando ID oculto de cliente:', cliente_id);
    }
    
    const mascota_id = document.getElementById('citaMascotaSelect')?.value;
    const servicio_id = document.getElementById('citaServicio')?.value;
    const groomer_id = document.getElementById('citaGroomer')?.value || null;
    const observaciones = document.getElementById('citaObs')?.value || '';
    
    let fecha = document.getElementById('citaFecha')?.value.trim() || '';
    let hora = document.getElementById('citaHora')?.value.trim() || '';
    
    console.log('📋 VALORES:', { cliente_id, mascota_id, servicio_id, fecha, hora });
    
    if (!cliente_id) { showToast('❌ Seleccione un cliente', 'error'); return; }
    if (!mascota_id) { showToast('❌ Seleccione una mascota', 'error'); return; }
    if (!servicio_id) { showToast('❌ Seleccione un servicio', 'error'); return; }
    if (!fecha) { showToast('❌ Ingrese una fecha', 'error'); return; }
    if (!hora) { showToast('❌ Ingrese una hora', 'error'); return; }
    
    if (hora && hora.split(':').length === 2) {
        hora = hora + ':00';
    }
    
    const datosEnviar = {
        cliente_id: parseInt(cliente_id),
        mascota_id: parseInt(mascota_id),
        servicio_id: parseInt(servicio_id),
        fecha: fecha,
        hora: hora,
        observaciones: observaciones
    };
    
    if (groomer_id && groomer_id !== '') {
        datosEnviar.groomer_id = parseInt(groomer_id);
    }
    
    console.log('📤 Enviando:', datosEnviar);
    
    const btn = document.querySelector('#modalNuevaCita .btn-teal');
    if (!btn) return;
    
    const originalText = btn.textContent;
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'citas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosEnviar)
        });
        
        const data = await response.json();
        console.log('📦 Respuesta:', data);
        
        if (data.success) {
            showToast('✅ Cita agendada correctamente', 'success');
            closeModal('modalNuevaCita');
            
            if (typeof cargarCitasSemana === 'function') cargarCitasSemana();
            if (typeof cargarListaCitas === 'function') cargarListaCitas();
            if (IS_CLIENT && typeof cargarCitasCliente === 'function') cargarCitasCliente();
            
            const fechaInput = document.getElementById('citaFecha');
            const horaInput = document.getElementById('citaHora');
            const obsInput = document.getElementById('citaObs');
            if (fechaInput) fechaInput.value = '';
            if (horaInput) horaInput.value = '';
            if (obsInput) obsInput.value = '';
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo guardar'), 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('❌ Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// ============================================
// DASHBOARD
// ============================================

async function cargarDashboard() {
    console.log('Cargando dashboard...');
    
    const statsGrid = document.getElementById('statsGrid');
    if (!statsGrid) return;
    
    statsGrid.innerHTML = '<div class="loading">Cargando estadísticas...</div>';
    
    try {
        const response = await fetch(API_URL + 'dashboard.php?action=kpi');
        const data = await response.json();
        
        if (data.success && data.kpi) {
            statsGrid.innerHTML = `
                <div class="stat-card teal">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Ventas Hoy</div>
                    <div class="stat-value">Bs. ${data.kpi.ventas_hoy || 0}</div>
                </div>
                <div class="stat-card caramel">
                    <div class="stat-icon">📅</div>
                    <div class="stat-label">Citas Hoy</div>
                    <div class="stat-value">${data.kpi.citas_hoy || 0}</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Clientes Nuevos</div>
                    <div class="stat-value">${data.kpi.clientes_nuevos_mes || 0}</div>
                </div>
                <div class="stat-card rust">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-label">Stock Bajo</div>
                    <div class="stat-value">${data.kpi.stock_bajo || 0}</div>
                </div>
            `;
        } else {
            statsGrid.innerHTML = '<div class="error">Error al cargar datos</div>';
        }
    } catch (error) {
        console.error('Error cargando dashboard:', error);
        statsGrid.innerHTML = '<div class="error">Error de conexión con el servidor</div>';
    }
}

function initApp(user) {
    console.log('Inicializando app para:', user.nombre);
    currentUser = user;
    
    const userNameEl = document.getElementById('userName');
    const userRoleEl = document.getElementById('userRoleLabel');
    const avatar = document.getElementById('userAvatar');
    const dashTitle = document.getElementById('dashTitle');
    const dashSub = document.getElementById('dashSub');
    
    if (userNameEl) userNameEl.textContent = user.nombre;
    if (userRoleEl) userRoleEl.textContent = getRoleName(user.rol);
    if (avatar) avatar.textContent = getRoleIcon(user.rol);
    
    const hoy = new Date();
    if (dashTitle) dashTitle.textContent = `Bienvenido, ${user.nombre}`;
    if (dashSub) dashSub.textContent = `Resumen del día — ${hoy.toLocaleDateString('es-BO')}`;
    
    cargarDashboard();
    if (user.rol === 'client') {
        cargarMascotasCliente(user.id);
    }
}

function getRoleName(role) {
    const roles = { admin: 'Administrador', recep: 'Recepcionista', groo: 'Groomer', client: 'Cliente' };
    return roles[role] || role;
}

function getRoleIcon(role) {
    const icons = { admin: '👑', recep: '🗓️', groo: '✂️', client: '🐶' };
    return icons[role] || '🐾';
}

// ============================================
// VERIFICACIONES
// ============================================

async function verificarClienteBD(id) {
    try {
        const response = await fetch(`${API_URL}clientes.php?action=verificar&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Cliente encontrado:', data.cliente);
            showToast(`Cliente verificado: ${data.cliente.nombre}`, 'success');
            return data.cliente;
        } else {
            showToast('Cliente no encontrado', 'error');
            return null;
        }
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

async function verificarMascotaBD(id) {
    try {
        const response = await fetch(`${API_URL}get_all_data.php`);
        const data = await response.json();
        
        if (data.success) {
            const mascota = data.data.mascotas?.find(m => m.id === id);
            if (mascota) {
                console.log('✅ Mascota encontrada:', mascota);
                showToast(`Mascota: ${mascota.nombre}`, 'success');
                return mascota;
            }
        }
        showToast('Mascota no encontrada', 'error');
        return null;
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

// ============================================
// TIMER
// ============================================

let timerSecs = 5025;
setInterval(() => {
    timerSecs++;
    const h = String(Math.floor(timerSecs/3600)).padStart(2,'0');
    const m = String(Math.floor((timerSecs%3600)/60)).padStart(2,'0');
    const s = String(timerSecs%60).padStart(2,'0');
    const timerEl = document.getElementById('timer');
    if (timerEl) timerEl.textContent = `${h}:${m}:${s}`;
}, 1000);

// ============================================
// INICIALIZACIÓN AL CARGAR
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 app.js cargado');
    
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
        try {
            const user = JSON.parse(savedUser);
            currentUser = user;
            console.log('👤 Usuario recuperado:', user.nombre);
            
            const loginScreen = document.getElementById('loginScreen');
            const appContainer = document.getElementById('app');
            
            if (loginScreen && appContainer) {
                loginScreen.style.display = 'none';
                appContainer.style.display = 'flex';
                initApp(user);
            }
        } catch(e) {
            console.log('Error al recuperar sesión');
            localStorage.removeItem('user');
        }
    }
});

// ============================================
// FUNCIONES ADICIONALES (para compatibilidad)
// ============================================

function mostrarLogin() {
    const loginForm = document.getElementById('loginForm');
    const registroForm = document.getElementById('registroForm');
    const tabs = document.querySelectorAll('.auth-tab');
    
    if (loginForm) loginForm.style.display = 'block';
    if (registroForm) registroForm.style.display = 'none';
    if (tabs && tabs[0]) tabs[0].classList.add('active');
    if (tabs && tabs[1]) tabs[1].classList.remove('active');
}

function mostrarRegistro() {
    const loginForm = document.getElementById('loginForm');
    const registroForm = document.getElementById('registroForm');
    const tabs = document.querySelectorAll('.auth-tab');
    
    if (loginForm) loginForm.style.display = 'none';
    if (registroForm) registroForm.style.display = 'block';
    if (tabs && tabs[0]) tabs[0].classList.remove('active');
    if (tabs && tabs[1]) tabs[1].classList.add('active');
}

async function doRegistro() {
    console.log('🔵 Iniciando registro...');
    
    const nombre = document.getElementById('regNombre')?.value.trim();
    const telefono = document.getElementById('regTelefono')?.value.trim();
    const email = document.getElementById('regEmail')?.value.trim();
    const password = document.getElementById('regPassword')?.value;
    const confirmPassword = document.getElementById('regConfirmPassword')?.value;
    
    const errorDiv = document.getElementById('registroError');
    const successDiv = document.getElementById('registroSuccess');
    
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
    
    if (!nombre || !email || !password) {
        if (errorDiv) {
            errorDiv.textContent = '❌ Completa todos los campos requeridos';
            errorDiv.style.display = 'block';
        }
        return;
    }
    
    if (password.length < 8) {
        if (errorDiv) {
            errorDiv.textContent = '❌ La contraseña debe tener al menos 8 caracteres';
            errorDiv.style.display = 'block';
        }
        return;
    }
    
    if (password !== confirmPassword) {
        if (errorDiv) {
            errorDiv.textContent = '❌ Las contraseñas no coinciden';
            errorDiv.style.display = 'block';
        }
        return;
    }
    
    const btn = document.querySelector('#registroForm .btn-primary');
    const originalText = btn ? btn.textContent : 'Crear cuenta';
    if (btn) {
        btn.textContent = '⏳ Creando cuenta...';
        btn.disabled = true;
    }
    
    try {
        const response = await fetch(API_URL + 'registro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, telefono, email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (successDiv) {
                successDiv.textContent = '✅ ' + data.message;
                successDiv.style.display = 'block';
            }
            
            ['regNombre', 'regTelefono', 'regEmail', 'regPassword', 'regConfirmPassword'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            
            setTimeout(() => {
                mostrarLogin();
                if (successDiv) successDiv.style.display = 'none';
                const loginUser = document.getElementById('loginUser');
                if (loginUser) loginUser.value = email;
            }, 2000);
        } else {
            if (errorDiv) {
                errorDiv.textContent = '❌ ' + (data.error || 'Error al crear la cuenta');
                errorDiv.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (errorDiv) {
            errorDiv.textContent = '❌ Error de conexión';
            errorDiv.style.display = 'block';
        }
    } finally {
        if (btn) {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }
}
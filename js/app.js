
const API_URL = '/pawspa/api/';


async function doLogin() {
    console.log('🟢 Iniciando login...');
    
    const email = document.getElementById('loginUser').value;
    const password = document.getElementById('loginPass').value;
    const role = document.getElementById('loginRole').value;
    
    const errorDiv = document.getElementById('loginError');
    errorDiv.style.display = 'none';
    
    if (!email || !password) {
        mostrarError('Por favor ingresa email y contraseña');
        return;
    }
    
    const btn = document.querySelector('.btn-primary');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Verificando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, role })
        });
        
        const data = await response.json();
        console.log('Respuesta:', data);
        
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            mostrarMensaje(data.message, 'success');
            
            setTimeout(() => {
                document.getElementById('loginScreen').style.display = 'none';
                document.getElementById('app').style.display = 'flex';
                initApp(data.user);
            }, 1000);
        } else {
            mostrarError(data.error || 'Error de autenticación');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión. ¿XAMPP está corriendo?');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

function quickLogin(role) {
    const creds = {
        admin: { email: 'admin@pawspa.com', pass: 'admin123' },
        recep: { email: 'recep@pawspa.com', pass: 'recep123' },
        groo: { email: 'maria@pawspa.com', pass: 'groomer123' },
        client: { email: 'ana@email.com', pass: 'cliente123' }
    };
    document.getElementById('loginUser').value = creds[role].email;
    document.getElementById('loginPass').value = creds[role].pass;
    document.getElementById('loginRole').value = role;
    doLogin();
}

function mostrarError(msg) {
    const div = document.getElementById('loginError');
    div.innerHTML = msg;
    div.style.display = 'block';
    div.style.background = '#FFEDED';
    div.style.color = '#C4532A';
    div.style.padding = '10px';
    div.style.borderRadius = '8px';
    div.style.marginBottom = '15px';
    div.style.textAlign = 'center';
    setTimeout(() => div.style.display = 'none', 5000);
}

function mostrarMensaje(msg, tipo) {
    const div = document.getElementById('loginError');
    div.innerHTML = msg;
    div.style.display = 'block';
    div.style.background = tipo === 'success' ? '#E8F8F0' : '#FFEDED';
    div.style.color = tipo === 'success' ? '#2EA87A' : '#C4532A';
    div.style.padding = '10px';
    div.style.borderRadius = '8px';
    div.style.marginBottom = '15px';
    div.style.textAlign = 'center';
    setTimeout(() => div.style.display = 'none', 3000);
}

function doLogout() {
    // Limpiar datos de sesión en el frontend
    localStorage.removeItem('user');
    
    // Llamar al logout de PHP para destruir la sesión
    fetch(API_URL + 'logout.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).finally(() => {
        // Redirigir al login recargando la página
        window.location.href = 'index.php';
    });
}

// ============================================
// INICIALIZACIÓN DE LA APP
// ============================================

function initApp(user) {
    console.log('Inicializando app para:', user.nombre);
    
    document.getElementById('userName').textContent = user.nombre;
    document.getElementById('userRoleLabel').textContent = getRoleName(user.rol);
    
    const avatar = document.getElementById('userAvatar');
    avatar.textContent = getRoleIcon(user.rol);
    
    const hoy = new Date();
    document.getElementById('dashTitle').textContent = `Bienvenido, ${user.nombre}`;
    document.getElementById('dashSub').textContent = `Resumen del día — ${hoy.toLocaleDateString('es-BO')}`;
    
    cargarDashboard();
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
// DASHBOARD
// ============================================

async function cargarDashboard() {
    console.log('Cargando dashboard...');
    
    const statsGrid = document.getElementById('statsGrid');
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

// ============================================
// INICIALIZAR AL CARGAR LA PÁGINA
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Página cargada');
    
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
        try {
            const user = JSON.parse(savedUser);
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('app').style.display = 'flex';
            initApp(user);
        } catch(e) {
            console.log('Error al recuperar sesión');
            localStorage.removeItem('user');
        }
    }
});

// ============================================
// LOGIN
// ============================================
async function doLogin() {
    const email = document.getElementById('loginUser').value;
    const password = document.getElementById('loginPass').value;
    const errorDiv = document.getElementById('loginError');
    errorDiv.style.display = 'none';
    
    if (email === 'admin@pawspa.com' && password === 'admin123') {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('app').style.display = 'flex';
        document.getElementById('userName').textContent = 'Carlos Admin';
        cargarDashboard();
        cargarAgenda();
        cargarProductosAdmin();
    } else {
        errorDiv.textContent = 'Credenciales incorrectas';
        errorDiv.style.display = 'block';
    }
}

function doLogout() {
    // Limpiar datos de sesión en el frontend
    localStorage.removeItem('user');
    
    // Llamar al logout de PHP para destruir la sesión
    fetch(API_URL + 'logout.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).finally(() => {
        // Redirigir al login recargando la página
        window.location.href = 'index.php';
    });
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'show ' + type;
    setTimeout(() => toast.className = '', 3000);
}

// Timer
let timerSecs = 5025;
setInterval(() => {
    timerSecs++;
    const h = String(Math.floor(timerSecs/3600)).padStart(2,'0');
    const m = String(Math.floor((timerSecs%3600)/60)).padStart(2,'0');
    const s = String(timerSecs%60).padStart(2,'0');
    const timerEl = document.getElementById('timer');
    if (timerEl) timerEl.textContent = `${h}:${m}:${s}`;
}, 1000);

// Función para verificar cliente en BD
async function verificarClienteBD(id) {
    try {
        const response = await fetch(`${API_URL}clientes.php?action=verificar&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Cliente encontrado en BD:', data.cliente);
            showToast(`Cliente verificado: ${data.cliente.nombre}`, 'success');
            return data.cliente;
        } else {
            console.log('❌ Cliente no encontrado:', data.error);
            showToast('Cliente no encontrado en BD', 'error');
            return null;
        }
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

// Función para verificar mascota en BD
async function verificarMascotaBD(id) {
    try {
        const response = await fetch(`${API_URL}get_all_data.php`);
        const data = await response.json();
        
        if (data.success) {
            const mascota = data.data.mascotas.find(m => m.id === id);
            if (mascota) {
                console.log('✅ Mascota encontrada en BD:', mascota);
                showToast(`Mascota verificado: ${mascota.nombre}`, 'success');
                return mascota;
            } else {
                showToast('Mascota no encontrada en BD', 'error');
                return null;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}
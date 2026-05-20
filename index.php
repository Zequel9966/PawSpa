<?php
session_start();

// Verificar si hay usuario logueado
$currentUser = $_SESSION['user'] ?? null;
$isLoggedIn = $currentUser !== null;
$isAdmin = $isLoggedIn && $currentUser['rol'] === 'admin';
$isRecep = $isLoggedIn && $currentUser['rol'] === 'recep';
$isGroomer = $isLoggedIn && $currentUser['rol'] === 'groo';
$isClient = $isLoggedIn && $currentUser['rol'] === 'client';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PawSpa — Sistema Integral de Spa & Tienda de Mascotas</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<style>
:root {
  --cream: #FAF6F0;
  --warm: #F5EDE0;
  --caramel: #C9956A;
  --caramel-dark: #A87048;
  --teal: #2D7A6B;
  --teal-light: #3D9B8A;
  --teal-pale: #E8F5F2;
  --rust: #C4532A;
  --gold: #D4A847;
  --charcoal: #2C2C2C;
  --gray: #6B6B6B;
  --gray-light: #E8E4DF;
  --white: #FFFFFF;
  --shadow: 0 4px 24px rgba(44,44,44,0.10);
  --shadow-lg: 0 12px 48px rgba(44,44,44,0.16);
  --radius: 16px;
  --radius-sm: 8px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--charcoal); min-height: 100vh; }
h1, h2, h3, h4 { font-family: 'Playfair Display', serif; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--warm); }
::-webkit-scrollbar-thumb { background: var(--caramel); border-radius: 3px; }

/* ═══ LOGIN ═══ */
#loginScreen {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #2D7A6B 0%, #1A4A40 50%, #C9956A 100%);
  position: relative; overflow: hidden;
}
#loginScreen::before {
  content: ''; position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.login-card {
  background: var(--white); border-radius: 28px; padding: 48px 44px;
  width: 440px; max-width: 95vw; box-shadow: var(--shadow-lg);
  position: relative; animation: slideUp .6s ease;
}
@keyframes slideUp { from { transform: translateY(32px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.login-logo { text-align: center; margin-bottom: 28px; }
.login-logo .paw-icon {
  width: 64px; height: 64px;
  background: linear-gradient(135deg, var(--teal), var(--caramel));
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px; font-size: 28px;
}
.login-logo h1 { font-size: 2.2rem; color: var(--charcoal); letter-spacing: -1px; }
.login-logo p { color: var(--gray); font-size: .9rem; margin-top: 4px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: .85rem; font-weight: 500; color: var(--gray); margin-bottom: 6px; }
.form-group input, .form-group select {
  width: 100%; padding: 12px 16px; border: 2px solid var(--gray-light);
  border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif;
  font-size: .95rem; color: var(--charcoal); background: var(--cream);
  transition: border-color .2s; outline: none;
}
.form-group input:focus, .form-group select:focus { border-color: var(--teal); background: var(--white); }
.form-group input.error { border-color: var(--rust); }
.login-error {
  background: #FFEDED; color: var(--rust); border-radius: 8px;
  padding: 10px 14px; font-size: .85rem; margin-bottom: 12px;
  display: none; border-left: 3px solid var(--rust);
}
.btn-primary {
  width: 100%; padding: 14px; background: linear-gradient(135deg, var(--teal), var(--teal-light));
  color: var(--white); border: none; border-radius: var(--radius-sm);
  font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 600;
  cursor: pointer; transition: transform .15s, box-shadow .15s; margin-top: 8px;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(45,122,107,.4); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.demo-roles { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--gray-light); }
.demo-roles p { text-align: center; font-size: .8rem; color: var(--gray); margin-bottom: 10px; }
.role-pills { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
.role-pill {
  padding: 6px 14px; border-radius: 20px; font-size: .78rem; font-weight: 500;
  cursor: pointer; border: 2px solid transparent; transition: all .15s;
}
.pill-admin { background: #FFF3E8; color: var(--rust); border-color: #F4C8A8; }
.pill-recep { background: #E8F5F2; color: var(--teal); border-color: #A8D9CF; }
.pill-groo { background: #FFF8E8; color: var(--gold); border-color: #F0D898; }
.pill-client { background: #F0EAF8; color: #7B5EA7; border-color: #C8B0E8; }
.role-pill:hover { transform: translateY(-1px); }
.captcha-wrap { display: flex; justify-content: center; margin: 16px 0 8px; }
.recaptcha-note { text-align:center; font-size:.75rem; color:var(--gray); margin-bottom:4px; }

/* ═══ APP SHELL ═══ */
#app { display: none; min-height: 100vh; flex-direction: column; }
#app.visible { display: flex; }
.topbar {
  background: var(--white); border-bottom: 1px solid var(--gray-light);
  padding: 0 28px; height: 64px; display: flex; align-items: center;
  justify-content: space-between; position: sticky; top: 0; z-index: 100;
  box-shadow: 0 2px 12px rgba(44,44,44,.06);
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-logo { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: var(--teal); letter-spacing: -0.5px; }
.topbar-logo span { color: var(--caramel); }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.user-badge {
  display: flex; align-items: center; gap: 10px;
  background: var(--cream); border-radius: 40px; padding: 6px 16px 6px 8px;
  cursor: pointer; transition: background .15s;
}
.user-badge:hover { background: var(--warm); }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 600; color: var(--white); }
.av-admin { background: linear-gradient(135deg, var(--rust), #E07050); }
.av-recep { background: linear-gradient(135deg, var(--teal), var(--teal-light)); }
.av-groo  { background: linear-gradient(135deg, var(--gold), #E8C060); }
.av-client { background: linear-gradient(135deg, #7B5EA7, #A07ACA); }
.user-name { font-size: .85rem; font-weight: 600; color: var(--charcoal); line-height: 1.2; }
.user-role { font-size: .72rem; color: var(--gray); }
.btn-logout {
  background: none; border: 1.5px solid var(--gray-light);
  color: var(--gray); padding: 7px 14px; border-radius: 8px;
  font-size: .8rem; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .15s;
}
.btn-logout:hover { border-color: var(--rust); color: var(--rust); }
.app-body { display: flex; flex: 1; }
.sidebar {
  width: 240px; background: var(--charcoal); min-height: calc(100vh - 64px);
  padding: 24px 16px; display: flex; flex-direction: column; gap: 4px;
  position: sticky; top: 64px; height: calc(100vh - 64px); overflow-y: auto;
}
.sidebar-section { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1.2px; color: #777; padding: 16px 12px 6px; }
.nav-item {
  display: flex; align-items: center; gap: 11px; padding: 10px 14px;
  border-radius: 10px; cursor: pointer; color: #B0B0B0; font-size: .88rem;
  font-weight: 500; transition: all .15s;
}
.nav-item:hover { background: rgba(255,255,255,.08); color: var(--white); }
.nav-item.active { background: linear-gradient(135deg, var(--teal), var(--teal-light)); color: var(--white); }
.nav-icon { font-size: 1.1rem; width: 22px; text-align: center; }
.nav-badge { margin-left: auto; background: var(--caramel); color: var(--white); font-size: .68rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; }
.main-content { flex: 1; padding: 28px; overflow-y: auto; max-width: 1200px; }
.page-header { margin-bottom: 28px; }
.page-header h2 { font-size: 1.9rem; color: var(--charcoal); font-weight: 900; letter-spacing: -0.5px; }
.page-header p { color: var(--gray); margin-top: 4px; font-size: .92rem; }
.page-header .header-actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
.stat-card {
  background: var(--white); border-radius: var(--radius); padding: 22px 24px;
  box-shadow: var(--shadow); position: relative; overflow: hidden; transition: transform .2s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-card::after { content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px; border-radius: 0 0 0 80px; opacity: .08; }
.stat-card.teal::after { background: var(--teal); }
.stat-card.caramel::after { background: var(--caramel); }
.stat-card.gold::after { background: var(--gold); }
.stat-card.rust::after { background: var(--rust); }
.stat-icon { font-size: 1.6rem; margin-bottom: 10px; }
.stat-label { font-size: .78rem; color: var(--gray); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
.stat-value { font-size: 2rem; font-family: 'Playfair Display', serif; font-weight: 700; margin: 4px 0; }
.stat-card.teal .stat-value { color: var(--teal); }
.stat-card.caramel .stat-value { color: var(--caramel-dark); }
.stat-card.gold .stat-value { color: var(--gold); }
.stat-card.rust .stat-value { color: var(--rust); }
.stat-change { font-size: .78rem; color: var(--gray); }
.stat-change.up { color: #2EA87A; } .stat-change.down { color: var(--rust); }
.card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 24px; margin-bottom: 20px; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.card-title { font-size: 1.1rem; font-weight: 700; color: var(--charcoal); }
.card-subtitle { font-size: .8rem; color: var(--gray); margin-top: 2px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { background: var(--cream); font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--gray); padding: 10px 14px; text-align: left; border-bottom: 2px solid var(--gray-light); }
td { padding: 12px 14px; border-bottom: 1px solid var(--gray-light); font-size: .88rem; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--cream); }
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
.badge-green  { background: #E8F8F0; color: #2EA87A; }
.badge-orange { background: #FFF4E8; color: var(--caramel-dark); }
.badge-blue   { background: #E8F0FF; color: #4A70D8; }
.badge-red    { background: #FFEDED; color: var(--rust); }
.badge-gray   { background: var(--gray-light); color: var(--gray); }
.badge-teal   { background: var(--teal-pale); color: var(--teal); }
.btn {
  display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px;
  border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif;
  font-size: .88rem; font-weight: 500; cursor: pointer; border: none; transition: all .15s;
}
.btn-sm { padding: 6px 12px; font-size: .8rem; }
.btn-teal { background: var(--teal); color: var(--white); }
.btn-teal:hover { background: var(--teal-light); }
.btn-caramel { background: var(--caramel); color: var(--white); }
.btn-caramel:hover { background: var(--caramel-dark); }
.btn-outline { background: transparent; border: 1.5px solid var(--gray-light); color: var(--charcoal); }
.btn-outline:hover { border-color: var(--teal); color: var(--teal); }
.btn-ghost { background: transparent; color: var(--gray); }
.btn-ghost:hover { background: var(--cream); color: var(--charcoal); }
.btn-danger { background: #FFEDED; color: var(--rust); }
.btn-danger:hover { background: var(--rust); color: var(--white); }
.btn-whatsapp { background: #25D366; color: var(--white); }
.btn-whatsapp:hover { background: #1EB857; }
.form-row { display: flex; gap: 14px; flex-wrap: wrap; }
.form-col { flex: 1; min-width: 180px; }
.input-group { margin-bottom: 16px; }
.input-group label { display: block; font-size: .83rem; font-weight: 500; color: var(--gray); margin-bottom: 5px; }
.input-group input, .input-group select, .input-group textarea {
  width: 100%; padding: 10px 14px; border: 1.5px solid var(--gray-light);
  border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif;
  font-size: .9rem; color: var(--charcoal); background: var(--cream); outline: none; transition: border-color .2s;
}
.input-group input:focus, .input-group select:focus, .input-group textarea:focus { border-color: var(--teal); background: var(--white); }
.input-group textarea { resize: vertical; min-height: 80px; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(44,44,44,.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px; animation: fadeIn .2s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal {
  background: var(--white); border-radius: 20px; padding: 32px;
  width: 580px; max-width: 100%; max-height: 88vh; overflow-y: auto;
  box-shadow: var(--shadow-lg); animation: slideUp .3s ease;
}
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.modal-title { font-size: 1.3rem; font-weight: 700; }
.modal-close { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--gray); line-height: 1; }
.modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
.modal-overlay.hidden { display: none; }
.calendar-grid {
  display: grid; grid-template-columns: 120px repeat(5, 1fr);
  gap: 0; border: 1px solid var(--gray-light); border-radius: var(--radius); overflow: hidden;
}
.cal-header { background: var(--charcoal); color: var(--white); padding: 10px 8px; text-align: center; font-size: .8rem; font-weight: 600; }
.cal-time { background: var(--cream); padding: 8px 12px; font-size: .75rem; color: var(--gray); border-right: 1px solid var(--gray-light); border-bottom: 1px solid var(--gray-light); display: flex; align-items: center; }
.cal-slot { padding: 4px; border-right: 1px solid var(--gray-light); border-bottom: 1px solid var(--gray-light); min-height: 52px; position: relative; cursor: pointer; transition: background .1s; }
.cal-slot:hover { background: var(--teal-pale); }
.cal-slot.occupied { background: #FFF4E8; }
.cal-event { background: linear-gradient(135deg, var(--teal), var(--teal-light)); color: var(--white); border-radius: 6px; padding: 4px 8px; font-size: .72rem; line-height: 1.3; }
.cal-event.caramel { background: linear-gradient(135deg, var(--caramel), #E0A880); }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px; }
.product-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; transition: transform .2s; }
.product-card:hover { transform: translateY(-3px); }
.product-img { height: 100px; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; }
.bg1 { background: linear-gradient(135deg, #FFF4E8, #FFE8CC); }
.bg2 { background: linear-gradient(135deg, #E8F5F2, #CCE8E0); }
.bg3 { background: linear-gradient(135deg, #F5EDF8, #E8D8F0); }
.bg4 { background: linear-gradient(135deg, #FFF8E8, #FFEEC0); }
.product-info { padding: 14px; }
.product-name { font-weight: 700; font-size: .95rem; }
.product-cat { font-size: .78rem; color: var(--gray); margin-top: 2px; }
.product-price { font-size: 1.1rem; font-family: 'Playfair Display', serif; font-weight: 700; color: var(--teal); margin: 8px 0 4px; }
.product-stock { font-size: .78rem; color: var(--gray); margin-bottom: 10px; }
.product-actions { display: flex; gap: 6px; }
.tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--gray-light); margin-bottom: 24px; flex-wrap: wrap; }
.tab { padding: 10px 18px; font-size: .88rem; font-weight: 500; color: var(--gray); cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all .15s; }
.tab:hover { color: var(--charcoal); }
.tab.active { color: var(--teal); border-bottom-color: var(--teal); font-weight: 600; }
.cart-panel {
  position: fixed; right: 0; top: 64px; width: 340px; height: calc(100vh - 64px);
  background: var(--white); box-shadow: -4px 0 24px rgba(44,44,44,.12);
  padding: 24px; transform: translateX(100%); transition: transform .3s ease;
  z-index: 90; overflow-y: auto;
}
.cart-panel.open { transform: translateX(0); }
.cart-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-light); }
.cart-item-icon { font-size: 1.8rem; }
.cart-item-info { flex: 1; }
.cart-item-name { font-size: .88rem; font-weight: 500; }
.cart-item-price { font-size: .8rem; color: var(--gray); }
.cart-qty { display: flex; align-items: center; gap: 8px; }
.qty-btn { width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid var(--gray-light); background: none; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.qty-btn:hover { border-color: var(--teal); color: var(--teal); }
.cart-total { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: var(--teal); }
.star-rating { display: flex; gap: 6px; }
.star { font-size: 1.4rem; cursor: pointer; transition: transform .1s; }
.star:hover, .star.active { transform: scale(1.2); }
.notif-item { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--gray-light); }
.notif-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.notif-dot.green { background: #2EA87A; }
.notif-dot.orange { background: var(--caramel); }
.notif-dot.red { background: var(--rust); }
.notif-content { flex: 1; }
.notif-title { font-size: .88rem; font-weight: 500; }
.notif-desc { font-size: .8rem; color: var(--gray); margin-top: 2px; }
.notif-time { font-size: .72rem; color: var(--gray); margin-top: 4px; }
.progress-bar { height: 8px; background: var(--gray-light); border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width .5s; }
.prog-teal { background: linear-gradient(90deg, var(--teal), var(--teal-light)); }
.prog-caramel { background: linear-gradient(90deg, var(--caramel), #E0A880); }
.prog-gold { background: linear-gradient(90deg, var(--gold), #E8C060); }
.page { display: none; }
.page.active { display: block; }
.pet-card { background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); display: flex; gap: 14px; align-items: flex-start; transition: transform .2s; }
.pet-card:hover { transform: translateY(-2px); }
.pet-avatar { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--caramel), var(--teal)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
.pet-info h4 { font-size: .95rem; font-weight: 600; }
.pet-info p { font-size: .8rem; color: var(--gray); margin-top: 3px; }
.pet-tags { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
.bar-chart { display: flex; align-items: flex-end; gap: 10px; height: 160px; }
.bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
.bar { width: 100%; border-radius: 6px 6px 0 0; transition: height .8s ease; }
.bar-label { font-size: .72rem; color: var(--gray); }
.bar-val { font-size: .8rem; font-weight: 600; }
.checklist-item {
  display: flex; align-items: center; gap: 10px; padding: 8px 12px;
  border-radius: 8px; cursor: pointer; margin-bottom: 6px;
  border: 1.5px solid var(--gray-light); transition: all .15s;
}
.checklist-item:hover { border-color: var(--teal); }
.checklist-item.checked { background: var(--teal-pale); border-color: var(--teal); }
.checklist-check { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--gray-light); display: flex; align-items: center; justify-content: center; font-size: .8rem; color: var(--teal); flex-shrink: 0; }
.checklist-item.checked .checklist-check { background: var(--teal); color: white; border-color: var(--teal); }
.checklist-label { flex: 1; font-size: .88rem; }
.checklist-obs { border: none; background: transparent; font-size: .8rem; color: var(--gray); outline: none; font-family: 'DM Sans', sans-serif; flex: 1; }

/* ═══ LOGS PAGE ═══ */
.log-entry {
  display: flex; gap: 14px; padding: 12px 16px;
  border-radius: 10px; margin-bottom: 8px;
  border-left: 4px solid transparent; font-size: .85rem;
  background: var(--white); box-shadow: 0 1px 6px rgba(44,44,44,.06);
  transition: transform .15s;
}
.log-entry:hover { transform: translateX(4px); }
.log-entry.login { border-left-color: #2EA87A; }
.log-entry.logout { border-left-color: var(--gray); }
.log-entry.create { border-left-color: var(--teal); }
.log-entry.edit { border-left-color: var(--gold); }
.log-entry.delete { border-left-color: var(--rust); }
.log-entry.error { border-left-color: var(--rust); background: #FFEFEF; }
.log-icon { font-size: 1.2rem; width: 28px; text-align: center; flex-shrink: 0; }
.log-info { flex: 1; }
.log-action { font-weight: 600; color: var(--charcoal); }
.log-detail { color: var(--gray); margin-top: 2px; font-size: .8rem; }
.log-meta { text-align: right; font-size: .75rem; color: var(--gray); flex-shrink: 0; }
.log-user { font-weight: 500; }
.log-filter { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
.log-filter-btn {
  padding: 6px 14px; border-radius: 20px; border: 1.5px solid var(--gray-light);
  background: var(--white); font-family: 'DM Sans', sans-serif; font-size: .8rem;
  cursor: pointer; transition: all .15s; color: var(--gray);
}
.log-filter-btn:hover, .log-filter-btn.active { border-color: var(--teal); color: var(--teal); background: var(--teal-pale); }

/* ═══ TOAST ═══ */
#toast {
  position: fixed; bottom: 28px; right: 28px;
  background: var(--charcoal); color: var(--white);
  padding: 12px 20px; border-radius: 10px; font-size: .88rem; font-weight: 500;
  box-shadow: var(--shadow-lg); z-index: 2000;
  transform: translateY(80px); opacity: 0;
  transition: transform .3s ease, opacity .3s ease; max-width: 320px;
}
#toast.show { transform: translateY(0); opacity: 1; }
#toast.success { background: var(--teal); }
#toast.error { background: var(--rust); }

@media (max-width: 768px) {
  .sidebar { display: none; }
  .main-content { padding: 16px; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
}

/* ═══ AUTH TABS (Login/Registro) ═══ */
.auth-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--gray-light);
}
.auth-tab {
    flex: 1;
    background: none;
    border: none;
    padding: 12px 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray);
    cursor: pointer;
    transition: all .2s;
    position: relative;
}
.auth-tab:hover {
    color: var(--teal);
}
.auth-tab.active {
    color: var(--teal);
}
.auth-tab.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--teal);
}
.login-success {
    background: #E8F8F0;
    color: #2EA87A;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .85rem;
    margin-bottom: 12px;
    text-align: center;
}
.password-strength {
    font-size: .7rem;
    margin-top: 5px;
}
.password-strength.weak { color: var(--rust); }
.password-strength.medium { color: var(--gold); }
.password-strength.strong { color: #2EA87A; }
</style>
</head>
<body>

<!-- ==================== LOGIN (solo si NO está logueado) ==================== -->
<?php if (!$isLoggedIn): ?>
<!-- ═══════════════ LOGIN ═══════════════ -->
<!-- ═══════════════ LOGIN / REGISTRO ═══════════════ -->
<div id="loginScreen">
    <div class="login-card" id="loginCard">
        <!-- Pestañas de navegación -->
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="mostrarLogin()">Iniciar Sesión</button>
            <button class="auth-tab" onclick="mostrarRegistro()">Crear Cuenta</button>
        </div>

        <!-- ==================== FORMULARIO DE LOGIN ==================== -->
        <div id="loginForm">
            <div class="login-logo">
                <div class="paw-icon">🐾</div>
                <h1>PawSpa</h1>
                <p>Sistema Integral de Spa &amp; Tienda de Mascotas</p>
            </div>
            
            <div id="loginError" class="login-error">❌ Usuario o contraseña incorrectos</div>
            
            <div class="form-group">
                <label>Usuario / Email</label>
                <input type="text" id="loginUser" placeholder="correo@pawspa.com">
            </div>
            
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" id="loginPass" placeholder="••••••••">
            </div>
            
            <div class="form-group">
                <label>Rol de acceso</label>
                <select id="loginRole">
                    <option value="admin">Administrador</option>
                    <option value="recep">Recepcionista</option>
                    <option value="groo">Groomer</option>
                    <option value="client">Cliente</option>
                </select>
            </div>
            
            <div class="captcha-wrap">
                <div class="g-recaptcha" data-sitekey="6LfXRt0sAAAAAGrBtHT_YXD2r_AWYai6IzdSbLlo" data-theme="light"></div>
            </div>
            <p class="recaptcha-note">Verificación de seguridad requerida</p>
            <div id="intentosRestantes" style="display:none; margin-top:8px;">
              
            </div>
            
            <button class="btn-primary" onclick="doLogin()">Ingresar al Sistema</button>
        </div>

        <!-- ==================== FORMULARIO DE REGISTRO ==================== -->
        <div id="registroForm" style="display: none;">
            <div class="login-logo">
                <div class="paw-icon">🐾</div>
                <h1>Crear cuenta</h1>
                <p>Únete a la familia PawSpa</p>
            </div>
            
            <div id="registroError" class="login-error"></div>
            <div id="registroSuccess" class="login-success" style="display:none; background:#E8F8F0; color:#2EA87A;"></div>
            
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" id="regNombre" placeholder="Ej: Ana Torres">
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" id="regTelefono" placeholder="7XXXXXXX">
            </div>
            
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" id="regEmail" placeholder="correo@ejemplo.com">
            </div>
            
            <div class="form-group">
                <label>Contraseña (mín. 8 caracteres)</label>
                <input type="password" id="regPassword" placeholder="••••••••">
                <div id="passwordStrength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" id="regConfirmPassword" placeholder="••••••••">
            </div>

            <button class="btn-primary" onclick="doRegistro()">Crear cuenta</button>
            
            <div class="demo-roles">
            <p>¿Ya tienes cuenta? <a href="javascript:void(0)" onclick="mostrarLogin()" style="color:var(--teal); text-decoration:none; font-weight:600">Inicia sesión</a></p>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- ==================== APP PRINCIPAL (solo si está logueado) ==================== -->
<?php if ($isLoggedIn): ?>
<div id="app" class="visible">
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-logo">Paw<span>Spa</span></span>
      <span id="topbarSection" style="color:var(--gray);font-size:.85rem">
        <?php echo $isAdmin ? 'Panel de Administración' : ($isClient ? 'Mi Cuenta' : ($isRecep ? 'Recepción' : 'Groomer')); ?>
      </span>
    </div>
    <div class="topbar-right">
      <div class="user-badge">
        <div class="user-avatar" id="userAvatar"><?php echo $isAdmin ? '👑' : ($isClient ? '🐶' : ($isRecep ? '🗓️' : '✂️')); ?></div>
        <div class="user-info">
          <div class="user-name" id="userName"><?php echo htmlspecialchars($currentUser['nombre']); ?></div>
          <div class="user-role" id="userRoleLabel"><?php echo $isAdmin ? 'Administrador' : ($isClient ? 'Cliente' : ($isRecep ? 'Recepcionista' : 'Groomer')); ?></div>
        </div>
      </div>
      <button class="btn-logout" onclick="doLogout()">↩ Salir</button>
    </div>
  </div>

  <div class="app-body">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar"></div>
    <div class="main-content" id="mainContent">
            <!-- DASHBOARD -->
      <div id="page-dashboard" class="page active">
        <div class="page-header">
          <h2 id="dashTitle">Panel de Control</h2>
          <p id="dashSub">Resumen del día — <span id="todayDate"></span></p>
        </div>
        <div class="stats-grid" id="statsGrid"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Citas de Hoy</div><div class="card-subtitle">Actividad del día</div></div>
              <span class="badge badge-teal">6 totales</span>
            </div>
            <table><thead><tr><th>Hora</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th></tr></thead>
            <tbody>
              <tr><td>09:00</td><td>🐩 Luna</td><td>Baño + Corte</td><td>María</td><td><span class="badge badge-green">✓ Completado</span></td></tr>
              <tr><td>10:00</td><td>🐕 Max</td><td>Baño Rápido</td><td>Carlos</td><td><span class="badge badge-green">✓ Completado</span></td></tr>
              <tr><td>11:30</td><td>🐈 Mishi</td><td>Completo</td><td>María</td><td><span class="badge badge-orange">⏳ En progreso</span></td></tr>
              <tr><td>14:00</td><td>🐕 Rocky</td><td>Baño + Corte</td><td>Carlos</td><td><span class="badge badge-blue">📅 Confirmado</span></td></tr>
              <tr><td>15:30</td><td>🐩 Coco</td><td>Baño Rápido</td><td>Ana</td><td><span class="badge badge-blue">📅 Confirmado</span></td></tr>
              <tr><td>16:00</td><td>🐕 Toby</td><td>Completo</td><td>María</td><td><span class="badge badge-gray">⌚ Pendiente</span></td></tr>
            </tbody></table>
          </div>
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Ocupación por Groomer</div><div class="card-subtitle">% del día laboral</div></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;margin-top:8px">
              <div><div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:.88rem;font-weight:500">María González</span><span style="font-size:.8rem;color:var(--teal);font-weight:600">78%</span></div><div class="progress-bar"><div class="progress-fill prog-teal" style="width:78%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:.88rem;font-weight:500">Carlos Ríos</span><span style="font-size:.8rem;color:var(--caramel-dark);font-weight:600">55%</span></div><div class="progress-bar"><div class="progress-fill prog-caramel" style="width:55%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:.88rem;font-weight:500">Ana Flores</span><span style="font-size:.8rem;color:var(--gold);font-weight:600">40%</span></div><div class="progress-bar"><div class="progress-fill prog-gold" style="width:40%"></div></div></div>
            </div>
            <div style="margin-top:28px">
              <div class="card-title" style="margin-bottom:14px">Ventas esta semana</div>
              <div class="bar-chart">
                <div class="bar-item"><div class="bar-val">Bs.320</div><div class="bar" style="height:80px;background:var(--teal-pale)"></div><div class="bar-label">Lun</div></div>
                <div class="bar-item"><div class="bar-val">Bs.450</div><div class="bar" style="height:110px;background:var(--teal-light)"></div><div class="bar-label">Mar</div></div>
                <div class="bar-item"><div class="bar-val">Bs.280</div><div class="bar" style="height:68px;background:var(--teal-pale)"></div><div class="bar-label">Mié</div></div>
                <div class="bar-item"><div class="bar-val">Bs.620</div><div class="bar" style="height:155px;background:var(--teal)"></div><div class="bar-label">Jue</div></div>
                <div class="bar-item"><div class="bar-val">Bs.390</div><div class="bar" style="height:96px;background:var(--teal-light)"></div><div class="bar-label">Vie</div></div>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">Notificaciones Recientes</div><button class="btn btn-ghost btn-sm">Ver todas</button></div>
          <div class="notif-item"><div class="notif-dot green"></div><div class="notif-content"><div class="notif-title">Luna — Listo para recoger 🎀</div><div class="notif-desc">El servicio completo ha sido finalizado. Se notificó al dueño vía WhatsApp.</div><div class="notif-time">Hace 12 min</div></div></div>
          <div class="notif-item"><div class="notif-dot orange"></div><div class="notif-content"><div class="notif-title">Recordatorio enviado — Rocky a las 14:00</div><div class="notif-desc">Notificación de 2h antes enviada a Juana Pérez.</div><div class="notif-time">Hace 1h</div></div></div>
          <div class="notif-item"><div class="notif-dot red"></div><div class="notif-content"><div class="notif-title">Stock bajo — Shampoo Lavanda (3 unid.)</div><div class="notif-desc">El stock está por debajo del umbral mínimo (5 unidades). Revisar inventario.</div><div class="notif-time">Hace 3h</div></div></div>
        </div>
      </div>

      <!-- AGENDA -->
      <div id="page-agenda" class="page">
        <div class="page-header">
          <h2>Agenda de Citas</h2>
          <p>Gestión de turnos y disponibilidad de groomers</p>
          <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalNuevaCita')">➕ Nueva Cita</button>
            <button class="btn btn-outline" onclick="openModal('modalBloqueo')">🚫 Bloquear Horario</button>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <div style="display:flex;gap:10px;align-items:center">
              <button class="btn btn-ghost btn-sm">◀</button>
              <span style="font-weight:600;font-size:.95rem" id="calWeekLabel">Semana del 5 – 9 Mayo, 2025</span>
              <button class="btn btn-ghost btn-sm">▶</button>
            </div>
            <div style="display:flex;gap:8px">
              <span class="badge badge-teal">● Disponible</span>
              <span class="badge badge-orange">● Ocupado</span>
              <span class="badge badge-red">● Bloqueado</span>
            </div>
          </div>
          <div class="table-wrap"><div class="calendar-grid" id="calendarGrid"></div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">Lista de Citas — Próximas</div>
            <input type="text" placeholder="🔍 Buscar cliente o mascota..." style="padding:8px 14px;border:1.5px solid var(--gray-light);border-radius:8px;font-family:inherit;font-size:.85rem;outline:none;width:240px">
          </div>
          <div class="table-wrap">
            <table id="citasTable">
              <thead><tr><th>ID</th><th>Fecha/Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th><th>Acciones</th></tr></thead>
              <tbody id="citasBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- GROOMING -->
      <div id="page-grooming" class="page">
        <div class="page-header">
          <h2>Fichas de Grooming</h2>
          <p>Gestión de servicios activos e historial de atención</p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
          <div class="card" style="border-left:4px solid var(--caramel)">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:14px">
              <div>
                <div style="font-size:.75rem;color:var(--gray);text-transform:uppercase;font-weight:600">CITA #001 — EN PROGRESO</div>
                <h3 style="margin-top:4px;font-size:1.1rem">🐈 Mishi — Servicio Completo</h3>
                <p style="font-size:.82rem;color:var(--gray)">Dueña: Ana Torres · Groomer: María González</p>
              </div>
              <span class="badge badge-orange">⏳ Activo</span>
            </div>
            <div style="display:flex;gap:10px;margin-bottom:16px">
              <div style="flex:1;background:var(--cream);border-radius:8px;padding:10px">
                <div style="font-size:.72rem;color:var(--gray);font-weight:600;margin-bottom:4px">ESTADO INGRESO</div>
                <div style="font-size:.82rem">Nudos: <strong>Moderados</strong></div>
                <div style="font-size:.82rem">Temperamento: <strong>Tranquilo</strong></div>
                <div style="font-size:.82rem">Raza: <strong>Persa</strong></div>
              </div>
              <div style="flex:1;background:var(--cream);border-radius:8px;padding:10px">
                <div style="font-size:.72rem;color:var(--gray);font-weight:600;margin-bottom:4px">TIEMPO</div>
                <div style="font-size:1.4rem;font-family:'Playfair Display',serif;font-weight:700;color:var(--teal)" id="timer">01:23:45</div>
                <div style="font-size:.72rem;color:var(--gray)">Duración base: 90 min</div>
              </div>
            </div>
            <div style="margin-bottom:14px">
              <div style="font-size:.8rem;font-weight:600;color:var(--gray);margin-bottom:8px">CHECKLIST DE SERVICIO <span style="color:var(--caramel)" id="checkCount">(4/6 completados)</span></div>
              <div id="checklistContainer">
                <div class="checklist-item checked" onclick="toggleCheck(this)"><div class="checklist-check">✓</div><div class="checklist-label">🛁 Baño</div><input class="checklist-obs" placeholder="Observación..." value="Shampoo medicado"></div>
                <div class="checklist-item checked" onclick="toggleCheck(this)"><div class="checklist-check">✓</div><div class="checklist-label">✂️ Corte de pelo</div><input class="checklist-obs" placeholder="Observación..." value="Corte higiénico"></div>
                <div class="checklist-item checked" onclick="toggleCheck(this)"><div class="checklist-check">✓</div><div class="checklist-label">💅 Corte de uñas</div><input class="checklist-obs" placeholder="Observación..."></div>
                <div class="checklist-item checked" onclick="toggleCheck(this)"><div class="checklist-check">✓</div><div class="checklist-label">👂 Limpieza de oídos</div><input class="checklist-obs" placeholder="Observación..."></div>
                <div class="checklist-item" onclick="toggleCheck(this)"><div class="checklist-check"></div><div class="checklist-label">🫧 Expresión de glándulas</div><input class="checklist-obs" placeholder="Observación..."></div>
                <div class="checklist-item" onclick="toggleCheck(this)"><div class="checklist-check"></div><div class="checklist-label">🌸 Perfume / acabado</div><input class="checklist-obs" placeholder="Observación..."></div>
              </div>
            </div>
            <div class="input-group">
              <label>Observaciones y recomendaciones</label>
              <textarea placeholder="Ingrese observaciones...">Requiere cita de seguimiento en 3 semanas. Pelaje en buen estado post-servicio.</textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-teal" style="flex:1" onclick="cerrarServicio()">✅ Cerrar Servicio</button>
              <button class="btn btn-outline" onclick="showToast('Ficha guardada 💾','success')">💾 Guardar</button>
            </div>
          </div>
          <div>
            <div class="card" style="margin-bottom:16px">
              <div class="card-header"><div class="card-title">Cola de Espera</div></div>
              <table><thead><tr><th>Hora</th><th>Mascota</th><th>Servicio</th><th>Estado</th></tr></thead>
              <tbody>
                <tr><td>14:00</td><td>🐕 Rocky</td><td>Baño+Corte</td><td><span class="badge badge-blue">En espera</span></td></tr>
                <tr><td>15:30</td><td>🐩 Coco</td><td>Baño Rápido</td><td><span class="badge badge-gray">Agendado</span></td></tr>
                <tr><td>16:00</td><td>🐕 Toby</td><td>Completo</td><td><span class="badge badge-gray">Agendado</span></td></tr>
              </tbody></table>
            </div>
          </div>
        </div>
      </div>

      <!-- CLIENTES -->
      <div id="page-clientes" class="page">
        <div class="page-header">
          <h2>Clientes &amp; Mascotas</h2>
          <p>Gestión de perfiles, fichas y historial completo</p>
          <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalNuevoCliente')">➕ Nuevo Cliente</button>
            <input type="text" placeholder="🔍 Buscar cliente..." style="padding:9px 16px;border:1.5px solid var(--gray-light);border-radius:8px;font-family:inherit;font-size:.88rem;outline:none;width:220px" oninput="filterClients(this.value)">
          </div>
        </div>
        <div class="tabs">
          <div class="tab active" onclick="switchTab(this,'tab-clientes')">👤 Clientes</div>
          <div class="tab" onclick="switchTab(this,'tab-mascotas')">🐾 Mascotas</div>
          <div class="tab" onclick="switchTab(this,'tab-historial')">📋 Historial</div>
        </div>
        <div id="tab-clientes">
          <div class="table-wrap">
            <table>
              <thead><tr><th>Cliente</th><th>Teléfono</th><th>Email</th><th>Mascotas</th><th>Última visita</th><th>Nivel</th><th>Acciones</th></tr></thead>
              <tbody id="clientesBody"></tbody>
            </table>
          </div>
        </div>
        <div id="tab-mascotas" style="display:none">
          <div id="mascotasGrid" class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))"></div>
        </div>
        <div id="tab-historial" style="display:none">
          <div class="card">
            <div class="card-header"><div class="card-title">Historial Completo — Mishi (🐈)</div><span class="badge badge-teal">8 servicios</span></div>
            <table><thead><tr><th>Fecha</th><th>Servicio</th><th>Groomer</th><th>Observaciones</th><th>Factura</th></tr></thead>
            <tbody>
              <tr><td>10/05/2025</td><td>Servicio Completo</td><td>María G.</td><td>Requiere seguimiento 3 sem.</td><td><span class="badge badge-green">Bs.120</span></td></tr>
              <tr><td>15/04/2025</td><td>Baño + Corte</td><td>María G.</td><td>Pelaje en buen estado</td><td><span class="badge badge-green">Bs.85</span></td></tr>
            </tbody></table>
          </div>
        </div>
      </div>

      <!-- CATALOGO -->
      <div id="page-catalogo" class="page">
        <div class="page-header">
          <h2>Catálogo &amp; Tienda</h2>
          <p>Productos disponibles para venta y pedidos</p>
          <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalNuevoProducto')" id="btnAddProduct">➕ Agregar Producto</button>
            <button class="btn btn-caramel" onclick="toggleCart()">🛒 Carrito <span id="cartCount" style="background:rgba(255,255,255,.3);border-radius:10px;padding:2px 8px;font-size:.8rem">0</span></button>
          </div>
        </div>
        <div class="tabs">
          <div class="tab active" onclick="filterCatalog('todos',this)">Todos</div>
          <div class="tab" onclick="filterCatalog('alimentos',this)">🥩 Alimentos</div>
          <div class="tab" onclick="filterCatalog('shampoo',this)">🧴 Shampoo</div>
          <div class="tab" onclick="filterCatalog('juguetes',this)">🎾 Juguetes</div>
          <div class="tab" onclick="filterCatalog('accesorios',this)">🎀 Accesorios</div>
        </div>
        <div class="products-grid" id="productsGrid"></div>
      </div>

      <!-- PAGOS -->
      <div id="page-pagos" class="page">
        <div class="page-header">
          <h2>Pagos &amp; Facturación</h2>
          <p>Registro de cobros, ventas y cierre de caja</p>
          <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalNuevoPago')">💳 Nuevo Cobro</button>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card teal"><div class="stat-icon">💰</div><div class="stat-label">Ventas Hoy</div><div class="stat-value">Bs.980</div><div class="stat-change up">↑ +12% vs ayer</div></div>
          <div class="stat-card caramel"><div class="stat-icon">🧾</div><div class="stat-label">Facturas emitidas</div><div class="stat-value">14</div><div class="stat-change">Hoy</div></div>
          <div class="stat-card gold"><div class="stat-icon">📦</div><div class="stat-label">Ticket promedio</div><div class="stat-value">Bs.70</div><div class="stat-change up">↑ Bs.12</div></div>
          <div class="stat-card rust"><div class="stat-icon">⏳</div><div class="stat-label">Pendiente cobro</div><div class="stat-value">Bs.180</div><div class="stat-change down">2 citas</div></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">Ventas Recientes</div><button class="btn btn-outline btn-sm" onclick="showToast('Exportando a PDF...','success')">📄 Exportar PDF</button></div>
          <div class="table-wrap">
            <table><thead><tr><th>Factura</th><th>Fecha/Hora</th><th>Cliente</th><th>Servicio</th><th>Total</th><th>Método</th><th>Estado</th></tr></thead>
            <tbody>
              <tr><td>#F-0114</td><td>10/05 09:45</td><td>Ana Torres</td><td>Completo Bs.120</td><td><strong>Bs.120</strong></td><td>🔲 QR</td><td><span class="badge badge-green">Pagado</span></td></tr>
              <tr><td>#F-0113</td><td>10/05 11:00</td><td>Juana Pérez</td><td>Baño+Corte Bs.85</td><td><strong>Bs.110</strong></td><td>💵 Efectivo</td><td><span class="badge badge-green">Pagado</span></td></tr>
              <tr><td>#F-0112</td><td>09/05 16:30</td><td>Luis Mamani</td><td>—</td><td><strong>Bs.180</strong></td><td>🔲 QR</td><td><span class="badge badge-green">Pagado</span></td></tr>
              <tr><td>#F-0111</td><td>09/05 14:00</td><td>Carmen Flores</td><td>Baño Rápido Bs.45</td><td><strong>Bs.45</strong></td><td>💵 Efectivo</td><td><span class="badge badge-orange">Pendiente</span></td></tr>
            </tbody></table>
          </div>
        </div>
      </div>

      <!-- INVENTARIO -->
      <div id="page-inventario" class="page">
        <div class="page-header">
          <h2>Inventario</h2>
          <p>Control de stock de productos e insumos</p>
          <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalMovInventario')">📦 Registrar Movimiento</button>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card teal"><div class="stat-icon">📦</div><div class="stat-label">Productos Activos</div><div class="stat-value">42</div></div>
          <div class="stat-card rust"><div class="stat-icon">⚠️</div><div class="stat-label">Stock Bajo</div><div class="stat-value">3</div><div class="stat-change down">Requieren reposición</div></div>
          <div class="stat-card gold"><div class="stat-icon">🔄</div><div class="stat-label">Movimientos Hoy</div><div class="stat-value" id="movHoy">8</div></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">Lista de Productos</div></div>
          <div class="table-wrap">
            <table><thead><tr><th>Producto</th><th>Categoría</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="inventarioBody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- REPORTES -->
      <div id="page-reportes" class="page">
        <div class="page-header">
          <h2>Reportes &amp; Analytics</h2>
          <p>Indicadores operativos y de desempeño</p>
          <div class="header-actions">
            <button class="btn btn-outline" onclick="showToast('Exportando CSV...','success')">📊 Exportar CSV</button>
            <button class="btn btn-outline" onclick="showToast('Exportando PDF...','success')">📄 Exportar PDF</button>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="card">
            <div class="card-title" style="margin-bottom:16px">Top Servicios del Mes</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:.88rem">Baño + Corte</span><span style="font-weight:600;color:var(--teal)">34 citas</span></div><div class="progress-bar"><div class="progress-fill prog-teal" style="width:85%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:.88rem">Servicio Completo</span><span style="font-weight:600;color:var(--caramel-dark)">28 citas</span></div><div class="progress-bar"><div class="progress-fill prog-caramel" style="width:70%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:.88rem">Baño Rápido</span><span style="font-weight:600;color:var(--gold)">19 citas</span></div><div class="progress-bar"><div class="progress-fill prog-gold" style="width:48%"></div></div></div>
            </div>
          </div>
          <div class="card">
            <div class="card-title" style="margin-bottom:16px">Top Productos Vendidos</div>
            <div class="table-wrap">
              <table><thead><tr><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
              <tbody>
                <tr><td>🥩 Alimento Premium 3kg</td><td>24</td><td>Bs.4,320</td></tr>
                <tr><td>🧴 Shampoo Lavanda</td><td>18</td><td>Bs.450</td></tr>
                <tr><td>🎾 Pelota Kong</td><td>15</td><td>Bs.375</td></tr>
              </tbody></table>
            </div>
          </div>
        </div>
      </div>

      <!-- CONFIGURACION -->
      <div id="page-config" class="page">
        <div class="page-header">
          <h2>Configuración del Sistema</h2>
          <p>Parámetros, usuarios, servicios y seguridad</p>
        </div>
        <div class="tabs">
          <div class="tab active" onclick="switchTab(this,'tab-servicios')">✂️ Servicios</div>
          <div class="tab" onclick="switchTab(this,'tab-groomers')">👤 Groomers</div>
          <div class="tab" onclick="switchTab(this,'tab-usuarios')">🔐 Usuarios</div>
          <div class="tab" onclick="switchTab(this,'tab-sistema')">⚙️ Sistema</div>
        </div>
        <div id="tab-servicios">
          <div class="card">
            <div class="card-header"><div class="card-title">Servicios y Precios</div><button class="btn btn-sm btn-teal" onclick="showToast('Nuevo servicio agregado ✓','success')">➕ Nuevo</button></div>
            <table><thead><tr><th>Servicio</th><th>Duración</th><th>Precio Base</th><th>Activo</th></tr></thead>
            <tbody>
              <tr><td>Baño Rápido</td><td><input type="number" value="30" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"> min</td><td>Bs.<input type="number" value="45" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"></td><td><input type="checkbox" checked></td></tr>
              <tr><td>Baño + Corte</td><td><input type="number" value="60" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"> min</td><td>Bs.<input type="number" value="85" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"></td><td><input type="checkbox" checked></td></tr>
              <tr><td>Servicio Completo</td><td><input type="number" value="90" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"> min</td><td>Bs.<input type="number" value="120" style="width:70px;padding:5px 8px;border:1px solid var(--gray-light);border-radius:4px;font-family:inherit"></td><td><input type="checkbox" checked></td></tr>
            </tbody></table>
            <button class="btn btn-teal" style="margin-top:14px" onclick="saveConfig()">💾 Guardar cambios</button>
          </div>
        </div>
        <div id="tab-groomers" style="display:none">
          <div class="card">
            <div class="card-header"><div class="card-title">Groomers y Disponibilidad</div></div>
            <table><thead><tr><th>Nombre</th><th>Horario</th><th>Días</th><th>Estado</th></tr></thead>
            <tbody>
              <tr><td>María González</td><td>09:00 – 18:00</td><td>Lun – Vie</td><td><span class="badge badge-green">Activa</span></td></tr>
              <tr><td>Carlos Ríos</td><td>10:00 – 17:00</td><td>Lun – Sáb</td><td><span class="badge badge-green">Activo</span></td></tr>
              <tr><td>Ana Flores</td><td>09:00 – 15:00</td><td>Mar – Sáb</td><td><span class="badge badge-green">Activa</span></td></tr>
            </tbody></table>
          </div>
        </div>
        <div id="tab-usuarios" style="display:none">
          <div class="card">
            <div class="card-header"><div class="card-title">Usuarios del Sistema</div><button class="btn btn-sm btn-teal" onclick="openModal('modalNuevoUsuario')">➕ Nuevo Usuario</button></div>
            <table><thead><tr><th>Email</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="usuariosBody"></tbody></table>
          </div>
        </div>
        <div id="tab-sistema" style="display:none">
          <div class="card">
            <div class="card-title" style="margin-bottom:16px">Parámetros Generales</div>
            <div class="form-row">
              <div class="form-col input-group"><label>Nombre del Negocio</label><input type="text" id="cfgNombre" value="PawSpa Bolivia"></div>
              <div class="form-col input-group"><label>WhatsApp de negocio</label><input type="text" id="cfgWA" value="+591 72345678"></div>
            </div>
            <div class="form-row">
              <div class="form-col input-group"><label>Stock mínimo por defecto</label><input type="number" id="cfgStock" value="5"></div>
              <div class="form-col input-group"><label>Recordatorio (horas antes)</label><input type="number" id="cfgRecord" value="2"></div>
            </div>
            <button class="btn btn-teal" onclick="saveConfig()">💾 Guardar configuración</button>
          </div>
        </div>
      </div>

      <!-- LOGS -->
      <div id="page-logs" class="page">
        <div class="page-header">
          <h2>📋 Registro de Actividad</h2>
          <p>Historial completo de acciones en el sistema — auditoría y trazabilidad</p>
          <div class="header-actions">
            <button class="btn btn-outline btn-sm" onclick="exportLogs()">📄 Exportar logs</button>
            <button class="btn btn-danger btn-sm" onclick="clearLogs()">🗑️ Limpiar</button>
          </div>
        </div>
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
            <div class="log-filter">
              <button class="log-filter-btn active" onclick="filterLogs('all',this)">Todos</button>
              <button class="log-filter-btn" onclick="filterLogs('login',this)">🔐 Login</button>
              <button class="log-filter-btn" onclick="filterLogs('create',this)">➕ Creación</button>
              <button class="log-filter-btn" onclick="filterLogs('edit',this)">✏️ Edición</button>
              <button class="log-filter-btn" onclick="filterLogs('delete',this)">🗑️ Eliminación</button>
              <button class="log-filter-btn" onclick="filterLogs('error',this)">❌ Errores</button>
            </div>
            <span id="logCount" style="font-size:.8rem;color:var(--gray)">0 registros</span>
          </div>
          <div id="logsContainer"></div>
        </div>
      </div>

<!-- MI CUENTA (cliente) -->
<div id="page-micuenta" class="page">
    <div class="page-header">
        <h2>Mi Cuenta</h2>
        <p>Gestiona tus citas, mascotas e historial</p>
        <div class="header-actions">
            <button class="btn btn-teal" onclick="openModal('modalNuevaCita')">📅 Agendar Cita</button>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card teal"><div class="stat-icon">📅</div><div class="stat-label">Próxima Cita</div><div class="stat-value">17/05</div><div class="stat-change">10:00 AM</div></div>
        <div class="stat-card caramel"><div class="stat-icon">🐾</div><div class="stat-label">Mis Mascotas</div><div class="stat-value" id="totalMascotasCliente">0</div><div class="stat-change" id="primeraMascotaCliente">-</div></div>
        <div class="stat-card gold"><div class="stat-icon">🏆</div><div class="stat-label">Visitas Totales</div><div class="stat-value">8</div><div class="stat-change up">↑ Desde 2024</div></div>
        <div class="stat-card rust"><div class="stat-icon">⭐</div><div class="stat-label">Puntos Gold</div><div class="stat-value">1,240</div><div class="stat-change up">Nivel Gold ⭐</div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Mis Citas Recientes -->
        <div class="card">
            <div class="card-header"><div class="card-title">Mis Citas Recientes</div></div>
            <div class="table-wrap">
                <table style="width:100%">
                    <thead><tr><th>Fecha</th><th>Mascota</th><th>Servicio</th><th>Estado</th></tr></thead>
                    <tbody id="citasClienteTable">
                        <tr><td colspan="4" class="loading">Cargando citas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Mis Mascotas (VERSIÓN DINÁMICA) -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Mis Mascotas</div>
                <button class="btn btn-sm btn-teal" onclick="abrirModalMascotaCliente()">➕ Agregar</button>
            </div>
            <div id="mascotasClienteContainer">
                <div class="loading">Cargando mascotas...</div>
            </div>
        </div>
    </div>
    
    <!-- Calificar Último Servicio -->
    <div class="card" style="margin-top:20px">
        <div class="card-header"><div class="card-title">Calificar Último Servicio</div></div>
        <p style="font-size:.88rem;color:var(--gray);margin-bottom:14px">¿Cómo fue tu experiencia en PawSpa? — Mishi · 10/05/2025</p>
        <div class="star-rating">
            <span class="star active" onclick="rateStar(1)">⭐</span>
            <span class="star active" onclick="rateStar(2)">⭐</span>
            <span class="star active" onclick="rateStar(3)">⭐</span>
            <span class="star active" onclick="rateStar(4)">⭐</span>
            <span class="star" onclick="rateStar(5)">⭐</span>
        </div>
        <div class="input-group" style="margin-top:14px"><textarea placeholder="Comparte tu experiencia..."></textarea></div>
        <button class="btn btn-teal" onclick="showToast('¡Gracias por tu calificación! 🌟','success')">Enviar calificación</button>
    </div>
</div>

    </div><!-- /main-content -->
  </div><!-- /app-body -->
</div><!-- /app -->

<!-- CART SIDEBAR -->
<div class="cart-panel" id="cartPanel">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h3 style="font-size:1.1rem">🛒 Mi Carrito</h3>
    <button class="btn btn-ghost btn-sm" onclick="toggleCart()">✕</button>
  </div>
  <div id="cartItems"><p style="color:var(--gray);text-align:center;padding:20px">Carrito vacío</p></div>
  <div style="border-top:2px solid var(--gray-light);padding-top:16px;margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <span style="font-weight:600">Total:</span>
      <span class="cart-total" id="cartTotal">Bs. 0</span>
    </div>
    <button class="btn btn-whatsapp" style="width:100%" onclick="sendWhatsApp()">📱 Pedir por WhatsApp</button>
  </div>
</div>

<!-- TOAST -->
<div id="toast"></div>

<!-- ═══════════ MODALS ═══════════ -->

<!-- Modal Nueva Cita -->
<div class="modal-overlay hidden" id="modalNuevaCita">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">📅 Nueva Cita</h3><button class="modal-close" onclick="closeModal('modalNuevaCita')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Cliente</label><input type="text" id="citaCliente" placeholder="Nombre del cliente"></div>
      <div class="form-col input-group"><label>Mascota</label><input type="text" id="citaMascota" placeholder="Nombre de la mascota"></div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Fecha</label><input type="date" id="citaFecha"></div>
      <div class="form-col input-group"><label>Hora</label><input type="time" id="citaHora" value="09:00"></div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Servicio</label>
        <select id="citaServicio">
          <option value="Baño Rápido">Baño Rápido (30 min · Bs.45)</option>
          <option value="Baño + Corte">Baño + Corte (60 min · Bs.85)</option>
          <option value="Servicio Completo">Servicio Completo (90 min · Bs.120)</option>
        </select>
      </div>
      <div class="form-col input-group"><label>Groomer</label>
        <select id="citaGroomer">
          <option>María González</option>
          <option>Carlos Ríos</option>
          <option>Ana Flores</option>
        </select>
      </div>
    </div>
    <div class="input-group"><label>Observaciones</label><textarea id="citaObs" placeholder="Alergias, temperamento, notas especiales..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevaCita')">Cancelar</button>
      <button class="btn btn-teal" onclick="guardarCita()">✅ Confirmar Cita</button>
    </div>
  </div>
</div>

<!-- Modal Ficha Grooming -->
<div class="modal-overlay hidden" id="modalFicha">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">📋 Ficha de Servicio #001</h3><button class="modal-close" onclick="closeModal('modalFicha')">×</button></div>
    <div style="background:var(--cream);border-radius:10px;padding:16px;margin-bottom:16px">
      <div style="font-size:.8rem;color:var(--gray);margin-bottom:4px">MASCOTA</div>
      <div style="font-size:1rem;font-weight:600">🐈 Mishi — Persa, 3 años</div>
      <div style="font-size:.8rem;color:var(--gray);margin-top:4px">Dueña: Ana Torres · ⚠ Alergia al polvo</div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Servicio realizado</label><select><option>Servicio Completo</option></select></div>
      <div class="form-col input-group"><label>Groomer</label><input type="text" value="María González"></div>
    </div>
    <div class="input-group"><label>Observaciones</label><textarea>Pelaje en buen estado. Requiere seguimiento en 3 semanas.</textarea></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalFicha')">Cerrar</button>
      <button class="btn btn-teal" onclick="showToast('Ficha actualizada ✓','success');closeModal('modalFicha')">💾 Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal-overlay hidden" id="modalNuevoCliente">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">👤 Nuevo Cliente</h3><button class="modal-close" onclick="closeModal('modalNuevoCliente')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre completo</label><input type="text" id="cliNombre" placeholder="Ej: Ana Torres"></div>
      <div class="form-col input-group"><label>Teléfono</label><input type="tel" id="cliTel" placeholder="7XXXXXXX"></div>
    </div>
    <div class="input-group"><label>Email</label><input type="email" id="cliEmail" placeholder="cliente@email.com"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
<button class="btn btn-teal" onclick="guardarClienteBD()">✅ Guardar Cliente</button>    </div>
  </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal-overlay hidden" id="modalEditCliente">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">✏️ Editar Cliente</h3><button class="modal-close" onclick="closeModal('modalEditCliente')">×</button></div>
    <input type="hidden" id="editCliId">
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre completo</label><input type="text" id="editCliNombre"></div>
      <div class="form-col input-group"><label>Teléfono</label><input type="tel" id="editCliTel"></div>
    </div>
    <div class="input-group"><label>Email</label><input type="email" id="editCliEmail"></div>
    <div class="input-group"><label>Nivel</label>
      <select id="editCliNivel">
        <option value="Bronze">🥉 Bronze</option>
        <option value="Silver">🥈 Silver</option>
        <option value="Gold">⭐ Gold</option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalEditCliente')">Cancelar</button>
      <button class="btn btn-teal" onclick="updateCliente()">💾 Actualizar</button>
    </div>
  </div>
</div>

<!-- Modal Nueva Mascota -->
<div class="modal-overlay hidden" id="modalNuevaMascota">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">🐾 Nueva Mascota</h3><button class="modal-close" onclick="closeModal('modalNuevaMascota')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre</label><input type="text" id="petNombre" placeholder="Ej: Luna"></div>
      <div class="form-col input-group"><label>Especie</label><select id="petEspecie"><option>Perro</option><option>Gato</option><option>Otro</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Raza</label><input type="text" id="petRaza" placeholder="Ej: Labrador"></div>
      <div class="form-col input-group"><label>Dueño</label><input type="text" id="petDuenio" placeholder="Nombre del cliente"></div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Edad (años)</label><input type="number" id="petEdad" placeholder="3"></div>
      <div class="form-col input-group"><label>Peso (kg)</label><input type="number" id="petPeso" placeholder="5.5"></div>
    </div>
    <div class="input-group"><label>Alergias / Restricciones</label><textarea id="petAlergias" placeholder="Ej: Alérgico al polvo..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevaMascota')">Cancelar</button>
      <button class="btn btn-teal" onclick="guardarMascota()">✅ Guardar Mascota</button>
    </div>
  </div>
</div>

<div class="modal-overlay hidden" id="modalEditMascota">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">✏️ Editar Mascota</h3><button class="modal-close" onclick="closeModal('modalEditMascota')">×</button></div>
    <input type="hidden" id="editMascotaId">
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre</label><input type="text" id="editMascotaNombre" placeholder="Ej: Luna"></div>
      <div class="form-col input-group"><label>Especie</label>
        <select id="editMascotaEspecie"><option value="perro">Perro</option><option value="gato">Gato</option><option value="otro">Otro</option></select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Raza</label><input type="text" id="editMascotaRaza" placeholder="Ej: Labrador"></div>
      <div class="form-col input-group"><label>Dueño</label><input type="text" id="editMascotaDuenio" placeholder="Nombre del cliente"></div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Edad (años)</label><input type="number" id="editMascotaEdad"></div>
      <div class="form-col input-group"><label>Peso (kg)</label><input type="number" id="editMascotaPeso" step="0.1"></div>
    </div>
    <div class="input-group"><label>Alergias / Restricciones</label><textarea id="editMascotaAlergias" rows="2"></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalEditMascota')">Cancelar</button>
      <button class="btn btn-teal" onclick="updateMascotaBD()">💾 Actualizar</button>
    </div>
  </div>
</div>

<!-- Modal Nuevo Producto -->
<div class="modal-overlay hidden" id="modalNuevoProducto">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title" id="modalProdTitle">📦 Nuevo Producto</h3><button class="modal-close" onclick="closeModal('modalNuevoProducto')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre del producto</label><input type="text" id="prodNombre" placeholder="Ej: Shampoo Lavanda"></div>
      <div class="form-col input-group"><label>Categoría</label>
        <select id="prodCat"><option value="alimentos">Alimentos</option><option value="shampoo">Shampoo</option><option value="juguetes">Juguetes</option><option value="accesorios">Accesorios</option></select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Precio (Bs.)</label><input type="number" id="prodPrecio" placeholder="0.00"></div>
      <div class="form-col input-group"><label>Stock inicial</label><input type="number" id="prodStock" placeholder="0"></div>
    </div>
    <div class="input-group"><label>Variante / Presentación</label><input type="text" id="prodVariante" placeholder="Ej: 250ml, 3kg"></div>
    <input type="hidden" id="prodEditIdx" value="">
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevoProducto')">Cancelar</button>
      <button class="btn btn-teal" onclick="guardarProducto()">✅ Guardar Producto</button>
    </div>
  </div>
</div>

<!-- Modal Nuevo Pago -->
<div class="modal-overlay hidden" id="modalNuevoPago">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">💳 Nuevo Cobro</h3><button class="modal-close" onclick="closeModal('modalNuevoPago')">×</button></div>
    <div class="input-group"><label>Cita / Pedido</label>
      <select><option>#001 — Mishi (Ana Torres) — Completo Bs.120</option><option>#002 — Rocky (Juana Pérez) — B+Corte Bs.85</option></select>
    </div>
    <div class="form-row">
      <div class="form-col input-group"><label>Subtotal servicio</label><input type="number" value="120"></div>
      <div class="form-col input-group"><label>Productos adicionales</label><input type="number" value="0"></div>
    </div>
    <div class="input-group"><label>Método de pago</label>
      <select><option>💵 Efectivo</option><option>🔲 QR / Transferencia</option><option>💳 Tarjeta (POS)</option></select>
    </div>
    <div style="background:var(--cream);border-radius:8px;padding:14px;text-align:center;margin-top:8px">
      <div style="font-size:.8rem;color:var(--gray)">TOTAL A COBRAR</div>
      <div style="font-size:2rem;font-family:'Playfair Display',serif;font-weight:700;color:var(--teal)">Bs. 120</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevoPago')">Cancelar</button>
      <button class="btn btn-teal" onclick="procesarPago()">✅ Registrar Pago</button>
    </div>
  </div>
</div>

<!-- Modal Mov Inventario -->
<div class="modal-overlay hidden" id="modalMovInventario">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">📦 Movimiento de Inventario</h3><button class="modal-close" onclick="closeModal('modalMovInventario')">×</button></div>
    <div class="input-group"><label>Tipo de movimiento</label>
      <select id="movTipo"><option value="entrada">📥 Entrada (reposición)</option><option value="salida">📤 Salida (venta/uso)</option><option value="ajuste">🔄 Ajuste de inventario</option></select>
    </div>
    <div class="input-group"><label>Producto</label><select id="movProducto"></select></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Cantidad</label><input type="number" id="movCantidad" placeholder="0" min="1"></div>
      <div class="form-col input-group"><label>Motivo</label><input type="text" id="movMotivo" placeholder="Ej: Reposición mensual"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalMovInventario')">Cancelar</button>
      <button class="btn btn-teal" onclick="registrarMovimiento()">✅ Registrar</button>
    </div>
  </div>
</div>

<!-- Modal Bloqueo -->
<div class="modal-overlay hidden" id="modalBloqueo">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">🚫 Bloquear Horario</h3><button class="modal-close" onclick="closeModal('modalBloqueo')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Fecha inicio</label><input type="date"></div>
      <div class="form-col input-group"><label>Fecha fin</label><input type="date"></div>
    </div>
    <div class="input-group"><label>Motivo</label><input type="text" placeholder="Ej: Feriado, mantenimiento..."></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalBloqueo')">Cancelar</button>
      <button class="btn btn-danger" onclick="bloquearHorario()">🚫 Bloquear</button>
    </div>
  </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal-overlay hidden" id="modalNuevoUsuario">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">🔐 Nuevo Usuario</h3><button class="modal-close" onclick="closeModal('modalNuevoUsuario')">×</button></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Nombre completo</label><input type="text" id="usrNombre" placeholder="Nombre"></div>
      <div class="form-col input-group"><label>Rol</label>
        <select id="usrRol"><option value="admin">Administrador</option><option value="recep">Recepcionista</option><option value="groo">Groomer</option><option value="client">Cliente</option></select>
      </div>
    </div>
    <div class="input-group"><label>Email (usuario)</label><input type="email" id="usrEmail" placeholder="usuario@pawspa.com"></div>
    <div class="form-row">
      <div class="form-col input-group"><label>Contraseña</label><input type="password" id="usrPass" placeholder="Mínimo 8 caracteres"></div>
      <div class="form-col input-group"><label>Confirmar</label><input type="password" id="usrPass2" placeholder="Repetir contraseña"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalNuevoUsuario')">Cancelar</button>
      <button class="btn btn-teal" onclick="crearUsuario()">✅ Crear Usuario</button>
    </div>
  </div>
</div>

<!-- Modal Mascota Cliente -->
<div class="modal-overlay hidden" id="modalMascotaCliente">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalMascotaClienteTitle">➕ Nueva Mascota</h3>
            <button class="modal-close" onclick="closeModal('modalMascotaCliente')">×</button>
        </div>
        <input type="hidden" id="mascotaClienteId">
        <div class="form-row">
            <div class="form-col input-group">
                <label>Nombre *</label>
                <input type="text" id="mascotaClienteNombre" placeholder="Ej: Luna">
            </div>
            <div class="form-col input-group">
                <label>Especie</label>
                <select id="mascotaClienteEspecie">
                    <option value="perro">🐕 Perro</option>
                    <option value="gato">🐈 Gato</option>
                    <option value="otro">🐾 Otro</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-col input-group">
                <label>Raza</label>
                <input type="text" id="mascotaClienteRaza" placeholder="Ej: Labrador">
            </div>
        </div>
        <div class="form-row">
            <div class="form-col input-group">
                <label>Edad (años)</label>
                <input type="number" id="mascotaClienteEdad" placeholder="3" step="1" min="0">
            </div>
            <div class="form-col input-group">
                <label>Peso (kg)</label>
                <input type="number" id="mascotaClientePeso" placeholder="5.5" step="0.1" min="0">
            </div>
        </div>
        <div class="input-group">
            <label>Alergias / Restricciones médicas</label>
            <textarea id="mascotaClienteAlergias" rows="2" placeholder="Ej: Alérgico al polvo, problemas de cadera..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modalMascotaCliente')">Cancelar</button>
            <button class="btn btn-teal" onclick="guardarMascotaCliente()">💾 Guardar Mascota</button>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- CART PANEL, TOAST, MODALS (copiar de tu archivo) -->
<div id="toast"></div>

<script>
// ============================================
// CONFIGURACIÓN
// ============================================
const API_URL = '/pawspa/api/';
const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const IS_CLIENT = <?php echo $isClient ? 'true' : 'false'; ?>;
const IS_RECEP = <?php echo $isRecep ? 'true' : 'false'; ?>;
const IS_GROOMER = <?php echo $isGroomer ? 'true' : 'false'; ?>;

// ============================================
// STORAGE — localStorage persistence
// ============================================

let DB = {
    productos: [],
    clientes: [],
    citas: [],
    usuarios: [],
    mascotas: [],
    config: {},
    logs: [],  // logs pueden seguir en localStorage
    nextCitaId: 1,
    nextProdId: 10
};

async function cargarDatosDesdeBD() {
    try {
        const response = await fetch(API_URL + 'get_all_data.php');
        const data = await response.json();
        
        if (data.success) {
            DB.productos = data.data.productos || [];
            DB.citas = data.data.citas || [];
            DB.usuarios = data.data.usuarios || [];
            DB.mascotas = data.data.mascotas || [];
            DB.config = { ...DB.config, ...data.data.config };
            
            // Cargar clientes
            await cargarClientes();
            
            // Cargar logs desde BD
            await cargarLogsDesdeBD();
            
            // Renderizar
            if (typeof renderProductos === 'function') renderProductos('todos');
            if (typeof renderCitas === 'function') renderCitas();
            if (typeof renderUsuarios === 'function') renderUsuarios();
            if (typeof renderMascotas === 'function') renderMascotas();
            if (typeof renderInventario === 'function') renderInventario();
            
            console.log('✅ Datos cargados desde la base de datos');
        }
    } catch (error) {
        console.error('Error de conexión:', error);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Guardar nuevo cliente en BD
async function guardarClienteBD() {
    const nombre = document.getElementById('cliNombre').value.trim();
    const telefono = document.getElementById('cliTel').value.trim();
    const email = document.getElementById('cliEmail').value.trim();
    
    if (!nombre || !email) {
        showToast('Nombre y email son requeridos', 'error');
        return;
    }
    
    const btn = document.querySelector('#modalNuevoCliente .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, telefono, email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente registrado correctamente', 'success');
            closeModal('modalNuevoCliente');
            await cargarDatosDesdeBD(); // Recargar todos los datos
            // Limpiar formulario
            document.getElementById('cliNombre').value = '';
            document.getElementById('cliTel').value = '';
            document.getElementById('cliEmail').value = '';
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Abrir modal de edición
async function openEditClienteBD(id) {
    // Buscar el cliente en DB.clientes (que ya está cargado)
    const cliente = DB.clientes.find(c => c.id === id);
    if (!cliente) return;
    
    document.getElementById('editCliId').value = cliente.id;
    document.getElementById('editCliNombre').value = cliente.nombre;
    document.getElementById('editCliTel').value = cliente.telefono || '';
    document.getElementById('editCliEmail').value = cliente.email;
    document.getElementById('editCliNivel').value = cliente.nivel || 'Bronze';
    
    openModal('modalEditCliente');
}

// Actualizar cliente en BD
async function updateClienteBD() {
    const id = parseInt(document.getElementById('editCliId').value);
    const nombre = document.getElementById('editCliNombre').value.trim();
    const telefono = document.getElementById('editCliTel').value.trim();
    const email = document.getElementById('editCliEmail').value.trim();
    const nivel = document.getElementById('editCliNivel').value;
    
    if (!nombre || !email) {
        showToast('Nombre y email son requeridos', 'error');
        return;
    }
    
    const btn = document.querySelector('#modalEditCliente .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Actualizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, telefono, email, nivel })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente actualizado correctamente', 'success');
            closeModal('modalEditCliente');
            await cargarDatosDesdeBD(); // Recargar todos los datos
        } else {
            showToast(data.error || 'Error al actualizar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Eliminar cliente de BD
async function deleteClienteBD(id) {
    const cliente = DB.clientes.find(c => c.id === id);
    if (!confirm(`¿Eliminar al cliente "${cliente?.nombre}"? Esta acción no se puede deshacer.`)) return;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente eliminado correctamente', 'success');
            await cargarDatosDesdeBD(); // Recargar todos los datos
        } else {
            showToast(data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

// Eliminar o comentar saveDB() si ya no la usas
function saveDB() {
    // Ya no se usa para datos principales, solo para logs
    saveLogs();
}

// ============================================
// LOGS
// ============================================
const LOG_ICONS = { login:'🔐', logout:'🚪', create:'➕', edit:'✏️', delete:'🗑️', error:'❌', pay:'💳', move:'📦' };
function addLog(type, action, detail, user) {
  const entry = {
    id: Date.now(),
    type,
    action,
    detail,
    user: user || (currentUser ? currentUser.nombre : 'Sistema'),
    role: currentRole,
    ts: new Date().toLocaleString('es-BO'),
  };
  DB.logs.unshift(entry);
  if (DB.logs.length > 500) DB.logs = DB.logs.slice(0,500);
  saveDB();
  if (document.getElementById('page-logs').classList.contains('active')) renderLogs('all');
}

async function cargarLogsDesdeBD() {
    try {
        const response = await fetch(API_URL + 'ver_logs.php?format=json');
        const data = await response.json();
        
        if (data.success) {
            DB.logs = data.logs;
            renderLogs(logsFilter);
            console.log('✅ Logs cargados:', DB.logs.length);
        }
    } catch (error) {
        console.error('Error cargando logs:', error);
    }
}

function renderLogs(filter) {
    logsFilter = filter;
    const container = document.getElementById('logsContainer');
    if (!container) return;
    
    const items = filter === 'all' ? DB.logs : DB.logs.filter(l => l.tipo === filter);
    document.getElementById('logCount').textContent = `${items.length} registro${items.length !== 1 ? 's' : ''}`;
    
    if (!items.length) {
        container.innerHTML = '<p style="color:var(--gray);text-align:center;padding:40px 0">Sin registros para el filtro seleccionado</p>';
        return;
    }
    
    container.innerHTML = items.map(l => `
        <div class="log-entry ${l.tipo}">
            <div class="log-icon">${LOG_ICONS[l.tipo] || '📝'}</div>
            <div class="log-info">
                <div class="log-action">${escapeHtml(l.accion)}</div>
                <div class="log-detail">${escapeHtml(l.detalle)}</div>
            </div>
            <div class="log-meta">
                <div class="log-user">${escapeHtml(l.usuario_nombre || 'Sistema')}</div>
                <div>${l.usuario_rol || ''}</div>
                <div style="margin-top:4px">${l.fecha || l.ts}</div>
            </div>
        </div>
    `).join('');
}

let logsFilter = 'all';
function renderLogs(filter) {
  logsFilter = filter;
  const container = document.getElementById('logsContainer');
  const items = filter === 'all' ? DB.logs : DB.logs.filter(l => l.type === filter);
  document.getElementById('logCount').textContent = `${items.length} registro${items.length !== 1 ? 's' : ''}`;
  if (!items.length) {
    container.innerHTML = '<p style="color:var(--gray);text-align:center;padding:40px 0">Sin registros para el filtro seleccionado</p>';
    return;
  }
  container.innerHTML = items.map(l => `
    <div class="log-entry ${l.type}">
      <div class="log-icon">${LOG_ICONS[l.type] || '📝'}</div>
      <div class="log-info">
        <div class="log-action">${l.action}</div>
        <div class="log-detail">${l.detail}</div>
      </div>
      <div class="log-meta">
        <div class="log-user">${l.user}</div>
        <div>${l.role || ''}</div>
        <div style="margin-top:4px">${l.ts}</div>
      </div>
    </div>`).join('');
}

function filterLogs(filter, el) {
  document.querySelectorAll('.log-filter-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  renderLogs(filter);
}

function clearLogs() {
  if (!confirm('¿Limpiar todos los registros de actividad?')) return;
  DB.logs = [];
  saveDB();
  addLog('edit','Logs limpiados','El administrador limpió el historial de actividad');
  renderLogs('all');
  showToast('Logs limpiados ✓','success');
}

function exportLogs() {
  const lines = DB.logs.map(l => `[${l.ts}] [${l.type.toUpperCase()}] [${l.user}] ${l.action} — ${l.detail}`).join('\n');
  const blob = new Blob([lines], {type:'text/plain'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `pawspa_logs_${new Date().toISOString().slice(0,10)}.txt`;
  a.click();
  showToast('Logs exportados ✓','success');
}

// ============================================
// ROLES & NAV CONFIG
// ============================================
const ROLES_META = {
  admin:  { label:'Administrador', avatar:'👑', avClass:'av-admin', greeting:'Panel de Administración' },
  recep:  { label:'Recepcionista', avatar:'🗓️', avClass:'av-recep', greeting:'Panel de Recepción' },
  groo:   { label:'Groomer', avatar:'✂️', avClass:'av-groo', greeting:'Mi Agenda de Grooming' },
  client: { label:'Cliente', avatar:'🐶', avClass:'av-client', greeting:'Mi Espacio PawSpa' },
};
const NAV_CONFIG = {
  admin: [
    { section:'Principal' }, { id:'dashboard', icon:'📊', label:'Dashboard' },
    { section:'Operaciones' }, { id:'agenda', icon:'🗓️', label:'Agenda', badge:'6' },
    { id:'grooming', icon:'✂️', label:'Grooming' }, { id:'clientes', icon:'👥', label:'Clientes & Mascotas' },
    { section:'Tienda' }, { id:'catalogo', icon:'🛍️', label:'Catálogo & Tienda' },
    { section:'Gestión' }, { id:'pagos', icon:'💳', label:'Pagos & Facturación' },
    { id:'inventario', icon:'📦', label:'Inventario' }, { id:'reportes', icon:'📈', label:'Reportes' },
    { section:'Sistema' }, { id:'logs', icon:'📋', label:'Registro de Actividad' }, { id:'config', icon:'⚙️', label:'Configuración' },
  ],
  recep: [
    { section:'Principal' }, { id:'dashboard', icon:'📊', label:'Dashboard' },
    { section:'Operaciones' }, { id:'agenda', icon:'🗓️', label:'Agenda', badge:'6' },
    { id:'clientes', icon:'👥', label:'Clientes & Mascotas' }, { section:'Cobros' }, { id:'pagos', icon:'💳', label:'Pagos & Cobros' },
    { id:'logs', icon:'📋', label:'Actividad' },
  ],
  groo: [
    { section:'Mi Día' }, { id:'dashboard', icon:'📊', label:'Mi Dashboard' },
    { id:'agenda', icon:'🗓️', label:'Mi Agenda', badge:'3' }, { section:'Servicio' },
    { id:'grooming', icon:'✂️', label:'Fichas de Grooming' }, { id:'clientes', icon:'👥', label:'Mis Mascotas' },
  ],
  client: [
    { section:'Mi Espacio' }, { id:'micuenta', icon:'🐾', label:'Mi Cuenta' }, { id:'catalogo', icon:'🛍️', label:'Tienda' },
  ],
};
const STATS_CONFIG = {
  admin: [
    { label:'Citas Hoy', value:'6', change:'+2 vs ayer', dir:'up', color:'teal', icon:'📅' },
    { label:'Ingresos del Día', value:'Bs.980', change:'+12%', dir:'up', color:'caramel', icon:'💰' },
    { label:'Ocupación Groomers', value:'58%', change:'3 activos', dir:'', color:'gold', icon:'✂️' },
    { label:'Ventas Tienda', value:'Bs.340', change:'8 productos', dir:'up', color:'rust', icon:'🛍️' },
  ],
  recep: [
    { label:'Citas Hoy', value:'6', change:'2 pendientes', dir:'', color:'teal', icon:'📅' },
    { label:'Cobros Pendientes', value:'Bs.180', change:'2 citas', dir:'down', color:'rust', icon:'⏳' },
    { label:'Clientes Nuevos', value:'2', change:'Esta semana', dir:'up', color:'caramel', icon:'👤' },
    { label:'Confirmadas', value:'4', change:'de 6 citas', dir:'up', color:'gold', icon:'✅' },
  ],
  groo: [
    { label:'Mis Citas Hoy', value:'3', change:'1 completada', dir:'', color:'teal', icon:'📅' },
    { label:'En Progreso', value:'1', change:'Mishi', dir:'', color:'caramel', icon:'⏳' },
    { label:'Mis Citas Semana', value:'14', change:'82% ocupación', dir:'up', color:'gold', icon:'📈' },
    { label:'Checklist Pendiente', value:'2', change:'ítems por marcar', dir:'down', color:'rust', icon:'✅' },
  ],
  client: [
    { label:'Próxima Cita', value:'17/05', change:'10:00 AM', dir:'', color:'teal', icon:'📅' },
    { label:'Mis Mascotas', value:'1', change:'Mishi', dir:'', color:'caramel', icon:'🐾' },
    { label:'Visitas Totales', value:'8', change:'Desde 2024', dir:'up', color:'gold', icon:'🏆' },
    { label:'Puntos Gold', value:'1,240', change:'Nivel Gold ⭐', dir:'up', color:'rust', icon:'⭐' },
  ],
};

let currentRole = '<?php echo $isLoggedIn ? $currentUser['rol'] : 'admin'; ?>';
let currentUser = <?php echo $isLoggedIn ? json_encode($currentUser) : 'null'; ?>;
let currentPage = 'dashboard';
let cart = {};
let currentFilter = 'todos';

// ============================================
// LOGIN / AUTH (PHP ya maneja la sesión)
// ============================================
function showLoginError(msg) {
  const el = document.getElementById('loginError');
  if (el) {
    el.textContent = '❌ ' + msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 5000);
  }
}

// ============================================
// LOGIN FUNCTIONS
// ============================================
async function doLogin() {
    const email = document.getElementById('loginUser').value;
    const password = document.getElementById('loginPass').value;
    const role = document.getElementById('loginRole').value;
    const captchaToken = grecaptcha ? grecaptcha.getResponse() : '';
    
    const errorDiv = document.getElementById('loginError');
    const intentosDiv = document.getElementById('intentosRestantes');
    
    errorDiv.style.display = 'none';
    if (intentosDiv) intentosDiv.style.display = 'none';
    
    if (!email || !password) {
        showLoginError('Por favor ingresa email y contraseña');
        return;
    }
    
    if (!captchaToken) {
        showLoginError('Por favor completa el reCAPTCHA');
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
            body: JSON.stringify({ email, password, role, captcha_token: captchaToken })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Recargar la página para que PHP detecte la nueva sesión
            window.location.href = 'index.php';
        } else {
            // Mostrar error con información de intentos restantes si está disponible
            let errorMessage = data.error || 'Error de autenticación';
            
            // Si hay información de intentos restantes, mostrarla
            if (data.intentos_restantes !== undefined) {
                errorMessage += `\n⚠️ Te quedan ${data.intentos_restantes} intentos antes del bloqueo.`;
                
                // Mostrar en el div específico de intentos
                if (intentosDiv) {
                    intentosDiv.textContent = `⚠️ Te quedan ${data.intentos_restantes} intentos antes del bloqueo por 15 minutos.`;
                    intentosDiv.style.display = 'block';
                    intentosDiv.style.background = '#FFF3E8';
                    intentosDiv.style.color = '#C9956A';
                    intentosDiv.style.padding = '8px 12px';
                    intentosDiv.style.borderRadius = '8px';
                    intentosDiv.style.marginTop = '8px';
                    intentosDiv.style.fontSize = '.75rem';
                    intentosDiv.style.textAlign = 'center';
                }
            }
            
            // Si la cuenta está bloqueada, mostrar mensaje especial
            if (data.bloqueado === true) {
                errorMessage = '❌ Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.';
                if (intentosDiv) {
                    intentosDiv.textContent = '🔒 Cuenta temporalmente bloqueada. Espera 15 minutos para intentar nuevamente.';
                    intentosDiv.style.display = 'block';
                    intentosDiv.style.background = '#FFEDED';
                    intentosDiv.style.color = '#C4532A';
                }
            }
            
            showLoginError(errorMessage);
            
            // Limpiar el campo de contraseña por seguridad
            document.getElementById('loginPass').value = '';
            document.getElementById('loginPass').focus();
            
            if (grecaptcha) grecaptcha.reset();
        }
    } catch (error) {
        console.error('Error:', error);
        showLoginError('Error de conexión con el servidor');
        if (grecaptcha) grecaptcha.reset();
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

function showLoginError(msg) {
    const el = document.getElementById('loginError');
    if (el) {
        el.innerHTML = msg.replace(/\n/g, '<br>');
        el.style.display = 'block';
        setTimeout(() => {
            el.style.display = 'none';
            // Limpiar mensaje de intentos después de 5 segundos también
            const intentosDiv = document.getElementById('intentosRestantes');
            if (intentosDiv) intentosDiv.style.display = 'none';
        }, 8000);
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
    // Simular captcha para demo
    if (typeof grecaptcha !== 'undefined') {
        // No reseteamos, solo continuamos
    }
    doLogin();
}


function doLogout() {
    fetch(API_URL + 'logout.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).finally(() => {
        window.location.href = 'index.php';
    });
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', async () => {
    await cargarDatosDesdeBD();
    
    if (IS_ADMIN || IS_RECEP || IS_GROOMER) {
        initApp(currentRole);
    } else if (IS_CLIENT) {
        initApp('client');
    }
});

// ============================================
// NAVEGACIÓN
// ============================================
function navigateTo(pageId) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const page = document.getElementById('page-' + pageId);
  if (page) page.classList.add('active');
  const navItem = document.getElementById('nav-' + pageId);
  if (navItem) navItem.classList.add('active');
  currentPage = pageId;
  document.getElementById('topbarSection').textContent = '';
  if (pageId === 'logs') renderLogs(logsFilter);
  if (pageId === 'inventario') { populateMovProductos(); }
}

function buildSidebar(role) {
  const nav = NAV_CONFIG[role];
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;
  sidebar.innerHTML = '';
  nav.forEach(item => {
    if (item.section) {
      sidebar.innerHTML += `<div class="sidebar-section">${item.section}</div>`;
    } else {
      const badge = item.badge ? `<span class="nav-badge">${item.badge}</span>` : '';
      sidebar.innerHTML += `<div class="nav-item" id="nav-${item.id}" onclick="navigateTo('${item.id}')">
        <span class="nav-icon">${item.icon}</span>${item.label}${badge}</div>`;
    }
  });
}

function buildStats(role) {
  const grid = document.getElementById('statsGrid');
  if (!grid) return;
  const stats = STATS_CONFIG[role];
  grid.innerHTML = stats.map(s => `
    <div class="stat-card ${s.color}">
      <div class="stat-icon">${s.icon}</div>
      <div class="stat-label">${s.label}</div>
      <div class="stat-value">${s.value}</div>
      <div class="stat-change ${s.dir}">${s.dir==='up'?'↑ ':s.dir==='down'?'↓ ':''}${s.change}</div>
    </div>`).join('');
}

function buildCalendar() {
  const grid = document.getElementById('calendarGrid');
  if (!grid) return;
  const days = ['', 'Lunes 5', 'Martes 6', 'Miércoles 7', 'Jueves 8', 'Viernes 9'];
  const hours = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
  const events = {
    '09:00-0': '<div class="cal-event">Mishi — Completo</div>',
    '10:00-1': '<div class="cal-event caramel">Max — Rápido</div>',
    '11:00-0': '<div class="cal-event">Rocky — B+C</div>',
    '14:00-1': '<div class="cal-event caramel">Coco — Rápido</div>',
    '12:30-0': '<div style="font-size:.7rem;color:var(--rust);padding:4px">🚫 Almuerzo</div>',
  };
  let html = days.map(d => `<div class="cal-header">${d}</div>`).join('');
  hours.forEach(h => {
    html += `<div class="cal-time">${h}</div>`;
    for (let i = 0; i < 5; i++) {
      const key = `${h}-${i%3}`;
      const hasEvent = events[key];
      html += `<div class="cal-slot ${hasEvent?'occupied':''}" onclick="showToast('Slot ${h} seleccionado','success')">${hasEvent||''}</div>`;
    }
  });
  grid.innerHTML = html;
}

function initApp(role) {
  const r = ROLES_META[role];
  const av = document.getElementById('userAvatar');
  if (av) {
    av.textContent = r.avatar;
    av.className = `user-avatar ${r.avClass}`;
  }
  document.getElementById('userName').textContent = currentUser ? currentUser.nombre : r.label;
  document.getElementById('userRoleLabel').textContent = r.label;
  document.getElementById('dashTitle').textContent = r.greeting;
  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('es-BO',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
  buildSidebar(role);
  buildStats(role);
  buildCalendar();
  renderProducts('todos');
  renderClientes();
  renderInventario();
  renderUsuarios();
  renderCitas();
  const btnAdd = document.getElementById('btnAddProduct');
  if (btnAdd) btnAdd.style.display = role === 'admin' ? '' : 'none';
  startTimer();
  const defaultPage = role === 'client' ? 'micuenta' : 'dashboard';
  navigateTo(defaultPage);

  if (role === 'client') {
  cargarMascotasCliente();  // <-- AGREGAR ESTA LÍNEA
  }

}

// ============================================
// CLIENTES CRUD - VERSIÓN CON BASE DE DATOS
// ============================================

// Cargar clientes desde la BD (llamar a esta función después de cargar datos)
async function cargarClientes() {
    try {
        const response = await fetch(API_URL + 'clientes.php');
        const data = await response.json();
        
        if (data.success) {
            DB.clientes = data.clientes;
            renderClientes();
            console.log('✅ Clientes cargados:', DB.clientes.length);
        } else {
            console.error('Error cargando clientes:', data.error);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showToast('Error al cargar clientes', 'error');
    }
}

// Renderizar tabla de clientes
function renderClientes() {
    const tbody = document.getElementById('clientesBody');
    if (!tbody) {
        console.log('No se encontró el elemento clientesBody');
        return;
    }
    
    console.log('Renderizando clientes, cantidad:', DB.clientes?.length);
    
    if (!DB.clientes || DB.clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading">No hay clientes registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = DB.clientes.map(c => {
        const nivelBadge = c.nivel === 'Gold' ? 'badge-teal' : (c.nivel === 'Silver' ? 'badge-orange' : 'badge-gray');
        const nivelEmoji = c.nivel === 'Gold' ? '⭐' : (c.nivel === 'Silver' ? '🥈' : '🥉');
        
        return `<tr>
            <td><strong>${escapeHtml(c.nombre)}</strong></td>
            <td>${c.telefono || '-'}</td>
            <td>${c.email}</td>
            <td>${c.total_mascotas || 0}</td>
            <td>${c.ultima_visita || 'Nunca'}</td>
            <td><span class="badge ${nivelBadge}">${nivelEmoji} ${c.nivel || 'Bronze'}</span></td>
            <td style="display:flex; gap:4px">
                <button class="btn btn-sm btn-outline" onclick="openEditClienteBD(${c.id})">✏️ Editar</button>
                <button class="btn btn-sm btn-danger" onclick="deleteClienteBD(${c.id})">🗑️ Eliminar</button>
            </td>
        </tr>`;
    }).join('');
}

// Guardar nuevo cliente en BD
async function guardarCliente() {
    const nombre = document.getElementById('cliNombre').value.trim();
    const telefono = document.getElementById('cliTel').value.trim();
    const email = document.getElementById('cliEmail').value.trim();
    
    if (!nombre || !email) {
        showToast('Nombre y email son requeridos', 'error');
        return;
    }
    
    const btn = document.querySelector('#modalNuevoCliente .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, telefono, email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente registrado correctamente', 'success');
            closeModal('modalNuevoCliente');
            await cargarClientes(); // Recargar lista
            // Limpiar formulario
            document.getElementById('cliNombre').value = '';
            document.getElementById('cliTel').value = '';
            document.getElementById('cliEmail').value = '';
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Abrir modal de edición
async function openEditCliente(id) {
    const cliente = DB.clientes.find(c => c.id === id);
    if (!cliente) return;
    
    document.getElementById('editCliId').value = cliente.id;
    document.getElementById('editCliNombre').value = cliente.nombre;
    document.getElementById('editCliTel').value = cliente.telefono || '';
    document.getElementById('editCliEmail').value = cliente.email;
    document.getElementById('editCliNivel').value = cliente.nivel || 'Bronze';
    
    openModal('modalEditCliente');
}

// Actualizar cliente en BD
async function updateCliente() {
    const id = parseInt(document.getElementById('editCliId').value);
    const nombre = document.getElementById('editCliNombre').value.trim();
    const telefono = document.getElementById('editCliTel').value.trim();
    const email = document.getElementById('editCliEmail').value.trim();
    const nivel = document.getElementById('editCliNivel').value;
    
    if (!nombre || !email) {
        showToast('Nombre y email son requeridos', 'error');
        return;
    }
    
    const btn = document.querySelector('#modalEditCliente .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Actualizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, telefono, email, nivel })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente actualizado correctamente', 'success');
            closeModal('modalEditCliente');
            await cargarClientes(); // Recargar lista
        } else {
            showToast(data.error || 'Error al actualizar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Eliminar cliente de BD
async function deleteCliente(id) {
    const cliente = DB.clientes.find(c => c.id === id);
    if (!confirm(`¿Eliminar al cliente "${cliente?.nombre}"? Esta acción no se puede deshacer.`)) return;
    
    try {
        const response = await fetch(API_URL + 'clientes.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Cliente eliminado correctamente', 'success');
            await cargarClientes(); // Recargar lista
        } else {
            showToast(data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

// Filtrar clientes en la tabla
function filterClients(val) {
    const rows = document.querySelectorAll('#tab-clientes tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val.toLowerCase()) ? '' : 'none';
    });
}

// ============================================
// MASCOTAS CRUD - CONEXIÓN A BD
// ============================================

// Cargar mascotas desde la BD
async function cargarMascotas() {
    try {
        const response = await fetch(API_URL + 'get_all_data.php');
        const data = await response.json();
        
        if (data.success) {
            DB.mascotas = data.data.mascotas || [];
            renderMascotas();
            console.log('✅ Mascotas cargadas:', DB.mascotas.length);
        }
    } catch (error) {
        console.error('Error cargando mascotas:', error);
    }
}

// Renderizar tarjetas de mascotas
function renderMascotas() {
    const grid = document.getElementById('mascotasGrid');
    if (!grid) return;
    
    if (!DB.mascotas || DB.mascotas.length === 0) {
        grid.innerHTML = '<p class="loading">No hay mascotas registradas</p>';
        return;
    }
    
    const emojiMap = { 'perro': '🐕', 'gato': '🐈', 'otro': '🐾' };
    
    grid.innerHTML = DB.mascotas.map(m => `
        <div class="pet-card">
            <div class="pet-avatar">${emojiMap[m.especie?.toLowerCase()] || '🐾'}</div>
            <div class="pet-info" style="flex:1">
                <h4>${escapeHtml(m.nombre)}</h4>
                <p>${escapeHtml(m.raza || 'Sin raza')} · ${m.especie || 'Mascota'} · ${m.edad || '?'} años · ${m.peso || '?'} kg</p>
                <div class="pet-tags">
                    ${m.alergias ? `<span class="badge badge-red">⚠️ ${escapeHtml(m.alergias)}</span>` : ''}
                    <span class="badge badge-blue">💉 Al día</span>
                </div>
                <div style="margin-top:8px; font-size:.8rem; color:var(--gray)">Dueño: ${escapeHtml(m.duenio_nombre || 'Sin dueño')}</div>
            </div>
            <div class="pet-actions" style="display:flex; gap:5px">
                <button class="btn btn-sm btn-outline" onclick="openEditMascota(${m.id})">✏️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteMascotaBD(${m.id})">🗑️</button>
            </div>
        </div>
    `).join('');
}

// Guardar nueva mascota en BD
async function guardarMascotaBD() {
    const nombre = document.getElementById('petNombre').value.trim();
    const especie = document.getElementById('petEspecie').value;
    const raza = document.getElementById('petRaza').value.trim();
    const duenio = document.getElementById('petDuenio').value.trim();
    const edad = parseInt(document.getElementById('petEdad').value) || 0;
    const peso = parseFloat(document.getElementById('petPeso').value) || 0;
    const alergias = document.getElementById('petAlergias').value;
    
    if (!nombre || !duenio) {
        showToast('Nombre y dueño son requeridos', 'error');
        return;
    }
    
    // Buscar el ID del cliente por nombre
    const cliente = DB.clientes.find(c => c.nombre.toLowerCase() === duenio.toLowerCase());
    if (!cliente) {
        showToast(`No se encontró el cliente "${duenio}". Verifica el nombre.`, 'error');
        return;
    }
    
    const btn = document.querySelector('#modalNuevaMascota .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php?action=mascota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre, especie, raza, cliente_id: cliente.id,
                edad, peso, alergias, temperamento: 'tranquilo'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Mascota registrada correctamente', 'success');
            closeModal('modalNuevaMascota');
            await cargarMascotas(); // Recargar lista
            // Limpiar formulario
            document.getElementById('petNombre').value = '';
            document.getElementById('petRaza').value = '';
            document.getElementById('petDuenio').value = '';
            document.getElementById('petEdad').value = '';
            document.getElementById('petPeso').value = '';
            document.getElementById('petAlergias').value = '';
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Abrir modal para editar mascota
async function openEditMascota(id) {
    const mascota = DB.mascotas.find(m => m.id === id);
    if (!mascota) return;
    
    document.getElementById('editMascotaId').value = mascota.id;
    document.getElementById('editMascotaNombre').value = mascota.nombre;
    document.getElementById('editMascotaEspecie').value = mascota.especie || 'perro';
    document.getElementById('editMascotaRaza').value = mascota.raza || '';
    document.getElementById('editMascotaDuenio').value = mascota.duenio_nombre || '';
    document.getElementById('editMascotaEdad').value = mascota.edad || '';
    document.getElementById('editMascotaPeso').value = mascota.peso || '';
    document.getElementById('editMascotaAlergias').value = mascota.alergias || '';
    
    openModal('modalEditMascota');
}

// Actualizar mascota en BD
async function updateMascotaBD() {
    const id = parseInt(document.getElementById('editMascotaId').value);
    const nombre = document.getElementById('editMascotaNombre').value.trim();
    const especie = document.getElementById('editMascotaEspecie').value;
    const raza = document.getElementById('editMascotaRaza').value.trim();
    const duenio = document.getElementById('editMascotaDuenio').value.trim();
    const edad = parseInt(document.getElementById('editMascotaEdad').value) || 0;
    const peso = parseFloat(document.getElementById('editMascotaPeso').value) || 0;
    const alergias = document.getElementById('editMascotaAlergias').value;
    
    if (!nombre || !duenio) {
        showToast('Nombre y dueño son requeridos', 'error');
        return;
    }
    
    // Buscar el ID del cliente
    const cliente = DB.clientes.find(c => c.nombre.toLowerCase() === duenio.toLowerCase());
    if (!cliente) {
        showToast(`No se encontró el cliente "${duenio}"`, 'error');
        return;
    }
    
    const btn = document.querySelector('#modalEditMascota .btn-teal');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Actualizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'clientes.php?action=mascota', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id, nombre, especie, raza, cliente_id: cliente.id,
                edad, peso, alergias, temperamento: 'tranquilo'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Mascota actualizada correctamente', 'success');
            closeModal('modalEditMascota');
            await cargarMascotas();
        } else {
            showToast(data.error || 'Error al actualizar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Eliminar mascota de BD
async function deleteMascotaBD(id) {
    const mascota = DB.mascotas.find(m => m.id === id);
    if (!confirm(`¿Eliminar la mascota "${mascota?.nombre}"?`)) return;
    
    try {
        const response = await fetch(API_URL + 'clientes.php?action=mascota', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Mascota eliminada correctamente', 'success');
            await cargarMascotas();
        } else {
            showToast(data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}
// ============================================
// CITAS CRUD
// ============================================
function renderCitas() {
  const tbody = document.getElementById('citasBody');
  if (!tbody) return;
  const estadoBadge = { 'En progreso':'badge-orange', 'Confirmado':'badge-blue', 'Pendiente':'badge-gray', 'Completado':'badge-green', 'Cancelado':'badge-red' };
  tbody.innerHTML = DB.citas.map((c,i) => `
    <tr>
      <td>${c.id}</td>
      <td>${c.fecha}</td>
      <td>${c.cliente}</td>
      <td>${c.mascota}</td>
      <td>${c.servicio}</td>
      <td>${c.groomer}</td>
      <td><span class="badge ${estadoBadge[c.estado]||'badge-gray'}">${c.estado}</span></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <button class="btn btn-sm btn-outline" onclick="openModal('modalFicha')">Ver Ficha</button>
        ${['admin','recep'].includes(currentRole) ? `<button class="btn btn-sm btn-danger" onclick="cancelarCita(${i})">Cancelar</button>` : ''}
      </td>
    </tr>`).join('');
}

function guardarCita() {
  const cliente = document.getElementById('citaCliente').value.trim();
  const mascota = document.getElementById('citaMascota').value.trim();
  const fecha = document.getElementById('citaFecha').value;
  const hora = document.getElementById('citaHora').value;
  const servicio = document.getElementById('citaServicio').value;
  const groomer = document.getElementById('citaGroomer').value;
  if (!cliente || !mascota || !fecha) { showToast('Complete los campos requeridos','error'); return; }
  const nueva = {
    id: `#00${DB.nextCitaId++}`,
    fecha: `${fecha.slice(5).replace('-','/')} ${hora}`,
    cliente, mascota: `🐾 ${mascota}`, servicio, groomer: groomer.split(' ')[0]+' G.', estado:'Confirmado'
  };
  DB.citas.push(nueva);
  saveDB();
  addLog('create','Cita registrada', `${cliente} — ${mascota} — ${servicio} — ${fecha} ${hora}`);
  renderCitas();
  closeModal('modalNuevaCita');
  showToast('✅ Cita confirmada. Notificación enviada al cliente.','success');
}

function cancelarCita(idx) {
  if (!confirm('¿Cancelar esta cita?')) return;
  DB.citas[idx].estado = 'Cancelado';
  saveDB();
  addLog('edit','Cita cancelada', `Cita ${DB.citas[idx].id} — ${DB.citas[idx].cliente}`);
  renderCitas();
  showToast('Cita cancelada','success');
}

// ============================================
// PRODUCTOS CRUD
// ============================================
function renderProducts(filter) {
  currentFilter = filter;
  const grid = document.getElementById('productsGrid');
  if (!grid) return;
  const prods = filter === 'todos' ? DB.productos : DB.productos.filter(p => p.cat === filter);
  grid.innerHTML = prods.map(p => `
    <div class="product-card">
      <div class="product-img ${p.bgClass}">${p.emoji}</div>
      <div class="product-info">
        <div class="product-name">${p.nombre}</div>
        <div class="product-cat">${p.variante} · ${p.cat}</div>
        <div class="product-price">Bs. ${p.precio}</div>
        <div class="product-stock" style="${p.stock < 5 ? 'color:var(--rust);font-weight:600' : ''}">
          ${p.stock < 5 ? '⚠️ ' : ''}Stock: ${p.stock} unid.
        </div>
        <div class="product-actions">
          <button class="btn btn-sm btn-teal" onclick="addToCart(${p.id})">🛒 Agregar</button>
          ${currentRole === 'admin' ? `<button class="btn btn-sm btn-ghost" onclick="openEditProducto(${p.id})">✏️</button>` : ''}
        </div>
      </div>
    </div>`).join('');
}

function filterCatalog(filter, el) {
  document.querySelectorAll('#page-catalogo .tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderProducts(filter);
}

function openEditProducto(id) {
  const p = DB.productos.find(x => x.id === id);
  if (!p) return;
  document.getElementById('modalProdTitle').textContent = '✏️ Editar Producto';
  document.getElementById('prodNombre').value = p.nombre;
  document.getElementById('prodCat').value = p.cat;
  document.getElementById('prodPrecio').value = p.precio;
  document.getElementById('prodStock').value = p.stock;
  document.getElementById('prodVariante').value = p.variante;
  document.getElementById('prodEditIdx').value = id;
  openModal('modalNuevoProducto');
}

function guardarProducto() {
  const nombre = document.getElementById('prodNombre').value.trim();
  const cat = document.getElementById('prodCat').value;
  const precio = parseFloat(document.getElementById('prodPrecio').value) || 0;
  const stock = parseInt(document.getElementById('prodStock').value) || 0;
  const variante = document.getElementById('prodVariante').value.trim();
  const editId = document.getElementById('prodEditIdx').value;
  if (!nombre) { showToast('El nombre es requerido','error'); return; }
  const catEmojis = { alimentos:'🥩', shampoo:'🧴', juguetes:'🎾', accesorios:'🎀' };
  const catBg = { alimentos:'bg1', shampoo:'bg2', juguetes:'bg4', accesorios:'bg3' };
  if (editId) {
    const idx = DB.productos.findIndex(p => p.id === parseInt(editId));
    if (idx >= 0) {
      const old = {...DB.productos[idx]};
      DB.productos[idx] = { ...DB.productos[idx], nombre, cat, precio, stock, variante, emoji: catEmojis[cat]||'📦', bgClass: catBg[cat]||'bg1' };
      addLog('edit','Producto actualizado', `${old.nombre} → ${nombre} — Bs.${precio} — Stock:${stock}`);
    }
  } else {
    const nuevo = { id: DB.nextProdId++, nombre, variante, cat, emoji: catEmojis[cat]||'📦', bgClass: catBg[cat]||'bg1', precio, stock };
    DB.productos.push(nuevo);
    addLog('create','Producto agregado al catálogo', `${nombre} (${variante}) — Bs.${precio}`);
  }
  saveDB();
  renderProducts(currentFilter);
  renderInventario();
  closeModal('modalNuevoProducto');
  document.getElementById('prodEditIdx').value = '';
  document.getElementById('modalProdTitle').textContent = '📦 Nuevo Producto';
  showToast('✅ Producto guardado en el catálogo.','success');
}

// ============================================
// INVENTARIO
// ============================================
function renderInventario() {
  const tbody = document.getElementById('inventarioBody');
  if (!tbody) return;
  tbody.innerHTML = DB.productos.map((p,i) => {
    const bajo = p.stock < 5;
    return `<tr>
      <td>${p.emoji} ${p.nombre} ${p.variante}</td>
      <td>${p.cat}</td>
      <td style="${bajo ? 'color:var(--rust);font-weight:600' : ''}">${p.stock}</td>
      <td>5</td><td>Bs.${p.precio}</td>
      <td>${bajo ? '<span class="badge badge-red">⚠ Stock bajo</span>' : '<span class="badge badge-green">✓ OK</span>'}</td>
      <td style="display:flex;gap:4px">
        ${bajo ? `<button class="btn btn-sm btn-teal" onclick="reponerStock(${i})">Reponer</button>` : ''}
        <button class="btn btn-sm btn-outline" onclick="openEditProducto(${p.id})">Editar</button>
      </td>
    </tr>`;
  }).join('');
}

function reponerStock(idx) {
  const cant = parseInt(prompt('¿Cuántas unidades reponer?') || '0');
  if (cant <= 0) return;
  DB.productos[idx].stock += cant;
  saveDB();
  addLog('move','Reposición de inventario', `${DB.productos[idx].nombre} +${cant} unidades. Nuevo stock: ${DB.productos[idx].stock}`);
  renderInventario();
  showToast(`✅ Stock repuesto: +${cant} unidades`,'success');
}

function populateMovProductos() {
  const sel = document.getElementById('movProducto');
  if (!sel) return;
  sel.innerHTML = DB.productos.map(p => `<option value="${p.id}">${p.emoji} ${p.nombre} ${p.variante} (${p.stock} unid.)</option>`).join('');
}

function registrarMovimiento() {
  const tipo = document.getElementById('movTipo').value;
  const prodId = parseInt(document.getElementById('movProducto').value);
  const cant = parseInt(document.getElementById('movCantidad').value) || 0;
  const motivo = document.getElementById('movMotivo').value;
  if (cant <= 0) { showToast('Ingrese una cantidad válida','error'); return; }
  const idx = DB.productos.findIndex(p => p.id === prodId);
  if (idx < 0) return;
  const prod = DB.productos[idx];
  if (tipo === 'entrada') prod.stock += cant;
  else if (tipo === 'salida') prod.stock = Math.max(0, prod.stock - cant);
  else prod.stock = cant;
  saveDB();
  addLog('move', `Movimiento de inventario: ${tipo}`, `${prod.nombre} — ${cant} unid. — ${motivo || 'Sin motivo'}`);
  renderInventario();
  renderProducts(currentFilter);
  closeModal('modalMovInventario');
  showToast('✅ Movimiento registrado correctamente.','success');
}

// ============================================
// USUARIOS
// ============================================
function renderUsuarios() {
  const tbody = document.getElementById('usuariosBody');
  if (!tbody) return;
  const roleBadge = { admin:'badge-red', recep:'badge-teal', groo:'badge-orange', client:'badge-blue' };
  tbody.innerHTML = DB.usuarios.map((u,i) => `
    <tr>
      <td>${u.email}</td>
      <td><span class="badge ${roleBadge[u.role]||'badge-gray'}">${ROLES_META[u.role]?.label || u.role}</span></td>
      <td>${u.lastLogin || 'Nunca'}</td>
      <td><span class="badge ${u.activo ? 'badge-green' : 'badge-red'}">${u.activo ? 'Activo' : 'Inactivo'}</span></td>
      <td><button class="btn btn-sm btn-ghost" onclick="toggleUsuario(${i})">${u.activo ? '🔒 Desactivar' : '🔓 Activar'}</button></td>
    </tr>`).join('');
}

function crearUsuario() {
  const nombre = document.getElementById('usrNombre').value.trim();
  const email = document.getElementById('usrEmail').value.trim();
  const pass = document.getElementById('usrPass').value;
  const pass2 = document.getElementById('usrPass2').value;
  const role = document.getElementById('usrRol').value;
  if (!nombre || !email || !pass) { showToast('Complete todos los campos','error'); return; }
  if (pass !== pass2) { showToast('Las contraseñas no coinciden','error'); return; }
  if (DB.usuarios.find(u => u.email === email)) { showToast('El email ya existe en el sistema','error'); return; }
  DB.usuarios.push({ email, pass, role, nombre, activo:true, lastLogin:'' });
  saveDB();
  addLog('create','Usuario creado', `${nombre} (${email}) — Rol: ${ROLES_META[role]?.label}`);
  renderUsuarios();
  closeModal('modalNuevoUsuario');
  showToast(`✅ Usuario ${nombre} creado correctamente.`,'success');
}

function toggleUsuario(idx) {
  DB.usuarios[idx].activo = !DB.usuarios[idx].activo;
  saveDB();
  addLog('edit','Estado de usuario cambiado', `${DB.usuarios[idx].email} → ${DB.usuarios[idx].activo ? 'Activo' : 'Inactivo'}`);
  renderUsuarios();
  showToast(`Usuario ${DB.usuarios[idx].activo ? 'activado' : 'desactivado'}`,'success');
}

// ============================================
// PAGOS
// ============================================
function procesarPago() {
  addLog('pay','Pago registrado','Cita #001 — Mishi — Bs.120 — QR');
  showToast('✅ Pago registrado. Factura generada.','success');
  closeModal('modalNuevoPago');
}

// ============================================
// CART
// ============================================
function addToCart(id) {
  const prod = DB.productos.find(p => p.id === id);
  if (!prod) return;
  cart[id] = cart[id] ? { ...cart[id], qty: cart[id].qty + 1 } : { ...prod, qty: 1 };
  updateCartUI();
  showToast(`✅ ${prod.nombre} (${prod.variante}) agregado al carrito`, 'success');
}
function updateCartUI() {
  const total = Object.values(cart).reduce((a, p) => a + p.precio * p.qty, 0);
  const count = Object.values(cart).reduce((a, p) => a + p.qty, 0);
  document.getElementById('cartCount').textContent = count;
  document.getElementById('cartTotal').textContent = `Bs. ${total}`;
  const itemsEl = document.getElementById('cartItems');
  itemsEl.innerHTML = Object.values(cart).map(p => `
    <div class="cart-item">
      <div class="cart-item-icon">${p.emoji}</div>
      <div class="cart-item-info">
        <div class="cart-item-name">${p.nombre} ${p.variante}</div>
        <div class="cart-item-price">Bs.${p.precio} c/u</div>
      </div>
      <div class="cart-qty">
        <button class="qty-btn" onclick="changeQty(${p.id},-1)">−</button>
        <span>${p.qty}</span>
        <button class="qty-btn" onclick="changeQty(${p.id},1)">+</button>
      </div>
    </div>`).join('') || '<p style="color:var(--gray);text-align:center;padding:20px">Carrito vacío</p>';
}
function changeQty(id, delta) {
  if (!cart[id]) return;
  cart[id].qty += delta;
  if (cart[id].qty <= 0) delete cart[id];
  updateCartUI();
}
function toggleCart() {
  document.getElementById('cartPanel').classList.toggle('open');
}
function sendWhatsApp() {
  const items = Object.values(cart);
  if (!items.length) { showToast('El carrito está vacío','error'); return; }
  const msg = items.map(p => `• ${p.nombre} ${p.variante} x${p.qty} = Bs.${p.precio * p.qty}`).join('\n');
  const total = items.reduce((a, p) => a + p.precio * p.qty, 0);
  const full = `🐾 *Pedido PawSpa*\n\n${msg}\n\n*Total: Bs.${total}*`;
  window.open(`https://wa.me/59172345678?text=${encodeURIComponent(full)}`, '_blank');
  showToast('Abriendo WhatsApp con tu pedido 📱', 'success');
}

// ============================================
// CONFIG
// ============================================
function saveConfig() {
  const n = document.getElementById('cfgNombre');
  if (n) DB.config.nombre = n.value;
  const w = document.getElementById('cfgWA');
  if (w) DB.config.wa = w.value;
  saveDB();
  addLog('edit','Configuración del sistema guardada','Parámetros generales actualizados');
  showToast('✅ Configuración guardada correctamente.','success');
}

// ============================================
// MISC
// ============================================
function toggleCheck(el) {
  el.classList.toggle('checked');
  const check = el.querySelector('.checklist-check');
  check.textContent = el.classList.contains('checked') ? '✓' : '';
  const checked = document.querySelectorAll('.checklist-item.checked').length;
  const total = document.querySelectorAll('.checklist-item').length;
  const counter = document.getElementById('checkCount');
  if (counter) counter.textContent = `(${checked}/${total} completados)`;
}

function cerrarServicio() {
  const checked = document.querySelectorAll('#checklistContainer .checklist-item.checked').length;
  if (checked < 5) { showToast('❌ Mínimo 5 ítems del checklist deben estar marcados','error'); return; }
  addLog('edit','Servicio cerrado','Cita #001 — Mishi — Servicio Completo finalizado');
  showToast('✅ Servicio cerrado correctamente. Notificando al dueño...','success');
}

let timerSecs = 5025;
function startTimer() {
  setInterval(() => {
    timerSecs++;
    const h = String(Math.floor(timerSecs/3600)).padStart(2,'0');
    const m = String(Math.floor((timerSecs%3600)/60)).padStart(2,'0');
    const s = String(timerSecs%60).padStart(2,'0');
    const el = document.getElementById('timer');
    if (el) el.textContent = `${h}:${m}:${s}`;
  }, 1000);
}

function switchTab(el, activeId) {
  const parent = el.parentElement;
  parent.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const parentContent = parent.parentElement;
  parentContent.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
  const target = document.getElementById(activeId);
  if (target) target.style.display = '';
}

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.add('hidden');
  });
});

function bloquearHorario() {
  addLog('edit','Horario bloqueado','Bloqueo de disponibilidad registrado');
  showToast('🚫 Horario bloqueado correctamente.','success');
  closeModal('modalBloqueo');
  buildCalendar();
}

function rateStar(n) {
  document.querySelectorAll('.star-rating .star').forEach((s,i) => { s.classList.toggle('active', i < n); });
  showToast(`⭐ Calificación: ${n}/5 estrellas`, 'success');
}

let toastTimer;
function showToast(msg, type = '') {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = `show ${type}`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.className = '', 3200);
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', async () => {
  // Primero cargar datos desde la BD
  await cargarDatosDesdeBD();
  
  // Luego inicializar la app
  if (IS_ADMIN || IS_RECEP || IS_GROOMER) {
    initApp(currentRole);
  } else if (IS_CLIENT) {
    initApp('client');
  }
});

// ============================================
// ALTERNAR ENTRE LOGIN Y REGISTRO
// ============================================
function mostrarLogin() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registroForm').style.display = 'none';
    document.querySelectorAll('.auth-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector('.auth-tab:first-child').classList.add('active');
    // Resetear errores
    document.getElementById('loginError').style.display = 'none';
    document.getElementById('registroError').style.display = 'none';
}

function mostrarRegistro() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registroForm').style.display = 'block';
    document.querySelectorAll('.auth-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector('.auth-tab:last-child').classList.add('active');
    
    // Limpiar errores al cambiar de pestaña
    document.getElementById('registroError').style.display = 'none';
    document.getElementById('registroSuccess').style.display = 'none';
}

// ============================================
// MEDIDOR DE FUERZA DE CONTRASEÑA
// ============================================
function medirFuerzaPassword(password) {
    let fuerza = 0;
    if (password.length >= 8) fuerza++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) fuerza++;
    if (password.match(/\d/)) fuerza++;
    if (password.match(/[^a-zA-Z\d]/)) fuerza++;
    
    if (password.length === 0) return { texto: '', clase: '' };
    if (fuerza <= 2) return { texto: '🔴 Débil', clase: 'weak' };
    if (fuerza === 3) return { texto: '🟡 Media', clase: 'medium' };
    return { texto: '🟢 Fuerte', clase: 'strong' };
}

// Evento para medir fuerza de contraseña
document.addEventListener('DOMContentLoaded', () => {
    const passInput = document.getElementById('regPassword');
    if (passInput) {
        passInput.addEventListener('input', function() {
            const fuerza = medirFuerzaPassword(this.value);
            const strengthDiv = document.getElementById('passwordStrength');
            if (strengthDiv) {
                strengthDiv.textContent = fuerza.texto;
                strengthDiv.className = `password-strength ${fuerza.clase}`;
            }
        });
    }
});

// ============================================
// FUNCIÓN DE REGISTRO (CORREGIDA)
// ============================================
async function doRegistro() {
    console.log('🔵 Iniciando registro...');
    
    const nombre = document.getElementById('regNombre').value.trim();
    const telefono = document.getElementById('regTelefono').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    
    const errorDiv = document.getElementById('registroError');
    const successDiv = document.getElementById('registroSuccess');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    // Validaciones
    if (!nombre || !email || !password) {
        errorDiv.textContent = '❌ Por favor completa todos los campos requeridos';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (password.length < 8) {
        errorDiv.textContent = '❌ La contraseña debe tener al menos 8 caracteres';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (password !== confirmPassword) {
        errorDiv.textContent = '❌ Las contraseñas no coinciden';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorDiv.textContent = '❌ Por favor ingresa un correo electrónico válido';
        errorDiv.style.display = 'block';
        return;
    }
    
    const btn = document.querySelector('#registroForm .btn-primary');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Creando cuenta...';
    btn.disabled = true;
    
    try {
        const datosEnvio = {
            nombre: nombre,
            telefono: telefono,
            email: email,
            password: password
        };
        
        console.log('📤 Enviando datos:', datosEnvio);
        
        const response = await fetch(API_URL + 'registro.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(datosEnvio)
        });
        
        console.log('📡 Status HTTP:', response.status);
        
        const textResponse = await response.text();
        console.log('📦 Respuesta cruda:', textResponse);
        
        let data;
        try {
            data = JSON.parse(textResponse);
        } catch (e) {
            console.error('❌ Error parseando JSON:', e);
            throw new Error('El servidor devolvió una respuesta inválida');
        }
        
        console.log('📦 Datos parseados:', data);
        
        if (data.success) {
            successDiv.textContent = '✅ ' + data.message;
            successDiv.style.display = 'block';
            
            // Limpiar formulario
            document.getElementById('regNombre').value = '';
            document.getElementById('regTelefono').value = '';
            document.getElementById('regEmail').value = '';
            document.getElementById('regPassword').value = '';
            document.getElementById('regConfirmPassword').value = '';
            
            // Redirigir al login después de 2 segundos
            setTimeout(() => {
                mostrarLogin();
                successDiv.style.display = 'none';
                document.getElementById('loginUser').value = email;
            }, 2000);
        } else {
            errorDiv.textContent = '❌ ' + (data.error || 'Error al crear la cuenta');
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('❌ Error detallado:', error);
        errorDiv.textContent = '❌ Error de conexión. ¿XAMPP está corriendo?';
        errorDiv.style.display = 'block';
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// ============================================
// MASCOTAS DEL CLIENTE (Desde su panel)
// ============================================

// Cargar mascotas del cliente logueado (CORREGIDO)
async function cargarMascotasCliente() {
    if (!currentUser || !currentUser.id) {
        console.log('No hay usuario logueado');
        return;
    }
    
    const container = document.getElementById('mascotasClienteContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading">Cargando mascotas...</div>';
    
    try {
        // Usar el endpoint correcto
        const response = await fetch(`${API_URL}clientes.php?action=mascotas_cliente&cliente_id=${currentUser.id}`);
        const data = await response.json();
        
        console.log('Mascotas cargadas:', data);
        
        if (data.success && data.mascotas) {
            if (data.mascotas.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:20px; color:var(--gray)">
                        🐾 No tienes mascotas registradas.<br>
                        <button class="btn btn-sm btn-teal" style="margin-top:10px" onclick="abrirModalMascotaCliente()">➕ Agregar tu primera mascota</button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = data.mascotas.map(m => `
                <div class="pet-card" style="margin-bottom:12px; padding:12px; display:flex; gap:12px; align-items:flex-start">
                    <div class="pet-avatar" style="width:45px; height:45px; font-size:1.5rem; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--caramel),var(--teal)); border-radius:50%">
                        ${m.especie === 'perro' ? '🐕' : (m.especie === 'gato' ? '🐈' : '🐾')}
                    </div>
                    <div class="pet-info" style="flex:1">
                        <h4 style="font-size:.95rem">${escapeHtml(m.nombre)}</h4>
                        <p style="font-size:.75rem; color:var(--gray)">
                            ${m.raza || 'Sin raza'} · ${m.edad || '?'} años · ${m.peso || '?'} kg
                        </p>
                        ${m.alergias ? `<span class="badge badge-red" style="font-size:.7rem">⚠️ ${escapeHtml(m.alergias.substring(0,30))}</span>` : ''}
                    </div>
                    <div class="pet-actions" style="display:flex; gap:5px">
                        <button class="btn btn-sm btn-ghost" onclick="editarMascotaCliente(${m.id})">✏️</button>
                        <button class="btn btn-sm btn-ghost" onclick="eliminarMascotaCliente(${m.id})">🗑️</button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="error">Error al cargar mascotas: ' + (data.error || 'Desconocido') + '</div>';
        }
    } catch (error) {
        console.error('Error cargando mascotas:', error);
        container.innerHTML = '<div class="error">Error de conexión al cargar mascotas</div>';
    }
}

// Abrir modal para agregar mascota (cliente)
function abrirModalMascotaCliente() {
    document.getElementById('modalMascotaClienteTitle').textContent = '➕ Nueva Mascota';
    document.getElementById('mascotaClienteId').value = '';
    document.getElementById('mascotaClienteNombre').value = '';
    document.getElementById('mascotaClienteEspecie').value = 'perro';
    document.getElementById('mascotaClienteRaza').value = '';
    document.getElementById('mascotaClienteEdad').value = '';
    document.getElementById('mascotaClientePeso').value = '';
    document.getElementById('mascotaClienteAlergias').value = '';
    openModal('modalMascotaCliente');
}

// Guardar mascota del cliente (CORREGIDA)
async function guardarMascotaCliente() {
    // Obtener usuario actual desde la variable global
    const usuarioId = currentUser?.id;
    const usuarioNombre = currentUser?.nombre;
    
    // Depuración - ver qué está pasando
    console.log('CurrentUser:', currentUser);
    console.log('Usuario ID:', usuarioId);
    
    // Validar que el usuario esté logueado
    if (!usuarioId) {
        showToast('Debes iniciar sesión para agregar una mascota', 'error');
        // Intentar recuperar de localStorage
        const storedUser = localStorage.getItem('user');
        if (storedUser) {
            try {
                const user = JSON.parse(storedUser);
                if (user && user.id) {
                    // Recargar la página para restaurar la sesión
                    window.location.reload();
                    return;
                }
            } catch(e) {}
        }
        return;
    }
    
    const mascotaId = document.getElementById('mascotaClienteId')?.value || '';
    const nombre = document.getElementById('mascotaClienteNombre')?.value.trim() || '';
    const especie = document.getElementById('mascotaClienteEspecie')?.value || 'perro';
    const raza = document.getElementById('mascotaClienteRaza')?.value.trim() || '';
    const edad = parseInt(document.getElementById('mascotaClienteEdad')?.value) || 0;
    const peso = parseFloat(document.getElementById('mascotaClientePeso')?.value) || 0;
    const alergias = document.getElementById('mascotaClienteAlergias')?.value || '';
    
    if (!nombre) {
        showToast('El nombre de la mascota es requerido', 'error');
        return;
    }
    
    const btn = document.querySelector('#modalMascotaCliente .btn-teal');
    if (!btn) {
        showToast('Error: No se encontró el botón', 'error');
        return;
    }
    
    const originalText = btn.textContent;
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;
    
    try {
        let url = API_URL + 'clientes.php?action=mascota_cliente';
        let method = 'POST';
        let body = {
            cliente_id: usuarioId,
            nombre: nombre,
            especie: especie,
            raza: raza,
            edad: edad,
            peso: peso,
            alergias: alergias,
            tamanio: 'mediano',
            temperamento: 'tranquilo'
        };
        
        if (mascotaId) {
            method = 'PUT';
            body.mascota_id = parseInt(mascotaId);
            url = API_URL + 'clientes.php?action=mascota_cliente';
        }
        
        console.log('Enviando:', { url, method, body });
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const data = await response.json();
        console.log('Respuesta:', data);
        
        if (data.success) {
            showToast(mascotaId ? '✅ Mascota actualizada' : '✅ Mascota agregada', 'success');
            closeModal('modalMascotaCliente');
            
            // Recargar las mascotas
            if (typeof cargarMascotasCliente === 'function') {
                await cargarMascotasCliente();
            }
            if (typeof cargarMascotas === 'function') {
                await cargarMascotas();
            }
            
            // Limpiar formulario
            const form = document.getElementById('modalMascotaCliente');
            if (form) {
                document.getElementById('mascotaClienteId').value = '';
                document.getElementById('mascotaClienteNombre').value = '';
                document.getElementById('mascotaClienteRaza').value = '';
                document.getElementById('mascotaClienteEdad').value = '';
                document.getElementById('mascotaClientePeso').value = '';
                document.getElementById('mascotaClienteAlergias').value = '';
            }
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error detallado:', error);
        showToast('Error de conexión: ' + error.message, 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Editar mascota del cliente
async function editarMascotaCliente(mascotaId) {
    try {
        const response = await fetch(`${API_URL}clientes.php?action=mascotas&cliente_id=${currentUser.id}`);
        const data = await response.json();
        
        if (data.success) {
            const mascota = data.mascotas.find(m => m.id === mascotaId);
            if (mascota) {
                document.getElementById('modalMascotaClienteTitle').textContent = '✏️ Editar Mascota';
                document.getElementById('mascotaClienteId').value = mascota.id;
                document.getElementById('mascotaClienteNombre').value = mascota.nombre;
                document.getElementById('mascotaClienteEspecie').value = mascota.especie;
                document.getElementById('mascotaClienteRaza').value = mascota.raza || '';
                document.getElementById('mascotaClienteEdad').value = mascota.edad || '';
                document.getElementById('mascotaClientePeso').value = mascota.peso || '';
                document.getElementById('mascotaClienteAlergias').value = mascota.alergias || '';
                openModal('modalMascotaCliente');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al cargar datos', 'error');
    }
}

// Eliminar mascota del cliente
async function eliminarMascotaCliente(mascotaId) {
    if (!confirm('¿Estás seguro de eliminar esta mascota? No se puede deshacer.')) return;
    
    try {
        const response = await fetch(API_URL + 'clientes.php?action=mascota_cliente', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mascota_id: mascotaId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Mascota eliminada', 'success');
            await cargarMascotasCliente();
            if (typeof cargarMascotas === 'function') cargarMascotas();
        } else {
            showToast(data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

// Actualizar contador de mascotas en las tarjetas KPI
function actualizarStatsMascotas() {
    const container = document.getElementById('mascotasClienteContainer');
    if (!container) return;
    
    // Buscar cuántas mascotas hay
    const mascotasCards = container.querySelectorAll('.pet-card');
    const total = mascotasCards.length;
    
    document.getElementById('totalMascotasCliente').textContent = total;
    
    if (total > 0) {
        const primeraMascota = mascotasCards[0]?.querySelector('h4')?.textContent || '-';
        document.getElementById('primeraMascotaCliente').textContent = primeraMascota;
    } else {
        document.getElementById('primeraMascotaCliente').textContent = '-';
    }
}

// ============================================
// CIERRE DE SESIÓN POR INACTIVIDAD
// ============================================

let inactivityTimer;
const INACTIVITY_TIME = 15 * 60 * 1000; // 15 minutos en milisegundos

// Función para reiniciar el temporizador de inactividad
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        // Cerrar sesión por inactividad
        showToast('⚠️ Sesión cerrada por inactividad', 'warning');
        doLogout();
    }, INACTIVITY_TIME);
}

// Eventos que indican actividad del usuario
const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click', 'keydown'];

activityEvents.forEach(event => {
    document.addEventListener(event, () => {
        resetInactivityTimer();
        // Notificar al servidor que el usuario está activo (opcional)
        fetch(API_URL + 'keep_alive.php', { method: 'POST', keepalive: true }).catch(() => {});
    });
});

// Iniciar el temporizador cuando la página carga
if (IS_ADMIN || IS_RECEP || IS_GROOMER || IS_CLIENT) {
    resetInactivityTimer();
}

</script>
</script>
</body>
</html>
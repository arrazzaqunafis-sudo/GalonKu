<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GalonKu Admin</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">G</span>
      <strong>GalonKu</strong>
    </div>

    <nav class="side-nav" aria-label="Menu admin">
      <button class="nav-item active" data-page="Dashboard">
        <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z"/></svg>
        <span>Dashboard</span>
      </button>
      <button class="nav-item" data-page="Produk">
        <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 8 9 5 9-5M3 15l9 5 9-5"/></svg>
        <span>Produk</span>
      </button>
      <button class="nav-item" data-page="Pesanan">
        <svg viewBox="0 0 24 24"><path d="M3 6h2l2 10h10l2-7H7M9 20h.01M17 20h.01"/></svg>
        <span>Pesanan</span>
      </button>
      <button class="nav-item" data-page="Pelanggan">
        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Pelanggan</span>
      </button>
      <button class="nav-item" data-page="Komplain">
        <svg viewBox="0 0 24 24"><path d="M4 4h16v12H7l-3 4V4Z"/></svg>
        <span>Komplain</span>
      </button>
      <button class="nav-item" data-page="Pengaturan">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-1.42 1.42-.06-.06A1.65 1.65 0 0 0 16.5 18a1.65 1.65 0 0 0-1 .6V19h-7v-.4a1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06-1.42-1.42.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1H3.6v-4H4a1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06L5.63 5.7l.06.06A1.65 1.65 0 0 0 7.5 6a1.65 1.65 0 0 0 1-.6V5h7v.4a1.65 1.65 0 0 0 1 .6 1.65 1.65 0 0 0 1.82-.33l.06-.06 1.42 1.42-.06.06A1.65 1.65 0 0 0 19.4 9c.25.34.46.67.6 1h.4v4H20a1.65 1.65 0 0 0-.6 1Z"/></svg>
        <span>Pengaturan</span>
      </button>
    </nav>

    <div class="plan-card">
      <div class="plan-illustration" aria-hidden="true">
        <span class="mini-gallon"></span>
      </div>
      <strong>Kelola GalonKu</strong>
      <small>Panel administrasi aktif</small>
    </div>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <button class="menu-button" id="menu-button" aria-label="Buka menu">☰</button>
      <label class="search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
        <input type="search" placeholder="Cari data...">
      </label>
      <div class="top-actions">
        <button class="icon-button" aria-label="Notifikasi">
          <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
          <span class="notice-dot"></span>
        </button>
        <button class="profile-button" aria-label="Profil admin">
          <span class="avatar">A</span>
          <span class="profile-name"><strong>Admin</strong><small>GalonKu</small></span>
        </button>
      </div>
    </header>

    <div class="dashboard">
      <div class="page-heading">
        <div>
          <small>Ringkasan bisnis</small>
          <h1 id="page-title">Dashboard</h1>
        </div>
        <button class="period-button">Bulan ini <span>⌄</span></button>
      </div>

      <section class="stat-grid" aria-label="Statistik utama">
        <article class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M4 20V10m5 10V4m6 16v-7m5 7V7"/></svg>
          </div>
          <div><span>Pesanan Selesai</span><strong id="stat-orders">0</strong><small class="positive">+12% <b>dari bulan lalu</b></small></div>
        </article>
        <article class="stat-card">
          <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24"><path d="M3 7h18v12H3V7Zm3-3h12v3M8 12h8M8 16h5"/></svg>
          </div>
          <div><span>Total Penjualan</span><strong id="stat-sales">Rp0</strong><small class="positive">+8% <b>dari bulan lalu</b></small></div>
        </article>
        <article class="stat-card">
          <div class="stat-icon mint">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 6.5c0-1.9-2.2-3.5-5-3.5S7 4.6 7 6.5 9.2 10 12 10s5 1.6 5 3.5S14.8 17 12 17s-5-1.6-5-3.5"/></svg>
          </div>
          <div><span>Saldo Tunai</span><strong id="stat-cash">Rp0</strong><small class="positive">+5% <b>dari bulan lalu</b></small></div>
        </article>
        <article class="stat-card">
          <div class="stat-icon coral">
            <svg viewBox="0 0 24 24"><path d="M4 4h16v12H7l-3 4V4Z"/><path d="M8 9h8M8 12h5"/></svg>
          </div>
          <div><span>Komplain Aktif</span><strong id="stat-complaints">0</strong><small class="negative"><b>Perlu ditinjau</b></small></div>
        </article>
      </section>

      <section class="analytics-grid">
        <article class="panel sales-panel">
          <div class="panel-heading">
            <div><small>Ringkasan pendapatan</small><h2>Penjualan Bulanan</h2></div>
            <button class="year-button">2026 <span>⌄</span></button>
          </div>
          <div class="sales-total">
            <span>Total penjualan</span>
            <strong id="sales-total">Rp0</strong>
          </div>
          <div class="chart-wrap">
            <div class="chart-y"><span>Rp40jt</span><span>Rp30jt</span><span>Rp20jt</span><span>Rp10jt</span><span>Rp0</span></div>
            <svg class="chart" viewBox="0 0 760 220" preserveAspectRatio="none" role="img" aria-label="Grafik penjualan bulanan">
              <defs>
                <linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#82b1eb" stop-opacity=".32"/>
                  <stop offset="100%" stop-color="#82b1eb" stop-opacity=".03"/>
                </linearGradient>
              </defs>
              <g class="grid-lines">
                <path d="M0 20H760M0 65H760M0 110H760M0 155H760M0 200H760"/>
              </g>
              <path class="chart-area" d="M0 166 C45 160 55 174 92 158 S145 142 185 151 S240 118 278 130 S330 112 370 124 S420 84 462 96 S518 86 558 62 S618 96 658 73 S714 62 760 46 L760 220 L0 220 Z"/>
              <path class="chart-line" d="M0 166 C45 160 55 174 92 158 S145 142 185 151 S240 118 278 130 S330 112 370 124 S420 84 462 96 S518 86 558 62 S618 96 658 73 S714 62 760 46"/>
              <line class="chart-marker" x1="462" y1="96" x2="462" y2="200"/>
              <circle class="chart-point" cx="462" cy="96" r="6"/>
            </svg>
            <div class="chart-tooltip"><small>Juli</small><strong id="tooltip-sales">Rp12.458.000</strong></div>
            <div class="chart-x"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span><span>Jul</span><span>Ags</span><span>Sep</span><span>Okt</span><span>Nov</span><span>Des</span></div>
          </div>
        </article>

        <article class="panel inventory-panel">
          <div class="panel-heading">
            <div><small>Stok saat ini</small><h2>Inventori Galon</h2></div>
            <button class="more-button" aria-label="Menu inventori">⋮</button>
          </div>
          <div id="inventory-list" class="inventory-list">
            <p class="loading">Memuat inventori...</p>
          </div>
        </article>
      </section>
    </div>
  </main>
  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  <script src="assets/admin.js"></script>
</body>
</html>

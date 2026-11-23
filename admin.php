<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GalonKu Admin - Panel Kendali Depot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </div>
      <div class="brand-text">
        <strong>GalonKu</strong>
        <small>Admin Panel</small>
      </div>
    </div>

    <nav class="side-nav" aria-label="Menu Admin">
      <button class="nav-item active" data-page="Dashboard">
        <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z"/></svg>
        <span>Dashboard</span>
      </button>
      <button class="nav-item" data-page="Produk">
        <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 8 9 5 9-5M3 15l9 5 9-5"/></svg>
        <span>Produk & Stok</span>
      </button>
      <button class="nav-item" data-page="Pesanan">
        <svg viewBox="0 0 24 24"><path d="M3 6h2l2 10h10l2-7H7M9 20h.01M17 20h.01"/></svg>
        <span>Daftar Pesanan</span>
        <span class="nav-badge" id="badge-pesanan" style="display:none;">0</span>
      </button>
      <button class="nav-item" data-page="Pelanggan">
        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Pelanggan & Kurir</span>
      </button>
      <button class="nav-item" data-page="Komplain">
        <svg viewBox="0 0 24 24"><path d="M4 4h16v12H7l-3 4V4Z"/></svg>
        <span>Laporan Komplain</span>
        <span class="nav-badge alert" id="badge-komplain" style="display:none;">0</span>
      </button>
      <button class="nav-item" data-page="Pengaturan">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-1.42 1.42-.06-.06A1.65 1.65 0 0 0 16.5 18a1.65 1.65 0 0 0-1 .6V19h-7v-.4a1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06-1.42-1.42.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1H3.6v-4H4a1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06L5.63 5.7l.06.06A1.65 1.65 0 0 0 7.5 6a1.65 1.65 0 0 0 1-.6V5h7v.4a1.65 1.65 0 0 0 1 .6 1.65 1.65 0 0 0 1.82-.33l.06-.06 1.42 1.42-.06.06A1.65 1.65 0 0 0 19.4 9c.25.34.46.67.6 1h.4v4H20a1.65 1.65 0 0 0-.6 1Z"/></svg>
        <span>Promo & Pengaturan</span>
      </button>
    </nav>

    <div class="plan-card">
      <div class="plan-illustration" aria-hidden="true">
        <span class="mini-gallon"></span>
      </div>
      <strong>Depot GalonKu</strong>
      <small>Sistem Manajemen Aktif</small>
      <div class="status-indicator"><span class="pulse-dot"></span> Server Online</div>
    </div>
  </aside>

  <!-- MAIN CONTAINER -->
  <main class="main-content">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-button" id="menu-button" aria-label="Buka Menu">☰</button>
        <div class="search-box">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
          <input type="search" id="global-search" placeholder="Cari di halaman ini...">
        </div>
      </div>
      <div class="top-actions">
        <button class="icon-button" id="btn-refresh" title="Muat Ulang Data">
          <svg viewBox="0 0 24 24"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.19"/></svg>
        </button>
        <button class="icon-button" id="btn-notif" title="Notifikasi Komplain">
          <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
          <span class="notice-dot" id="topbar-notif-dot" style="display:none;"></span>
        </button>
        <div class="profile-chip">
          <span class="avatar">A</span>
          <div class="profile-name">
            <strong>Administrator</strong>
            <small>Depot Bandung</small>
          </div>
        </div>
      </div>
    </header>

    <div class="content-body">
      <!-- 1. VIEW DASHBOARD -->
      <section id="view-dashboard" class="page-view active">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Ringkasan Bisnis</span>
            <h1>Dashboard</h1>
          </div>
          <div class="heading-actions">
            <span class="live-clock" id="live-clock">--:--:--</span>
          </div>
        </div>

        <div class="stat-grid" aria-label="Statistik Utama">
          <article class="stat-card">
            <div class="stat-icon blue">
              <svg viewBox="0 0 24 24"><path d="M4 20V10m5 10V4m6 16v-7m5 7V7"/></svg>
            </div>
            <div class="stat-body">
              <span>Pesanan Selesai</span>
              <strong id="stat-orders">0</strong>
              <small class="positive"><span>●</span> Transaksi berhasil</small>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon cyan">
              <svg viewBox="0 0 24 24"><path d="M3 7h18v12H3V7Zm3-3h12v3M8 12h8M8 16h5"/></svg>
            </div>
            <div class="stat-body">
              <span>Total Penjualan</span>
              <strong id="stat-sales">Rp0</strong>
              <small class="positive"><span>●</span> Akumulasi omset</small>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon mint">
              <svg viewBox="0 0 24 24"><path d="M12 2v20M17 6.5c0-1.9-2.2-3.5-5-3.5S7 4.6 7 6.5 9.2 10 12 10s5 1.6 5 3.5S14.8 17 12 17s-5-1.6-5-3.5"/></svg>
            </div>
            <div class="stat-body">
              <span>Saldo Tunai Diterima</span>
              <strong id="stat-cash">Rp0</strong>
              <small class="positive"><span>●</span> Kas pembayaran tunai</small>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon coral">
              <svg viewBox="0 0 24 24"><path d="M4 4h16v12H7l-3 4V4Z"/><path d="M8 9h8M8 12h5"/></svg>
            </div>
            <div class="stat-body">
              <span>Komplain Aktif</span>
              <strong id="stat-complaints">0</strong>
              <small class="negative" id="stat-complaints-label">Perlu ditindaklanjuti</small>
            </div>
          </article>
        </div>

        <div class="analytics-grid">
          <!-- GRAFIK PENJUALAN -->
          <article class="panel sales-panel">
            <div class="panel-heading">
              <div>
                <small class="panel-tag">Tren Pendapatan</small>
                <h2>Penjualan Tahun <?= date('Y') ?></h2>
              </div>
              <div class="sales-total-badge">
                <span>Total:</span>
                <strong id="sales-total">Rp0</strong>
              </div>
            </div>
            <div class="chart-container" id="chart-container">
              <!-- Bar chart rendered dynamically by JS -->
            </div>
          </article>

          <!-- INVENTORI RINGKAS -->
          <article class="panel inventory-panel">
            <div class="panel-heading">
              <div>
                <small class="panel-tag">Stok Gudang</small>
                <h2>Inventori Galon</h2>
              </div>
              <button class="btn-text" onclick="switchPage('Produk')">Kelola Stok &rarr;</button>
            </div>
            <div id="inventory-list" class="inventory-list">
              <p class="loading">Memuat inventori...</p>
            </div>
          </article>
        </div>

        <!-- TABEL PESANAN TERBARU -->
        <article class="panel table-panel">
          <div class="panel-heading">
            <div>
              <small class="panel-tag">Aktivitas Terkini</small>
              <h2>Pesanan Terbaru</h2>
            </div>
            <button class="btn-text" onclick="switchPage('Pesanan')">Lihat Semua Pesanan &rarr;</button>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Pelanggan</th>
                  <th>Metode</th>
                  <th>Total</th>
                  <th>Status Pesanan</th>
                  <th>Waktu</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="recent-orders-list">
                <tr><td colspan="7" class="loading">Memuat pesanan terbaru...</td></tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <!-- 2. VIEW PRODUK -->
      <section id="view-produk" class="page-view">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Katalog & Persediaan</span>
            <h1>Kelola Produk & Stok Galon</h1>
          </div>
          <button class="btn btn-primary" onclick="openAddProductModal()">
            <svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Produk
          </button>
        </div>

        <div class="filter-bar">
          <div class="filter-chips" id="product-category-filters">
            <button class="chip active" data-cat="all">Semua Kategori</button>
            <button class="chip" data-cat="isi_ulang">Galon Isi Ulang</button>
            <button class="chip" data-cat="galon_baru">Galon Baru</button>
            <button class="chip" data-cat="galon_bekas">Galon Bekas</button>
          </div>
        </div>

        <div class="panel table-panel">
          <div class="table-responsive">
            <table class="data-table" id="table-products">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Produk</th>
                  <th>Kategori</th>
                  <th>Harga Satuan</th>
                  <th>Stok Isi (Siap Kirim)</th>
                  <th>Stok Kosong</th>
                  <th>Deskripsi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="products-tbody">
                <tr><td colspan="8" class="loading">Memuat produk...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 3. VIEW PESANAN -->
      <section id="view-pesanan" class="page-view">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Transaksi Pelanggan</span>
            <h1>Daftar Pesanan & Pengiriman</h1>
          </div>
          <div class="filter-chips" id="order-status-filters">
            <button class="chip active" data-status="semua">Semua</button>
            <button class="chip" data-status="menunggu_bayar">Menunggu Bayar</button>
            <button class="chip" data-status="diproses">Diproses</button>
            <button class="chip" data-status="dikirim">Dikirim</button>
            <button class="chip" data-status="selesai">Selesai</button>
            <button class="chip" data-status="dibatalkan">Dibatalkan</button>
          </div>
        </div>

        <div class="panel table-panel">
          <div class="table-responsive">
            <table class="data-table" id="table-orders">
              <thead>
                <tr>
                  <th>ID Pesanan</th>
                  <th>Pelanggan</th>
                  <th>Alamat Pengiriman</th>
                  <th>Metode Bayar</th>
                  <th>Status Bayar</th>
                  <th>Status Antar</th>
                  <th>Status Pesanan</th>
                  <th>Total</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="orders-tbody">
                <tr><td colspan="9" class="loading">Memuat data pesanan...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 4. VIEW PELANGGAN -->
      <section id="view-pelanggan" class="page-view">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Basis Data Pengguna</span>
            <h1>Pelanggan, Kurir & Pengguna</h1>
          </div>
          <button class="btn btn-primary" onclick="openAddUserModal()">
            <svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Pengguna
          </button>
        </div>

        <div class="filter-bar">
          <div class="filter-chips" id="user-role-filters">
            <button class="chip active" data-role="semua">Semua Pengguna</button>
            <button class="chip" data-role="masyarakat">Pelanggan (Masyarakat)</button>
            <button class="chip" data-role="tukang_galon">Kurir (Tukang Galon)</button>
            <button class="chip" data-role="admin">Admin</button>
          </div>
        </div>

        <div class="panel table-panel">
          <div class="table-responsive">
            <table class="data-table" id="table-users">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama Lengkap</th>
                  <th>Email</th>
                  <th>No HP / WA</th>
                  <th>Role</th>
                  <th>Poin Loyalitas</th>
                  <th>Status Kurir</th>
                  <th>Alamat Utama</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="users-tbody">
                <tr><td colspan="9" class="loading">Memuat daftar pengguna...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 5. VIEW KOMPLAIN -->
      <section id="view-komplain" class="page-view">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Layanan Purna Jual</span>
            <h1>Laporan Komplain Pelanggan</h1>
          </div>
          <div class="filter-chips" id="complaint-status-filters">
            <button class="chip active" data-cstatus="semua">Semua</button>
            <button class="chip" data-cstatus="diproses">Perlu Ditinjau (Diproses)</button>
            <button class="chip" data-cstatus="diretur">Diretur</button>
            <button class="chip" data-cstatus="selesai">Selesai</button>
          </div>
        </div>

        <div class="panel table-panel">
          <div class="table-responsive">
            <table class="data-table" id="table-complaints">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>No Pesanan</th>
                  <th>Pelapor</th>
                  <th>Kategori Masalah</th>
                  <th>Keterangan Komplain</th>
                  <th>Status</th>
                  <th>Waktu Lapor</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="complaints-tbody">
                <tr><td colspan="8" class="loading">Memuat data komplain...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 6. VIEW PENGATURAN -->
      <section id="view-pengaturan" class="page-view">
        <div class="page-heading">
          <div>
            <span class="breadcrumb">Konfigurasi Sistem</span>
            <h1>Promo Diskon & Info Depot</h1>
          </div>
        </div>

        <div class="settings-grid">
          <!-- PROMO MANAGEMENT -->
          <article class="panel">
            <div class="panel-heading">
              <div>
                <small class="panel-tag">Pemasaran</small>
                <h2>Kode Promo & Diskon</h2>
              </div>
              <button class="btn btn-sm btn-primary" onclick="openAddPromoModal()">+ Buat Promo</button>
            </div>
            <div class="table-responsive">
              <table class="data-table" id="table-promos">
                <thead>
                  <tr>
                    <th>Kode Promo</th>
                    <th>Nama Promo</th>
                    <th>Potongan</th>
                    <th>Min Poin</th>
                    <th>Berlaku Sampai</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody id="promos-tbody">
                  <tr><td colspan="6" class="loading">Memuat promo...</td></tr>
                </tbody>
              </table>
            </div>
          </article>

          <!-- PROFIL DEPOT / TOKO -->
          <article class="panel">
            <div class="panel-heading">
              <div>
                <small class="panel-tag">Identitas Usaha</small>
                <h2>Informasi Depot Galon</h2>
              </div>
            </div>
            <form id="store-profile-form" class="form-grid" onsubmit="event.preventDefault(); saveStoreProfile();">
              <div class="form-group">
                <label>Nama Usaha / Depot</label>
                <input type="text" id="cfg-store-name" value="Depot GalonKu Bandung" required>
              </div>
              <div class="form-group">
                <label>WhatsApp Layanan Pelanggan</label>
                <input type="text" id="cfg-store-wa" value="081234567890" required>
              </div>
              <div class="form-group full-width">
                <label>Alamat Lokasi Depot</label>
                <input type="text" id="cfg-store-address" value="Jl. Contoh No. 10, Bandung, Jawa Barat" required>
              </div>
              <div class="form-group">
                <label>Jam Operasional</label>
                <input type="text" id="cfg-store-hours" value="07:00 - 20:00 WIB" required>
              </div>
              <div class="form-group">
                <label>Radius Pengiriman Maksimal</label>
                <input type="text" id="cfg-store-radius" value="5 km" required>
              </div>
              <div class="form-group full-width" style="text-align: right; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">Simpan Profil Depot</button>
              </div>
            </form>
          </article>
        </div>
      </section>
    </div>
  </main>

  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <!-- ================= MODALS ================= -->

  <!-- 1. MODAL PRODUK -->
  <div class="modal" id="modal-product" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 id="modal-product-title">Tambah Produk Baru</h3>
        <button class="modal-close" onclick="closeModal('modal-product')">&times;</button>
      </div>
      <form id="form-product" onsubmit="event.preventDefault(); submitProductForm();">
        <input type="hidden" id="prod-id" value="0">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Produk <span class="req">*</span></label>
            <input type="text" id="prod-nama" required placeholder="Contoh: Isi Ulang 19 Liter">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Kategori <span class="req">*</span></label>
              <select id="prod-kategori" required>
                <option value="isi_ulang">Galon Isi Ulang</option>
                <option value="galon_baru">Galon Baru Siap Pakai</option>
                <option value="galon_bekas">Tukar Galon Bekas</option>
              </select>
            </div>
            <div class="form-group">
              <label>Harga Satuan (Rp) <span class="req">*</span></label>
              <input type="number" id="prod-harga" required min="0" step="500" placeholder="Contoh: 15000">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Stok Galon Isi (Siap Kirim)</label>
              <input type="number" id="prod-stok-isi" min="0" value="0">
            </div>
            <div class="form-group">
              <label>Stok Galon Kosong</label>
              <input type="number" id="prod-stok-kosong" min="0" value="0">
            </div>
          </div>
          <div class="form-group">
            <label>Deskripsi Produk</label>
            <textarea id="prod-deskripsi" rows="3" placeholder="Keterangan singkat produk..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modal-product')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Produk</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. MODAL DETAIL & EDIT PESANAN -->
  <div class="modal" id="modal-order" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-header">
        <h3 id="modal-order-title">Detail Pesanan #--</h3>
        <button class="modal-close" onclick="closeModal('modal-order')">&times;</button>
      </div>
      <div class="modal-body" id="modal-order-content">
        <p class="loading">Memuat detail...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-order')">Tutup</button>
      </div>
    </div>
  </div>

  <!-- 3. MODAL USER -->
  <div class="modal" id="modal-user" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 id="modal-user-title">Tambah Pengguna</h3>
        <button class="modal-close" onclick="closeModal('modal-user')">&times;</button>
      </div>
      <form id="form-user" onsubmit="event.preventDefault(); submitUserForm();">
        <input type="hidden" id="user-id" value="0">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Lengkap <span class="req">*</span></label>
            <input type="text" id="user-nama" required placeholder="Nama lengkap">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email <span class="req">*</span></label>
              <input type="email" id="user-email" required placeholder="user@contoh.test">
            </div>
            <div class="form-group">
              <label>No HP / WhatsApp <span class="req">*</span></label>
              <input type="tel" id="user-phone" required placeholder="08xxxxxxxxxx">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Role Pengguna <span class="req">*</span></label>
              <select id="user-role" required>
                <option value="masyarakat">Pelanggan (Masyarakat)</option>
                <option value="tukang_galon">Kurir (Tukang Galon)</option>
                <option value="admin">Administrator</option>
              </select>
            </div>
            <div class="form-group">
              <label>Poin Loyalitas</label>
              <input type="number" id="user-poin" value="0" min="0">
            </div>
          </div>
          <div class="form-group">
            <label>Kata Sandi <small id="pwd-note" style="color:var(--muted);">(Wajib untuk pengguna baru)</small></label>
            <input type="password" id="user-pwd" placeholder="Minimal 6 karakter">
          </div>
          <div class="form-group">
            <label>Alamat Lengkap</label>
            <textarea id="user-alamat" rows="2" placeholder="Alamat tinggal / pengiriman..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modal-user')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. MODAL PROMO -->
  <div class="modal" id="modal-promo" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 id="modal-promo-title">Tambah Promo Baru</h3>
        <button class="modal-close" onclick="closeModal('modal-promo')">&times;</button>
      </div>
      <form id="form-promo" onsubmit="event.preventDefault(); submitPromoForm();">
        <input type="hidden" id="promo-id" value="0">
        <div class="modal-body">
          <div class="form-group">
            <label>Kode Promo <span class="req">*</span></label>
            <input type="text" id="promo-kode" required placeholder="Contoh: HEMAT10RB" style="text-transform: uppercase;">
          </div>
          <div class="form-group">
            <label>Nama Promo <span class="req">*</span></label>
            <input type="text" id="promo-nama" required placeholder="Contoh: Diskon Pelanggan Setia">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Potongan Harga (Rp) <span class="req">*</span></label>
              <input type="number" id="promo-potongan" required min="1000" step="500" placeholder="10000">
            </div>
            <div class="form-group">
              <label>Syarat Min. Poin</label>
              <input type="number" id="promo-min-poin" value="0" min="0">
            </div>
          </div>
          <div class="form-group">
            <label>Berlaku Sampai Tanggal <span class="req">*</span></label>
            <input type="date" id="promo-tgl" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modal-promo')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Promo</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 5. MODAL DETAIL KOMPLAIN -->
  <div class="modal" id="modal-complaint" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3>Tindak Lanjut Komplain</h3>
        <button class="modal-close" onclick="closeModal('modal-complaint')">&times;</button>
      </div>
      <div class="modal-body" id="modal-complaint-content">
        <p class="loading">Memuat...</p>
      </div>
    </div>
  </div>

  <!-- TOAST NOTIFICATION -->
  <div id="toast" class="toast"></div>

  <script src="assets/admin.js"></script>
</body>
</html>

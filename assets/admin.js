// ================= UTILITIES =================
const rupiah = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0
});

function escapeHtml(val) {
  return String(val ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function showToast(message, isError = false) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.style.background = isError ? '#ef4444' : '#0f172a';
  toast.classList.add('show');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.classList.remove('show');
  }, 3200);
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }
}

// Close modals on clicking backdrop
document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal(modal.id);
  });
});

// ESC key to close modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(m => closeModal(m.id));
  }
});

// ================= LIVE CLOCK =================
function updateClock() {
  const clock = document.getElementById('live-clock');
  if (clock) {
    const now = new Date();
    clock.textContent = now.toLocaleDateString('id-ID', {
      weekday: 'short', day: 'numeric', month: 'short'
    }) + ' • ' + now.toLocaleTimeString('id-ID');
  }
}
setInterval(updateClock, 1000);
updateClock();

// ================= NAVIGATION =================
let currentPage = 'Dashboard';

function switchPage(pageName) {
  currentPage = pageName;
  document.querySelectorAll('.nav-item').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.page === pageName);
  });

  document.querySelectorAll('.page-view').forEach(view => {
    view.classList.remove('active');
  });

  const targetId = 'view-' + pageName.toLowerCase();
  const targetView = document.getElementById(targetId);
  if (targetView) {
    targetView.classList.add('active');
  }

  // Close mobile sidebar if open
  sidebar.classList.remove('open');
  sidebarBackdrop.classList.remove('open');

  // Clear global search input
  const searchInput = document.getElementById('global-search');
  if (searchInput) searchInput.value = '';

  // Load view data
  loadCurrentPageData();
}

function loadCurrentPageData() {
  switch (currentPage) {
    case 'Dashboard':
      loadDashboard();
      break;
    case 'Produk':
      loadProducts();
      break;
    case 'Pesanan':
      loadOrders();
      break;
    case 'Pelanggan':
      loadUsers();
      break;
    case 'Komplain':
      loadComplaints();
      break;
    case 'Pengaturan':
      loadPromos();
      break;
  }
}

document.querySelectorAll('.nav-item').forEach(button => {
  button.addEventListener('click', () => {
    switchPage(button.dataset.page);
  });
});

// Sidebar drawer for mobile
const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');
const menuButton = document.getElementById('menu-button');

if (menuButton) {
  menuButton.addEventListener('click', () => {
    sidebar.classList.add('open');
    sidebarBackdrop.classList.add('open');
  });
}

if (sidebarBackdrop) {
  sidebarBackdrop.addEventListener('click', () => {
    sidebar.classList.remove('open');
    sidebarBackdrop.classList.remove('open');
  });
}

// Refresh button
document.getElementById('btn-refresh')?.addEventListener('click', () => {
  loadCurrentPageData();
  showToast('Data berhasil dimuat ulang');
});

document.getElementById('btn-notif')?.addEventListener('click', () => {
  switchPage('Komplain');
});

// ================= 1. DASHBOARD =================
async function loadDashboard() {
  try {
    const res = await fetch('api/admin_api.php?action=dashboard');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    const { penjualan, saldo_tunai, komplain, pesanan_aktif, inventori, pesanan_terbaru, monthly_sales } = result.data;
    const salesTotal = Number(penjualan?.total_penjualan || 0);

    // Stats
    document.getElementById('stat-orders').textContent = Number(penjualan?.jumlah_order || 0).toLocaleString('id-ID');
    document.getElementById('stat-sales').textContent = rupiah.format(salesTotal);
    document.getElementById('stat-cash').textContent = rupiah.format(Number(saldo_tunai?.saldo_tunai || 0));
    document.getElementById('stat-complaints').textContent = Number(komplain?.jumlah_komplain || 0).toLocaleString('id-ID');
    document.getElementById('sales-total').textContent = rupiah.format(salesTotal);

    // Badges
    const badgeKomplain = document.getElementById('badge-komplain');
    const topbarNotifDot = document.getElementById('topbar-notif-dot');
    const countKomplain = Number(komplain?.jumlah_komplain || 0);
    if (countKomplain > 0) {
      badgeKomplain.textContent = countKomplain;
      badgeKomplain.style.display = 'inline-block';
      topbarNotifDot.style.display = 'block';
    } else {
      badgeKomplain.style.display = 'none';
      topbarNotifDot.style.display = 'none';
    }

    const badgePesanan = document.getElementById('badge-pesanan');
    const countAktif = Number(pesanan_aktif?.total_aktif || 0);
    if (countAktif > 0) {
      badgePesanan.textContent = countAktif;
      badgePesanan.style.display = 'inline-block';
    } else {
      badgePesanan.style.display = 'none';
    }

    // Render monthly chart
    renderMonthlyChart(monthly_sales);

    // Render mini inventory
    const invEl = document.getElementById('inventory-list');
    if (inventori && inventori.length > 0) {
      invEl.innerHTML = inventori.map(item => `
        <div class="inventory-row">
          <div class="inv-info">
            <strong>${escapeHtml(item.nama_produk)}</strong>
            <small>${item.kategori === 'isi_ulang' ? 'Galon Isi Ulang' : item.kategori === 'galon_baru' ? 'Galon Baru' : 'Galon Bekas'}</small>
          </div>
          <div class="inv-badges">
            <span class="badge-stock" title="Siap Kirim">${Number(item.stok_isi || 0)} Isi</span>
            <span class="badge-stock empty" title="Galon Kosong">${Number(item.stok_kosong || 0)} Kosong</span>
          </div>
        </div>
      `).join('');
    } else {
      invEl.innerHTML = '<p class="loading">Belum ada inventori produk.</p>';
    }

    // Render recent orders
    const recentEl = document.getElementById('recent-orders-list');
    if (pesanan_terbaru && pesanan_terbaru.length > 0) {
      recentEl.innerHTML = pesanan_terbaru.map(order => `
        <tr>
          <td><strong>#${order.transaksi_id}</strong></td>
          <td>${escapeHtml(order.nama_pelanggan)}</td>
          <td><span class="badge badge-gray">${escapeHtml(order.metode_bayar || '-')}</span></td>
          <td><strong>${rupiah.format(Number(order.total_harga || 0))}</strong></td>
          <td>${renderOrderStatusBadge(order.status_pesanan)}</td>
          <td><small style="color:var(--muted);">${formatDate(order.created_at)}</small></td>
          <td>
            <button class="btn btn-sm btn-outline" onclick="openOrderDetailModal(${order.transaksi_id})">Detail</button>
          </td>
        </tr>
      `).join('');
    } else {
      recentEl.innerHTML = '<tr><td colspan="7" class="loading">Belum ada transaksi pesanan terbaru.</td></tr>';
    }

  } catch (err) {
    console.error(err);
    showToast('Gagal memuat dashboard: ' + err.message, true);
  }
}

function renderMonthlyChart(monthlySales) {
  const container = document.getElementById('chart-container');
  if (!container) return;

  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
  const values = months.map((_, i) => Number(monthlySales ? monthlySales[i + 1] : 0) || 0);
  const maxVal = Math.max(...values, 50000);

  let html = '<div class="chart-bars-wrap">';
  months.forEach((m, idx) => {
    const val = values[idx];
    const pct = Math.max(4, Math.round((val / maxVal) * 100));
    html += `
      <div class="bar-col">
        <div class="bar-tooltip">${m}: ${rupiah.format(val)}</div>
        <div class="bar-fill" style="height: ${pct}%;"></div>
        <span class="bar-label">${m}</span>
      </div>
    `;
  });
  html += '</div>';

  container.innerHTML = html;
}

// ================= 2. PRODUK =================
let allProducts = [];
let currentProductCat = 'all';

async function loadProducts() {
  try {
    const res = await fetch('api/admin_api.php?action=products');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    allProducts = result.data || [];
    renderProductTable();
  } catch (err) {
    showToast('Gagal memuat produk: ' + err.message, true);
  }
}

function renderProductTable(filterQuery = '') {
  const tbody = document.getElementById('products-tbody');
  if (!tbody) return;

  let filtered = allProducts;
  if (currentProductCat !== 'all') {
    filtered = filtered.filter(p => p.kategori === currentProductCat);
  }
  if (filterQuery.trim() !== '') {
    const q = filterQuery.toLowerCase();
    filtered = filtered.filter(p => 
      p.nama_produk.toLowerCase().includes(q) ||
      p.kategori.toLowerCase().includes(q) ||
      String(p.harga).includes(q)
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="loading">Tidak ada produk ditemukan.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map((item, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>
        <strong>${escapeHtml(item.nama_produk)}</strong>
      </td>
      <td>
        <span class="badge ${item.kategori === 'isi_ulang' ? 'badge-blue' : item.kategori === 'galon_baru' ? 'badge-green' : 'badge-yellow'}">
          ${item.kategori === 'isi_ulang' ? 'Isi Ulang' : item.kategori === 'galon_baru' ? 'Galon Baru' : 'Galon Bekas'}
        </span>
      </td>
      <td><strong>${rupiah.format(Number(item.harga))}</strong></td>
      <td>
        <div class="stock-stepper">
          <button class="btn-step" onclick="adjustProductStock(${item.produk_id}, 'isi', -1)" title="Kurangi 1">-</button>
          <strong style="min-width: 32px; text-align: center;">${item.stok_isi}</strong>
          <button class="btn-step" onclick="adjustProductStock(${item.produk_id}, 'isi', 1)" title="Tambah 1">+</button>
        </div>
      </td>
      <td>
        <div class="stock-stepper">
          <button class="btn-step" onclick="adjustProductStock(${item.produk_id}, 'kosong', -1)" title="Kurangi 1">-</button>
          <strong style="min-width: 32px; text-align: center;">${item.stok_kosong}</strong>
          <button class="btn-step" onclick="adjustProductStock(${item.produk_id}, 'kosong', 1)" title="Tambah 1">+</button>
        </div>
      </td>
      <td><small style="color:var(--muted);">${escapeHtml(item.deskripsi || '-')}</small></td>
      <td>
        <div class="btn-action-group">
          <button class="btn-action" onclick="openEditProductModal(${item.produk_id})" title="Edit Produk">
            <svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          </button>
          <button class="btn-action delete" onclick="deleteProduct(${item.produk_id})" title="Hapus Produk">
            <svg viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

// Category filter buttons
document.querySelectorAll('#product-category-filters .chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('#product-category-filters .chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    currentProductCat = chip.dataset.cat;
    renderProductTable(document.getElementById('global-search')?.value || '');
  });
});

async function adjustProductStock(id, type, delta) {
  try {
    const formData = new FormData();
    formData.append('action', 'adjust_stock');
    formData.append('produk_id', id);
    formData.append('type', type);
    formData.append('delta', delta);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    // Optimistic update local
    const p = allProducts.find(item => item.produk_id == id);
    if (p) {
      if (type === 'kosong') p.stok_kosong = Math.max(0, Number(p.stok_kosong) + delta);
      else p.stok_isi = Math.max(0, Number(p.stok_isi) + delta);
    }
    renderProductTable(document.getElementById('global-search')?.value || '');
    showToast(result.message);
  } catch (err) {
    showToast(err.message, true);
  }
}

function openAddProductModal() {
  document.getElementById('modal-product-title').textContent = 'Tambah Produk Baru';
  document.getElementById('prod-id').value = '0';
  document.getElementById('prod-nama').value = '';
  document.getElementById('prod-kategori').value = 'isi_ulang';
  document.getElementById('prod-harga').value = '';
  document.getElementById('prod-stok-isi').value = '0';
  document.getElementById('prod-stok-kosong').value = '0';
  document.getElementById('prod-deskripsi').value = '';
  openModal('modal-product');
}

function openEditProductModal(id) {
  const p = allProducts.find(item => item.produk_id == id);
  if (!p) return;
  document.getElementById('modal-product-title').textContent = 'Edit Produk #' + id;
  document.getElementById('prod-id').value = p.produk_id;
  document.getElementById('prod-nama').value = p.nama_produk;
  document.getElementById('prod-kategori').value = p.kategori;
  document.getElementById('prod-harga').value = Number(p.harga);
  document.getElementById('prod-stok-isi').value = p.stok_isi;
  document.getElementById('prod-stok-kosong').value = p.stok_kosong;
  document.getElementById('prod-deskripsi').value = p.deskripsi || '';
  openModal('modal-product');
}

async function submitProductForm() {
  try {
    const formData = new FormData();
    formData.append('action', 'save_product');
    formData.append('produk_id', document.getElementById('prod-id').value);
    formData.append('nama_produk', document.getElementById('prod-nama').value);
    formData.append('kategori', document.getElementById('prod-kategori').value);
    formData.append('harga', document.getElementById('prod-harga').value);
    formData.append('stok_isi', document.getElementById('prod-stok-isi').value);
    formData.append('stok_kosong', document.getElementById('prod-stok-kosong').value);
    formData.append('deskripsi', document.getElementById('prod-deskripsi').value);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    closeModal('modal-product');
    showToast(result.message);
    loadProducts();
  } catch (err) {
    showToast(err.message, true);
  }
}

async function deleteProduct(id) {
  if (!confirm(`Yakin ingin menghapus produk ini?`)) return;
  try {
    const formData = new FormData();
    formData.append('action', 'delete_product');
    formData.append('produk_id', id);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    showToast(result.message);
    loadProducts();
  } catch (err) {
    showToast(err.message, true);
  }
}

// ================= 3. PESANAN =================
let allOrders = [];
let currentOrderStatus = 'semua';
let allCouriers = [];

async function loadOrders() {
  try {
    const res = await fetch('api/admin_api.php?action=orders');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    allOrders = result.data || [];
    renderOrdersTable();

    // Load couriers list in background for assignment dropdown
    loadCouriersList();
  } catch (err) {
    showToast('Gagal memuat pesanan: ' + err.message, true);
  }
}

async function loadCouriersList() {
  try {
    const res = await fetch('api/admin_api.php?action=couriers');
    const result = await res.json();
    if (result.status === 'success') {
      allCouriers = result.data || [];
    }
  } catch (e) {
    console.error(e);
  }
}

function renderOrdersTable(filterQuery = '') {
  const tbody = document.getElementById('orders-tbody');
  if (!tbody) return;

  let filtered = allOrders;
  if (currentOrderStatus !== 'semua') {
    filtered = filtered.filter(o => o.status_pesanan === currentOrderStatus);
  }
  if (filterQuery.trim() !== '') {
    const q = filterQuery.toLowerCase();
    filtered = filtered.filter(o =>
      String(o.transaksi_id).includes(q) ||
      o.nama_pelanggan.toLowerCase().includes(q) ||
      (o.alamat_lengkap && o.alamat_lengkap.toLowerCase().includes(q)) ||
      (o.nama_kurir && o.nama_kurir.toLowerCase().includes(q))
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" class="loading">Tidak ada data pesanan ditemukan.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(order => `
    <tr>
      <td><strong>#${order.transaksi_id}</strong></td>
      <td>
        <strong>${escapeHtml(order.nama_pelanggan)}</strong>
        <br><small style="color:var(--muted);">${escapeHtml(order.no_hp_pelanggan || '-')}</small>
      </td>
      <td>
        <small>${escapeHtml(order.alamat_lengkap || '-')}</small>
      </td>
      <td><span class="badge badge-gray">${escapeHtml(order.metode_bayar || 'tunai')}</span></td>
      <td>
        <span class="badge ${order.status_bayar === 'lunas' ? 'badge-green' : order.status_bayar === 'pending' ? 'badge-yellow' : 'badge-red'}">
          ${order.status_bayar || 'pending'}
        </span>
      </td>
      <td>
        <small style="font-weight:600; color:${order.status_antar === 'sampai_tujuan' ? 'var(--green)' : 'var(--ink)'};">
          ${order.nama_kurir ? `${escapeHtml(order.nama_kurir)} (${order.status_antar})` : 'Belum Ditugaskan'}
        </small>
      </td>
      <td>${renderOrderStatusBadge(order.status_pesanan)}</td>
      <td><strong>${rupiah.format(Number(order.total_harga))}</strong></td>
      <td>
        <button class="btn btn-sm btn-outline" onclick="openOrderDetailModal(${order.transaksi_id})">Kelola</button>
      </td>
    </tr>
  `).join('');
}

// Order status filter buttons
document.querySelectorAll('#order-status-filters .chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('#order-status-filters .chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    currentOrderStatus = chip.dataset.status;
    renderOrdersTable(document.getElementById('global-search')?.value || '');
  });
});

async function openOrderDetailModal(id) {
  document.getElementById('modal-order-title').textContent = `Detail Pesanan #${id}`;
  const container = document.getElementById('modal-order-content');
  container.innerHTML = '<p class="loading">Memuat rincian pesanan...</p>';
  openModal('modal-order');

  try {
    const res = await fetch(`api/admin_api.php?action=order_detail&id=${id}`);
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    const order = result.data;
    if (allCouriers.length === 0) await loadCouriersList();

    const courierOptions = allCouriers.map(k => `
      <option value="${k.user_id}" ${k.user_id == order.kurir_id ? 'selected' : ''}>
        ${escapeHtml(k.nama)} (${k.status_aktif}) - ${k.no_hp}
      </option>
    `).join('');

    container.innerHTML = `
      <div class="order-detail-grid">
        <div class="detail-box">
          <span>Pelanggan</span>
          <strong>${escapeHtml(order.nama_pelanggan)}</strong>
          <small>${escapeHtml(order.no_hp_pelanggan || '')} | ${escapeHtml(order.email_pelanggan || '')}</small>
        </div>
        <div class="detail-box">
          <span>Waktu Pemesanan</span>
          <strong>${formatDate(order.created_at)}</strong>
          <small>ID Transaksi: #${order.transaksi_id}</small>
        </div>
        <div class="detail-box">
          <span>Alamat Pengiriman</span>
          <strong>${escapeHtml(order.label_alamat || 'Rumah')}</strong>
          <small>${escapeHtml(order.alamat_lengkap || '-')}</small>
        </div>
        <div class="detail-box">
          <span>Pembayaran</span>
          <strong>${rupiah.format(Number(order.total_harga))} (${escapeHtml(order.metode_bayar || 'tunai')})</strong>
          <small>Status Bayar: <b>${escapeHtml(order.status_bayar || 'pending')}</b></small>
        </div>
      </div>

      <h4 style="font-size:13.5px; font-weight:700; margin-top:8px;">Daftar Item Galon</h4>
      <div class="order-items-list">
        ${(order.items || []).map(item => `
          <div class="order-item-row">
            <div>
              <strong>${escapeHtml(item.nama_produk)}</strong>
              <div style="font-size:11.5px; color:var(--muted);">${item.jumlah} x ${rupiah.format(Number(item.harga_satuan))}</div>
            </div>
            <strong>${rupiah.format(Number(item.subtotal))}</strong>
          </div>
        `).join('')}
      </div>

      <div class="order-actions-box">
        <h4>Perbarui Status & Pengiriman</h4>
        <form onsubmit="event.preventDefault(); submitUpdateOrder(${order.transaksi_id});">
          <div class="form-row">
            <div class="form-group">
              <label>Status Pesanan</label>
              <select id="edit-order-status">
                <option value="menunggu_bayar" ${order.status_pesanan === 'menunggu_bayar' ? 'selected' : ''}>Menunggu Bayar</option>
                <option value="diproses" ${order.status_pesanan === 'diproses' ? 'selected' : ''}>Diproses</option>
                <option value="dikirim" ${order.status_pesanan === 'dikirim' ? 'selected' : ''}>Dikirim (Dalam Pengantaran)</option>
                <option value="selesai" ${order.status_pesanan === 'selesai' ? 'selected' : ''}>Selesai</option>
                <option value="dibatalkan" ${order.status_pesanan === 'dibatalkan' ? 'selected' : ''}>Dibatalkan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Pilih Kurir Antar</label>
              <select id="edit-order-courier">
                <option value="0">-- Pilih Kurir Galon --</option>
                ${courierOptions}
              </select>
            </div>
          </div>
          <div class="form-row" style="margin-top:10px;">
            <div class="form-group">
              <label>Status Pembayaran</label>
              <select id="edit-order-pay-status">
                <option value="pending" ${order.status_bayar === 'pending' ? 'selected' : ''}>Pending (Belum Dibayar)</option>
                <option value="lunas" ${order.status_bayar === 'lunas' ? 'selected' : ''}>Lunas</option>
                <option value="gagal" ${order.status_bayar === 'gagal' ? 'selected' : ''}>Gagal</option>
              </select>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
              <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Status Pesanan</button>
            </div>
          </div>
        </form>
      </div>
    `;
  } catch (err) {
    container.innerHTML = `<p class="loading" style="color:var(--red);">Gagal memuat rincian: ${escapeHtml(err.message)}</p>`;
  }
}

async function submitUpdateOrder(id) {
  try {
    const statusPesanan = document.getElementById('edit-order-status').value;
    const kurirId = document.getElementById('edit-order-courier').value;
    const statusBayar = document.getElementById('edit-order-pay-status').value;

    const formData = new FormData();
    formData.append('action', 'update_order_status');
    formData.append('transaksi_id', id);
    formData.append('status_pesanan', statusPesanan);
    formData.append('kurir_id', kurirId);
    formData.append('status_bayar', statusBayar);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    showToast(result.message);
    closeModal('modal-order');
    loadOrders();
    loadDashboard();
  } catch (err) {
    showToast(err.message, true);
  }
}

// ================= 4. PELANGGAN & PENGGUNA =================
let allUsers = [];
let currentUserRole = 'semua';

async function loadUsers() {
  try {
    const res = await fetch('api/admin_api.php?action=users');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    allUsers = result.data || [];
    renderUsersTable();
  } catch (err) {
    showToast('Gagal memuat pengguna: ' + err.message, true);
  }
}

function renderUsersTable(filterQuery = '') {
  const tbody = document.getElementById('users-tbody');
  if (!tbody) return;

  let filtered = allUsers;
  if (currentUserRole !== 'semua') {
    filtered = filtered.filter(u => u.role === currentUserRole);
  }
  if (filterQuery.trim() !== '') {
    const q = filterQuery.toLowerCase();
    filtered = filtered.filter(u =>
      u.nama.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q) ||
      u.no_hp.includes(q) ||
      (u.alamat_lengkap && u.alamat_lengkap.toLowerCase().includes(q))
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" class="loading">Tidak ada pengguna ditemukan.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(u => `
    <tr>
      <td>${u.user_id}</td>
      <td><strong>${escapeHtml(u.nama)}</strong></td>
      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.no_hp)}</td>
      <td>
        <span class="badge ${u.role === 'admin' ? 'badge-purple' : u.role === 'tukang_galon' ? 'badge-blue' : 'badge-green'}">
          ${u.role === 'admin' ? 'Admin' : u.role === 'tukang_galon' ? 'Kurir' : 'Pelanggan'}
        </span>
      </td>
      <td><strong>${u.poin_loyalitas} Poin</strong></td>
      <td>
        ${u.role === 'tukang_galon' 
          ? `<span class="badge ${u.status_aktif === 'online' ? 'badge-green' : 'badge-gray'}">${u.status_aktif}</span>` 
          : '-'}
      </td>
      <td><small>${escapeHtml(u.alamat_lengkap || '-')}</small></td>
      <td>
        <div class="btn-action-group">
          <button class="btn-action" onclick="openEditUserModal(${u.user_id})" title="Edit Pengguna">
            <svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          </button>
          ${u.user_id != 1 ? `
            <button class="btn-action delete" onclick="deleteUser(${u.user_id})" title="Hapus Pengguna">
              <svg viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          ` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

document.querySelectorAll('#user-role-filters .chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('#user-role-filters .chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    currentUserRole = chip.dataset.role;
    renderUsersTable(document.getElementById('global-search')?.value || '');
  });
});

function openAddUserModal() {
  document.getElementById('modal-user-title').textContent = 'Tambah Pengguna Baru';
  document.getElementById('user-id').value = '0';
  document.getElementById('user-nama').value = '';
  document.getElementById('user-email').value = '';
  document.getElementById('user-phone').value = '';
  document.getElementById('user-role').value = 'masyarakat';
  document.getElementById('user-poin').value = '0';
  document.getElementById('user-pwd').value = '';
  document.getElementById('user-alamat').value = '';
  document.getElementById('pwd-note').textContent = '(Wajib untuk pengguna baru minimal 6 karakter)';
  openModal('modal-user');
}

function openEditUserModal(id) {
  const u = allUsers.find(item => item.user_id == id);
  if (!u) return;
  document.getElementById('modal-user-title').textContent = 'Edit Pengguna #' + id;
  document.getElementById('user-id').value = u.user_id;
  document.getElementById('user-nama').value = u.nama;
  document.getElementById('user-email').value = u.email;
  document.getElementById('user-phone').value = u.no_hp;
  document.getElementById('user-role').value = u.role;
  document.getElementById('user-poin').value = u.poin_loyalitas;
  document.getElementById('user-pwd').value = '';
  document.getElementById('user-alamat').value = u.alamat_lengkap || '';
  document.getElementById('pwd-note').textContent = '(Kosongkan jika tidak ingin mengubah kata sandi)';
  openModal('modal-user');
}

async function submitUserForm() {
  try {
    const formData = new FormData();
    formData.append('action', 'save_user');
    formData.append('user_id', document.getElementById('user-id').value);
    formData.append('nama', document.getElementById('user-nama').value);
    formData.append('email', document.getElementById('user-email').value);
    formData.append('no_hp', document.getElementById('user-phone').value);
    formData.append('role', document.getElementById('user-role').value);
    formData.append('poin_loyalitas', document.getElementById('user-poin').value);
    formData.append('password', document.getElementById('user-pwd').value);
    formData.append('alamat', document.getElementById('user-alamat').value);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    closeModal('modal-user');
    showToast(result.message);
    loadUsers();
  } catch (err) {
    showToast(err.message, true);
  }
}

async function deleteUser(id) {
  if (!confirm(`Yakin ingin menghapus pengguna ini?`)) return;
  try {
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', id);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    showToast(result.message);
    loadUsers();
  } catch (err) {
    showToast(err.message, true);
  }
}

// ================= 5. KOMPLAIN =================
let allComplaints = [];
let currentComplaintStatus = 'semua';

async function loadComplaints() {
  try {
    const res = await fetch('api/admin_api.php?action=complaints');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    allComplaints = result.data || [];
    renderComplaintsTable();
  } catch (err) {
    showToast('Gagal memuat komplain: ' + err.message, true);
  }
}

function renderComplaintsTable(filterQuery = '') {
  const tbody = document.getElementById('complaints-tbody');
  if (!tbody) return;

  let filtered = allComplaints;
  if (currentComplaintStatus !== 'semua') {
    filtered = filtered.filter(c => c.status_laporan === currentComplaintStatus);
  }
  if (filterQuery.trim() !== '') {
    const q = filterQuery.toLowerCase();
    filtered = filtered.filter(c =>
      String(c.laporan_id).includes(q) ||
      String(c.transaksi_id).includes(q) ||
      c.nama_pelapor.toLowerCase().includes(q) ||
      c.deskripsi.toLowerCase().includes(q)
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="loading">Tidak ada komplain ditemukan.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(item => `
    <tr>
      <td><strong>#${item.laporan_id}</strong></td>
      <td><a href="javascript:void(0)" onclick="openOrderDetailModal(${item.transaksi_id})">#${item.transaksi_id}</a></td>
      <td>
        <strong>${escapeHtml(item.nama_pelapor)}</strong>
        <br><small style="color:var(--muted);">${escapeHtml(item.no_hp_pelapor || '')}</small>
      </td>
      <td>
        <span class="badge ${item.kategori_masalah === 'air_kotor' ? 'badge-yellow' : 'badge-red'}">
          ${item.kategori_masalah === 'air_kotor' ? 'Air Kotor/Bau' : 'Galon Rusak/Bocor'}
        </span>
      </td>
      <td><small>${escapeHtml(item.deskripsi)}</small></td>
      <td>
        <span class="badge ${item.status_laporan === 'selesai' ? 'badge-green' : item.status_laporan === 'diretur' ? 'badge-yellow' : 'badge-red'}">
          ${item.status_laporan}
        </span>
      </td>
      <td><small style="color:var(--muted);">${formatDate(item.created_at)}</small></td>
      <td>
        <button class="btn btn-sm btn-outline" onclick="openComplaintModal(${item.laporan_id})">Tindak Lanjut</button>
      </td>
    </tr>
  `).join('');
}

document.querySelectorAll('#complaint-status-filters .chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('#complaint-status-filters .chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    currentComplaintStatus = chip.dataset.cstatus;
    renderComplaintsTable(document.getElementById('global-search')?.value || '');
  });
});

function openComplaintModal(id) {
  const item = allComplaints.find(c => c.laporan_id == id);
  if (!item) return;

  const container = document.getElementById('modal-complaint-content');
  container.innerHTML = `
    <div class="order-detail-grid">
      <div class="detail-box">
        <span>Pelapor</span>
        <strong>${escapeHtml(item.nama_pelapor)}</strong>
        <small>${escapeHtml(item.no_hp_pelapor || '')}</small>
      </div>
      <div class="detail-box">
        <span>ID Pesanan</span>
        <strong>#${item.transaksi_id}</strong>
        <small>Total: ${rupiah.format(Number(item.total_harga || 0))}</small>
      </div>
    </div>

    <div style="margin: 10px 0;">
      <span style="font-size:12px; font-weight:700; color:var(--muted);">Kategori Masalah:</span>
      <div style="margin-top:4px;">
        <span class="badge ${item.kategori_masalah === 'air_kotor' ? 'badge-yellow' : 'badge-red'}">
          ${item.kategori_masalah === 'air_kotor' ? 'Air Kotor / Berbau' : 'Galon Rusak / Bocor'}
        </span>
      </div>
    </div>

    <div style="margin: 10px 0;">
      <span style="font-size:12px; font-weight:700; color:var(--muted);">Keluhan Pelanggan:</span>
      <p style="background:var(--canvas); padding:10px 14px; border-radius:var(--radius-sm); border:1px solid var(--line-light); font-size:13px; margin-top:4px;">
        ${escapeHtml(item.deskripsi)}
      </p>
    </div>

    <div class="order-actions-box">
      <h4>Pilih Solusi / Perbarui Status</h4>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-sm btn-outline" onclick="updateComplaintStatus(${item.laporan_id}, 'diproses')">
          Tandai Masih Diproses
        </button>
        <button class="btn btn-sm btn-primary" onclick="updateComplaintStatus(${item.laporan_id}, 'diretur')">
          Setujui Retur / Ganti Galon
        </button>
        <button class="btn btn-sm btn-primary" style="background:var(--green); border-color:var(--green);" onclick="updateComplaintStatus(${item.laporan_id}, 'selesai')">
          Tandai Komplain Selesai
        </button>
      </div>
    </div>
  `;

  openModal('modal-complaint');
}

async function updateComplaintStatus(id, newStatus) {
  try {
    const formData = new FormData();
    formData.append('action', 'update_complaint');
    formData.append('laporan_id', id);
    formData.append('status_laporan', newStatus);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    showToast(result.message);
    closeModal('modal-complaint');
    loadComplaints();
    loadDashboard();
  } catch (err) {
    showToast(err.message, true);
  }
}

// ================= 6. PROMO & PENGATURAN =================
let allPromos = [];

async function loadPromos() {
  try {
    const res = await fetch('api/admin_api.php?action=promos');
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    allPromos = result.data || [];
    renderPromosTable();
    loadStoreProfile();
  } catch (err) {
    showToast('Gagal memuat promo: ' + err.message, true);
  }
}

function renderPromosTable() {
  const tbody = document.getElementById('promos-tbody');
  if (!tbody) return;

  if (allPromos.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="loading">Belum ada promo terdaftar.</td></tr>';
    return;
  }

  tbody.innerHTML = allPromos.map(item => `
    <tr>
      <td><strong>${escapeHtml(item.kode_promo)}</strong></td>
      <td>${escapeHtml(item.nama_promo)}</td>
      <td><strong>${rupiah.format(Number(item.potongan_harga))}</strong></td>
      <td>${item.min_poin_syarat} Poin</td>
      <td>${item.tanggal_berakhir}</td>
      <td>
        <div class="btn-action-group">
          <button class="btn-action" onclick="openEditPromoModal(${item.promo_id})" title="Edit Promo">
            <svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          </button>
          <button class="btn-action delete" onclick="deletePromo(${item.promo_id})" title="Hapus Promo">
            <svg viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function openAddPromoModal() {
  document.getElementById('modal-promo-title').textContent = 'Buat Promo Baru';
  document.getElementById('promo-id').value = '0';
  document.getElementById('promo-kode').value = '';
  document.getElementById('promo-nama').value = '';
  document.getElementById('promo-potongan').value = '';
  document.getElementById('promo-min-poin').value = '0';
  document.getElementById('promo-tgl').value = new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0];
  openModal('modal-promo');
}

function openEditPromoModal(id) {
  const p = allPromos.find(item => item.promo_id == id);
  if (!p) return;
  document.getElementById('modal-promo-title').textContent = 'Edit Promo #' + id;
  document.getElementById('promo-id').value = p.promo_id;
  document.getElementById('promo-kode').value = p.kode_promo;
  document.getElementById('promo-nama').value = p.nama_promo;
  document.getElementById('promo-potongan').value = Number(p.potongan_harga);
  document.getElementById('promo-min-poin').value = p.min_poin_syarat;
  document.getElementById('promo-tgl').value = p.tanggal_berakhir;
  openModal('modal-promo');
}

async function submitPromoForm() {
  try {
    const formData = new FormData();
    formData.append('action', 'save_promo');
    formData.append('promo_id', document.getElementById('promo-id').value);
    formData.append('kode_promo', document.getElementById('promo-kode').value);
    formData.append('nama_promo', document.getElementById('promo-nama').value);
    formData.append('potongan_harga', document.getElementById('promo-potongan').value);
    formData.append('min_poin_syarat', document.getElementById('promo-min-poin').value);
    formData.append('tanggal_berakhir', document.getElementById('promo-tgl').value);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    closeModal('modal-promo');
    showToast(result.message);
    loadPromos();
  } catch (err) {
    showToast(err.message, true);
  }
}

async function deletePromo(id) {
  if (!confirm(`Hapus kode promo ini?`)) return;
  try {
    const formData = new FormData();
    formData.append('action', 'delete_promo');
    formData.append('promo_id', id);

    const res = await fetch('api/admin_api.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status !== 'success') throw new Error(result.message);

    showToast(result.message);
    loadPromos();
  } catch (err) {
    showToast(err.message, true);
  }
}

function loadStoreProfile() {
  const saved = localStorage.getItem('galonku_store_cfg');
  if (saved) {
    try {
      const cfg = JSON.parse(saved);
      if (cfg.name) document.getElementById('cfg-store-name').value = cfg.name;
      if (cfg.wa) document.getElementById('cfg-store-wa').value = cfg.wa;
      if (cfg.address) document.getElementById('cfg-store-address').value = cfg.address;
      if (cfg.hours) document.getElementById('cfg-store-hours').value = cfg.hours;
      if (cfg.radius) document.getElementById('cfg-store-radius').value = cfg.radius;
    } catch (e) {}
  }
}

function saveStoreProfile() {
  const cfg = {
    name: document.getElementById('cfg-store-name').value,
    wa: document.getElementById('cfg-store-wa').value,
    address: document.getElementById('cfg-store-address').value,
    hours: document.getElementById('cfg-store-hours').value,
    radius: document.getElementById('cfg-store-radius').value,
  };
  localStorage.setItem('galonku_store_cfg', JSON.stringify(cfg));
  showToast('Profil Depot GalonKu berhasil disimpan!');
}

// ================= GLOBAL SEARCH FILTER =================
document.getElementById('global-search')?.addEventListener('input', (e) => {
  const q = e.target.value;
  switch (currentPage) {
    case 'Produk':
      renderProductTable(q);
      break;
    case 'Pesanan':
      renderOrdersTable(q);
      break;
    case 'Pelanggan':
      renderUsersTable(q);
      break;
    case 'Komplain':
      renderComplaintsTable(q);
      break;
  }
});

// ================= HELPERS =================
function renderOrderStatusBadge(status) {
  switch (status) {
    case 'selesai':
      return '<span class="badge badge-green">Selesai</span>';
    case 'dikirim':
      return '<span class="badge badge-blue">Dikirim</span>';
    case 'diproses':
      return '<span class="badge badge-yellow">Diproses</span>';
    case 'menunggu_bayar':
      return '<span class="badge badge-purple">Menunggu Bayar</span>';
    case 'dibatalkan':
      return '<span class="badge badge-red">Dibatalkan</span>';
    default:
      return `<span class="badge badge-gray">${escapeHtml(status || '-')}</span>`;
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' +
           d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  } catch (e) {
    return dateStr;
  }
}

// ================= INIT =================
loadDashboard();

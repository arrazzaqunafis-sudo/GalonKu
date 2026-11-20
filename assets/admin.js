const rupiah = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0
});

function setText(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

async function loadDashboard() {
  try {
    const response = await fetch('api/dashboard_admin.php');
    if (!response.ok) throw new Error('Dashboard tidak dapat dimuat');
    const result = await response.json();
    if (result.status !== 'success') throw new Error(result.message || 'Data tidak tersedia');

    const { penjualan, saldo_tunai, komplain, inventori } = result.data;
    const sales = Number(penjualan.total_penjualan || 0);

    setText('stat-orders', Number(penjualan.jumlah_order || 0).toLocaleString('id-ID'));
    setText('stat-sales', rupiah.format(sales));
    setText('stat-cash', rupiah.format(Number(saldo_tunai.saldo_tunai || 0)));
    setText('stat-complaints', Number(komplain.jumlah_komplain || 0).toLocaleString('id-ID'));
    setText('sales-total', rupiah.format(sales));

    const inventory = document.getElementById('inventory-list');
    inventory.innerHTML = (inventori || []).map((item) => `
      <div class="inventory-row">
        <div class="inventory-icon"><span class="bottle-mini"></span></div>
        <div class="inventory-info">
          <strong>${escapeHtml(item.nama_produk)}</strong>
          <small>${item.kategori === 'isi_ulang' ? 'Galon isi ulang' : item.kategori === 'galon_baru' ? 'Galon baru' : 'Galon bekas'}</small>
        </div>
        <div class="stock-badge">
          <strong>${Number(item.stok_isi || 0)}</strong>
          <small>Isi / ${Number(item.stok_kosong || 0)} kosong</small>
        </div>
      </div>
    `).join('') || '<p class="loading">Belum ada data inventori.</p>';
  } catch (error) {
    setText('stat-orders', '-');
    setText('stat-sales', '-');
    setText('stat-cash', '-');
    setText('stat-complaints', '-');
    setText('sales-total', '-');
    document.getElementById('inventory-list').innerHTML = `<p class="loading">${escapeHtml(error.message)}</p>`;
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

document.querySelectorAll('.nav-item').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
    setText('page-title', button.dataset.page);
  });
});

const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

document.getElementById('menu-button').addEventListener('click', () => {
  sidebar.classList.add('open');
  sidebarBackdrop.classList.add('open');
});

sidebarBackdrop.addEventListener('click', () => {
  sidebar.classList.remove('open');
  sidebarBackdrop.classList.remove('open');
});

loadDashboard();

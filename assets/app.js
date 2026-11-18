const sheet = document.getElementById('order-sheet');
const backdrop = document.getElementById('sheet-backdrop');
let selectedProduct = 1;

const products = {
  1: { name: 'Isi Ulang 19 Liter', price: 'Rp15.000', bottle: 'refill' },
  2: { name: 'Galon Baru 19 Liter', price: 'Rp80.000', bottle: 'new' },
  3: { name: 'Tukar Galon Bekas', price: 'Rp25.000', bottle: 'used' }
};

function openSheet(productId = 1) {
  selectedProduct = Number(productId);
  const product = products[selectedProduct];
  document.getElementById('produk').value = String(selectedProduct);
  document.getElementById('sheet-product').textContent = product.name;
  document.getElementById('sheet-price').textContent = product.price;
  document.getElementById('sheet-bottle').className = `bottle-illustration ${product.bottle}`;
  document.getElementById('jumlah').value = '1';
  document.getElementById('checkout-result').textContent = '';
  document.getElementById('galon-photo-label').classList.toggle('hidden', selectedProduct !== 3);
  sheet.classList.add('open');
  backdrop.classList.add('open');
  sheet.setAttribute('aria-hidden', 'false');
}

function closeSheet() {
  sheet.classList.remove('open');
  backdrop.classList.remove('open');
  sheet.setAttribute('aria-hidden', 'true');
}

document.getElementById('open-order').addEventListener('click', () => openSheet(1));
document.getElementById('close-sheet').addEventListener('click', closeSheet);
backdrop.addEventListener('click', closeSheet);

document.querySelectorAll('.add-button').forEach((button) => {
  button.addEventListener('click', () => {
    openSheet(button.closest('.product-card').dataset.product);
  });
});

document.querySelectorAll('.quick-actions button').forEach((button) => {
  button.addEventListener('click', () => {
    const action = button.dataset.action;
    if (action === 'water') openSheet(1);
    if (action === 'used') openSheet(3);
    if (action === 'tracking') document.getElementById('courier-trx')?.focus();
  });
});

document.getElementById('qty-minus').addEventListener('click', () => {
  const input = document.getElementById('jumlah');
  input.value = String(Math.max(1, Number(input.value) - 1));
});

document.getElementById('qty-plus').addEventListener('click', () => {
  const input = document.getElementById('jumlah');
  input.value = String(Number(input.value) + 1);
});

async function findCouriers() {
  if (!navigator.geolocation) {
    document.getElementById('couriers').innerHTML = '<p>GPS tidak didukung perangkat ini.</p>';
    return;
  }

  document.getElementById('gps-status').textContent = 'Mencari lokasi...';
  navigator.geolocation.getCurrentPosition(async (position) => {
    const { latitude, longitude } = position.coords;
    document.getElementById('gps-status').textContent = 'Lokasi Anda aktif';
    const response = await fetch(`api/cari_kurir.php?lat=${latitude}&lng=${longitude}`);
    const result = await response.json();
    document.getElementById('couriers').innerHTML = (result.data || []).map((courier) =>
      `<div class="courier-row"><strong>${courier.nama}</strong><span>${Number(courier.jarak_km).toFixed(1)} km</span></div>`
    ).join('') || '<p>Tidak ada kurir dalam radius 5 km.</p>';
  }, () => {
    document.getElementById('gps-status').textContent = 'GPS belum diizinkan';
  });
}

document.getElementById('locate').addEventListener('click', findCouriers);
document.getElementById('search-courier').addEventListener('click', findCouriers);

document.getElementById('checkout').addEventListener('click', async () => {
  const jumlah = document.getElementById('jumlah').value;
  const metode = document.getElementById('metode').value;
  let photoPath = null;

  if (selectedProduct === 3) {
    const photo = document.getElementById('galon-photo').files[0];
    if (!photo) {
      document.getElementById('checkout-result').textContent = 'Foto kondisi galon bekas wajib diunggah.';
      return;
    }
    const upload = new FormData();
    upload.set('foto_galon_bekas', photo);
    const uploadResponse = await fetch('api/upload_galon_bekas.php', { method: 'POST', body: upload });
    const uploadResult = await uploadResponse.json();
    if (!uploadResponse.ok) {
      document.getElementById('checkout-result').textContent = JSON.stringify(uploadResult, null, 2);
      return;
    }
    photoPath = uploadResult.path;
  }

  const form = new FormData();
  form.set('pembeli_id', '2');
  form.set('alamat_id', '1');
  form.set('metode_bayar', metode);
  form.set('items', JSON.stringify([{
    produk_id: selectedProduct,
    jumlah: Number(jumlah),
    foto_galon_bekas: photoPath
  }]));

  const response = await fetch('api/checkout.php', { method: 'POST', body: form });
  document.getElementById('checkout-result').textContent = JSON.stringify(await response.json(), null, 2);
});

document.querySelectorAll('.bottom-nav button[data-bottom]').forEach((button) => {
  button.addEventListener('click', () => {
    const target = button.dataset.bottom;
    if (target === 'order') {
      openSheet(1);
      return;
    }
    if (target === 'products') {
      document.querySelector('.content-section:last-of-type').scrollIntoView({ behavior: 'smooth' });
    }
    if (target === 'orders') {
      openSheet(1);
      document.getElementById('sheet-title').textContent = 'Lacak Pesanan';
    }

    document.querySelectorAll('.bottom-nav button').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
  });
});

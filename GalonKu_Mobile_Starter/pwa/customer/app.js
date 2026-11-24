const API_BASE = '../../../api/';
const AUTH_API_BASE = '../../backend/galonku-api/api/';
const customerTokenKey = 'galonku_customer_token';
const customerUserKey = 'galonku_customer_user';
const sheet = document.getElementById('sheet');
const backdrop = document.getElementById('backdrop');
let currentCustomer = null;

function showCustomerApp(result) {
  currentCustomer = {
    ...result.user,
    alamat_id: result.alamat_id,
    alamat_lengkap: result.alamat_lengkap || ''
  };
  document.getElementById('auth-screen').hidden = true;
  document.getElementById('customer-app').hidden = false;
  document.getElementById('customer-nav').hidden = false;
  const gpsStatus = document.getElementById('gps-status');
  if (gpsStatus) {
    gpsStatus.textContent = currentCustomer.alamat_lengkap || 'Alamat pengiriman belum tersedia';
  }
  document.body.classList.remove('auth-required');

  const customerAvatar = document.querySelector('.dashboard-header .avatar');
  if (customerAvatar) customerAvatar.textContent = (currentCustomer.nama || 'Pelanggan').trim().charAt(0).toUpperCase();
}

let registrationLocation = null;

async function completeGoogleLogin(idToken, extraData = {}) {
  const errorElement = document.getElementById('google-login-error');
  if (errorElement) errorElement.textContent = '';
  try {
    const isRegister = document.querySelector('[data-auth-tab="register"]')?.classList.contains('active');
    const phone = document.getElementById('register-phone')?.value.trim() || extraData.phone || '';
    const address = document.getElementById('register-address')?.value.trim() || extraData.address || '';
    const password = document.getElementById('customer-login-password')?.value || document.getElementById('register-password')?.value || '';

    const response = await fetch(`${AUTH_API_BASE}auth/google.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_token: idToken,
        role: 'masyarakat',
        no_hp: phone,
        password: password,
        alamat_lengkap: 'Bandung, Jawa Barat',
        label_alamat: 'Rumah',
        latitude: registrationLocation?.latitude || -6.9175,
        longitude: registrationLocation?.longitude || 107.6191
      })
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Login Google tidak berhasil.');
    }
    localStorage.setItem(customerTokenKey, result.token);
    localStorage.setItem(customerUserKey, JSON.stringify(result));
    showCustomerApp(result);
    if (result.registered) {
      alert('Selamat datang! Akun pelanggan Anda berhasil didaftarkan dengan Google.');
    }
  } catch (error) {
    if (errorElement) errorElement.textContent = error.message;
  }
}

window.galonkuGoogleLoginSuccess = completeGoogleLogin;
window.galonkuGoogleLoginFailure = (message) => {
  const errorElement = document.getElementById('google-login-error');
  if (errorElement) errorElement.textContent = message;
};

function createSimulatedGoogleToken(email, name) {
  const header = btoa(JSON.stringify({ alg: 'simulated', typ: 'JWT' })).replace(/=/g, '');
  const now = Math.floor(Date.now() / 1000);
  const payloadData = {
    iss: 'galonku-simulated-google',
    sub: 'g_' + btoa(encodeURIComponent(email)).replace(/[^a-zA-Z0-9]/g, '').slice(0, 20),
    email: email.toLowerCase().trim(),
    email_verified: true,
    name: name.trim() || email.split('@')[0],
    iat: now,
    exp: now + 3600
  };
  const payload = btoa(unescape(encodeURIComponent(JSON.stringify(payloadData)))).replace(/=/g, '');
  const sig = btoa('simulated_signature').replace(/=/g, '');
  return `${header}.${payload}.${sig}`;
}

async function setupGoogleSignIn() {
  const googleBtn = document.getElementById('google-btn');
  const googleBtnLabel = document.getElementById('google-btn-label');
  const modalGoogle = document.getElementById('modal-google');
  const modalGoogleEmail = document.getElementById('modal-google-email');
  const modalGoogleName = document.getElementById('modal-google-name');
  const modalGoogleCancel = document.getElementById('modal-google-cancel');
  const modalGoogleSubmit = document.getElementById('modal-google-submit');
  const browserButton = document.getElementById('google-signin-button');
  const errorElement = document.getElementById('google-login-error');

  const openGoogleModal = () => {
    if (!modalGoogle) return;
    const isRegister = document.querySelector('[data-auth-tab="register"]')?.classList.contains('active');
    const emailInput = isRegister ? document.getElementById('register-email')?.value : document.getElementById('customer-login-identity')?.value;
    const nameInput = document.getElementById('register-name')?.value;
    if (modalGoogleEmail && emailInput && emailInput.includes('@')) modalGoogleEmail.value = emailInput;
    if (modalGoogleName && nameInput) modalGoogleName.value = nameInput;
    modalGoogle.hidden = false;
    modalGoogleEmail?.focus();
  };

  const closeGoogleModal = () => {
    if (modalGoogle) modalGoogle.hidden = true;
  };

  modalGoogleCancel?.addEventListener('click', closeGoogleModal);
  modalGoogle?.addEventListener('click', (e) => {
    if (e.target === modalGoogle) closeGoogleModal();
  });

  modalGoogleSubmit?.addEventListener('click', () => {
    const email = modalGoogleEmail?.value.trim();
    const name = modalGoogleName?.value.trim() || '';
    if (!email || !email.includes('@')) {
      alert('Masukkan email Google yang valid.');
      modalGoogleEmail?.focus();
      return;
    }
    closeGoogleModal();
    const simulatedToken = createSimulatedGoogleToken(email, name);
    completeGoogleLogin(simulatedToken, {
      phone: document.getElementById('register-phone')?.value.trim() || '',
      address: document.getElementById('register-address')?.value.trim() || ''
    });
  });

  if (googleBtn) {
    googleBtn.addEventListener('click', () => {
      if (window.GalonKuNativeAuth) {
        window.GalonKuNativeAuth.signIn();
        return;
      }
      if (window.google?.accounts?.id) {
        try {
          window.google.accounts.id.prompt((notification) => {
            if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
              openGoogleModal();
            }
          });
          return;
        } catch (_) {}
      }
      openGoogleModal();
    });
  }

  // Load Google Identity Services SDK
  try {
    const response = await fetch(`${AUTH_API_BASE}auth/google-config.php`);
    const config = await response.json();
    if (response.ok && config.client_id) {
      const script = document.createElement('script');
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.defer = true;
      script.onload = () => {
        try {
          window.google.accounts.id.initialize({
            client_id: config.client_id,
            callback: ({ credential }) => completeGoogleLogin(credential)
          });
          if (browserButton) {
            window.google.accounts.id.renderButton(browserButton, {
              theme: 'outline',
              size: 'large',
              text: 'continue_with',
              width: 280
            });
          }
        } catch (e) {
          console.warn('Google Identity initialization notice:', e);
        }
      };
      document.head.appendChild(script);
    }
  } catch (err) {
    console.warn('Google config load notice:', err);
  }
}

setupGoogleSignIn();

document.querySelectorAll('[data-auth-tab="login"]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-auth-tab]').forEach((tab) => tab.classList.remove('active'));
    button.classList.add('active');
    const loginForm = document.getElementById('customer-login-form');
    if (loginForm) loginForm.hidden = false;
    const label = document.getElementById('google-btn-label');
    if (label) label.textContent = 'Masuk dengan Google';
  });
});

document.querySelectorAll('.password-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.target);
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});

document.getElementById('customer-login-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const errorElement = document.getElementById('google-login-error');
  errorElement.textContent = '';
  try {
    const response = await fetch(`${AUTH_API_BASE}auth/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        identity: document.getElementById('customer-login-identity').value.trim(),
        password: document.getElementById('customer-login-password').value
      })
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Login tidak berhasil.');
    if (result.user.role !== 'masyarakat') throw new Error('Akun ini bukan akun pelanggan.');
    localStorage.setItem(customerTokenKey, result.token);
    localStorage.setItem(customerUserKey, JSON.stringify(result));
    showCustomerApp(result);
  } catch (error) {
    errorElement.textContent = error.message;
  }
});

document.getElementById('customer-register-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const errorElement = document.getElementById('google-login-error');
  errorElement.textContent = '';
  try {
    const response = await fetch(`${AUTH_API_BASE}auth/register.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nama: document.getElementById('register-name').value.trim(),
        email: document.getElementById('register-email').value.trim(),
        no_hp: document.getElementById('register-phone').value.trim(),
        password: document.getElementById('register-password').value,
        alamat_lengkap: 'Bandung, Jawa Barat',
        role: 'masyarakat',
        latitude: registrationLocation?.latitude,
        longitude: registrationLocation?.longitude
      })
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Pendaftaran tidak berhasil.');
    localStorage.setItem(customerTokenKey, 'registered-' + Date.now());
    localStorage.setItem(customerUserKey, JSON.stringify(result));
    showCustomerApp(result);
  } catch (error) {
    errorElement.textContent = error.message;
  }
});

const savedCustomer = localStorage.getItem(customerUserKey);
if (savedCustomer && localStorage.getItem(customerTokenKey)) {
  try {
    showCustomerApp(JSON.parse(savedCustomer));
  } catch {
    localStorage.removeItem(customerUserKey);
    localStorage.removeItem(customerTokenKey);
  }
}

let selectedProduct = 1;
const productNames = {
  1: ['Isi Ulang 19 Liter', 'Rp15.000'],
  2: ['Galon Baru 19 Liter', 'Rp80.000'],
  3: ['Tukar Galon Bekas', 'Rp25.000']
};

function openSheet(productId = 1) {
  selectedProduct = Number(productId);
  document.getElementById('produk').value = String(selectedProduct);
  const [name, price] = productNames[selectedProduct] || productNames[1];
  document.getElementById('sheet-product-name').textContent = name;
  document.getElementById('sheet-product-price').textContent = price;
  document.getElementById('sheet-title').textContent = 'Pesan Galon';
  document.getElementById('photo-label').classList.toggle('hidden', selectedProduct !== 3);
  document.getElementById('jumlah').value = '1';
  document.getElementById('result').textContent = '';
  sheet.classList.add('open');
  backdrop.classList.add('open');
  document.body.classList.add('sheet-open');
  sheet.setAttribute('aria-hidden', 'false');
}

function closeSheet() {
  sheet.classList.remove('open');
  backdrop.classList.remove('open');
  document.body.classList.remove('sheet-open');
  sheet.setAttribute('aria-hidden', 'true');
}

document.querySelectorAll('[data-product]').forEach((element) => {
  element.addEventListener('click', () => openSheet(element.dataset.product));
});
document.getElementById('order-now')?.addEventListener('click', () => openSheet(1));
document.getElementById('nav-order')?.addEventListener('click', () => openSheet(1));
document.getElementById('close-sheet')?.addEventListener('click', closeSheet);
backdrop?.addEventListener('click', closeSheet);

document.getElementById('minus').addEventListener('click', () => {
  const input = document.getElementById('jumlah');
  input.value = String(Math.max(1, Number(input.value) - 1));
});
document.getElementById('plus').addEventListener('click', () => {
  const input = document.getElementById('jumlah');
  input.value = String(Number(input.value) + 1);
});

async function findCouriers() {
  if (!navigator.geolocation) {
    document.getElementById('couriers').textContent = 'GPS tidak didukung.';
    return;
  }
  document.getElementById('gps-status').textContent = 'Mencari lokasi...';
  navigator.geolocation.getCurrentPosition(async ({ coords }) => {
    document.getElementById('gps-status').textContent = 'Lokasi Anda aktif';
    const response = await fetch(`${API_BASE}cari_kurir.php?lat=${coords.latitude}&lng=${coords.longitude}`);
    const result = await response.json();
    document.getElementById('couriers').innerHTML = (result.data || []).map((courier) =>
      `<div style="display:flex;justify-content:space-between;padding-top:8px"><strong>${courier.nama}</strong><span>${Number(courier.jarak_km).toFixed(1)} km</span></div>`
    ).join('') || '<div style="padding-top:8px">Tidak ada kurir dalam radius 5 km.</div>';
  }, () => {
    document.getElementById('gps-status').textContent = 'GPS belum diizinkan';
  });
}

document.getElementById('locate')?.addEventListener('click', findCouriers);
document.getElementById('search-courier')?.addEventListener('click', findCouriers);

async function loadCatalog() {
  try {
    const response = await fetch(`${API_BASE}catalog.php`);
    const result = await response.json();
    if (result.status !== 'success') return;
    const names = { isi_ulang: 'Isi ulang', galon_baru: 'Galon baru', galon_bekas: 'Galon bekas' };
    document.querySelectorAll('.product').forEach((card, index) => {
      const product = result.data[index];
      if (!product) return;
      card.dataset.product = String(product.produk_id);
      const category = card.querySelector('small');
      if (category) category.textContent = names[product.kategori] || product.kategori;
      const title = card.querySelector('h3');
      const price = card.querySelector('strong');
      if (title) title.textContent = product.nama_produk;
      if (price) price.textContent = `Rp${Number(product.harga).toLocaleString('id-ID')}`;
      let add = card.querySelector('.add');
      if (!add) {
        add = document.createElement('button');
        add.type = 'button';
        add.className = 'add';
        add.setAttribute('aria-label', `Pesan ${product.nama_produk}`);
        card.querySelector('.product-info')?.appendChild(add);
      }
      add.dataset.product = String(product.produk_id);
      add.textContent = '+';
      add.addEventListener('click', () => openSheet(product.produk_id));
    });
  } catch (error) {
    console.warn('Katalog tidak dapat dimuat.', error);
  }
}

document.getElementById('checkout').addEventListener('click', async () => {
  const productId = Number(document.getElementById('produk').value);
  const jumlah = document.getElementById('jumlah').value;
  const metode = document.getElementById('metode').value;
  let photoPath = null;

  if (productId === 3) {
    const photo = document.getElementById('photo').files[0];
    if (!photo) {
      document.getElementById('result').textContent = 'Foto kondisi galon bekas wajib diunggah.';
      return;
    }
    const upload = new FormData();
    upload.set('foto_galon_bekas', photo);
    const uploadResponse = await fetch(`${API_BASE}upload_galon_bekas.php`, { method: 'POST', body: upload });
    const uploadResult = await uploadResponse.json();
    if (!uploadResponse.ok) {
      document.getElementById('result').textContent = JSON.stringify(uploadResult, null, 2);
      return;
    }
    photoPath = uploadResult.path;
  }

  if (!currentCustomer?.user_id || !currentCustomer.alamat_id) {
    document.getElementById('result').textContent =
      'Akun ini belum memiliki alamat pengiriman. Masuk kembali dan lengkapi alamat serta lokasi GPS.';
    return;
  }
  const form = new FormData();
  form.set('pembeli_id', String(currentCustomer.user_id));
  form.set('alamat_id', String(currentCustomer.alamat_id));
  form.set('metode_bayar', metode);
  form.set('items', JSON.stringify([{ produk_id: productId, jumlah: Number(jumlah), foto_galon_bekas: photoPath }]));
  const response = await fetch(`${API_BASE}checkout.php`, { method: 'POST', body: form });
  const result = await response.json();
  document.getElementById('result').textContent = JSON.stringify(result, null, 2);
});

document.getElementById('nav-orders').addEventListener('click', async () => {
  if (!currentCustomer?.user_id) return;
  const response = await fetch(`${API_BASE}pesanan.php?pembeli_id=${encodeURIComponent(currentCustomer.user_id)}`);
  const result = await response.json();
  document.getElementById('tracking-screen').classList.remove('hidden');
  if (result.data && result.data[0]) {
    const latest = result.data[0];
    document.getElementById('tracking-status').textContent =
      latest.status_antar === 'menuju_lokasi' ? 'Kurir sedang menuju lokasi' :
      latest.status_antar === 'sampai_tujuan' ? 'Pesanan sudah selesai' :
      'Menunggu penugasan kurir';
  }
});

function showScreen(id) {
  document.querySelectorAll('.mobile-screen').forEach((screen) => screen.classList.add('hidden'));
  if (id) document.getElementById(id).classList.remove('hidden');
  document.querySelectorAll('.nav button').forEach((button) => button.classList.remove('active'));
}

document.getElementById('nav-home').addEventListener('click', () => showScreen(null));
document.getElementById('nav-products').addEventListener('click', () => {
  showScreen(null);
  document.querySelector('.section:last-of-type').scrollIntoView({ behavior: 'smooth' });
});
document.getElementById('nav-account').addEventListener('click', () => {
  if (!currentCustomer) return;
  if (confirm(`Masuk sebagai ${currentCustomer.email}. Keluar dari akun ini?`)) {
    localStorage.removeItem(customerTokenKey);
    localStorage.removeItem(customerUserKey);
    currentCustomer = null;
    document.getElementById('auth-screen').hidden = false;
    document.getElementById('customer-app').hidden = true;
    document.getElementById('customer-nav').hidden = true;
  }
});
document.getElementById('nav-order').addEventListener('click', () => {
  document.querySelectorAll('.nav button').forEach((button) => button.classList.remove('active'));
  document.getElementById('nav-order')?.classList.add('active');
  openSheet(1);
});
document.getElementById('order-back')?.addEventListener('click', () => showScreen(null));
document.getElementById('tracking-back')?.addEventListener('click', () => showScreen(null));
document.getElementById('tracking')?.addEventListener('click', () => showScreen('tracking-screen'));
document.getElementById('order-product')?.addEventListener('change', (event) => {
  document.getElementById('order-photo-label').classList.toggle('hidden', Number(event.target.value) !== 3);
});

document.getElementById('place-order')?.addEventListener('click', async () => {
  const productId = Number(document.getElementById('order-product').value);
  const jumlah = Number(document.getElementById('order-quantity').value);
  let photoPath = null;
  if (productId === 3) {
    const photo = document.getElementById('order-photo').files[0];
    if (!photo) {
      alert('Foto kondisi galon bekas wajib diunggah.');
      return;
    }
    const upload = new FormData();
    upload.set('foto_galon_bekas', photo);
    const uploadResponse = await fetch(`${API_BASE}upload_galon_bekas.php`, { method: 'POST', body: upload });
    const uploadResult = await uploadResponse.json();
    if (!uploadResponse.ok) {
      alert(uploadResult.message || 'Foto gagal diunggah.');
      return;
    }
    photoPath = uploadResult.path;
  }
  const form = new FormData();
  form.set('pembeli_id', '2');
  form.set('alamat_id', '1');
  form.set('metode_bayar', 'tunai');
  form.set('items', JSON.stringify([{ produk_id: productId, jumlah, foto_galon_bekas: photoPath }]));
  const response = await fetch(`${API_BASE}checkout.php`, { method: 'POST', body: form });
  const result = await response.json();
  if (result.status === 'success') {
    showScreen('tracking-screen');
    document.getElementById('tracking-status').textContent = 'Pesanan diproses';
  } else {
    alert(result.message || 'Pesanan gagal dibuat.');
  }
});

if (!document.getElementById('tracking-screen')) {
  const trackingScreen = document.createElement('section');
  trackingScreen.id = 'tracking-screen';
  trackingScreen.className = 'mobile-screen hidden';
  trackingScreen.innerHTML = '<div class="active-order"><div class="active-order-copy"><small>Status pesanan</small><strong id="tracking-status">Memuat pesanan...</strong></div></div>';
  document.querySelector('.app')?.appendChild(trackingScreen);
}

// Menyesuaikan dashboard setelah halaman dimuat.
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('.search-wrap input');
  if (searchInput) searchInput.placeholder = 'Cari produk atau layanan';

  document.querySelectorAll('.section').forEach((section) => {
    const title = section.querySelector('.heading h2')?.textContent.trim();
    if (title === 'Produk Unggulan') section.hidden = true;
    if (title === 'Isi Ulang Galon') section.classList.add('galon-section');
  });

  const bell = document.querySelector('.dashboard-header .bell');
  bell?.addEventListener('click', () => {
    document.querySelector('#nav-orders')?.click();
  });
});

loadCatalog();

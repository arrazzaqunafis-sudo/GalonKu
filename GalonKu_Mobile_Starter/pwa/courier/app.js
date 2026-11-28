const API_BASE = '../../backend/galonku-api/api/';
const tokenKey = 'galonku_courier_token';
const userKey = 'galonku_courier_user';
const appRole = 'tukang_galon';
let locationWatch = null;
let refreshTimer = null;
let dismissedJobs = new Set(JSON.parse(localStorage.getItem('galonku_courier_dismissed') || '[]'));

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[char]));
const currency = (value) => `Rp${Number(value || 0).toLocaleString('id-ID')}`;

function showNotice(message) {
  const notice = document.getElementById('notice');
  notice.textContent = message;
  notice.classList.add('show');
  window.setTimeout(() => notice.classList.remove('show'), 2600);
}

async function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
  const token = localStorage.getItem(tokenKey);
  if (token) headers.set('Authorization', `Bearer ${token}`);
  const response = await fetch(`${API_BASE}${path}`, { ...options, headers });
  const payload = await response.json();
  if (!response.ok) throw new Error(payload.message || 'Permintaan gagal.');
  return payload;
}

function showApp(user) {
  document.getElementById('login-screen').hidden = true;
  document.getElementById('app-screen').hidden = false;
  document.getElementById('bottom-nav').hidden = false;
  document.getElementById('driver-name').textContent = user.nama || 'Mitra Kurir';
  document.getElementById('avatar').textContent = (user.nama || 'K').trim().charAt(0).toUpperCase();
  setOnline(true);
}

document.getElementById('login-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const error = document.getElementById('login-error');
  error.textContent = '';
  try {
    const result = await api('auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        identity: document.getElementById('identity').value.trim(),
        password: document.getElementById('password').value
      })
    });
    if (result.user.role !== 'tukang_galon') {
      throw new Error('Akun ini bukan akun kurir GalonKu.');
    }
    localStorage.setItem(tokenKey, result.token);
    localStorage.setItem(userKey, JSON.stringify(result.user));
    showApp(result.user);
  } catch (requestError) {
    error.textContent = requestError.message;
  }
});

async function completeGoogleLogin(idToken, extraData = {}) {
  const error = document.getElementById('login-error');
  if (error) error.textContent = '';
  try {
    const isRegister = document.querySelector('[data-courier-auth="register"]')?.classList.contains('active');
    const phone = document.getElementById('courier-register-phone')?.value.trim() || extraData.phone || '';
    const password = document.getElementById('password')?.value || document.getElementById('courier-register-password')?.value || '';

    const result = await api('auth/google.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_token: idToken,
        role: appRole,
        no_hp: phone,
        password: password
      })
    });
    if (result.user.role !== appRole) {
      throw new Error('Akun ini bukan akun kurir GalonKu.');
    }
    localStorage.setItem(tokenKey, result.token);
    localStorage.setItem(userKey, JSON.stringify(result.user));
    showApp(result.user);
    showNotice(result.registered ? 'Akun kurir berhasil didaftarkan dengan Google.' : 'Login Google kurir berhasil.');
  } catch (requestError) {
    if (error) error.textContent = requestError.message;
  }
}

window.galonkuGoogleLoginSuccess = completeGoogleLogin;
window.galonkuGoogleLoginFailure = (message) => {
  const error = document.getElementById('login-error');
  if (error) error.textContent = message;
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
  const error = document.getElementById('login-error');

  const openGoogleModal = () => {
    if (!modalGoogle) return;
    const isRegister = document.querySelector('[data-courier-auth="register"]')?.classList.contains('active');
    const emailInput = isRegister ? document.getElementById('courier-register-email')?.value : document.getElementById('identity')?.value;
    const nameInput = document.getElementById('courier-register-name')?.value;
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
      phone: document.getElementById('courier-register-phone')?.value.trim() || ''
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
        } catch (_) { }
      }
      openGoogleModal();
    });
  }

  // Load Google Identity Services SDK
  try {
    const response = await fetch(`${API_BASE}auth/google-config.php`);
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

document.querySelectorAll('[data-courier-auth]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-courier-auth]').forEach((tab) => {
      tab.classList.remove('active');
      tab.style.background = 'transparent';
      tab.style.color = '#758196';
    });
    button.classList.add('active');
    button.style.background = '#fff';
    button.style.color = '#2853d4';
    const isRegister = button.dataset.courierAuth === 'register';
    document.getElementById('login-form').hidden = isRegister;
    document.getElementById('courier-register-form').hidden = !isRegister;
    const desc = document.getElementById('courier-auth-description');
    if (desc) desc.textContent = isRegister ? 'Daftar akun baru mitra kurir GalonKu.' : 'Masuk untuk menerima dan mengelola pesanan pelanggan.';
    const label = document.getElementById('google-btn-label');
    if (label) label.textContent = isRegister ? 'Daftar dengan Google' : 'Masuk dengan Google';
    const modalSubtitle = document.getElementById('modal-google-subtitle');
    if (modalSubtitle) modalSubtitle.textContent = isRegister ? 'Daftarkan akun mitra kurir dengan Akun Google' : 'Masuk ke GalonKu Mitra dengan Akun Google';
  });
});

document.getElementById('courier-register-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const error = document.getElementById('login-error');
  error.textContent = '';
  try {
    const result = await api('auth/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nama: document.getElementById('courier-register-name').value.trim(),
        email: document.getElementById('courier-register-email').value.trim(),
        no_hp: document.getElementById('courier-register-phone').value.trim(),
        password: document.getElementById('courier-register-password').value,
        role: appRole
      })
    });
    if (!result.success) throw new Error(result.message || 'Pendaftaran tidak berhasil.');
    showNotice('Akun kurir berhasil didaftarkan. Silakan masuk.');
    document.querySelector('[data-courier-auth="login"]').click();
    document.getElementById('identity').value =
      document.getElementById('courier-register-email').value.trim();
  } catch (requestError) {
    error.textContent = requestError.message;
  }
});

const savedUser = localStorage.getItem(userKey);
if (savedUser && localStorage.getItem(tokenKey)) {
  try {
    showApp(JSON.parse(savedUser));
  } catch {
    localStorage.removeItem(userKey);
    localStorage.removeItem(tokenKey);
  }
}

async function setOnline(isOnline) {
  const button = document.getElementById('online-toggle');
  const onlineText = document.getElementById('online-label');
  const status = document.getElementById('online-status');
  button.disabled = true;
  try {
    await api('courier/online.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ online: isOnline })
    });
    button.classList.toggle('online', isOnline);
    button.setAttribute('aria-pressed', String(isOnline));
    onlineText.textContent = isOnline ? 'Online' : 'Offline';
    status.textContent = isOnline ? 'Anda siap menerima pesanan' : 'Anda sedang offline';
    document.getElementById('map-status').textContent = isOnline
      ? 'Mencari pesanan di sekitar Anda'
      : 'Aktifkan status online untuk mulai';

    if (isOnline) {
      startLocationWatch();
      if (!refreshTimer) refreshTimer = window.setInterval(loadJobs, 15000);
      loadJobs();
    } else {
      stopLocationWatch();
      window.clearInterval(refreshTimer);
      refreshTimer = null;
      await loadJobs();
    }
  } catch (error) {
    showNotice(error.message);
  } finally {
    button.disabled = false;
  }
}

document.getElementById('online-toggle').addEventListener('click', () => {
  const goingOnline = document.getElementById('online-toggle').getAttribute('aria-pressed') !== 'true';
  setOnline(goingOnline);
});

function startLocationWatch() {
  if (locationWatch !== null && navigator.geolocation) navigator.geolocation.clearWatch(locationWatch);
  if (!navigator.geolocation) {
    document.getElementById('map-status').textContent = 'Online · Lokasi default Bandung aktif';
    void api('courier/location.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ latitude: -6.9175, longitude: 107.6191, accuracy: 20 })
    }).catch(() => { });
    return;
  }
  locationWatch = navigator.geolocation.watchPosition(async ({ coords }) => {
    document.getElementById('map-status').textContent = 'GPS aktif · lokasi diperbarui';
    try {
      await api('courier/location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          latitude: coords.latitude,
          longitude: coords.longitude,
          accuracy: coords.accuracy
        })
      });
    } catch (error) {
      console.warn('Gagal perbarui lokasi GPS:', error);
    }
  }, (error) => {
  // Jika GPS ditolak atau belum aktif, tetap biarkan kurir ONLINE dengan koordinat default Bandung
  document.getElementById('map-status').textContent = 'Online · Lokasi default Bandung aktif';
  void api('courier/location.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ latitude: -6.9175, longitude: 107.6191, accuracy: 25 })
  }).catch(() => { });
}, { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 });
}

function stopLocationWatch() {
  if (locationWatch !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(locationWatch);
    locationWatch = null;
  }
}

async function loadJobs() {
  if (!localStorage.getItem(tokenKey)) return;
  try {
    const result = await api('courier/jobs.php');
    const toggle = document.getElementById('online-toggle');
    const online = Boolean(result.online);
    toggle.classList.toggle('online', online);
    toggle.setAttribute('aria-pressed', String(online));
    document.getElementById('online-label').textContent = online ? 'Online' : 'Offline';
    document.getElementById('online-status').textContent =
      online ? 'Anda siap menerima pesanan' : 'Anda sedang offline';
    if (online) {
      if (locationWatch === null) startLocationWatch();
      if (refreshTimer === null) refreshTimer = window.setInterval(loadJobs, 15000);
    } else {
      stopLocationWatch();
      window.clearInterval(refreshTimer);
      refreshTimer = null;
    }
    renderJobs(result.available || [], result.assigned || [], result.history || [], result.summary || {});
  } catch (error) {
      if (error.message.includes('Token') || error.message.includes('Akses')) {
        logout();
      } else {
        console.warn('Info load jobs:', error.message);
      }
    }
  }

  function routeMarkup(job) {
    return `<div class="route">
    <div class="route-point"><small>Alamat pelanggan</small><strong>${escapeHtml(job.nama_pelanggan || 'Pelanggan')} · ${escapeHtml(job.no_hp || '')}</strong></div>
    <div class="route-point drop"><small>Tujuan</small><strong>${escapeHtml(job.alamat_lengkap || 'Alamat tidak tersedia')}</strong></div>
  </div>`;
  }

  function renderJobs(available, assigned, history, summary) {
    available = available.filter((job) => !dismissedJobs.has(String(job.transaksi_id)));
    document.getElementById('trip-count').textContent = summary.completed_today || '0';
    document.getElementById('distance-total').textContent =
      `${Number(summary.distance_today || 0).toFixed(1)} km`;
    document.getElementById('cash-total').textContent = currency(summary.cash_today);
    document.getElementById('income-total').textContent = currency(summary.cash_today);
    document.getElementById('active-count').textContent = `${assigned.length} pesanan`;
    document.getElementById('available-count').textContent = `${available.length} tersedia`;
    document.getElementById('orders-count').textContent = `${assigned.length} aktif`;

    const assignedMarkup = assigned.length ? assigned.map((job) => `
    <article class="card next-card">
      <div class="trip-head"><strong>Pesanan #${escapeHtml(job.transaksi_id)}</strong><span class="badge">Dalam perjalanan</span></div>
      ${routeMarkup(job)}
      <div class="trip-meta"><span>Jarak<br><b>${Number(job.jarak_km || 0).toFixed(1)} km</b></span><span>Pembayaran<br><b>${escapeHtml(job.metode_bayar)}</b></span><span>Total<br><b>${currency(job.total_harga)}</b></span></div>
      <div class="button-row"><button class="accept complete-order" data-id="${escapeHtml(job.transaksi_id)}">Selesaikan pesanan</button></div>
    </article>`).join('') : '<div class="card empty"><b>Belum ada pesanan aktif</b>Pesanan yang Anda terima akan muncul di sini.</div>';
    document.getElementById('assigned-list').innerHTML = assignedMarkup;

    const availableMarkup = available.length ? available.map((job) => `
    <article class="card job-card">
      <div class="job-top"><span class="job-id">Pesanan #${escapeHtml(job.transaksi_id)}</span><span class="distance">${Number(job.jarak_km || 0).toFixed(1)} km</span></div>
      ${routeMarkup(job)}
      <div class="job-footer"><div><small>${escapeHtml(job.ringkasan_item || 'Pesanan GalonKu')}</small><br><strong>${currency(job.total_harga)}</strong></div><small>${escapeHtml(job.metode_bayar || '')}</small></div>
      <div class="button-row"><button class="reject reject-order" data-id="${escapeHtml(job.transaksi_id)}">Lewati</button><button class="accept accept-order" data-id="${escapeHtml(job.transaksi_id)}">Terima pesanan</button></div>
    </article>`).join('') : '<div class="card empty"><b>Belum ada pesanan baru</b>Pesanan dalam radius 5 km akan tampil di sini.</div>';
    document.getElementById('available-list').innerHTML = availableMarkup;
    document.getElementById('orders-list').innerHTML = assignedMarkup;
    document.getElementById('history-list').innerHTML = history.length ? history.map((job) => `
    <article class="card job-card">
      <div class="job-top"><span class="job-id">Pesanan #${escapeHtml(job.transaksi_id)}</span><span class="badge">Selesai</span></div>
      <div class="route"><div class="route-point drop"><small>Alamat pengantaran</small><strong>${escapeHtml(job.alamat_lengkap)}</strong></div></div>
      <div class="job-footer"><small>${Number(job.jarak_km || 0).toFixed(1)} km · ${escapeHtml(job.metode_bayar)}</small><strong>${currency(job.total_harga)}</strong></div>
    </article>`).join('') : '<div class="card empty"><b>Belum ada riwayat</b>Pengantaran selesai akan tercatat di sini.</div>';

    document.querySelectorAll('.accept-order').forEach((button) => {
      button.addEventListener('click', () => acceptJob(button.dataset.id));
    });
    document.querySelectorAll('.reject-order').forEach((button) => {
      button.addEventListener('click', () => {
        dismissedJobs.add(String(button.dataset.id));
        localStorage.setItem('galonku_courier_dismissed', JSON.stringify([...dismissedJobs]));
        button.closest('.job-card').remove();
        showNotice('Pesanan dilewati.');
      });
    });
    document.querySelectorAll('.complete-order').forEach((button) => {
      button.addEventListener('click', () => openCompleteModal(button.dataset.id));
    });
  }

  async function acceptJob(id) {
    try {
      const result = await api('courier/jobs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transaksi_id: Number(id) })
      });
      showNotice(result.message || 'Pesanan diterima.');
      await loadJobs();
    } catch (error) {
      showNotice(error.message);
      loadJobs();
    }
  }

  function openCompleteModal(id) {
    document.getElementById('complete-order-id').value = id;
    document.getElementById('proof-photo').value = '';
    document.getElementById('complete-modal').classList.add('open');
  }
  function closeCompleteModal() {
    document.getElementById('complete-modal').classList.remove('open');
  }
  document.getElementById('cancel-complete').addEventListener('click', closeCompleteModal);

  document.getElementById('complete-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData();
    form.set('transaksi_id', document.getElementById('complete-order-id').value);
    form.set('foto_bukti', document.getElementById('proof-photo').files[0]);
    try {
      const result = await api('courier/complete.php', { method: 'POST', body: form });
      closeCompleteModal();
      showNotice(result.message || 'Pengantaran berhasil diselesaikan.');
      await loadJobs();
    } catch (error) {
      showNotice(error.message);
    }
  });

  async function logout() {
      const token = localStorage.getItem(tokenKey);
      if (token) {
        try {
          await api('courier/online.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ online: false })
          });
        } catch (_) {
          // Abaikan kegagalan panggilan online.php saat sesi kedaluwarsa
        }
      }
      stopLocationWatch();
      window.clearInterval(refreshTimer);
      refreshTimer = null;
      localStorage.removeItem(tokenKey);
      localStorage.removeItem(userKey);
      document.getElementById('app-screen').hidden = true;
      document.getElementById('bottom-nav').hidden = true;
      document.getElementById('login-screen').hidden = false;
    }
    document.getElementById('avatar').addEventListener('click', () => {
      if (confirm('Keluar dari akun kurir?')) void logout();
    });

    document.querySelectorAll('.nav button[data-screen]').forEach((button) => {
      button.addEventListener('click', () => {
        document.querySelectorAll('.nav button').forEach((item) => item.classList.remove('active'));
        document.querySelectorAll('.screen').forEach((screen) => screen.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(button.dataset.screen).classList.add('active');
        if (button.dataset.screen === 'orders-screen' || button.dataset.screen === 'history-screen') loadJobs();
      });
    });

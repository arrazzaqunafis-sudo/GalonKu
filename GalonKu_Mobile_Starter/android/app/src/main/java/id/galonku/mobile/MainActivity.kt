package id.galonku.mobile

import android.Manifest
import android.annotation.SuppressLint
import android.app.AlertDialog
import android.content.Intent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.webkit.JavascriptInterface
import android.view.Gravity
import android.view.ViewGroup
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.location.Location
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import com.google.android.gms.auth.api.signin.GoogleSignIn
import com.google.android.gms.auth.api.signin.GoogleSignInOptions
import com.google.android.gms.common.api.ApiException
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.Priority
import org.json.JSONObject
import java.net.URI

class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    private lateinit var progress: ProgressBar
    private var fileChooserCallback: ValueCallback<Array<Uri>>? = null
    private val pendingLocations = mutableMapOf<Int, PendingLocation>()
    private val locationCallbacks = mutableMapOf<Int, LocationCallback>()
    private val requestLocationCode = 701
    private val requestFileCode = 702
    private val preferences by lazy { getSharedPreferences("galonku_app", MODE_PRIVATE) }
    private val fusedLocation by lazy { LocationServices.getFusedLocationProviderClient(this) }
    private var locationReceiverRegistered = false
    private val googleSignInLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode != RESULT_OK) {
                dispatchGoogleAuthError("Login Google dibatalkan.")
            } else {
                try {
                    val account = GoogleSignIn.getSignedInAccountFromIntent(result.data)
                        .getResult(ApiException::class.java)
                    val idToken = account.idToken
                    if (idToken.isNullOrBlank()) {
                        dispatchGoogleAuthError("Google tidak mengirim token login.")
                    } else if (::webView.isInitialized) {
                        webView.evaluateJavascript(
                            "window.galonkuGoogleLoginSuccess(${JSONObject.quote(idToken)})",
                            null
                        )
                    }
                } catch (error: ApiException) {
                    dispatchGoogleAuthError("Login Google gagal (${error.statusCode}).")
                }
            }
        }

    private data class PendingLocation(
        val oneShot: Boolean,
        val token: String,
        val apiUrl: String
    )

    private val locationReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            val id = intent?.getIntExtra("watch_id", -1) ?: return
            val latitude = intent.getDoubleExtra("latitude", Double.NaN)
            val longitude = intent.getDoubleExtra("longitude", Double.NaN)
            val accuracy = intent.getFloatExtra("accuracy", 0f)
            deliverLocation(id, latitude, longitude, accuracy)
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.statusBarColor = android.graphics.Color.rgb(24, 76, 134)

        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(android.graphics.Color.WHITE)
        }
        val toolbar = LinearLayout(this).apply {
            gravity = Gravity.CENTER_VERTICAL
            setPadding(dp(18), dp(10), dp(12), dp(10))
            setBackgroundColor(android.graphics.Color.rgb(24, 76, 134))
        }
        val title = TextView(this).apply {
            text = if (BuildConfig.APP_ROLE == "tukang_galon") "GalonKu Mitra Kurir" else "GalonKu Pelanggan"
            textSize = 17f
            setTextColor(android.graphics.Color.WHITE)
            typeface = android.graphics.Typeface.DEFAULT_BOLD
            layoutParams = LinearLayout.LayoutParams(0, dp(42), 1f)
            gravity = Gravity.CENTER_VERTICAL
        }
        val serverButton = Button(this).apply {
            text = "Server"
            textSize = 11f
            setOnClickListener { showServerSettings() }
        }
        toolbar.addView(title)
        toolbar.addView(serverButton)
        progress = ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal).apply {
            max = 100
            visibility = ProgressBar.GONE
        }
        webView = WebView(this).apply {
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.allowFileAccess = false
            settings.javaScriptCanOpenWindowsAutomatically = false
            addJavascriptInterface(NativeLocationBridge(), "GalonKuNativeLocation")
            addJavascriptInterface(NativeAuthBridge(), "GalonKuNativeAuth")
            webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    val target = request.url
                    val initial = Uri.parse(appUrl())
                    return target.host != initial.host || target.scheme !in listOf("https", "http")
                }

                override fun onReceivedError(
                    view: WebView,
                    request: WebResourceRequest,
                    error: android.webkit.WebResourceError
                ) {
                    super.onReceivedError(view, request, error)
                    if (request.isForMainFrame) showConnectionError()
                }

                override fun onPageFinished(view: WebView, url: String) {
                    super.onPageFinished(view, url)
                    installNativeLocationBridge()
                }
            }
            webChromeClient = object : WebChromeClient() {
                override fun onProgressChanged(view: WebView, newProgress: Int) {
                    this@MainActivity.progress.progress = newProgress
                    this@MainActivity.progress.visibility =
                        if (newProgress < 100) ProgressBar.VISIBLE else ProgressBar.GONE
                }

                override fun onShowFileChooser(
                    webView: WebView,
                    callback: ValueCallback<Array<Uri>>,
                    params: FileChooserParams
                ): Boolean {
                    fileChooserCallback?.onReceiveValue(null)
                    fileChooserCallback = callback
                    val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
                        type = "image/*"
                        addCategory(Intent.CATEGORY_OPENABLE)
                    }
                    startActivityForResult(Intent.createChooser(intent, "Pilih foto"), requestFileCode)
                    return true
                }
            }
        }
        root.addView(toolbar)
        root.addView(progress, LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(3)))
        root.addView(webView, LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 0, 1f))
        setContentView(root)
        val filter = IntentFilter(NativeLocationEvents.ACTION_LOCATION)
        ContextCompat.registerReceiver(this, locationReceiver, filter, ContextCompat.RECEIVER_NOT_EXPORTED)
        locationReceiverRegistered = true
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) webView.goBack() else finish()
            }
        })
        webView.loadUrl(appUrl())
    }

    private fun appUrl(): String {
        val savedUrl = preferences.getString("app_url", null)?.trim()?.takeIf(String::isNotEmpty)
            ?: return BuildConfig.DEFAULT_APP_URL
        val oldLocalServer = "http://192.168.1.9:8080/percobaan/GalonKu_Mobile_Starter/pwa/"
        val migratedUrl = savedUrl
            .replace("http://192.168.1.9/percobaan/GalonKu_Mobile_Starter/pwa/customer/", "http://192.168.43.194/galonku-app/GalonKu_Mobile_Starter/pwa/customer/")
            .replace("http://192.168.1.9/percobaan/GalonKu_Mobile_Starter/pwa/courier/", "http://192.168.43.194/galonku-app/GalonKu_Mobile_Starter/pwa/courier/")
            .replace("192.168.175.34:8080/GalonKu", "192.168.43.194/galonku-app")
        if (migratedUrl != savedUrl) {
            preferences.edit().putString("app_url", migratedUrl).apply()
            return migratedUrl
        }
        if (savedUrl.startsWith(oldLocalServer)) {
            val updatedUrl = savedUrl.replaceFirst("http://192.168.1.9:8080", "http://192.168.1.9")
            preferences.edit().putString("app_url", updatedUrl).apply()
            return updatedUrl
        }
        return savedUrl
    }

    private fun showServerSettings() {
        val input = EditText(this).apply {
            setSingleLine(true)
            hint = "https://domain-anda/galonku/pwa/${if (BuildConfig.APP_ROLE == "tukang_galon") "courier" else "customer"}/"
            setText(appUrl())
            selectAll()
        }
        AlertDialog.Builder(this)
            .setTitle("Alamat server GalonKu")
            .setMessage("Masukkan URL aplikasi sesuai role. Gunakan domain HTTPS agar dapat diakses melalui data seluler.")
            .setView(input)
            .setPositiveButton("Simpan") { _, _ ->
                val entered = input.text.toString().trim()
                val uri = runCatching { Uri.parse(entered) }.getOrNull()
                if (uri == null || uri.host.isNullOrBlank() || uri.scheme !in listOf("https", "http")) {
                    showConnectionError("URL server tidak valid. Gunakan alamat lengkap http:// atau https://.")
                } else {
                    preferences.edit().putString("app_url", entered).apply()
                    webView.loadUrl(entered)
                }
            }
            .setNegativeButton("Batal", null)
            .show()
    }

    private fun showConnectionError(message: String = "Server belum dapat dijangkau.") {
        if (isFinishing || isDestroyed) return
        AlertDialog.Builder(this)
            .setTitle("Tidak dapat terhubung")
            .setMessage("$message\n\nUntuk Wi-Fi lokal, komputer dan HP harus berada di jaringan yang sama. Untuk data seluler, gunakan alamat server publik dengan HTTPS.")
            .setPositiveButton("Coba lagi") { _, _ -> webView.loadUrl(appUrl()) }
            .setNeutralButton("Atur server") { _, _ -> showServerSettings() }
            .setNegativeButton("Nanti", null)
            .show()
    }

    private fun installNativeLocationBridge() {
        webView.evaluateJavascript(
            """
            (() => {
              if (!window.GalonKuNativeLocation || window.__galonkuNativeGeoInstalled) return;
              window.__galonkuNativeGeoInstalled = true;
              const callbacks = new Map();
              let nextId = 1;
              window.__galonkuLocationSuccess = (id, value) => {
                const entry = callbacks.get(id);
                if (!entry) return;
                entry.success({
                  coords: {
                    latitude: value.latitude,
                    longitude: value.longitude,
                    accuracy: value.accuracy,
                    altitude: null,
                    altitudeAccuracy: null,
                    heading: null,
                    speed: null
                  },
                  timestamp: value.timestamp
                });
                if (entry.oneShot) callbacks.delete(id);
              };
              window.__galonkuLocationError = (id, code) => {
                const entry = callbacks.get(id);
                if (!entry) return;
                if (entry.error) entry.error({ code, message: 'Izin atau lokasi GPS tidak tersedia.' });
                callbacks.delete(id);
              };
              const start = (success, error, oneShot) => {
                const id = nextId++;
                callbacks.set(id, { success, error, oneShot });
                const token = localStorage.getItem('galonku_courier_token') || '';
                window.GalonKuNativeLocation.start(id, oneShot, token);
                return id;
              };
              Object.defineProperty(navigator, 'geolocation', {
                configurable: true,
                value: {
                  getCurrentPosition: (success, error) => start(success, error, true),
                  watchPosition: (success, error) => start(success, error, false),
                  clearWatch: (id) => {
                    callbacks.delete(id);
                    window.GalonKuNativeLocation.stop(id);
                  }
                }
              });
            })();
            """.trimIndent(),
            null
        )
    }

    private inner class NativeLocationBridge {
        @JavascriptInterface
        fun start(id: Int, oneShot: Boolean, token: String) {
            runOnUiThread {
                val apiUrl = courierLocationUrl()
                pendingLocations[id] = PendingLocation(oneShot, token, apiUrl)
                requestNativeLocationPermission()
            }
        }

        @JavascriptInterface
        fun stop(id: Int) {
            runOnUiThread {
                pendingLocations.remove(id)
                locationCallbacks.remove(id)?.let(fusedLocation::removeLocationUpdates)
                if (BuildConfig.APP_ROLE == "tukang_galon" &&
                    pendingLocations.values.none { !it.oneShot }) {
                    stopService(Intent(this@MainActivity, CourierLocationService::class.java))
                } else if (BuildConfig.APP_ROLE == "tukang_galon") {
                    startService(
                        Intent(this@MainActivity, CourierLocationService::class.java)
                            .setAction(CourierLocationService.ACTION_REMOVE_WATCH)
                            .putExtra(CourierLocationService.EXTRA_WATCH_ID, id)
                    )
                }
            }
        }
    }

    private inner class NativeAuthBridge {
        @JavascriptInterface
        fun signIn() {
            runOnUiThread {
                if (BuildConfig.GOOGLE_WEB_CLIENT_ID.isBlank()) {
                    dispatchGoogleAuthError("Google Sign-In belum dikonfigurasi untuk APK ini.")
                    return@runOnUiThread
                }
                val options = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
                    .requestEmail()
                    .requestIdToken(BuildConfig.GOOGLE_WEB_CLIENT_ID)
                    .build()
                googleSignInLauncher.launch(
                    GoogleSignIn.getClient(this@MainActivity, options).signInIntent
                )
            }
        }
    }

    private fun dispatchGoogleAuthError(message: String) {
        if (!::webView.isInitialized || isFinishing || isDestroyed) return
        webView.post {
            webView.evaluateJavascript(
                "window.galonkuGoogleLoginFailure(${JSONObject.quote(message)})",
                null
            )
        }
    }

    private fun requestNativeLocationPermission() {
        val permissions = arrayOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION
        ) + if (BuildConfig.APP_ROLE == "tukang_galon" && Build.VERSION.SDK_INT >= 33) {
            arrayOf(Manifest.permission.POST_NOTIFICATIONS)
        } else {
            emptyArray()
        }
        val missing = permissions.filter {
            ContextCompat.checkSelfPermission(this, it) != PackageManager.PERMISSION_GRANTED
        }
        if (missing.isEmpty()) {
            startPendingLocationRequests()
            return
        }
        ActivityCompat.requestPermissions(this, missing.toTypedArray(), requestLocationCode)
    }

    private fun startPendingLocationRequests() {
        val courierWatch = pendingLocations.values.any { !it.oneShot } &&
            BuildConfig.APP_ROLE == "tukang_galon"
        if (courierWatch) {
            val request = pendingLocations.values.first { !it.oneShot }
            getSharedPreferences("galonku_location", MODE_PRIVATE).edit()
                .putString("token", request.token)
                .putString("api_url", request.apiUrl)
                .apply()
            ContextCompat.startForegroundService(
                this,
                Intent(this, CourierLocationService::class.java).apply {
                    action = CourierLocationService.ACTION_START
                    putExtra(CourierLocationService.EXTRA_TOKEN, request.token)
                    putExtra(CourierLocationService.EXTRA_API_URL, request.apiUrl)
                    putExtra(
                        CourierLocationService.EXTRA_WATCH_ID,
                        pendingLocations.entries.first { !it.value.oneShot }.key
                    )
                }
            )
        }

        pendingLocations.toMap().forEach { (id, request) ->
            if (request.oneShot || BuildConfig.APP_ROLE != "tukang_galon") {
                startActivityLocationUpdates(id, request.oneShot)
            }
        }
    }

    @SuppressLint("MissingPermission")
    private fun startActivityLocationUpdates(id: Int, oneShot: Boolean) {
        val locationRequest = LocationRequest.Builder(
            Priority.PRIORITY_HIGH_ACCURACY,
            if (oneShot) 1000L else 5000L
        ).setMinUpdateIntervalMillis(1000L).build()
        val callback = object : LocationCallback() {
            override fun onLocationResult(result: LocationResult) {
                val location = result.lastLocation ?: return
                deliverLocation(id, location)
                if (oneShot) {
                    locationCallbacks.remove(id)?.let(fusedLocation::removeLocationUpdates)
                    pendingLocations.remove(id)
                }
            }
        }
        locationCallbacks[id] = callback
        fusedLocation.requestLocationUpdates(locationRequest, callback, mainLooper)
            .addOnFailureListener {
                webView.evaluateJavascript("window.__galonkuLocationError($id, 2)", null)
                pendingLocations.remove(id)
                locationCallbacks.remove(id)
            }
    }

    private fun deliverLocation(id: Int, latitude: Double, longitude: Double, accuracy: Float) {
        if (!latitude.isFinite() || !longitude.isFinite()) return
        val escapedAccuracy = accuracy.toString()
        webView.post {
            webView.evaluateJavascript(
                "window.__galonkuLocationSuccess($id, {latitude:$latitude, longitude:$longitude, accuracy:$escapedAccuracy, timestamp:Date.now()})",
                null
            )
        }
    }

    private fun deliverLocation(id: Int, location: Location) =
        deliverLocation(id, location.latitude, location.longitude, location.accuracy)

    private fun courierLocationUrl(): String {
        val current = URI(appUrl())
        return current.resolve("../../backend/galonku-api/api/courier/location.php").toString()
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == requestLocationCode) {
            val locationGranted =
                ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) ==
                    PackageManager.PERMISSION_GRANTED ||
                    ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) ==
                    PackageManager.PERMISSION_GRANTED
            if (locationGranted) {
                startPendingLocationRequests()
            } else {
                pendingLocations.keys.forEach { id ->
                    webView.evaluateJavascript("window.__galonkuLocationError($id, 1)", null)
                }
                pendingLocations.clear()
            }
        }
    }

    @Deprecated("Deprecated in Android API; retained for WebView file chooser compatibility.")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == requestFileCode) {
            val result = if (resultCode == RESULT_OK) {
                data?.data?.let { arrayOf(it) }
            } else null
            fileChooserCallback?.onReceiveValue(result)
            fileChooserCallback = null
        }
    }

    override fun onDestroy() {
        if (locationReceiverRegistered) {
            unregisterReceiver(locationReceiver)
        }
        locationCallbacks.values.forEach(fusedLocation::removeLocationUpdates)
        locationCallbacks.clear()
        if (::webView.isInitialized) {
            webView.stopLoading()
            webView.destroy()
        }
        super.onDestroy()
    }

    private fun dp(value: Int): Int = (value * resources.displayMetrics.density).toInt()
}

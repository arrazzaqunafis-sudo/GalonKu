package id.galonku.mobile

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.content.pm.PackageManager
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import androidx.core.app.NotificationCompat
import androidx.core.app.ServiceCompat
import androidx.core.content.ContextCompat
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.Executors

object NativeLocationEvents {
    const val ACTION_LOCATION = "id.galonku.mobile.LOCATION_UPDATE"
}

class CourierLocationService : Service() {
    private val channelId = "galonku_location"
    private val networkExecutor = Executors.newSingleThreadExecutor()
    private val fused by lazy { LocationServices.getFusedLocationProviderClient(this) }
    private var callback: LocationCallback? = null
    private var token: String = ""
    private var apiUrl: String = ""
    private val watchIds = ConcurrentHashMap.newKeySet<Int>()

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            stopTracking()
            stopSelf()
            return START_NOT_STICKY
        }
        if (intent?.action == ACTION_REMOVE_WATCH) {
            watchIds.remove(intent.getIntExtra(EXTRA_WATCH_ID, -1))
            if (watchIds.isEmpty()) {
                stopTracking()
                stopSelf()
            }
            return START_NOT_STICKY
        }

        token = intent?.getStringExtra(EXTRA_TOKEN)
            ?: getSharedPreferences("galonku_location", MODE_PRIVATE).getString("token", "").orEmpty()
        apiUrl = intent?.getStringExtra(EXTRA_API_URL)
            ?: getSharedPreferences("galonku_location", MODE_PRIVATE).getString("api_url", "").orEmpty()
        intent?.getIntExtra(EXTRA_WATCH_ID, -1)?.takeIf { it >= 0 }?.let(watchIds::add)
        if (token.isBlank() || apiUrl.isBlank()) {
            stopSelf()
            return START_NOT_STICKY
        }

        val notification = buildNotification()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            ServiceCompat.startForeground(
                this,
                1001,
                notification,
                ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION
            )
        } else {
            startForeground(1001, notification)
        }
        beginLocationUpdates()
        return START_NOT_STICKY
    }

    private fun beginLocationUpdates() {
        val fine = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION)
        val coarse = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION)
        if (fine != PackageManager.PERMISSION_GRANTED && coarse != PackageManager.PERMISSION_GRANTED) {
            stopSelf()
            return
        }
        callback?.let(fused::removeLocationUpdates)
        val request = LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY, 15_000L)
            .setMinUpdateIntervalMillis(10_000L)
            .setMinUpdateDistanceMeters(25f)
            .build()
        callback = object : LocationCallback() {
            override fun onLocationResult(result: LocationResult) {
                val location = result.lastLocation ?: return
                watchIds.forEach { id ->
                    sendBroadcast(
                        Intent(NativeLocationEvents.ACTION_LOCATION)
                            .setPackage(packageName)
                            .putExtra("watch_id", id)
                            .putExtra("latitude", location.latitude)
                            .putExtra("longitude", location.longitude)
                            .putExtra("accuracy", location.accuracy)
                            .putExtra("timestamp", location.time)
                    )
                }
                updateBackend(location.latitude, location.longitude, location.accuracy)
            }
        }
        try {
            fused.requestLocationUpdates(request, callback!!, mainLooper)
        } catch (_: SecurityException) {
            stopSelf()
        }
    }

    private fun updateBackend(latitude: Double, longitude: Double, accuracy: Float) {
        networkExecutor.execute {
            var connection: HttpURLConnection? = null
            try {
                connection = (URL(apiUrl).openConnection() as HttpURLConnection).apply {
                    requestMethod = "POST"
                    connectTimeout = 10_000
                    readTimeout = 10_000
                    doOutput = true
                    setRequestProperty("Authorization", "Bearer $token")
                    setRequestProperty("Content-Type", "application/json; charset=utf-8")
                }
                val payload = JSONObject()
                    .put("latitude", latitude)
                    .put("longitude", longitude)
                    .put("accuracy", accuracy)
                    .toString()
                connection.outputStream.use { it.write(payload.toByteArray(Charsets.UTF_8)) }
                connection.inputStream.close()
            } catch (_: Exception) {
                // GPS continues locally; the next location update will retry the API request.
            } finally {
                connection?.disconnect()
            }
        }
    }

    private fun stopTracking() {
        callback?.let(fused::removeLocationUpdates)
        callback = null
        watchIds.clear()
        ServiceCompat.stopForeground(this, ServiceCompat.STOP_FOREGROUND_REMOVE)
    }

    override fun onDestroy() {
        stopTracking()
        networkExecutor.shutdown()
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                channelId,
                "Pelacakan lokasi kurir",
                NotificationManager.IMPORTANCE_LOW
            )
            getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
        }
    }

    private fun buildNotification(): Notification =
        NotificationCompat.Builder(this, channelId)
            .setContentTitle("GalonKu — Lokasi kurir aktif")
            .setContentText("Lokasi dibagikan saat status kurir online.")
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setOngoing(true)
            .build()

    companion object {
        const val ACTION_START = "id.galonku.mobile.action.START_LOCATION"
        const val ACTION_STOP = "id.galonku.mobile.action.STOP_LOCATION"
        const val ACTION_REMOVE_WATCH = "id.galonku.mobile.action.REMOVE_WATCH"
        const val EXTRA_TOKEN = "token"
        const val EXTRA_API_URL = "api_url"
        const val EXTRA_WATCH_ID = "watch_id"
    }
}

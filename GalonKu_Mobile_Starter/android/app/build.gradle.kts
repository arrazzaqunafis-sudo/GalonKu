plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

val googleWebClientId = providers.gradleProperty("galonkuGoogleClientId")
    .orElse(providers.environmentVariable("GALONKU_GOOGLE_CLIENT_ID"))
    .getOrElse("")
val escapedGoogleWebClientId = googleWebClientId
    .replace("\\", "\\\\")
    .replace("\"", "\\\"")

android {
    namespace = "id.galonku.mobile"
    compileSdk = 35

    defaultConfig {
        applicationId = "id.galonku.mobile"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"
        buildConfigField("String", "GOOGLE_WEB_CLIENT_ID", "\"$escapedGoogleWebClientId\"")
    }

    flavorDimensions += "role"
    productFlavors {
        create("customer") {
            dimension = "role"
            applicationIdSuffix = ".customer"
            resValue("string", "app_name", "GalonKu Pelanggan")
            buildConfigField("String", "APP_ROLE", "\"masyarakat\"")
            buildConfigField(
                "String",
                "DEFAULT_APP_URL",
                "\"http://192.168.43.194/galonku-app/GalonKu_Mobile_Starter/pwa/customer/\""
            )
        }
        create("courier") {
            dimension = "role"
            applicationIdSuffix = ".courier"
            resValue("string", "app_name", "GalonKu Kurir")
            buildConfigField("String", "APP_ROLE", "\"tukang_galon\"")
            buildConfigField(
                "String",
                "DEFAULT_APP_URL",
                "\"http://192.168.43.194/galonku-app/GalonKu_Mobile_Starter/pwa/courier/\""
            )
        }
    }

    buildFeatures { buildConfig = true }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions { jvmTarget = "17" }
}

dependencies {
    implementation("com.google.android.gms:play-services-auth:21.3.0")
    implementation("com.google.android.gms:play-services-location:21.3.0")
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
}

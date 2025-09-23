# Postman Collection - POS Subscription API

## 📁 File Collection

-   **Collection**: `POS_Subscription_API.postman_collection.json`
-   **Environment**: `POS_Subscription_Environment.postman_environment.json`

## 🚀 Cara Import ke Postman

### 1. Import Collection

1. Buka Postman
2. Klik **Import** di sidebar kiri
3. Drag & drop file `POS_Subscription_API.postman_collection.json` atau klik **Upload Files**
4. Klik **Import**

### 2. Import Environment

1. Klik **Import** lagi
2. Drag & drop file `POS_Subscription_Environment.postman_environment.json`
3. Klik **Import**
4. Pilih environment "POS Subscription Environment" di dropdown kanan atas

## 📋 Workflow Testing

### Skenario 1: Trial Flow

```
1. Register Merchant
2. Login (otomatis set token)
3. Get All Plans (lihat available plans)
4. Start Trial (gunakan plan_id dari step 3)
5. Get Trial Status
6. Register Device
7. Issue License
8. Validate License
```

### Skenario 2: Subscription Flow

```
1. Register Merchant
2. Login (otomatis set token)
3. Get All Plans
4. Checkout Subscription (otomatis set invoice_id)
5. Submit Payment Confirmation (upload bukti transfer)
6. Get Checkout Status
7. Register Device
8. Issue License
9. Validate License
```

### Skenario 3: License Management

```
1. Login
2. Validate License (cek status license)
3. Refresh License (jika diperlukan)
```

## ⚙️ Variables yang Digunakan

### Collection Variables (Auto-filled)

-   `base_url`: http://localhost
-   `token`: Bearer token (auto-set saat login)
-   `merchant_id`: ID merchant (auto-set saat login)
-   `device_id`: ID device (auto-set saat register device)
-   `device_uuid`: UUID device (auto-set saat register device)
-   `subscription_id`: ID subscription (auto-set saat start trial/checkout)
-   `invoice_id`: ID invoice (auto-set saat checkout)
-   `license_token`: JWT token license (auto-set saat issue license)

### Environment Variables

-   `plan_id_monthly`: 1 (ID untuk monthly plan)
-   `plan_id_yearly`: 2 (ID untuk yearly plan)

## 🔐 Authentication

Semua endpoint yang memerlukan autentikasi sudah dikonfigurasi untuk menggunakan `Bearer {{token}}` yang akan otomatis terisi setelah login berhasil.

## 📝 Request Body Examples

### Register Merchant

```json
{
    "name": "John Doe",
    "email": "merchant@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "business_name": "My Store",
    "business_type": "retail",
    "phone": "+6281234567890",
    "address": "Jl. Sudirman No. 123, Jakarta"
}
```

### Login

```json
{
    "email": "merchant@example.com",
    "password": "password123"
}
```

### Start Trial

```json
{
    "plan_id": 1
}
```

### Checkout Subscription

```json
{
    "plan_id": 1
}
```

### Register Device

```json
{
    "device_name": "POS Terminal 1",
    "device_uuid": "DEVICE-UUID-12345",
    "device_type": "pos_terminal",
    "os_version": "Windows 10",
    "app_version": "1.0.0"
}
```

### Submit Payment Confirmation

-   Gunakan **form-data** karena ada file upload
-   Fields:
    -   `invoice_id`: {{invoice_id}}
    -   `amount`: 199000 (dalam IDR, bukan cents)
    -   `bank_name`: "BCA"
    -   `reference_no`: "TRX123456789"
    -   `notes`: "Payment via mobile banking"
    -   `evidence_file`: [Upload file JPG/PNG/PDF max 5MB]

### Issue License

```json
{
    "device_uuid": "{{device_uuid}}"
}
```

### Validate License

```json
{
    "license_token": "{{license_token}}",
    "device_uuid": "{{device_uuid}}"
}
```

## 🧪 Test Scripts

Collection sudah dilengkapi dengan test scripts otomatis yang akan:

1. Otomatis menyimpan token saat login berhasil
2. Otomatis menyimpan ID merchant, device, invoice, dll.
3. Validasi response time < 5 detik
4. Validasi format JSON response

## 🔄 Environment Setup

Untuk environment production, update variabel `base_url` ke URL production server Anda.

### Development

```
base_url: http://localhost
```

### Production

```
base_url: https://your-production-domain.com
```

## 📊 Response Examples

### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // response data here
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // validation errors if any
    }
}
```

## 🎯 Tips Penggunaan

1. **Urutan Testing**: Ikuti workflow yang sudah disediakan untuk hasil yang optimal
2. **Auto Variables**: Banyak variabel akan otomatis terisi, tidak perlu manual input
3. **File Upload**: Untuk payment confirmation, pastikan file evidence dalam format yang didukung
4. **Error Handling**: Cek response body jika ada error untuk debug
5. **Environment**: Pastikan environment sudah dipilih sebelum testing

## 🛠 Troubleshooting

### Token Expired

-   Login ulang untuk mendapatkan token baru

### Device Already Registered

-   Gunakan device_uuid yang berbeda atau gunakan yang sudah terdaftar

### Invoice Not Found

-   Pastikan sudah melakukan checkout terlebih dahulu

### License Validation Failed

-   Cek apakah subscription masih aktif
-   Pastikan license token dan device_uuid sesuai

Semua endpoint sudah siap untuk testing dan dapat langsung digunakan setelah import ke Postman!

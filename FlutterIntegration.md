# 📱 Flutter Integration

## Quick Integration Guide

The POS Subscription system provides a seamless integration path for Flutter applications with a simple workflow:

1. **Status Check** → **Trial/Checkout** → **Payment** → **License** → **Auto-Refresh**

## Integration Flow

### 1. First Run - Check Subscription Status

```dart
Future<SubscriptionStatus> checkSubscriptionStatus() async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/subscription/status'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'merchant_id': merchantId,
      'device_uid': deviceUID, // Unique device identifier
    }),
  );

  if (response.statusCode == 200) {
    return SubscriptionStatus.fromJson(jsonDecode(response.body));
  }
  throw Exception('Failed to check subscription status');
}
```

### 2. Handle Inactive Subscription

```dart
Future<void> handleInactiveSubscription(bool hasTrialUsed) async {
  if (!hasTrialUsed) {
    // Offer trial option
    await showTrialDialog();
  } else {
    // Direct to checkout/payment
    await showCheckoutDialog();
  }
}

// Start trial subscription
Future<TrialResult> startTrial(int planId) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/trials/start'),
    headers: {
      'Authorization': 'Bearer $authToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'plan_id': planId,
      'device_id': deviceId,
    }),
  );

  if (response.statusCode == 201) {
    final result = TrialResult.fromJson(jsonDecode(response.body));
    await storeSecureToken(result.licenseToken);
    return result;
  }
  throw Exception('Failed to start trial');
}
```

### 3. Payment Confirmation Flow

```dart
Future<void> submitPaymentProof(File evidenceFile) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('$baseUrl/api/v1/payment-confirmations'),
  );

  request.headers['Authorization'] = 'Bearer $authToken';
  request.fields.addAll({
    'invoice_id': invoiceId.toString(),
    'amount': amountCents.toString(),
    'bank_name': bankName,
    'reference_no': referenceNumber,
  });

  request.files.add(await http.MultipartFile.fromPath(
    'evidence_file',
    evidenceFile.path,
  ));

  final response = await request.send();
  if (response.statusCode == 201) {
    // Payment submitted, wait for admin approval
    await pollPaymentStatus();
  }
}
```

### 4. License Management & Storage

```dart
// Store JWT securely
Future<void> storeSecureToken(String token) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('license_token', token);
  // For sensitive data, use flutter_secure_storage:
  // await secureStorage.write(key: 'license_token', value: token);
}

// Local token validation on app boot
Future<bool> validateLocalToken() async {
  final prefs = await SharedPreferences.getInstance();
  final token = prefs.getString('license_token');

  if (token == null) return false;

  try {
    // Decode JWT without verification to check expiry
    final parts = token.split('.');
    final payload = jsonDecode(
      utf8.decode(base64Url.decode(base64Url.normalize(parts[1])))
    );

    final exp = payload['exp'] as int;
    final expiryDate = DateTime.fromMillisecondsSinceEpoch(exp * 1000);
    final now = DateTime.now();

    // If token expires within 72 hours, refresh it
    if (expiryDate.difference(now).inHours < 72) {
      return await refreshLicense();
    }

    return expiryDate.isAfter(now);
  } catch (e) {
    return false;
  }
}

// Auto-refresh license token
Future<bool> refreshLicense() async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/licenses/refresh'),
    headers: {
      'Authorization': 'Bearer $authToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'device_id': deviceId}),
  );

  if (response.statusCode == 200) {
    final result = jsonDecode(response.body);
    await storeSecureToken(result['data']['license_token']);
    return true;
  }
  return false;
}
```

## Key Implementation Notes

-   **Device UID**: Use a consistent device identifier (Android ID, iOS identifierForVendor)
-   **Secure Storage**: Store JWT tokens in secure storage for production apps
-   **Auto-Refresh**: Check token expiry on app launch and refresh when needed
-   **Offline Grace**: Allow 72-hour grace period for license validation
-   **Error Handling**: Implement proper retry mechanisms for network failures

## Dependencies

```yaml
dependencies:
    http: ^0.13.5
    shared_preferences: ^2.0.15
    flutter_secure_storage: ^9.0.0 # For production token storage
```

## Complete Integration Example

```dart
class SubscriptionManager {
  static const String baseUrl = 'https://your-api-domain.com';
  late SharedPreferences _prefs;
  late FlutterSecureStorage _secureStorage;

  Future<void> initialize() async {
    _prefs = await SharedPreferences.getInstance();
    _secureStorage = const FlutterSecureStorage();
  }

  // Main entry point for subscription flow
  Future<bool> ensureValidSubscription() async {
    // Check local token first
    if (await validateLocalToken()) {
      return true;
    }

    // Check subscription status from server
    final status = await checkSubscriptionStatus();

    if (status.isActive) {
      await issueNewLicense();
      return true;
    }

    // Handle inactive subscription
    return await handleSubscriptionFlow(status);
  }

  Future<bool> handleSubscriptionFlow(SubscriptionStatus status) async {
    if (!status.trialUsed) {
      // Show trial options
      return await offerTrialFlow();
    } else {
      // Show payment options
      return await offerPaymentFlow();
    }
  }

  Future<String?> getStoredToken() async {
    return await _secureStorage.read(key: 'license_token');
  }

  Future<void> clearStoredToken() async {
    await _secureStorage.delete(key: 'license_token');
  }
}
```

## Error Handling

```dart
class SubscriptionException implements Exception {
  final String message;
  final int? statusCode;

  SubscriptionException(this.message, [this.statusCode]);
}

// Usage in API calls
try {
  final result = await startTrial(selectedPlanId);
  showSuccessDialog('Trial started successfully!');
} on SubscriptionException catch (e) {
  if (e.statusCode == 422) {
    showErrorDialog('Trial already used or invalid plan');
  } else {
    showErrorDialog('Network error: ${e.message}');
  }
} catch (e) {
  showErrorDialog('Unexpected error occurred');
}
```

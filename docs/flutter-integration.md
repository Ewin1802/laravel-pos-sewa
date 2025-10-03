# Flutter Integration Guide

## POS Subscription System API Integration

This guide provides comprehensive documentation for integrating the Laravel POS Subscription System with Flutter applications.

## Base Configuration

```dart
class ApiConfig {
  static const String baseUrl = 'https://your-domain.com/api/v1';
  static const Duration timeout = Duration(seconds: 30);

  // Headers
  static Map<String, String> headers(String? token) => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };
}
```

## Authentication

### 1. Merchant Registration

```dart
class AuthService {
  static Future<AuthResponse> registerMerchant({
    required String name,
    required String email,
    required String password,
    required String businessName,
    required String contactName,
    required String phone,
    String? whatsapp,
    String? address,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/auth/register-merchant'),
        headers: ApiConfig.headers(null),
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'business_name': businessName,
          'contact_name': contactName,
          'phone': phone,
          'whatsapp': whatsapp,
          'address': address,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return AuthResponse.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<AuthResponse> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/auth/login'),
        headers: ApiConfig.headers(null),
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return AuthResponse.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}
```

### 2. Data Models

```dart
class AuthResponse {
  final User user;
  final Merchant merchant;
  final String token;

  AuthResponse({
    required this.user,
    required this.merchant,
    required this.token,
  });

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      user: User.fromJson(json['user']),
      merchant: Merchant.fromJson(json['merchant']),
      token: json['token'],
    );
  }
}

class User {
  final int id;
  final String name;
  final String email;
  final DateTime? emailVerifiedAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.emailVerifiedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      emailVerifiedAt: json['email_verified_at'] != null
          ? DateTime.parse(json['email_verified_at'])
          : null,
    );
  }
}

class Merchant {
  final int id;
  final String name;
  final String contactName;
  final String email;
  final String phone;
  final String? whatsapp;
  final String? address;
  final String status;
  final bool trialUsed;

  Merchant({
    required this.id,
    required this.name,
    required this.contactName,
    required this.email,
    required this.phone,
    this.whatsapp,
    this.address,
    required this.status,
    required this.trialUsed,
  });

  factory Merchant.fromJson(Map<String, dynamic> json) {
    return Merchant(
      id: json['id'],
      name: json['name'],
      contactName: json['contact_name'],
      email: json['email'],
      phone: json['phone'],
      whatsapp: json['whatsapp'],
      address: json['address'],
      status: json['status'],
      trialUsed: json['trial_used'] ?? false,
    );
  }
}
```

## Device Management

```dart
class DeviceService {
  static Future<Device> registerDevice({
    required String token,
    required String deviceUid,
    String? label,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/devices/register'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'device_uid': deviceUid,
          'label': label ?? 'Flutter Device',
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return Device.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}

class Device {
  final int id;
  final String deviceUid;
  final String label;
  final bool isActive;
  final DateTime? lastSeenAt;

  Device({
    required this.id,
    required this.deviceUid,
    required this.label,
    required this.isActive,
    this.lastSeenAt,
  });

  factory Device.fromJson(Map<String, dynamic> json) {
    return Device(
      id: json['id'],
      deviceUid: json['device_uid'],
      label: json['label'],
      isActive: json['is_active'],
      lastSeenAt: json['last_seen_at'] != null
          ? DateTime.parse(json['last_seen_at'])
          : null,
    );
  }
}
```

## Subscription Management

### 1. Get Subscription Status

```dart
class SubscriptionService {
  static Future<SubscriptionStatus> getStatus(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/subscription/status'),
        headers: ApiConfig.headers(token),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return SubscriptionStatus.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}

class SubscriptionStatus {
  final bool hasSubscription;
  final Subscription? subscription;

  SubscriptionStatus({
    required this.hasSubscription,
    this.subscription,
  });

  factory SubscriptionStatus.fromJson(Map<String, dynamic> json) {
    return SubscriptionStatus(
      hasSubscription: json['has_subscription'],
      subscription: json['subscription'] != null
          ? Subscription.fromJson(json['subscription'])
          : null,
    );
  }
}

class Subscription {
  final int id;
  final String status;
  final bool isTrial;
  final DateTime? startAt;
  final DateTime? endAt;
  final DateTime? trialStartedAt;
  final DateTime? trialEndAt;
  final bool isTrialExpired;
  final Plan plan;
  final Invoice? currentInvoice;

  Subscription({
    required this.id,
    required this.status,
    required this.isTrial,
    this.startAt,
    this.endAt,
    this.trialStartedAt,
    this.trialEndAt,
    required this.isTrialExpired,
    required this.plan,
    this.currentInvoice,
  });

  factory Subscription.fromJson(Map<String, dynamic> json) {
    return Subscription(
      id: json['id'],
      status: json['status'],
      isTrial: json['is_trial'],
      startAt: json['start_at'] != null ? DateTime.parse(json['start_at']) : null,
      endAt: json['end_at'] != null ? DateTime.parse(json['end_at']) : null,
      trialStartedAt: json['trial_started_at'] != null
          ? DateTime.parse(json['trial_started_at'])
          : null,
      trialEndAt: json['trial_end_at'] != null
          ? DateTime.parse(json['trial_end_at'])
          : null,
      isTrialExpired: json['is_trial_expired'],
      plan: Plan.fromJson(json['plan']),
      currentInvoice: json['current_invoice'] != null
          ? Invoice.fromJson(json['current_invoice'])
          : null,
    );
  }

  bool get isExpired => status == 'expired';
  bool get isActive => status == 'active';
  bool get isPending => status == 'pending';
}
```

## Subscription Renewal

### 1. Check Renewal Status

```dart
class RenewalService {
  static Future<RenewalStatus> getRenewalStatus(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/subscription/renewal/status'),
        headers: ApiConfig.headers(token),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return RenewalStatus.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<RenewalResult> renewSubscription(String token) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/subscription/renewal/renew'),
        headers: ApiConfig.headers(token),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return RenewalResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<RenewalResult> renewWithPlan({
    required String token,
    required int planId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/subscription/renewal/renew-with-plan'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'plan_id': planId,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return RenewalResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}

class RenewalStatus {
  final bool hasSubscription;
  final bool canRenew;
  final bool renewalAvailable;
  final Subscription? subscription;
  final Invoice? pendingRenewalInvoice;

  RenewalStatus({
    required this.hasSubscription,
    required this.canRenew,
    required this.renewalAvailable,
    this.subscription,
    this.pendingRenewalInvoice,
  });

  factory RenewalStatus.fromJson(Map<String, dynamic> json) {
    return RenewalStatus(
      hasSubscription: json['has_subscription'],
      canRenew: json['can_renew'],
      renewalAvailable: json['renewal_available'],
      subscription: json['subscription'] != null
          ? Subscription.fromJson(json['subscription'])
          : null,
      pendingRenewalInvoice: json['pending_renewal_invoice'] != null
          ? Invoice.fromJson(json['pending_renewal_invoice'])
          : null,
    );
  }
}

class RenewalResult {
  final Subscription subscription;
  final Invoice invoice;
  final PaymentInstructions paymentInstructions;
  final String renewalType;

  RenewalResult({
    required this.subscription,
    required this.invoice,
    required this.paymentInstructions,
    required this.renewalType,
  });

  factory RenewalResult.fromJson(Map<String, dynamic> json) {
    return RenewalResult(
      subscription: Subscription.fromJson(json['subscription']),
      invoice: Invoice.fromJson(json['invoice']),
      paymentInstructions: PaymentInstructions.fromJson(json['payment_instructions']),
      renewalType: json['renewal_type'],
    );
  }
}
```

## Trial Management

```dart
class TrialService {
  static Future<TrialResult> startTrial({
    required String token,
    required String deviceUid,
    int? planId,
    int? trialDays,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/trial/start'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'device_uid': deviceUid,
          if (planId != null) 'plan_id': planId,
          if (trialDays != null) 'trial_days': trialDays,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return TrialResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<TrialStats> getTrialStatus(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/trial/status'),
        headers: ApiConfig.headers(token),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return TrialStats.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<ConversionResult> convertToPaid({
    required String token,
    required int planId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/trial/convert'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'plan_id': planId,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return ConversionResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}
```

## Payment & Checkout

```dart
class PaymentService {
  static Future<CheckoutResult> checkout({
    required String token,
    required int planId,
    required String deviceUid,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/checkout'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'plan_id': planId,
          'device_uid': deviceUid,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return CheckoutResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<PaymentConfirmationResult> submitPaymentConfirmation({
    required String token,
    required int invoiceId,
    required double amount,
    required String paymentMethod,
    required String referenceNo,
    required DateTime paymentDate,
    String? notes,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/payment-confirmations'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'invoice_id': invoiceId,
          'amount': amount,
          'payment_method': paymentMethod,
          'reference_no': referenceNo,
          'payment_date': paymentDate.toIso8601String(),
          if (notes != null) 'notes': notes,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return PaymentConfirmationResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}

class PaymentInstructions {
  final double amount;
  final String currency;
  final String dueDate;
  final int invoiceId;
  final Map<String, dynamic> paymentMethods;

  PaymentInstructions({
    required this.amount,
    required this.currency,
    required this.dueDate,
    required this.invoiceId,
    required this.paymentMethods,
  });

  factory PaymentInstructions.fromJson(Map<String, dynamic> json) {
    return PaymentInstructions(
      amount: json['amount'].toDouble(),
      currency: json['currency'],
      dueDate: json['due_date'],
      invoiceId: json['invoice_id'],
      paymentMethods: json['payment_methods'],
    );
  }
}
```

## License Management

```dart
class LicenseService {
  static Future<LicenseResult> issueLicense({
    required String token,
    required String deviceUid,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/license/issue'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'device_uid': deviceUid,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return LicenseResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<LicenseValidationResult> validateLicense({
    required String token,
    required String licenseToken,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/license/validate'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'license_token': licenseToken,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return LicenseValidationResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }

  static Future<LicenseResult> refreshLicense({
    required String token,
    required String deviceUid,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/license/refresh'),
        headers: ApiConfig.headers(token),
        body: jsonEncode({
          'device_uid': deviceUid,
        }),
      ).timeout(ApiConfig.timeout);

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return LicenseResult.fromJson(data['data']);
      } else {
        throw ApiException(data['message'], response.statusCode);
      }
    } catch (e) {
      throw _handleError(e);
    }
  }
}
```

## Error Handling

```dart
class ApiException implements Exception {
  final String message;
  final int statusCode;

  ApiException(this.message, this.statusCode);

  @override
  String toString() => 'ApiException: $message (HTTP $statusCode)';
}

Exception _handleError(dynamic error) {
  if (error is SocketException) {
    return ApiException('No internet connection', 0);
  } else if (error is TimeoutException) {
    return ApiException('Request timeout', 0);
  } else if (error is ApiException) {
    return error;
  } else {
    return ApiException('Unknown error: $error', 0);
  }
}
```

## Usage Examples

### Complete Flow Example

```dart
class PosSubscriptionManager {
  String? _token;
  Merchant? _merchant;
  Device? _device;

  Future<void> registerAndSetup({
    required String name,
    required String email,
    required String password,
    required String businessName,
    required String contactName,
    required String phone,
    required String deviceUid,
  }) async {
    try {
      // 1. Register merchant
      final authResponse = await AuthService.registerMerchant(
        name: name,
        email: email,
        password: password,
        businessName: businessName,
        contactName: contactName,
        phone: phone,
      );

      _token = authResponse.token;
      _merchant = authResponse.merchant;

      // 2. Register device
      _device = await DeviceService.registerDevice(
        token: _token!,
        deviceUid: deviceUid,
        label: 'Flutter POS Device',
      );

      // 3. Start trial if not used
      if (!_merchant!.trialUsed) {
        await TrialService.startTrial(
          token: _token!,
          deviceUid: deviceUid,
        );
      }

      print('Setup completed successfully!');
    } catch (e) {
      print('Setup failed: $e');
      rethrow;
    }
  }

  Future<void> handleRenewal() async {
    if (_token == null) throw Exception('Not authenticated');

    try {
      // Check renewal status
      final renewalStatus = await RenewalService.getRenewalStatus(_token!);

      if (renewalStatus.canRenew && renewalStatus.renewalAvailable) {
        // Renew subscription
        final renewalResult = await RenewalService.renewSubscription(_token!);

        // Show payment instructions to user
        _showPaymentInstructions(renewalResult.paymentInstructions);

        print('Renewal initiated. Please complete payment.');
      } else if (renewalStatus.pendingRenewalInvoice != null) {
        print('You have a pending renewal payment.');
      } else {
        print('Renewal not available at this time.');
      }
    } catch (e) {
      print('Renewal failed: $e');
      rethrow;
    }
  }

  void _showPaymentInstructions(PaymentInstructions instructions) {
    // Show payment instructions to user in UI
    print('Payment Amount: ${instructions.amount} ${instructions.currency}');
    print('Due Date: ${instructions.dueDate}');
    print('Invoice ID: ${instructions.invoiceId}');
    print('Payment Methods: ${instructions.paymentMethods}');
  }
}
```

## Best Practices

### 1. Token Management

```dart
class TokenManager {
  static const String _tokenKey = 'auth_token';

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }
}
```

### 2. Automatic License Validation

```dart
class LicenseManager {
  static Timer? _validationTimer;

  static void startPeriodicValidation({
    required String token,
    required String licenseToken,
    Duration interval = const Duration(hours: 6),
  }) {
    _validationTimer?.cancel();
    _validationTimer = Timer.periodic(interval, (timer) async {
      try {
        await LicenseService.validateLicense(
          token: token,
          licenseToken: licenseToken,
        );
        print('License validation successful');
      } catch (e) {
        print('License validation failed: $e');
        // Handle license validation failure
      }
    });
  }

  static void stopPeriodicValidation() {
    _validationTimer?.cancel();
  }
}
```

### 3. Subscription Status Monitoring

```dart
class SubscriptionMonitor {
  static void checkSubscriptionStatus(Subscription subscription) {
    if (subscription.isExpired) {
      // Show renewal prompt
      _showRenewalPrompt();
    } else if (subscription.endAt != null) {
      final daysUntilExpiry = subscription.endAt!.difference(DateTime.now()).inDays;

      if (daysUntilExpiry <= 7) {
        // Show expiry warning
        _showExpiryWarning(daysUntilExpiry);
      }
    }
  }

  static void _showRenewalPrompt() {
    // Show renewal UI
  }

  static void _showExpiryWarning(int days) {
    // Show expiry warning UI
  }
}
```

This documentation provides a complete guide for integrating the Laravel POS Subscription System with Flutter applications, including authentication, subscription management, renewals, payments, and license validation.
# REST API Postman Collection & Live Production Integration Guide

This guide provides the complete, importable **Postman Collection v2.1.0 JSON** template and production-ready server and client configurations to launch and consume your REST API live.

---

## 1. Importable Postman Collection (v2.1.0)

Copy the JSON block below and save it as `school_api_collection.json`. In Postman, click **Import** and select the file.

```json
{
	"info": {
		"_postman_id": "8c4d12f9-7ea8-48dc-be4e-76efba9831d1",
		"name": "Eduos School & SMS Platform REST API",
		"description": "Comprehensive multi-tenant REST API collection for school operations, messages, payments, gradebooks, and support ticketing.",
		"schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
	},
	"item": [
		{
			"name": "Authentication",
			"item": [
				{
					"name": "User Login",
					"event": [
						{
							"listen": "test",
							"script": {
								"exec": [
									"const response = pm.response.json();",
									"if (response.success && response.data.token) {",
									"    pm.environment.set(\"token\", response.data.token);",
									"}"
								],
								"type": "text/javascript"
							}
						}
					],
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"login\": \"admin\",\n    \"password\": \"admin\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/auth/login",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"auth",
								"login"
							]
						},
						"description": "Log in with multi-field credentials (email, username, or phone). Automatically sets the bearer token variable upon successful authentication."
					},
					"response": []
				},
				{
					"name": "Get Authenticated User",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/auth/me",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"auth",
								"me"
							]
						},
						"description": "Fetch the authenticated user's profile and dynamic relationships (student_record or staff details)."
					},
					"response": []
				},
				{
					"name": "User Logout",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/auth/logout",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"auth",
								"logout"
							]
						},
						"description": "Revoke the current authentication token."
					},
					"response": []
				}
			]
		},
		{
			"name": "Students",
			"item": [
				{
					"name": "List Students",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/students?class_id={{class_id}}",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"students"
							],
							"query": [
								{
									"key": "class_id",
									"value": "{{class_id}}",
									"description": "Hashed class ID"
								}
							]
						},
						"description": "Retrieves the list of active students. Can be filtered by class."
					},
					"response": []
				},
				{
					"name": "Student Profile Detail",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/students/{{student_record_id}}",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"students",
								"{{student_record_id}}"
							]
						},
						"description": "Returns full academic and user record details of the specified student."
					},
					"response": []
				}
			]
		},
		{
			"name": "Messenger",
			"item": [
				{
					"name": "Fetch Threads",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/messages",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"messages"
							]
						},
						"description": "Retrieves the logged-in user's active chats with unread messages count."
					},
					"response": []
				},
				{
					"name": "Send Reply",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"message\": \"Hello team, checking in on the system upgrades.\"\n}"
						},
						"url": {
							"raw": "{{base_url}}/messages/{{thread_id}}/reply",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"messages",
								"{{thread_id}}",
								"reply"
							]
						},
						"description": "Sends a message inside a thread."
					},
					"response": []
				}
			]
		},
		{
			"name": "Billing & Payments",
			"item": [
				{
					"name": "Student Invoices",
					"request": {
						"method": "GET",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							}
						],
						"url": {
							"raw": "{{base_url}}/payments/invoice/{{student_user_id}}",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"payments",
								"invoice",
								"{{student_user_id}}"
							]
						},
						"description": "Returns clear and uncleared fee structures and bills for a student."
					},
					"response": []
				},
				{
					"name": "Pay Now",
					"request": {
						"method": "POST",
						"header": [
							{
								"key": "Accept",
								"value": "application/json",
								"type": "text"
							},
							{
								"key": "Content-Type",
								"value": "application/json",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\n    \"amt_paid\": 15000\n}"
						},
						"url": {
							"raw": "{{base_url}}/payments/pay/{{payment_record_id}}",
							"host": [
								"{{base_url}}"
							],
							"path": [
								"payments",
								"pay",
								"{{payment_record_id}}"
							]
						},
						"description": "Submits a fee payment. Automatically generates a transaction receipt and triggers parent notification channels."
					},
					"response": []
				}
			]
		}
	],
	"auth": {
		"type": "bearer",
		"bearer": [
			{
				"key": "token",
				"value": "{{token}}",
				"type": "string"
			}
		]
	},
	"event": [
		{
			"listen": "prerequest",
			"script": {
				"type": "text/javascript",
				"exec": [
					""
				]
			}
		},
		{
			"listen": "test",
			"script": {
				"type": "text/javascript",
				"exec": [
					""
				]
			}
		}
	]
}
```

---

## 2. Live Production Support Configuration

### A. CORS Configuration (`config/cors.php`)
When launching mobile or web apps, ensure browser-based clients do not run into Cross-Origin Resource Sharing (CORS) blocks. Define allowed origins in `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000', // React local dev
        'https://yourdomain.com', // Live Web App
        'https://admin.yourdomain.com'
    ],
    'allowed_origins_patterns' => [
        'https://*.yourdomain.com' // Wildcard matching for schools subdomains
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### B. Live Server Nginx Config (Subdomains & SSL Setup)
For Stancl Tenancy to resolve school subdomains dynamically (e.g., `school1.yoursmsplatform.com`), configure wildcard DNS pointing to your server, and set up your Nginx virtual host as follows:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name .yoursmsplatform.com; # Wildcard matching
    root /var/www/Eduos-Backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
> Generate dynamic SSL certificates using Certbot:
> `sudo certbot --nginx -d yoursmsplatform.com -d *.yoursmsplatform.com --preferred-challenges dns`

### C. Secure storage on Mobile Clients (Production Security)

#### Flutter (using `flutter_secure_storage`)
Do not store Sanctum plain text tokens in standard `SharedPreferences` (Android) or `NSUserDefaults` (iOS), as they are easily read on rooted/jailbroken devices. Always use secure storage hardware bindings:

```yaml
dependencies:
  flutter_secure_storage: ^9.0.0
```

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenManager {
  static const _storage = FlutterSecureStorage();
  static const _keyToken = 'auth_token';

  static Future<void> saveToken(String token) async {
    await _storage.write(key: _keyToken, value: token);
  }

  static Future<String?> getToken() async {
    return await _storage.read(key: _keyToken);
  }

  static Future<void> deleteToken() async {
    await _storage.delete(key: _keyToken);
  }
}
```

#### React Native (using `react-native-keychain`)
Similarly, in React Native, avoid standard `AsyncStorage` for passwords or API tokens:

```bash
npm install react-native-keychain
```

```javascript
import * as Keychain from 'react-native-keychain';

// Store credentials
await Keychain.setGenericPassword('auth_token', token);

// Retrieve credentials
try {
  const credentials = await Keychain.getGenericPassword();
  if (credentials) {
    const token = credentials.password;
    console.log('Secure Token retrieved:', token);
  }
} catch (error) {
  console.log("Keychain access failed", error);
}
```

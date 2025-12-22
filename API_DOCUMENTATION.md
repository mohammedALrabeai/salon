# 🚀 API Documentation

## نظام إدارة الصالونات متعدد الفروع

### REST API Complete Reference

**الإصدار:** v1.0  
**Base URL:** `https://api.salon-system.com/v1`  
**التاريخ:** ديسمبر 2025  
**الحالة:** وثيقة نهائية - جاهزة للتطوير

---

## 📑 جدول المحتويات

1. [نظرة عامة](#-نظرة-عامة)
2. [المصادقة](#-المصادقة-authentication)
3. [أكواد الحالة](#-أكواد-الحالة-status-codes)
4. [تنسيق الاستجابة](#-تنسيق-الاستجابة-response-format)
5. [معالجة الأخطاء](#-معالجة-الأخطاء-error-handling)
6. [APIs - المستخدمين](#-users-apis)
7. [APIs - الفروع](#-branches-apis)
8. [APIs - الموظفين](#-employees-apis)
9. [APIs - الإدخالات اليومية](#-daily-entries-apis)
10. [APIs - إغلاق اليوم](#-day-closures-apis)
11. [APIs - دفتر الحسابات](#-ledger-apis)
12. [APIs - طلبات السلف](#-advance-requests-apis)
13. [APIs - الوثائق](#-documents-apis)
14. [APIs - الإشعارات](#-notifications-apis)
15. [APIs - التقارير](#-reports-apis)
16. [APIs - التحليلات](#-analytics-apis)
17. [Webhooks](#-webhooks)
18. [Rate Limiting](#-rate-limiting)
19. [أمثلة عملية](#-أمثلة-عملية)

---

## 🌐 نظرة عامة

### معلومات الـ API

```yaml
Protocol: HTTPS
Format: JSON
Encoding: UTF-8
Timezone: Asia/Riyadh (UTC+3)
Language: Arabic (ar) / English (en)
```

### Headers المطلوبة

```http
Content-Type: application/json; charset=utf-8
Accept: application/json
Accept-Language: ar
Authorization: Bearer {access_token}
X-API-Key: {api_key}
X-Request-ID: {unique_request_id}
```

### Base URLs

```
Production:  https://api.salon-system.com/v1
Staging:     https://staging-api.salon-system.com/v1
Development: https://dev-api.salon-system.com/v1
Local:       http://localhost:3000/api/v1
```

---

## 🔐 المصادقة (Authentication)

### 1. تسجيل الدخول (Login)

**Endpoint:** `POST /auth/login`

**Request Body:**

```json
{
    "phone": "0500000000",
    "password": "SecurePassword123!",
    "device_info": {
        "device_id": "uuid-device-id",
        "device_name": "iPhone 14 Pro",
        "os": "iOS 17.0",
        "app_version": "1.0.0"
    }
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": "uuid-user-id",
            "name": "أحمد محمد",
            "phone": "0500000000",
            "email": "ahmed@example.com",
            "role": "barber",
            "branch_id": "uuid-branch-id",
            "branch_name": "الفرع الرئيسي",
            "avatar_url": "https://cdn.salon.com/avatars/user.jpg",
            "status": "active",
            "settings": {
                "language": "ar",
                "notifications_enabled": true
            }
        },
        "tokens": {
            "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "token_type": "Bearer",
            "expires_in": 3600,
            "expires_at": "2025-12-22T12:00:00Z"
        },
        "permissions": [
            "daily_entries.create",
            "daily_entries.read_own",
            "advance_requests.create",
            "documents.read_own"
        ]
    },
    "timestamp": "2025-12-22T11:00:00Z"
}
```

**Errors:**

```json
// 401 Unauthorized
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "رقم الجوال أو كلمة المرور غير صحيحة",
    "field": "credentials"
  },
  "timestamp": "2025-12-22T11:00:00Z"
}

// 403 Forbidden
{
  "success": false,
  "error": {
    "code": "ACCOUNT_INACTIVE",
    "message": "حسابك غير نشط. يرجى التواصل مع الإدارة",
    "details": {
      "status": "inactive",
      "suspended_at": "2025-12-20T10:00:00Z"
    }
  },
  "timestamp": "2025-12-22T11:00:00Z"
}
```

---

### 2. تحديث Token

**Endpoint:** `POST /auth/refresh`

**Request Body:**

```json
{
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "expires_at": "2025-12-22T13:00:00Z"
    }
}
```

---

### 3. تسجيل الخروج

**Endpoint:** `POST /auth/logout`

**Headers:**

```http
Authorization: Bearer {access_token}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تسجيل الخروج بنجاح"
}
```

---

### 4. المستخدم الحالي

**Endpoint:** `GET /auth/me`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-user-id",
        "name": "أحمد محمد",
        "phone": "0500000000",
        "email": "ahmed@example.com",
        "role": "barber",
        "branch": {
            "id": "uuid-branch-id",
            "name": "الفرع الرئيسي",
            "code": "MAIN"
        },
        "avatar_url": "https://cdn.salon.com/avatars/user.jpg",
        "status": "active",
        "last_login_at": "2025-12-22T11:00:00Z",
        "created_at": "2025-01-01T00:00:00Z"
    }
}
```

---

## 📊 أكواد الحالة (Status Codes)

| Code    | الحالة                | الوصف                     |
| ------- | --------------------- | ------------------------- |
| **200** | OK                    | الطلب نجح                 |
| **201** | Created               | تم الإنشاء بنجاح          |
| **204** | No Content            | نجح بدون محتوى            |
| **400** | Bad Request           | بيانات غير صحيحة          |
| **401** | Unauthorized          | غير مصرح                  |
| **403** | Forbidden             | ممنوع                     |
| **404** | Not Found             | غير موجود                 |
| **409** | Conflict              | تعارض في البيانات         |
| **422** | Unprocessable Entity  | بيانات غير قابلة للمعالجة |
| **429** | Too Many Requests     | طلبات كثيرة جداً          |
| **500** | Internal Server Error | خطأ في الخادم             |
| **503** | Service Unavailable   | الخدمة غير متاحة          |

---

## 📦 تنسيق الاستجابة (Response Format)

### استجابة ناجحة

```json
{
    "success": true,
    "message": "العملية تمت بنجاح",
    "data": {
        // البيانات المطلوبة
    },
    "meta": {
        "timestamp": "2025-12-22T11:00:00Z",
        "request_id": "req_abc123xyz",
        "version": "1.0.0"
    }
}
```

### استجابة بقائمة (Pagination)

```json
{
    "success": true,
    "data": [
        {
            /* item 1 */
        },
        {
            /* item 2 */
        }
    ],
    "pagination": {
        "total": 100,
        "count": 20,
        "per_page": 20,
        "current_page": 1,
        "total_pages": 5,
        "links": {
            "first": "/api/v1/resource?page=1",
            "last": "/api/v1/resource?page=5",
            "prev": null,
            "next": "/api/v1/resource?page=2"
        }
    },
    "meta": {
        "timestamp": "2025-12-22T11:00:00Z"
    }
}
```

### استجابة خطأ

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "رسالة الخطأ بالعربية",
        "field": "field_name",
        "details": {
            // تفاصيل إضافية
        }
    },
    "meta": {
        "timestamp": "2025-12-22T11:00:00Z",
        "request_id": "req_abc123xyz"
    }
}
```

---

## ❌ معالجة الأخطاء (Error Handling)

### أكواد الأخطاء

| Code                   | الوصف              |
| ---------------------- | ------------------ |
| `INVALID_CREDENTIALS`  | بيانات دخول خاطئة  |
| `ACCOUNT_INACTIVE`     | الحساب غير نشط     |
| `ACCOUNT_LOCKED`       | الحساب مقفل        |
| `TOKEN_EXPIRED`        | انتهت صلاحية الرمز |
| `TOKEN_INVALID`        | رمز غير صحيح       |
| `PERMISSION_DENIED`    | لا يوجد صلاحية     |
| `RESOURCE_NOT_FOUND`   | المورد غير موجود   |
| `VALIDATION_ERROR`     | خطأ في التحقق      |
| `DUPLICATE_ENTRY`      | قيمة مكررة         |
| `DAY_LOCKED`           | اليوم مغلق         |
| `INSUFFICIENT_BALANCE` | رصيد غير كافٍ      |
| `LIMIT_EXCEEDED`       | تجاوز الحد المسموح |

### أمثلة الأخطاء

```json
// Validation Error (422)
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "البيانات المدخلة غير صحيحة",
    "validation_errors": {
      "phone": ["رقم الجوال مطلوب", "رقم الجوال غير صحيح"],
      "amount": ["المبلغ يجب أن يكون أكبر من 0"]
    }
  }
}

// Permission Denied (403)
{
  "success": false,
  "error": {
    "code": "PERMISSION_DENIED",
    "message": "ليس لديك صلاحية لتنفيذ هذا الإجراء",
    "required_permission": "daily_entries.delete",
    "user_role": "barber"
  }
}
```

---

## 👥 Users APIs

### 1. قائمة المستخدمين

**Endpoint:** `GET /users`

**Query Parameters:**

```
?page=1
&per_page=20
&role=barber
&status=active
&branch_id=uuid-branch-id
&search=أحمد
&sort_by=created_at
&sort_order=desc
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-1",
            "name": "أحمد محمد",
            "phone": "0500000000",
            "email": "ahmed@example.com",
            "role": "barber",
            "branch": {
                "id": "uuid-branch-id",
                "name": "الفرع الرئيسي"
            },
            "status": "active",
            "last_login_at": "2025-12-22T11:00:00Z",
            "created_at": "2025-01-01T00:00:00Z"
        }
    ],
    "pagination": {
        "total": 50,
        "current_page": 1,
        "per_page": 20,
        "total_pages": 3
    }
}
```

---

### 2. إنشاء مستخدم

**Endpoint:** `POST /users`

**Permissions:** `users.create`

**Request Body:**

```json
{
    "name": "محمد أحمد",
    "phone": "0501234567",
    "email": "mohammed@example.com",
    "password": "SecurePassword123!",
    "role": "barber",
    "branch_id": "uuid-branch-id",
    "commission_rate": 50.0,
    "status": "active"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إنشاء المستخدم بنجاح",
    "data": {
        "id": "uuid-new-user",
        "name": "محمد أحمد",
        "phone": "0501234567",
        "role": "barber",
        "status": "active",
        "created_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 3. تفاصيل مستخدم

**Endpoint:** `GET /users/{user_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-user-id",
        "name": "أحمد محمد",
        "phone": "0500000000",
        "email": "ahmed@example.com",
        "role": "barber",
        "branch": {
            "id": "uuid-branch-id",
            "name": "الفرع الرئيسي",
            "code": "MAIN",
            "city": "الرياض"
        },
        "avatar_url": "https://cdn.salon.com/avatars/user.jpg",
        "status": "active",
        "settings": {
            "language": "ar",
            "notifications_enabled": true,
            "theme": "light"
        },
        "stats": {
            "total_entries": 150,
            "total_sales": 45000.0,
            "total_commission": 22500.0
        },
        "created_at": "2025-01-01T00:00:00Z",
        "updated_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 4. تحديث مستخدم

**Endpoint:** `PUT /users/{user_id}`

**Request Body:**

```json
{
    "name": "أحمد محمد المحدث",
    "email": "ahmed.new@example.com",
    "branch_id": "uuid-new-branch-id",
    "status": "active"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تحديث المستخدم بنجاح",
    "data": {
        "id": "uuid-user-id",
        "name": "أحمد محمد المحدث",
        "email": "ahmed.new@example.com",
        "updated_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 5. حذف مستخدم (Soft Delete)

**Endpoint:** `DELETE /users/{user_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم حذف المستخدم بنجاح"
}
```

---

### 6. تغيير كلمة المرور

**Endpoint:** `POST /users/{user_id}/change-password`

**Request Body:**

```json
{
    "current_password": "OldPassword123!",
    "new_password": "NewSecurePassword123!",
    "new_password_confirmation": "NewSecurePassword123!"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تغيير كلمة المرور بنجاح"
}
```

---

## 🏢 Branches APIs

### 1. قائمة الفروع

**Endpoint:** `GET /branches`

**Query Parameters:**

```
?status=active
&city=الرياض
&manager_id=uuid
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-branch-1",
            "name": "الفرع الرئيسي",
            "code": "MAIN",
            "city": "الرياض",
            "address": "شارع الملك فهد",
            "phone": "0112345678",
            "manager": {
                "id": "uuid-manager",
                "name": "خالد أحمد"
            },
            "status": "active",
            "employees_count": 12,
            "stats": {
                "today_sales": 5000.0,
                "month_sales": 150000.0
            },
            "created_at": "2025-01-01T00:00:00Z"
        }
    ]
}
```

---

### 2. إنشاء فرع

**Endpoint:** `POST /branches`

**Request Body:**

```json
{
    "name": "فرع الخبر",
    "code": "KBR",
    "city": "الخبر",
    "region": "المنطقة الشرقية",
    "address": "الكورنيش الشمالي",
    "phone": "0133334444",
    "manager_id": "uuid-manager",
    "opening_time": "09:00",
    "closing_time": "23:00",
    "working_days": [
        "sunday",
        "monday",
        "tuesday",
        "wednesday",
        "thursday",
        "friday",
        "saturday"
    ]
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إنشاء الفرع بنجاح",
    "data": {
        "id": "uuid-new-branch",
        "name": "فرع الخبر",
        "code": "KBR",
        "created_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 3. تفاصيل فرع

**Endpoint:** `GET /branches/{branch_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-branch-id",
        "name": "الفرع الرئيسي",
        "code": "MAIN",
        "address": "شارع الملك فهد، الرياض",
        "city": "الرياض",
        "region": "منطقة الرياض",
        "phone": "0112345678",
        "email": "main@salon.com",
        "manager": {
            "id": "uuid-manager",
            "name": "خالد أحمد",
            "phone": "0501111111"
        },
        "status": "active",
        "opening_time": "09:00",
        "closing_time": "23:00",
        "working_days": [
            "sunday",
            "monday",
            "tuesday",
            "wednesday",
            "thursday",
            "friday",
            "saturday"
        ],
        "employees": [
            {
                "id": "uuid-emp-1",
                "name": "أحمد محمد",
                "role": "barber",
                "status": "active"
            }
        ],
        "stats": {
            "total_employees": 12,
            "active_employees": 10,
            "today_sales": 5000.0,
            "today_entries": 25,
            "month_sales": 150000.0,
            "month_entries": 750
        },
        "created_at": "2025-01-01T00:00:00Z",
        "updated_at": "2025-12-22T11:00:00Z"
    }
}
```

---

## 👨‍💼 Employees APIs

### 1. قائمة الموظفين

**Endpoint:** `GET /employees`

**Query Parameters:**

```
?branch_id=uuid
&role=barber
&status=active
&search=أحمد
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-emp-1",
            "name": "أحمد محمد",
            "phone": "0500000000",
            "role": "barber",
            "branch": {
                "id": "uuid-branch",
                "name": "الفرع الرئيسي"
            },
            "commission_rate": 50.0,
            "hire_date": "2025-01-01",
            "status": "active",
            "stats": {
                "total_sales": 45000.0,
                "total_commission": 22500.0,
                "total_entries": 150
            }
        }
    ]
}
```

---

### 2. إنشاء موظف

**Endpoint:** `POST /employees`

**Request Body:**

```json
{
    "branch_id": "uuid-branch",
    "name": "عبدالله سعيد",
    "phone": "0509999999",
    "email": "abdullah@example.com",
    "national_id": "1234567890",
    "role": "barber",
    "hire_date": "2025-12-22",
    "commission_rate": 50.0,
    "commission_type": "percentage",
    "base_salary": 3000.0
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إنشاء الموظف بنجاح",
    "data": {
        "id": "uuid-new-emp",
        "name": "عبدالله سعيد",
        "phone": "0509999999",
        "role": "barber",
        "created_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 3. تفاصيل موظف

**Endpoint:** `GET /employees/{employee_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-emp-id",
        "name": "أحمد محمد",
        "phone": "0500000000",
        "email": "ahmed@example.com",
        "national_id": "1234567890",
        "role": "barber",
        "branch": {
            "id": "uuid-branch",
            "name": "الفرع الرئيسي",
            "code": "MAIN"
        },
        "hire_date": "2025-01-01",
        "commission_rate": 50.0,
        "commission_type": "percentage",
        "base_salary": 3000.0,
        "status": "active",
        "avatar_url": "https://cdn.salon.com/avatars/emp.jpg",
        "stats": {
            "total_sales": 45000.0,
            "total_commission": 22500.0,
            "total_bonus": 1500.0,
            "total_entries": 150,
            "avg_daily_sales": 300.0,
            "ledger_balance": -500.0
        },
        "documents_count": 5,
        "documents_expiring_soon": 2,
        "created_at": "2025-01-01T00:00:00Z"
    }
}
```

---

## 💰 Daily Entries APIs

### 1. قائمة الإدخالات اليومية

**Endpoint:** `GET /daily-entries`

**Query Parameters:**

```
?employee_id=uuid
&branch_id=uuid
&date_from=2025-12-01
&date_to=2025-12-31
&is_locked=false
&page=1
&per_page=20
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-entry-1",
            "date": "2025-12-22",
            "employee": {
                "id": "uuid-emp",
                "name": "أحمد محمد"
            },
            "branch": {
                "id": "uuid-branch",
                "name": "الفرع الرئيسي"
            },
            "sales": 1500.0,
            "cash": 500.0,
            "expense": 100.0,
            "net": 900.0,
            "commission": 750.0,
            "commission_rate": 50.0,
            "bonus": 50.0,
            "note": "يوم جيد",
            "transactions_count": 8,
            "is_locked": false,
            "source": "mobile",
            "created_at": "2025-12-22T20:00:00Z"
        }
    ],
    "pagination": {
        "total": 150,
        "current_page": 1,
        "per_page": 20,
        "total_pages": 8
    },
    "summary": {
        "total_sales": 45000.0,
        "total_cash": 15000.0,
        "total_expense": 3000.0,
        "total_net": 27000.0,
        "total_commission": 22500.0,
        "total_bonus": 1500.0,
        "entries_count": 150
    }
}
```

---

### 2. إنشاء إدخال يومي

**Endpoint:** `POST /daily-entries`

**Permissions:** `daily_entries.create`

**Request Body:**

```json
{
    "employee_id": "uuid-emp",
    "branch_id": "uuid-branch",
    "date": "2025-12-22",
    "sales": 1500.0,
    "cash": 500.0,
    "expense": 100.0,
    "commission_rate": 50.0,
    "bonus": 50.0,
    "bonus_reason": "أداء ممتاز",
    "note": "يوم جيد",
    "transactions_count": 8
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إنشاء الإدخال بنجاح",
    "data": {
        "id": "uuid-new-entry",
        "date": "2025-12-22",
        "sales": 1500.0,
        "cash": 500.0,
        "expense": 100.0,
        "net": 900.0,
        "commission": 750.0,
        "bonus": 50.0,
        "total_earnings": 800.0,
        "created_at": "2025-12-22T20:00:00Z"
    }
}
```

**Errors:**

```json
// Day Locked (409 Conflict)
{
  "success": false,
  "error": {
    "code": "DAY_LOCKED",
    "message": "هذا اليوم مغلق ولا يمكن إضافة إدخالات جديدة",
    "details": {
      "date": "2025-12-22",
      "locked_at": "2025-12-23T01:00:00Z",
      "locked_by": "uuid-manager"
    }
  }
}

// Duplicate Entry (409 Conflict)
{
  "success": false,
  "error": {
    "code": "DUPLICATE_ENTRY",
    "message": "يوجد إدخال مسجل لهذا الموظف في هذا التاريخ",
    "details": {
      "existing_entry_id": "uuid-existing",
      "date": "2025-12-22",
      "employee_id": "uuid-emp"
    }
  }
}
```

---

### 3. تفاصيل إدخال

**Endpoint:** `GET /daily-entries/{entry_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-entry",
        "date": "2025-12-22",
        "employee": {
            "id": "uuid-emp",
            "name": "أحمد محمد",
            "phone": "0500000000",
            "commission_rate": 50.0
        },
        "branch": {
            "id": "uuid-branch",
            "name": "الفرع الرئيسي",
            "code": "MAIN"
        },
        "sales": 1500.0,
        "cash": 500.0,
        "expense": 100.0,
        "net": 900.0,
        "commission": 750.0,
        "commission_rate": 50.0,
        "bonus": 50.0,
        "bonus_reason": "أداء ممتاز",
        "total_earnings": 800.0,
        "note": "يوم جيد",
        "transactions_count": 8,
        "is_locked": false,
        "source": "mobile",
        "created_by": {
            "id": "uuid-user",
            "name": "أحمد محمد"
        },
        "created_at": "2025-12-22T20:00:00Z",
        "updated_at": "2025-12-22T20:30:00Z"
    }
}
```

---

### 4. تحديث إدخال يومي

**Endpoint:** `PUT /daily-entries/{entry_id}`

**Permissions:** `daily_entries.update` or `daily_entries.update_own`

**Request Body:**

```json
{
    "sales": 1600.0,
    "cash": 550.0,
    "expense": 120.0,
    "bonus": 100.0,
    "note": "تحديث: يوم رائع"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تحديث الإدخال بنجاح",
    "data": {
        "id": "uuid-entry",
        "sales": 1600.0,
        "cash": 550.0,
        "expense": 120.0,
        "net": 930.0,
        "commission": 800.0,
        "bonus": 100.0,
        "total_earnings": 900.0,
        "updated_at": "2025-12-22T21:00:00Z"
    }
}
```

---

### 5. حذف إدخال

**Endpoint:** `DELETE /daily-entries/{entry_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم حذف الإدخال بنجاح"
}
```

---

### 6. إحصائيات الموظف

**Endpoint:** `GET /daily-entries/stats/employee/{employee_id}`

**Query Parameters:**

```
?date_from=2025-12-01
&date_to=2025-12-31
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "employee": {
            "id": "uuid-emp",
            "name": "أحمد محمد"
        },
        "period": {
            "from": "2025-12-01",
            "to": "2025-12-31",
            "days": 31
        },
        "totals": {
            "sales": 45000.0,
            "cash": 15000.0,
            "expense": 3000.0,
            "net": 27000.0,
            "commission": 22500.0,
            "bonus": 1500.0,
            "total_earnings": 24000.0,
            "entries": 25
        },
        "averages": {
            "daily_sales": 1800.0,
            "daily_commission": 900.0,
            "daily_bonus": 60.0
        },
        "best_day": {
            "date": "2025-12-15",
            "sales": 2500.0,
            "net": 1200.0,
            "commission": 1250.0
        },
        "worst_day": {
            "date": "2025-12-05",
            "sales": 800.0,
            "net": 300.0,
            "commission": 400.0
        },
        "working_days": 25,
        "zero_days": 6
    }
}
```

---

## 🔒 Day Closures APIs

### 1. قائمة الإغلاقات

**Endpoint:** `GET /day-closures`

**Query Parameters:**

```
?branch_id=uuid
&date_from=2025-12-01
&date_to=2025-12-31
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-closure-1",
            "date": "2025-12-21",
            "branch": {
                "id": "uuid-branch",
                "name": "الفرع الرئيسي"
            },
            "total_sales": 12000.0,
            "total_cash": 4000.0,
            "total_expense": 800.0,
            "total_net": 7200.0,
            "total_commission": 6000.0,
            "total_bonus": 500.0,
            "entries_count": 8,
            "employees_count": 8,
            "closed_by": {
                "id": "uuid-manager",
                "name": "خالد المدير"
            },
            "closed_at": "2025-12-22T01:00:00Z",
            "pdf_url": "https://cdn.salon.com/closures/2025-12-21.pdf",
            "pdf_generated_at": "2025-12-22T01:05:00Z"
        }
    ]
}
```

---

### 2. إنشاء إغلاق يومي

**Endpoint:** `POST /day-closures`

**Permissions:** `day_closures.create`

**Request Body:**

```json
{
    "branch_id": "uuid-branch",
    "date": "2025-12-22",
    "notes": "إغلاق يوم 22 ديسمبر - لا ملاحظات"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إغلاق اليوم بنجاح",
    "data": {
        "id": "uuid-new-closure",
        "date": "2025-12-22",
        "branch_id": "uuid-branch",
        "summary": {
            "total_sales": 12000.0,
            "total_cash": 4000.0,
            "total_expense": 800.0,
            "total_net": 7200.0,
            "total_commission": 6000.0,
            "total_bonus": 500.0,
            "entries_count": 8,
            "employees_count": 8
        },
        "pdf_url": "https://cdn.salon.com/closures/2025-12-22.pdf",
        "closed_at": "2025-12-23T01:00:00Z"
    }
}
```

**Errors:**

```json
// Already Closed (409)
{
  "success": false,
  "error": {
    "code": "DAY_ALREADY_CLOSED",
    "message": "هذا اليوم مغلق مسبقاً",
    "details": {
      "closure_id": "uuid-existing",
      "closed_at": "2025-12-22T01:00:00Z"
    }
  }
}

// No Entries (400)
{
  "success": false,
  "error": {
    "code": "NO_ENTRIES_TO_CLOSE",
    "message": "لا توجد إدخالات لهذا اليوم",
    "details": {
      "date": "2025-12-22",
      "branch_id": "uuid-branch"
    }
  }
}
```

---

### 3. تفاصيل إغلاق

**Endpoint:** `GET /day-closures/{closure_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-closure",
        "date": "2025-12-22",
        "branch": {
            "id": "uuid-branch",
            "name": "الفرع الرئيسي",
            "code": "MAIN"
        },
        "summary": {
            "total_sales": 12000.0,
            "total_cash": 4000.0,
            "total_expense": 800.0,
            "total_net": 7200.0,
            "total_commission": 6000.0,
            "total_bonus": 500.0,
            "entries_count": 8,
            "employees_count": 8
        },
        "entries": [
            {
                "employee_name": "أحمد محمد",
                "sales": 1500.0,
                "commission": 750.0,
                "bonus": 50.0
            }
        ],
        "closed_by": {
            "id": "uuid-manager",
            "name": "خالد المدير"
        },
        "closed_at": "2025-12-23T01:00:00Z",
        "pdf_url": "https://cdn.salon.com/closures/2025-12-22.pdf",
        "pdf_generated_at": "2025-12-23T01:05:00Z",
        "notes": "إغلاق يوم 22 ديسمبر"
    }
}
```

---

### 4. تحميل PDF

**Endpoint:** `GET /day-closures/{closure_id}/pdf`

**Response:** PDF File Download

---

## 📒 Ledger APIs

### 1. قائمة القيود

**Endpoint:** `GET /ledger-entries`

**Query Parameters:**

```
?party_type=employee
&party_id=uuid
&type=debit
&date_from=2025-12-01
&date_to=2025-12-31
&category=salary
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-ledger-1",
            "date": "2025-12-22",
            "party_type": "employee",
            "party": {
                "id": "uuid-emp",
                "name": "أحمد محمد"
            },
            "type": "debit",
            "amount": 500.0,
            "description": "سلفة شهر ديسمبر",
            "category": "advance",
            "source": "advance_request",
            "reference_id": "uuid-advance",
            "payment_method": "cash",
            "status": "confirmed",
            "created_by": {
                "id": "uuid-manager",
                "name": "خالد المدير"
            },
            "created_at": "2025-12-22T10:00:00Z"
        }
    ],
    "balance": {
        "total_debit": 2000.0,
        "total_credit": 1500.0,
        "balance": -500.0,
        "balance_label": "عليه 500.00 ريال"
    }
}
```

---

### 2. إنشاء قيد يدوي

**Endpoint:** `POST /ledger-entries`

**Request Body:**

```json
{
    "party_type": "employee",
    "party_id": "uuid-emp",
    "date": "2025-12-22",
    "type": "credit",
    "amount": 1000.0,
    "description": "دفعة راتب شهر ديسمبر",
    "category": "salary",
    "payment_method": "bank_transfer"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إضافة القيد بنجاح",
    "data": {
        "id": "uuid-new-ledger",
        "date": "2025-12-22",
        "type": "credit",
        "amount": 1000.0,
        "new_balance": 500.0,
        "created_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 3. رصيد الحساب

**Endpoint:** `GET /ledger-entries/balance/{party_type}/{party_id}`

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "party_type": "employee",
        "party": {
            "id": "uuid-emp",
            "name": "أحمد محمد"
        },
        "balance": -500.0,
        "balance_label": "عليه 500.00 ريال",
        "total_debit": 2000.0,
        "total_credit": 1500.0,
        "entries_count": 12,
        "last_entry_date": "2025-12-22"
    }
}
```

---

## 💸 Advance Requests APIs

### 1. قائمة طلبات السلف

**Endpoint:** `GET /advance-requests`

**Query Parameters:**

```
?employee_id=uuid
&branch_id=uuid
&status=pending
&date_from=2025-12-01
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-request-1",
            "employee": {
                "id": "uuid-emp",
                "name": "أحمد محمد",
                "phone": "0500000000"
            },
            "branch": {
                "id": "uuid-branch",
                "name": "الفرع الرئيسي"
            },
            "amount": 500.0,
            "reason": "ظروف طارئة",
            "status": "pending",
            "requested_at": "2025-12-22T10:00:00Z",
            "attachment_url": "https://cdn.salon.com/attachments/request.jpg"
        }
    ]
}
```

---

### 2. إنشاء طلب سلفة

**Endpoint:** `POST /advance-requests`

**Request Body:**

```json
{
    "amount": 500.0,
    "reason": "ظروف طارئة",
    "attachment": "base64_encoded_image"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم تقديم الطلب بنجاح",
    "data": {
        "id": "uuid-new-request",
        "amount": 500.0,
        "status": "pending",
        "requested_at": "2025-12-22T10:00:00Z"
    }
}
```

---

### 3. الموافقة على طلب

**Endpoint:** `POST /advance-requests/{request_id}/approve`

**Permissions:** `advance_requests.approve`

**Request Body:**

```json
{
    "decision_notes": "تمت الموافقة",
    "payment_date": "2025-12-22",
    "payment_method": "cash"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تمت الموافقة على الطلب",
    "data": {
        "id": "uuid-request",
        "status": "approved",
        "processed_at": "2025-12-22T11:00:00Z",
        "ledger_entry_id": "uuid-ledger"
    }
}
```

---

### 4. رفض طلب

**Endpoint:** `POST /advance-requests/{request_id}/reject`

**Request Body:**

```json
{
    "rejection_reason": "لا يمكن الموافقة حالياً"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم رفض الطلب",
    "data": {
        "id": "uuid-request",
        "status": "rejected",
        "processed_at": "2025-12-22T11:00:00Z"
    }
}
```

---

## 📄 Documents APIs

### 1. قائمة الوثائق

**Endpoint:** `GET /documents`

**Query Parameters:**

```
?owner_type=employee
&owner_id=uuid
&type=إقامة
&status=urgent
&expiring_soon=true
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-doc-1",
            "owner_type": "employee",
            "owner": {
                "id": "uuid-emp",
                "name": "أحمد محمد"
            },
            "type": "إقامة",
            "number": "1234567890",
            "title": "إقامة - أحمد محمد",
            "issue_date": "2024-01-01",
            "expiry_date": "2026-01-01",
            "status": "safe",
            "days_remaining": 375,
            "files_count": 2,
            "created_at": "2025-01-01T00:00:00Z"
        }
    ]
}
```

---

### 2. إنشاء وثيقة

**Endpoint:** `POST /documents`

**Request Body (multipart/form-data):**

```json
{
    "owner_type": "employee",
    "owner_id": "uuid-emp",
    "type": "إقامة",
    "number": "1234567890",
    "title": "إقامة - أحمد محمد",
    "issue_date": "2024-01-01",
    "expiry_date": "2026-01-01",
    "notify_before_days": 30,
    "notes": "تحتاج تجديد في يناير 2026",
    "files": ["file1.pdf", "file2.jpg"]
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم إضافة الوثيقة بنجاح",
    "data": {
        "id": "uuid-new-doc",
        "type": "إقامة",
        "number": "1234567890",
        "expiry_date": "2026-01-01",
        "status": "safe",
        "days_remaining": 375,
        "files_count": 2
    }
}
```

---

### 3. تحديث وثيقة

**Endpoint:** `PUT /documents/{document_id}`

**Request Body:**

```json
{
    "expiry_date": "2026-06-01",
    "notify_before_days": 60,
    "notes": "تم التجديد"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تحديث الوثيقة بنجاح",
    "data": {
        "id": "uuid-doc",
        "expiry_date": "2026-06-01",
        "status": "safe",
        "days_remaining": 525
    }
}
```

---

### 4. إضافة ملف لوثيقة

**Endpoint:** `POST /documents/{document_id}/files`

**Request Body (multipart/form-data):**

```
file: [binary file]
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "تم رفع الملف بنجاح",
    "data": {
        "id": "uuid-file",
        "name": "document.pdf",
        "size": 1024000,
        "mime_type": "application/pdf",
        "file_url": "https://cdn.salon.com/documents/file.pdf",
        "uploaded_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 5. الوثائق المنتهية قريباً

**Endpoint:** `GET /documents/expiring-soon`

**Query Parameters:**

```
?days=30
&owner_type=employee
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "urgent": [
            {
                "id": "uuid-doc-1",
                "owner": { "name": "أحمد محمد" },
                "type": "إقامة",
                "expiry_date": "2025-12-30",
                "days_remaining": 8
            }
        ],
        "near": [
            {
                "id": "uuid-doc-2",
                "owner": { "name": "محمد علي" },
                "type": "جواز سفر",
                "expiry_date": "2026-01-15",
                "days_remaining": 24
            }
        ],
        "counts": {
            "urgent": 1,
            "near": 1,
            "total": 2
        }
    }
}
```

---

## 🔔 Notifications APIs

### 1. قائمة الإشعارات

**Endpoint:** `GET /notifications`

**Query Parameters:**

```
?status=pending
&type=document_expiry
&priority=urgent
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-notif-1",
            "type": "document_expiry",
            "title": "تنبيه: وثيقة قاربت على الانتهاء",
            "message": "إقامة أحمد محمد ستنتهي خلال 8 أيام",
            "priority": "urgent",
            "status": "pending",
            "data": {
                "document_id": "uuid-doc",
                "days_remaining": 8
            },
            "action_url": "/documents/uuid-doc",
            "created_at": "2025-12-22T09:00:00Z"
        }
    ],
    "unread_count": 5
}
```

---

### 2. قراءة إشعار

**Endpoint:** `POST /notifications/{notification_id}/read`

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تعليم الإشعار كمقروء",
    "data": {
        "id": "uuid-notif",
        "status": "read",
        "read_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### 3. قراءة جميع الإشعارات

**Endpoint:** `POST /notifications/read-all`

**Response (200 OK):**

```json
{
    "success": true,
    "message": "تم تعليم جميع الإشعارات كمقروءة",
    "data": {
        "count": 5
    }
}
```

---

## 📊 Reports APIs

### 1. تقرير المبيعات

**Endpoint:** `GET /reports/sales`

**Query Parameters:**

```
?date_from=2025-12-01
&date_to=2025-12-31
&branch_id=uuid
&employee_id=uuid
&group_by=day
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "period": {
            "from": "2025-12-01",
            "to": "2025-12-31",
            "days": 31
        },
        "summary": {
            "total_sales": 450000.0,
            "total_cash": 150000.0,
            "total_expense": 30000.0,
            "total_net": 270000.0,
            "total_commission": 225000.0,
            "total_bonus": 15000.0,
            "entries_count": 750,
            "avg_daily_sales": 14516.13
        },
        "chart_data": [
            {
                "date": "2025-12-01",
                "sales": 15000.0,
                "net": 9000.0,
                "entries": 25
            }
        ],
        "top_employees": [
            {
                "employee_id": "uuid-emp-1",
                "name": "أحمد محمد",
                "sales": 45000.0,
                "commission": 22500.0,
                "entries": 150
            }
        ],
        "branches_breakdown": [
            {
                "branch_id": "uuid-branch-1",
                "name": "الفرع الرئيسي",
                "sales": 300000.0,
                "percentage": 66.67
            }
        ]
    }
}
```

---

### 2. تقرير الحلاقين

**Endpoint:** `GET /reports/employees`

**Query Parameters:**

```
?date_from=2025-12-01
&date_to=2025-12-31
&branch_id=uuid
&sort_by=sales
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "employee": {
                "id": "uuid-emp-1",
                "name": "أحمد محمد",
                "role": "barber"
            },
            "stats": {
                "total_sales": 45000.0,
                "total_commission": 22500.0,
                "total_bonus": 1500.0,
                "total_earnings": 24000.0,
                "entries": 150,
                "working_days": 25,
                "avg_daily_sales": 1800.0,
                "best_day": {
                    "date": "2025-12-15",
                    "sales": 2500.0
                }
            },
            "rank": 1
        }
    ]
}
```

---

### 3. تقرير الفروع

**Endpoint:** `GET /reports/branches`

**Response (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "branch": {
                "id": "uuid-branch-1",
                "name": "الفرع الرئيسي",
                "code": "MAIN"
            },
            "stats": {
                "total_sales": 300000.0,
                "total_net": 180000.0,
                "entries": 500,
                "employees_count": 10,
                "avg_per_employee": 30000.0
            },
            "rank": 1,
            "performance": "excellent"
        }
    ]
}
```

---

### 4. تقرير دفتر الحسابات

**Endpoint:** `GET /reports/ledger`

**Query Parameters:**

```
?party_type=employee
&date_from=2025-12-01
&date_to=2025-12-31
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "accounts": [
            {
                "party": {
                    "id": "uuid-emp-1",
                    "name": "أحمد محمد"
                },
                "balance": -500.0,
                "balance_label": "عليه 500.00 ريال",
                "total_debit": 2000.0,
                "total_credit": 1500.0,
                "entries_count": 12
            }
        ],
        "summary": {
            "total_debit": 50000.0,
            "total_credit": 45000.0,
            "net_balance": -5000.0
        }
    }
}
```

---

## 📈 Analytics APIs

### 1. لوحة المعلومات

**Endpoint:** `GET /analytics/dashboard`

**Query Parameters:**

```
?period=today
&branch_id=uuid
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "period": "today",
        "date": "2025-12-22",
        "kpis": {
            "sales": {
                "value": 5000.0,
                "change": 15.5,
                "trend": "up",
                "comparison": "مقارنة بالأمس"
            },
            "net": {
                "value": 3000.0,
                "change": 10.2,
                "trend": "up"
            },
            "entries": {
                "value": 25,
                "change": 8.7,
                "trend": "up"
            },
            "active_employees": {
                "value": 8,
                "change": 0,
                "trend": "stable"
            }
        },
        "chart": {
            "sales_trend": [
                { "hour": "09:00", "sales": 500 },
                { "hour": "10:00", "sales": 750 }
            ]
        },
        "top_performers": [
            {
                "employee": "أحمد محمد",
                "sales": 800.0,
                "rank": 1
            }
        ]
    }
}
```

---

### 2. مقارنة الفترات

**Endpoint:** `GET /analytics/compare`

**Query Parameters:**

```
?period1_from=2025-11-01&period1_to=2025-11-30
&period2_from=2025-12-01&period2_to=2025-12-31
```

**Response (200 OK):**

```json
{
    "success": true,
    "data": {
        "period1": {
            "label": "نوفمبر 2025",
            "sales": 400000.0,
            "net": 240000.0,
            "entries": 700
        },
        "period2": {
            "label": "ديسمبر 2025",
            "sales": 450000.0,
            "net": 270000.0,
            "entries": 750
        },
        "comparison": {
            "sales_change": 12.5,
            "net_change": 12.5,
            "entries_change": 7.14,
            "trend": "up"
        }
    }
}
```

---

## 🔗 Webhooks

### تسجيل Webhook

**Endpoint:** `POST /webhooks`

**Request Body:**

```json
{
    "url": "https://your-app.com/webhooks/salon",
    "events": [
        "daily_entry.created",
        "day_closure.completed",
        "advance_request.submitted",
        "document.expiring"
    ],
    "secret": "your_webhook_secret"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "data": {
        "id": "uuid-webhook",
        "url": "https://your-app.com/webhooks/salon",
        "events": ["daily_entry.created", "..."],
        "status": "active",
        "created_at": "2025-12-22T11:00:00Z"
    }
}
```

---

### Webhook Payload Example

```json
{
    "event": "daily_entry.created",
    "timestamp": "2025-12-22T11:00:00Z",
    "data": {
        "entry_id": "uuid-entry",
        "employee_id": "uuid-emp",
        "date": "2025-12-22",
        "sales": 1500.0,
        "commission": 750.0
    },
    "signature": "sha256_signature"
}
```

---

## ⏱️ Rate Limiting

### الحدود

| نوع المستخدم | الطلبات/دقيقة | الطلبات/ساعة |
| ------------ | ------------- | ------------ |
| **Owner**    | 1000          | 10000        |
| **Manager**  | 500           | 5000         |
| **Barber**   | 100           | 1000         |
| **Guest**    | 10            | 50           |

### Headers

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640174400
```

### استجابة عند التجاوز (429)

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "لقد تجاوزت الحد المسموح من الطلبات",
        "retry_after": 60
    }
}
```

---

## 💡 أمثلة عملية

### مثال 1: تطبيق الحلاق - تسجيل دخول وإضافة إدخال

```javascript
// 1. Login
const loginResponse = await fetch(
    "https://api.salon-system.com/v1/auth/login",
    {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            phone: "0500000000",
            password: "SecurePassword123!",
        }),
    }
);

const {
    data: { tokens },
} = await loginResponse.json();
const accessToken = tokens.access_token;

// 2. Create Daily Entry
const entryResponse = await fetch(
    "https://api.salon-system.com/v1/daily-entries",
    {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${accessToken}`,
        },
        body: JSON.stringify({
            employee_id: "uuid-emp",
            branch_id: "uuid-branch",
            date: "2025-12-22",
            sales: 1500.0,
            cash: 500.0,
            expense: 100.0,
            commission_rate: 50.0,
            bonus: 50.0,
            note: "يوم جيد",
        }),
    }
);

const entry = await entryResponse.json();
console.log("Entry created:", entry);
```

---

### مثال 2: لوحة التحكم - إحصائيات اليوم

```javascript
const response = await fetch(
    "https://api.salon-system.com/v1/analytics/dashboard?period=today",
    {
        headers: {
            Authorization: `Bearer ${accessToken}`,
        },
    }
);

const { data } = await response.json();
console.log("Today Sales:", data.kpis.sales.value);
```

---

### مثال 3: إغلاق اليوم

```javascript
const closureResponse = await fetch(
    "https://api.salon-system.com/v1/day-closures",
    {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${accessToken}`,
        },
        body: JSON.stringify({
            branch_id: "uuid-branch",
            date: "2025-12-22",
            notes: "إغلاق يوم جيد",
        }),
    }
);

const closure = await closureResponse.json();
console.log("PDF URL:", closure.data.pdf_url);
```

---

## 📝 ملاحظات مهمة

### الأمان

-   ✅ جميع الطلبات تتطلب HTTPS
-   ✅ استخدام JWT للمصادقة
-   ✅ تشفير البيانات الحساسة
-   ✅ Rate Limiting لمنع الإساءة

### الأداء

-   ✅ Caching للبيانات المتكررة
-   ✅ Pagination لجميع القوائم
-   ✅ Compression (gzip)
-   ✅ CDN للملفات الثابتة

### التوافق

-   ✅ متوافق مع REST standards
-   ✅ يدعم JSON فقط
-   ✅ UTF-8 encoding
-   ✅ CORS enabled

---

## 🎯 الخلاصة

هذا API يوفر:

-   ✅ **78+ Endpoint** كامل
-   ✅ **مصادقة آمنة** JWT-based
-   ✅ **معالجة أخطاء احترافية**
-   ✅ **Pagination & Filtering**
-   ✅ **Rate Limiting**
-   ✅ **Webhooks** للتكامل
-   ✅ **أمثلة عملية** جاهزة

**جاهز للتطوير الفوري!** 🚀

---

**تم بحمد الله ✨**
**Version:** 1.0.0  
**Last Updated:** ديسمبر 2025

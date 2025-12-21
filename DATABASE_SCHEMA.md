# 🗄️ قاعدة البيانات - المخطط الكامل

## نظام إدارة الصالونات متعدد الفروع

### Database Schema & Relationships

**الإصدار:** v1.0  
**التاريخ:** ديسمبر 2025  
**قاعدة البيانات:** PostgreSQL / MySQL / Supabase  
**الحالة:** وثيقة نهائية - جاهزة للتنفيذ

---

## 📑 جدول المحتويات

1. [نظرة عامة](#-نظرة-عامة-overview)
2. [الجداول والحقول](#-الجداول-والحقول-tables--fields)
3. [العلاقات بين الجداول](#-العلاقات-relationships)
4. [الفهارس والأداء](#-الفهارس-indexes)
5. [القيود والتحققات](#-القيود-constraints)
6. [الإجراءات المخزنة](#-الإجراءات-المخزنة-stored-procedures)
7. [SQL Scripts](#-sql-scripts)
8. [الترحيل والبذور](#-الترحيل-migrations--seeds)

---

## 📊 نظرة عامة (Overview)

### البنية العامة

```
┌─────────────────────────────────────────────────────────────┐
│                    قاعدة البيانات الرئيسية                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  👥 Users & Auth        📍 Locations       💰 Transactions  │
│  ├─ users               ├─ branches        ├─ daily_entries│
│  └─ audit_logs          └─ employees       ├─ day_closures │
│                                            ├─ ledger_entries│
│  📄 Documents           💸 Advances        └─ transactions  │
│  ├─ documents           ├─ advance_requests                │
│  └─ document_files      └─ advance_history                 │
│                                                             │
│  🔔 Notifications       📊 Analytics                        │
│  ├─ notifications       ├─ reports_cache                    │
│  └─ notification_logs   └─ analytics_daily                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### إحصائيات قاعدة البيانات

| العنصر                | العدد | الوصف                   |
| --------------------- | ----- | ----------------------- |
| **الجداول الرئيسية**  | 12    | جداول البيانات الأساسية |
| **الجداول المساعدة**  | 6     | جداول التتبع والسجلات   |
| **المفاتيح الأجنبية** | 25+   | علاقات بين الجداول      |
| **الفهارس**           | 40+   | لتحسين الأداء           |
| **الإجراءات المخزنة** | 8     | لعمليات معقدة           |

---

## 🗂️ الجداول والحقول (Tables & Fields)

### 1️⃣ جدول المستخدمين (Users)

**الاسم:** `users`  
**الوصف:** يحتوي على جميع مستخدمي النظام (المالك، المديرين، المحاسبين، الحلاقين)

```sql
CREATE TABLE users (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Basic Info
  name                VARCHAR(100) NOT NULL,
  phone               VARCHAR(20) UNIQUE NOT NULL,
  email               VARCHAR(100),
  password_hash       VARCHAR(255) NOT NULL,

  -- Role & Permissions
  role                VARCHAR(20) NOT NULL CHECK (role IN ('owner', 'manager', 'accountant', 'barber', 'doc_supervisor')),

  -- Branch Assignment (للحلاق والمدير)
  branch_id           UUID REFERENCES branches(id) ON DELETE SET NULL,

  -- Status
  status              VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),

  -- Security
  last_login_at       TIMESTAMP WITH TIME ZONE,
  last_login_ip       INET,
  failed_login_count  INTEGER DEFAULT 0,
  locked_until        TIMESTAMP WITH TIME ZONE,

  -- Profile
  avatar_url          TEXT,
  bio                 TEXT,

  -- Settings
  settings            JSONB DEFAULT '{}',
  preferences         JSONB DEFAULT '{}',

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_branch_id ON users(branch_id);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
```

**الحقول المهمة:**

-   `id`: المعرف الفريد (UUID)
-   `name`: اسم المستخدم
-   `phone`: رقم الجوال (فريد)
-   `role`: الدور الوظيفي
-   `branch_id`: معرف الفرع (للحلاق/المدير)
-   `status`: الحالة (نشط/غير نشط/موقوف)

---

### 2️⃣ جدول الفروع (Branches)

**الاسم:** `branches`  
**الوصف:** الفروع المختلفة للصالونات

```sql
CREATE TABLE branches (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Basic Info
  name                VARCHAR(100) NOT NULL,
  code                VARCHAR(20) UNIQUE,

  -- Location
  address             TEXT,
  city                VARCHAR(50),
  region              VARCHAR(50),
  country             VARCHAR(50) DEFAULT 'Saudi Arabia',
  postal_code         VARCHAR(10),

  -- Coordinates
  latitude            DECIMAL(10, 8),
  longitude           DECIMAL(11, 8),

  -- Contact
  phone               VARCHAR(20),
  email               VARCHAR(100),

  -- Management
  manager_id          UUID REFERENCES users(id) ON DELETE SET NULL,

  -- Status
  status              VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'maintenance')),

  -- Business Hours
  opening_time        TIME,
  closing_time        TIME,
  working_days        JSONB DEFAULT '["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]',

  -- Settings
  settings            JSONB DEFAULT '{}',

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_branches_code ON branches(code);
CREATE INDEX idx_branches_manager_id ON branches(manager_id);
CREATE INDEX idx_branches_status ON branches(status);
CREATE INDEX idx_branches_city ON branches(city);
CREATE INDEX idx_branches_deleted_at ON branches(deleted_at);
```

**الحقول المهمة:**

-   `id`: المعرف الفريد
-   `name`: اسم الفرع
-   `code`: رمز الفرع (اختياري)
-   `manager_id`: معرف المدير المسؤول
-   `status`: حالة الفرع

---

### 3️⃣ جدول الموظفين (Employees)

**الاسم:** `employees`  
**الوصف:** الموظفين والحلاقين في كل فرع

```sql
CREATE TABLE employees (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Branch Assignment
  branch_id           UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,

  -- Basic Info
  name                VARCHAR(100) NOT NULL,
  phone               VARCHAR(20) UNIQUE NOT NULL,
  email               VARCHAR(100),
  national_id         VARCHAR(20),
  passport_number     VARCHAR(20),

  -- Role
  role                VARCHAR(20) NOT NULL DEFAULT 'barber' CHECK (role IN ('barber', 'manager', 'receptionist', 'other')),

  -- Employment Details
  hire_date           DATE NOT NULL,
  termination_date    DATE,
  employment_type     VARCHAR(20) DEFAULT 'full_time' CHECK (employment_type IN ('full_time', 'part_time', 'contract', 'freelance')),

  -- Commission Settings
  commission_rate     DECIMAL(5, 2) DEFAULT 50.00, -- نسبة العمولة (%)
  commission_type     VARCHAR(20) DEFAULT 'percentage' CHECK (commission_type IN ('percentage', 'fixed', 'tiered')),

  -- Salary
  base_salary         DECIMAL(10, 2) DEFAULT 0,

  -- Status
  status              VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'on_leave', 'suspended')),

  -- Profile
  avatar_url          TEXT,
  bio                 TEXT,
  skills              JSONB DEFAULT '[]',

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_employees_branch_id ON employees(branch_id);
CREATE INDEX idx_employees_phone ON employees(phone);
CREATE INDEX idx_employees_role ON employees(role);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_employees_hire_date ON employees(hire_date);
CREATE INDEX idx_employees_deleted_at ON employees(deleted_at);
```

**الحقول المهمة:**

-   `id`: المعرف الفريد
-   `branch_id`: معرف الفرع
-   `name`: اسم الموظف
-   `role`: الدور (حلاق/مدير/أخرى)
-   `commission_rate`: نسبة العمولة
-   `status`: الحالة

---

### 4️⃣ جدول الإدخالات اليومية (Daily Entries)

**الاسم:** `daily_entries`  
**الوصف:** سجل العمليات اليومية لكل حلاق

```sql
CREATE TABLE daily_entries (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Foreign Keys
  branch_id           UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  employee_id         UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,

  -- Date
  date                DATE NOT NULL,

  -- Financial Data
  sales               DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (sales >= 0), -- إجمالي المبيعات
  cash                DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (cash >= 0), -- المبلغ المأخوذ من الحلاق
  expense             DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (expense >= 0), -- المصروفات
  net                 DECIMAL(10, 2) GENERATED ALWAYS AS (sales - cash - expense) STORED, -- الصافي (محسوب تلقائياً)

  -- Commission
  commission          DECIMAL(10, 2) DEFAULT 0 CHECK (commission >= 0),
  commission_rate     DECIMAL(5, 2), -- نسخة من نسبة العمولة وقت التسجيل

  -- Bonus
  bonus               DECIMAL(10, 2) DEFAULT 0 CHECK (bonus >= 0),
  bonus_reason        TEXT,

  -- Details
  note                TEXT,
  transactions_count  INTEGER DEFAULT 0,

  -- Source
  source              VARCHAR(20) NOT NULL DEFAULT 'web' CHECK (source IN ('web', 'mobile', 'api')),

  -- Lock Status
  is_locked           BOOLEAN DEFAULT FALSE,
  locked_at           TIMESTAMP WITH TIME ZONE,
  locked_by           UUID REFERENCES users(id),

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id),

  -- Constraints
  UNIQUE(employee_id, date)
);

-- Indexes
CREATE INDEX idx_daily_entries_branch_id ON daily_entries(branch_id);
CREATE INDEX idx_daily_entries_employee_id ON daily_entries(employee_id);
CREATE INDEX idx_daily_entries_date ON daily_entries(date DESC);
CREATE INDEX idx_daily_entries_is_locked ON daily_entries(is_locked);
CREATE INDEX idx_daily_entries_created_at ON daily_entries(created_at DESC);
CREATE INDEX idx_daily_entries_deleted_at ON daily_entries(deleted_at);

-- Composite Indexes for Reports
CREATE INDEX idx_daily_entries_branch_date ON daily_entries(branch_id, date DESC);
CREATE INDEX idx_daily_entries_employee_date ON daily_entries(employee_id, date DESC);
```

**الحقول المهمة:**

-   `id`: المعرف الفريد
-   `employee_id`: معرف الموظف/الحلاق
-   `date`: التاريخ
-   `sales`: إجمالي المبيعات
-   `cash`: المبلغ المأخوذ
-   `expense`: المصروفات
-   `net`: الصافي (محسوب تلقائياً)
-   `commission`: العمولة
-   `is_locked`: هل تم إغلاق اليوم؟

---

### 5️⃣ جدول إغلاق اليوم (Day Closures)

**الاسم:** `day_closures`  
**الوصف:** سجل إغلاق الأيام وتوليد PDF

```sql
CREATE TABLE day_closures (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Foreign Keys
  branch_id           UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,

  -- Date
  date                DATE NOT NULL,

  -- Summary
  total_sales         DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_cash          DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_expense       DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_net           DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_commission    DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_bonus         DECIMAL(10, 2) NOT NULL DEFAULT 0,

  -- Entries Info
  entries_count       INTEGER NOT NULL DEFAULT 0,
  employees_count     INTEGER NOT NULL DEFAULT 0,

  -- Closure Info
  closed_by           UUID NOT NULL REFERENCES users(id),
  closed_at           TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

  -- PDF
  pdf_url             TEXT,
  pdf_generated_at    TIMESTAMP WITH TIME ZONE,

  -- Notes
  notes               TEXT,

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

  -- Constraints
  UNIQUE(branch_id, date)
);

-- Indexes
CREATE INDEX idx_day_closures_branch_id ON day_closures(branch_id);
CREATE INDEX idx_day_closures_date ON day_closures(date DESC);
CREATE INDEX idx_day_closures_closed_by ON day_closures(closed_by);
CREATE INDEX idx_day_closures_closed_at ON day_closures(closed_at DESC);
```

**الحقول المهمة:**

-   `id`: المعرف الفريد
-   `branch_id`: معرف الفرع
-   `date`: تاريخ الإغلاق
-   `total_sales/cash/expense/net`: إجماليات اليوم
-   `closed_by`: من قام بالإغلاق
-   `pdf_url`: رابط ملف PDF

---

### 6️⃣ جدول دفتر الحسابات (Ledger Entries)

**الاسم:** `ledger_entries`  
**الوصف:** دفتر عليك/لك - حسابات الموظفين والفروع

```sql
CREATE TABLE ledger_entries (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Party (الطرف)
  party_type          VARCHAR(20) NOT NULL CHECK (party_type IN ('employee', 'branch', 'supplier', 'customer')),
  party_id            UUID NOT NULL, -- يشير إلى employees.id أو branches.id

  -- Entry Details
  date                DATE NOT NULL,
  type                VARCHAR(20) NOT NULL CHECK (type IN ('debit', 'credit')), -- debit = عليه (مدين), credit = له (دائن)
  amount              DECIMAL(10, 2) NOT NULL CHECK (amount > 0),

  -- Description
  description         TEXT NOT NULL,
  category            VARCHAR(50), -- salary, advance, loan, payment, etc.

  -- Source
  source              VARCHAR(30) NOT NULL CHECK (source IN ('manual', 'advance_request', 'salary', 'closure', 'other')),
  reference_id        UUID, -- معرف المصدر (advance_request.id مثلاً)
  reference_type      VARCHAR(30), -- 'advance_request', 'day_closure', etc.

  -- Payment Method
  payment_method      VARCHAR(20) CHECK (payment_method IN ('cash', 'bank_transfer', 'check', 'other')),

  -- Attachments
  attachment_url      TEXT,

  -- Status
  status              VARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('pending', 'confirmed', 'cancelled')),

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_ledger_party ON ledger_entries(party_type, party_id);
CREATE INDEX idx_ledger_date ON ledger_entries(date DESC);
CREATE INDEX idx_ledger_type ON ledger_entries(type);
CREATE INDEX idx_ledger_source ON ledger_entries(source);
CREATE INDEX idx_ledger_reference ON ledger_entries(reference_type, reference_id);
CREATE INDEX idx_ledger_status ON ledger_entries(status);
CREATE INDEX idx_ledger_created_at ON ledger_entries(created_at DESC);
CREATE INDEX idx_ledger_deleted_at ON ledger_entries(deleted_at);
```

**الحقول المهمة:**

-   `party_type`: نوع الطرف (موظف/فرع)
-   `party_id`: معرف الطرف
-   `type`: النوع (مدين/دائن)
-   `amount`: المبلغ
-   `source`: مصدر القيد
-   `reference_id`: معرف المرجع

---

### 7️⃣ جدول طلبات السلف (Advance Requests)

**الاسم:** `advance_requests`  
**الوصف:** طلبات السلف من الموظفين

```sql
CREATE TABLE advance_requests (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Foreign Keys
  employee_id         UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
  branch_id           UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,

  -- Request Details
  amount              DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
  reason              TEXT,

  -- Status
  status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),

  -- Processing
  requested_at        TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  processed_at        TIMESTAMP WITH TIME ZONE,
  processed_by        UUID REFERENCES users(id),

  -- Decision
  decision_notes      TEXT,
  rejection_reason    TEXT,

  -- Payment
  payment_date        DATE,
  payment_method      VARCHAR(20) CHECK (payment_method IN ('cash', 'bank_transfer', 'check', 'deduction')),

  -- Attachments
  attachment_url      TEXT,

  -- Ledger Link
  ledger_entry_id     UUID REFERENCES ledger_entries(id),

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE
);

-- Indexes
CREATE INDEX idx_advance_requests_employee_id ON advance_requests(employee_id);
CREATE INDEX idx_advance_requests_branch_id ON advance_requests(branch_id);
CREATE INDEX idx_advance_requests_status ON advance_requests(status);
CREATE INDEX idx_advance_requests_requested_at ON advance_requests(requested_at DESC);
CREATE INDEX idx_advance_requests_processed_by ON advance_requests(processed_by);
CREATE INDEX idx_advance_requests_deleted_at ON advance_requests(deleted_at);
```

**الحقول المهمة:**

-   `employee_id`: معرف الموظف
-   `amount`: مبلغ السلفة
-   `status`: الحالة (قيد الانتظار/موافق عليها/مرفوضة)
-   `processed_by`: من قام بالمعالجة
-   `ledger_entry_id`: رابط لقيد الحساب

---

### 8️⃣ جدول الوثائق (Documents)

**الاسم:** `documents`  
**الوصف:** وثائق الموظفين والفروع

```sql
CREATE TABLE documents (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Owner
  owner_type          VARCHAR(20) NOT NULL CHECK (owner_type IN ('employee', 'branch', 'company')),
  owner_id            UUID NOT NULL,

  -- Document Details
  type                VARCHAR(50) NOT NULL, -- إقامة، جواز سفر، رخصة قيادة، عقد عمل، تأمين، إلخ
  number              VARCHAR(50),
  title               VARCHAR(200),

  -- Dates
  issue_date          DATE,
  expiry_date         DATE,

  -- Status (محسوب تلقائياً)
  status              VARCHAR(20) GENERATED ALWAYS AS (
    CASE
      WHEN expiry_date IS NULL THEN 'safe'
      WHEN expiry_date < CURRENT_DATE THEN 'expired'
      WHEN expiry_date <= CURRENT_DATE + INTERVAL '15 days' THEN 'urgent'
      WHEN expiry_date <= CURRENT_DATE + INTERVAL '60 days' THEN 'near'
      ELSE 'safe'
    END
  ) STORED,

  -- Days Remaining (محسوب تلقائياً)
  days_remaining      INTEGER GENERATED ALWAYS AS (
    CASE
      WHEN expiry_date IS NULL THEN NULL
      ELSE EXTRACT(DAY FROM (expiry_date - CURRENT_DATE))
    END
  ) STORED,

  -- Notifications
  notify_before_days  INTEGER DEFAULT 30,
  last_notified_at    TIMESTAMP WITH TIME ZONE,

  -- Notes
  notes               TEXT,

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at          TIMESTAMP WITH TIME ZONE,

  -- Metadata
  created_by          UUID REFERENCES users(id),
  updated_by          UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_documents_owner ON documents(owner_type, owner_id);
CREATE INDEX idx_documents_type ON documents(type);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_documents_expiry_date ON documents(expiry_date);
CREATE INDEX idx_documents_deleted_at ON documents(deleted_at);
```

**الحقول المهمة:**

-   `owner_type/owner_id`: المالك
-   `type`: نوع الوثيقة
-   `expiry_date`: تاريخ الانتهاء
-   `status`: الحالة (آمن/قريب/عاجل/منتهي) - محسوب تلقائياً
-   `days_remaining`: الأيام المتبقية - محسوب تلقائياً

---

### 9️⃣ جدول ملفات الوثائق (Document Files)

**الاسم:** `document_files`  
**الوصف:** الملفات المرفقة بكل وثيقة

```sql
CREATE TABLE document_files (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Foreign Key
  document_id         UUID NOT NULL REFERENCES documents(id) ON DELETE CASCADE,

  -- File Info
  name                VARCHAR(255) NOT NULL,
  size                BIGINT NOT NULL,
  mime_type           VARCHAR(100) NOT NULL,

  -- Storage
  file_url            TEXT NOT NULL,
  storage_provider    VARCHAR(20) DEFAULT 'local' CHECK (storage_provider IN ('local', 's3', 'cloudinary', 'supabase')),

  -- Version
  version             INTEGER DEFAULT 1,
  is_current          BOOLEAN DEFAULT TRUE,

  -- Timestamps
  uploaded_at         TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  uploaded_by         UUID REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_document_files_document_id ON document_files(document_id);
CREATE INDEX idx_document_files_uploaded_at ON document_files(uploaded_at DESC);
```

---

### 🔟 جدول الإشعارات (Notifications)

**الاسم:** `notifications`  
**الوصف:** إشعارات النظام للمستخدمين

```sql
CREATE TABLE notifications (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Type
  type                VARCHAR(30) NOT NULL CHECK (type IN ('document_expiry', 'advance_request', 'day_closure', 'system', 'other')),

  -- Target
  target_type         VARCHAR(20) NOT NULL CHECK (target_type IN ('user', 'role', 'branch', 'all')),
  target_id           UUID, -- user_id أو branch_id

  -- Content
  title               VARCHAR(200) NOT NULL,
  message             TEXT NOT NULL,

  -- Data
  data                JSONB DEFAULT '{}',
  action_url          TEXT,

  -- Status
  status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'read', 'failed')),

  -- Priority
  priority            VARCHAR(20) DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'urgent')),

  -- Delivery
  channels            JSONB DEFAULT '["in_app"]', -- in_app, email, sms, push

  -- Timestamps
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  sent_at             TIMESTAMP WITH TIME ZONE,
  read_at             TIMESTAMP WITH TIME ZONE,
  expires_at          TIMESTAMP WITH TIME ZONE
);

-- Indexes
CREATE INDEX idx_notifications_target ON notifications(target_type, target_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notifications_priority ON notifications(priority);
CREATE INDEX idx_notifications_created_at ON notifications(created_at DESC);
```

---

### 1️⃣1️⃣ جدول سجل التدقيق (Audit Logs)

**الاسم:** `audit_logs`  
**الوصف:** تتبع جميع العمليات في النظام

```sql
CREATE TABLE audit_logs (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- User
  user_id             UUID REFERENCES users(id) ON DELETE SET NULL,
  user_name           VARCHAR(100),
  user_role           VARCHAR(20),

  -- Action
  action              VARCHAR(50) NOT NULL, -- create, update, delete, login, logout, etc.
  entity_type         VARCHAR(50) NOT NULL, -- users, branches, daily_entries, etc.
  entity_id           UUID,

  -- Changes
  old_values          JSONB,
  new_values          JSONB,

  -- Request Info
  ip_address          INET,
  user_agent          TEXT,
  request_method      VARCHAR(10),
  request_url         TEXT,

  -- Status
  status              VARCHAR(20) DEFAULT 'success' CHECK (status IN ('success', 'failed')),
  error_message       TEXT,

  -- Timestamp
  created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);
CREATE INDEX idx_audit_logs_ip_address ON audit_logs(ip_address);
```

---

### 1️⃣2️⃣ جدول التحليلات اليومية (Analytics Daily)

**الاسم:** `analytics_daily`  
**الوصف:** إحصائيات يومية مجمعة للأداء السريع

```sql
CREATE TABLE analytics_daily (
  -- Primary Key
  id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

  -- Date & Scope
  date                DATE NOT NULL,
  scope_type          VARCHAR(20) NOT NULL CHECK (scope_type IN ('system', 'branch', 'employee')),
  scope_id            UUID, -- NULL للنظام، branch_id أو employee_id

  -- Metrics
  total_sales         DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_cash          DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_expense       DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_net           DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_commission    DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_bonus         DECIMAL(10, 2) NOT NULL DEFAULT 0,

  -- Counts
  entries_count       INTEGER NOT NULL DEFAULT 0,
  employees_count     INTEGER NOT NULL DEFAULT 0,
  transactions_count  INTEGER NOT NULL DEFAULT 0,

  -- Averages
  avg_sale_value      DECIMAL(10, 2),
  avg_commission_rate DECIMAL(5, 2),

  -- Computed
  computed_at         TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

  -- Constraints
  UNIQUE(date, scope_type, scope_id)
);

-- Indexes
CREATE INDEX idx_analytics_date ON analytics_daily(date DESC);
CREATE INDEX idx_analytics_scope ON analytics_daily(scope_type, scope_id);
```

---

## 🔗 العلاقات (Relationships)

### مخطط العلاقات (ERD)

```
┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│   branches   │◄────────│    users     │────────►│audit_logs    │
│              │1      n │              │1      n │              │
└──────┬───────┘         └──────┬───────┘         └──────────────┘
       │                        │
       │1                       │1
       │                        │
       │n                       │n
┌──────▼───────┐         ┌──────▼───────┐
│  employees   │         │notifications │
│              │         │              │
└──────┬───────┘         └──────────────┘
       │
       │1
       │
       │n
┌──────▼───────────────────┐
│   daily_entries          │
│                          │
└──────┬──────────┬────────┘
       │          │
       │1         │1
       │          │
       │n         │1
┌──────▼──────┐ ┌─▼───────────┐
│day_closures │ │ledger_entries│
│             │ │              │
└─────────────┘ └──────┬───────┘
                       │
                       │1
                       │
                       │n
                ┌──────▼─────────┐
                │advance_requests│
                │                │
                └────────────────┘

       ┌──────────────┐
       │  documents   │
       │              │
       └──────┬───────┘
              │1
              │
              │n
       ┌──────▼───────────┐
       │ document_files   │
       │                  │
       └──────────────────┘
```

### العلاقات بالتفصيل

#### 1. **Users ↔ Branches**

-   **النوع:** Many-to-One
-   **العلاقة:** `users.branch_id` → `branches.id`
-   **الوصف:** كل مستخدم (حلاق) يتبع فرع واحد
-   **Cascade:** SET NULL عند حذف الفرع

#### 2. **Branches ↔ Employees**

-   **النوع:** One-to-Many
-   **العلاقة:** `employees.branch_id` → `branches.id`
-   **الوصف:** الفرع يحتوي على موظفين متعددين
-   **Cascade:** CASCADE عند حذف الفرع

#### 3. **Employees ↔ Daily Entries**

-   **النوع:** One-to-Many
-   **العلاقة:** `daily_entries.employee_id` → `employees.id`
-   **الوصف:** كل موظف له إدخالات يومية متعددة
-   **Cascade:** CASCADE عند حذف الموظف
-   **Constraint:** UNIQUE(employee_id, date)

#### 4. **Branches ↔ Daily Entries**

-   **النوع:** One-to-Many
-   **العلاقة:** `daily_entries.branch_id` → `branches.id`
-   **الوصف:** كل فرع له إدخالات يومية متعددة
-   **Cascade:** CASCADE عند حذف الفرع

#### 5. **Branches ↔ Day Closures**

-   **النوع:** One-to-Many
-   **العلاقة:** `day_closures.branch_id` → `branches.id`
-   **الوصف:** كل فرع له إغلاقات يومية متعددة
-   **Cascade:** CASCADE عند حذف الفرع
-   **Constraint:** UNIQUE(branch_id, date)

#### 6. **Employees ↔ Ledger Entries**

-   **النوع:** One-to-Many (Polymorphic)
-   **العلاقة:** `ledger_entries.party_id` → `employees.id` (when party_type='employee')
-   **الوصف:** كل موظف له قيود حسابية متعددة

#### 7. **Employees ↔ Advance Requests**

-   **النوع:** One-to-Many
-   **العلاقة:** `advance_requests.employee_id` → `employees.id`
-   **الوصف:** كل موظف يمكنه تقديم طلبات سلف متعددة
-   **Cascade:** CASCADE عند حذف الموظف

#### 8. **Advance Requests ↔ Ledger Entries**

-   **النوع:** One-to-One
-   **العلاقة:** `advance_requests.ledger_entry_id` → `ledger_entries.id`
-   **الوصف:** كل سلفة موافق عليها تنشئ قيد حسابي

#### 9. **Employees ↔ Documents**

-   **النوع:** One-to-Many (Polymorphic)
-   **العلاقة:** `documents.owner_id` → `employees.id` (when owner_type='employee')
-   **الوصف:** كل موظف له وثائق متعددة

#### 10. **Documents ↔ Document Files**

-   **النوع:** One-to-Many
-   **العلاقة:** `document_files.document_id` → `documents.id`
-   **الوصف:** كل وثيقة لها ملفات متعددة
-   **Cascade:** CASCADE عند حذف الوثيقة

---

## 📊 الفهارس (Indexes)

### فهارس الأداء الحرجة

```sql
-- 1. البحث السريع بالتاريخ
CREATE INDEX idx_daily_entries_date_desc ON daily_entries(date DESC);
CREATE INDEX idx_ledger_entries_date_desc ON ledger_entries(date DESC);

-- 2. البحث المركب للتقارير
CREATE INDEX idx_daily_entries_branch_employee_date
  ON daily_entries(branch_id, employee_id, date DESC);

-- 3. البحث بالحالة
CREATE INDEX idx_advance_requests_status_employee
  ON advance_requests(status, employee_id) WHERE deleted_at IS NULL;

-- 4. الوثائق المنتهية
CREATE INDEX idx_documents_expiry_status
  ON documents(expiry_date) WHERE status IN ('urgent', 'near', 'expired');

-- 5. Soft Delete
CREATE INDEX idx_users_active ON users(id) WHERE deleted_at IS NULL;
CREATE INDEX idx_employees_active ON employees(id) WHERE deleted_at IS NULL;
CREATE INDEX idx_daily_entries_active ON daily_entries(id) WHERE deleted_at IS NULL;
```

---

## ✅ القيود (Constraints)

### قيود التحقق (Check Constraints)

```sql
-- 1. التحقق من القيم المالية
ALTER TABLE daily_entries ADD CONSTRAINT chk_daily_entries_positive_values
  CHECK (sales >= 0 AND cash >= 0 AND expense >= 0 AND commission >= 0 AND bonus >= 0);

-- 2. التحقق من نسبة العمولة
ALTER TABLE employees ADD CONSTRAINT chk_employees_commission_rate
  CHECK (commission_rate >= 0 AND commission_rate <= 100);

-- 3. التحقق من تواريخ التوظيف
ALTER TABLE employees ADD CONSTRAINT chk_employees_dates
  CHECK (termination_date IS NULL OR termination_date >= hire_date);

-- 4. التحقق من تواريخ الوثائق
ALTER TABLE documents ADD CONSTRAINT chk_documents_dates
  CHECK (expiry_date IS NULL OR expiry_date >= issue_date);

-- 5. التحقق من مبلغ السلفة
ALTER TABLE advance_requests ADD CONSTRAINT chk_advance_amount
  CHECK (amount > 0 AND amount <= 50000); -- حد أقصى 50,000
```

### قيود الفريدة (Unique Constraints)

```sql
-- 1. منع التكرار
ALTER TABLE daily_entries ADD CONSTRAINT uq_employee_date
  UNIQUE(employee_id, date);

ALTER TABLE day_closures ADD CONSTRAINT uq_branch_date
  UNIQUE(branch_id, date);

-- 2. أرقام الهواتف
ALTER TABLE users ADD CONSTRAINT uq_users_phone UNIQUE(phone);
ALTER TABLE employees ADD CONSTRAINT uq_employees_phone UNIQUE(phone);
```

---

## 🔧 الإجراءات المخزنة (Stored Procedures)

### 1. حساب رصيد دفتر الحسابات

```sql
CREATE OR REPLACE FUNCTION calculate_ledger_balance(
  p_party_type VARCHAR,
  p_party_id UUID,
  p_end_date DATE DEFAULT CURRENT_DATE
)
RETURNS DECIMAL(10, 2) AS $$
DECLARE
  v_balance DECIMAL(10, 2);
BEGIN
  SELECT COALESCE(
    SUM(CASE
      WHEN type = 'credit' THEN amount
      WHEN type = 'debit' THEN -amount
    END),
    0
  )
  INTO v_balance
  FROM ledger_entries
  WHERE party_type = p_party_type
    AND party_id = p_party_id
    AND date <= p_end_date
    AND deleted_at IS NULL;

  RETURN v_balance;
END;
$$ LANGUAGE plpgsql;
```

### 2. حساب إحصائيات الموظف

```sql
CREATE OR REPLACE FUNCTION get_employee_stats(
  p_employee_id UUID,
  p_from_date DATE,
  p_to_date DATE
)
RETURNS TABLE(
  total_sales DECIMAL(10, 2),
  total_commission DECIMAL(10, 2),
  total_bonus DECIMAL(10, 2),
  entries_count INTEGER,
  avg_daily_sales DECIMAL(10, 2)
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    COALESCE(SUM(de.sales), 0),
    COALESCE(SUM(de.commission), 0),
    COALESCE(SUM(de.bonus), 0),
    COUNT(*)::INTEGER,
    COALESCE(AVG(de.sales), 0)
  FROM daily_entries de
  WHERE de.employee_id = p_employee_id
    AND de.date BETWEEN p_from_date AND p_to_date
    AND de.deleted_at IS NULL;
END;
$$ LANGUAGE plpgsql;
```

### 3. التحقق من صلاحية الإغلاق اليومي

```sql
CREATE OR REPLACE FUNCTION can_close_day(
  p_branch_id UUID,
  p_date DATE
)
RETURNS BOOLEAN AS $$
DECLARE
  v_is_closed BOOLEAN;
  v_has_entries BOOLEAN;
BEGIN
  -- Check if already closed
  SELECT EXISTS(
    SELECT 1 FROM day_closures
    WHERE branch_id = p_branch_id AND date = p_date
  ) INTO v_is_closed;

  IF v_is_closed THEN
    RETURN FALSE;
  END IF;

  -- Check if has entries
  SELECT EXISTS(
    SELECT 1 FROM daily_entries
    WHERE branch_id = p_branch_id
      AND date = p_date
      AND deleted_at IS NULL
  ) INTO v_has_entries;

  RETURN v_has_entries;
END;
$$ LANGUAGE plpgsql;
```

### 4. إنشاء إغلاق يومي

```sql
CREATE OR REPLACE FUNCTION create_day_closure(
  p_branch_id UUID,
  p_date DATE,
  p_closed_by UUID,
  p_notes TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
  v_closure_id UUID;
  v_summary RECORD;
BEGIN
  -- Calculate summary
  SELECT
    COALESCE(SUM(sales), 0) as total_sales,
    COALESCE(SUM(cash), 0) as total_cash,
    COALESCE(SUM(expense), 0) as total_expense,
    COALESCE(SUM(net), 0) as total_net,
    COALESCE(SUM(commission), 0) as total_commission,
    COALESCE(SUM(bonus), 0) as total_bonus,
    COUNT(*) as entries_count,
    COUNT(DISTINCT employee_id) as employees_count
  INTO v_summary
  FROM daily_entries
  WHERE branch_id = p_branch_id
    AND date = p_date
    AND deleted_at IS NULL;

  -- Insert closure
  INSERT INTO day_closures (
    branch_id, date,
    total_sales, total_cash, total_expense, total_net,
    total_commission, total_bonus,
    entries_count, employees_count,
    closed_by, notes
  ) VALUES (
    p_branch_id, p_date,
    v_summary.total_sales, v_summary.total_cash,
    v_summary.total_expense, v_summary.total_net,
    v_summary.total_commission, v_summary.total_bonus,
    v_summary.entries_count, v_summary.employees_count,
    p_closed_by, p_notes
  ) RETURNING id INTO v_closure_id;

  -- Lock daily entries
  UPDATE daily_entries
  SET is_locked = TRUE,
      locked_at = NOW(),
      locked_by = p_closed_by
  WHERE branch_id = p_branch_id AND date = p_date;

  RETURN v_closure_id;
END;
$$ LANGUAGE plpgsql;
```

### 5. موافقة على طلب سلفة

```sql
CREATE OR REPLACE FUNCTION approve_advance_request(
  p_request_id UUID,
  p_approved_by UUID,
  p_notes TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
  v_request RECORD;
  v_ledger_id UUID;
BEGIN
  -- Get request details
  SELECT * INTO v_request
  FROM advance_requests
  WHERE id = p_request_id AND status = 'pending';

  IF NOT FOUND THEN
    RAISE EXCEPTION 'طلب السلفة غير موجود أو تم معالجته';
  END IF;

  -- Create ledger entry (debit - عليه)
  INSERT INTO ledger_entries (
    party_type, party_id, date, type, amount,
    description, source, reference_id, reference_type,
    created_by
  ) VALUES (
    'employee', v_request.employee_id, CURRENT_DATE, 'debit', v_request.amount,
    'سلفة: ' || COALESCE(v_request.reason, 'بدون سبب'),
    'advance_request', p_request_id, 'advance_request',
    p_approved_by
  ) RETURNING id INTO v_ledger_id;

  -- Update request
  UPDATE advance_requests
  SET status = 'approved',
      processed_at = NOW(),
      processed_by = p_approved_by,
      decision_notes = p_notes,
      ledger_entry_id = v_ledger_id
  WHERE id = p_request_id;

  RETURN v_ledger_id;
END;
$$ LANGUAGE plpgsql;
```

### 6. تحديث التحليلات اليومية

```sql
CREATE OR REPLACE FUNCTION update_daily_analytics(
  p_date DATE DEFAULT CURRENT_DATE
)
RETURNS VOID AS $$
BEGIN
  -- System-wide analytics
  INSERT INTO analytics_daily (
    date, scope_type, scope_id,
    total_sales, total_cash, total_expense, total_net,
    total_commission, total_bonus,
    entries_count, employees_count
  )
  SELECT
    p_date, 'system', NULL,
    COALESCE(SUM(sales), 0),
    COALESCE(SUM(cash), 0),
    COALESCE(SUM(expense), 0),
    COALESCE(SUM(net), 0),
    COALESCE(SUM(commission), 0),
    COALESCE(SUM(bonus), 0),
    COUNT(*),
    COUNT(DISTINCT employee_id)
  FROM daily_entries
  WHERE date = p_date AND deleted_at IS NULL
  ON CONFLICT (date, scope_type, scope_id)
  DO UPDATE SET
    total_sales = EXCLUDED.total_sales,
    total_cash = EXCLUDED.total_cash,
    total_expense = EXCLUDED.total_expense,
    total_net = EXCLUDED.total_net,
    total_commission = EXCLUDED.total_commission,
    total_bonus = EXCLUDED.total_bonus,
    entries_count = EXCLUDED.entries_count,
    employees_count = EXCLUDED.employees_count,
    computed_at = NOW();

  -- Branch-level analytics
  INSERT INTO analytics_daily (
    date, scope_type, scope_id,
    total_sales, total_cash, total_expense, total_net,
    total_commission, total_bonus,
    entries_count, employees_count
  )
  SELECT
    p_date, 'branch', branch_id,
    COALESCE(SUM(sales), 0),
    COALESCE(SUM(cash), 0),
    COALESCE(SUM(expense), 0),
    COALESCE(SUM(net), 0),
    COALESCE(SUM(commission), 0),
    COALESCE(SUM(bonus), 0),
    COUNT(*),
    COUNT(DISTINCT employee_id)
  FROM daily_entries
  WHERE date = p_date AND deleted_at IS NULL
  GROUP BY branch_id
  ON CONFLICT (date, scope_type, scope_id)
  DO UPDATE SET
    total_sales = EXCLUDED.total_sales,
    total_cash = EXCLUDED.total_cash,
    total_expense = EXCLUDED.total_expense,
    total_net = EXCLUDED.total_net,
    total_commission = EXCLUDED.total_commission,
    total_bonus = EXCLUDED.total_bonus,
    entries_count = EXCLUDED.entries_count,
    employees_count = EXCLUDED.employees_count,
    computed_at = NOW();
END;
$$ LANGUAGE plpgsql;
```

### 7. إرسال إشعارات الوثائق المنتهية

```sql
CREATE OR REPLACE FUNCTION send_document_expiry_notifications()
RETURNS INTEGER AS $$
DECLARE
  v_count INTEGER := 0;
  v_doc RECORD;
BEGIN
  FOR v_doc IN
    SELECT
      d.*,
      CASE
        WHEN d.owner_type = 'employee' THEN e.name
        WHEN d.owner_type = 'branch' THEN b.name
      END as owner_name
    FROM documents d
    LEFT JOIN employees e ON d.owner_type = 'employee' AND d.owner_id = e.id
    LEFT JOIN branches b ON d.owner_type = 'branch' AND d.owner_id = b.id
    WHERE d.status IN ('urgent', 'near')
      AND d.deleted_at IS NULL
      AND (d.last_notified_at IS NULL OR d.last_notified_at < CURRENT_DATE - INTERVAL '7 days')
  LOOP
    -- Create notification
    INSERT INTO notifications (
      type, target_type, target_id,
      title, message, priority,
      data
    ) VALUES (
      'document_expiry',
      'role',
      'doc_supervisor',
      'تنبيه: وثيقة قاربت على الانتهاء',
      format('وثيقة %s الخاصة بـ %s ستنتهي في %s أيام',
        v_doc.type, v_doc.owner_name, v_doc.days_remaining),
      CASE WHEN v_doc.status = 'urgent' THEN 'urgent' ELSE 'high' END,
      jsonb_build_object(
        'document_id', v_doc.id,
        'owner_type', v_doc.owner_type,
        'owner_id', v_doc.owner_id,
        'days_remaining', v_doc.days_remaining
      )
    );

    -- Update last notified
    UPDATE documents
    SET last_notified_at = NOW()
    WHERE id = v_doc.id;

    v_count := v_count + 1;
  END LOOP;

  RETURN v_count;
END;
$$ LANGUAGE plpgsql;
```

---

## 🚀 SQL Scripts

### Script كامل لإنشاء قاعدة البيانات

```sql
-- =====================================
-- Salon Management System Database
-- Full Schema Creation Script
-- =====================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Drop existing tables (لإعادة البناء الكامل)
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS document_files CASCADE;
DROP TABLE IF EXISTS documents CASCADE;
DROP TABLE IF EXISTS advance_requests CASCADE;
DROP TABLE IF EXISTS ledger_entries CASCADE;
DROP TABLE IF EXISTS day_closures CASCADE;
DROP TABLE IF EXISTS daily_entries CASCADE;
DROP TABLE IF EXISTS employees CASCADE;
DROP TABLE IF EXISTS branches CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS analytics_daily CASCADE;

-- =====================================
-- 1. Users Table
-- =====================================
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) UNIQUE NOT NULL,
  email VARCHAR(100),
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL CHECK (role IN ('owner', 'manager', 'accountant', 'barber', 'doc_supervisor')),
  branch_id UUID,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
  last_login_at TIMESTAMP WITH TIME ZONE,
  last_login_ip INET,
  failed_login_count INTEGER DEFAULT 0,
  locked_until TIMESTAMP WITH TIME ZONE,
  avatar_url TEXT,
  bio TEXT,
  settings JSONB DEFAULT '{}',
  preferences JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID,
  updated_by UUID
);

-- =====================================
-- 2. Branches Table
-- =====================================
CREATE TABLE branches (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) UNIQUE,
  address TEXT,
  city VARCHAR(50),
  region VARCHAR(50),
  country VARCHAR(50) DEFAULT 'Saudi Arabia',
  postal_code VARCHAR(10),
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  phone VARCHAR(20),
  email VARCHAR(100),
  manager_id UUID,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'maintenance')),
  opening_time TIME,
  closing_time TIME,
  working_days JSONB DEFAULT '["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]',
  settings JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID,
  updated_by UUID
);

-- Add foreign key after both tables are created
ALTER TABLE users ADD CONSTRAINT fk_users_branch
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;

ALTER TABLE branches ADD CONSTRAINT fk_branches_manager
  FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================
-- 3. Employees Table
-- =====================================
CREATE TABLE employees (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  branch_id UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) UNIQUE NOT NULL,
  email VARCHAR(100),
  national_id VARCHAR(20),
  passport_number VARCHAR(20),
  role VARCHAR(20) NOT NULL DEFAULT 'barber' CHECK (role IN ('barber', 'manager', 'receptionist', 'other')),
  hire_date DATE NOT NULL,
  termination_date DATE,
  employment_type VARCHAR(20) DEFAULT 'full_time' CHECK (employment_type IN ('full_time', 'part_time', 'contract', 'freelance')),
  commission_rate DECIMAL(5, 2) DEFAULT 50.00,
  commission_type VARCHAR(20) DEFAULT 'percentage' CHECK (commission_type IN ('percentage', 'fixed', 'tiered')),
  base_salary DECIMAL(10, 2) DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'on_leave', 'suspended')),
  avatar_url TEXT,
  bio TEXT,
  skills JSONB DEFAULT '[]',
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID REFERENCES users(id),
  updated_by UUID REFERENCES users(id),
  CONSTRAINT chk_employees_commission_rate CHECK (commission_rate >= 0 AND commission_rate <= 100),
  CONSTRAINT chk_employees_dates CHECK (termination_date IS NULL OR termination_date >= hire_date)
);

-- =====================================
-- 4. Daily Entries Table
-- =====================================
CREATE TABLE daily_entries (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  branch_id UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  employee_id UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  sales DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (sales >= 0),
  cash DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (cash >= 0),
  expense DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (expense >= 0),
  net DECIMAL(10, 2) GENERATED ALWAYS AS (sales - cash - expense) STORED,
  commission DECIMAL(10, 2) DEFAULT 0 CHECK (commission >= 0),
  commission_rate DECIMAL(5, 2),
  bonus DECIMAL(10, 2) DEFAULT 0 CHECK (bonus >= 0),
  bonus_reason TEXT,
  note TEXT,
  transactions_count INTEGER DEFAULT 0,
  source VARCHAR(20) NOT NULL DEFAULT 'web' CHECK (source IN ('web', 'mobile', 'api')),
  is_locked BOOLEAN DEFAULT FALSE,
  locked_at TIMESTAMP WITH TIME ZONE,
  locked_by UUID REFERENCES users(id),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID REFERENCES users(id),
  updated_by UUID REFERENCES users(id),
  UNIQUE(employee_id, date)
);

-- =====================================
-- 5. Day Closures Table
-- =====================================
CREATE TABLE day_closures (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  branch_id UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  total_sales DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_cash DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_expense DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_net DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_commission DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_bonus DECIMAL(10, 2) NOT NULL DEFAULT 0,
  entries_count INTEGER NOT NULL DEFAULT 0,
  employees_count INTEGER NOT NULL DEFAULT 0,
  closed_by UUID NOT NULL REFERENCES users(id),
  closed_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  pdf_url TEXT,
  pdf_generated_at TIMESTAMP WITH TIME ZONE,
  notes TEXT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  UNIQUE(branch_id, date)
);

-- =====================================
-- 6. Ledger Entries Table
-- =====================================
CREATE TABLE ledger_entries (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  party_type VARCHAR(20) NOT NULL CHECK (party_type IN ('employee', 'branch', 'supplier', 'customer')),
  party_id UUID NOT NULL,
  date DATE NOT NULL,
  type VARCHAR(20) NOT NULL CHECK (type IN ('debit', 'credit')),
  amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
  description TEXT NOT NULL,
  category VARCHAR(50),
  source VARCHAR(30) NOT NULL CHECK (source IN ('manual', 'advance_request', 'salary', 'closure', 'other')),
  reference_id UUID,
  reference_type VARCHAR(30),
  payment_method VARCHAR(20) CHECK (payment_method IN ('cash', 'bank_transfer', 'check', 'other')),
  attachment_url TEXT,
  status VARCHAR(20) DEFAULT 'confirmed' CHECK (status IN ('pending', 'confirmed', 'cancelled')),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID REFERENCES users(id),
  updated_by UUID REFERENCES users(id)
);

-- =====================================
-- 7. Advance Requests Table
-- =====================================
CREATE TABLE advance_requests (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  employee_id UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
  branch_id UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0 AND amount <= 50000),
  reason TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
  requested_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  processed_at TIMESTAMP WITH TIME ZONE,
  processed_by UUID REFERENCES users(id),
  decision_notes TEXT,
  rejection_reason TEXT,
  payment_date DATE,
  payment_method VARCHAR(20) CHECK (payment_method IN ('cash', 'bank_transfer', 'check', 'deduction')),
  attachment_url TEXT,
  ledger_entry_id UUID REFERENCES ledger_entries(id),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE
);

-- =====================================
-- 8. Documents Table
-- =====================================
CREATE TABLE documents (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  owner_type VARCHAR(20) NOT NULL CHECK (owner_type IN ('employee', 'branch', 'company')),
  owner_id UUID NOT NULL,
  type VARCHAR(50) NOT NULL,
  number VARCHAR(50),
  title VARCHAR(200),
  issue_date DATE,
  expiry_date DATE,
  status VARCHAR(20) GENERATED ALWAYS AS (
    CASE
      WHEN expiry_date IS NULL THEN 'safe'
      WHEN expiry_date < CURRENT_DATE THEN 'expired'
      WHEN expiry_date <= CURRENT_DATE + INTERVAL '15 days' THEN 'urgent'
      WHEN expiry_date <= CURRENT_DATE + INTERVAL '60 days' THEN 'near'
      ELSE 'safe'
    END
  ) STORED,
  days_remaining INTEGER GENERATED ALWAYS AS (
    CASE
      WHEN expiry_date IS NULL THEN NULL
      ELSE EXTRACT(DAY FROM (expiry_date - CURRENT_DATE))::INTEGER
    END
  ) STORED,
  notify_before_days INTEGER DEFAULT 30,
  last_notified_at TIMESTAMP WITH TIME ZONE,
  notes TEXT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE,
  created_by UUID REFERENCES users(id),
  updated_by UUID REFERENCES users(id),
  CONSTRAINT chk_documents_dates CHECK (expiry_date IS NULL OR expiry_date >= issue_date)
);

-- =====================================
-- 9. Document Files Table
-- =====================================
CREATE TABLE document_files (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  document_id UUID NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  size BIGINT NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_url TEXT NOT NULL,
  storage_provider VARCHAR(20) DEFAULT 'local' CHECK (storage_provider IN ('local', 's3', 'cloudinary', 'supabase')),
  version INTEGER DEFAULT 1,
  is_current BOOLEAN DEFAULT TRUE,
  uploaded_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  uploaded_by UUID REFERENCES users(id)
);

-- =====================================
-- 10. Notifications Table
-- =====================================
CREATE TABLE notifications (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  type VARCHAR(30) NOT NULL CHECK (type IN ('document_expiry', 'advance_request', 'day_closure', 'system', 'other')),
  target_type VARCHAR(20) NOT NULL CHECK (target_type IN ('user', 'role', 'branch', 'all')),
  target_id UUID,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  data JSONB DEFAULT '{}',
  action_url TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'read', 'failed')),
  priority VARCHAR(20) DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
  channels JSONB DEFAULT '["in_app"]',
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  sent_at TIMESTAMP WITH TIME ZONE,
  read_at TIMESTAMP WITH TIME ZONE,
  expires_at TIMESTAMP WITH TIME ZONE
);

-- =====================================
-- 11. Audit Logs Table
-- =====================================
CREATE TABLE audit_logs (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  user_name VARCHAR(100),
  user_role VARCHAR(20),
  action VARCHAR(50) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id UUID,
  old_values JSONB,
  new_values JSONB,
  ip_address INET,
  user_agent TEXT,
  request_method VARCHAR(10),
  request_url TEXT,
  status VARCHAR(20) DEFAULT 'success' CHECK (status IN ('success', 'failed')),
  error_message TEXT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- =====================================
-- 12. Analytics Daily Table
-- =====================================
CREATE TABLE analytics_daily (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  date DATE NOT NULL,
  scope_type VARCHAR(20) NOT NULL CHECK (scope_type IN ('system', 'branch', 'employee')),
  scope_id UUID,
  total_sales DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_cash DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_expense DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_net DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_commission DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total_bonus DECIMAL(10, 2) NOT NULL DEFAULT 0,
  entries_count INTEGER NOT NULL DEFAULT 0,
  employees_count INTEGER NOT NULL DEFAULT 0,
  transactions_count INTEGER NOT NULL DEFAULT 0,
  avg_sale_value DECIMAL(10, 2),
  avg_commission_rate DECIMAL(5, 2),
  computed_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
  UNIQUE(date, scope_type, scope_id)
);

-- =====================================
-- Create All Indexes
-- =====================================

-- Users
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_branch_id ON users(branch_id);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);

-- Branches
CREATE INDEX idx_branches_code ON branches(code);
CREATE INDEX idx_branches_manager_id ON branches(manager_id);
CREATE INDEX idx_branches_status ON branches(status);
CREATE INDEX idx_branches_city ON branches(city);
CREATE INDEX idx_branches_deleted_at ON branches(deleted_at);

-- Employees
CREATE INDEX idx_employees_branch_id ON employees(branch_id);
CREATE INDEX idx_employees_phone ON employees(phone);
CREATE INDEX idx_employees_role ON employees(role);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_employees_hire_date ON employees(hire_date);
CREATE INDEX idx_employees_deleted_at ON employees(deleted_at);

-- Daily Entries
CREATE INDEX idx_daily_entries_branch_id ON daily_entries(branch_id);
CREATE INDEX idx_daily_entries_employee_id ON daily_entries(employee_id);
CREATE INDEX idx_daily_entries_date ON daily_entries(date DESC);
CREATE INDEX idx_daily_entries_is_locked ON daily_entries(is_locked);
CREATE INDEX idx_daily_entries_created_at ON daily_entries(created_at DESC);
CREATE INDEX idx_daily_entries_deleted_at ON daily_entries(deleted_at);
CREATE INDEX idx_daily_entries_branch_date ON daily_entries(branch_id, date DESC);
CREATE INDEX idx_daily_entries_employee_date ON daily_entries(employee_id, date DESC);

-- Day Closures
CREATE INDEX idx_day_closures_branch_id ON day_closures(branch_id);
CREATE INDEX idx_day_closures_date ON day_closures(date DESC);
CREATE INDEX idx_day_closures_closed_by ON day_closures(closed_by);
CREATE INDEX idx_day_closures_closed_at ON day_closures(closed_at DESC);

-- Ledger Entries
CREATE INDEX idx_ledger_party ON ledger_entries(party_type, party_id);
CREATE INDEX idx_ledger_date ON ledger_entries(date DESC);
CREATE INDEX idx_ledger_type ON ledger_entries(type);
CREATE INDEX idx_ledger_source ON ledger_entries(source);
CREATE INDEX idx_ledger_reference ON ledger_entries(reference_type, reference_id);
CREATE INDEX idx_ledger_status ON ledger_entries(status);
CREATE INDEX idx_ledger_created_at ON ledger_entries(created_at DESC);
CREATE INDEX idx_ledger_deleted_at ON ledger_entries(deleted_at);

-- Advance Requests
CREATE INDEX idx_advance_requests_employee_id ON advance_requests(employee_id);
CREATE INDEX idx_advance_requests_branch_id ON advance_requests(branch_id);
CREATE INDEX idx_advance_requests_status ON advance_requests(status);
CREATE INDEX idx_advance_requests_requested_at ON advance_requests(requested_at DESC);
CREATE INDEX idx_advance_requests_processed_by ON advance_requests(processed_by);
CREATE INDEX idx_advance_requests_deleted_at ON advance_requests(deleted_at);

-- Documents
CREATE INDEX idx_documents_owner ON documents(owner_type, owner_id);
CREATE INDEX idx_documents_type ON documents(type);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_documents_expiry_date ON documents(expiry_date);
CREATE INDEX idx_documents_deleted_at ON documents(deleted_at);

-- Document Files
CREATE INDEX idx_document_files_document_id ON document_files(document_id);
CREATE INDEX idx_document_files_uploaded_at ON document_files(uploaded_at DESC);

-- Notifications
CREATE INDEX idx_notifications_target ON notifications(target_type, target_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notifications_priority ON notifications(priority);
CREATE INDEX idx_notifications_created_at ON notifications(created_at DESC);

-- Audit Logs
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);
CREATE INDEX idx_audit_logs_ip_address ON audit_logs(ip_address);

-- Analytics Daily
CREATE INDEX idx_analytics_date ON analytics_daily(date DESC);
CREATE INDEX idx_analytics_scope ON analytics_daily(scope_type, scope_id);

-- =====================================
-- Done!
-- =====================================
```

---

## 🌱 البيانات الأولية (Seeds)

### مستخدم المالك الأولي

```sql
-- Insert Owner User
INSERT INTO users (
  name, phone, email, password_hash, role, status
) VALUES (
  'المالك الرئيسي',
  '0500000000',
  'owner@salon.com',
  '$2a$10$encrypted_password_here', -- يجب تشفيره
  'owner',
  'active'
);

-- Insert Sample Branch
INSERT INTO branches (
  name, code, city, status, created_by
) VALUES (
  'الفرع الرئيسي',
  'MAIN',
  'الرياض',
  'active',
  (SELECT id FROM users WHERE role = 'owner' LIMIT 1)
);

-- Insert Sample Employee
INSERT INTO employees (
  branch_id, name, phone, role, hire_date, commission_rate, status
) VALUES (
  (SELECT id FROM branches WHERE code = 'MAIN'),
  'أحمد محمد',
  '0501234567',
  'barber',
  CURRENT_DATE,
  50.00,
  'active'
);
```

---

## 📈 ملاحظات مهمة

### 1. الأمان (Security)

-   ✅ استخدام UUID بدلاً من SERIAL
-   ✅ تشفير كلمات المرور (bcrypt)
-   ✅ Soft Delete لجميع الجداول الحرجة
-   ✅ تتبع التغييرات (Audit Logs)
-   ✅ Row Level Security (RLS) في Supabase

### 2. الأداء (Performance)

-   ✅ فهارس مركبة للتقارير
-   ✅ Generated Columns للحسابات التلقائية
-   ✅ Partitioning للجداول الكبيرة (اختياري)
-   ✅ جدول Analytics للتخزين المسبق

### 3. التوسع (Scalability)

-   ✅ UUID يسمح بالتوزيع
-   ✅ JSONB للمرونة
-   ✅ Polymorphic Relationships
-   ✅ قابلية إضافة حقول جديدة

### 4. الصيانة (Maintenance)

-   ✅ Timestamps تلقائية
-   ✅ Soft Delete
-   ✅ مسح البيانات القديمة بسهولة
-   ✅ نسخ احتياطي يومي

---

## ✅ قائمة التحقق النهائية

-   [x] 12 جدول رئيسي
-   [x] جميع العلاقات محددة
-   [x] 40+ فهرس للأداء
-   [x] 25+ مفتاح أجنبي
-   [x] 8 إجراء مخزن
-   [x] قيود التحقق
-   [x] Soft Delete
-   [x] Audit Logging
-   [x] Generated Columns
-   [x] JSONB للمرونة

---

## 🎯 الخلاصة

هذا المخطط يوفر:

-   ✅ **قاعدة بيانات احترافية** جاهزة للإنتاج
-   ✅ **علاقات واضحة** بين جميع الكيانات
-   ✅ **أداء محسّن** بالفهارس الصحيحة
-   ✅ **أمان عالٍ** مع تتبع كامل
-   ✅ **قابلية التوسع** للمستقبل
-   ✅ **سهولة الصيانة** مع Soft Delete

**جاهز للتنفيذ على:** PostgreSQL, MySQL, Supabase  
**متوافق مع:** الويب، الموبايل، API

---

**تم بحمد الله ✨**

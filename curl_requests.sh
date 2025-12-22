#!/usr/bin/env bash

BASE_URL="http://127.0.0.1:8000/api/v1"
ACCESS_TOKEN="YOUR_ACCESS_TOKEN"
REFRESH_TOKEN="YOUR_REFRESH_TOKEN"

USER_ID="YOUR_USER_ID"
BRANCH_ID="YOUR_BRANCH_ID"
MANAGER_ID="YOUR_MANAGER_ID"
EMPLOYEE_ID="YOUR_EMPLOYEE_ID"
DAILY_ENTRY_ID="YOUR_DAILY_ENTRY_ID"
DAY_CLOSURE_ID="YOUR_DAY_CLOSURE_ID"
LEDGER_PARTY_TYPE="employee"
LEDGER_PARTY_ID="$EMPLOYEE_ID"
ADVANCE_REQUEST_ID="YOUR_ADVANCE_REQUEST_ID"
DOCUMENT_ID="YOUR_DOCUMENT_ID"
DOCUMENT_OWNER_TYPE="employee"
DOCUMENT_OWNER_ID="$EMPLOYEE_ID"
NOTIFICATION_ID="YOUR_NOTIFICATION_ID"

# Auth
curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "0500000000",
    "password": "SecurePassword123!",
    "device_info": {
      "device_id": "uuid-device-id",
      "device_name": "iPhone 14 Pro",
      "os": "iOS 17.0",
      "app_version": "1.0.0"
    }
  }'

curl -s -X POST "$BASE_URL/auth/refresh" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}"

curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Users
curl -s -X GET "$BASE_URL/users?page=1&per_page=20&status=active" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/users" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Mohammed Ahmed",
    "phone": "0501234567",
    "email": "mohammed@example.com",
    "password": "SecurePassword123!",
    "role": "barber",
    "branch_id": "'$BRANCH_ID'",
    "status": "active"
  }'

curl -s -X GET "$BASE_URL/users/$USER_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X PUT "$BASE_URL/users/$USER_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Ahmed Updated",
    "email": "ahmed.new@example.com",
    "status": "active"
  }'

curl -s -X DELETE "$BASE_URL/users/$USER_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/users/$USER_ID/change-password" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "OldPassword123!",
    "new_password": "NewSecurePassword123!",
    "new_password_confirmation": "NewSecurePassword123!"
  }'

# Branches
curl -s -X GET "$BASE_URL/branches?status=active&city=Riyadh" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/branches" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Khobar Branch",
    "code": "KBR",
    "city": "Khobar",
    "region": "Eastern",
    "address": "North Corniche",
    "phone": "0133334444",
    "manager_id": "'$MANAGER_ID'",
    "opening_time": "09:00",
    "closing_time": "23:00",
    "working_days": ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]
  }'

curl -s -X GET "$BASE_URL/branches/$BRANCH_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Employees
curl -s -X GET "$BASE_URL/employees?branch_id=$BRANCH_ID&status=active" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/employees" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "branch_id": "'$BRANCH_ID'",
    "name": "Abdullah Saeed",
    "phone": "0509999999",
    "email": "abdullah@example.com",
    "national_id": "1234567890",
    "role": "barber",
    "hire_date": "2025-12-22",
    "commission_rate": 50.0,
    "commission_type": "percentage",
    "base_salary": 3000.0
  }'

curl -s -X GET "$BASE_URL/employees/$EMPLOYEE_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Daily Entries
curl -s -X GET "$BASE_URL/daily-entries?employee_id=$EMPLOYEE_ID&date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/daily-entries" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "employee_id": "'$EMPLOYEE_ID'",
    "branch_id": "'$BRANCH_ID'",
    "date": "2025-12-22",
    "sales": 1500.0,
    "cash": 500.0,
    "expense": 100.0,
    "commission_rate": 50.0,
    "bonus": 50.0,
    "bonus_reason": "Great performance",
    "note": "Good day",
    "transactions_count": 8
  }'

curl -s -X GET "$BASE_URL/daily-entries/$DAILY_ENTRY_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X PUT "$BASE_URL/daily-entries/$DAILY_ENTRY_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sales": 1600.0,
    "cash": 550.0,
    "expense": 120.0,
    "bonus": 100.0,
    "note": "Update: great day"
  }'

curl -s -X DELETE "$BASE_URL/daily-entries/$DAILY_ENTRY_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/daily-entries/stats/employee/$EMPLOYEE_ID?date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Day Closures
curl -s -X GET "$BASE_URL/day-closures?branch_id=$BRANCH_ID&date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/day-closures" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "branch_id": "'$BRANCH_ID'",
    "date": "2025-12-22",
    "notes": "Day closure 2025-12-22"
  }'

curl -s -X GET "$BASE_URL/day-closures/$DAY_CLOSURE_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/day-closures/$DAY_CLOSURE_ID/pdf" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/pdf" \
  -o "day-closure.pdf"

# Ledger Entries
curl -s -X GET "$BASE_URL/ledger-entries?party_type=employee&party_id=$EMPLOYEE_ID&date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/ledger-entries" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "party_type": "employee",
    "party_id": "'$EMPLOYEE_ID'",
    "date": "2025-12-22",
    "type": "credit",
    "amount": 1000.0,
    "description": "Salary payment",
    "category": "salary",
    "payment_method": "bank_transfer"
  }'

curl -s -X GET "$BASE_URL/ledger-entries/balance/$LEDGER_PARTY_TYPE/$LEDGER_PARTY_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Advance Requests
curl -s -X GET "$BASE_URL/advance-requests?employee_id=$EMPLOYEE_ID&status=pending" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/advance-requests" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "employee_id": "'$EMPLOYEE_ID'",
    "amount": 500.0,
    "reason": "Emergency",
    "attachment": "BASE64_IMAGE_DATA"
  }'

curl -s -X POST "$BASE_URL/advance-requests/$ADVANCE_REQUEST_ID/approve" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "decision_notes": "Approved",
    "payment_date": "2025-12-22",
    "payment_method": "cash"
  }'

curl -s -X POST "$BASE_URL/advance-requests/$ADVANCE_REQUEST_ID/reject" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "rejection_reason": "Not possible right now"
  }'

# Documents
curl -s -X GET "$BASE_URL/documents?owner_type=$DOCUMENT_OWNER_TYPE&owner_id=$DOCUMENT_OWNER_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/documents" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -F "owner_type=$DOCUMENT_OWNER_TYPE" \
  -F "owner_id=$DOCUMENT_OWNER_ID" \
  -F "type=Residency" \
  -F "number=1234567890" \
  -F "title=Residency - Ahmed" \
  -F "issue_date=2024-01-01" \
  -F "expiry_date=2026-01-01" \
  -F "notify_before_days=30" \
  -F "notes=Renew in Jan 2026" \
  -F "files[]=@/path/to/file1.pdf"

curl -s -X PUT "$BASE_URL/documents/$DOCUMENT_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "expiry_date": "2026-06-01",
    "notify_before_days": 60,
    "notes": "Renewed"
  }'

curl -s -X POST "$BASE_URL/documents/$DOCUMENT_ID/files" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -F "file=@/path/to/document.pdf"

curl -s -X GET "$BASE_URL/documents/expiring-soon?days=30&owner_type=$DOCUMENT_OWNER_TYPE" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Notifications
curl -s -X GET "$BASE_URL/notifications?status=pending" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/notifications/$NOTIFICATION_ID/read" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X POST "$BASE_URL/notifications/read-all" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Reports
curl -s -X GET "$BASE_URL/reports/sales?date_from=2025-12-01&date_to=2025-12-31&group_by=day" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/reports/employees?date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/reports/branches?date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/reports/ledger?party_type=employee&date_from=2025-12-01&date_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Analytics
curl -s -X GET "$BASE_URL/analytics/dashboard?period=today" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

curl -s -X GET "$BASE_URL/analytics/compare?period1_from=2025-11-01&period1_to=2025-11-30&period2_from=2025-12-01&period2_to=2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Accept: application/json"

# Webhooks
curl -s -X POST "$BASE_URL/webhooks" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "url": "https://your-app.com/webhooks/salon",
    "events": ["daily_entry.created", "day_closure.completed", "advance_request.submitted", "document.expiring"],
    "secret": "your_webhook_secret"
  }'

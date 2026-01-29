# دليل نقاط نهاية API - روائع الحرم

**Base URL:** `https://809147dc35e2.ngrok-free.app/api`

---

## 1. المصادقة (Authentication)

### 1.1 تسجيل الدخول
```
POST https://809147dc35e2.ngrok-free.app/api/auth/login
```

**الوصف:** يستخدم لتسجيل دخول المستخدم إلى النظام باستخدام اسم المستخدم/البريد الإلكتروني وكلمة المرور. يُرجع token للمصادقة.

**مثال Request:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**مثال Response (نجاح):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "username": "admin",
      "role": "admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "default_route": "/dashboard"
  }
}
```

---

### 1.2 تسجيل حساب جديد
```
POST https://809147dc35e2.ngrok-free.app/api/auth/register
```

**الوصف:** يستخدم لإنشاء حساب متبرع جديد في النظام.

**مثال Request:**
```json
{
  "name": "محمد أحمد",
  "email": "mohamed@example.com",
  "username": "mohamed123",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+966501234567"
}
```

**مثال Response (نجاح):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 10,
      "name": "محمد أحمد",
      "email": "mohamed@example.com",
      "username": "mohamed123",
      "role": "donor"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

### 1.3 تحديث Token
```
POST https://809147dc35e2.ngrok-free.app/api/auth/refresh
```

**الوصف:** يستخدم لتجديد token المصادقة قبل انتهاء صلاحيته.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

### 1.4 تسجيل الخروج
```
POST https://809147dc35e2.ngrok-free.app/api/auth/logout
```

**الوصف:** يستخدم لتسجيل خروج المستخدم وإبطال token الحالي.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 1.5 نسيت كلمة المرور
```
POST https://809147dc35e2.ngrok-free.app/api/auth/forgot-password
```

**الوصف:** يستخدم لإرسال رابط إعادة تعيين كلمة المرور إلى البريد الإلكتروني.

**مثال Request:**
```json
{
  "email": "user@example.com"
}
```

**مثال Response:**
```json
{
  "success": true,
  "message": "If the email exists, a password reset link has been sent."
}
```

---

### 1.6 إعادة تعيين كلمة المرور
```
POST https://809147dc35e2.ngrok-free.app/api/auth/reset-password
```

**الوصف:** يستخدم لإعادة تعيين كلمة المرور باستخدام token المُرسل بالبريد.

**مثال Request:**
```json
{
  "token": "reset-token-from-email",
  "email": "user@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**مثال Response:**
```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

---

## 2. المساجد (Mosques)

### 2.1 جلب قائمة المساجد
```
GET https://809147dc35e2.ngrok-free.app/api/mosques
```

**الوصف:** يستخدم لجلب قائمة جميع المساجد مع إمكانية الفلترة والترتيب.

**Query Parameters (اختياري):**
- `search` - البحث باسم المسجد
- `need_level` - فلترة حسب مستوى الحاجة (Low, Medium, High)
- `min_need_score` - الحد الأدنى لنقاط الحاجة
- `page` - رقم الصفحة (افتراضي: 1)
- `per_page` - عدد العناصر في الصفحة (افتراضي: 15، الحد الأقصى: 100)
- `sort_by` - حقل الترتيب (need_score, name, created_at)
- `sort_order` - اتجاه الترتيب (asc, desc)

**مثال Request:**
```
GET https://809147dc35e2.ngrok-free.app/api/mosques?need_level=High&per_page=10
```

---

### 2.2 جلب تفاصيل مسجد
```
GET https://809147dc35e2.ngrok-free.app/api/mosques/{id}
```

**الوصف:** يستخدم لجلب تفاصيل مسجد محدد مع آخر التبرعات والتوصيلات.

**مثال Request:**
```
GET https://809147dc35e2.ngrok-free.app/api/mosques/1
```

---

### 2.3 إنشاء مسجد جديد (Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/mosques
```

**الوصف:** يستخدم لإنشاء مسجد جديد في النظام. متاح للمدير فقط.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "name": "مسجد النور",
  "location": "الرياض، حي النزهة",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "capacity": 5000,
  "required_water_level": 3000,
  "description": "مسجد كبير يخدم حي النزهة"
}
```

---

### 2.4 تحديث مسجد (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/mosques/{id}
```

**الوصف:** يستخدم لتحديث بيانات مسجد موجود.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "name": "مسجد النور الكبير",
  "location": "الرياض، حي النزهة",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "capacity": 6000,
  "required_water_level": 4000,
  "description": "مسجد كبير يخدم حي النزهة والأحياء المجاورة"
}
```

---

### 2.5 حذف مسجد (Admin فقط)
```
DELETE https://809147dc35e2.ngrok-free.app/api/mosques/{id}
```

**الوصف:** يستخدم لحذف مسجد من النظام.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

## 3. التبرعات (Donations)

### 3.1 جلب قائمة التبرعات
```
GET https://809147dc35e2.ngrok-free.app/api/donations
```

**الوصف:** يستخدم لجلب قائمة التبرعات مع إمكانية الفلترة.

**Query Parameters (اختياري):**
- `donor_id` - فلترة حسب المتبرع
- `mosque_id` - فلترة حسب المسجد
- `status` - فلترة حسب الحالة (pending, completed, failed, cancelled)
- `date_from` - من تاريخ
- `date_to` - إلى تاريخ
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 3.2 جلب تفاصيل تبرع
```
GET https://809147dc35e2.ngrok-free.app/api/donations/{id}
```

**الوصف:** يستخدم لجلب تفاصيل تبرع محدد.

---

### 3.3 إنشاء تبرع جديد (Donor فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/donations
```

**الوصف:** يستخدم لإنشاء تبرع جديد. متاح للمتبرعين فقط.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "mosque_id": 1,
  "amount": 500.00,
  "payment_method": "mada",
  "payment_transaction_id": "TXN123456"
}
```

**القيم المتاحة لـ payment_method:**
- `apple_pay`
- `mada`
- `stc_pay`
- `other`

---

### 3.4 جلب سجل تبرعاتي (Donor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/donations/my-history
```

**الوصف:** يستخدم لجلب سجل تبرعات المستخدم الحالي.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 3.5 التحقق من تبرع (Admin/Auditor فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/donations/{id}/verify
```

**الوصف:** يستخدم للتحقق من صحة تبرع أو إلغاء التحقق منه.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "verified": true
}
```

---

### 3.6 تحديث حالة تبرع (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/donations/{id}/status
```

**الوصف:** يستخدم لتحديث حالة تبرع.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "status": "completed"
}
```

**القيم المتاحة لـ status:**
- `pending`
- `completed`
- `failed`
- `cancelled`

---

## 4. طلبات الحاجة (Need Requests)

### 4.1 جلب قائمة طلبات الحاجة
```
GET https://809147dc35e2.ngrok-free.app/api/need-requests
```

**الوصف:** يستخدم لجلب قائمة طلبات الحاجة للماء.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters (اختياري):**
- `mosque_id` - فلترة حسب المسجد
- `status` - فلترة حسب الحالة (pending, approved, rejected, fulfilled)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 4.2 جلب طلبات مسجدي (Mosque Admin فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/need-requests/my-mosque
```

**الوصف:** يستخدم لجلب طلبات الحاجة الخاصة بالمسجد الذي يديره المستخدم.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 4.3 إنشاء طلب حاجة (Mosque Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/need-requests
```

**الوصف:** يستخدم لإنشاء طلب حاجة ماء جديد للمسجد.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "mosque_id": 1,
  "water_quantity": 2000
}
```

---

### 4.4 الموافقة على طلب حاجة (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/need-requests/{id}/approve
```

**الوصف:** يستخدم للموافقة على طلب حاجة معلق.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 4.5 رفض طلب حاجة (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/need-requests/{id}/reject
```

**الوصف:** يستخدم لرفض طلب حاجة مع ذكر السبب.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "rejection_reason": "الكمية المطلوبة تتجاوز سعة الخزان"
}
```

---

## 5. صور الخزانات (Tank Images)

### 5.1 جلب صور الخزانات
```
GET https://809147dc35e2.ngrok-free.app/api/tank-images
```

**الوصف:** يستخدم لجلب صور خزانات المياه.

**Query Parameters (اختياري):**
- `mosque_id` - فلترة حسب المسجد
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 5.2 جلب صور خزان مسجدي (Mosque Admin فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/tank-images/my-mosque
```

**الوصف:** يستخدم لجلب صور خزان المسجد الذي يديره المستخدم.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 5.3 رفع صورة خزان (Mosque Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/tank-images
```

**الوصف:** يستخدم لرفع صورة جديدة لخزان المياه.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**مثال Request (FormData):**
```
mosque_id: 1
image: [ملف الصورة - jpg, jpeg, png, gif, webp - الحد الأقصى 5MB]
description: "صورة خزان المسجد بعد التعبئة"
```

---

### 5.4 حذف صورة خزان (Mosque Admin فقط)
```
DELETE https://809147dc35e2.ngrok-free.app/api/tank-images/{id}
```

**الوصف:** يستخدم لحذف صورة خزان.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

## 6. الشاحنات (Trucks)

### 6.1 جلب قائمة الشاحنات
```
GET https://809147dc35e2.ngrok-free.app/api/trucks
```

**الوصف:** يستخدم لجلب قائمة شاحنات توصيل المياه. متاح لـ Admin, Auditor, Logistics Supervisor.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters (اختياري):**
- `status` - فلترة حسب الحالة (active, inactive, maintenance)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 6.2 جلب تفاصيل شاحنة
```
GET https://809147dc35e2.ngrok-free.app/api/trucks/{id}
```

**الوصف:** يستخدم لجلب تفاصيل شاحنة محددة. متاح لـ Admin, Auditor, Logistics Supervisor.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 6.3 إنشاء شاحنة جديدة (Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/trucks
```

**الوصف:** يستخدم لإضافة شاحنة جديدة للأسطول.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "truck_id": "TRK-001",
  "name": "شاحنة المياه 1",
  "capacity": 10000
}
```

---

### 6.4 تحديث شاحنة (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/trucks/{id}
```

**الوصف:** يستخدم لتحديث بيانات شاحنة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "truck_id": "TRK-001",
  "name": "شاحنة المياه الكبيرة",
  "capacity": 15000,
  "status": "active"
}
```

---

### 6.5 تحديث موقع شاحنة (Admin/Logistics Supervisor فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/trucks/{id}/location
```

**الوصف:** يستخدم لتحديث الموقع الجغرافي للشاحنة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "latitude": 24.7136,
  "longitude": 46.6753
}
```

---

## 7. التوصيلات (Deliveries)

### 7.1 جلب قائمة التوصيلات
```
GET https://809147dc35e2.ngrok-free.app/api/deliveries
```

**الوصف:** يستخدم لجلب قائمة عمليات توصيل المياه.

**Query Parameters (اختياري):**
- `truck_id` - فلترة حسب الشاحنة
- `mosque_id` - فلترة حسب المسجد
- `status` - فلترة حسب الحالة (pending, in-transit, delivered, cancelled)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 7.2 جلب تفاصيل توصيل
```
GET https://809147dc35e2.ngrok-free.app/api/deliveries/{id}
```

**الوصف:** يستخدم لجلب تفاصيل عملية توصيل محددة.

---

### 7.3 إنشاء توصيل جديد (Admin/Logistics Supervisor فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/deliveries
```

**الوصف:** يستخدم لإنشاء عملية توصيل مياه جديدة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "truck_id": 1,
  "mosque_id": 1,
  "need_request_id": 5,
  "liters_delivered": 5000,
  "expected_delivery_date": "2025-12-10"
}
```

---

### 7.4 تحديث حالة توصيل (Admin/Logistics Supervisor فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/deliveries/{id}/status
```

**الوصف:** يستخدم لتحديث حالة عملية التوصيل.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "status": "delivered"
}
```

**القيم المتاحة لـ status:**
- `pending` - قيد الانتظار
- `in-transit` - في الطريق
- `delivered` - تم التسليم
- `cancelled` - ملغي

---

### 7.5 رفع إثبات التوصيل (Admin/Logistics Supervisor فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/deliveries/{id}/proof
```

**الوصف:** يستخدم لرفع صورة إثبات التوصيل.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**مثال Request (FormData):**
```
image: [ملف الصورة - jpg, jpeg, png, gif, webp - الحد الأقصى 5MB]
delivery_latitude: 24.7136 (اختياري)
delivery_longitude: 46.6753 (اختياري)
notes: "تم التسليم بنجاح" (اختياري)
```

---

## 8. الحملات (Campaigns)

### 8.1 جلب قائمة الحملات
```
GET https://809147dc35e2.ngrok-free.app/api/campaigns
```

**الوصف:** يستخدم لجلب قائمة الحملات الترويجية.

**Query Parameters (اختياري):**
- `active` - فلترة حسب النشاط (true/false)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 8.2 جلب تفاصيل حملة
```
GET https://809147dc35e2.ngrok-free.app/api/campaigns/{id}
```

**الوصف:** يستخدم لجلب تفاصيل حملة محددة.

---

### 8.3 إنشاء حملة جديدة (Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/campaigns
```

**الوصف:** يستخدم لإنشاء حملة ترويجية جديدة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "title": "حملة رمضان",
  "description": "حملة توزيع المياه في شهر رمضان",
  "start_date": "2025-03-01",
  "end_date": "2025-03-30",
  "active": true
}
```

---

### 8.4 تحديث حملة (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/campaigns/{id}
```

**الوصف:** يستخدم لتحديث بيانات حملة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

### 8.5 حذف حملة (Admin فقط)
```
DELETE https://809147dc35e2.ngrok-free.app/api/campaigns/{id}
```

**الوصف:** يستخدم لحذف حملة.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

## 9. الإعلانات (Ads)

### 9.1 جلب قائمة الإعلانات
```
GET https://809147dc35e2.ngrok-free.app/api/ads
```

**الوصف:** يستخدم لجلب قائمة الإعلانات.

**Query Parameters (اختياري):**
- `position` - فلترة حسب موقع الإعلان
- `active` - فلترة حسب النشاط (true/false)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة

---

### 9.2 جلب تفاصيل إعلان
```
GET https://809147dc35e2.ngrok-free.app/api/ads/{id}
```

**الوصف:** يستخدم لجلب تفاصيل إعلان محدد.

---

### 9.3 إنشاء إعلان جديد (Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/ads
```

**الوصف:** يستخدم لإنشاء إعلان جديد.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**مثال Request (FormData):**
```
title: "إعلان الصفحة الرئيسية"
content: "محتوى الإعلان هنا"
position: "homepage_banner"
image: [ملف الصورة - اختياري]
link_url: "https://example.com" (اختياري)
active: true
```

---

### 9.4 تحديث إعلان (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/ads/{id}
```

**الوصف:** يستخدم لتحديث إعلان.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

---

### 9.5 حذف إعلان (Admin فقط)
```
DELETE https://809147dc35e2.ngrok-free.app/api/ads/{id}
```

**الوصف:** يستخدم لحذف إعلان.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

## 10. نصوص المحتوى (Content Texts)

### 10.1 جلب جميع نصوص المحتوى
```
GET https://809147dc35e2.ngrok-free.app/api/content-texts
```

**الوصف:** يستخدم لجلب جميع النصوص الديناميكية للتطبيق (عربي وإنجليزي).

---

### 10.2 جلب نص محتوى بالمفتاح
```
GET https://809147dc35e2.ngrok-free.app/api/content-texts/{key}
```

**الوصف:** يستخدم لجلب نص محدد باستخدام المفتاح.

**مثال Request:**
```
GET https://809147dc35e2.ngrok-free.app/api/content-texts/welcome_message
```

---

### 10.3 إنشاء/تحديث نص محتوى (Admin فقط)
```
POST https://809147dc35e2.ngrok-free.app/api/content-texts
```

**الوصف:** يستخدم لإنشاء نص جديد أو تحديث موجود.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "key": "welcome_message",
  "value_ar": "مرحباً بكم في روائع الحرم",
  "value_en": "Welcome to Rawae Al-Haram"
}
```

---

### 10.4 تحديث نص محتوى (Admin فقط)
```
PUT https://809147dc35e2.ngrok-free.app/api/content-texts/{key}
```

**الوصف:** يستخدم لتحديث نص محتوى موجود.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Request:**
```json
{
  "value_ar": "مرحباً بكم",
  "value_en": "Welcome"
}
```

---

### 10.5 حذف نص محتوى (Admin فقط)
```
DELETE https://809147dc35e2.ngrok-free.app/api/content-texts/{key}
```

**الوصف:** يستخدم لحذف نص محتوى.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

---

## 11. لوحة التحكم (Dashboard)

### 11.1 جلب إحصائيات لوحة التحكم
```
GET https://809147dc35e2.ngrok-free.app/api/dashboard/stats
```

**الوصف:** يستخدم لجلب إحصائيات لوحة التحكم حسب دور المستخدم.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Response للـ Admin:**
```json
{
  "success": true,
  "data": {
    "total_donations": 45230,
    "water_delivered": 12450,
    "mosques_needing_supply": 23,
    "active_fleet": 15
  }
}
```

**مثال Response للـ Auditor:**
```json
{
  "success": true,
  "data": {
    "total_revenue": 125000,
    "verified_donations": 1245,
    "pending_verification": 12,
    "compliance_score": 98
  }
}
```

**مثال Response للـ Investor:**
```json
{
  "success": true,
  "data": {
    "total_impact": 125000,
    "mosques_served": 127,
    "water_delivered": 12450,
    "roi": 15.5
  }
}
```

---

### 11.2 جلب الأنشطة الأخيرة
```
GET https://809147dc35e2.ngrok-free.app/api/dashboard/activities
```

**الوصف:** يستخدم لجلب آخر الأنشطة في النظام.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters (اختياري):**
- `limit` - عدد الأنشطة (1-100، افتراضي: 10)

---

### 11.3 جلب بيانات نشاط التبرعات
```
GET https://809147dc35e2.ngrok-free.app/api/dashboard/donation-activity
```

**الوصف:** يستخدم لجلب بيانات الرسم البياني لنشاط التبرعات.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters (اختياري):**
- `period` - الفترة الزمنية (week, month, year - افتراضي: month)

**مثال Response:**
```json
{
  "success": true,
  "data": {
    "labels": ["Week 1", "Week 2", "Week 3", "Week 4"],
    "values": [5000, 7500, 6000, 8500]
  }
}
```

---

## 12. التقارير (Reports)

### 12.1 جلب سجل التبرعات (Admin/Auditor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/reports/donation-ledger
```

**الوصف:** يستخدم لجلب سجل التبرعات للتقارير المالية.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters (اختياري):**
- `date_from` - من تاريخ
- `date_to` - إلى تاريخ
- `verified` - فلترة حسب التحقق (true/false)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة (افتراضي: 50)

---

### 12.2 تصدير التقرير كـ PDF (Admin/Auditor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/reports/export/pdf
```

**الوصف:** يستخدم لتصدير سجل التبرعات كملف PDF.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters:** نفس معاملات donation-ledger

**مثال Response:**
```json
{
  "success": true,
  "file_url": "https://example.com/storage/reports/donation-ledger-2025-12-07.pdf"
}
```

---

### 12.3 تصدير التقرير كـ Excel (Admin/Auditor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/reports/export/excel
```

**الوصف:** يستخدم لتصدير سجل التبرعات كملف Excel.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**Query Parameters:** نفس معاملات donation-ledger

**مثال Response:**
```json
{
  "success": true,
  "file_url": "https://example.com/storage/reports/donation-ledger-2025-12-07.xlsx"
}
```

---

## 13. تأثير المستثمر (Investor Impact)

### 13.1 جلب مقاييس التأثير (Admin/Investor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/investor-impact/metrics
```

**الوصف:** يستخدم لجلب مقاييس تأثير الاستثمار.

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Response:**
```json
{
  "success": true,
  "data": {
    "roi": 15.5,
    "total_impact": 125000,
    "mosques_served": 127,
    "total_donations": 45230,
    "water_delivered": 12450
  }
}
```

---

### 13.2 جلب بيانات القمع (Admin/Investor فقط)
```
GET https://809147dc35e2.ngrok-free.app/api/investor-impact/funnel
```

**الوصف:** يستخدم لجلب بيانات قمع التحويل (من التبرع إلى التأثير).

**Headers المطلوبة:**
```
Authorization: Bearer {token}
```

**مثال Response:**
```json
{
  "success": true,
  "data": [
    {
      "stage": { "en": "Donations", "ar": "التبرعات" },
      "value": 100
    },
    {
      "stage": { "en": "Delivery", "ar": "التسليم" },
      "value": 85
    },
    {
      "stage": { "en": "Impact", "ar": "التأثير" },
      "value": 75
    }
  ]
}
```

---

## ملخص أدوار المستخدمين والصلاحيات

| الدور | الوصف |
|-------|-------|
| `admin` | مدير النظام - صلاحية كاملة |
| `donor` | متبرع - يمكنه التبرع وعرض سجل تبرعاته |
| `mosque_admin` | مدير مسجد - يدير طلبات الحاجة وصور الخزان |
| `auditor` | مدقق - يمكنه التحقق من التبرعات والوصول للتقارير |
| `investor` | مستثمر - يمكنه الوصول لمقاييس التأثير |
| `logistics_supervisor` | مشرف لوجستي - يدير الشاحنات والتوصيلات |

---

## صيغ الأخطاء الشائعة

### خطأ التحقق (422)
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### غير مصرح (401)
```json
{
  "success": false,
  "message": "Unauthorized."
}
```

### ممنوع الوصول (403)
```json
{
  "success": false,
  "message": "Unauthorized. Access denied."
}
```

### غير موجود (404)
```json
{
  "success": false,
  "message": "Resource not found."
}
```

---

## ملاحظات مهمة

1. **جميع الطلبات تحتاج Header:**
   ```
   Content-Type: application/json
   Accept: application/json
   ```

2. **للطلبات المحمية، أضف:**
   ```
   Authorization: Bearer {token}
   ```

3. **لرفع الملفات، استخدم:**
   ```
   Content-Type: multipart/form-data
   ```

4. **Token صالح لمدة 24 ساعة** ويمكن تجديده باستخدام `/api/auth/refresh`


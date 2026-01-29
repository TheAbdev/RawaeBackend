# Rawae Al Haram - Backend API Specification

## Table of Contents
1. [Database Schema](#database-schema)
2. [API Endpoints](#api-endpoints)
3. [Authentication & Authorization](#authentication--authorization)
4. [Data Models](#data-models)
5. [File Uploads](#file-uploads)
6. [Payment Integration](#payment-integration)

---


## Database Schema

### 1. Users Table
Stores all user accounts with their roles and authentication information.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor') NOT NULL,
    phone VARCHAR(20) NULL,
    email_verified_at TIMESTAMP NULL,
    nafath_id VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
);
```

### 2. Mosques Table
Stores mosque information including location and capacity.

```sql
CREATE TABLE mosques (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    capacity INT UNSIGNED NOT NULL COMMENT 'Water capacity in liters',
    current_water_level INT UNSIGNED DEFAULT 0,
    required_water_level INT UNSIGNED NOT NULL,
    need_level ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    need_score INT UNSIGNED DEFAULT 0 COMMENT 'AI calculated need score (0-100)',
    description TEXT NULL,
    mosque_admin_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (mosque_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_location (latitude, longitude),
    INDEX idx_need_score (need_score),
    INDEX idx_mosque_admin (mosque_admin_id)
);
```

### 3. Donations Table
Stores donation transactions.

```sql
CREATE TABLE donations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    donor_id BIGINT UNSIGNED NOT NULL,
    mosque_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('apple_pay', 'mada', 'stc_pay', 'other') NOT NULL,
    payment_transaction_id VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    verified BOOLEAN DEFAULT FALSE,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_donor (donor_id),
    INDEX idx_mosque (mosque_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### 4. Need Requests Table
Stores water need requests from mosque admins.

```sql
CREATE TABLE need_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mosque_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    water_quantity INT UNSIGNED NOT NULL COMMENT 'Quantity in liters',
    status ENUM('pending', 'approved', 'rejected', 'fulfilled') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mosque (mosque_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### 5. Tank Images Table
Stores tank images uploaded by mosque admins.

```sql
CREATE TABLE tank_images (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mosque_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mosque (mosque_id),
    INDEX idx_created_at (created_at)
);
```

### 6. Trucks Table
Stores truck information for logistics.

```sql
CREATE TABLE trucks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    truck_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    capacity INT UNSIGNED NOT NULL COMMENT 'Water capacity in liters',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    current_latitude DECIMAL(10, 8) NULL,
    current_longitude DECIMAL(11, 8) NULL,
    last_location_update TIMESTAMP NULL,
    assigned_driver_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_truck_id (truck_id),
    INDEX idx_status (status),
    INDEX idx_location (current_latitude, current_longitude)
);
```

### 7. Deliveries Table
Stores delivery records.

```sql
CREATE TABLE deliveries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    truck_id BIGINT UNSIGNED NOT NULL,
    mosque_id BIGINT UNSIGNED NOT NULL,
    need_request_id BIGINT UNSIGNED NULL,
    liters_delivered INT UNSIGNED NOT NULL,
    delivery_latitude DECIMAL(10, 8) NULL,
    delivery_longitude DECIMAL(11, 8) NULL,
    proof_image_path VARCHAR(500) NULL,
    proof_image_url VARCHAR(500) NULL,
    status ENUM('pending', 'in-transit', 'delivered', 'cancelled') DEFAULT 'pending',
    expected_delivery_date DATE NULL,
    actual_delivery_date TIMESTAMP NULL,
    delivered_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (truck_id) REFERENCES trucks(id) ON DELETE CASCADE,
    FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
    FOREIGN KEY (need_request_id) REFERENCES need_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (delivered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_truck (truck_id),
    INDEX idx_mosque (mosque_id),
    INDEX idx_status (status),
    INDEX idx_delivery_date (actual_delivery_date)
);
```

### 8. Campaigns Table
Stores marketing campaigns.

```sql
CREATE TABLE campaigns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (active)
);
```

### 9. Ads Table
Stores advertisement content.

```sql
CREATE TABLE ads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    position VARCHAR(100) NOT NULL COMMENT 'e.g., homepage-top, sidebar',
    image_path VARCHAR(500) NULL,
    image_url VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_position (position),
    INDEX idx_active (active)
);
```

### 10. Content Texts Table
Stores translatable content texts.

```sql
CREATE TABLE content_texts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value_ar TEXT NOT NULL,
    value_en TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_key (key)
);
```

### 11. Activity Logs Table
Stores system activity logs for dashboard.

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type ENUM('donation', 'delivery', 'mosque', 'user', 'campaign', 'other') NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    related_id BIGINT UNSIGNED NULL COMMENT 'ID of related entity',
    related_type VARCHAR(100) NULL COMMENT 'Model name of related entity',
    message_ar TEXT NOT NULL,
    message_en TEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_type (type),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);
```

### 12. Password Reset Tokens Table
For password reset functionality.

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_token (token)
);
```

---

## API Endpoints

### Authentication Endpoints

#### POST `/api/auth/login`
Login with username/email and password.

**Request:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response (200):**
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

**Response (401):**
```json
{
  "success": false,
  "message": "Invalid username or password"
}
```

#### POST `/api/auth/register`
Register new donor account.

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "username": "johndoe",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+966501234567"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 10,
      "name": "John Doe",
      "email": "john@example.com",
      "username": "johndoe",
      "role": "donor"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### POST `/api/auth/logout`
Logout current user.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### POST `/api/auth/refresh`
Refresh authentication token.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### POST `/api/auth/forgot-password`
Request password reset.

**Request:**
```json
{
  "email": "user@example.com"
}
```

#### POST `/api/auth/reset-password`
Reset password with token.

**Request:**
```json
{
  "token": "reset-token",
  "email": "user@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

### Mosques Endpoints

#### GET `/api/mosques`
Get list of mosques with filtering and pagination.

**Query Parameters:**
- `search` (string, optional): Search by name
- `need_level` (enum: Low, Medium, High, optional)
- `min_need_score` (integer, optional)
- `page` (integer, default: 1)
- `per_page` (integer, default: 15)
- `sort_by` (string, default: "need_score"): need_score, name, created_at
- `sort_order` (enum: asc, desc, default: "desc")

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 101,
        "name": "Masjid Al-Haram Area 1",
        "location": "Makkah, Saudi Arabia",
        "latitude": "21.4225",
        "longitude": "39.8262",
        "capacity": 1500,
        "current_water_level": 2000,
        "required_water_level": 10000,
        "need_level": "High",
        "need_score": 95,
        "description": "One of the most important mosques",
        "mosque_admin": {
          "id": 5,
          "name": "Mosque Admin"
        }
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 10,
    "last_page": 1
  }
}
```

#### GET `/api/mosques/{id}`
Get single mosque details.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Masjid Al-Haram Area 1",
    "location": "Makkah, Saudi Arabia",
    "latitude": "21.4225",
    "longitude": "39.8262",
    "capacity": 1500,
    "current_water_level": 2000,
    "required_water_level": 10000,
    "need_level": "High",
    "need_score": 95,
    "description": "One of the most important mosques",
    "mosque_admin": {
      "id": 5,
      "name": "Mosque Admin"
    },
    "recent_donations": [...],
    "recent_deliveries": [...]
  }
}
```

#### POST `/api/mosques`
Create new mosque (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "name": "New Mosque",
  "location": "Riyadh, Saudi Arabia",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "capacity": 1000,
  "required_water_level": 8000,
  "description": "Mosque description"
}
```

#### PUT `/api/mosques/{id}`
Update mosque (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:** Same as POST

#### DELETE `/api/mosques/{id}`
Delete mosque (Admin only).

**Headers:** `Authorization: Bearer {token}`

---

### Donations Endpoints

#### GET `/api/donations`
Get list of donations with filtering.

**Query Parameters:**
- `donor_id` (integer, optional)
- `mosque_id` (integer, optional)
- `status` (enum: pending, completed, failed, cancelled, optional)
- `date_from` (date, optional)
- `date_to` (date, optional)
- `page` (integer, default: 1)
- `per_page` (integer, default: 15)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "donor": {
          "id": 4,
          "name": "Ahmed Ali",
          "email": "ahmed@example.com"
        },
        "mosque": {
          "id": 101,
          "name": "Masjid Al-Haram Area 1"
        },
        "amount": "500.00",
        "payment_method": "mada",
        "payment_transaction_id": "TXN123456",
        "status": "completed",
        "verified": true,
        "verified_by": {
          "id": 2,
          "name": "Audit Officer"
        },
        "verified_at": "2025-01-20T10:30:00Z",
        "created_at": "2025-01-20T08:15:00Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

#### GET `/api/donations/{id}`
Get single donation details.

#### POST `/api/donations`
Create new donation (Donor only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "mosque_id": 101,
  "amount": 500.00,
  "payment_method": "mada",
  "payment_transaction_id": "TXN123456"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 100,
    "donor_id": 4,
    "mosque_id": 101,
    "amount": "500.00",
    "payment_method": "mada",
    "status": "pending",
    "created_at": "2025-01-20T12:00:00Z"
  }
}
```

#### GET `/api/donations/my-history`
Get current user's donation history (Donor only).

**Headers:** `Authorization: Bearer {token}`

#### PUT `/api/donations/{id}/verify`
Verify donation (Admin/Auditor only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "verified": true
}
```

#### PUT `/api/donations/{id}/status`
Update donation status (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "status": "completed"
}
```

---

### Need Requests Endpoints

#### GET `/api/need-requests`
Get list of need requests.

**Query Parameters:**
- `mosque_id` (integer, optional)
- `status` (enum: pending, approved, rejected, fulfilled, optional)
- `page` (integer, default: 1)

**Headers:** `Authorization: Bearer {token}`

#### GET `/api/need-requests/my-mosque`
Get need requests for current user's mosque (Mosque Admin only).

**Headers:** `Authorization: Bearer {token}`

#### POST `/api/need-requests`
Create new need request (Mosque Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "mosque_id": 101,
  "water_quantity": 10000
}
```

#### PUT `/api/need-requests/{id}/approve`
Approve need request (Admin only).

**Headers:** `Authorization: Bearer {token}`

#### PUT `/api/need-requests/{id}/reject`
Reject need request (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "rejection_reason": "Insufficient funds"
}
```

---

### Tank Images Endpoints

#### GET `/api/tank-images`
Get tank images.

**Query Parameters:**
- `mosque_id` (integer, optional)
- `page` (integer, default: 1)

#### GET `/api/tank-images/my-mosque`
Get tank images for current user's mosque (Mosque Admin only).

**Headers:** `Authorization: Bearer {token}`

#### POST `/api/tank-images`
Upload tank image (Mosque Admin only).

**Headers:** `Authorization: Bearer {token}`, `Content-Type: multipart/form-data`

**Request (Form Data):**
- `mosque_id` (integer, required)
- `image` (file, required)
- `description` (string, optional)

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "mosque_id": 101,
    "image_url": "https://storage.example.com/tanks/tank-1.jpg",
    "description": "Tank status",
    "created_at": "2025-01-20T12:00:00Z"
  }
}
```

#### DELETE `/api/tank-images/{id}`
Delete tank image (Mosque Admin only).

**Headers:** `Authorization: Bearer {token}`

---

### Trucks Endpoints

#### GET `/api/trucks`
Get list of trucks.

**Query Parameters:**
- `status` (enum: active, inactive, maintenance, optional)
- `page` (integer, default: 1)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "truck_id": "TR-001",
        "name": "Truck 001",
        "capacity": 10000,
        "status": "active",
        "current_latitude": "21.4225",
        "current_longitude": "39.8262",
        "last_location_update": "2025-01-20T12:00:00Z",
        "assigned_driver": {
          "id": 6,
          "name": "Driver Name"
        }
      }
    ]
  }
}
```

#### GET `/api/trucks/{id}`
Get single truck details.

#### POST `/api/trucks`
Create new truck (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "truck_id": "TR-010",
  "name": "Truck 010",
  "capacity": 10000
}
```

#### PUT `/api/trucks/{id}`
Update truck (Admin only).

**Headers:** `Authorization: Bearer {token}`

#### PUT `/api/trucks/{id}/location`
Update truck location (Logistics Supervisor/Driver only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "latitude": 21.4225,
  "longitude": 39.8262
}
```

---

### Deliveries Endpoints

#### GET `/api/deliveries`
Get list of deliveries.

**Query Parameters:**
- `truck_id` (integer, optional)
- `mosque_id` (integer, optional)
- `status` (enum: pending, in-transit, delivered, cancelled, optional)
- `page` (integer, default: 1)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "truck": {
          "id": 1,
          "truck_id": "TR-001",
          "name": "Truck 001"
        },
        "mosque": {
          "id": 101,
          "name": "Masjid Al-Haram Area 1"
        },
        "liters_delivered": 5000,
        "proof_image_url": "https://storage.example.com/proofs/delivery-1.jpg",
        "status": "delivered",
        "expected_delivery_date": "2025-01-22",
        "actual_delivery_date": "2025-01-20T12:35:00Z",
        "delivered_by": {
          "id": 6,
          "name": "Driver Name"
        },
        "created_at": "2025-01-20T08:00:00Z"
      }
    ]
  }
}
```

#### GET `/api/deliveries/{id}`
Get single delivery details.

#### POST `/api/deliveries`
Create new delivery (Admin/Logistics Supervisor only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "truck_id": 1,
  "mosque_id": 101,
  "need_request_id": 5,
  "liters_delivered": 5000,
  "expected_delivery_date": "2025-01-22"
}
```

#### PUT `/api/deliveries/{id}/status`
Update delivery status.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "status": "in-transit"
}
```

#### POST `/api/deliveries/{id}/proof`
Upload delivery proof image.

**Headers:** `Authorization: Bearer {token}`, `Content-Type: multipart/form-data`

**Request (Form Data):**
- `image` (file, required)
- `delivery_latitude` (decimal, optional)
- `delivery_longitude` (decimal, optional)
- `notes` (string, optional)

---

### Campaigns Endpoints

#### GET `/api/campaigns`
Get list of campaigns.

**Query Parameters:**
- `active` (boolean, optional)
- `page` (integer, default: 1)

#### GET `/api/campaigns/{id}`
Get single campaign.

#### POST `/api/campaigns`
Create campaign (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "title": "Ramadan Water Drive",
  "description": "Help provide water during Ramadan",
  "start_date": "2025-03-01",
  "end_date": "2025-04-30",
  "active": true
}
```

#### PUT `/api/campaigns/{id}`
Update campaign (Admin only).

**Headers:** `Authorization: Bearer {token}`

#### DELETE `/api/campaigns/{id}`
Delete campaign (Admin only).

**Headers:** `Authorization: Bearer {token}`

---

### Ads Endpoints

#### GET `/api/ads`
Get list of ads.

**Query Parameters:**
- `position` (string, optional)
- `active` (boolean, optional)
- `page` (integer, default: 1)

#### GET `/api/ads/{id}`
Get single ad.

#### POST `/api/ads`
Create ad (Admin only).

**Headers:** `Authorization: Bearer {token}`, `Content-Type: multipart/form-data`

**Request (Form Data):**
- `title` (string, required)
- `content` (text, required)
- `position` (string, required)
- `image` (file, optional)
- `link_url` (string, optional)
- `active` (boolean, default: true)

#### PUT `/api/ads/{id}`
Update ad (Admin only).

**Headers:** `Authorization: Bearer {token}`

#### DELETE `/api/ads/{id}`
Delete ad (Admin only).

**Headers:** `Authorization: Bearer {token}`

---

### Content Texts Endpoints

#### GET `/api/content-texts`
Get all content texts.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "homepage.welcome": {
      "key": "homepage.welcome",
      "value_ar": "مرحباً بكم في رواء الحرم",
      "value_en": "Welcome to Rawae Al Haram"
    },
    "homepage.subtitle": {
      "key": "homepage.subtitle",
      "value_ar": "دعم المساجد بالمياه النظيفة",
      "value_en": "Supporting mosques with clean water"
    }
  }
}
```

#### GET `/api/content-texts/{key}`
Get single content text by key.

#### POST `/api/content-texts`
Create or update content text (Admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "key": "homepage.welcome",
  "value_ar": "مرحباً بكم في رواء الحرم",
  "value_en": "Welcome to Rawae Al Haram"
}
```

#### PUT `/api/content-texts/{key}`
Update content text (Admin only).

**Headers:** `Authorization: Bearer {token}`

#### DELETE `/api/content-texts/{key}`
Delete content text (Admin only).

**Headers:** `Authorization: Bearer {token}`

---

### Dashboard Endpoints

#### GET `/api/dashboard/stats`
Get dashboard statistics based on user role.

**Headers:** `Authorization: Bearer {token}`

**Response (200) - Admin:**
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

**Response (200) - Auditor:**
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

**Response (200) - Investor:**
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

#### GET `/api/dashboard/activities`
Get recent activities for dashboard.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `limit` (integer, default: 10)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "donation",
      "message_ar": "تبرع جديد بقيمة 1,500 دولار من نور الشمري",
      "message_en": "New donation of $1,500 from Noor Al-Shammari",
      "created_at": "2025-01-20T10:00:00Z"
    }
  ]
}
```

#### GET `/api/dashboard/donation-activity`
Get donation activity chart data.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `period` (enum: week, month, year, default: "month")

**Response (200):**
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

### Reports Endpoints

#### GET `/api/reports/donation-ledger`
Get donation ledger for reports (Admin/Auditor only).

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `date_from` (date, optional)
- `date_to` (date, optional)
- `verified` (boolean, optional)
- `page` (integer, default: 1)
- `per_page` (integer, default: 50)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "date": "2025-01-20",
        "donor": {
          "id": 4,
          "name": "Ahmed Ali"
        },
        "amount": "500.00",
        "verified": true
      }
    ],
    "summary": {
      "total_amount": 45230.00,
      "verified_amount": 40000.00,
      "pending_amount": 5230.00
    }
  }
}
```

#### GET `/api/reports/export/pdf`
Export donation ledger as PDF (Admin/Auditor only).

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** Same as donation-ledger

#### GET `/api/reports/export/excel`
Export donation ledger as Excel (Admin/Auditor only).

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** Same as donation-ledger

---

### Investor Impact Endpoints

#### GET `/api/investor-impact/metrics`
Get investor impact metrics (Admin/Investor only).

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
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

#### GET `/api/investor-impact/funnel`
Get funnel flow data (Admin/Investor only).

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "stage": {
        "en": "Donations",
        "ar": "التبرعات"
      },
      "value": 100
    },
    {
      "stage": {
        "en": "Delivery",
        "ar": "التسليم"
      },
      "value": 85
    },
    {
      "stage": {
        "en": "Impact",
        "ar": "التأثير"
      },
      "value": 75
    }
  ]
}
```

---

## Authentication & Authorization

### JWT Token Structure
- Token expires in 24 hours
- Refresh token expires in 30 days
- Token includes: user_id, role, email

### Role-Based Access Control

| Endpoint | Admin | Auditor | Investor | Donor | Mosque Admin | Logistics Supervisor |
|----------|-------|---------|----------|-------|--------------|---------------------|
| GET /api/mosques | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /api/mosques | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| GET /api/donations | ✅ | ✅ | ✅ | ✅ (own only) | ❌ | ❌ |
| POST /api/donations | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| PUT /api/donations/{id}/verify | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| GET /api/need-requests/my-mosque | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| POST /api/need-requests | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| POST /api/tank-images | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| GET /api/trucks | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| PUT /api/trucks/{id}/location | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| POST /api/deliveries | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| POST /api/campaigns | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| GET /api/reports/* | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| GET /api/investor-impact/* | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |

---

## Data Models

### User Model
```php
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@example.com",
  "username": "admin",
  "role": "admin",
  "phone": "+966501234567",
  "is_active": true,
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Mosque Model
```php
{
  "id": 101,
  "name": "Masjid Al-Haram Area 1",
  "location": "Makkah, Saudi Arabia",
  "latitude": "21.4225",
  "longitude": "39.8262",
  "capacity": 1500,
  "current_water_level": 2000,
  "required_water_level": 10000,
  "need_level": "High",
  "need_score": 95,
  "description": "One of the most important mosques",
  "mosque_admin_id": 5,
  "is_active": true,
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Donation Model
```php
{
  "id": 1,
  "donor_id": 4,
  "mosque_id": 101,
  "amount": "500.00",
  "payment_method": "mada",
  "payment_transaction_id": "TXN123456",
  "status": "completed",
  "verified": true,
  "verified_by": 2,
  "verified_at": "2025-01-20T10:30:00Z",
  "created_at": "2025-01-20T08:15:00Z",
  "updated_at": "2025-01-20T10:30:00Z"
}
```

---

## File Uploads

### Supported File Types
- Images: jpg, jpeg, png, gif, webp
- Max file size: 5MB per file

### Storage Structure
```
storage/
  app/
    public/
      tanks/
        {mosque_id}/
          {timestamp}_{filename}
      proofs/
        {delivery_id}/
          {timestamp}_{filename}
      ads/
        {timestamp}_{filename}
```

### Upload Endpoints
- POST `/api/tank-images` - Upload tank image
- POST `/api/deliveries/{id}/proof` - Upload delivery proof
- POST `/api/ads` - Upload ad image

---

## Payment Integration

### Supported Payment Methods
1. **Apple Pay**
2. **Mada** (Saudi debit card network)
3. **STC Pay** (Saudi mobile payment)

### Payment Flow
1. Frontend initiates payment with amount and method
2. Backend creates donation record with status "pending"
3. Backend calls payment gateway API
4. Payment gateway returns transaction ID
5. Backend updates donation with transaction ID
6. On payment success, status changes to "completed"
7. On payment failure, status changes to "failed"

### Payment Gateway Response
```json
{
  "success": true,
  "transaction_id": "TXN123456",
  "amount": 500.00,
  "payment_method": "mada",
  "status": "completed"
}
```

---

## Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error message for field"]
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page` (integer, default: 1)
- `per_page` (integer, default: 15, max: 100)

**Response Format:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

---

## Notes for Backend Developer

1. **AI Need Score Calculation**: The `need_score` for mosques should be calculated based on:
   - Current water level vs required water level
   - Time since last delivery
   - Historical usage patterns
   - Geographic factors

2. **Real-time Location Updates**: Consider using WebSockets or Server-Sent Events for real-time truck location updates.

3. **Image Processing**: Resize and optimize uploaded images before storage.

4. **Payment Gateway**: Integrate with actual payment gateways (Apple Pay, Mada, STC Pay) - currently mocked.

5. **Email Notifications**: Send email notifications for:
   - New donations
   - Delivery confirmations
   - Need request approvals/rejections

6. **Activity Logging**: Log all important actions to `activity_logs` table for dashboard display.

7. **Caching**: Consider caching frequently accessed data like mosque list, stats, etc.

8. **Rate Limiting**: Implement rate limiting for API endpoints to prevent abuse.

9. **Validation**: Validate all input data according to the schema definitions.

10. **Localization**: All text content should support Arabic (ar) and English (en) translations.


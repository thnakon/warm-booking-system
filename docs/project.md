# Hotel Booking System (Single Hotel)

> ระบบจองโรงแรมครบวงจร สำหรับโรงแรมเดียว (ไม่ใช่ SaaS) รองรับการจองจากหลายช่องทางโดยไม่เกิดการชนของการจอง

---

## 1. Vision & Goals

* สร้างระบบจองโรงแรมที่ **เสถียร ใช้งานจริงได้** สำหรับโรงแรมเดียว
* เคสเป้าหมาย: รีสอร์ทขนาดเล็ก **รวม 8 ห้อง**

  * ห้องเล็ก: 4 ห้อง
  * ห้องกลาง: 2 ห้อง
  * ห้องใหญ่: 2 ห้อง
* รองรับการจองจาก: เว็บ, หน้าฟรอนต์ (รับโทร/LINE/Walk-in), และต่อยอดไป OTA ในอนาคต
* ป้องกัน **Overbooking** ด้วย Booking Engine กลางเพียงชุดเดียว
* มี Back Office สำหรับพนักงาน + ระบบสมาชิก (Loyalty)
* โฟกัสตลาดไทย: ภาษาไทย, โอนเงิน/แนบสลิป, ใช้งานง่าย

---

## 2. Tech Stack

**Backend**

* Laravel (Monolith)
* PHP 8.x
* MySQL
* Laravel Queue (เริ่มจาก Database Queue → ต่อ Redis ได้)
* Cache (File/Redis)
* Auth: Laravel Breeze / Jetstream
* Permission: spatie/laravel-permission

**Frontend**

* Blade
* Tailwind CSS
* Alpine.js
* Livewire

**Infra / Ops**

* Nginx + PHP-FPM
* Supervisor (Queue Worker)
* Cron
* Storage: Local / S3-compatible (สำหรับสลิป/เอกสาร)

**Integration (Phase ถัดไป)**

* Email (Laravel Mail)
* LINE Notify / LINE OA
* Payment Gateway (PromptPay/Opn/Stripe)

---

## 3. Core Principles

* Single Source of Truth สำหรับ Availability
* ทุก Channel ต้องเรียก Booking Engine เดียวกัน
* ใช้ Transaction + Lock เพื่อกันการชน
* แยก Module ชัดเจน แต่ยังอยู่ใน Monolith
* เริ่มจากของจำเป็นก่อน แล้วค่อยขยาย

---

## 4. Modules

### 4.1 Public Booking

* ค้นหาห้องว่าง (เลือกวันเข้า/ออก)
* แสดง Room Type + ราคา
* กรอกข้อมูลผู้เข้าพัก
* สร้าง Booking (HOLD → CONFIRMED)
* แจ้งเตือนทาง Email/LINE (ผ่าน Queue)

### 4.2 Booking Engine (Core)

* ตรวจ Availability
* Lock แถวข้อมูล (SELECT ... FOR UPDATE)
* สร้าง Booking แบบ Transaction
* สถานะ: HOLD, CONFIRMED, CANCELLED, NO_SHOW
* Update Inventory/Availability ต่อวัน

### 4.3 Back Office (Staff/Admin)

* Dashboard (Today Check-in/Check-out)
* Booking Management: ดู/แก้/ย้ายห้อง/ยกเลิก
* Manual Booking (รับโทร/LINE/Walk-in)
* Calendar View (Occupancy)
* Room & Rate Management
* Customer Profiles
* Staff & Roles (Admin, Front Desk, Manager)

### 4.4 Member System (Hotel Loyalty)

* สมัครสมาชิก / Login
* ดูประวัติการจอง
* Member Rate / ส่วนลด
* Points: ได้จากการจอง / ใช้เป็นส่วนลด

### 4.5 Payment (Phase 2)

* สถานะ: Pending, Paid, Refunded
* วิธีชำระ: โอนเงิน/แนบสลิป, เงินสดหน้าเคาน์เตอร์
* เก็บ Payment Logs + หลักฐาน

### 4.6 Notification

* Email Confirmation
* LINE Notify (อนาคต)
* ใช้ Queue สำหรับงานเบื้องหลัง

### 4.7 Audit & Logs

* บันทึกการเปลี่ยนแปลง Booking
* ใครทำอะไร เมื่อไหร่
* ใช้สำหรับตรวจสอบย้อนหลัง

---

## 5. Data Model (High Level)

* users
* roles, permissions
* customers
* room_types (small, medium, large)
* rooms (รวม 8 ห้อง)
* rate_plans
* availability (date, room_type_id, qty_available)
* bookings
* booking_items
* payments
* payment_logs
* members
* points_logs
* audit_logs

> หมายเหตุ: สำหรับรีสอร์ทขนาดเล็ก ใช้การคุมสต็อกระดับ **Room Type ต่อวัน** ก่อน (ไม่ต้อง map เป็นรายห้องใน Phase แรก)

---

## 6. Booking Flow (No Overbooking)

1. User/Staff เลือกวัน + ห้อง
2. System BEGIN TRANSACTION
3. Lock availability row (FOR UPDATE)
4. ถ้าว่าง → create booking (HOLD)
5. COMMIT
6. เมื่อชำระเงิน/ยืนยัน → update เป็น CONFIRMED
7. Update availability

> ทุก Channel ต้องใช้ Flow นี้เท่านั้น

---

## 7. Roles

* Admin: จัดการทุกอย่าง
* Manager: ดูรายงาน/ราคา
* Staff: จัดการ Booking หน้าฟรอนต์

---

## 8. UI/UX

* ใช้ Tailwind CSS
* เน้นใช้งานง่ายสำหรับพนักงาน
* Responsive (Tablet ใช้หน้าเคาน์เตอร์ได้)
* Calendar View สำหรับดูห้องว่าง

---

## 9. Roadmap

### Phase 1: Core (MVP สำหรับรีสอร์ท 8 ห้อง)

* Auth + Roles
* RoomType (เล็ก/กลาง/ใหญ่) + Rooms (8 ห้อง)
* RatePlan (Standard / Member)
* Availability ต่อวัน (คุมสต็อกตาม RoomType)
* Booking Engine (Transaction + Lock)
* Manual Booking (Staff รับโทร/LINE/Walk-in)
* Public Booking Page (ค้นหาวัน + แสดงห้องว่าง + จอง)

### Phase 2: Back Office

* Dashboard

* Booking Management

* Calendar View

* Customer Profiles

* Audit Logs

* Dashboard

* Booking Management

* Calendar View

* Customer Profiles

* Audit Logs

### Phase 3: Member

* Register / Login
* Booking History
* Member Discount
* Points System (Basic)

### Phase 4: Payment & Notification

* Upload Slip
* Payment Status
* Email/LINE Notify
* Reports (Daily/Monthly)

### Phase 5: Integration (Optional)

* OTA Integration
* API (Sanctum)
* Advanced Loyalty

---

## 10. Non-Goals (ช่วงแรก)

* ไม่ทำ SaaS Multi-tenant
* ไม่แยก Frontend เป็น React/Next
* ไม่ทำ Microservices

---

## 11. Success Criteria

* ไม่มี Overbooking
* Staff ใช้งานได้จริงหน้าฟรอนต์
* ลูกค้าจองผ่านเว็บได้ลื่น
* ระบบเสถียร ดูแลต่อยอดได้ง่าย

---

## 12. Notes

* โฟกัสให้ “ใช้งานจริง” ก่อน “ซับซ้อน”
* ทำเป็น Monolith ที่ออกแบบดี และค่อย ๆ ขยาย

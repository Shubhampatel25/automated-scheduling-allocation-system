# Automated Scheduling & Allocation System

A Laravel-based academic scheduling system for managing courses, timetables, student registrations, and fee payments across departments.

## Features

- **Role-based access**: Admin, HOD, Professor, Student dashboards
- **Timetable generation**: Constraint-based engine with conflict detection (teacher/room double-booking, capacity, availability)
- **Course registration**: Prerequisite enforcement, retake handling, schedule conflict checks, pessimistic-lock capacity control
- **Fee payments**: Full and partial payments via Stripe (idempotent success handling) plus manual admin entry
- **Activity logging**: Audit trail for all major actions

## Tech Stack

- PHP 8.x / Laravel 10+
- MySQL
- Stripe (payment processing)
- Blade templates

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Set `STRIPE_KEY` and `STRIPE_SECRET` in `.env` for payment functionality.

## Roles

| Role    | Key Capabilities |
|---------|-----------------|
| Admin   | Manage students, teachers, departments, rooms, fee payments |
| HOD     | Generate & activate timetables, assign teachers to courses |
| Professor | View assigned courses and students |
| Student | Register/drop courses, view timetable, pay fees |

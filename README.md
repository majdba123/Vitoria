# Vitoria

[English](README.md) | [العربية](README_AR.md)

> Multi-role agriculture and veterinary commerce platform for product discovery, vendor operations, ordering, syndicate oversight, logistics, analytics, and customer workflows.

## Overview

Vitoria is a full-stack business platform built around agriculture and veterinary commerce workflows. It connects customers with vendors while also providing operational surfaces for administrators, employees, and syndicate-oriented roles.

The application goes beyond a simple online store. Its backend exposes dedicated APIs for public browsing, authenticated customer operations, vendor workflows, employee workflows, syndicate dashboards, and administrative management.

## Core Capabilities

- Product catalog and product comparison
- Agriculture and veterinary product discovery
- Vendor profiles and vendor operations
- Shopping cart and coupon workflows
- Checkout and order management
- Order cancellation and return requests
- Invoice access
- Saved favourites and product reviews
- User addresses and shipping-method workflows
- Notifications and notification preferences
- Contact and customer-service flows
- Syndicate dashboards, vendor maps, reports, and analytics
- Vendor product, order, and return analytics
- AI-oriented product endpoints for agriculture and veterinary products
- Real-time communication support through Laravel Reverb / Echo

## Roles & Operational Surfaces

The repository contains separate route groups for different areas of the system, including:

- Public / customer-facing APIs
- Authenticated user workflows
- Vendor APIs
- Syndicate APIs
- Employee APIs
- Administrative APIs

This separation reflects a multi-role operational platform rather than a single storefront.

## Architecture

```text
React 19 + Inertia.js Frontend
            │
            ▼
      Laravel 12
 REST APIs + Web Routes
            │
   ┌────────┼─────────┐
   │        │         │
Customers Vendors Syndicates/Admin
   │        │         │
   └────────┼─────────┘
            ▼
 Eloquent / Database Layer
            │
      Redis / Reverb
```

## Technology Stack

| Area | Technologies |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Authentication | Laravel Sanctum |
| Frontend | React 19, Inertia.js |
| UI / Build | Tailwind CSS 4, Vite 7 |
| Real-time | Laravel Reverb, Laravel Echo, Pusher JS |
| Cache / Data Infrastructure | Redis via Predis |
| Maps | Leaflet |
| Tables / Dashboards | TanStack Table, Recharts |
| Validation | Zod |
| Testing | Pest / Laravel testing stack |

## API Structure

The application separates API responsibilities across multiple route files:

```text
routes/
├── api.php             # public and authenticated customer workflows
├── api_admin.php       # administration APIs
├── api_vendor.php      # vendor operations
├── api_syndicate.php   # syndicate dashboards and analytics
├── api_employee.php    # employee workflows
└── web.php             # web / Inertia application routes
```

Examples of currently represented API capabilities include product browsing and comparison, cart management, checkout, orders, returns, invoices, vendor browsing, syndicate analytics, favourites, reviews, notifications, and AI product discovery.

## Real-time & Performance

The repository includes Laravel Reverb and Laravel Echo for real-time application capabilities. Redis support is available through Predis, and the application defines throttling and response-caching behavior for selected API areas.

See [Redis Setup](REDIS_SETUP.md) for the repository's Redis-specific notes.

## Development

The project provides Composer scripts for local setup, development, and testing.

Typical setup starts with:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Database, Redis, mail, broadcasting, and other environment-specific services should be configured through `.env` before running the application.

Development tooling can then be started using the repository's Composer / npm scripts.

> Runtime success is not implied by this documentation. Dependencies, migrations, tests, and builds should be validated in the target environment before deployment.

## Repository Notes

This README documents capabilities that are directly represented in the current repository. It intentionally avoids claims about production scale, customer counts, transaction volume, uptime, or external certifications that are not proven by the source code.

## API Testing

A Postman collection is included in the repository for API exploration and testing:

`SyriaZone.postman_collection.json`

The historical collection filename is preserved to avoid breaking existing developer workflows.

---

Vitoria combines commerce, operational management, vendor tooling, and sector-specific agriculture/veterinary workflows in one multi-role platform.
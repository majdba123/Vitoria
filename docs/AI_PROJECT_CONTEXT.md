# AI Project Context

## Purpose

This document gives coding assistants and agents a repository-safe technical context for Vitoria. It intentionally uses repository-relative paths, avoids workstation-specific paths, and does not contain production credentials or private infrastructure values.

## Current Architecture

Vitoria is a multi-role agriculture and veterinary commerce platform built on **Laravel 12** with **Inertia + React 19** for the current application frontend.

Primary platform roles include customers, vendors, syndicate-oriented users, employees, and administrators. The system combines public product discovery with authenticated operational workflows, dashboards, ordering, analytics, notifications, and administration.

## Current Stack

- Backend: PHP 8.2+, Laravel 12
- Authentication: Laravel Sanctum
- Frontend: React 19 + Inertia.js
- Build: Vite 7
- Styling: Tailwind CSS 4
- Real-time: Laravel Reverb + Laravel Echo + Pusher JS
- Data/cache support: Eloquent + Redis/Predis
- Validation/tooling: Zod where used by React flows
- Testing: Pest / Laravel test stack

Evidence for the current React/Inertia architecture is in `composer.json`, `package.json`, `resources/js/app.jsx`, `resources/js/ssr.jsx`, and `resources/js/Pages/`.

## Repository Map

- `app/` — Laravel application code, controllers, requests, resources, services, policies, events, observers, and domain logic
- `bootstrap/` — Laravel bootstrap and middleware/routing configuration
- `config/` — framework and integration configuration
- `database/` — migrations, factories, and seeders
- `resources/js/` — Inertia/React application, pages, layouts, components, hooks, and shared client utilities
- `resources/css/` — Tailwind and application styling
- `resources/views/` — server-rendered templates still used where applicable
- `routes/` — public, authenticated, vendor, syndicate, employee, admin, channel, and console routes
- `tests/` — feature/unit and security-oriented tests
- `docs/` — architecture, deployment, setup, and engineering documentation
- `flutter/` / `mobile/` — mobile-related work retained separately from the primary Laravel/Inertia application

## Product Scope

Current repository capabilities include:

- public agriculture/veterinary product discovery
- categories and product comparison
- customer authentication and account flows
- vendor profiles and vendor operations
- cart, checkout, coupons, orders, cancellation/return workflows
- favourites and reviews
- addresses and shipping-method workflows
- notifications and preferences
- contact/customer-service flows
- syndicate dashboards and analytics
- vendor reporting and analytics
- administrative APIs and management surfaces
- real-time communication through Reverb/Echo

Do not infer unsupported features from package names or old documents. Use current implementation, routes, tests, and migrations as the source of truth.

## Engineering Rules for AI-Assisted Changes

1. Preserve role/permission boundaries and ownership scoping.
2. Validate input through Laravel request validation or equivalent domain validation.
3. Avoid returning raw models when an API Resource or explicit response contract exists.
4. Keep secrets, provider tokens, credentials, raw infrastructure IPs, and machine-specific paths out of source control.
5. Use environment configuration for deployment-specific values.
6. Maintain existing API contracts unless a change is explicitly versioned or coordinated with clients.
7. Add or update tests for authorization, validation, data ownership, and regressions when behavior changes.
8. Treat generated browser/audit artifacts as local outputs; they are ignored and must not be recommitted.
9. Do not claim runtime, scale, deployment, or security guarantees without repository or execution evidence.

## Security Context

- Laravel Sanctum protects authenticated API flows.
- Role-specific routes and authorization boundaries must remain server-enforced.
- Client-provided IDs are never authorization boundaries by themselves.
- Reverb client configuration may expose only client-safe connection values; server secrets remain private.
- Provider credentials belong in `.env`, deployment environments, or GitHub Secrets.
- Any credential that has ever entered Git history must be rotated/revoked at its provider; deleting it from the current branch is not sufficient.

## Validation Before Delivery

For meaningful changes, validate the relevant subset of:

```bash
composer test
npm run build
```

Also run focused Laravel/Pest tests for the touched domain, plus any applicable frontend checks or runtime smoke tests. Report what was actually executed; never imply successful validation that was not run.

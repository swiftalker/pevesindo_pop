---
name: Odoo Integration Pattern
description: Rules for communicating with Odoo using Pop-App standards (No third-party packages, Jobs only).
---

# Odoo Integration Skill

When integrating models or syncing features with the Odoo ERP, you **MUST** strictly follow this pattern:

## 1. No External Packages
DO NOT use specific XML-RPC or JSON-RPC third party packages for Odoo. Use Laravel's built-in `Http` facade.
Service Class yang menangani: `App\Services\OdooService`.

## 2. No Direct Push from Controllers/Livewire Component
You **MUST NOT** perform POST/PUT requests directly to Odoo from the HTTP cycle or Livewire component handles.
All PUSH operations must dispatch the `App\Jobs\OdooSyncJob` worker.
The UI optimistic state updates to `:syncing`.

## 3. Idempotency is Required
Every Odoo Job MUST carry a `pop_app_ref` (unique ID reference) from Pop-App.
Before `create` in Odoo, write a checker to see if `pop_app_ref` already exists to prevent duplicate data mapping during failed retries.

## 4. Cache Reference Data
PULL requests (taking Journals, Customers, Pricelists) must be cached using `Cache::remember()` with defined TTLs (usually minimum 5 minutes, default 1 hour).
Do not run real-time fetch to populate select options mapping! Use the background `OdooPullJob` to populate a local reference table or standard cache.

---
name: Pop-App UI Patterns
description: Livewire component patterns for Single Page Operations, Conditional Rendering, and Realtime Events.
---

# Pop-App UI Skill

When building the user interface on FilamentPHP or Livewire, ensure you enforce these UX patterns:

## 1. Single-Page CRUD
We do not use segregated `/create`, `/edit`, `/show` routes for operational models (Sales, Project, Tasks). 
Build a unified Full-Page Livewire Component that handles View + State transition inside a single window. Form fields transition from read-only to input dynamically.

## 2. Progressive Disclosure
Do NOT show the full form if it's not needed by the transaction state.
- If `sales_type == 'close'`, render the order lines directly.
- If `sales_type == 'open'`, only show Customer Name and Note fields. 

Wrap different UI segments inside `if ($step === ...)` blocks using local properties to hide future complexities.

## 3. Disable Buttons based on Sync State
If the model's sync state is `:syncing`, you must disable all actionable buttons (Payment, Confirm, Edit) and show a loading spinner or "Memproses sinkronisasi..." label.

## 4. Listen to Realtime Events
Subscribe your Livewire component to Laravel Reverb via Echo.
```javascript
window.Echo.private('notifications.company.' + this.companyId)
    .listen('.SaleOrderConfirmed', (e) => {
        // ... play notification sound and refresh component
    });
```
Every operational action must emit specific sounds and show a flash toast.

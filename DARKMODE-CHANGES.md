# Dark Mode Changes — Revert Guide (booking-sheduling web)

Started: 2026-06-10. Feature: light/dark theme toggle (default = light).

## How to REVERT all dark-mode changes
Originals are backed up in `.darkmode-backup/` (same relative paths).

**PowerShell — restore everything:**
```powershell
$root = "D:\laragon\www\booking-sheduling"
Get-ChildItem -Path "$root\.darkmode-backup" -Recurse -File | ForEach-Object {
  $rel = $_.FullName.Substring((Join-Path $root '.darkmode-backup').Length + 1)
  Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $root $rel) -Force
}
```
Then delete the NEW file (below) + `.darkmode-backup/` + this file. Re-run Vite (or `npm run build`).

## Files MODIFIED (restore from backup to revert)
- tailwind.config.js                                  (darkMode: class + tokens → CSS vars)
- resources/css/app.css                               (:root + .dark vars; input-base bg; color-scheme + accent-color for native date pickers)
- resources/js/app.js                                 (init theme on boot)
- resources/js/components/layout/AppTopbar.vue        (☀️/🌙 toggle button + bg-white sweep)
- resources/js/components/layout/AppSidebar.vue       (bg-white → bg-surface-raised)
- resources/js/components/base/BaseCard.vue           (bg-white → bg-surface-raised)
- resources/js/components/base/BaseModal.vue          (bg-white → bg-surface-raised)
- resources/js/components/base/BaseButton.vue         (bg-white → bg-surface-raised)
- resources/js/components/base/ToastContainer.vue     (bg-white → bg-surface-raised ×3)
- resources/js/components/booking/BookingCalendar.vue (bg-white → bg-surface-raised ×3)
- resources/js/pages/customer/BookLanding.vue         (bg-white → bg-surface-raised)

## NEW file created (delete to revert)
- resources/js/stores/theme.js   (Pinia theme store: toggle + localStorage + .dark on <html>)

## Notes
- No business logic / API / routing touched — pure styling + one toggle button.
- `AuthLayout.vue` bg-white/15 left as-is (decorative overlay on the indigo auth panel).
- Status colors (emerald/red/amber) unchanged (keep meaning in both themes).
- Dev: Vite hot-reloads. If a tailwind.config change doesn't apply, restart Vite (`npm run dev`).
  Production: rebuild with `npm run build`.

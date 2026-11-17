# Trip service codes and SER

SER uses the selected trip service-code title followed by its service number: ES01, ES02; ED01, ED02; EX01, EX02; EH01, EH02. Rounds of a service keep the same SER. No service-code selection silently falls back to ED.

In **Trip Management → Manage Trips → Edit**, select the desired service code and click **Update**. Eligible pending entries using the previous prefix or the known incorrect ED prefix are synchronized automatically, preserving their numeric suffix, identifiers, assignments, dates and times. The success message reports corrected entries and protected or unrecognized entries left unchanged.

Verified, started, completed or cancelled duties, DOR history, attendance and protected rosters retain their historical code. Custom or ambiguous codes remain unchanged for manual review. Newly generated dates use the selected prefix even when their schedule was copied from a protected historical day.

Trip-sheet templates and imports use the selected prefix. Update Roster, trip-sheet displays and roster downloads read the corrected entry SER. Download fresh Excel files after changing a trip service code; old codes fail import validation and stale previews cannot overwrite the new codes.

## Correcting existing pending entries

Saving the trip normally performs synchronization. For maintenance across existing trips, preview first:

```powershell
php artisan trips:sync-service-codes
php artisan trips:sync-service-codes 'TRP2026#0056'
```

Apply using an active Super Admin ID:

```powershell
php artisan trips:sync-service-codes 'TRP2026#0056' --apply --actor=ADMIN_USER_ID
```

Omit the trip code to process all trips. Each trip is processed transactionally; trips missing a service-code selection are reported and skipped. The command recognizes the selected prefix and legacy ED codes; it cannot infer arbitrary previous prefixes. Repeated applications leave already-correct entries unchanged. No additional database migration is needed for SER synchronization.

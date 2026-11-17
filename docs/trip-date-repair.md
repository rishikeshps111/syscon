# Trip date synchronization and repairs

Use **Trip Management → Manage Trips → Edit → Update**. No separate repair button or preview is required. The success message reports moved, generated and removed duties and generated rosters.

- Moving a date range without changing its duration shifts existing pending duties, rosters and assignment ranges together, preserving assigned drivers and vehicles. Protected duties, attendance, shared external rosters, conflicts and invalid driver licences block the transaction.
- Extending a range retains existing duties and copies the latest saved day's schedule (including stop times) onto missing dates. New duties receive one roster each with status **Unassigned**, no driver, vehicle or recurring assignment, and no copied verification, attendance or journey history.
- Shrinking a range removes only pending duties with no driver, vehicle, covering resource assignment, attendance or protected records. Assigned or protected excluded duties block the entire update.
- Saving an unchanged range fills missing dates and missing rosters without duplicating existing records. A missing roster on an existing pending duty retains its existing resources or recurring assignment.
- Create or import the first trip-sheet schedule before changing dates. The latest saved day must have service codes and departure/arrival times for generation.

Unassigned rosters appear in roster lists and can be assigned later through the existing roster or trip assignment flows. They do not reserve resources or inherit recurring resource defaults. Vendor is optional when no vehicle is assigned.

After a successful change, download a fresh Bulk Roster Import Excel file. Previous files and previews retain their original dates and cannot be used to update moved duties.

## Existing mismatches

For an existing mismatch, clicking Update automatically shifts sheets when they cover a complete contiguous range of the same duration as the requested range. Ambiguous mismatches are rejected without changing data.

The maintenance command remains available for verified original ranges that cannot be inferred automatically. Run it from the application directory on the intended database. Supply the original dates from a verified source; the trip record must already contain the desired dates. Preview is the default and rolls back all changes:

```powershell
php artisan trips:repair-dates 'TRP2026#0056' --from=2026-11-14 --to=2026-11-14
```

Review the trip code, date ranges and affected counts. To apply, supply the ID of an active Super Admin:

```powershell
php artisan trips:repair-dates 'TRP2026#0056' --from=2026-11-14 --to=2026-11-14 --actor=ADMIN_USER_ID --apply
```

The command rechecks all restrictions when applying. It rejects sheets outside the supplied original range, and sheets already entirely within the current range (including previously repaired trips). Overlapping ranges that cannot be identified safely require manual reconciliation. Do not infer original dates or bulk apply repairs without checking each trip.

Run `php artisan migrate` during deployment. The new roster `inherit_trip_assignment` flag defaults to true for existing records; newly generated duties set it to false so they stay explicitly unassigned even when a recurring assignment covers their date. Apply the migration before serving the updated code. Deployment alone does not repair historical mismatches; save the affected trip using Update.

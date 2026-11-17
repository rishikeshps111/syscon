# Update Roster infinite loading

Open **Trip Management → Manage Trips → Update Roaster**. The table loads 25 duties initially and fetches subsequent batches as its scroll area approaches the bottom. A keyboard-accessible **Load more duties** button is also available. Failed requests show a retry action; reaching the final batch stops further requests.

Driver and vehicle dropdowns use Select2 as soon as each batch is displayed. Their search results are fetched only when opened or searched and load 25 options at a time, using the existing trip depot, active-user and vehicle classification/status restrictions. Each duty initially includes only its current driver and vehicle labels. Loading batches show an animated spinner with accessible status text, and the table keeps its contrasting header visible while scrolling.

Editing an assignment selects the duty. Selections and edited values are retained when scrolling or changing filters, including duties currently hidden by filters. The selected count includes those hidden duties. **Select all loaded duties** affects only the duties currently loaded for the current filters; future batches are not automatically selected. Update Roasters submits every selected duty, including hidden ones, through the existing roster save validation.

Pending selections are stored in the current page, not across navigation or reload. Save manual edits before completing an Excel import, which refreshes the page. While an import operation runs, manual editing and feed loading are disabled; late responses cannot append stale duties.

## Interfaces

- `GET /trips/{trip}/roster/rows`: optional `date_from`, `date_to`, `ser` and encrypted `cursor`; returns `rows` and `next_cursor`. Batches are fixed at 25. Ordering is duty date descending, sequence (null treated as zero) ascending, entry ID ascending. Cursors are bound to the trip and active filters.
- `GET /trips/{trip}/roster/drivers` and `/vehicles`: optional `q` and `page`; return Select2 `results` and `pagination.more`. Lookups use the same roster authorization as the main page.
- Existing save, Excel template and bulk-import routes remain unchanged. The legacy DataTables AJAX response remains available for compatibility; the updated page uses the new feeds.

No database migration or new browser dependency is needed. Deploy the updated Blade view, JavaScript assets, controller and routes together, and rebuild any route/view caches using the normal deployment process.

## Verification

```powershell
php artisan test tests/Integration/TripRosterPaginationTest.php
node --test tests/Support/trip-roster-scroll.test.cjs
```

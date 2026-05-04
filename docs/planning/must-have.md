# Must-Have Requirements

This project is an Inventory Management System (IMS) for furniture distribution warehouse operations. It must track all products primarily by SKU and the AO number. The AO number is the DC's internal per-unit serial number — all AO numbers are unique. SKU numbers identify the product type.

Each warehouse store location will be identified by two pieces of data: the store number (unique) and the city in which the store is located. The primary ID for each store will be its store number (e.g. Store #207, Leeds AL).

Each store row will be related to multiple users. Common user roles will be Manager, Credit Manager, DC_Warehouse, Warehouse Supervisor, Warehouse, Sales. Warehouse Supervisor and above will be able to edit inventory. Warehouse will have a limited subset of tools available.

So we will end up with these as a minimum set of tables.

* user
* role
* store
* product
* product_status (relational table to track if a product is damaged)
* product_image (to store records for images attached to damaged products)

### Per-Store PQA Email

Each store record must have a configurable `pqa_email` field. When a damage report has images attached, the warehouse user can press **Send Images** which emails all attached damage photos to that store's PQA email address. The external PQA system will then automatically associate the images with the correct PQA case. The damage notes stored in this system are for internal warehouse reference only — managers will enter their own notes in the PQA system directly.

## Project Motivation

The existing inventory system (a general-purpose third-party platform) does not provide a means to track the volume of product that each store receives damaged. This leads to store managers wasting significant time when trying to source product from other locations. This application provides each registered store location a focused tool to easily track which products arrive damaged and which are in good order and can be sold or transferred to another location if needed.

Each product that is marked as damaged should also have images attached since it is possible for a product to be slightly damaged and still be sold at a discount, but store managers need to be able to see the damage so they can determine if they want to transfer the product before sending warehouse personel to pick the product up.

Ultimately it would also be useful if a user with the role `DC_Warehouse` could log in and generate reporting around what percentage of product received by each store is damaged. This provides insight into how well DC personnel are handling product during loading prior to shipment, and whether they are actively screening product for damage before shipping.

The prefered workflow is when a DC incoming shipment is processed at each location the application will provide a means to scan the barcode on each product (we will need to identify a php library that provides that functionality). I have images of a sample SKU card which has the SKU id and the Tag ID on every product that gets shipped. Once the manifest is processed then the store inventory can be updated, or possibly it can be updated in real time each time a product is scanned. Then once the product is being prepped for delivery or prepared to go to the sales floor if a damaged product is found then one can be modified and marked as damaged, pictures taken and it flagged as damaged. When the product is flagged as damaged a notification should be sent to the Manager for that location so that a PQA process can be started so that the store can be issued a credit on that particular product from the corporate office. The PQA process is outside the scope of this project.

### Barcode Scanning — ZXing-js (Camera-based, Mobile-first)

SKU cards use **Code 128B** barcodes encoding the AO# (e.g. `A006523361`). Scanning will be implemented using **[`@zxing/library`](https://github.com/zxing-js/library)** — a pure JavaScript port of ZXing that reads Code 128 natively via the device camera.

**Initial deployment:** mobile camera only. Warehouse staff point their phone camera at the SKU card barcode during manifest processing; the decoded AO# is sent directly to the scan input field and handled identically to a typed entry.

**Future:** if hardware USB HID or Bluetooth wedge scanners are adopted, no library changes are needed — hardware scanners act as keyboards and emit keystrokes straight into the focused `<input>`, so the same handler works without modification.

**SKU Card — all data is DC-internal:**  
The DC prints and applies these cards to every product as it is loaded for shipment. Every field on the card originates from the DC system:

| Field | Example | Description |
|---|---|---|
| AO# | `A006523361` | Per-unit unique ID (DC internal) |
| SKU | `195844` | 6-digit integer product-type ID (DC internal) |
| VSN | `P0ZZ266457` | Vendor Stock Number |
| Vendor | `EMBY` | Vendor abbreviation |
| Vendor Model | `SM590NS` | Vendor model number |
| Description | `NIGHTSTAND` | Product description |
| Finish/Cover/Size/ST | *(varies)* | Customer configuration specs |

The barcode encodes the **AO#** — the DC's primary per-unit tracking identifier (confirmed by barcode scan).

**Future integration opportunity:** Since all this data originates in the DC's system, a data feed (CSV export, API, or EDI) from the DC could allow manifests to be pre-populated with full product details before the shipment arrives at each store, eliminating manual entry entirely.

**Data available from a successful scan:**
- AO# pre-filled from barcode scan
- SKU, vendor, description, specs entered manually on first encounter; auto-filled on repeat SKUs as the local catalogue grows

**Phase 1 — manual entry with AO pre-fill:**  
The AO# is populated automatically from the scan. The user manually enters any remaining fields on first encounter. Over time the system builds a product catalogue keyed by **SKU** (6-digit integer) — once a SKU has been seen, future scans can auto-fill vendor, vendor model, description, and specs, reducing manual input progressively.

**Future — full lookup:**  
Once a sufficient product catalogue exists (or a DC data feed is established), scanning can resolve the full product record server-side with no manual entry required.

The scan input must remain focused after each confirmation so the user can scan the next item without tapping the screen.

### Project dependecies

This project is built on a custom Mezzio skeleton. The **active runtime is the PHP built-in web server** (`php -S 0.0.0.0:8080`) — standard synchronous PHP. The TrueAsync runtime (`src/mezzio-async/`) is retained for future reintegration once the extension stabilises.

Front-end: HTMX + Laminas View templates. Notifications via SSE/HTMX (planned). CSS framework: Bootstrap 5.3+. Auth: `mezzio/mezzio-authentication` backed by PHP sessions. Authorisation: `mezzio/mezzio-authorization` with `laminas-acl`. Command bus: `webware/command-bus`.

#### Database Choice

We will be using PhpDb for the database abstraction layer since I am one of the maintainers of that project. We need to explore how complicated it will be to provide Async support to MySQL via the new mysql X protocol or if we should just use PostGres since we already have working code for postgres. If possible I would prefer to use MySQL simply because I have a lot more experience with mysql than postgres but we will go with what is best for the project and what the research determines is the correct choice.

##### Code Requirements

PHP 8.5+ features are available (devcontainer runs PHP 8.5.5; `composer.json` declares `^8.6` for future async compatibility). Proper abstraction throughout; composition over inheritance; avoid static usage. Enforce PER 3.0 and `webware/coding-standard` throughout. Note: `php-cs-fixer` does not support TrueAsync syntax — not a concern for the current synchronous runtime.

##### Architecture

We will be using a "module" architecture in the sense that each namespace we add to /src/{module} will expose its own ConfigProvider and will be its own "module". We will most likely follow a Repository/Entity pattern for the database entry points and we may use Valinor for upcasting incoming request to entity types. Mezzio now has a package just for this. We may explore its usage here.

###### Chat answers

1. A manifest table is a great idea actually. This would allow for the creation of a perm record tying each product to its incoming manifest to provide much richer reporting. We can provide FK's to each products Tag ID.

2. AO and Tag ID is the same ID.

3. Vendor will just be a stored string for each product.

4. For status I think the following should cover it. Each product should support multiple of these options to be selected. Its possible that a product may be damaged, but also on the floor. A product may also be damaged and in the bargain_center.

  * overtock
  * damaged
  * floor
  * pending_pqa
  * bargain_center
  * reparable
  * non_reparable

  We will need a process order routine so that as the warehouse personel prep an order they can scan each item and it be removed from inventory. This is where we will most likely attach a customer name. Celerant handles the primary customer tracking, we would really only be interested in which customer got which product as a record of if they purchased a damaged piece. This is allowed and they would get a discount on it but its very useful information because at times we have customers that try to be slick and convince us that they got a product "new and undamaged" when in reality they bought it as-is at a discount but want us to then replace it a month later saying that was not the case. Celerant provides no way to track this data so it could be a huge win for the warehouse dept.

5. Transfer workflow is handled outside of this system, this system will just be used to provide the needed information as to whether the product is viable for transfer based on its condition.

6. The external platform (Celerant) provides primary customer tracking. This IMS provides additional data that the external system does not track. This application is a focused warehouse operations tool targeting gaps left by general-purpose inventory systems.

7. Managers can view other stores inventory and each products status, but they can not modify another stores inventory or another stores products status.

8. Local filesystem will be enough since when products are sold and the batch processing runs then each image attached that AO number should be removed since its no longer required.

9. These are used to categorize inventory. We probably need to provide a way to manage those so a relational table would probably be easiest. IIRC that stands for Major Code so a major_code table will probably be needed.

##### Follow up answers

1. I think just stored on the product will be enough. 

2. The scan out process will be similar but not identical. For product moving to the floor it should remain in inventory its status just changes. For actual deliveries/pick up then it will be removed from inventory since it will no longer be in store or available for sale or transfer.

3. I meant overstock, was just a typo. I am human after all :P

---

## Bundled / Per-Case Items — Workflow & Data Model Notes

Some manifest lines represent a **single AO# that contains multiple physical pieces** (e.g. one
box with 2 chairs, SKU 189780). The same SKU can also arrive as a standalone single unit on a
different manifest. Both variants must be trackable without skewing piece counts.

### Data model

`manifest_item` holds `case_qty SMALLINT UNSIGNED NOT NULL DEFAULT 1` recording how many
physical pieces arrived in the box.

- `case_qty = 1` — normal single unit (default; no special handling)
- `case_qty > 1` — bundled case; the AO# covers `case_qty` physical pieces

At manifest confirmation the application **expands** each `manifest_item` into `case_qty`
individual `product` rows. Every piece row carries the same `ao_number` and the same
`case_qty` value (for provenance — "I came from a 2-piece box"). Each piece then has fully
independent `product_status` tracking from day one.

**Piece counts on the `product` table are plain `COUNT(*)`** — no `SUM` needed — because
every row represents exactly one physical piece. `SUM(manifest_item.case_qty)` is only
used when summarising manifest receiving totals (lines received vs pieces received).

### Scanning workflow

During manifest processing the operator toggles **"Bundled / Per case"** and enters the piece
count before confirming the scan. The toggle is off by default and resets to off after each
confirmation so normal single-item scanning is unaffected.

### Damage workflow

Because each piece is its own `product` row, partial damage within a case requires no
split-record workaround. The operator simply marks the damaged piece rows with the `damaged`
status flag while leaving the clean piece rows untouched.

### Reporting

- Piece counts are `COUNT(*)` on `product` (active inventory) or `SUM(manifest_item.case_qty)`
  on `manifest_item` (receiving totals).
- Manifest summary stats should show both line count and piece count where bundled items are
  present (e.g. "38 lines / 44 pcs").

### Confirmed decisions

1. **One `product` row per physical piece.** `case_qty` on `product` records the original
   box size for context; it does not affect count queries.
2. **`ao_number` is `NOT NULL`** on both `manifest_item` and `product`. Every piece that came
   out of a box shares that box's AO#. There is no unique constraint on `ao_number` alone in
   `product` — multiple pieces from one box legitimately share the same AO#.
3. **Partial qty sell-out:** no longer a schema concern — each piece is already a separate row
   and can be removed independently.

---

## Inventory Removal Workflows

There are exactly **four** workflows that can remove a SKU from inventory for non-supervisor
roles. Managers and supervisors may also perform direct inventory adjustments.

| # | Workflow | Who | What happens |
|---|---|---|---|
| 1 | **Process Manifest** | Warehouse | Adds items to inventory (inbound — not a removal) |
| 2 | **Process Ticket** (Delivery or Pickup) | Warehouse | Removes items; records customer name per AO for fraud protection |
| 3 | **Process Transfer** | Warehouse | Removes items from current store; marks them in-transit to destination store |
| 4 | **PQA Resolution** | Manager | Closes damage report after credit received; removes item from damaged inventory |

### Process Ticket

A ticket represents a Celerant customer order being fulfilled — either a **Delivery** (truck)
or **Pickup** (customer collects in store). The operator scans each AO# as items are loaded or
handed over. On completion, items are removed from inventory and the **customer name** is stored
against each AO. This is the anti-fraud record that Celerant does not provide.

Tickets originate in Celerant and are imported or manually entered here. This system does not
create tickets — it only processes them.

### Process Transfer

An inter-store transfer requires the sending warehouse to scan all items before they leave.
The destination store is selected at the start. On completion, items are removed from the
source store's inventory. A future receipt-scan at the destination store can confirm arrival.

### PQA Resolution

When corporate issues a credit for a damaged item, a manager resolves the PQA case from the
damage detail page. The item is removed from active inventory (disposed, returned to DC, or
sold at discount). This is not a standalone list — it is triggered from the damage detail view.

### Floor / Status Changes (NOT removal workflows)

Moving a product to the sales floor is a **status change only** — the item remains in
inventory. Overstock, Bargain Center, and Repairable flags do not reduce inventory counts.
Only the four workflows above actually decrement the piece count.
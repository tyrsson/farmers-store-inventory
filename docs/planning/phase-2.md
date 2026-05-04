# Phase 2 Planning

Features deferred from v0.1.0. None of these should be built until the Phase 1
(must-have) feature set is stable and in production.

> **Runtime note:** Phase 2 geocoding background jobs are documented below as async coroutines. If TrueAsync is reintegrated by the time Phase 2 starts, use `Async\Scope` / `spawn`. If still on the synchronous runtime, implement as a queued CLI job or use a process supervisor.

---

## Delivery Route Builder

### Motivation

The ticket workflow already captures **Delivery** vs **Pickup** as a `ticket_type`.
For delivery tickets the warehouse needs to know where the truck is going. In Phase 2
we want to be able to select a set of pending delivery tickets and generate a driving
route — ordered stops — for a given date's truck run. That requires a delivery address
attached to each delivery ticket.

### Schema additions

#### `delivery_address`

Stores the customer delivery address for a ticket. Only relevant for
`ticket_type = 'delivery'`; pickup tickets have no address.

```sql
CREATE TABLE IF NOT EXISTS delivery_address (
    id           INT UNSIGNED  AUTO_INCREMENT,
    ticket_id    INT UNSIGNED  NOT NULL,
    street       VARCHAR(255)  NOT NULL,
    city         VARCHAR(100)  NOT NULL,
    state        CHAR(2)       NOT NULL,
    zip          VARCHAR(10)   NOT NULL,
    notes        VARCHAR(255)  NULL     COMMENT 'Gate codes, apartment numbers, landmarks, etc.',
    lat          DECIMAL(9,6)  NULL     COMMENT 'Geocoded latitude; populated by geocoding step',
    lng          DECIMAL(9,6)  NULL     COMMENT 'Geocoded longitude; populated by geocoding step',
    geocoded_at  DATETIME      NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_delivery_address_ticket (ticket_id),
    CONSTRAINT fk_da_ticket FOREIGN KEY (ticket_id) REFERENCES ticket (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`lat`/`lng` are nullable so a record can be saved before geocoding runs. A background
job (or on-save hook) can call a geocoding API and fill them in.

#### `route`

A named collection of delivery tickets assigned to a single truck run.

```sql
CREATE TABLE IF NOT EXISTS route (
    id           INT UNSIGNED      AUTO_INCREMENT,
    store_id     SMALLINT UNSIGNED NOT NULL,
    name         VARCHAR(100)      NOT NULL COMMENT 'e.g. "Tuesday Morning Run"',
    scheduled_at DATE              NOT NULL,
    created_by   INT UNSIGNED      NOT NULL,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_route_store_date (store_id, scheduled_at),
    CONSTRAINT fk_route_store      FOREIGN KEY (store_id)   REFERENCES store (store_number),
    CONSTRAINT fk_route_created_by FOREIGN KEY (created_by) REFERENCES user  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `route_stop`

Links tickets to a route and records the planned stop order.

```sql
CREATE TABLE IF NOT EXISTS route_stop (
    id         INT UNSIGNED AUTO_INCREMENT,
    route_id   INT UNSIGNED NOT NULL,
    ticket_id  INT UNSIGNED NOT NULL,
    stop_order TINYINT UNSIGNED NOT NULL COMMENT 'Driver stop sequence (1 = first)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_route_stop_ticket (route_id, ticket_id),
    UNIQUE KEY uq_route_stop_order  (route_id, stop_order),
    CONSTRAINT fk_rs_route  FOREIGN KEY (route_id)  REFERENCES route  (id),
    CONSTRAINT fk_rs_ticket FOREIGN KEY (ticket_id) REFERENCES ticket (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Relationship to Phase 1 ticket table

No changes to `011_ticket.sql` are required for Phase 2. The `delivery_address` table
hangs off `ticket.id` via a `UNIQUE` FK, so it is a clean zero-impact addition. Existing
pickup ticket rows simply have no `delivery_address` row — enforced at the application
layer, not the DB.

### UI additions (Phase 2)

- **New/Edit Ticket modal** — adds address fields when `ticket_type = delivery` is
  selected (progressive disclosure; hidden for pickup).
- **Routes page** — lists routes by date; "Build Route" picks pending delivery tickets,
  orders stops, geocodes addresses.
- **Route detail** — ordered stop list with map embed; marks stops complete as the
  driver progresses.

### Geocoding

Addresses should be geocoded to lat/lng before route optimisation runs. Options:

- **Google Maps Geocoding API** — most accurate, paid beyond free tier
- **Nominatim (OpenStreetMap)** — free, usage policy requires reasonable request rate
- **Geocodio** — US/Canada focused, generous free tier, good for this use case

Geocoding should be a background step (queue job or async coroutine) triggered on
address save, not a blocking request in the HTTP handler.

### Route optimisation (future)

Once stops have lat/lng, stop order can be optimised by:

- Simple nearest-neighbour heuristic (good enough for small truck runs, zero external deps)
- Google Routes API / Directions API (optimal but paid)
- OSRM self-hosted (open source, can run in Docker)

For v0.1.0 of Phase 2, manual drag-to-reorder stop sequence is sufficient.

---

## User QR Code — Transfer Sign-off

### Motivation

When warehouse personnel hand off a transfer, requiring them to log in to sign is
friction. A per-user QR code lets the receiving or sending person scan their badge
to cryptographically sign the transfer record without touching a keyboard.

### Schema addition

Add a `qr_token` column to the `user` table:

```sql
ALTER TABLE user
    ADD COLUMN qr_token  VARCHAR(64)  NULL UNIQUE
        COMMENT 'Randomly generated token encoded in the user QR badge; NULL until generated'
        AFTER active,
    ADD COLUMN qr_generated_at  DATETIME  NULL  AFTER qr_token;
```

`qr_token` is a cryptographically random string (e.g. 32 bytes, hex-encoded → 64 chars)
generated server-side and never derived from user data. It is stored in plain text because
it is not a secret credential — it is used only within a physically secure warehouse
environment. If a badge is lost the token can be regenerated (old one is invalidated
immediately by the unique constraint update).

### QR code content

The QR code encodes a signed URL:

```
https://{host}/api/qr-auth?token={qr_token}&ts={unix_timestamp}&sig={hmac}
```

- `ts` prevents indefinite replay (server rejects requests older than N minutes)
- `sig` is an HMAC-SHA256 of `token + ts` using a server-side secret key — prevents
  anyone who reads the QR from forging requests with an arbitrary timestamp

### Transfer sign-off flow

1. Operator opens the Process Transfer page on a shared terminal or their phone.
2. Instead of typing credentials, they tap **"Scan Badge"** — camera activates.
3. Camera scans their QR badge → decoded URL is POSTed to the sign-off endpoint.
4. Server validates HMAC, checks timestamp freshness, resolves the user from `qr_token`.
5. The transfer is stamped with that user's `id` as `completed_by`.

### UI additions (Phase 2)

- **Settings → My Badge** — generates or regenerates a user's QR code; displays it
  full-screen for printing or saving to phone.
- **Process Transfer page** — "Scan Badge to Sign" button replaces (or supplements)
  the current "Complete Transfer" confirmation modal.
- Manager view to regenerate any user's QR token (lost badge scenario).

---

## Other Phase 2 Candidates

*(Add items here as they are identified during Phase 1 development.)*

- DC data feed integration — pre-populate manifests from DC system export before truck
  arrives, eliminating manual AO# entry entirely.
- Destination store receipt scan — confirm transfer arrival at the receiving store.
- Analytics date range switching — the analytics page currently shows static mock data;
  wire it to real queries with a date picker.
- SSE live notifications — notify managers in real time when a damage report is filed.

# RDBMS Selection Research: MySQL vs PostgreSQL

## Summary / Recommendation

**Use MySQL via `pdo_mysql` + TrueAsync PDO Pool.**

The MySQL X Protocol path is a dead end (see below). The standard `pdo_mysql` driver is
explicitly supported by TrueAsync's built-in PDO Pool. Adding MySQL support to
`phpdb-async` is a small, low-risk task. Since you have more operational experience
with MySQL, there is no reason to incur the overhead of PostgreSQL.

---

## Option 1: MySQL via Standard PDO + TrueAsync PDO Pool

### How it works

TrueAsync's PDO Pool is implemented at the PHP core level and is **driver-agnostic**.
Each coroutine transparently gets its own connection from the pool. No `suspend()`
polling, no manual acquire/release — the pool handles it all.

### Supported drivers (official TrueAsync documentation)

| Driver     | Supported |
|------------|-----------|
| pdo_mysql  | **Yes**   |
| pdo_pgsql  | **Yes**   |
| pdo_sqlite | **Yes**   |
| pdo_odbc   | No        |

Source: https://true-async.github.io/en/docs/components/pdo-pool.html

### What this means for phpdb-async

The existing `PhpDb\Async\Pdo\Driver` (in `src/phpdb-async/src/Pdo/Driver.php`) already
does everything needed — it extends the Pgsql PDO driver and sets `PDO::ATTR_POOL_ENABLED`
and related pool attributes. A MySQL equivalent is a near-identical class that extends
`PhpDb\Mysql\Pdo\Driver` (or the base PhpDb PDO driver) instead, with a MySQL DSN.

Estimated implementation effort: **small** — one new Driver class, one factory, one
ConfigProvider entry.

### Pool configuration (identical to PostgreSQL path)

```php
$pdo = new PDO('mysql:host=localhost;dbname=app;charset=utf8mb4', 'user', 'secret', [
    PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_POOL_ENABLED              => true,
    PDO::ATTR_POOL_MIN                  => 2,
    PDO::ATTR_POOL_MAX                  => 10,
    PDO::ATTR_POOL_HEALTHCHECK_INTERVAL => 30,
]);
```

### Key pool behaviours relevant to this project

- Each coroutine/HTTP request gets its own connection — no data mixing
- Transactions are pinned to the coroutine; automatic rollback if `commit()` is not called
- Broken connections (server restart, network drop) are detected on return and destroyed;
  the next coroutine gets a fresh connection
- Error state (`errorCode()` / `errorInfo()`) is isolated per coroutine

---

## Option 2: MySQL X Protocol (mysql_xdevapi PECL extension)

### What it is

A PECL extension implementing MySQL's X DevAPI — a new CRUD-style API for accessing
MySQL 8 as a Document Store via the X Protocol (port 33060). Different protocol,
different port, different API surface from standard SQL.

### Why this path is not viable

| Concern | Detail |
|---|---|
| **Abandoned** | Last commit: 4 years ago. Last release: 8.0.30. No PHP 8.5/8.6 compatibility work. |
| **Not in TrueAsync Docker image** | Would require building from source in the custom PHP build. |
| **Heavy build dependencies** | Requires `libprotobuf-dev`, `boost` libraries, and a C++17-capable compiler. |
| **Different API** | Fluent CRUD / Document Store style — incompatible with phpdb-async's PDO/SQL interface. |
| **No TrueAsync integration** | The extension has no knowledge of TrueAsync coroutines or the PDO Pool. |
| **Tiny adoption** | 18 GitHub stars; essentially an Oracle-internal experiment that was never widely adopted. |

### Conclusion on X Protocol

The X Protocol approach would require significant custom C-level integration work,
is built on an unmaintained extension, and provides no benefit over standard `pdo_mysql`
for relational SQL access. **Do not pursue.**

---

## Option 3: Stay with PostgreSQL

### Pros
- Already working in `phpdb-async` (two implementations: `Pgsql\Connection` and `Pdo\Driver`)
- Docker Compose already has a Postgres container
- Integration tests exist

### Cons
- Less operational experience for the team
- Would need to stay with Postgres permanently once schema is defined

### Verdict

Not ruled out, but MySQL is preferred given team familiarity and the fact that
`pdo_mysql` async support is now confirmed to be trivial to add.

---

## Decision

| | MySQL (`pdo_mysql`) | PostgreSQL (`pdo_pgsql`) | X Protocol |
|---|---|---|---|
| TrueAsync PDO Pool support | Yes | Yes | No |
| phpdb-async work needed | Small (1 class) | None (done) | Prohibitive |
| Operational familiarity | High | Low | N/A |
| Docker image changes | None | None | Major |
| Risk | Low | Low | High |

**Selected: MySQL via `pdo_mysql` + TrueAsync PDO Pool.**

The implementation task is: create `PhpDb\Async\Mysql\Pdo\Driver` mirroring the
existing `PhpDb\Async\Pdo\Driver`, wire it through a factory and ConfigProvider entry.
The existing Postgres implementation in `phpdb-async` can remain as a reference and
for any future Postgres use.

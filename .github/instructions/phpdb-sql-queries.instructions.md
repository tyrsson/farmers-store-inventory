---
applyTo: "src/**/*.php,test/**/*.php"
---

# PhpDb SQL Query Guidelines

## Rule: Always Use the `PhpDb\Sql\*` API for DML Queries

**All SELECT, INSERT, UPDATE, and DELETE queries must use the `PhpDb\Sql\*`
object API.** Raw SQL strings passed directly to `$adapter->query()` are
forbidden for DML. Raw strings are only acceptable for DDL statements (e.g.
`CREATE TABLE`, `ALTER TABLE`) where no `Sql` object equivalent exists.

---

## Obtaining a Bound `Sql` Instance

Every repository composes a `PhpDb\TableGateway\TableGateway` instance, bound
to its primary table at construction time. Call `getSql()` to retrieve the
pre-bound `Sql` object:

```php
use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\TableGateway;

final class UserRepository implements UserRepositoryInterface
{
    private readonly TableGateway $gateway;

    public function __construct(AdapterInterface $adapter, mixed $userFactory)
    {
        $this->gateway = new TableGateway('user', $adapter);
    }
}
```

The `Sql` instance returned by `$this->gateway->getSql()` is already bound to
the table — `$sql->select()` produces a `Select` pre-scoped to `user`.

---

## Executing Queries

Always prepare and execute via `prepareStatementForSqlObject()`:

```php
$sql    = $this->gateway->getSql();
$select = $sql->select()
    ->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
    ->where(['user.email' => $email])
    ->limit(1);

$row = $sql->prepareStatementForSqlObject($select)->execute()->current();
```

Do **not** call `$adapter->query($rawSqlString, [...])` for DML — this bypasses
the query builder and is harder to maintain and refactor.

---

## INSERT

```php
$sql    = $this->gateway->getSql();
$insert = $sql->insert()->values($data);

$sql->prepareStatementForSqlObject($insert)->execute();

$id = (int) $this->gateway->getAdapter()
    ->getDriver()->getConnection()->getLastGeneratedValue();
```

- Pass the full `$data` array to `values()` — column names are the array keys.
- Retrieve the last insert ID from the adapter driver **after** execute.

---

## UPDATE

```php
$sql    = $this->gateway->getSql();
$update = $sql->update()->set($data)->where(['id' => $id]);

$sql->prepareStatementForSqlObject($update)->execute();
```

- Do **not** put `id` inside `$data` — pass it as a separate `where()` predicate.

---

## DELETE

```php
$sql    = $this->gateway->getSql();
$delete = $sql->delete()->where(['id' => $id]);

$sql->prepareStatementForSqlObject($delete)->execute();
```

---

## JOIN Columns

When joining tables, specify the columns from the joined table explicitly to
avoid key collisions with the primary table's columns:

```php
->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
// maps role.role_id to the key 'role_name' in the result row
```

Use `[]` (empty array) as the columns argument if you need the join for
filtering only and want no columns from the joined table.

---

## Conditional WHERE Clauses

Apply `where()` conditionally — the `Select` object is mutable:

```php
$select = $sql->select()
    ->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
    ->order('user.display_name ASC');

if ($storeId !== null) {
    $select->where(['user.store_id' => $storeId]);
}

$results = $sql->prepareStatementForSqlObject($select)->execute();
```

---

## What NOT to Do

```php
// FORBIDDEN — raw DML string
$this->adapter->query(
    'SELECT * FROM user WHERE email = :email',
    ['email' => $email],
);

// FORBIDDEN — mixing raw SQL into Sql object methods
$select->where('email = "foo@bar.com"'); // use array or Predicate objects

// FORBIDDEN — DDL via Sql object (use raw string for DDL only)
$sql->insert()->into('CREATE TABLE ...');
```

---

## Summary

| Operation | API |
|---|---|
| SELECT | `$sql->select()->join()->where()->order()->limit()` |
| INSERT | `$sql->insert()->values($data)` |
| UPDATE | `$sql->update()->set($data)->where(...)` |
| DELETE | `$sql->delete()->where(...)` |
| Execute | `$sql->prepareStatementForSqlObject($obj)->execute()` |
| Last insert ID | `$gateway->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue()` |
| DDL only | Raw string via `$adapter->query($ddlString, Adapter::QUERY_MODE_EXECUTE)` |

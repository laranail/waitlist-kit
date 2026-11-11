# Architecture

How the kit is layered, and why it is built the way it is.

## Layers

| Layer | Namespace | May use |
| --- | --- | --- |
| Provider | `Providers` | The container and the framework, to wire everything else |
| Commands | `Commands` | Injected services; they are the console's edge |
| Actions | `Actions` | Injected collaborators only; one intent, one `handle()` |
| Models | `Models` | Eloquent; no facade or global helper |
| Support | `Support` | Injected collaborators only |
| Enums, Exceptions | `Enums`, `Exceptions` | Nothing from the framework |

`tests/Arch` enforces the table: an action that calls `config()` or a facade fails the build.

## Why the schema allows many campaigns in every mode

How many campaigns an install may run is a policy, not a shape. Keeping the schema the same in every mode means a
site can move from `single` to `multi` with a config change, never a migration.

## Why the tables carry a prefix and an optional connection

Table names are a flat namespace the application owns. A bare `subscribers` or `campaigns` is one sibling package
away from a collision, so every table carries `laranail_waitlist_kit_` unless the application chooses otherwise.

## Why ULID keys

Subscriber and campaign ids appear in URLs (status pages, confirmation links). ULIDs are not guessable in sequence and
sort by creation time, so they leak no counts and still index well.

## Why nothing runs at boot

The provider reads configuration and nothing else: no query, no network call. A database outage then fails the
requests that need the database, never the application's boot.

---

[← Docs index](../README.md#documentation)

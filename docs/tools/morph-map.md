# Morph map

Three polymorphic relations, each stored as an alias so a renamed class never strands a row.

| Column pair | On | Points at |
| --- | --- | --- |
| `blockable` | blocks | The page the block belongs to (a campaign) |
| `eventable` | funnel events | What the step is about (the campaign or a subscriber) |
| `integrable` | integrations | The campaign an integration serves, or nothing for every campaign |

The provider merges `Simtabi\Laranail\WaitlistKit\Support\MorphMap::MAP` into the application's map: the kit's five
models under `laranail-waitlist-kit.*` (`laranail-waitlist-kit.campaign`, ...). It merges rather than replaces, and it
does not call `Relation::enforceMorphMap()`: that is global, and would refuse every model of the application's own
that the application has not mapped. `MorphMapTest` enforces the kit's side instead: every model mapped, aliases
stored, every polymorphic column and `MorphTo` relation named `*able`.

---

[← Docs index](../../README.md#documentation)

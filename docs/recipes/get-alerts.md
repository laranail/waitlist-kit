# Get alerts

Hear about new sign-ups, enquiries and list milestones by mail.

```dotenv
WAITLIST_KIT_ALERTS=true
WAITLIST_KIT_ALERTS_TO=team@example.com
WAITLIST_KIT_ALERT_SIGNUPS=true
```

Alerts are queued, so run a worker. Milestones default to 100, 500, 1000, 5000 and 10000 (`alerts.milestones`). For
another channel, listen to the events instead. See [Events, listeners and alerts](../tools/events.md).

---

[← Docs index](../../README.md#documentation)

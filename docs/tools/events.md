# Events, listeners and alerts

12 events, dispatched after the change commits, carry the models involved and the `Actor` who acted.

## Events

| Event | When | Carries |
| --- | --- | --- |
| `SubscriberSignedUp` | A new address joins (not a repeat) | `campaign`, `subscriber` |
| `SubscriberConfirmed` | Someone joins the list: confirms, or signs up without double opt-in | `campaign`, `subscriber` |
| `SubscriberUnsubscribed` | Someone leaves | `subscriber` |
| `SubscribersInvited` | A batch is invited | `campaign`, `subscribers` |
| `SubscriberConverted` | An invitee becomes a customer | `subscriber` |
| `ReferralCredited` | A referrer moves up | `campaign`, `referrer`, `referred` |
| `SubscriberForgotten` | An address is erased everywhere | `email`, `subscribers`, `enquiries` |
| `EnquiryReceived` | A contact enquiry is stored | `enquiry` |
| `CampaignCreated` | A campaign is created | `campaign` |
| `CampaignUpdated` | A campaign's own fields change | `campaign`, `changes` |
| `CampaignDeleted` | A campaign is deleted | `id`, `slug`, `name` |
| `CampaignMilestoneReached` | A list passes one of `alerts.milestones` | `campaign`, `size` |

All are in `Simtabi\Laranail\WaitlistKit\Events` and implement `ShouldDispatchAfterCommit`, so a listener never sees a
rolled-back row. `SubscriberForgotten` carries the erased address so a listener can erase it elsewhere; never log it.

```php
Event::listen(SubscriberConfirmed::class, fn (SubscriberConfirmed $e) => $crm->add($e->subscriber->email));
```

## The kit's listeners

| Listener | On | Does |
| --- | --- | --- |
| `CheckListMilestones` | `SubscriberConfirmed` | Raises `CampaignMilestoneReached` once per milestone per campaign (claimed with the cache's atomic `add`); when several pass at once, only the largest |
| `SendSignupAlert` | `SubscriberSignedUp` | A `SignupAlert`, when `alerts.on.signup` |
| `SendEnquiryAlert` | `EnquiryReceived` | An `EnquiryAlert`, when `alerts.on.enquiry` |
| `SendMilestoneAlert` | `CampaignMilestoneReached` | A `MilestoneAlert`, when `alerts.on.milestone` |

`WaitlistKitServiceProvider::LISTENERS` lists them.

## Alerts and notifications

```dotenv
WAITLIST_KIT_ALERTS=true
WAITLIST_KIT_ALERTS_TO=team@example.com,founders@example.com
WAITLIST_KIT_ALERT_SIGNUPS=true   # off by default; enquiries and milestones are on
```

`AlertDispatcher` sends each alert by mail to `alerts.to` (an on-demand route) and, with `WAITLIST_KIT_ALERTS_ADMINS`
in bundled sign-in, to every kit administrator. Alerts are queued notifications (`SignupAlert`, `EnquiryAlert`,
`MilestoneAlert`, all extending `Alert`) that say what happened and link to the backend; they never include a
subscriber's or sender's address. To send them elsewhere (Slack, your own users), listen to the events, or send the
notifications to your own notifiables; a notifiable with `waitlistKitAlertChannels()` chooses its channels.

---

[← Docs index](../../README.md#documentation)

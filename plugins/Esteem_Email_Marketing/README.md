# Esteem Email Marketing

Admin-only, consent-aware campaign and automation plugin for RISE CRM. It is not activated or enabled by default.

## Installation

Install the plugin from RISE CRM's Plugins screen. The installer creates the prefixed tables in `install/database.sql`. Activate it only for local testing, then enable the module in Email Marketing Settings. Configure the existing CRM mail settings before any test send.

## Safety

New consent rows are `Unknown`; only `Opted In` can be queued. Suppressed/unsubscribed addresses are checked again immediately before delivery. Test mode is enabled by default and only permits the campaign's single Test recipient. Delivery runs from the existing cron hook in batches (25 by default), never from a browser request. Campaigns remain Draft until deliberately advanced by an administrator.

The public endpoint is `/index.php/email_unsubscribe/{token}`. It does not require login and immediately marks queued deliveries unsubscribed.

## Local test

Leave the module disabled, install the SQL, create a Draft, verify its fields and consent rows, and inspect the queue/log tables. To test delivery without real customers, use a local mailbox/test address, enable Test mode, and set exactly one Test recipient. No real email was sent during development.

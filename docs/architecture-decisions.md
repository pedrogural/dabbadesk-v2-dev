# DabbaDesk v2 — Architecture Decisions

## ADR-001 — Public Order App and DabbaDesk stay separate

The public order app and DabbaDesk v2 are separate applications.

They must not share:
- databases
- sessions
- authentication state
- direct internal models

They communicate only through explicit signed endpoints.

## ADR-002 — DabbaDesk is the operational source of truth

DabbaDesk owns:
- retailer detection authority
- fee policy
- order request persistence
- lifecycle references
- request → draft → order conversion
- staff notifications
- customer communication history

The public order app collects customer input and submits it to DabbaDesk.

## ADR-003 — Shared intake logic should become reusable

Reusable intake business logic should eventually move into a local Composer package:

```text
packages/dabbadirect/intake-engine

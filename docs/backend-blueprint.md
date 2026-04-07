# Azveria Online Backend Blueprint

## Stack
- Laravel: API, auth, validation, permissions, business rules.
- MySQL: persistent game state.
- Ratchet (WebSocket): only real-time chat and announcements fan-out.
- Docker Compose: isolated services on one internal network.

## WebSocket Scope (Where Needed)
Use WebSocket for:
- Global announcements live feed.
- Chat message delivery and unread/presence updates.

Do not use WebSocket for:
- Player stats pages.
- Nation edits.
- Shop browsing.
- Map metadata and uploads.

Those remain REST API calls with normal caching and pagination.

## Auth and Roles
- Token auth (Laravel Sanctum or JWT).
- Roles: `admin`, `player`.
- Authorization via policies/gates:
  - Players can read/write only own nation profile and settings.
  - Admin can manage all nations, map layers, shop entries, group memberships.

## Page to API Mapping

### Player
- GET `/api/me/dashboard`
- PATCH `/api/me/about`
- GET `/api/me/units?status=owned|training`
- GET `/api/me/buildings?status=built|constructing|upgrading`

### Announcements
- GET `/api/announcements`
- POST `/api/announcements` (admin only)
- WS channels:
  - `announcements.global`

### Map
- GET `/api/maps/layers`
- GET `/api/me/terrain-square-miles`
- POST `/api/admin/maps/layers/{layerType}` (admin only upload)

### Chat
- GET `/api/chats`
- POST `/api/chats` (player create group or dm, constrained by policy)
- GET `/api/chats/{chat}/messages`
- POST `/api/chats/{chat}/messages`
- Admin management:
  - POST `/api/admin/chats`
  - DELETE `/api/admin/chats/{chat}`
  - POST `/api/admin/chats/{chat}/members`
  - DELETE `/api/admin/chats/{chat}/members/{user}`
- WS channels:
  - `chat.{id}`

### Other Nations (Player)
- GET `/api/nations?search=...`
- GET `/api/nations/{nation}` (policy-filtered fields)

### Shop
- GET `/api/shop/categories`
- GET `/api/shop/items?category=refinement|upgrades|recruitment|crafting`
- POST `/api/shop/buy` (player)
- PUT `/api/admin/shop/items/{item}` (admin)

### Settings
- GET `/api/me/settings`
- PATCH `/api/me/settings`

### Help
- GET `/api/meta/about`
- Logout endpoint from auth package.

## UI Contract Notes
- Top-right resource bar should call `GET /api/me/resources` once and subscribe to events that can change resources after purchases/actions.
- `about_text` must be visible from nation detail endpoint for other players.
- Nation list search supports partial match on `nation_name` and `player_name`.

## Seed/Import Plan From Source Files
- Put Azveria static definitions into seed tables:
  - structures
  - craftables
  - resources
  - terrain modifiers
  - unit catalog
- Use the Dakotians file as first faction baseline in `unit_catalog` and faction modifiers.

## Suggested Laravel Modules
- `Auth`
- `Nations`
- `Resources`
- `Military`
- `Buildings`
- `Chat`
- `Announcements`
- `Maps`
- `Shop`
- `Settings`

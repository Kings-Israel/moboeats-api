# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Authenticated endpoints use Laravel Sanctum bearer tokens. Obtain a token from the login endpoints (e.g. <code>POST /api/v3/login</code>) and send it as <code>Authorization: Bearer {token}</code>.

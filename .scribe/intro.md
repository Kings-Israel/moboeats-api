# Introduction

REST API for the MoboEats platform: customers (orderers), restaurants and grocery shops, riders and administrators.

<aside>
    <strong>Base URL</strong>: <code>http://moboeats.test</code>
</aside>

    This is version 3 of the MoboEats API, served under `/api/v3`.

    **Conventions**
    - Successful responses return the resource itself (e.g. a `Restaurant` object, or an array of them).
      Paginated lists return `{"data": [...], "meta": {"current_page", "per_page", "total", "last_page"}}`.
    - Errors return `{"message": "..."}` with a meaningful HTTP status code. Validation errors use `422` and add
      `"errors": {"field": ["message"]}`. `401` means not authenticated, `403` means not allowed.
    - Shared object shapes are described under **Schemas** (`components.schemas`) of the OpenAPI spec.

    The previous API (`/api/v1`, `/api/v2`) is deprecated and documented separately at `/docs-legacy`.


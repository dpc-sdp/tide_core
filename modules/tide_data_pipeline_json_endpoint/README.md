# Tide Data Pipeline JSON Endpoint

Provides a `json_endpoint` dataset source for the `data_pipelines` module. External systems push JSON payloads to a Drupal REST endpoint, which stores the data and optionally triggers immediate reprocessing of the dataset.

## How it works

1. An external system authenticates with Drupal using OAuth 2.0 (client credentials flow) to obtain a short-lived bearer token.
2. It POSTs a JSON payload to `/api/datasets/{machine_name}/push`.
3. Drupal saves the payload to the private filesystem and, by default, synchronously reprocesses the dataset.

---

## Requirements

- `data_pipelines` module
- `consumers` module
- `simple_oauth` module (provides the OAuth 2.0 token endpoint)
- `tide_oauth` module (provides the authentication provider that validates bearer tokens)
- A configured private filesystem (`$settings['file_private_path']` in `settings.php`)
- OAuth public/private keys generated (run `drush tide-oauth:keygen`)

---

## Installation

Enable the module:

```bash
drush en tide_data_pipeline_json_endpoint
```

On install the module creates:

- A **`data_pipeline_pusher` role** with the single permission `push data pipeline json endpoint`.
- A **`Data Pipeline Pusher` OAuth consumer** (`client_id: data_pipeline_pusher`) wired to that role. The consumer is created as confidential — it cannot issue tokens until a client secret is set (see [OAuth set up](#oauth-set-up) below).

---

## OAuth set up

### 1. Ensure OAuth keys exist

If you have not already generated OAuth keys, run:

```bash
drush tide-oauth:keygen
```

### 2. Set the consumer client secret

The consumer is created without a secret so that no credential is ever stored in code. You must set one before the consumer can issue tokens.

1. Go to **Admin > Configuration > Web services > Consumers** (`/admin/config/services/consumer`).
2. Open the **Data Pipeline Pusher** consumer.
3. Enter a strong random value in the **New Secret** field and save.

Store the secret securely (e.g. in a secrets manager or CI/CD environment variable). Drupal stores only a bcrypt hash — the plaintext is never recoverable from the database.

### 3. Verify the token endpoint

Confirm that `/oauth/token` is accessible and returns a token:

```bash
curl -s -X POST https://your-site.com/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id=data_pipeline_pusher" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  | jq .
```

A successful response:

```json
{
  "token_type": "Bearer",
  "expires_in": 300,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhb..."
}
```

---

## Creating a dataset

Datasets are content entities managed at **Admin > Content > Data Pipelines**.

### Via the admin UI

1. Go to `/admin/content/data-pipelines/add`.
2. Set **Source** to **JSON Endpoint**.
3. Enter a **Machine name** — this becomes the `{machine_name}` segment in the push URL.
4. Optionally set **Path to data** if your payload wraps the records in a nested key (see [Path to data](#path-to-data)).
5. Configure your pipeline and destination as normal.
6. **Publish** the dataset. Unpublished datasets reject push requests with `422`.

### Via Drush / API

```php
$dataset = \Drupal\data_pipelines\Entity\Dataset::create([
  'name'         => 'Suburbs',
  'machine_name' => 'suburbs',
  'source'       => 'json_endpoint',
  'pipeline'     => 'my_pipeline',
  'published'    => TRUE,
  'destinations' => [$destination],
]);
$dataset->save();
```

### Path to data

If your payload nests records under a key rather than being a top-level array, use the **Path to data** field to provide a [JSONPath](https://github.com/SoftCreatR/JSONPath) expression.

| Payload shape | Path to data |
|---|---|
| `[{"id":1}, ...]` (top-level array) | *(leave empty)* |
| `{"data": [{"id":1}, ...]}` | `$.data` |
| `{"results": {"items": [...]}}` | `$.results.items` |

---

## Pushing data

### Endpoint

```
POST /api/datasets/{machine_name}/push
```

| Header | Value |
|---|---|
| `Authorization` | `Bearer <access_token>` |
| `Content-Type` | `application/json` |

### Modes

| Query string | Behaviour |
|---|---|
| *(none)* | Save the payload **and** immediately reprocess the dataset synchronously. |
| `?save_only=1` | Save the payload only. Reprocessing is deferred to the next scheduled run or a manual trigger. |

### Response codes

| Code | Meaning |
|---|---|
| `200` | Success. Body is `{"status":"processed","machine_name":"..."}` or `{"status":"saved","machine_name":"..."}`. |
| `400` | Request body is not valid JSON. |
| `401` | Missing or invalid bearer token. |
| `403` | Token is valid but the associated user lacks the `push data pipeline json endpoint` permission. |
| `404` | No published `json_endpoint` dataset with that machine name exists. |
| `415` | `Content-Type` is not `application/json`. |
| `422` | The dataset exists but is not published. |

---

## Example curl

The following example pushes a list of suburb records to a dataset with the machine name `suburbs`.

### Step 1 — obtain a token

```bash
TOKEN=$(curl -s -X POST https://your-site.com/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id=data_pipeline_pusher" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  | jq -r '.access_token')
```

### Step 2 — push data

```bash
curl -s -X POST https://your-site.com/api/datasets/suburbs/push \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '[
    {"id": 1, "name": "Carlton",  "postcode": "3053"},
    {"id": 2, "name": "Fitzroy",  "postcode": "3065"},
    {"id": 3, "name": "Collingwood", "postcode": "3066"}
  ]' \
  | jq .
```

Expected response:

```json
{
  "status": "processed",
  "machine_name": "suburbs"
}
```

### Save only (defer processing)

```bash
curl -s -X POST "https://your-site.com/api/datasets/suburbs/push?save_only=1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '[{"id": 1, "name": "Carlton", "postcode": "3053"}]' \
  | jq .
```

```json
{
  "status": "saved",
  "machine_name": "suburbs"
}
```

---

## Payload storage

Each push overwrites the previous payload. The file is stored at:

```
private://data_pipelines_json_endpoint/{machine_name}.json
```

This path is inside Drupal's private filesystem and is not publicly accessible.

---

## Token expiry

Access tokens are short-lived (default 5 minutes in `simple_oauth`). Clients should request a new token when the current one is near expiry rather than reusing a cached token indefinitely.

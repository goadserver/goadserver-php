<?php
/**
 * UserWebhook
 *
 * PHP version 8.1
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * GoAdServer Public API
 *
 * Public REST API for advertisers and publishers on the GoAdServer platform.  **Authentication.** All endpoints (except `/api/public/_*`) require an API key passed as `Authorization: Bearer gas_live_…`. Create and manage keys in your account settings — each key has its own scopes, optional resource pinning, optional IP allowlist, and per-key rate limit.  **Apps (OAuth 2.0).** A tool other people connect uses an app token instead: authorization code + PKCE at `/oauth/authorize`, tokens from `/api/public/oauth/token`, passed as `Authorization: Bearer oat_…` on the same endpoints with the same scopes. See the [Build an integration](/api/docs/integrations) guide.  **System rules apply over API rules.** The public API never lets a key bypass restrictions the same user faces in the panel:  - **Source names** (`stats_source_names` right). When the user lacks   this right, advertiser stats responses return `{id, name}` with   `name` equal to the id — no human label leaks through. - **SubID encoding** (`modules.global.subid_encoding`). When enabled   for the system, the user only ever sees encoded SubIDs in the panel.   The API mirrors this both ways: `subid_excl`/`subid_incl` filter   endpoints accept the encoded form on write (decoded server-side   before storage) and emit the encoded form on read. A plain SubID   sent on write is stored as sent — a value is decoded only when it is   a genuine encoded token. - **Module gating.** Endpoints whose module is disabled for the   tenant don't appear in the spec or routes.txt at all.  **Rate limits.** Every response to an API key or app token carries the caller's budget for the current one-minute window:  | Header | Meaning | |---|---| | `X-RateLimit-Limit` | Requests per minute for this key (its `rate_limit_rpm`, default 60; an app's per-installation limit for app tokens). | | `X-RateLimit-Remaining` | Calls left in the current window. | | `X-RateLimit-Reset` | Unix seconds at which the window resets. | | `Retry-After` | On `429 rate_limited` only: seconds until a retry can succeed. |  Windows are fixed and start with the first call, so `Reset` is exact. The MCP endpoint (`POST /api/v1/mcp`) is charged per `tools/call` against its own bucket (default 240/min), and its response headers report that bucket; an exhausted MCP bucket answers the tool call with an error result rather than an HTTP 429, and the HTTP response then carries `Retry-After`.  **Idempotent writes.** Send `Idempotency-Key: <1–255 printable ASCII characters>` (a UUID is ideal) on a `POST` or `PATCH` to make a retry safe. For 24 hours per key (per app installation for app tokens):  - the first request runs and its response — status, body, `Content-Type`,   `Location`, `ETag` — is stored; - the same key with the same method, path, query and body replays that   response without running anything, adding `Idempotent-Replayed: true`; - the same key with a different request is `422 idempotency_key_reused`; - the same key while the first request is still running is   `409 idempotency_key_in_progress` (with `Retry-After: 1`).  `5xx` results are not stored — the key is released and a retry runs again. Bodies over 32 MB cannot carry the header (`413`). An invalid key is `400 invalid_idempotency_key`.  **Concurrency control (ETag / If-Match).** Every JSON `GET` response (up to 1 MB) carries a weak `ETag`, a hash of the body. Send it back:  - `If-None-Match: <etag>` on a `GET` — `304 Not Modified` when unchanged; - `If-Match: <etag>` on a `PATCH`, `PUT` or `DELETE` — the server reads the   resource's current representation (the `GET` of the same path, or of the   nearest parent path that has a `GET`: `PATCH …/campaigns/ron/7/active` is   checked against `GET …/campaigns/ron/7`) and answers   `412 precondition_failed` when it no longer matches, with the current tag in   `ETag` and `current_etag`. `If-Match: *` only requires the resource to   exist. A resource with no readable representation is also `412`.  The check is optimistic, not a lock: it closes the read-modify-write window two agents (or an agent and a person in the panel) would otherwise race in. Fields that change on their own (live spend counters) change the tag too — re-read and retry. A write response carries no new ETag; `GET` again.  **Strict fields.** Unknown body fields are a client error, not something to ignore: a request that sends a field the endpoint does not accept gets `422 unknown_fields` naming it. This is the default for keys created from 2026-10-12 (the key's `strict_fields` setting, shown on the key screen); older keys keep ignoring unknown fields (except on the few endpoints that have always refused them — the budget and on/off toggles). Override per request with `?strict=1` (reject) or `?strict=0` (ignore, everywhere). App tokens default to ignoring unknown fields; `?strict=1` opts in.  **Errors.** Every non-2xx response has the same shape:  ```json { \"error\": \"validation_error\", \"message\": \"Name is required.\", \"errors\": { \"name\": [\"Name is required.\"] } } ```  `error` is a stable machine-readable code — branch on it, never on `message`. `errors` (field → messages) appears on field-level validation failures; some codes add their own members (`scope`, `accepted_scopes`, `unknown_fields`, `current_etag`). Codes:  | Status | Codes | |---|---| | 400 | `bad_request`, `invalid_idempotency_key`, `invalid_body` | | 401 | `unauthenticated`, `invalid_api_key`, `revoked_api_key`, `expired_api_key`, `invalid_token`, `revoked_token`, `expired_token`, `user_not_found`, `account_deleted`, `account_disabled`, `admin_key_on_user_api`, `user_key_on_admin_api` | | 403 | `forbidden`, `missing_scope`, `forbidden_resource`, `ip_not_allowed`, `api_disabled`, `systemid_mismatch`, `endpoint_not_api_accessible`, `oauth_token_wrong_surface`, `mcp_not_enabled_for_app` | | 404 | `not_found` (also a disabled module or missing right — see ModuleDisabled) | | 405 | `method_not_allowed` — the `Allow` header (and the message) lists the methods the path does accept | | 409 | `conflict`, `idempotency_key_in_progress`, and resource-specific conflicts (e.g. `email_exists`) | | 412 | `precondition_failed` | | 413 | `payload_too_large` | | 422 | `validation_error`, `unknown_fields`, `idempotency_key_reused` | | 429 | `rate_limited` | | 5xx | `server_error`, `service_unavailable`, `bad_gateway`, `gateway_timeout` |  Endpoint-specific codes are listed on the endpoint. A response that would otherwise lack a code gets the generic one for its status from this table.  **Client SDKs.** Each SDK is generated from this spec and published as a public GitHub repository under the `goadserver` organization. The same tarball is also embedded in the API server and downloadable from `/api/public/sdk/<lang>.tgz` — useful when GitHub is unreachable.  > **Per-network host.** The default `https://up.go-adserver.com` > baked into the published SDKs is a reference host. Every white-label > tenant runs on its own panel domain — override the host on first use > (`setHost()` / `Configuration.host` / `basePath` etc.). Or, fetch the > spec from your own tenant (`/api/public/openapi.yaml` returns it with > *your* hostname as the default) and regenerate the SDK locally — the > regenerated client has your URL baked in.  - **PHP** — `github.com/goadserver/goadserver-php`   (namespace `GoAdServer\\Api`, composer name `goadserver/sdk`).    ```bash   composer config repositories.goadserver vcs https://github.com/goadserver/goadserver-php   composer require goadserver/sdk:dev-main   ```    ```php   $cfg = GoAdServer\\Api\\Configuration::getDefaultConfiguration()           ->setHost('https://up.go-adserver.com')           ->setAccessToken('gas_live_…');   $api = new GoAdServer\\Api\\Api\\AccountAPIApi(null, $cfg);   $me  = $api->apiV1AccountGet();   ```  - **Go** — `github.com/goadserver/goadserver-go`.    ```bash   go get github.com/goadserver/goadserver-go@latest   ```    ```go   import gas \"github.com/goadserver/goadserver-go\"    cfg := gas.NewConfiguration()   cfg.Servers = gas.ServerConfigurations{{URL: \"https://up.go-adserver.com\"}}   cli := gas.NewAPIClient(cfg)   ctx := context.WithValue(context.Background(),       gas.ContextAccessToken, \"gas_live_…\")   me, _, err := cli.AccountAPI.ApiV1AccountGet(ctx).Execute()   ```  - **Python** — `github.com/goadserver/goadserver-python` (package   `goadserver`). Pulls `pydantic` + `urllib3` automatically.    ```bash   pip install git+https://github.com/goadserver/goadserver-python.git   ```    ```python   from goadserver import ApiClient, Configuration   from goadserver.api import AccountApi, CampaignsApi    cfg = Configuration(host=\"https://up.go-adserver.com\")   cfg.access_token = \"gas_live_…\"   client = ApiClient(cfg)    me = AccountApi(client).api_v1_account_get()   camps = CampaignsApi(client).api_v1_campaigns_get(per_page=10)   ```  - **JavaScript / TypeScript** — `github.com/goadserver/goadserver-js`   (npm name `goadserver-sdk`, generator `typescript-fetch`).    ```bash   npm install github:goadserver/goadserver-js   ```    ```ts   import { Configuration, AccountApi, CampaignsApi }     from \"goadserver-sdk\";    const cfg = new Configuration({     basePath: \"https://up.go-adserver.com\",     accessToken: \"gas_live_…\",   });    const me   = await new AccountApi(cfg).apiV1AccountGet();   const list = await new CampaignsApi(cfg).apiV1CampaignsGet({ perPage: 10 });   ```    The CJS build is broadly compatible (Node + bundlers); the ESM   build is missing trailing `.js` extensions in its imports, a known   `typescript-fetch` quirk — most consumers use CJS.  **Offline / firewalled install.** If the customer's network can't reach GitHub, every SDK's source is also served as a tarball from the API host:  ```bash curl -O https://up.go-adserver.com/api/public/sdk/python.tgz tar xzf python.tgz && pip install ./python ```  Round-trip smoke tests for all four SDKs live at `clients/_smoke/` in the platform repo.
 *
 * The version of the OpenAPI document: 1.0.0
 * Generated by: https://openapi-generator.tech
 * Generator version: 7.21.0
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace GoAdServerApi\Model;

use \ArrayAccess;
use \GoAdServerApi\ObjectSerializer;

/**
 * UserWebhook Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class UserWebhook implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'UserWebhook';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'url' => 'string',
        'events' => 'string[]',
        'description' => 'string',
        'status' => 'string',
        'paused_reason' => 'string',
        'fail_count' => 'int',
        'credential' => 'string',
        'api_key_id' => 'int',
        'api_key_name' => 'string',
        'app_id' => 'int',
        'app_name' => 'string',
        'credential_active' => 'bool',
        'key_id' => 'string',
        'previous_key_id' => 'string',
        'previous_valid_until' => '\DateTime',
        'waiting' => 'int',
        'created_at' => '\DateTime',
        'updated_at' => '\DateTime',
        'secret' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'id' => null,
        'url' => null,
        'events' => null,
        'description' => null,
        'status' => null,
        'paused_reason' => null,
        'fail_count' => null,
        'credential' => null,
        'api_key_id' => null,
        'api_key_name' => null,
        'app_id' => null,
        'app_name' => null,
        'credential_active' => null,
        'key_id' => null,
        'previous_key_id' => null,
        'previous_valid_until' => 'date-time',
        'waiting' => null,
        'created_at' => 'date-time',
        'updated_at' => 'date-time',
        'secret' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'url' => false,
        'events' => false,
        'description' => false,
        'status' => false,
        'paused_reason' => false,
        'fail_count' => false,
        'credential' => false,
        'api_key_id' => false,
        'api_key_name' => false,
        'app_id' => false,
        'app_name' => false,
        'credential_active' => false,
        'key_id' => false,
        'previous_key_id' => false,
        'previous_valid_until' => false,
        'waiting' => false,
        'created_at' => false,
        'updated_at' => false,
        'secret' => false
    ];

    /**
     * If a nullable field gets set to null, insert it here
     *
     * @var boolean[]
     */
    protected array $openAPINullablesSetToNull = [];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of nullable properties
     *
     * @return array
     */
    protected static function openAPINullables(): array
    {
        return self::$openAPINullables;
    }

    /**
     * Array of nullable field names deliberately set to null
     *
     * @return boolean[]
     */
    private function getOpenAPINullablesSetToNull(): array
    {
        return $this->openAPINullablesSetToNull;
    }

    /**
     * Setter - Array of nullable field names deliberately set to null
     *
     * @param boolean[] $openAPINullablesSetToNull
     */
    private function setOpenAPINullablesSetToNull(array $openAPINullablesSetToNull): void
    {
        $this->openAPINullablesSetToNull = $openAPINullablesSetToNull;
    }

    /**
     * Checks if a property is nullable
     *
     * @param string $property
     * @return bool
     */
    public static function isNullable(string $property): bool
    {
        return self::openAPINullables()[$property] ?? false;
    }

    /**
     * Checks if a nullable property is set to null.
     *
     * @param string $property
     * @return bool
     */
    public function isNullableSetToNull(string $property): bool
    {
        return in_array($property, $this->getOpenAPINullablesSetToNull(), true);
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'id' => 'id',
        'url' => 'url',
        'events' => 'events',
        'description' => 'description',
        'status' => 'status',
        'paused_reason' => 'paused_reason',
        'fail_count' => 'fail_count',
        'credential' => 'credential',
        'api_key_id' => 'api_key_id',
        'api_key_name' => 'api_key_name',
        'app_id' => 'app_id',
        'app_name' => 'app_name',
        'credential_active' => 'credential_active',
        'key_id' => 'key_id',
        'previous_key_id' => 'previous_key_id',
        'previous_valid_until' => 'previous_valid_until',
        'waiting' => 'waiting',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
        'secret' => 'secret'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'url' => 'setUrl',
        'events' => 'setEvents',
        'description' => 'setDescription',
        'status' => 'setStatus',
        'paused_reason' => 'setPausedReason',
        'fail_count' => 'setFailCount',
        'credential' => 'setCredential',
        'api_key_id' => 'setApiKeyId',
        'api_key_name' => 'setApiKeyName',
        'app_id' => 'setAppId',
        'app_name' => 'setAppName',
        'credential_active' => 'setCredentialActive',
        'key_id' => 'setKeyId',
        'previous_key_id' => 'setPreviousKeyId',
        'previous_valid_until' => 'setPreviousValidUntil',
        'waiting' => 'setWaiting',
        'created_at' => 'setCreatedAt',
        'updated_at' => 'setUpdatedAt',
        'secret' => 'setSecret'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'url' => 'getUrl',
        'events' => 'getEvents',
        'description' => 'getDescription',
        'status' => 'getStatus',
        'paused_reason' => 'getPausedReason',
        'fail_count' => 'getFailCount',
        'credential' => 'getCredential',
        'api_key_id' => 'getApiKeyId',
        'api_key_name' => 'getApiKeyName',
        'app_id' => 'getAppId',
        'app_name' => 'getAppName',
        'credential_active' => 'getCredentialActive',
        'key_id' => 'getKeyId',
        'previous_key_id' => 'getPreviousKeyId',
        'previous_valid_until' => 'getPreviousValidUntil',
        'waiting' => 'getWaiting',
        'created_at' => 'getCreatedAt',
        'updated_at' => 'getUpdatedAt',
        'secret' => 'getSecret'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const CREDENTIAL_PANEL = 'panel';
    public const CREDENTIAL_KEY = 'key';
    public const CREDENTIAL_APP = 'app';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getStatusAllowableValues()
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getCredentialAllowableValues()
    {
        return [
            self::CREDENTIAL_PANEL,
            self::CREDENTIAL_KEY,
            self::CREDENTIAL_APP,
        ];
    }

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[]|null $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(?array $data = null)
    {
        $this->setIfExists('id', $data ?? [], null);
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('events', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('status', $data ?? [], null);
        $this->setIfExists('paused_reason', $data ?? [], null);
        $this->setIfExists('fail_count', $data ?? [], null);
        $this->setIfExists('credential', $data ?? [], null);
        $this->setIfExists('api_key_id', $data ?? [], null);
        $this->setIfExists('api_key_name', $data ?? [], null);
        $this->setIfExists('app_id', $data ?? [], null);
        $this->setIfExists('app_name', $data ?? [], null);
        $this->setIfExists('credential_active', $data ?? [], null);
        $this->setIfExists('key_id', $data ?? [], null);
        $this->setIfExists('previous_key_id', $data ?? [], null);
        $this->setIfExists('previous_valid_until', $data ?? [], null);
        $this->setIfExists('waiting', $data ?? [], null);
        $this->setIfExists('created_at', $data ?? [], null);
        $this->setIfExists('updated_at', $data ?? [], null);
        $this->setIfExists('secret', $data ?? [], null);
    }

    /**
     * Sets $this->container[$variableName] to the given data or to the given default Value; if $variableName
     * is nullable and its value is set to null in the $fields array, then mark it as "set to null" in the
     * $this->openAPINullablesSetToNull array
     *
     * @param string $variableName
     * @param array  $fields
     * @param mixed  $defaultValue
     */
    private function setIfExists(string $variableName, array $fields, $defaultValue): void
    {
        if (self::isNullable($variableName) && array_key_exists($variableName, $fields) && is_null($fields[$variableName])) {
            $this->openAPINullablesSetToNull[] = $variableName;
        }

        $this->container[$variableName] = $fields[$variableName] ?? $defaultValue;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        $allowedValues = $this->getStatusAllowableValues();
        if (!is_null($this->container['status']) && !in_array($this->container['status'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'status', must be one of '%s'",
                $this->container['status'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getCredentialAllowableValues();
        if (!is_null($this->container['credential']) && !in_array($this->container['credential'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'credential', must be one of '%s'",
                $this->container['credential'],
                implode("', '", $allowedValues)
            );
        }

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     *
     * @param int|null $id id
     *
     * @return self
     */
    public function setId($id)
    {
        if (is_null($id)) {
            throw new \InvalidArgumentException('non-nullable id cannot be null');
        }
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string|null $url url
     *
     * @return self
     */
    public function setUrl($url)
    {
        if (is_null($url)) {
            throw new \InvalidArgumentException('non-nullable url cannot be null');
        }
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets events
     *
     * @return string[]|null
     */
    public function getEvents()
    {
        return $this->container['events'];
    }

    /**
     * Sets events
     *
     * @param string[]|null $events events
     *
     * @return self
     */
    public function setEvents($events)
    {
        if (is_null($events)) {
            throw new \InvalidArgumentException('non-nullable events cannot be null');
        }
        $this->container['events'] = $events;

        return $this;
    }

    /**
     * Gets description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->container['description'];
    }

    /**
     * Sets description
     *
     * @param string|null $description description
     *
     * @return self
     */
    public function setDescription($description)
    {
        if (is_null($description)) {
            throw new \InvalidArgumentException('non-nullable description cannot be null');
        }
        $this->container['description'] = $description;

        return $this;
    }

    /**
     * Gets status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->container['status'];
    }

    /**
     * Sets status
     *
     * @param string|null $status status
     *
     * @return self
     */
    public function setStatus($status)
    {
        if (is_null($status)) {
            throw new \InvalidArgumentException('non-nullable status cannot be null');
        }
        $allowedValues = $this->getStatusAllowableValues();
        if (!in_array($status, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'status', must be one of '%s'",
                    $status,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['status'] = $status;

        return $this;
    }

    /**
     * Gets paused_reason
     *
     * @return string|null
     */
    public function getPausedReason()
    {
        return $this->container['paused_reason'];
    }

    /**
     * Sets paused_reason
     *
     * @param string|null $paused_reason `auto_failing` when paused after 20 failures in a row; empty when paused by hand.
     *
     * @return self
     */
    public function setPausedReason($paused_reason)
    {
        if (is_null($paused_reason)) {
            throw new \InvalidArgumentException('non-nullable paused_reason cannot be null');
        }
        $this->container['paused_reason'] = $paused_reason;

        return $this;
    }

    /**
     * Gets fail_count
     *
     * @return int|null
     */
    public function getFailCount()
    {
        return $this->container['fail_count'];
    }

    /**
     * Sets fail_count
     *
     * @param int|null $fail_count fail_count
     *
     * @return self
     */
    public function setFailCount($fail_count)
    {
        if (is_null($fail_count)) {
            throw new \InvalidArgumentException('non-nullable fail_count cannot be null');
        }
        $this->container['fail_count'] = $fail_count;

        return $this;
    }

    /**
     * Gets credential
     *
     * @return string|null
     */
    public function getCredential()
    {
        return $this->container['credential'];
    }

    /**
     * Sets credential
     *
     * @param string|null $credential What created it — its events follow that credential's access.
     *
     * @return self
     */
    public function setCredential($credential)
    {
        if (is_null($credential)) {
            throw new \InvalidArgumentException('non-nullable credential cannot be null');
        }
        $allowedValues = $this->getCredentialAllowableValues();
        if (!in_array($credential, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'credential', must be one of '%s'",
                    $credential,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['credential'] = $credential;

        return $this;
    }

    /**
     * Gets api_key_id
     *
     * @return int|null
     */
    public function getApiKeyId()
    {
        return $this->container['api_key_id'];
    }

    /**
     * Sets api_key_id
     *
     * @param int|null $api_key_id api_key_id
     *
     * @return self
     */
    public function setApiKeyId($api_key_id)
    {
        if (is_null($api_key_id)) {
            throw new \InvalidArgumentException('non-nullable api_key_id cannot be null');
        }
        $this->container['api_key_id'] = $api_key_id;

        return $this;
    }

    /**
     * Gets api_key_name
     *
     * @return string|null
     */
    public function getApiKeyName()
    {
        return $this->container['api_key_name'];
    }

    /**
     * Sets api_key_name
     *
     * @param string|null $api_key_name api_key_name
     *
     * @return self
     */
    public function setApiKeyName($api_key_name)
    {
        if (is_null($api_key_name)) {
            throw new \InvalidArgumentException('non-nullable api_key_name cannot be null');
        }
        $this->container['api_key_name'] = $api_key_name;

        return $this;
    }

    /**
     * Gets app_id
     *
     * @return int|null
     */
    public function getAppId()
    {
        return $this->container['app_id'];
    }

    /**
     * Sets app_id
     *
     * @param int|null $app_id app_id
     *
     * @return self
     */
    public function setAppId($app_id)
    {
        if (is_null($app_id)) {
            throw new \InvalidArgumentException('non-nullable app_id cannot be null');
        }
        $this->container['app_id'] = $app_id;

        return $this;
    }

    /**
     * Gets app_name
     *
     * @return string|null
     */
    public function getAppName()
    {
        return $this->container['app_name'];
    }

    /**
     * Sets app_name
     *
     * @param string|null $app_name app_name
     *
     * @return self
     */
    public function setAppName($app_name)
    {
        if (is_null($app_name)) {
            throw new \InvalidArgumentException('non-nullable app_name cannot be null');
        }
        $this->container['app_name'] = $app_name;

        return $this;
    }

    /**
     * Gets credential_active
     *
     * @return bool|null
     */
    public function getCredentialActive()
    {
        return $this->container['credential_active'];
    }

    /**
     * Sets credential_active
     *
     * @param bool|null $credential_active false once the key is revoked or the app disconnected — nothing is sent then.
     *
     * @return self
     */
    public function setCredentialActive($credential_active)
    {
        if (is_null($credential_active)) {
            throw new \InvalidArgumentException('non-nullable credential_active cannot be null');
        }
        $this->container['credential_active'] = $credential_active;

        return $this;
    }

    /**
     * Gets key_id
     *
     * @return string|null
     */
    public function getKeyId()
    {
        return $this->container['key_id'];
    }

    /**
     * Sets key_id
     *
     * @param string|null $key_id Current signing secret generation (X-Integration-Key-Id).
     *
     * @return self
     */
    public function setKeyId($key_id)
    {
        if (is_null($key_id)) {
            throw new \InvalidArgumentException('non-nullable key_id cannot be null');
        }
        $this->container['key_id'] = $key_id;

        return $this;
    }

    /**
     * Gets previous_key_id
     *
     * @return string|null
     */
    public function getPreviousKeyId()
    {
        return $this->container['previous_key_id'];
    }

    /**
     * Sets previous_key_id
     *
     * @param string|null $previous_key_id previous_key_id
     *
     * @return self
     */
    public function setPreviousKeyId($previous_key_id)
    {
        if (is_null($previous_key_id)) {
            throw new \InvalidArgumentException('non-nullable previous_key_id cannot be null');
        }
        $this->container['previous_key_id'] = $previous_key_id;

        return $this;
    }

    /**
     * Gets previous_valid_until
     *
     * @return \DateTime|null
     */
    public function getPreviousValidUntil()
    {
        return $this->container['previous_valid_until'];
    }

    /**
     * Sets previous_valid_until
     *
     * @param \DateTime|null $previous_valid_until previous_valid_until
     *
     * @return self
     */
    public function setPreviousValidUntil($previous_valid_until)
    {
        if (is_null($previous_valid_until)) {
            throw new \InvalidArgumentException('non-nullable previous_valid_until cannot be null');
        }
        $this->container['previous_valid_until'] = $previous_valid_until;

        return $this;
    }

    /**
     * Gets waiting
     *
     * @return int|null
     */
    public function getWaiting()
    {
        return $this->container['waiting'];
    }

    /**
     * Sets waiting
     *
     * @param int|null $waiting Deliveries queued (held while paused).
     *
     * @return self
     */
    public function setWaiting($waiting)
    {
        if (is_null($waiting)) {
            throw new \InvalidArgumentException('non-nullable waiting cannot be null');
        }
        $this->container['waiting'] = $waiting;

        return $this;
    }

    /**
     * Gets created_at
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->container['created_at'];
    }

    /**
     * Sets created_at
     *
     * @param \DateTime|null $created_at created_at
     *
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        if (is_null($created_at)) {
            throw new \InvalidArgumentException('non-nullable created_at cannot be null');
        }
        $this->container['created_at'] = $created_at;

        return $this;
    }

    /**
     * Gets updated_at
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->container['updated_at'];
    }

    /**
     * Sets updated_at
     *
     * @param \DateTime|null $updated_at updated_at
     *
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        if (is_null($updated_at)) {
            throw new \InvalidArgumentException('non-nullable updated_at cannot be null');
        }
        $this->container['updated_at'] = $updated_at;

        return $this;
    }

    /**
     * Gets secret
     *
     * @return string|null
     */
    public function getSecret()
    {
        return $this->container['secret'];
    }

    /**
     * Sets secret
     *
     * @param string|null $secret Only on create and rotate-secret.
     *
     * @return self
     */
    public function setSecret($secret)
    {
        if (is_null($secret)) {
            throw new \InvalidArgumentException('non-nullable secret cannot be null');
        }
        $this->container['secret'] = $secret;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer|string $offset Offset
     *
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet(mixed $offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}



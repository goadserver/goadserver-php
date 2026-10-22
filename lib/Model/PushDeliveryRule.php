<?php
/**
 * PushDeliveryRule
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
 * PushDeliveryRule Class Doc Comment
 *
 * @category Class
 * @description A standing, auction-driven send: every &#x60;cadence_hours&#x60; the worker picks an ad per subscriber in the target segment and delivers it. Distinct from a push campaign, which carries fixed creative content and is queued once.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class PushDeliveryRule implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'PushDeliveryRule';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'name' => 'string',
        'segment_id' => 'int',
        'segment_name' => 'string',
        'cadence_hours' => 'int',
        'freq_cap_hours' => 'int',
        'max_per_run' => 'int',
        'is_active' => 'bool',
        'last_run_at' => 'string',
        'last_sent_count' => 'int',
        'last_failed_count' => 'int',
        'last_no_ads_count' => 'int',
        'created_at' => 'string'
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
        'name' => null,
        'segment_id' => null,
        'segment_name' => null,
        'cadence_hours' => null,
        'freq_cap_hours' => null,
        'max_per_run' => null,
        'is_active' => null,
        'last_run_at' => null,
        'last_sent_count' => null,
        'last_failed_count' => null,
        'last_no_ads_count' => null,
        'created_at' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'name' => false,
        'segment_id' => false,
        'segment_name' => false,
        'cadence_hours' => false,
        'freq_cap_hours' => false,
        'max_per_run' => false,
        'is_active' => false,
        'last_run_at' => false,
        'last_sent_count' => false,
        'last_failed_count' => false,
        'last_no_ads_count' => false,
        'created_at' => false
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
        'name' => 'name',
        'segment_id' => 'segment_id',
        'segment_name' => 'segment_name',
        'cadence_hours' => 'cadence_hours',
        'freq_cap_hours' => 'freq_cap_hours',
        'max_per_run' => 'max_per_run',
        'is_active' => 'is_active',
        'last_run_at' => 'last_run_at',
        'last_sent_count' => 'last_sent_count',
        'last_failed_count' => 'last_failed_count',
        'last_no_ads_count' => 'last_no_ads_count',
        'created_at' => 'created_at'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'name' => 'setName',
        'segment_id' => 'setSegmentId',
        'segment_name' => 'setSegmentName',
        'cadence_hours' => 'setCadenceHours',
        'freq_cap_hours' => 'setFreqCapHours',
        'max_per_run' => 'setMaxPerRun',
        'is_active' => 'setIsActive',
        'last_run_at' => 'setLastRunAt',
        'last_sent_count' => 'setLastSentCount',
        'last_failed_count' => 'setLastFailedCount',
        'last_no_ads_count' => 'setLastNoAdsCount',
        'created_at' => 'setCreatedAt'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'name' => 'getName',
        'segment_id' => 'getSegmentId',
        'segment_name' => 'getSegmentName',
        'cadence_hours' => 'getCadenceHours',
        'freq_cap_hours' => 'getFreqCapHours',
        'max_per_run' => 'getMaxPerRun',
        'is_active' => 'getIsActive',
        'last_run_at' => 'getLastRunAt',
        'last_sent_count' => 'getLastSentCount',
        'last_failed_count' => 'getLastFailedCount',
        'last_no_ads_count' => 'getLastNoAdsCount',
        'created_at' => 'getCreatedAt'
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
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('segment_id', $data ?? [], null);
        $this->setIfExists('segment_name', $data ?? [], null);
        $this->setIfExists('cadence_hours', $data ?? [], null);
        $this->setIfExists('freq_cap_hours', $data ?? [], null);
        $this->setIfExists('max_per_run', $data ?? [], null);
        $this->setIfExists('is_active', $data ?? [], null);
        $this->setIfExists('last_run_at', $data ?? [], null);
        $this->setIfExists('last_sent_count', $data ?? [], null);
        $this->setIfExists('last_failed_count', $data ?? [], null);
        $this->setIfExists('last_no_ads_count', $data ?? [], null);
        $this->setIfExists('created_at', $data ?? [], null);
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
     * Gets name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     *
     * @param string|null $name name
     *
     * @return self
     */
    public function setName($name)
    {
        if (is_null($name)) {
            throw new \InvalidArgumentException('non-nullable name cannot be null');
        }
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets segment_id
     *
     * @return int|null
     */
    public function getSegmentId()
    {
        return $this->container['segment_id'];
    }

    /**
     * Sets segment_id
     *
     * @param int|null $segment_id PushSegment id the rule targets. 0 = the whole active audience.
     *
     * @return self
     */
    public function setSegmentId($segment_id)
    {
        if (is_null($segment_id)) {
            throw new \InvalidArgumentException('non-nullable segment_id cannot be null');
        }
        $this->container['segment_id'] = $segment_id;

        return $this;
    }

    /**
     * Gets segment_name
     *
     * @return string|null
     */
    public function getSegmentName()
    {
        return $this->container['segment_name'];
    }

    /**
     * Sets segment_name
     *
     * @param string|null $segment_name Empty when segment_id is 0.
     *
     * @return self
     */
    public function setSegmentName($segment_name)
    {
        if (is_null($segment_name)) {
            throw new \InvalidArgumentException('non-nullable segment_name cannot be null');
        }
        $this->container['segment_name'] = $segment_name;

        return $this;
    }

    /**
     * Gets cadence_hours
     *
     * @return int|null
     */
    public function getCadenceHours()
    {
        return $this->container['cadence_hours'];
    }

    /**
     * Sets cadence_hours
     *
     * @param int|null $cadence_hours How often the rule fires. Minimum 1; defaults to 24.
     *
     * @return self
     */
    public function setCadenceHours($cadence_hours)
    {
        if (is_null($cadence_hours)) {
            throw new \InvalidArgumentException('non-nullable cadence_hours cannot be null');
        }
        $this->container['cadence_hours'] = $cadence_hours;

        return $this;
    }

    /**
     * Gets freq_cap_hours
     *
     * @return int|null
     */
    public function getFreqCapHours()
    {
        return $this->container['freq_cap_hours'];
    }

    /**
     * Sets freq_cap_hours
     *
     * @param int|null $freq_cap_hours Minimum gap between two sends to the same subscriber. 0 = uncapped.
     *
     * @return self
     */
    public function setFreqCapHours($freq_cap_hours)
    {
        if (is_null($freq_cap_hours)) {
            throw new \InvalidArgumentException('non-nullable freq_cap_hours cannot be null');
        }
        $this->container['freq_cap_hours'] = $freq_cap_hours;

        return $this;
    }

    /**
     * Gets max_per_run
     *
     * @return int|null
     */
    public function getMaxPerRun()
    {
        return $this->container['max_per_run'];
    }

    /**
     * Sets max_per_run
     *
     * @param int|null $max_per_run Cap on subscribers reached per run. 0 = unlimited.
     *
     * @return self
     */
    public function setMaxPerRun($max_per_run)
    {
        if (is_null($max_per_run)) {
            throw new \InvalidArgumentException('non-nullable max_per_run cannot be null');
        }
        $this->container['max_per_run'] = $max_per_run;

        return $this;
    }

    /**
     * Gets is_active
     *
     * @return bool|null
     */
    public function getIsActive()
    {
        return $this->container['is_active'];
    }

    /**
     * Sets is_active
     *
     * @param bool|null $is_active is_active
     *
     * @return self
     */
    public function setIsActive($is_active)
    {
        if (is_null($is_active)) {
            throw new \InvalidArgumentException('non-nullable is_active cannot be null');
        }
        $this->container['is_active'] = $is_active;

        return $this;
    }

    /**
     * Gets last_run_at
     *
     * @return string|null
     */
    public function getLastRunAt()
    {
        return $this->container['last_run_at'];
    }

    /**
     * Sets last_run_at
     *
     * @param string|null $last_run_at YYYY-MM-DD HH:MM, empty if the rule has never run.
     *
     * @return self
     */
    public function setLastRunAt($last_run_at)
    {
        if (is_null($last_run_at)) {
            throw new \InvalidArgumentException('non-nullable last_run_at cannot be null');
        }
        $this->container['last_run_at'] = $last_run_at;

        return $this;
    }

    /**
     * Gets last_sent_count
     *
     * @return int|null
     */
    public function getLastSentCount()
    {
        return $this->container['last_sent_count'];
    }

    /**
     * Sets last_sent_count
     *
     * @param int|null $last_sent_count Deliveries accepted on the last run.
     *
     * @return self
     */
    public function setLastSentCount($last_sent_count)
    {
        if (is_null($last_sent_count)) {
            throw new \InvalidArgumentException('non-nullable last_sent_count cannot be null');
        }
        $this->container['last_sent_count'] = $last_sent_count;

        return $this;
    }

    /**
     * Gets last_failed_count
     *
     * @return int|null
     */
    public function getLastFailedCount()
    {
        return $this->container['last_failed_count'];
    }

    /**
     * Sets last_failed_count
     *
     * @param int|null $last_failed_count Deliveries rejected by the push service on the last run.
     *
     * @return self
     */
    public function setLastFailedCount($last_failed_count)
    {
        if (is_null($last_failed_count)) {
            throw new \InvalidArgumentException('non-nullable last_failed_count cannot be null');
        }
        $this->container['last_failed_count'] = $last_failed_count;

        return $this;
    }

    /**
     * Gets last_no_ads_count
     *
     * @return int|null
     */
    public function getLastNoAdsCount()
    {
        return $this->container['last_no_ads_count'];
    }

    /**
     * Sets last_no_ads_count
     *
     * @param int|null $last_no_ads_count Subscribers skipped on the last run because the auction returned no ad.
     *
     * @return self
     */
    public function setLastNoAdsCount($last_no_ads_count)
    {
        if (is_null($last_no_ads_count)) {
            throw new \InvalidArgumentException('non-nullable last_no_ads_count cannot be null');
        }
        $this->container['last_no_ads_count'] = $last_no_ads_count;

        return $this;
    }

    /**
     * Gets created_at
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->container['created_at'];
    }

    /**
     * Sets created_at
     *
     * @param string|null $created_at YYYY-MM-DD HH:MM.
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



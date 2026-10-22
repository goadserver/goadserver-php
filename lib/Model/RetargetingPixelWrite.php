<?php
/**
 * RetargetingPixelWrite
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
 * RetargetingPixelWrite Class Doc Comment
 *
 * @category Class
 * @description Retargeting list write body. Takes the panel&#39;s request keys (&#x60;rmdays&#x60;, &#x60;skipdays&#x60;, &#x60;skiphours&#x60;, &#x60;skipvisits&#x60;) or the stored names the reads return (&#x60;purge_days_after&#x60;, &#x60;skip_days_after&#x60;, &#x60;skip_before_hours&#x60;, &#x60;skip_visits_after&#x60;) — so a fetched record can be sent back unchanged. Both names of one window with different values is a 422. &#x60;id&#x60;, &#x60;is_active&#x60; and &#x60;visitors&#x60; are accepted and ignored. Windows are whole numbers &gt;&#x3D; 0 (0 &#x3D; off). The name has HTML tags stripped and is 1–255 characters. Unknown keys are a 422.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class RetargetingPixelWrite implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'RetargetingPixelWrite';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'rmdays' => 'int',
        'skipdays' => 'int',
        'skiphours' => 'int',
        'skipvisits' => 'int',
        'purge_days_after' => 'int',
        'skip_days_after' => 'int',
        'skip_before_hours' => 'int',
        'skip_visits_after' => 'int',
        'id' => 'int',
        'is_active' => 'bool',
        'visitors' => 'int'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'name' => null,
        'rmdays' => null,
        'skipdays' => null,
        'skiphours' => null,
        'skipvisits' => null,
        'purge_days_after' => null,
        'skip_days_after' => null,
        'skip_before_hours' => null,
        'skip_visits_after' => null,
        'id' => null,
        'is_active' => null,
        'visitors' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'rmdays' => false,
        'skipdays' => false,
        'skiphours' => false,
        'skipvisits' => false,
        'purge_days_after' => false,
        'skip_days_after' => false,
        'skip_before_hours' => false,
        'skip_visits_after' => false,
        'id' => false,
        'is_active' => false,
        'visitors' => false
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
        'name' => 'name',
        'rmdays' => 'rmdays',
        'skipdays' => 'skipdays',
        'skiphours' => 'skiphours',
        'skipvisits' => 'skipvisits',
        'purge_days_after' => 'purge_days_after',
        'skip_days_after' => 'skip_days_after',
        'skip_before_hours' => 'skip_before_hours',
        'skip_visits_after' => 'skip_visits_after',
        'id' => 'id',
        'is_active' => 'is_active',
        'visitors' => 'visitors'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'rmdays' => 'setRmdays',
        'skipdays' => 'setSkipdays',
        'skiphours' => 'setSkiphours',
        'skipvisits' => 'setSkipvisits',
        'purge_days_after' => 'setPurgeDaysAfter',
        'skip_days_after' => 'setSkipDaysAfter',
        'skip_before_hours' => 'setSkipBeforeHours',
        'skip_visits_after' => 'setSkipVisitsAfter',
        'id' => 'setId',
        'is_active' => 'setIsActive',
        'visitors' => 'setVisitors'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'rmdays' => 'getRmdays',
        'skipdays' => 'getSkipdays',
        'skiphours' => 'getSkiphours',
        'skipvisits' => 'getSkipvisits',
        'purge_days_after' => 'getPurgeDaysAfter',
        'skip_days_after' => 'getSkipDaysAfter',
        'skip_before_hours' => 'getSkipBeforeHours',
        'skip_visits_after' => 'getSkipVisitsAfter',
        'id' => 'getId',
        'is_active' => 'getIsActive',
        'visitors' => 'getVisitors'
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
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('rmdays', $data ?? [], null);
        $this->setIfExists('skipdays', $data ?? [], null);
        $this->setIfExists('skiphours', $data ?? [], null);
        $this->setIfExists('skipvisits', $data ?? [], null);
        $this->setIfExists('purge_days_after', $data ?? [], null);
        $this->setIfExists('skip_days_after', $data ?? [], null);
        $this->setIfExists('skip_before_hours', $data ?? [], null);
        $this->setIfExists('skip_visits_after', $data ?? [], null);
        $this->setIfExists('id', $data ?? [], null);
        $this->setIfExists('is_active', $data ?? [], null);
        $this->setIfExists('visitors', $data ?? [], null);
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

        if (!is_null($this->container['name']) && (mb_strlen($this->container['name']) > 255)) {
            $invalidProperties[] = "invalid value for 'name', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['rmdays']) && ($this->container['rmdays'] < 0)) {
            $invalidProperties[] = "invalid value for 'rmdays', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skipdays']) && ($this->container['skipdays'] < 0)) {
            $invalidProperties[] = "invalid value for 'skipdays', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skiphours']) && ($this->container['skiphours'] < 0)) {
            $invalidProperties[] = "invalid value for 'skiphours', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skipvisits']) && ($this->container['skipvisits'] < 0)) {
            $invalidProperties[] = "invalid value for 'skipvisits', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['purge_days_after']) && ($this->container['purge_days_after'] < 0)) {
            $invalidProperties[] = "invalid value for 'purge_days_after', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skip_days_after']) && ($this->container['skip_days_after'] < 0)) {
            $invalidProperties[] = "invalid value for 'skip_days_after', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skip_before_hours']) && ($this->container['skip_before_hours'] < 0)) {
            $invalidProperties[] = "invalid value for 'skip_before_hours', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['skip_visits_after']) && ($this->container['skip_visits_after'] < 0)) {
            $invalidProperties[] = "invalid value for 'skip_visits_after', must be bigger than or equal to 0.";
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
        if ((mb_strlen($name) > 255)) {
            throw new \InvalidArgumentException('invalid length for $name when calling RetargetingPixelWrite., must be smaller than or equal to 255.');
        }

        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets rmdays
     *
     * @return int|null
     */
    public function getRmdays()
    {
        return $this->container['rmdays'];
    }

    /**
     * Sets rmdays
     *
     * @param int|null $rmdays rmdays
     *
     * @return self
     */
    public function setRmdays($rmdays)
    {
        if (is_null($rmdays)) {
            throw new \InvalidArgumentException('non-nullable rmdays cannot be null');
        }

        if (($rmdays < 0)) {
            throw new \InvalidArgumentException('invalid value for $rmdays when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['rmdays'] = $rmdays;

        return $this;
    }

    /**
     * Gets skipdays
     *
     * @return int|null
     */
    public function getSkipdays()
    {
        return $this->container['skipdays'];
    }

    /**
     * Sets skipdays
     *
     * @param int|null $skipdays skipdays
     *
     * @return self
     */
    public function setSkipdays($skipdays)
    {
        if (is_null($skipdays)) {
            throw new \InvalidArgumentException('non-nullable skipdays cannot be null');
        }

        if (($skipdays < 0)) {
            throw new \InvalidArgumentException('invalid value for $skipdays when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skipdays'] = $skipdays;

        return $this;
    }

    /**
     * Gets skiphours
     *
     * @return int|null
     */
    public function getSkiphours()
    {
        return $this->container['skiphours'];
    }

    /**
     * Sets skiphours
     *
     * @param int|null $skiphours skiphours
     *
     * @return self
     */
    public function setSkiphours($skiphours)
    {
        if (is_null($skiphours)) {
            throw new \InvalidArgumentException('non-nullable skiphours cannot be null');
        }

        if (($skiphours < 0)) {
            throw new \InvalidArgumentException('invalid value for $skiphours when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skiphours'] = $skiphours;

        return $this;
    }

    /**
     * Gets skipvisits
     *
     * @return int|null
     */
    public function getSkipvisits()
    {
        return $this->container['skipvisits'];
    }

    /**
     * Sets skipvisits
     *
     * @param int|null $skipvisits skipvisits
     *
     * @return self
     */
    public function setSkipvisits($skipvisits)
    {
        if (is_null($skipvisits)) {
            throw new \InvalidArgumentException('non-nullable skipvisits cannot be null');
        }

        if (($skipvisits < 0)) {
            throw new \InvalidArgumentException('invalid value for $skipvisits when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skipvisits'] = $skipvisits;

        return $this;
    }

    /**
     * Gets purge_days_after
     *
     * @return int|null
     */
    public function getPurgeDaysAfter()
    {
        return $this->container['purge_days_after'];
    }

    /**
     * Sets purge_days_after
     *
     * @param int|null $purge_days_after purge_days_after
     *
     * @return self
     */
    public function setPurgeDaysAfter($purge_days_after)
    {
        if (is_null($purge_days_after)) {
            throw new \InvalidArgumentException('non-nullable purge_days_after cannot be null');
        }

        if (($purge_days_after < 0)) {
            throw new \InvalidArgumentException('invalid value for $purge_days_after when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['purge_days_after'] = $purge_days_after;

        return $this;
    }

    /**
     * Gets skip_days_after
     *
     * @return int|null
     */
    public function getSkipDaysAfter()
    {
        return $this->container['skip_days_after'];
    }

    /**
     * Sets skip_days_after
     *
     * @param int|null $skip_days_after skip_days_after
     *
     * @return self
     */
    public function setSkipDaysAfter($skip_days_after)
    {
        if (is_null($skip_days_after)) {
            throw new \InvalidArgumentException('non-nullable skip_days_after cannot be null');
        }

        if (($skip_days_after < 0)) {
            throw new \InvalidArgumentException('invalid value for $skip_days_after when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skip_days_after'] = $skip_days_after;

        return $this;
    }

    /**
     * Gets skip_before_hours
     *
     * @return int|null
     */
    public function getSkipBeforeHours()
    {
        return $this->container['skip_before_hours'];
    }

    /**
     * Sets skip_before_hours
     *
     * @param int|null $skip_before_hours skip_before_hours
     *
     * @return self
     */
    public function setSkipBeforeHours($skip_before_hours)
    {
        if (is_null($skip_before_hours)) {
            throw new \InvalidArgumentException('non-nullable skip_before_hours cannot be null');
        }

        if (($skip_before_hours < 0)) {
            throw new \InvalidArgumentException('invalid value for $skip_before_hours when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skip_before_hours'] = $skip_before_hours;

        return $this;
    }

    /**
     * Gets skip_visits_after
     *
     * @return int|null
     */
    public function getSkipVisitsAfter()
    {
        return $this->container['skip_visits_after'];
    }

    /**
     * Sets skip_visits_after
     *
     * @param int|null $skip_visits_after skip_visits_after
     *
     * @return self
     */
    public function setSkipVisitsAfter($skip_visits_after)
    {
        if (is_null($skip_visits_after)) {
            throw new \InvalidArgumentException('non-nullable skip_visits_after cannot be null');
        }

        if (($skip_visits_after < 0)) {
            throw new \InvalidArgumentException('invalid value for $skip_visits_after when calling RetargetingPixelWrite., must be bigger than or equal to 0.');
        }

        $this->container['skip_visits_after'] = $skip_visits_after;

        return $this;
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
     * Gets visitors
     *
     * @return int|null
     */
    public function getVisitors()
    {
        return $this->container['visitors'];
    }

    /**
     * Sets visitors
     *
     * @param int|null $visitors visitors
     *
     * @return self
     */
    public function setVisitors($visitors)
    {
        if (is_null($visitors)) {
            throw new \InvalidArgumentException('non-nullable visitors cannot be null');
        }
        $this->container['visitors'] = $visitors;

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



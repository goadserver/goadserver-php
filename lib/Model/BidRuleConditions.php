<?php
/**
 * BidRuleConditions
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
 * BidRuleConditions Class Doc Comment
 *
 * @category Class
 * @description Structured per-rule match conditions. Every field is an independent include/exclude filter on one request dimension — missing or empty &#x3D; \&quot;no filter on this dimension\&quot; (i.e. matches every value). A rule fires when **all** populated dimensions match the incoming RTB request.  Notes on specific dimensions:  - &#x60;regionids&#x60; is matched against the visitor&#39;s full geo lineage   (continent → country → region → region2 → city) — selecting   \&quot;US\&quot; (the country row from &#x60;/api/v1/lookups/regions&#x60;) at the   editor therefore fires for every US visitor regardless of   state / city. Send leaf ids when you want a tighter window. - &#x60;zone&#x60; / &#x60;domainids&#x60; are include-lists; &#x60;zone_block&#x60; /   &#x60;domainids_block&#x60; are exclude-lists. You can mix include +   exclude on the same rule — they&#39;re independent. - &#x60;domainids&#x60; / &#x60;domainids_block&#x60; reference the global   &#x60;bigdata.domains&#x60; catalogue (same id-space &#x60;xref.DomainId&#x60;   uses at serve time). The catalogue is far too large for a   list picker — IDs come from advertiser reports. - Daypart: &#x60;hour_start&#x60; and &#x60;hour_end&#x60; are integers 0..23   (inclusive), evaluated in the rule&#39;s &#x60;tz&#x60; (or the ruleset&#39;s   &#x60;default_tz&#x60; when the rule has &#x60;tz&#x3D;\&quot;\&quot;&#x60;). When   &#x60;hour_end &lt; hour_start&#x60; the window wraps past midnight —   e.g. &#x60;hour_start&#x3D;18, hour_end&#x3D;3&#x60; &#x3D; 18:00 through 03:00 next   morning. - &#x60;conn_type&#x60; matches the visitor&#39;s connection class. Pair   &#x60;conn_type&#x3D;[\&quot;cellular\&quot;]&#x60; with &#x60;carrier_id&#x60; for per-carrier   mobile targeting.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class BidRuleConditions implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'BidRuleConditions';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'regionids' => 'int[]',
        'os_id' => 'int[]',
        'browser_id' => 'int[]',
        'device_id' => 'int[]',
        'zone' => 'int[]',
        'domainids' => 'int[]',
        'zone_block' => 'int[]',
        'domainids_block' => 'int[]',
        'carrier_id' => 'int[]',
        'conn_type' => 'string[]',
        'lang' => 'string[]',
        'hour_start' => 'int',
        'hour_end' => 'int'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'regionids' => null,
        'os_id' => null,
        'browser_id' => null,
        'device_id' => null,
        'zone' => null,
        'domainids' => null,
        'zone_block' => null,
        'domainids_block' => null,
        'carrier_id' => null,
        'conn_type' => null,
        'lang' => null,
        'hour_start' => null,
        'hour_end' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'regionids' => false,
        'os_id' => false,
        'browser_id' => false,
        'device_id' => false,
        'zone' => false,
        'domainids' => false,
        'zone_block' => false,
        'domainids_block' => false,
        'carrier_id' => false,
        'conn_type' => false,
        'lang' => false,
        'hour_start' => false,
        'hour_end' => false
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
        'regionids' => 'regionids',
        'os_id' => 'os_id',
        'browser_id' => 'browser_id',
        'device_id' => 'device_id',
        'zone' => 'zone',
        'domainids' => 'domainids',
        'zone_block' => 'zone_block',
        'domainids_block' => 'domainids_block',
        'carrier_id' => 'carrier_id',
        'conn_type' => 'conn_type',
        'lang' => 'lang',
        'hour_start' => 'hour_start',
        'hour_end' => 'hour_end'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'regionids' => 'setRegionids',
        'os_id' => 'setOsId',
        'browser_id' => 'setBrowserId',
        'device_id' => 'setDeviceId',
        'zone' => 'setZone',
        'domainids' => 'setDomainids',
        'zone_block' => 'setZoneBlock',
        'domainids_block' => 'setDomainidsBlock',
        'carrier_id' => 'setCarrierId',
        'conn_type' => 'setConnType',
        'lang' => 'setLang',
        'hour_start' => 'setHourStart',
        'hour_end' => 'setHourEnd'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'regionids' => 'getRegionids',
        'os_id' => 'getOsId',
        'browser_id' => 'getBrowserId',
        'device_id' => 'getDeviceId',
        'zone' => 'getZone',
        'domainids' => 'getDomainids',
        'zone_block' => 'getZoneBlock',
        'domainids_block' => 'getDomainidsBlock',
        'carrier_id' => 'getCarrierId',
        'conn_type' => 'getConnType',
        'lang' => 'getLang',
        'hour_start' => 'getHourStart',
        'hour_end' => 'getHourEnd'
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

    public const CONN_TYPE_WIFI = 'wifi';
    public const CONN_TYPE_CELLULAR = 'cellular';
    public const CONN_TYPE_ETHERNET = 'ethernet';
    public const CONN_TYPE_DIALUP = 'dialup';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getConnTypeAllowableValues()
    {
        return [
            self::CONN_TYPE_WIFI,
            self::CONN_TYPE_CELLULAR,
            self::CONN_TYPE_ETHERNET,
            self::CONN_TYPE_DIALUP,
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
        $this->setIfExists('regionids', $data ?? [], null);
        $this->setIfExists('os_id', $data ?? [], null);
        $this->setIfExists('browser_id', $data ?? [], null);
        $this->setIfExists('device_id', $data ?? [], null);
        $this->setIfExists('zone', $data ?? [], null);
        $this->setIfExists('domainids', $data ?? [], null);
        $this->setIfExists('zone_block', $data ?? [], null);
        $this->setIfExists('domainids_block', $data ?? [], null);
        $this->setIfExists('carrier_id', $data ?? [], null);
        $this->setIfExists('conn_type', $data ?? [], null);
        $this->setIfExists('lang', $data ?? [], null);
        $this->setIfExists('hour_start', $data ?? [], null);
        $this->setIfExists('hour_end', $data ?? [], null);
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

        if (!is_null($this->container['hour_start']) && ($this->container['hour_start'] > 23)) {
            $invalidProperties[] = "invalid value for 'hour_start', must be smaller than or equal to 23.";
        }

        if (!is_null($this->container['hour_start']) && ($this->container['hour_start'] < 0)) {
            $invalidProperties[] = "invalid value for 'hour_start', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['hour_end']) && ($this->container['hour_end'] > 23)) {
            $invalidProperties[] = "invalid value for 'hour_end', must be smaller than or equal to 23.";
        }

        if (!is_null($this->container['hour_end']) && ($this->container['hour_end'] < 0)) {
            $invalidProperties[] = "invalid value for 'hour_end', must be bigger than or equal to 0.";
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
     * Gets regionids
     *
     * @return int[]|null
     */
    public function getRegionids()
    {
        return $this->container['regionids'];
    }

    /**
     * Sets regionids
     *
     * @param int[]|null $regionids `geo2_locations.id` include-list. Visitor's lineage (continent / country / region / region2 / city) matches when **any** ancestor id is in this set. From `GET /api/v1/lookups/regions`.
     *
     * @return self
     */
    public function setRegionids($regionids)
    {
        if (is_null($regionids)) {
            throw new \InvalidArgumentException('non-nullable regionids cannot be null');
        }
        $this->container['regionids'] = $regionids;

        return $this;
    }

    /**
     * Gets os_id
     *
     * @return int[]|null
     */
    public function getOsId()
    {
        return $this->container['os_id'];
    }

    /**
     * Sets os_id
     *
     * @param int[]|null $os_id OS id include-list. From `GET /api/v1/lookups/operating-systems`.
     *
     * @return self
     */
    public function setOsId($os_id)
    {
        if (is_null($os_id)) {
            throw new \InvalidArgumentException('non-nullable os_id cannot be null');
        }
        $this->container['os_id'] = $os_id;

        return $this;
    }

    /**
     * Gets browser_id
     *
     * @return int[]|null
     */
    public function getBrowserId()
    {
        return $this->container['browser_id'];
    }

    /**
     * Sets browser_id
     *
     * @param int[]|null $browser_id Browser id include-list. From `GET /api/v1/lookups/browsers`.
     *
     * @return self
     */
    public function setBrowserId($browser_id)
    {
        if (is_null($browser_id)) {
            throw new \InvalidArgumentException('non-nullable browser_id cannot be null');
        }
        $this->container['browser_id'] = $browser_id;

        return $this;
    }

    /**
     * Gets device_id
     *
     * @return int[]|null
     */
    public function getDeviceId()
    {
        return $this->container['device_id'];
    }

    /**
     * Sets device_id
     *
     * @param int[]|null $device_id Device-type id include-list. From `GET /api/v1/lookups/device-types`.
     *
     * @return self
     */
    public function setDeviceId($device_id)
    {
        if (is_null($device_id)) {
            throw new \InvalidArgumentException('non-nullable device_id cannot be null');
        }
        $this->container['device_id'] = $device_id;

        return $this;
    }

    /**
     * Gets zone
     *
     * @return int[]|null
     */
    public function getZone()
    {
        return $this->container['zone'];
    }

    /**
     * Sets zone
     *
     * @param int[]|null $zone Adzone id include-list (`publisher_adzones.id`).
     *
     * @return self
     */
    public function setZone($zone)
    {
        if (is_null($zone)) {
            throw new \InvalidArgumentException('non-nullable zone cannot be null');
        }
        $this->container['zone'] = $zone;

        return $this;
    }

    /**
     * Gets domainids
     *
     * @return int[]|null
     */
    public function getDomainids()
    {
        return $this->container['domainids'];
    }

    /**
     * Sets domainids
     *
     * @param int[]|null $domainids Domain id include-list (`bigdata.domains.id`).
     *
     * @return self
     */
    public function setDomainids($domainids)
    {
        if (is_null($domainids)) {
            throw new \InvalidArgumentException('non-nullable domainids cannot be null');
        }
        $this->container['domainids'] = $domainids;

        return $this;
    }

    /**
     * Gets zone_block
     *
     * @return int[]|null
     */
    public function getZoneBlock()
    {
        return $this->container['zone_block'];
    }

    /**
     * Sets zone_block
     *
     * @param int[]|null $zone_block Adzone id exclude-list (`publisher_adzones.id`).
     *
     * @return self
     */
    public function setZoneBlock($zone_block)
    {
        if (is_null($zone_block)) {
            throw new \InvalidArgumentException('non-nullable zone_block cannot be null');
        }
        $this->container['zone_block'] = $zone_block;

        return $this;
    }

    /**
     * Gets domainids_block
     *
     * @return int[]|null
     */
    public function getDomainidsBlock()
    {
        return $this->container['domainids_block'];
    }

    /**
     * Sets domainids_block
     *
     * @param int[]|null $domainids_block Domain id exclude-list (`bigdata.domains.id`).
     *
     * @return self
     */
    public function setDomainidsBlock($domainids_block)
    {
        if (is_null($domainids_block)) {
            throw new \InvalidArgumentException('non-nullable domainids_block cannot be null');
        }
        $this->container['domainids_block'] = $domainids_block;

        return $this;
    }

    /**
     * Gets carrier_id
     *
     * @return int[]|null
     */
    public function getCarrierId()
    {
        return $this->container['carrier_id'];
    }

    /**
     * Sets carrier_id
     *
     * @param int[]|null $carrier_id Cellular carrier id include-list (`isp_carriers.id`). Only meaningful when paired with `conn_type` containing `\"cellular\"`.
     *
     * @return self
     */
    public function setCarrierId($carrier_id)
    {
        if (is_null($carrier_id)) {
            throw new \InvalidArgumentException('non-nullable carrier_id cannot be null');
        }
        $this->container['carrier_id'] = $carrier_id;

        return $this;
    }

    /**
     * Gets conn_type
     *
     * @return string[]|null
     */
    public function getConnType()
    {
        return $this->container['conn_type'];
    }

    /**
     * Sets conn_type
     *
     * @param string[]|null $conn_type Connection-class include-list.
     *
     * @return self
     */
    public function setConnType($conn_type)
    {
        if (is_null($conn_type)) {
            throw new \InvalidArgumentException('non-nullable conn_type cannot be null');
        }
        $allowedValues = $this->getConnTypeAllowableValues();
        if (array_diff($conn_type, $allowedValues)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value for 'conn_type', must be one of '%s'",
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['conn_type'] = $conn_type;

        return $this;
    }

    /**
     * Gets lang
     *
     * @return string[]|null
     */
    public function getLang()
    {
        return $this->container['lang'];
    }

    /**
     * Sets lang
     *
     * @param string[]|null $lang Two-letter ISO language code include-list (e.g. `[\"en\", \"de\"]`).
     *
     * @return self
     */
    public function setLang($lang)
    {
        if (is_null($lang)) {
            throw new \InvalidArgumentException('non-nullable lang cannot be null');
        }
        $this->container['lang'] = $lang;

        return $this;
    }

    /**
     * Gets hour_start
     *
     * @return int|null
     */
    public function getHourStart()
    {
        return $this->container['hour_start'];
    }

    /**
     * Sets hour_start
     *
     * @param int|null $hour_start Inclusive start hour (0..23) of the daypart window in the rule's tz. Pair with `hour_end`. When omitted, the rule runs at any hour.
     *
     * @return self
     */
    public function setHourStart($hour_start)
    {
        if (is_null($hour_start)) {
            throw new \InvalidArgumentException('non-nullable hour_start cannot be null');
        }

        if (($hour_start > 23)) {
            throw new \InvalidArgumentException('invalid value for $hour_start when calling BidRuleConditions., must be smaller than or equal to 23.');
        }
        if (($hour_start < 0)) {
            throw new \InvalidArgumentException('invalid value for $hour_start when calling BidRuleConditions., must be bigger than or equal to 0.');
        }

        $this->container['hour_start'] = $hour_start;

        return $this;
    }

    /**
     * Gets hour_end
     *
     * @return int|null
     */
    public function getHourEnd()
    {
        return $this->container['hour_end'];
    }

    /**
     * Sets hour_end
     *
     * @param int|null $hour_end Inclusive end hour (0..23) of the daypart window in the rule's tz. When `hour_end < hour_start` the window wraps past midnight (e.g. 18→3 = 18:00 through 03:00 next day).
     *
     * @return self
     */
    public function setHourEnd($hour_end)
    {
        if (is_null($hour_end)) {
            throw new \InvalidArgumentException('non-nullable hour_end cannot be null');
        }

        if (($hour_end > 23)) {
            throw new \InvalidArgumentException('invalid value for $hour_end when calling BidRuleConditions., must be smaller than or equal to 23.');
        }
        if (($hour_end < 0)) {
            throw new \InvalidArgumentException('invalid value for $hour_end when calling BidRuleConditions., must be bigger than or equal to 0.');
        }

        $this->container['hour_end'] = $hour_end;

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



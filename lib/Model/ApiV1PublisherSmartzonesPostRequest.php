<?php
/**
 * ApiV1PublisherSmartzonesPostRequest
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
 * ApiV1PublisherSmartzonesPostRequest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1PublisherSmartzonesPostRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_publisher_smartzones_post_request';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'spaceid' => 'int',
        'smart_networkid' => 'int',
        'siteid' => 'int',
        'countries' => 'string',
        'device_type_ids' => 'string',
        'is_active' => 'bool',
        'select_type' => 'int',
        'select_pct' => 'int',
        'notes' => 'string',
        'creative' => 'string',
        'settings' => 'string',
        'use_adblock' => 'int',
        'network_zoneid' => 'int',
        'network_siteid' => 'int',
        'ssp_campaignid' => 'int',
        'scanning_enabled' => 'int'
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
        'spaceid' => null,
        'smart_networkid' => null,
        'siteid' => null,
        'countries' => null,
        'device_type_ids' => null,
        'is_active' => null,
        'select_type' => null,
        'select_pct' => null,
        'notes' => null,
        'creative' => null,
        'settings' => null,
        'use_adblock' => null,
        'network_zoneid' => 'int64',
        'network_siteid' => 'int64',
        'ssp_campaignid' => null,
        'scanning_enabled' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'spaceid' => false,
        'smart_networkid' => false,
        'siteid' => false,
        'countries' => false,
        'device_type_ids' => false,
        'is_active' => false,
        'select_type' => false,
        'select_pct' => false,
        'notes' => false,
        'creative' => false,
        'settings' => false,
        'use_adblock' => false,
        'network_zoneid' => false,
        'network_siteid' => false,
        'ssp_campaignid' => false,
        'scanning_enabled' => false
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
        'spaceid' => 'spaceid',
        'smart_networkid' => 'smart_networkid',
        'siteid' => 'siteid',
        'countries' => 'countries',
        'device_type_ids' => 'device_type_ids',
        'is_active' => 'is_active',
        'select_type' => 'select_type',
        'select_pct' => 'select_pct',
        'notes' => 'notes',
        'creative' => 'creative',
        'settings' => 'settings',
        'use_adblock' => 'use_adblock',
        'network_zoneid' => 'network_zoneid',
        'network_siteid' => 'network_siteid',
        'ssp_campaignid' => 'ssp_campaignid',
        'scanning_enabled' => 'scanning_enabled'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'spaceid' => 'setSpaceid',
        'smart_networkid' => 'setSmartNetworkid',
        'siteid' => 'setSiteid',
        'countries' => 'setCountries',
        'device_type_ids' => 'setDeviceTypeIds',
        'is_active' => 'setIsActive',
        'select_type' => 'setSelectType',
        'select_pct' => 'setSelectPct',
        'notes' => 'setNotes',
        'creative' => 'setCreative',
        'settings' => 'setSettings',
        'use_adblock' => 'setUseAdblock',
        'network_zoneid' => 'setNetworkZoneid',
        'network_siteid' => 'setNetworkSiteid',
        'ssp_campaignid' => 'setSspCampaignid',
        'scanning_enabled' => 'setScanningEnabled'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'spaceid' => 'getSpaceid',
        'smart_networkid' => 'getSmartNetworkid',
        'siteid' => 'getSiteid',
        'countries' => 'getCountries',
        'device_type_ids' => 'getDeviceTypeIds',
        'is_active' => 'getIsActive',
        'select_type' => 'getSelectType',
        'select_pct' => 'getSelectPct',
        'notes' => 'getNotes',
        'creative' => 'getCreative',
        'settings' => 'getSettings',
        'use_adblock' => 'getUseAdblock',
        'network_zoneid' => 'getNetworkZoneid',
        'network_siteid' => 'getNetworkSiteid',
        'ssp_campaignid' => 'getSspCampaignid',
        'scanning_enabled' => 'getScanningEnabled'
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

    public const SELECT_TYPE_NUMBER_0 = 0;
    public const SELECT_TYPE_NUMBER_1 = 1;
    public const SELECT_TYPE_NUMBER_2 = 2;
    public const SELECT_TYPE_NUMBER_3 = 3;
    public const USE_ADBLOCK_NUMBER_0 = 0;
    public const USE_ADBLOCK_NUMBER_1 = 1;
    public const SCANNING_ENABLED_NUMBER_0 = 0;
    public const SCANNING_ENABLED_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSelectTypeAllowableValues()
    {
        return [
            self::SELECT_TYPE_NUMBER_0,
            self::SELECT_TYPE_NUMBER_1,
            self::SELECT_TYPE_NUMBER_2,
            self::SELECT_TYPE_NUMBER_3,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseAdblockAllowableValues()
    {
        return [
            self::USE_ADBLOCK_NUMBER_0,
            self::USE_ADBLOCK_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getScanningEnabledAllowableValues()
    {
        return [
            self::SCANNING_ENABLED_NUMBER_0,
            self::SCANNING_ENABLED_NUMBER_1,
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
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('spaceid', $data ?? [], null);
        $this->setIfExists('smart_networkid', $data ?? [], null);
        $this->setIfExists('siteid', $data ?? [], null);
        $this->setIfExists('countries', $data ?? [], null);
        $this->setIfExists('device_type_ids', $data ?? [], null);
        $this->setIfExists('is_active', $data ?? [], true);
        $this->setIfExists('select_type', $data ?? [], null);
        $this->setIfExists('select_pct', $data ?? [], 100);
        $this->setIfExists('notes', $data ?? [], null);
        $this->setIfExists('creative', $data ?? [], null);
        $this->setIfExists('settings', $data ?? [], null);
        $this->setIfExists('use_adblock', $data ?? [], null);
        $this->setIfExists('network_zoneid', $data ?? [], null);
        $this->setIfExists('network_siteid', $data ?? [], null);
        $this->setIfExists('ssp_campaignid', $data ?? [], null);
        $this->setIfExists('scanning_enabled', $data ?? [], null);
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

        if ($this->container['name'] === null) {
            $invalidProperties[] = "'name' can't be null";
        }
        if ($this->container['spaceid'] === null) {
            $invalidProperties[] = "'spaceid' can't be null";
        }
        if (!is_null($this->container['smart_networkid']) && ($this->container['smart_networkid'] < 0)) {
            $invalidProperties[] = "invalid value for 'smart_networkid', must be bigger than or equal to 0.";
        }

        $allowedValues = $this->getSelectTypeAllowableValues();
        if (!is_null($this->container['select_type']) && !in_array($this->container['select_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'select_type', must be one of '%s'",
                $this->container['select_type'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['select_pct']) && ($this->container['select_pct'] > 100)) {
            $invalidProperties[] = "invalid value for 'select_pct', must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['select_pct']) && ($this->container['select_pct'] < 1)) {
            $invalidProperties[] = "invalid value for 'select_pct', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getUseAdblockAllowableValues();
        if (!is_null($this->container['use_adblock']) && !in_array($this->container['use_adblock'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_adblock', must be one of '%s'",
                $this->container['use_adblock'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getScanningEnabledAllowableValues();
        if (!is_null($this->container['scanning_enabled']) && !in_array($this->container['scanning_enabled'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'scanning_enabled', must be one of '%s'",
                $this->container['scanning_enabled'],
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
     * Gets name
     *
     * @return string
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     *
     * @param string $name name
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
     * Gets spaceid
     *
     * @return int
     */
    public function getSpaceid()
    {
        return $this->container['spaceid'];
    }

    /**
     * Sets spaceid
     *
     * @param int $spaceid Parent ad zone id (one of yours
     *
     * @return self
     */
    public function setSpaceid($spaceid)
    {
        if (is_null($spaceid)) {
            throw new \InvalidArgumentException('non-nullable spaceid cannot be null');
        }
        $this->container['spaceid'] = $spaceid;

        return $this;
    }

    /**
     * Gets smart_networkid
     *
     * @return int|null
     */
    public function getSmartNetworkid()
    {
        return $this->container['smart_networkid'];
    }

    /**
     * Sets smart_networkid
     *
     * @param int|null $smart_networkid Your smart network account id from `GET /api/v1/publisher/smartzones/networks` (`data[].id`). 0 = no network.
     *
     * @return self
     */
    public function setSmartNetworkid($smart_networkid)
    {
        if (is_null($smart_networkid)) {
            throw new \InvalidArgumentException('non-nullable smart_networkid cannot be null');
        }

        if (($smart_networkid < 0)) {
            throw new \InvalidArgumentException('invalid value for $smart_networkid when calling ApiV1PublisherSmartzonesPostRequest., must be bigger than or equal to 0.');
        }

        $this->container['smart_networkid'] = $smart_networkid;

        return $this;
    }

    /**
     * Gets siteid
     *
     * @return int|null
     */
    public function getSiteid()
    {
        return $this->container['siteid'];
    }

    /**
     * Sets siteid
     *
     * @param int|null $siteid Ignored — derived from `spaceid`.
     *
     * @return self
     */
    public function setSiteid($siteid)
    {
        if (is_null($siteid)) {
            throw new \InvalidArgumentException('non-nullable siteid cannot be null');
        }
        $this->container['siteid'] = $siteid;

        return $this;
    }

    /**
     * Gets countries
     *
     * @return string|null
     */
    public function getCountries()
    {
        return $this->container['countries'];
    }

    /**
     * Sets countries
     *
     * @param string|null $countries Comma-separated ISO-3166 alpha-2 codes. `0` = all countries.
     *
     * @return self
     */
    public function setCountries($countries)
    {
        if (is_null($countries)) {
            throw new \InvalidArgumentException('non-nullable countries cannot be null');
        }
        $this->container['countries'] = $countries;

        return $this;
    }

    /**
     * Gets device_type_ids
     *
     * @return string|null
     */
    public function getDeviceTypeIds()
    {
        return $this->container['device_type_ids'];
    }

    /**
     * Sets device_type_ids
     *
     * @param string|null $device_type_ids Comma-separated device type ids (setup-data `device_types`). `0` = all.
     *
     * @return self
     */
    public function setDeviceTypeIds($device_type_ids)
    {
        if (is_null($device_type_ids)) {
            throw new \InvalidArgumentException('non-nullable device_type_ids cannot be null');
        }
        $this->container['device_type_ids'] = $device_type_ids;

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
     * @param bool|null $is_active JSON boolean (an integer is refused as invalid JSON).
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
     * Gets select_type
     *
     * @return int|null
     */
    public function getSelectType()
    {
        return $this->container['select_type'];
    }

    /**
     * Sets select_type
     *
     * @param int|null $select_type 0 = percentage, 1 = eCPM, 2 = RTB, 3 = eCPM + limit %. A numeric string is accepted too.
     *
     * @return self
     */
    public function setSelectType($select_type)
    {
        if (is_null($select_type)) {
            throw new \InvalidArgumentException('non-nullable select_type cannot be null');
        }
        $allowedValues = $this->getSelectTypeAllowableValues();
        if (!in_array($select_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'select_type', must be one of '%s'",
                    $select_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['select_type'] = $select_type;

        return $this;
    }

    /**
     * Gets select_pct
     *
     * @return int|null
     */
    public function getSelectPct()
    {
        return $this->container['select_pct'];
    }

    /**
     * Sets select_pct
     *
     * @param int|null $select_pct Share of the ad zone's traffic (percent).
     *
     * @return self
     */
    public function setSelectPct($select_pct)
    {
        if (is_null($select_pct)) {
            throw new \InvalidArgumentException('non-nullable select_pct cannot be null');
        }

        if (($select_pct > 100)) {
            throw new \InvalidArgumentException('invalid value for $select_pct when calling ApiV1PublisherSmartzonesPostRequest., must be smaller than or equal to 100.');
        }
        if (($select_pct < 1)) {
            throw new \InvalidArgumentException('invalid value for $select_pct when calling ApiV1PublisherSmartzonesPostRequest., must be bigger than or equal to 1.');
        }

        $this->container['select_pct'] = $select_pct;

        return $this;
    }

    /**
     * Gets notes
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->container['notes'];
    }

    /**
     * Sets notes
     *
     * @param string|null $notes notes
     *
     * @return self
     */
    public function setNotes($notes)
    {
        if (is_null($notes)) {
            throw new \InvalidArgumentException('non-nullable notes cannot be null');
        }
        $this->container['notes'] = $notes;

        return $this;
    }

    /**
     * Gets creative
     *
     * @return string|null
     */
    public function getCreative()
    {
        return $this->container['creative'];
    }

    /**
     * Sets creative
     *
     * @param string|null $creative JSON-encoded string `{\"creative\": {\"type\": \"url\"|\"xml\"|\"code\", \"value\": \"…\"}}` — the tag/URL the network gave you.
     *
     * @return self
     */
    public function setCreative($creative)
    {
        if (is_null($creative)) {
            throw new \InvalidArgumentException('non-nullable creative cannot be null');
        }
        $this->container['creative'] = $creative;

        return $this;
    }

    /**
     * Gets settings
     *
     * @return string|null
     */
    public function getSettings()
    {
        return $this->container['settings'];
    }

    /**
     * Sets settings
     *
     * @param string|null $settings JSON-encoded string of zone settings (e.g. `referrer_stopwords`, `keywords_include`, `keywords_exclude`, `skip_when_stats`, `nw_zoneid`, `nw_siteid`). Keyword and stopword lists are normalised (trimmed, lower-cased, de-duplicated).
     *
     * @return self
     */
    public function setSettings($settings)
    {
        if (is_null($settings)) {
            throw new \InvalidArgumentException('non-nullable settings cannot be null');
        }
        $this->container['settings'] = $settings;

        return $this;
    }

    /**
     * Gets use_adblock
     *
     * @return int|null
     */
    public function getUseAdblock()
    {
        return $this->container['use_adblock'];
    }

    /**
     * Sets use_adblock
     *
     * @param int|null $use_adblock Panel checkbox \"Adblock enabled\": 1 = the zone may serve ad-blocked visitors.
     *
     * @return self
     */
    public function setUseAdblock($use_adblock)
    {
        if (is_null($use_adblock)) {
            throw new \InvalidArgumentException('non-nullable use_adblock cannot be null');
        }
        $allowedValues = $this->getUseAdblockAllowableValues();
        if (!in_array($use_adblock, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_adblock', must be one of '%s'",
                    $use_adblock,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_adblock'] = $use_adblock;

        return $this;
    }

    /**
     * Gets network_zoneid
     *
     * @return int|null
     */
    public function getNetworkZoneid()
    {
        return $this->container['network_zoneid'];
    }

    /**
     * Sets network_zoneid
     *
     * @param int|null $network_zoneid Numeric zone id on the network (alphanumeric ids go into `settings.nw_zoneid` with this at 0).
     *
     * @return self
     */
    public function setNetworkZoneid($network_zoneid)
    {
        if (is_null($network_zoneid)) {
            throw new \InvalidArgumentException('non-nullable network_zoneid cannot be null');
        }
        $this->container['network_zoneid'] = $network_zoneid;

        return $this;
    }

    /**
     * Gets network_siteid
     *
     * @return int|null
     */
    public function getNetworkSiteid()
    {
        return $this->container['network_siteid'];
    }

    /**
     * Sets network_siteid
     *
     * @param int|null $network_siteid Numeric site id on the network (alphanumeric ids go into `settings.nw_siteid`).
     *
     * @return self
     */
    public function setNetworkSiteid($network_siteid)
    {
        if (is_null($network_siteid)) {
            throw new \InvalidArgumentException('non-nullable network_siteid cannot be null');
        }
        $this->container['network_siteid'] = $network_siteid;

        return $this;
    }

    /**
     * Gets ssp_campaignid
     *
     * @return int|null
     */
    public function getSspCampaignid()
    {
        return $this->container['ssp_campaignid'];
    }

    /**
     * Sets ssp_campaignid
     *
     * @param int|null $ssp_campaignid One of your SSP campaigns (`GET /api/v1/publisher/smartzones/ssp-campaigns`) for RTB-type networks. 0 = none.
     *
     * @return self
     */
    public function setSspCampaignid($ssp_campaignid)
    {
        if (is_null($ssp_campaignid)) {
            throw new \InvalidArgumentException('non-nullable ssp_campaignid cannot be null');
        }
        $this->container['ssp_campaignid'] = $ssp_campaignid;

        return $this;
    }

    /**
     * Gets scanning_enabled
     *
     * @return int|null
     */
    public function getScanningEnabled()
    {
        return $this->container['scanning_enabled'];
    }

    /**
     * Sets scanning_enabled
     *
     * @param int|null $scanning_enabled Ad Scanner capture for this zone (when the tenant runs Ad Scanner).
     *
     * @return self
     */
    public function setScanningEnabled($scanning_enabled)
    {
        if (is_null($scanning_enabled)) {
            throw new \InvalidArgumentException('non-nullable scanning_enabled cannot be null');
        }
        $allowedValues = $this->getScanningEnabledAllowableValues();
        if (!in_array($scanning_enabled, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'scanning_enabled', must be one of '%s'",
                    $scanning_enabled,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['scanning_enabled'] = $scanning_enabled;

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



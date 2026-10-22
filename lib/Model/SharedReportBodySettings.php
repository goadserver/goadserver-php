<?php
/**
 * SharedReportBodySettings
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
 * SharedReportBodySettings Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SharedReportBodySettings implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'SharedReportBody_settings';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'ad_types' => 'int[]',
        'selling_types' => 'int[]',
        'device_types' => 'int[]',
        'countries' => 'string[]',
        'visible_metrics' => 'string[]',
        'visible_conv_types' => 'int[]',
        'allowed_groupby' => 'string[]',
        'default_groupby' => 'string',
        'allowed_viewtypes' => 'string[]',
        'viewer_filters' => 'string[]',
        'follow_groups' => 'bool',
        'show_names' => 'bool'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'ad_types' => null,
        'selling_types' => null,
        'device_types' => null,
        'countries' => null,
        'visible_metrics' => null,
        'visible_conv_types' => null,
        'allowed_groupby' => null,
        'default_groupby' => null,
        'allowed_viewtypes' => null,
        'viewer_filters' => null,
        'follow_groups' => null,
        'show_names' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'ad_types' => false,
        'selling_types' => false,
        'device_types' => false,
        'countries' => false,
        'visible_metrics' => false,
        'visible_conv_types' => false,
        'allowed_groupby' => false,
        'default_groupby' => false,
        'allowed_viewtypes' => false,
        'viewer_filters' => false,
        'follow_groups' => false,
        'show_names' => false
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
        'ad_types' => 'ad_types',
        'selling_types' => 'selling_types',
        'device_types' => 'device_types',
        'countries' => 'countries',
        'visible_metrics' => 'visible_metrics',
        'visible_conv_types' => 'visible_conv_types',
        'allowed_groupby' => 'allowed_groupby',
        'default_groupby' => 'default_groupby',
        'allowed_viewtypes' => 'allowed_viewtypes',
        'viewer_filters' => 'viewer_filters',
        'follow_groups' => 'follow_groups',
        'show_names' => 'show_names'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'ad_types' => 'setAdTypes',
        'selling_types' => 'setSellingTypes',
        'device_types' => 'setDeviceTypes',
        'countries' => 'setCountries',
        'visible_metrics' => 'setVisibleMetrics',
        'visible_conv_types' => 'setVisibleConvTypes',
        'allowed_groupby' => 'setAllowedGroupby',
        'default_groupby' => 'setDefaultGroupby',
        'allowed_viewtypes' => 'setAllowedViewtypes',
        'viewer_filters' => 'setViewerFilters',
        'follow_groups' => 'setFollowGroups',
        'show_names' => 'setShowNames'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'ad_types' => 'getAdTypes',
        'selling_types' => 'getSellingTypes',
        'device_types' => 'getDeviceTypes',
        'countries' => 'getCountries',
        'visible_metrics' => 'getVisibleMetrics',
        'visible_conv_types' => 'getVisibleConvTypes',
        'allowed_groupby' => 'getAllowedGroupby',
        'default_groupby' => 'getDefaultGroupby',
        'allowed_viewtypes' => 'getAllowedViewtypes',
        'viewer_filters' => 'getViewerFilters',
        'follow_groups' => 'getFollowGroups',
        'show_names' => 'getShowNames'
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
        $this->setIfExists('ad_types', $data ?? [], null);
        $this->setIfExists('selling_types', $data ?? [], null);
        $this->setIfExists('device_types', $data ?? [], null);
        $this->setIfExists('countries', $data ?? [], null);
        $this->setIfExists('visible_metrics', $data ?? [], null);
        $this->setIfExists('visible_conv_types', $data ?? [], null);
        $this->setIfExists('allowed_groupby', $data ?? [], null);
        $this->setIfExists('default_groupby', $data ?? [], 'period');
        $this->setIfExists('allowed_viewtypes', $data ?? [], null);
        $this->setIfExists('viewer_filters', $data ?? [], null);
        $this->setIfExists('follow_groups', $data ?? [], null);
        $this->setIfExists('show_names', $data ?? [], true);
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
     * Gets ad_types
     *
     * @return int[]|null
     */
    public function getAdTypes()
    {
        return $this->container['ad_types'];
    }

    /**
     * Sets ad_types
     *
     * @param int[]|null $ad_types ad_types
     *
     * @return self
     */
    public function setAdTypes($ad_types)
    {
        if (is_null($ad_types)) {
            throw new \InvalidArgumentException('non-nullable ad_types cannot be null');
        }
        $this->container['ad_types'] = $ad_types;

        return $this;
    }

    /**
     * Gets selling_types
     *
     * @return int[]|null
     */
    public function getSellingTypes()
    {
        return $this->container['selling_types'];
    }

    /**
     * Sets selling_types
     *
     * @param int[]|null $selling_types selling_types
     *
     * @return self
     */
    public function setSellingTypes($selling_types)
    {
        if (is_null($selling_types)) {
            throw new \InvalidArgumentException('non-nullable selling_types cannot be null');
        }
        $this->container['selling_types'] = $selling_types;

        return $this;
    }

    /**
     * Gets device_types
     *
     * @return int[]|null
     */
    public function getDeviceTypes()
    {
        return $this->container['device_types'];
    }

    /**
     * Sets device_types
     *
     * @param int[]|null $device_types device_types
     *
     * @return self
     */
    public function setDeviceTypes($device_types)
    {
        if (is_null($device_types)) {
            throw new \InvalidArgumentException('non-nullable device_types cannot be null');
        }
        $this->container['device_types'] = $device_types;

        return $this;
    }

    /**
     * Gets countries
     *
     * @return string[]|null
     */
    public function getCountries()
    {
        return $this->container['countries'];
    }

    /**
     * Sets countries
     *
     * @param string[]|null $countries countries
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
     * Gets visible_metrics
     *
     * @return string[]|null
     */
    public function getVisibleMetrics()
    {
        return $this->container['visible_metrics'];
    }

    /**
     * Sets visible_metrics
     *
     * @param string[]|null $visible_metrics visible_metrics
     *
     * @return self
     */
    public function setVisibleMetrics($visible_metrics)
    {
        if (is_null($visible_metrics)) {
            throw new \InvalidArgumentException('non-nullable visible_metrics cannot be null');
        }
        $this->container['visible_metrics'] = $visible_metrics;

        return $this;
    }

    /**
     * Gets visible_conv_types
     *
     * @return int[]|null
     */
    public function getVisibleConvTypes()
    {
        return $this->container['visible_conv_types'];
    }

    /**
     * Sets visible_conv_types
     *
     * @param int[]|null $visible_conv_types visible_conv_types
     *
     * @return self
     */
    public function setVisibleConvTypes($visible_conv_types)
    {
        if (is_null($visible_conv_types)) {
            throw new \InvalidArgumentException('non-nullable visible_conv_types cannot be null');
        }
        $this->container['visible_conv_types'] = $visible_conv_types;

        return $this;
    }

    /**
     * Gets allowed_groupby
     *
     * @return string[]|null
     */
    public function getAllowedGroupby()
    {
        return $this->container['allowed_groupby'];
    }

    /**
     * Sets allowed_groupby
     *
     * @param string[]|null $allowed_groupby allowed_groupby
     *
     * @return self
     */
    public function setAllowedGroupby($allowed_groupby)
    {
        if (is_null($allowed_groupby)) {
            throw new \InvalidArgumentException('non-nullable allowed_groupby cannot be null');
        }
        $this->container['allowed_groupby'] = $allowed_groupby;

        return $this;
    }

    /**
     * Gets default_groupby
     *
     * @return string|null
     */
    public function getDefaultGroupby()
    {
        return $this->container['default_groupby'];
    }

    /**
     * Sets default_groupby
     *
     * @param string|null $default_groupby default_groupby
     *
     * @return self
     */
    public function setDefaultGroupby($default_groupby)
    {
        if (is_null($default_groupby)) {
            throw new \InvalidArgumentException('non-nullable default_groupby cannot be null');
        }
        $this->container['default_groupby'] = $default_groupby;

        return $this;
    }

    /**
     * Gets allowed_viewtypes
     *
     * @return string[]|null
     */
    public function getAllowedViewtypes()
    {
        return $this->container['allowed_viewtypes'];
    }

    /**
     * Sets allowed_viewtypes
     *
     * @param string[]|null $allowed_viewtypes allowed_viewtypes
     *
     * @return self
     */
    public function setAllowedViewtypes($allowed_viewtypes)
    {
        if (is_null($allowed_viewtypes)) {
            throw new \InvalidArgumentException('non-nullable allowed_viewtypes cannot be null');
        }
        $this->container['allowed_viewtypes'] = $allowed_viewtypes;

        return $this;
    }

    /**
     * Gets viewer_filters
     *
     * @return string[]|null
     */
    public function getViewerFilters()
    {
        return $this->container['viewer_filters'];
    }

    /**
     * Sets viewer_filters
     *
     * @param string[]|null $viewer_filters viewer_filters
     *
     * @return self
     */
    public function setViewerFilters($viewer_filters)
    {
        if (is_null($viewer_filters)) {
            throw new \InvalidArgumentException('non-nullable viewer_filters cannot be null');
        }
        $this->container['viewer_filters'] = $viewer_filters;

        return $this;
    }

    /**
     * Gets follow_groups
     *
     * @return bool|null
     */
    public function getFollowGroups()
    {
        return $this->container['follow_groups'];
    }

    /**
     * Sets follow_groups
     *
     * @param bool|null $follow_groups Advertiser reports: also cover every campaign filed under `tagids`, now and later.
     *
     * @return self
     */
    public function setFollowGroups($follow_groups)
    {
        if (is_null($follow_groups)) {
            throw new \InvalidArgumentException('non-nullable follow_groups cannot be null');
        }
        $this->container['follow_groups'] = $follow_groups;

        return $this;
    }

    /**
     * Gets show_names
     *
     * @return bool|null
     */
    public function getShowNames()
    {
        return $this->container['show_names'];
    }

    /**
     * Sets show_names
     *
     * @param bool|null $show_names show_names
     *
     * @return self
     */
    public function setShowNames($show_names)
    {
        if (is_null($show_names)) {
            throw new \InvalidArgumentException('non-nullable show_names cannot be null');
        }
        $this->container['show_names'] = $show_names;

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



<?php
/**
 * CpaOptimizerBody
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
 * CpaOptimizerBody Class Doc Comment
 *
 * @category Class
 * @description CPA optimizer (target_cpa). Money in the account currency; stored as cents. &#x60;id&#x60; / &#x60;type&#x60; are accepted and ignored.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class CpaOptimizerBody implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'CpaOptimizerBody';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'title' => 'string',
        'filter_type' => 'string',
        'max_cpa' => 'float',
        'max_days' => 'int',
        'start_date' => '\DateTime',
        'target_cpa' => 'array<string,float>',
        'min_margin' => 'array<string,int>',
        'min_ctr' => 'float',
        'min_ctr_imps' => 'int',
        'max_ctr' => 'float',
        'max_ctr_imps' => 'int',
        'bounce_id' => 'int',
        'bounce_min_imps' => 'int',
        'bounce_max_perc' => 'int',
        'bounce_check_type' => 'string',
        'cpa_action' => 'string',
        'keep_spots_blocked' => 'int',
        'id' => 'int',
        'type' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'title' => null,
        'filter_type' => null,
        'max_cpa' => null,
        'max_days' => null,
        'start_date' => 'date',
        'target_cpa' => null,
        'min_margin' => null,
        'min_ctr' => null,
        'min_ctr_imps' => null,
        'max_ctr' => null,
        'max_ctr_imps' => null,
        'bounce_id' => null,
        'bounce_min_imps' => null,
        'bounce_max_perc' => null,
        'bounce_check_type' => null,
        'cpa_action' => null,
        'keep_spots_blocked' => null,
        'id' => null,
        'type' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'title' => false,
        'filter_type' => false,
        'max_cpa' => false,
        'max_days' => false,
        'start_date' => false,
        'target_cpa' => false,
        'min_margin' => false,
        'min_ctr' => false,
        'min_ctr_imps' => false,
        'max_ctr' => false,
        'max_ctr_imps' => false,
        'bounce_id' => false,
        'bounce_min_imps' => false,
        'bounce_max_perc' => false,
        'bounce_check_type' => false,
        'cpa_action' => false,
        'keep_spots_blocked' => false,
        'id' => false,
        'type' => false
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
        'title' => 'title',
        'filter_type' => 'filter_type',
        'max_cpa' => 'max_cpa',
        'max_days' => 'max_days',
        'start_date' => 'start_date',
        'target_cpa' => 'target_cpa',
        'min_margin' => 'min_margin',
        'min_ctr' => 'min_ctr',
        'min_ctr_imps' => 'min_ctr_imps',
        'max_ctr' => 'max_ctr',
        'max_ctr_imps' => 'max_ctr_imps',
        'bounce_id' => 'bounce_id',
        'bounce_min_imps' => 'bounce_min_imps',
        'bounce_max_perc' => 'bounce_max_perc',
        'bounce_check_type' => 'bounce_check_type',
        'cpa_action' => 'cpa_action',
        'keep_spots_blocked' => 'keep_spots_blocked',
        'id' => 'id',
        'type' => 'type'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'title' => 'setTitle',
        'filter_type' => 'setFilterType',
        'max_cpa' => 'setMaxCpa',
        'max_days' => 'setMaxDays',
        'start_date' => 'setStartDate',
        'target_cpa' => 'setTargetCpa',
        'min_margin' => 'setMinMargin',
        'min_ctr' => 'setMinCtr',
        'min_ctr_imps' => 'setMinCtrImps',
        'max_ctr' => 'setMaxCtr',
        'max_ctr_imps' => 'setMaxCtrImps',
        'bounce_id' => 'setBounceId',
        'bounce_min_imps' => 'setBounceMinImps',
        'bounce_max_perc' => 'setBounceMaxPerc',
        'bounce_check_type' => 'setBounceCheckType',
        'cpa_action' => 'setCpaAction',
        'keep_spots_blocked' => 'setKeepSpotsBlocked',
        'id' => 'setId',
        'type' => 'setType'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'title' => 'getTitle',
        'filter_type' => 'getFilterType',
        'max_cpa' => 'getMaxCpa',
        'max_days' => 'getMaxDays',
        'start_date' => 'getStartDate',
        'target_cpa' => 'getTargetCpa',
        'min_margin' => 'getMinMargin',
        'min_ctr' => 'getMinCtr',
        'min_ctr_imps' => 'getMinCtrImps',
        'max_ctr' => 'getMaxCtr',
        'max_ctr_imps' => 'getMaxCtrImps',
        'bounce_id' => 'getBounceId',
        'bounce_min_imps' => 'getBounceMinImps',
        'bounce_max_perc' => 'getBounceMaxPerc',
        'bounce_check_type' => 'getBounceCheckType',
        'cpa_action' => 'getCpaAction',
        'keep_spots_blocked' => 'getKeepSpotsBlocked',
        'id' => 'getId',
        'type' => 'getType'
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

    public const FILTER_TYPE_SPACEID = 'spaceid';
    public const FILTER_TYPE_ISPID = 'ispid';
    public const FILTER_TYPE_DOMAINID = 'domainid';
    public const BOUNCE_CHECK_TYPE_AUTO = 'auto';
    public const BOUNCE_CHECK_TYPE_VIEWS = 'views';
    public const BOUNCE_CHECK_TYPE_CLICKS = 'clicks';
    public const CPA_ACTION_BLOCK = 'block';
    public const CPA_ACTION_CHANGE_BID = 'change_bid';
    public const KEEP_SPOTS_BLOCKED_NUMBER_0 = 0;
    public const KEEP_SPOTS_BLOCKED_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getFilterTypeAllowableValues()
    {
        return [
            self::FILTER_TYPE_SPACEID,
            self::FILTER_TYPE_ISPID,
            self::FILTER_TYPE_DOMAINID,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getBounceCheckTypeAllowableValues()
    {
        return [
            self::BOUNCE_CHECK_TYPE_AUTO,
            self::BOUNCE_CHECK_TYPE_VIEWS,
            self::BOUNCE_CHECK_TYPE_CLICKS,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getCpaActionAllowableValues()
    {
        return [
            self::CPA_ACTION_BLOCK,
            self::CPA_ACTION_CHANGE_BID,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getKeepSpotsBlockedAllowableValues()
    {
        return [
            self::KEEP_SPOTS_BLOCKED_NUMBER_0,
            self::KEEP_SPOTS_BLOCKED_NUMBER_1,
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
        $this->setIfExists('title', $data ?? [], null);
        $this->setIfExists('filter_type', $data ?? [], null);
        $this->setIfExists('max_cpa', $data ?? [], null);
        $this->setIfExists('max_days', $data ?? [], null);
        $this->setIfExists('start_date', $data ?? [], null);
        $this->setIfExists('target_cpa', $data ?? [], null);
        $this->setIfExists('min_margin', $data ?? [], null);
        $this->setIfExists('min_ctr', $data ?? [], null);
        $this->setIfExists('min_ctr_imps', $data ?? [], null);
        $this->setIfExists('max_ctr', $data ?? [], null);
        $this->setIfExists('max_ctr_imps', $data ?? [], null);
        $this->setIfExists('bounce_id', $data ?? [], null);
        $this->setIfExists('bounce_min_imps', $data ?? [], null);
        $this->setIfExists('bounce_max_perc', $data ?? [], null);
        $this->setIfExists('bounce_check_type', $data ?? [], null);
        $this->setIfExists('cpa_action', $data ?? [], null);
        $this->setIfExists('keep_spots_blocked', $data ?? [], null);
        $this->setIfExists('id', $data ?? [], null);
        $this->setIfExists('type', $data ?? [], null);
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

        if ($this->container['title'] === null) {
            $invalidProperties[] = "'title' can't be null";
        }
        $allowedValues = $this->getFilterTypeAllowableValues();
        if (!is_null($this->container['filter_type']) && !in_array($this->container['filter_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'filter_type', must be one of '%s'",
                $this->container['filter_type'],
                implode("', '", $allowedValues)
            );
        }

        if ($this->container['max_cpa'] === null) {
            $invalidProperties[] = "'max_cpa' can't be null";
        }
        if (!is_null($this->container['bounce_max_perc']) && ($this->container['bounce_max_perc'] > 100)) {
            $invalidProperties[] = "invalid value for 'bounce_max_perc', must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['bounce_max_perc']) && ($this->container['bounce_max_perc'] < 0)) {
            $invalidProperties[] = "invalid value for 'bounce_max_perc', must be bigger than or equal to 0.";
        }

        $allowedValues = $this->getBounceCheckTypeAllowableValues();
        if (!is_null($this->container['bounce_check_type']) && !in_array($this->container['bounce_check_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'bounce_check_type', must be one of '%s'",
                $this->container['bounce_check_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getCpaActionAllowableValues();
        if (!is_null($this->container['cpa_action']) && !in_array($this->container['cpa_action'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'cpa_action', must be one of '%s'",
                $this->container['cpa_action'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getKeepSpotsBlockedAllowableValues();
        if (!is_null($this->container['keep_spots_blocked']) && !in_array($this->container['keep_spots_blocked'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'keep_spots_blocked', must be one of '%s'",
                $this->container['keep_spots_blocked'],
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
     * Gets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->container['title'];
    }

    /**
     * Sets title
     *
     * @param string $title Letters, digits and spaces; anything else becomes `_`.
     *
     * @return self
     */
    public function setTitle($title)
    {
        if (is_null($title)) {
            throw new \InvalidArgumentException('non-nullable title cannot be null');
        }
        $this->container['title'] = $title;

        return $this;
    }

    /**
     * Gets filter_type
     *
     * @return string|null
     */
    public function getFilterType()
    {
        return $this->container['filter_type'];
    }

    /**
     * Sets filter_type
     *
     * @param string|null $filter_type What a rule blocks / re-bids. Fixed after create in the panel.
     *
     * @return self
     */
    public function setFilterType($filter_type)
    {
        if (is_null($filter_type)) {
            throw new \InvalidArgumentException('non-nullable filter_type cannot be null');
        }
        $allowedValues = $this->getFilterTypeAllowableValues();
        if (!in_array($filter_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'filter_type', must be one of '%s'",
                    $filter_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['filter_type'] = $filter_type;

        return $this;
    }

    /**
     * Gets max_cpa
     *
     * @return float
     */
    public function getMaxCpa()
    {
        return $this->container['max_cpa'];
    }

    /**
     * Sets max_cpa
     *
     * @param float $max_cpa Spend (account currency) on a source before it is judged. Required, non-zero.
     *
     * @return self
     */
    public function setMaxCpa($max_cpa)
    {
        if (is_null($max_cpa)) {
            throw new \InvalidArgumentException('non-nullable max_cpa cannot be null');
        }
        $this->container['max_cpa'] = $max_cpa;

        return $this;
    }

    /**
     * Gets max_days
     *
     * @return int|null
     */
    public function getMaxDays()
    {
        return $this->container['max_days'];
    }

    /**
     * Sets max_days
     *
     * @param int|null $max_days max_days
     *
     * @return self
     */
    public function setMaxDays($max_days)
    {
        if (is_null($max_days)) {
            throw new \InvalidArgumentException('non-nullable max_days cannot be null');
        }
        $this->container['max_days'] = $max_days;

        return $this;
    }

    /**
     * Gets start_date
     *
     * @return \DateTime|null
     */
    public function getStartDate()
    {
        return $this->container['start_date'];
    }

    /**
     * Sets start_date
     *
     * @param \DateTime|null $start_date start_date
     *
     * @return self
     */
    public function setStartDate($start_date)
    {
        if (is_null($start_date)) {
            throw new \InvalidArgumentException('non-nullable start_date cannot be null');
        }
        $this->container['start_date'] = $start_date;

        return $this;
    }

    /**
     * Gets target_cpa
     *
     * @return array<string,float>|null
     */
    public function getTargetCpa()
    {
        return $this->container['target_cpa'];
    }

    /**
     * Sets target_cpa
     *
     * @param array<string,float>|null $target_cpa Conversion-type id → target CPA (account currency). Zero entries are dropped.
     *
     * @return self
     */
    public function setTargetCpa($target_cpa)
    {
        if (is_null($target_cpa)) {
            throw new \InvalidArgumentException('non-nullable target_cpa cannot be null');
        }
        $this->container['target_cpa'] = $target_cpa;

        return $this;
    }

    /**
     * Gets min_margin
     *
     * @return array<string,int>|null
     */
    public function getMinMargin()
    {
        return $this->container['min_margin'];
    }

    /**
     * Sets min_margin
     *
     * @param array<string,int>|null $min_margin Conversion-type id → minimum margin %.
     *
     * @return self
     */
    public function setMinMargin($min_margin)
    {
        if (is_null($min_margin)) {
            throw new \InvalidArgumentException('non-nullable min_margin cannot be null');
        }
        $this->container['min_margin'] = $min_margin;

        return $this;
    }

    /**
     * Gets min_ctr
     *
     * @return float|null
     */
    public function getMinCtr()
    {
        return $this->container['min_ctr'];
    }

    /**
     * Sets min_ctr
     *
     * @param float|null $min_ctr min_ctr
     *
     * @return self
     */
    public function setMinCtr($min_ctr)
    {
        if (is_null($min_ctr)) {
            throw new \InvalidArgumentException('non-nullable min_ctr cannot be null');
        }
        $this->container['min_ctr'] = $min_ctr;

        return $this;
    }

    /**
     * Gets min_ctr_imps
     *
     * @return int|null
     */
    public function getMinCtrImps()
    {
        return $this->container['min_ctr_imps'];
    }

    /**
     * Sets min_ctr_imps
     *
     * @param int|null $min_ctr_imps min_ctr_imps
     *
     * @return self
     */
    public function setMinCtrImps($min_ctr_imps)
    {
        if (is_null($min_ctr_imps)) {
            throw new \InvalidArgumentException('non-nullable min_ctr_imps cannot be null');
        }
        $this->container['min_ctr_imps'] = $min_ctr_imps;

        return $this;
    }

    /**
     * Gets max_ctr
     *
     * @return float|null
     */
    public function getMaxCtr()
    {
        return $this->container['max_ctr'];
    }

    /**
     * Sets max_ctr
     *
     * @param float|null $max_ctr max_ctr
     *
     * @return self
     */
    public function setMaxCtr($max_ctr)
    {
        if (is_null($max_ctr)) {
            throw new \InvalidArgumentException('non-nullable max_ctr cannot be null');
        }
        $this->container['max_ctr'] = $max_ctr;

        return $this;
    }

    /**
     * Gets max_ctr_imps
     *
     * @return int|null
     */
    public function getMaxCtrImps()
    {
        return $this->container['max_ctr_imps'];
    }

    /**
     * Sets max_ctr_imps
     *
     * @param int|null $max_ctr_imps max_ctr_imps
     *
     * @return self
     */
    public function setMaxCtrImps($max_ctr_imps)
    {
        if (is_null($max_ctr_imps)) {
            throw new \InvalidArgumentException('non-nullable max_ctr_imps cannot be null');
        }
        $this->container['max_ctr_imps'] = $max_ctr_imps;

        return $this;
    }

    /**
     * Gets bounce_id
     *
     * @return int|null
     */
    public function getBounceId()
    {
        return $this->container['bounce_id'];
    }

    /**
     * Sets bounce_id
     *
     * @param int|null $bounce_id Conversion pixel used as the bounce signal; -1 = off.
     *
     * @return self
     */
    public function setBounceId($bounce_id)
    {
        if (is_null($bounce_id)) {
            throw new \InvalidArgumentException('non-nullable bounce_id cannot be null');
        }
        $this->container['bounce_id'] = $bounce_id;

        return $this;
    }

    /**
     * Gets bounce_min_imps
     *
     * @return int|null
     */
    public function getBounceMinImps()
    {
        return $this->container['bounce_min_imps'];
    }

    /**
     * Sets bounce_min_imps
     *
     * @param int|null $bounce_min_imps bounce_min_imps
     *
     * @return self
     */
    public function setBounceMinImps($bounce_min_imps)
    {
        if (is_null($bounce_min_imps)) {
            throw new \InvalidArgumentException('non-nullable bounce_min_imps cannot be null');
        }
        $this->container['bounce_min_imps'] = $bounce_min_imps;

        return $this;
    }

    /**
     * Gets bounce_max_perc
     *
     * @return int|null
     */
    public function getBounceMaxPerc()
    {
        return $this->container['bounce_max_perc'];
    }

    /**
     * Sets bounce_max_perc
     *
     * @param int|null $bounce_max_perc bounce_max_perc
     *
     * @return self
     */
    public function setBounceMaxPerc($bounce_max_perc)
    {
        if (is_null($bounce_max_perc)) {
            throw new \InvalidArgumentException('non-nullable bounce_max_perc cannot be null');
        }

        if (($bounce_max_perc > 100)) {
            throw new \InvalidArgumentException('invalid value for $bounce_max_perc when calling CpaOptimizerBody., must be smaller than or equal to 100.');
        }
        if (($bounce_max_perc < 0)) {
            throw new \InvalidArgumentException('invalid value for $bounce_max_perc when calling CpaOptimizerBody., must be bigger than or equal to 0.');
        }

        $this->container['bounce_max_perc'] = $bounce_max_perc;

        return $this;
    }

    /**
     * Gets bounce_check_type
     *
     * @return string|null
     */
    public function getBounceCheckType()
    {
        return $this->container['bounce_check_type'];
    }

    /**
     * Sets bounce_check_type
     *
     * @param string|null $bounce_check_type bounce_check_type
     *
     * @return self
     */
    public function setBounceCheckType($bounce_check_type)
    {
        if (is_null($bounce_check_type)) {
            throw new \InvalidArgumentException('non-nullable bounce_check_type cannot be null');
        }
        $allowedValues = $this->getBounceCheckTypeAllowableValues();
        if (!in_array($bounce_check_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'bounce_check_type', must be one of '%s'",
                    $bounce_check_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['bounce_check_type'] = $bounce_check_type;

        return $this;
    }

    /**
     * Gets cpa_action
     *
     * @return string|null
     */
    public function getCpaAction()
    {
        return $this->container['cpa_action'];
    }

    /**
     * Sets cpa_action
     *
     * @param string|null $cpa_action cpa_action
     *
     * @return self
     */
    public function setCpaAction($cpa_action)
    {
        if (is_null($cpa_action)) {
            throw new \InvalidArgumentException('non-nullable cpa_action cannot be null');
        }
        $allowedValues = $this->getCpaActionAllowableValues();
        if (!in_array($cpa_action, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'cpa_action', must be one of '%s'",
                    $cpa_action,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['cpa_action'] = $cpa_action;

        return $this;
    }

    /**
     * Gets keep_spots_blocked
     *
     * @return int|null
     */
    public function getKeepSpotsBlocked()
    {
        return $this->container['keep_spots_blocked'];
    }

    /**
     * Sets keep_spots_blocked
     *
     * @param int|null $keep_spots_blocked keep_spots_blocked
     *
     * @return self
     */
    public function setKeepSpotsBlocked($keep_spots_blocked)
    {
        if (is_null($keep_spots_blocked)) {
            throw new \InvalidArgumentException('non-nullable keep_spots_blocked cannot be null');
        }
        $allowedValues = $this->getKeepSpotsBlockedAllowableValues();
        if (!in_array($keep_spots_blocked, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'keep_spots_blocked', must be one of '%s'",
                    $keep_spots_blocked,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['keep_spots_blocked'] = $keep_spots_blocked;

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
     * Gets type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Sets type
     *
     * @param string|null $type type
     *
     * @return self
     */
    public function setType($type)
    {
        if (is_null($type)) {
            throw new \InvalidArgumentException('non-nullable type cannot be null');
        }
        $this->container['type'] = $type;

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



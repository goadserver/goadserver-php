<?php
/**
 * CpmOptimizerBody
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
 * CpmOptimizerBody Class Doc Comment
 *
 * @category Class
 * @description CPM optimizer settings (the panel&#39;s Source Optimizer → CPM form). Money in the account currency, as entered. &#x60;id&#x60;, &#x60;type&#x60;, &#x60;userid&#x60; are accepted and ignored so a fetched record can be sent back.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class CpmOptimizerBody implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'CpmOptimizerBody';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'title' => 'string',
        'opt_type' => 'string',
        'min_spendings' => 'float',
        'min_impressions' => 'int',
        'max_days' => 'int',
        'start_date' => '\DateTime',
        'conv_type' => 'string',
        'optimise_with' => 'string',
        'max_cpa' => 'float',
        'allowed_margin' => 'float',
        'use_bidoptimiser' => 'int',
        'increase_bids' => 'int',
        'increase_bid_perc' => 'int',
        'increase_bid_cap' => 'int',
        'decrease_bids' => 'int',
        'decrease_bid_perc' => 'int',
        'decrease_bid_cap' => 'int',
        'min_ctr' => 'float',
        'min_ctr_imps' => 'int',
        'max_ctr' => 'float',
        'max_ctr_imps' => 'int',
        'id' => 'int',
        'type' => 'string',
        'userid' => 'int'
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
        'opt_type' => null,
        'min_spendings' => null,
        'min_impressions' => null,
        'max_days' => null,
        'start_date' => 'date',
        'conv_type' => null,
        'optimise_with' => null,
        'max_cpa' => null,
        'allowed_margin' => null,
        'use_bidoptimiser' => null,
        'increase_bids' => null,
        'increase_bid_perc' => null,
        'increase_bid_cap' => null,
        'decrease_bids' => null,
        'decrease_bid_perc' => null,
        'decrease_bid_cap' => null,
        'min_ctr' => null,
        'min_ctr_imps' => null,
        'max_ctr' => null,
        'max_ctr_imps' => null,
        'id' => null,
        'type' => null,
        'userid' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'title' => false,
        'opt_type' => false,
        'min_spendings' => false,
        'min_impressions' => false,
        'max_days' => false,
        'start_date' => false,
        'conv_type' => false,
        'optimise_with' => false,
        'max_cpa' => false,
        'allowed_margin' => false,
        'use_bidoptimiser' => false,
        'increase_bids' => false,
        'increase_bid_perc' => false,
        'increase_bid_cap' => false,
        'decrease_bids' => false,
        'decrease_bid_perc' => false,
        'decrease_bid_cap' => false,
        'min_ctr' => false,
        'min_ctr_imps' => false,
        'max_ctr' => false,
        'max_ctr_imps' => false,
        'id' => false,
        'type' => false,
        'userid' => false
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
        'opt_type' => 'opt_type',
        'min_spendings' => 'min_spendings',
        'min_impressions' => 'min_impressions',
        'max_days' => 'max_days',
        'start_date' => 'start_date',
        'conv_type' => 'conv_type',
        'optimise_with' => 'optimise_with',
        'max_cpa' => 'max_cpa',
        'allowed_margin' => 'allowed_margin',
        'use_bidoptimiser' => 'use_bidoptimiser',
        'increase_bids' => 'increase_bids',
        'increase_bid_perc' => 'increase_bid_perc',
        'increase_bid_cap' => 'increase_bid_cap',
        'decrease_bids' => 'decrease_bids',
        'decrease_bid_perc' => 'decrease_bid_perc',
        'decrease_bid_cap' => 'decrease_bid_cap',
        'min_ctr' => 'min_ctr',
        'min_ctr_imps' => 'min_ctr_imps',
        'max_ctr' => 'max_ctr',
        'max_ctr_imps' => 'max_ctr_imps',
        'id' => 'id',
        'type' => 'type',
        'userid' => 'userid'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'title' => 'setTitle',
        'opt_type' => 'setOptType',
        'min_spendings' => 'setMinSpendings',
        'min_impressions' => 'setMinImpressions',
        'max_days' => 'setMaxDays',
        'start_date' => 'setStartDate',
        'conv_type' => 'setConvType',
        'optimise_with' => 'setOptimiseWith',
        'max_cpa' => 'setMaxCpa',
        'allowed_margin' => 'setAllowedMargin',
        'use_bidoptimiser' => 'setUseBidoptimiser',
        'increase_bids' => 'setIncreaseBids',
        'increase_bid_perc' => 'setIncreaseBidPerc',
        'increase_bid_cap' => 'setIncreaseBidCap',
        'decrease_bids' => 'setDecreaseBids',
        'decrease_bid_perc' => 'setDecreaseBidPerc',
        'decrease_bid_cap' => 'setDecreaseBidCap',
        'min_ctr' => 'setMinCtr',
        'min_ctr_imps' => 'setMinCtrImps',
        'max_ctr' => 'setMaxCtr',
        'max_ctr_imps' => 'setMaxCtrImps',
        'id' => 'setId',
        'type' => 'setType',
        'userid' => 'setUserid'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'title' => 'getTitle',
        'opt_type' => 'getOptType',
        'min_spendings' => 'getMinSpendings',
        'min_impressions' => 'getMinImpressions',
        'max_days' => 'getMaxDays',
        'start_date' => 'getStartDate',
        'conv_type' => 'getConvType',
        'optimise_with' => 'getOptimiseWith',
        'max_cpa' => 'getMaxCpa',
        'allowed_margin' => 'getAllowedMargin',
        'use_bidoptimiser' => 'getUseBidoptimiser',
        'increase_bids' => 'getIncreaseBids',
        'increase_bid_perc' => 'getIncreaseBidPerc',
        'increase_bid_cap' => 'getIncreaseBidCap',
        'decrease_bids' => 'getDecreaseBids',
        'decrease_bid_perc' => 'getDecreaseBidPerc',
        'decrease_bid_cap' => 'getDecreaseBidCap',
        'min_ctr' => 'getMinCtr',
        'min_ctr_imps' => 'getMinCtrImps',
        'max_ctr' => 'getMaxCtr',
        'max_ctr_imps' => 'getMaxCtrImps',
        'id' => 'getId',
        'type' => 'getType',
        'userid' => 'getUserid'
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

    public const OPT_TYPE__0 = '0';
    public const OPT_TYPE__1 = '1';
    public const OPTIMISE_WITH_CONV_COUNT = 'conv_count';
    public const OPTIMISE_WITH_CONV_EARNINGS = 'conv_earnings';
    public const USE_BIDOPTIMISER_NUMBER_0 = 0;
    public const USE_BIDOPTIMISER_NUMBER_1 = 1;
    public const INCREASE_BIDS_NUMBER_0 = 0;
    public const INCREASE_BIDS_NUMBER_1 = 1;
    public const DECREASE_BIDS_NUMBER_0 = 0;
    public const DECREASE_BIDS_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getOptTypeAllowableValues()
    {
        return [
            self::OPT_TYPE__0,
            self::OPT_TYPE__1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getOptimiseWithAllowableValues()
    {
        return [
            self::OPTIMISE_WITH_CONV_COUNT,
            self::OPTIMISE_WITH_CONV_EARNINGS,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseBidoptimiserAllowableValues()
    {
        return [
            self::USE_BIDOPTIMISER_NUMBER_0,
            self::USE_BIDOPTIMISER_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getIncreaseBidsAllowableValues()
    {
        return [
            self::INCREASE_BIDS_NUMBER_0,
            self::INCREASE_BIDS_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getDecreaseBidsAllowableValues()
    {
        return [
            self::DECREASE_BIDS_NUMBER_0,
            self::DECREASE_BIDS_NUMBER_1,
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
        $this->setIfExists('opt_type', $data ?? [], null);
        $this->setIfExists('min_spendings', $data ?? [], null);
        $this->setIfExists('min_impressions', $data ?? [], null);
        $this->setIfExists('max_days', $data ?? [], null);
        $this->setIfExists('start_date', $data ?? [], null);
        $this->setIfExists('conv_type', $data ?? [], null);
        $this->setIfExists('optimise_with', $data ?? [], null);
        $this->setIfExists('max_cpa', $data ?? [], null);
        $this->setIfExists('allowed_margin', $data ?? [], null);
        $this->setIfExists('use_bidoptimiser', $data ?? [], null);
        $this->setIfExists('increase_bids', $data ?? [], null);
        $this->setIfExists('increase_bid_perc', $data ?? [], null);
        $this->setIfExists('increase_bid_cap', $data ?? [], null);
        $this->setIfExists('decrease_bids', $data ?? [], null);
        $this->setIfExists('decrease_bid_perc', $data ?? [], null);
        $this->setIfExists('decrease_bid_cap', $data ?? [], null);
        $this->setIfExists('min_ctr', $data ?? [], null);
        $this->setIfExists('min_ctr_imps', $data ?? [], null);
        $this->setIfExists('max_ctr', $data ?? [], null);
        $this->setIfExists('max_ctr_imps', $data ?? [], null);
        $this->setIfExists('id', $data ?? [], null);
        $this->setIfExists('type', $data ?? [], null);
        $this->setIfExists('userid', $data ?? [], null);
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
        $allowedValues = $this->getOptTypeAllowableValues();
        if (!is_null($this->container['opt_type']) && !in_array($this->container['opt_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'opt_type', must be one of '%s'",
                $this->container['opt_type'],
                implode("', '", $allowedValues)
            );
        }

        if ($this->container['min_spendings'] === null) {
            $invalidProperties[] = "'min_spendings' can't be null";
        }
        $allowedValues = $this->getOptimiseWithAllowableValues();
        if (!is_null($this->container['optimise_with']) && !in_array($this->container['optimise_with'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'optimise_with', must be one of '%s'",
                $this->container['optimise_with'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getUseBidoptimiserAllowableValues();
        if (!is_null($this->container['use_bidoptimiser']) && !in_array($this->container['use_bidoptimiser'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_bidoptimiser', must be one of '%s'",
                $this->container['use_bidoptimiser'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getIncreaseBidsAllowableValues();
        if (!is_null($this->container['increase_bids']) && !in_array($this->container['increase_bids'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'increase_bids', must be one of '%s'",
                $this->container['increase_bids'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['increase_bid_perc']) && ($this->container['increase_bid_perc'] > 99)) {
            $invalidProperties[] = "invalid value for 'increase_bid_perc', must be smaller than or equal to 99.";
        }

        if (!is_null($this->container['increase_bid_perc']) && ($this->container['increase_bid_perc'] < 1)) {
            $invalidProperties[] = "invalid value for 'increase_bid_perc', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['increase_bid_cap']) && ($this->container['increase_bid_cap'] > 99)) {
            $invalidProperties[] = "invalid value for 'increase_bid_cap', must be smaller than or equal to 99.";
        }

        if (!is_null($this->container['increase_bid_cap']) && ($this->container['increase_bid_cap'] < 1)) {
            $invalidProperties[] = "invalid value for 'increase_bid_cap', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getDecreaseBidsAllowableValues();
        if (!is_null($this->container['decrease_bids']) && !in_array($this->container['decrease_bids'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'decrease_bids', must be one of '%s'",
                $this->container['decrease_bids'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['decrease_bid_perc']) && ($this->container['decrease_bid_perc'] > 99)) {
            $invalidProperties[] = "invalid value for 'decrease_bid_perc', must be smaller than or equal to 99.";
        }

        if (!is_null($this->container['decrease_bid_perc']) && ($this->container['decrease_bid_perc'] < 1)) {
            $invalidProperties[] = "invalid value for 'decrease_bid_perc', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['decrease_bid_cap']) && ($this->container['decrease_bid_cap'] > 99)) {
            $invalidProperties[] = "invalid value for 'decrease_bid_cap', must be smaller than or equal to 99.";
        }

        if (!is_null($this->container['decrease_bid_cap']) && ($this->container['decrease_bid_cap'] < 1)) {
            $invalidProperties[] = "invalid value for 'decrease_bid_cap', must be bigger than or equal to 1.";
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
     * @param string $title title
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
     * Gets opt_type
     *
     * @return string|null
     */
    public function getOptType()
    {
        return $this->container['opt_type'];
    }

    /**
     * Sets opt_type
     *
     * @param string|null $opt_type 0 = start after min_spendings, 1 = after min_impressions.
     *
     * @return self
     */
    public function setOptType($opt_type)
    {
        if (is_null($opt_type)) {
            throw new \InvalidArgumentException('non-nullable opt_type cannot be null');
        }
        $allowedValues = $this->getOptTypeAllowableValues();
        if (!in_array($opt_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'opt_type', must be one of '%s'",
                    $opt_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['opt_type'] = $opt_type;

        return $this;
    }

    /**
     * Gets min_spendings
     *
     * @return float
     */
    public function getMinSpendings()
    {
        return $this->container['min_spendings'];
    }

    /**
     * Sets min_spendings
     *
     * @param float $min_spendings Spend (account currency) before a source is judged. Required, non-zero.
     *
     * @return self
     */
    public function setMinSpendings($min_spendings)
    {
        if (is_null($min_spendings)) {
            throw new \InvalidArgumentException('non-nullable min_spendings cannot be null');
        }
        $this->container['min_spendings'] = $min_spendings;

        return $this;
    }

    /**
     * Gets min_impressions
     *
     * @return int|null
     */
    public function getMinImpressions()
    {
        return $this->container['min_impressions'];
    }

    /**
     * Sets min_impressions
     *
     * @param int|null $min_impressions min_impressions
     *
     * @return self
     */
    public function setMinImpressions($min_impressions)
    {
        if (is_null($min_impressions)) {
            throw new \InvalidArgumentException('non-nullable min_impressions cannot be null');
        }
        $this->container['min_impressions'] = $min_impressions;

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
     * @param int|null $max_days Stats look-back in days.
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
     * Gets conv_type
     *
     * @return string|null
     */
    public function getConvType()
    {
        return $this->container['conv_type'];
    }

    /**
     * Sets conv_type
     *
     * @param string|null $conv_type Conversion-type id judged on.
     *
     * @return self
     */
    public function setConvType($conv_type)
    {
        if (is_null($conv_type)) {
            throw new \InvalidArgumentException('non-nullable conv_type cannot be null');
        }
        $this->container['conv_type'] = $conv_type;

        return $this;
    }

    /**
     * Gets optimise_with
     *
     * @return string|null
     */
    public function getOptimiseWith()
    {
        return $this->container['optimise_with'];
    }

    /**
     * Sets optimise_with
     *
     * @param string|null $optimise_with optimise_with
     *
     * @return self
     */
    public function setOptimiseWith($optimise_with)
    {
        if (is_null($optimise_with)) {
            throw new \InvalidArgumentException('non-nullable optimise_with cannot be null');
        }
        $allowedValues = $this->getOptimiseWithAllowableValues();
        if (!in_array($optimise_with, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'optimise_with', must be one of '%s'",
                    $optimise_with,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['optimise_with'] = $optimise_with;

        return $this;
    }

    /**
     * Gets max_cpa
     *
     * @return float|null
     */
    public function getMaxCpa()
    {
        return $this->container['max_cpa'];
    }

    /**
     * Sets max_cpa
     *
     * @param float|null $max_cpa optimise_with=conv_count: max cost per conversion (account currency).
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
     * Gets allowed_margin
     *
     * @return float|null
     */
    public function getAllowedMargin()
    {
        return $this->container['allowed_margin'];
    }

    /**
     * Sets allowed_margin
     *
     * @param float|null $allowed_margin optimise_with=conv_earnings: allowed margin %.
     *
     * @return self
     */
    public function setAllowedMargin($allowed_margin)
    {
        if (is_null($allowed_margin)) {
            throw new \InvalidArgumentException('non-nullable allowed_margin cannot be null');
        }
        $this->container['allowed_margin'] = $allowed_margin;

        return $this;
    }

    /**
     * Gets use_bidoptimiser
     *
     * @return int|null
     */
    public function getUseBidoptimiser()
    {
        return $this->container['use_bidoptimiser'];
    }

    /**
     * Sets use_bidoptimiser
     *
     * @param int|null $use_bidoptimiser use_bidoptimiser
     *
     * @return self
     */
    public function setUseBidoptimiser($use_bidoptimiser)
    {
        if (is_null($use_bidoptimiser)) {
            throw new \InvalidArgumentException('non-nullable use_bidoptimiser cannot be null');
        }
        $allowedValues = $this->getUseBidoptimiserAllowableValues();
        if (!in_array($use_bidoptimiser, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_bidoptimiser', must be one of '%s'",
                    $use_bidoptimiser,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_bidoptimiser'] = $use_bidoptimiser;

        return $this;
    }

    /**
     * Gets increase_bids
     *
     * @return int|null
     */
    public function getIncreaseBids()
    {
        return $this->container['increase_bids'];
    }

    /**
     * Sets increase_bids
     *
     * @param int|null $increase_bids increase_bids
     *
     * @return self
     */
    public function setIncreaseBids($increase_bids)
    {
        if (is_null($increase_bids)) {
            throw new \InvalidArgumentException('non-nullable increase_bids cannot be null');
        }
        $allowedValues = $this->getIncreaseBidsAllowableValues();
        if (!in_array($increase_bids, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'increase_bids', must be one of '%s'",
                    $increase_bids,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['increase_bids'] = $increase_bids;

        return $this;
    }

    /**
     * Gets increase_bid_perc
     *
     * @return int|null
     */
    public function getIncreaseBidPerc()
    {
        return $this->container['increase_bid_perc'];
    }

    /**
     * Sets increase_bid_perc
     *
     * @param int|null $increase_bid_perc Percent per step.
     *
     * @return self
     */
    public function setIncreaseBidPerc($increase_bid_perc)
    {
        if (is_null($increase_bid_perc)) {
            throw new \InvalidArgumentException('non-nullable increase_bid_perc cannot be null');
        }

        if (($increase_bid_perc > 99)) {
            throw new \InvalidArgumentException('invalid value for $increase_bid_perc when calling CpmOptimizerBody., must be smaller than or equal to 99.');
        }
        if (($increase_bid_perc < 1)) {
            throw new \InvalidArgumentException('invalid value for $increase_bid_perc when calling CpmOptimizerBody., must be bigger than or equal to 1.');
        }

        $this->container['increase_bid_perc'] = $increase_bid_perc;

        return $this;
    }

    /**
     * Gets increase_bid_cap
     *
     * @return int|null
     */
    public function getIncreaseBidCap()
    {
        return $this->container['increase_bid_cap'];
    }

    /**
     * Sets increase_bid_cap
     *
     * @param int|null $increase_bid_cap Hours between steps.
     *
     * @return self
     */
    public function setIncreaseBidCap($increase_bid_cap)
    {
        if (is_null($increase_bid_cap)) {
            throw new \InvalidArgumentException('non-nullable increase_bid_cap cannot be null');
        }

        if (($increase_bid_cap > 99)) {
            throw new \InvalidArgumentException('invalid value for $increase_bid_cap when calling CpmOptimizerBody., must be smaller than or equal to 99.');
        }
        if (($increase_bid_cap < 1)) {
            throw new \InvalidArgumentException('invalid value for $increase_bid_cap when calling CpmOptimizerBody., must be bigger than or equal to 1.');
        }

        $this->container['increase_bid_cap'] = $increase_bid_cap;

        return $this;
    }

    /**
     * Gets decrease_bids
     *
     * @return int|null
     */
    public function getDecreaseBids()
    {
        return $this->container['decrease_bids'];
    }

    /**
     * Sets decrease_bids
     *
     * @param int|null $decrease_bids decrease_bids
     *
     * @return self
     */
    public function setDecreaseBids($decrease_bids)
    {
        if (is_null($decrease_bids)) {
            throw new \InvalidArgumentException('non-nullable decrease_bids cannot be null');
        }
        $allowedValues = $this->getDecreaseBidsAllowableValues();
        if (!in_array($decrease_bids, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'decrease_bids', must be one of '%s'",
                    $decrease_bids,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['decrease_bids'] = $decrease_bids;

        return $this;
    }

    /**
     * Gets decrease_bid_perc
     *
     * @return int|null
     */
    public function getDecreaseBidPerc()
    {
        return $this->container['decrease_bid_perc'];
    }

    /**
     * Sets decrease_bid_perc
     *
     * @param int|null $decrease_bid_perc decrease_bid_perc
     *
     * @return self
     */
    public function setDecreaseBidPerc($decrease_bid_perc)
    {
        if (is_null($decrease_bid_perc)) {
            throw new \InvalidArgumentException('non-nullable decrease_bid_perc cannot be null');
        }

        if (($decrease_bid_perc > 99)) {
            throw new \InvalidArgumentException('invalid value for $decrease_bid_perc when calling CpmOptimizerBody., must be smaller than or equal to 99.');
        }
        if (($decrease_bid_perc < 1)) {
            throw new \InvalidArgumentException('invalid value for $decrease_bid_perc when calling CpmOptimizerBody., must be bigger than or equal to 1.');
        }

        $this->container['decrease_bid_perc'] = $decrease_bid_perc;

        return $this;
    }

    /**
     * Gets decrease_bid_cap
     *
     * @return int|null
     */
    public function getDecreaseBidCap()
    {
        return $this->container['decrease_bid_cap'];
    }

    /**
     * Sets decrease_bid_cap
     *
     * @param int|null $decrease_bid_cap decrease_bid_cap
     *
     * @return self
     */
    public function setDecreaseBidCap($decrease_bid_cap)
    {
        if (is_null($decrease_bid_cap)) {
            throw new \InvalidArgumentException('non-nullable decrease_bid_cap cannot be null');
        }

        if (($decrease_bid_cap > 99)) {
            throw new \InvalidArgumentException('invalid value for $decrease_bid_cap when calling CpmOptimizerBody., must be smaller than or equal to 99.');
        }
        if (($decrease_bid_cap < 1)) {
            throw new \InvalidArgumentException('invalid value for $decrease_bid_cap when calling CpmOptimizerBody., must be bigger than or equal to 1.');
        }

        $this->container['decrease_bid_cap'] = $decrease_bid_cap;

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
     * Gets userid
     *
     * @return int|null
     */
    public function getUserid()
    {
        return $this->container['userid'];
    }

    /**
     * Sets userid
     *
     * @param int|null $userid userid
     *
     * @return self
     */
    public function setUserid($userid)
    {
        if (is_null($userid)) {
            throw new \InvalidArgumentException('non-nullable userid cannot be null');
        }
        $this->container['userid'] = $userid;

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



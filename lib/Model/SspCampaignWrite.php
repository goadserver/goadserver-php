<?php
/**
 * SspCampaignWrite
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
 * SspCampaignWrite Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SspCampaignWrite implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'SspCampaignWrite';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'adtype' => 'int',
        'ssptype' => 'string',
        'rtbtype' => 'string',
        'header_bidding' => 'bool',
        'rtb_network' => 'int',
        'ssp_pct' => 'int',
        'siteid' => 'int',
        'slots' => '\GoAdServerApi\Model\SspCampaignWriteSlotsInner[]',
        'request_url' => 'string',
        'use_type' => 'int',
        'zoneurls' => '\GoAdServerApi\Model\SspCampaignWriteZoneurlsInner[]',
        'p_type' => 'int',
        'adm_response_type' => 'string',
        'rtb_tpl_id' => 'int',
        'bid_action' => 'int',
        'device_type_ids' => 'int[]',
        'osids' => 'int[]',
        'browserids' => 'int[]',
        'regionids' => 'int[]',
        'browserlanguages' => 'string[]',
        'connection_types' => 'int[]',
        'ssp_ids' => 'int[]',
        'wl_ron_campaign_ids' => 'int[]',
        'rtb_categories' => 'string[]',
        'dsp_ids' => 'int[]',
        'displayoptions' => 'string',
        'log_skipped' => 'int',
        'use_adscan' => 'int',
        'bill_on_burl' => 'int',
        'use_optimiser' => 'int',
        'optimiser_data' => 'object',
        'use_api' => 'int',
        'api_data' => 'object'
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
        'adtype' => null,
        'ssptype' => null,
        'rtbtype' => null,
        'header_bidding' => null,
        'rtb_network' => null,
        'ssp_pct' => null,
        'siteid' => null,
        'slots' => null,
        'request_url' => null,
        'use_type' => null,
        'zoneurls' => null,
        'p_type' => null,
        'adm_response_type' => null,
        'rtb_tpl_id' => null,
        'bid_action' => null,
        'device_type_ids' => null,
        'osids' => null,
        'browserids' => null,
        'regionids' => null,
        'browserlanguages' => null,
        'connection_types' => null,
        'ssp_ids' => null,
        'wl_ron_campaign_ids' => null,
        'rtb_categories' => null,
        'dsp_ids' => null,
        'displayoptions' => null,
        'log_skipped' => null,
        'use_adscan' => null,
        'bill_on_burl' => null,
        'use_optimiser' => null,
        'optimiser_data' => null,
        'use_api' => null,
        'api_data' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'adtype' => false,
        'ssptype' => false,
        'rtbtype' => false,
        'header_bidding' => false,
        'rtb_network' => false,
        'ssp_pct' => false,
        'siteid' => false,
        'slots' => false,
        'request_url' => false,
        'use_type' => false,
        'zoneurls' => false,
        'p_type' => false,
        'adm_response_type' => false,
        'rtb_tpl_id' => false,
        'bid_action' => false,
        'device_type_ids' => false,
        'osids' => false,
        'browserids' => false,
        'regionids' => false,
        'browserlanguages' => false,
        'connection_types' => false,
        'ssp_ids' => false,
        'wl_ron_campaign_ids' => false,
        'rtb_categories' => false,
        'dsp_ids' => false,
        'displayoptions' => false,
        'log_skipped' => false,
        'use_adscan' => false,
        'bill_on_burl' => false,
        'use_optimiser' => false,
        'optimiser_data' => false,
        'use_api' => false,
        'api_data' => false
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
        'adtype' => 'adtype',
        'ssptype' => 'ssptype',
        'rtbtype' => 'rtbtype',
        'header_bidding' => 'header_bidding',
        'rtb_network' => 'rtb_network',
        'ssp_pct' => 'ssp_pct',
        'siteid' => 'siteid',
        'slots' => 'slots',
        'request_url' => 'request_url',
        'use_type' => 'use_type',
        'zoneurls' => 'zoneurls',
        'p_type' => 'p_type',
        'adm_response_type' => 'adm_response_type',
        'rtb_tpl_id' => 'rtb_tpl_id',
        'bid_action' => 'bid_action',
        'device_type_ids' => 'device_type_ids',
        'osids' => 'osids',
        'browserids' => 'browserids',
        'regionids' => 'regionids',
        'browserlanguages' => 'browserlanguages',
        'connection_types' => 'connection_types',
        'ssp_ids' => 'ssp_ids',
        'wl_ron_campaign_ids' => 'wl_ron_campaign_ids',
        'rtb_categories' => 'rtb_categories',
        'dsp_ids' => 'dsp_ids',
        'displayoptions' => 'displayoptions',
        'log_skipped' => 'log_skipped',
        'use_adscan' => 'use_adscan',
        'bill_on_burl' => 'bill_on_burl',
        'use_optimiser' => 'use_optimiser',
        'optimiser_data' => 'optimiser_data',
        'use_api' => 'use_api',
        'api_data' => 'api_data'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'adtype' => 'setAdtype',
        'ssptype' => 'setSsptype',
        'rtbtype' => 'setRtbtype',
        'header_bidding' => 'setHeaderBidding',
        'rtb_network' => 'setRtbNetwork',
        'ssp_pct' => 'setSspPct',
        'siteid' => 'setSiteid',
        'slots' => 'setSlots',
        'request_url' => 'setRequestUrl',
        'use_type' => 'setUseType',
        'zoneurls' => 'setZoneurls',
        'p_type' => 'setPType',
        'adm_response_type' => 'setAdmResponseType',
        'rtb_tpl_id' => 'setRtbTplId',
        'bid_action' => 'setBidAction',
        'device_type_ids' => 'setDeviceTypeIds',
        'osids' => 'setOsids',
        'browserids' => 'setBrowserids',
        'regionids' => 'setRegionids',
        'browserlanguages' => 'setBrowserlanguages',
        'connection_types' => 'setConnectionTypes',
        'ssp_ids' => 'setSspIds',
        'wl_ron_campaign_ids' => 'setWlRonCampaignIds',
        'rtb_categories' => 'setRtbCategories',
        'dsp_ids' => 'setDspIds',
        'displayoptions' => 'setDisplayoptions',
        'log_skipped' => 'setLogSkipped',
        'use_adscan' => 'setUseAdscan',
        'bill_on_burl' => 'setBillOnBurl',
        'use_optimiser' => 'setUseOptimiser',
        'optimiser_data' => 'setOptimiserData',
        'use_api' => 'setUseApi',
        'api_data' => 'setApiData'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'adtype' => 'getAdtype',
        'ssptype' => 'getSsptype',
        'rtbtype' => 'getRtbtype',
        'header_bidding' => 'getHeaderBidding',
        'rtb_network' => 'getRtbNetwork',
        'ssp_pct' => 'getSspPct',
        'siteid' => 'getSiteid',
        'slots' => 'getSlots',
        'request_url' => 'getRequestUrl',
        'use_type' => 'getUseType',
        'zoneurls' => 'getZoneurls',
        'p_type' => 'getPType',
        'adm_response_type' => 'getAdmResponseType',
        'rtb_tpl_id' => 'getRtbTplId',
        'bid_action' => 'getBidAction',
        'device_type_ids' => 'getDeviceTypeIds',
        'osids' => 'getOsids',
        'browserids' => 'getBrowserids',
        'regionids' => 'getRegionids',
        'browserlanguages' => 'getBrowserlanguages',
        'connection_types' => 'getConnectionTypes',
        'ssp_ids' => 'getSspIds',
        'wl_ron_campaign_ids' => 'getWlRonCampaignIds',
        'rtb_categories' => 'getRtbCategories',
        'dsp_ids' => 'getDspIds',
        'displayoptions' => 'getDisplayoptions',
        'log_skipped' => 'getLogSkipped',
        'use_adscan' => 'getUseAdscan',
        'bill_on_burl' => 'getBillOnBurl',
        'use_optimiser' => 'getUseOptimiser',
        'optimiser_data' => 'getOptimiserData',
        'use_api' => 'getUseApi',
        'api_data' => 'getApiData'
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

    public const SSPTYPE_SSP = 'ssp';
    public const SSPTYPE_EXTERNAL = 'external';
    public const SSPTYPE_EXCHANGE = 'exchange';
    public const USE_TYPE_NUMBER_0 = 0;
    public const USE_TYPE_NUMBER_1 = 1;
    public const USE_TYPE_NUMBER_2 = 2;
    public const LOG_SKIPPED_NUMBER_0 = 0;
    public const LOG_SKIPPED_NUMBER_1 = 1;
    public const USE_ADSCAN_NUMBER_0 = 0;
    public const USE_ADSCAN_NUMBER_1 = 1;
    public const BILL_ON_BURL_NUMBER_0 = 0;
    public const BILL_ON_BURL_NUMBER_1 = 1;
    public const USE_OPTIMISER_NUMBER_0 = 0;
    public const USE_OPTIMISER_NUMBER_1 = 1;
    public const USE_API_NUMBER_0 = 0;
    public const USE_API_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSsptypeAllowableValues()
    {
        return [
            self::SSPTYPE_SSP,
            self::SSPTYPE_EXTERNAL,
            self::SSPTYPE_EXCHANGE,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseTypeAllowableValues()
    {
        return [
            self::USE_TYPE_NUMBER_0,
            self::USE_TYPE_NUMBER_1,
            self::USE_TYPE_NUMBER_2,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getLogSkippedAllowableValues()
    {
        return [
            self::LOG_SKIPPED_NUMBER_0,
            self::LOG_SKIPPED_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseAdscanAllowableValues()
    {
        return [
            self::USE_ADSCAN_NUMBER_0,
            self::USE_ADSCAN_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getBillOnBurlAllowableValues()
    {
        return [
            self::BILL_ON_BURL_NUMBER_0,
            self::BILL_ON_BURL_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseOptimiserAllowableValues()
    {
        return [
            self::USE_OPTIMISER_NUMBER_0,
            self::USE_OPTIMISER_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUseApiAllowableValues()
    {
        return [
            self::USE_API_NUMBER_0,
            self::USE_API_NUMBER_1,
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
        $this->setIfExists('adtype', $data ?? [], null);
        $this->setIfExists('ssptype', $data ?? [], 'ssp');
        $this->setIfExists('rtbtype', $data ?? [], 'openrtb');
        $this->setIfExists('header_bidding', $data ?? [], null);
        $this->setIfExists('rtb_network', $data ?? [], null);
        $this->setIfExists('ssp_pct', $data ?? [], 80);
        $this->setIfExists('siteid', $data ?? [], null);
        $this->setIfExists('slots', $data ?? [], null);
        $this->setIfExists('request_url', $data ?? [], null);
        $this->setIfExists('use_type', $data ?? [], null);
        $this->setIfExists('zoneurls', $data ?? [], null);
        $this->setIfExists('p_type', $data ?? [], null);
        $this->setIfExists('adm_response_type', $data ?? [], 'html');
        $this->setIfExists('rtb_tpl_id', $data ?? [], 1);
        $this->setIfExists('bid_action', $data ?? [], 1);
        $this->setIfExists('device_type_ids', $data ?? [], null);
        $this->setIfExists('osids', $data ?? [], null);
        $this->setIfExists('browserids', $data ?? [], null);
        $this->setIfExists('regionids', $data ?? [], null);
        $this->setIfExists('browserlanguages', $data ?? [], null);
        $this->setIfExists('connection_types', $data ?? [], null);
        $this->setIfExists('ssp_ids', $data ?? [], null);
        $this->setIfExists('wl_ron_campaign_ids', $data ?? [], null);
        $this->setIfExists('rtb_categories', $data ?? [], null);
        $this->setIfExists('dsp_ids', $data ?? [], null);
        $this->setIfExists('displayoptions', $data ?? [], '0');
        $this->setIfExists('log_skipped', $data ?? [], null);
        $this->setIfExists('use_adscan', $data ?? [], null);
        $this->setIfExists('bill_on_burl', $data ?? [], null);
        $this->setIfExists('use_optimiser', $data ?? [], null);
        $this->setIfExists('optimiser_data', $data ?? [], null);
        $this->setIfExists('use_api', $data ?? [], null);
        $this->setIfExists('api_data', $data ?? [], null);
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
        $allowedValues = $this->getSsptypeAllowableValues();
        if (!is_null($this->container['ssptype']) && !in_array($this->container['ssptype'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'ssptype', must be one of '%s'",
                $this->container['ssptype'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['ssp_pct']) && ($this->container['ssp_pct'] > 100)) {
            $invalidProperties[] = "invalid value for 'ssp_pct', must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['ssp_pct']) && ($this->container['ssp_pct'] < 1)) {
            $invalidProperties[] = "invalid value for 'ssp_pct', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getUseTypeAllowableValues();
        if (!is_null($this->container['use_type']) && !in_array($this->container['use_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_type', must be one of '%s'",
                $this->container['use_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getLogSkippedAllowableValues();
        if (!is_null($this->container['log_skipped']) && !in_array($this->container['log_skipped'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'log_skipped', must be one of '%s'",
                $this->container['log_skipped'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getUseAdscanAllowableValues();
        if (!is_null($this->container['use_adscan']) && !in_array($this->container['use_adscan'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_adscan', must be one of '%s'",
                $this->container['use_adscan'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getBillOnBurlAllowableValues();
        if (!is_null($this->container['bill_on_burl']) && !in_array($this->container['bill_on_burl'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'bill_on_burl', must be one of '%s'",
                $this->container['bill_on_burl'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getUseOptimiserAllowableValues();
        if (!is_null($this->container['use_optimiser']) && !in_array($this->container['use_optimiser'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_optimiser', must be one of '%s'",
                $this->container['use_optimiser'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getUseApiAllowableValues();
        if (!is_null($this->container['use_api']) && !in_array($this->container['use_api'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_api', must be one of '%s'",
                $this->container['use_api'],
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
     * Gets adtype
     *
     * @return int|null
     */
    public function getAdtype()
    {
        return $this->container['adtype'];
    }

    /**
     * Sets adtype
     *
     * @param int|null $adtype Ad type id (setup-data `ad_types[].id`).
     *
     * @return self
     */
    public function setAdtype($adtype)
    {
        if (is_null($adtype)) {
            throw new \InvalidArgumentException('non-nullable adtype cannot be null');
        }
        $this->container['adtype'] = $adtype;

        return $this;
    }

    /**
     * Gets ssptype
     *
     * @return string|null
     */
    public function getSsptype()
    {
        return $this->container['ssptype'];
    }

    /**
     * Sets ssptype
     *
     * @param string|null $ssptype ssptype
     *
     * @return self
     */
    public function setSsptype($ssptype)
    {
        if (is_null($ssptype)) {
            throw new \InvalidArgumentException('non-nullable ssptype cannot be null');
        }
        $allowedValues = $this->getSsptypeAllowableValues();
        if (!in_array($ssptype, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'ssptype', must be one of '%s'",
                    $ssptype,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['ssptype'] = $ssptype;

        return $this;
    }

    /**
     * Gets rtbtype
     *
     * @return string|null
     */
    public function getRtbtype()
    {
        return $this->container['rtbtype'];
    }

    /**
     * Sets rtbtype
     *
     * @param string|null $rtbtype openrtb or a legacy XML/JSON template type, per setup-data.
     *
     * @return self
     */
    public function setRtbtype($rtbtype)
    {
        if (is_null($rtbtype)) {
            throw new \InvalidArgumentException('non-nullable rtbtype cannot be null');
        }
        $this->container['rtbtype'] = $rtbtype;

        return $this;
    }

    /**
     * Gets header_bidding
     *
     * @return bool|null
     */
    public function getHeaderBidding()
    {
        return $this->container['header_bidding'];
    }

    /**
     * Sets header_bidding
     *
     * @param bool|null $header_bidding external/exchange only; locked after create.
     *
     * @return self
     */
    public function setHeaderBidding($header_bidding)
    {
        if (is_null($header_bidding)) {
            throw new \InvalidArgumentException('non-nullable header_bidding cannot be null');
        }
        $this->container['header_bidding'] = $header_bidding;

        return $this;
    }

    /**
     * Gets rtb_network
     *
     * @return int|null
     */
    public function getRtbNetwork()
    {
        return $this->container['rtb_network'];
    }

    /**
     * Sets rtb_network
     *
     * @param int|null $rtb_network RTB network id (setup-data `rtb_networks`). Required for ssptype=ssp.
     *
     * @return self
     */
    public function setRtbNetwork($rtb_network)
    {
        if (is_null($rtb_network)) {
            throw new \InvalidArgumentException('non-nullable rtb_network cannot be null');
        }
        $this->container['rtb_network'] = $rtb_network;

        return $this;
    }

    /**
     * Gets ssp_pct
     *
     * @return int|null
     */
    public function getSspPct()
    {
        return $this->container['ssp_pct'];
    }

    /**
     * Sets ssp_pct
     *
     * @param int|null $ssp_pct Revenue share in percent (required 1-100 for exchange).
     *
     * @return self
     */
    public function setSspPct($ssp_pct)
    {
        if (is_null($ssp_pct)) {
            throw new \InvalidArgumentException('non-nullable ssp_pct cannot be null');
        }

        if (($ssp_pct > 100)) {
            throw new \InvalidArgumentException('invalid value for $ssp_pct when calling SspCampaignWrite., must be smaller than or equal to 100.');
        }
        if (($ssp_pct < 1)) {
            throw new \InvalidArgumentException('invalid value for $ssp_pct when calling SspCampaignWrite., must be bigger than or equal to 1.');
        }

        $this->container['ssp_pct'] = $ssp_pct;

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
     * @param int|null $siteid One of your sites. Required for external and header bidding.
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
     * Gets slots
     *
     * @return \GoAdServerApi\Model\SspCampaignWriteSlotsInner[]|null
     */
    public function getSlots()
    {
        return $this->container['slots'];
    }

    /**
     * Sets slots
     *
     * @param \GoAdServerApi\Model\SspCampaignWriteSlotsInner[]|null $slots Header-bidding slots.
     *
     * @return self
     */
    public function setSlots($slots)
    {
        if (is_null($slots)) {
            throw new \InvalidArgumentException('non-nullable slots cannot be null');
        }
        $this->container['slots'] = $slots;

        return $this;
    }

    /**
     * Gets request_url
     *
     * @return string|null
     */
    public function getRequestUrl()
    {
        return $this->container['request_url'];
    }

    /**
     * Sets request_url
     *
     * @param string|null $request_url Partner endpoint (ssptype=ssp, use_type 0).
     *
     * @return self
     */
    public function setRequestUrl($request_url)
    {
        if (is_null($request_url)) {
            throw new \InvalidArgumentException('non-nullable request_url cannot be null');
        }
        $this->container['request_url'] = $request_url;

        return $this;
    }

    /**
     * Gets use_type
     *
     * @return int|null
     */
    public function getUseType()
    {
        return $this->container['use_type'];
    }

    /**
     * Sets use_type
     *
     * @param int|null $use_type ssp only: 0 = one URL, 1 = per-ad-zone URLs, 2 = smart-zone RTB feed.
     *
     * @return self
     */
    public function setUseType($use_type)
    {
        if (is_null($use_type)) {
            throw new \InvalidArgumentException('non-nullable use_type cannot be null');
        }
        $allowedValues = $this->getUseTypeAllowableValues();
        if (!in_array($use_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_type', must be one of '%s'",
                    $use_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_type'] = $use_type;

        return $this;
    }

    /**
     * Gets zoneurls
     *
     * @return \GoAdServerApi\Model\SspCampaignWriteZoneurlsInner[]|null
     */
    public function getZoneurls()
    {
        return $this->container['zoneurls'];
    }

    /**
     * Sets zoneurls
     *
     * @param \GoAdServerApi\Model\SspCampaignWriteZoneurlsInner[]|null $zoneurls use_type 1: per-zone URLs (your own ad zones only; others are dropped).
     *
     * @return self
     */
    public function setZoneurls($zoneurls)
    {
        if (is_null($zoneurls)) {
            throw new \InvalidArgumentException('non-nullable zoneurls cannot be null');
        }
        $this->container['zoneurls'] = $zoneurls;

        return $this;
    }

    /**
     * Gets p_type
     *
     * @return int|null
     */
    public function getPType()
    {
        return $this->container['p_type'];
    }

    /**
     * Sets p_type
     *
     * @param int|null $p_type p_type
     *
     * @return self
     */
    public function setPType($p_type)
    {
        if (is_null($p_type)) {
            throw new \InvalidArgumentException('non-nullable p_type cannot be null');
        }
        $this->container['p_type'] = $p_type;

        return $this;
    }

    /**
     * Gets adm_response_type
     *
     * @return string|null
     */
    public function getAdmResponseType()
    {
        return $this->container['adm_response_type'];
    }

    /**
     * Sets adm_response_type
     *
     * @param string|null $adm_response_type adm_response_type
     *
     * @return self
     */
    public function setAdmResponseType($adm_response_type)
    {
        if (is_null($adm_response_type)) {
            throw new \InvalidArgumentException('non-nullable adm_response_type cannot be null');
        }
        $this->container['adm_response_type'] = $adm_response_type;

        return $this;
    }

    /**
     * Gets rtb_tpl_id
     *
     * @return int|null
     */
    public function getRtbTplId()
    {
        return $this->container['rtb_tpl_id'];
    }

    /**
     * Sets rtb_tpl_id
     *
     * @param int|null $rtb_tpl_id rtb_tpl_id
     *
     * @return self
     */
    public function setRtbTplId($rtb_tpl_id)
    {
        if (is_null($rtb_tpl_id)) {
            throw new \InvalidArgumentException('non-nullable rtb_tpl_id cannot be null');
        }
        $this->container['rtb_tpl_id'] = $rtb_tpl_id;

        return $this;
    }

    /**
     * Gets bid_action
     *
     * @return int|null
     */
    public function getBidAction()
    {
        return $this->container['bid_action'];
    }

    /**
     * Sets bid_action
     *
     * @param int|null $bid_action bid_action
     *
     * @return self
     */
    public function setBidAction($bid_action)
    {
        if (is_null($bid_action)) {
            throw new \InvalidArgumentException('non-nullable bid_action cannot be null');
        }
        $this->container['bid_action'] = $bid_action;

        return $this;
    }

    /**
     * Gets device_type_ids
     *
     * @return int[]|null
     */
    public function getDeviceTypeIds()
    {
        return $this->container['device_type_ids'];
    }

    /**
     * Sets device_type_ids
     *
     * @param int[]|null $device_type_ids Empty = all. A comma-separated string is accepted too (same for every list below).
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
     * Gets osids
     *
     * @return int[]|null
     */
    public function getOsids()
    {
        return $this->container['osids'];
    }

    /**
     * Sets osids
     *
     * @param int[]|null $osids osids
     *
     * @return self
     */
    public function setOsids($osids)
    {
        if (is_null($osids)) {
            throw new \InvalidArgumentException('non-nullable osids cannot be null');
        }
        $this->container['osids'] = $osids;

        return $this;
    }

    /**
     * Gets browserids
     *
     * @return int[]|null
     */
    public function getBrowserids()
    {
        return $this->container['browserids'];
    }

    /**
     * Sets browserids
     *
     * @param int[]|null $browserids browserids
     *
     * @return self
     */
    public function setBrowserids($browserids)
    {
        if (is_null($browserids)) {
            throw new \InvalidArgumentException('non-nullable browserids cannot be null');
        }
        $this->container['browserids'] = $browserids;

        return $this;
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
     * @param int[]|null $regionids regionids
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
     * Gets browserlanguages
     *
     * @return string[]|null
     */
    public function getBrowserlanguages()
    {
        return $this->container['browserlanguages'];
    }

    /**
     * Sets browserlanguages
     *
     * @param string[]|null $browserlanguages browserlanguages
     *
     * @return self
     */
    public function setBrowserlanguages($browserlanguages)
    {
        if (is_null($browserlanguages)) {
            throw new \InvalidArgumentException('non-nullable browserlanguages cannot be null');
        }
        $this->container['browserlanguages'] = $browserlanguages;

        return $this;
    }

    /**
     * Gets connection_types
     *
     * @return int[]|null
     */
    public function getConnectionTypes()
    {
        return $this->container['connection_types'];
    }

    /**
     * Sets connection_types
     *
     * @param int[]|null $connection_types connection_types
     *
     * @return self
     */
    public function setConnectionTypes($connection_types)
    {
        if (is_null($connection_types)) {
            throw new \InvalidArgumentException('non-nullable connection_types cannot be null');
        }
        $this->container['connection_types'] = $connection_types;

        return $this;
    }

    /**
     * Gets ssp_ids
     *
     * @return int[]|null
     */
    public function getSspIds()
    {
        return $this->container['ssp_ids'];
    }

    /**
     * Sets ssp_ids
     *
     * @param int[]|null $ssp_ids ssp_ids
     *
     * @return self
     */
    public function setSspIds($ssp_ids)
    {
        if (is_null($ssp_ids)) {
            throw new \InvalidArgumentException('non-nullable ssp_ids cannot be null');
        }
        $this->container['ssp_ids'] = $ssp_ids;

        return $this;
    }

    /**
     * Gets wl_ron_campaign_ids
     *
     * @return int[]|null
     */
    public function getWlRonCampaignIds()
    {
        return $this->container['wl_ron_campaign_ids'];
    }

    /**
     * Sets wl_ron_campaign_ids
     *
     * @param int[]|null $wl_ron_campaign_ids From GET .../wl-ron-campaigns.
     *
     * @return self
     */
    public function setWlRonCampaignIds($wl_ron_campaign_ids)
    {
        if (is_null($wl_ron_campaign_ids)) {
            throw new \InvalidArgumentException('non-nullable wl_ron_campaign_ids cannot be null');
        }
        $this->container['wl_ron_campaign_ids'] = $wl_ron_campaign_ids;

        return $this;
    }

    /**
     * Gets rtb_categories
     *
     * @return string[]|null
     */
    public function getRtbCategories()
    {
        return $this->container['rtb_categories'];
    }

    /**
     * Sets rtb_categories
     *
     * @param string[]|null $rtb_categories rtb_categories
     *
     * @return self
     */
    public function setRtbCategories($rtb_categories)
    {
        if (is_null($rtb_categories)) {
            throw new \InvalidArgumentException('non-nullable rtb_categories cannot be null');
        }
        $this->container['rtb_categories'] = $rtb_categories;

        return $this;
    }

    /**
     * Gets dsp_ids
     *
     * @return int[]|null
     */
    public function getDspIds()
    {
        return $this->container['dsp_ids'];
    }

    /**
     * Sets dsp_ids
     *
     * @param int[]|null $dsp_ids Admin only (ignored for API keys).
     *
     * @return self
     */
    public function setDspIds($dsp_ids)
    {
        if (is_null($dsp_ids)) {
            throw new \InvalidArgumentException('non-nullable dsp_ids cannot be null');
        }
        $this->container['dsp_ids'] = $dsp_ids;

        return $this;
    }

    /**
     * Gets displayoptions
     *
     * @return string|null
     */
    public function getDisplayoptions()
    {
        return $this->container['displayoptions'];
    }

    /**
     * Sets displayoptions
     *
     * @param string|null $displayoptions displayoptions
     *
     * @return self
     */
    public function setDisplayoptions($displayoptions)
    {
        if (is_null($displayoptions)) {
            throw new \InvalidArgumentException('non-nullable displayoptions cannot be null');
        }
        $this->container['displayoptions'] = $displayoptions;

        return $this;
    }

    /**
     * Gets log_skipped
     *
     * @return int|null
     */
    public function getLogSkipped()
    {
        return $this->container['log_skipped'];
    }

    /**
     * Sets log_skipped
     *
     * @param int|null $log_skipped log_skipped
     *
     * @return self
     */
    public function setLogSkipped($log_skipped)
    {
        if (is_null($log_skipped)) {
            throw new \InvalidArgumentException('non-nullable log_skipped cannot be null');
        }
        $allowedValues = $this->getLogSkippedAllowableValues();
        if (!in_array($log_skipped, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'log_skipped', must be one of '%s'",
                    $log_skipped,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['log_skipped'] = $log_skipped;

        return $this;
    }

    /**
     * Gets use_adscan
     *
     * @return int|null
     */
    public function getUseAdscan()
    {
        return $this->container['use_adscan'];
    }

    /**
     * Sets use_adscan
     *
     * @param int|null $use_adscan use_adscan
     *
     * @return self
     */
    public function setUseAdscan($use_adscan)
    {
        if (is_null($use_adscan)) {
            throw new \InvalidArgumentException('non-nullable use_adscan cannot be null');
        }
        $allowedValues = $this->getUseAdscanAllowableValues();
        if (!in_array($use_adscan, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_adscan', must be one of '%s'",
                    $use_adscan,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_adscan'] = $use_adscan;

        return $this;
    }

    /**
     * Gets bill_on_burl
     *
     * @return int|null
     */
    public function getBillOnBurl()
    {
        return $this->container['bill_on_burl'];
    }

    /**
     * Sets bill_on_burl
     *
     * @param int|null $bill_on_burl Bill on the OpenRTB burl (external/exchange, openrtb).
     *
     * @return self
     */
    public function setBillOnBurl($bill_on_burl)
    {
        if (is_null($bill_on_burl)) {
            throw new \InvalidArgumentException('non-nullable bill_on_burl cannot be null');
        }
        $allowedValues = $this->getBillOnBurlAllowableValues();
        if (!in_array($bill_on_burl, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'bill_on_burl', must be one of '%s'",
                    $bill_on_burl,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['bill_on_burl'] = $bill_on_burl;

        return $this;
    }

    /**
     * Gets use_optimiser
     *
     * @return int|null
     */
    public function getUseOptimiser()
    {
        return $this->container['use_optimiser'];
    }

    /**
     * Sets use_optimiser
     *
     * @param int|null $use_optimiser use_optimiser
     *
     * @return self
     */
    public function setUseOptimiser($use_optimiser)
    {
        if (is_null($use_optimiser)) {
            throw new \InvalidArgumentException('non-nullable use_optimiser cannot be null');
        }
        $allowedValues = $this->getUseOptimiserAllowableValues();
        if (!in_array($use_optimiser, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_optimiser', must be one of '%s'",
                    $use_optimiser,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_optimiser'] = $use_optimiser;

        return $this;
    }

    /**
     * Gets optimiser_data
     *
     * @return object|null
     */
    public function getOptimiserData()
    {
        return $this->container['optimiser_data'];
    }

    /**
     * Sets optimiser_data
     *
     * @param object|null $optimiser_data {disc_rules: [...]}, external/exchange only.
     *
     * @return self
     */
    public function setOptimiserData($optimiser_data)
    {
        if (is_null($optimiser_data)) {
            throw new \InvalidArgumentException('non-nullable optimiser_data cannot be null');
        }
        $this->container['optimiser_data'] = $optimiser_data;

        return $this;
    }

    /**
     * Gets use_api
     *
     * @return int|null
     */
    public function getUseApi()
    {
        return $this->container['use_api'];
    }

    /**
     * Sets use_api
     *
     * @param int|null $use_api use_api
     *
     * @return self
     */
    public function setUseApi($use_api)
    {
        if (is_null($use_api)) {
            throw new \InvalidArgumentException('non-nullable use_api cannot be null');
        }
        $allowedValues = $this->getUseApiAllowableValues();
        if (!in_array($use_api, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'use_api', must be one of '%s'",
                    $use_api,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['use_api'] = $use_api;

        return $this;
    }

    /**
     * Gets api_data
     *
     * @return object|null
     */
    public function getApiData()
    {
        return $this->container['api_data'];
    }

    /**
     * Sets api_data
     *
     * @param object|null $api_data Partner reporting-API credentials (ssp/external).
     *
     * @return self
     */
    public function setApiData($api_data)
    {
        if (is_null($api_data)) {
            throw new \InvalidArgumentException('non-nullable api_data cannot be null');
        }
        $this->container['api_data'] = $api_data;

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



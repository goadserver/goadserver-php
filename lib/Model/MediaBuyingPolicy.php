<?php
/**
 * MediaBuyingPolicy
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
 * MediaBuyingPolicy Class Doc Comment
 *
 * @category Class
 * @description The buying policy of one ad zone (docs/MEDIA_BUYING.md). On PUT every field is optional: a field left out keeps its stored value; out-of-range values are clamped. Nothing scheduled acts on the zone until &#x60;enabled&#x60; is true.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class MediaBuyingPolicy implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'MediaBuyingPolicy';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'enabled' => 'bool',
        'mode' => 'string',
        'zone_slot' => 'string',
        'revenue_metric' => 'string',
        'window_days' => 'int',
        'min_spend' => 'float',
        'min_volume' => 'int',
        'target_margin_pct' => 'float',
        'max_cpa' => 'float',
        'max_blocks_per_day' => 'int',
        'judging' => 'string',
        'confidence_pct' => 'float',
        'pooling' => 'string',
        'pool_strength' => 'float',
        'ip_preblock_mode' => 'string',
        'ip_preblock_hosting' => 'bool',
        'ip_preblock_max' => 'int',
        'ip_preblock_ttl_days' => 'int',
        'attribution' => 'string',
        'report' => 'string',
        'min_delivery_pct' => 'float',
        'min_network_count' => 'int',
        'max_offgeo_pct' => 'float',
        'min_geo_volume' => 'int',
        'budget_reserve_pct' => 'float',
        'min_switch_minutes' => 'int',
        'max_daily_source_spend' => 'float',
        'stop_loss' => 'float',
        'bid_mode' => 'string',
        'bid_step_pct' => 'float',
        'bid_band_pct' => 'float',
        'bid_min_spend' => 'float',
        'min_bid' => 'float',
        'max_bid' => 'float',
        'bid_cooldown_hours' => 'int',
        'agent_mode' => 'string',
        'agent_every_hours' => 'int',
        'agent_every_minutes' => 'int',
        'agent_interval_auto' => 'bool',
        'agent_max_actions' => 'int',
        'agent_notes' => 'string',
        'control_for' => 'int',
        'start_preset' => 'string',
        'test_phase' => '\GoAdServerApi\Model\MediaBuyingPolicyTestPhase',
        'budget_mode' => 'string',
        'budget_headroom_pct' => 'float',
        'min_network_budget' => 'float',
        'max_network_budget' => 'float',
        'budget_pace_hours' => 'int',
        'segment_mode' => 'string',
        'segment_min_spend' => 'float',
        'segment_window_days' => 'int',
        'mirror_advertiser_blocks' => 'bool',
        'zone_bid_mode' => 'string',
        'zone_bid_step_pct' => 'float',
        'zone_bid_band_pct' => 'float',
        'zone_bid_floor_pct' => 'float',
        'zone_bid_ceiling_pct' => 'float',
        'inherit_blocks' => 'object'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'enabled' => null,
        'mode' => null,
        'zone_slot' => null,
        'revenue_metric' => null,
        'window_days' => null,
        'min_spend' => null,
        'min_volume' => null,
        'target_margin_pct' => null,
        'max_cpa' => null,
        'max_blocks_per_day' => null,
        'judging' => null,
        'confidence_pct' => null,
        'pooling' => null,
        'pool_strength' => null,
        'ip_preblock_mode' => null,
        'ip_preblock_hosting' => null,
        'ip_preblock_max' => null,
        'ip_preblock_ttl_days' => null,
        'attribution' => null,
        'report' => null,
        'min_delivery_pct' => null,
        'min_network_count' => null,
        'max_offgeo_pct' => null,
        'min_geo_volume' => null,
        'budget_reserve_pct' => null,
        'min_switch_minutes' => null,
        'max_daily_source_spend' => null,
        'stop_loss' => null,
        'bid_mode' => null,
        'bid_step_pct' => null,
        'bid_band_pct' => null,
        'bid_min_spend' => null,
        'min_bid' => null,
        'max_bid' => null,
        'bid_cooldown_hours' => null,
        'agent_mode' => null,
        'agent_every_hours' => null,
        'agent_every_minutes' => null,
        'agent_interval_auto' => null,
        'agent_max_actions' => null,
        'agent_notes' => null,
        'control_for' => null,
        'start_preset' => null,
        'test_phase' => null,
        'budget_mode' => null,
        'budget_headroom_pct' => null,
        'min_network_budget' => null,
        'max_network_budget' => null,
        'budget_pace_hours' => null,
        'segment_mode' => null,
        'segment_min_spend' => null,
        'segment_window_days' => null,
        'mirror_advertiser_blocks' => null,
        'zone_bid_mode' => null,
        'zone_bid_step_pct' => null,
        'zone_bid_band_pct' => null,
        'zone_bid_floor_pct' => null,
        'zone_bid_ceiling_pct' => null,
        'inherit_blocks' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'enabled' => false,
        'mode' => false,
        'zone_slot' => false,
        'revenue_metric' => false,
        'window_days' => false,
        'min_spend' => false,
        'min_volume' => false,
        'target_margin_pct' => false,
        'max_cpa' => false,
        'max_blocks_per_day' => false,
        'judging' => false,
        'confidence_pct' => false,
        'pooling' => false,
        'pool_strength' => false,
        'ip_preblock_mode' => false,
        'ip_preblock_hosting' => false,
        'ip_preblock_max' => false,
        'ip_preblock_ttl_days' => false,
        'attribution' => false,
        'report' => false,
        'min_delivery_pct' => false,
        'min_network_count' => false,
        'max_offgeo_pct' => false,
        'min_geo_volume' => false,
        'budget_reserve_pct' => false,
        'min_switch_minutes' => false,
        'max_daily_source_spend' => false,
        'stop_loss' => false,
        'bid_mode' => false,
        'bid_step_pct' => false,
        'bid_band_pct' => false,
        'bid_min_spend' => false,
        'min_bid' => false,
        'max_bid' => false,
        'bid_cooldown_hours' => false,
        'agent_mode' => false,
        'agent_every_hours' => false,
        'agent_every_minutes' => false,
        'agent_interval_auto' => false,
        'agent_max_actions' => false,
        'agent_notes' => false,
        'control_for' => false,
        'start_preset' => false,
        'test_phase' => false,
        'budget_mode' => false,
        'budget_headroom_pct' => false,
        'min_network_budget' => false,
        'max_network_budget' => false,
        'budget_pace_hours' => false,
        'segment_mode' => false,
        'segment_min_spend' => false,
        'segment_window_days' => false,
        'mirror_advertiser_blocks' => false,
        'zone_bid_mode' => false,
        'zone_bid_step_pct' => false,
        'zone_bid_band_pct' => false,
        'zone_bid_floor_pct' => false,
        'zone_bid_ceiling_pct' => false,
        'inherit_blocks' => false
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
        'enabled' => 'enabled',
        'mode' => 'mode',
        'zone_slot' => 'zone_slot',
        'revenue_metric' => 'revenue_metric',
        'window_days' => 'window_days',
        'min_spend' => 'min_spend',
        'min_volume' => 'min_volume',
        'target_margin_pct' => 'target_margin_pct',
        'max_cpa' => 'max_cpa',
        'max_blocks_per_day' => 'max_blocks_per_day',
        'judging' => 'judging',
        'confidence_pct' => 'confidence_pct',
        'pooling' => 'pooling',
        'pool_strength' => 'pool_strength',
        'ip_preblock_mode' => 'ip_preblock_mode',
        'ip_preblock_hosting' => 'ip_preblock_hosting',
        'ip_preblock_max' => 'ip_preblock_max',
        'ip_preblock_ttl_days' => 'ip_preblock_ttl_days',
        'attribution' => 'attribution',
        'report' => 'report',
        'min_delivery_pct' => 'min_delivery_pct',
        'min_network_count' => 'min_network_count',
        'max_offgeo_pct' => 'max_offgeo_pct',
        'min_geo_volume' => 'min_geo_volume',
        'budget_reserve_pct' => 'budget_reserve_pct',
        'min_switch_minutes' => 'min_switch_minutes',
        'max_daily_source_spend' => 'max_daily_source_spend',
        'stop_loss' => 'stop_loss',
        'bid_mode' => 'bid_mode',
        'bid_step_pct' => 'bid_step_pct',
        'bid_band_pct' => 'bid_band_pct',
        'bid_min_spend' => 'bid_min_spend',
        'min_bid' => 'min_bid',
        'max_bid' => 'max_bid',
        'bid_cooldown_hours' => 'bid_cooldown_hours',
        'agent_mode' => 'agent_mode',
        'agent_every_hours' => 'agent_every_hours',
        'agent_every_minutes' => 'agent_every_minutes',
        'agent_interval_auto' => 'agent_interval_auto',
        'agent_max_actions' => 'agent_max_actions',
        'agent_notes' => 'agent_notes',
        'control_for' => 'control_for',
        'start_preset' => 'start_preset',
        'test_phase' => 'test_phase',
        'budget_mode' => 'budget_mode',
        'budget_headroom_pct' => 'budget_headroom_pct',
        'min_network_budget' => 'min_network_budget',
        'max_network_budget' => 'max_network_budget',
        'budget_pace_hours' => 'budget_pace_hours',
        'segment_mode' => 'segment_mode',
        'segment_min_spend' => 'segment_min_spend',
        'segment_window_days' => 'segment_window_days',
        'mirror_advertiser_blocks' => 'mirror_advertiser_blocks',
        'zone_bid_mode' => 'zone_bid_mode',
        'zone_bid_step_pct' => 'zone_bid_step_pct',
        'zone_bid_band_pct' => 'zone_bid_band_pct',
        'zone_bid_floor_pct' => 'zone_bid_floor_pct',
        'zone_bid_ceiling_pct' => 'zone_bid_ceiling_pct',
        'inherit_blocks' => 'inherit_blocks'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'enabled' => 'setEnabled',
        'mode' => 'setMode',
        'zone_slot' => 'setZoneSlot',
        'revenue_metric' => 'setRevenueMetric',
        'window_days' => 'setWindowDays',
        'min_spend' => 'setMinSpend',
        'min_volume' => 'setMinVolume',
        'target_margin_pct' => 'setTargetMarginPct',
        'max_cpa' => 'setMaxCpa',
        'max_blocks_per_day' => 'setMaxBlocksPerDay',
        'judging' => 'setJudging',
        'confidence_pct' => 'setConfidencePct',
        'pooling' => 'setPooling',
        'pool_strength' => 'setPoolStrength',
        'ip_preblock_mode' => 'setIpPreblockMode',
        'ip_preblock_hosting' => 'setIpPreblockHosting',
        'ip_preblock_max' => 'setIpPreblockMax',
        'ip_preblock_ttl_days' => 'setIpPreblockTtlDays',
        'attribution' => 'setAttribution',
        'report' => 'setReport',
        'min_delivery_pct' => 'setMinDeliveryPct',
        'min_network_count' => 'setMinNetworkCount',
        'max_offgeo_pct' => 'setMaxOffgeoPct',
        'min_geo_volume' => 'setMinGeoVolume',
        'budget_reserve_pct' => 'setBudgetReservePct',
        'min_switch_minutes' => 'setMinSwitchMinutes',
        'max_daily_source_spend' => 'setMaxDailySourceSpend',
        'stop_loss' => 'setStopLoss',
        'bid_mode' => 'setBidMode',
        'bid_step_pct' => 'setBidStepPct',
        'bid_band_pct' => 'setBidBandPct',
        'bid_min_spend' => 'setBidMinSpend',
        'min_bid' => 'setMinBid',
        'max_bid' => 'setMaxBid',
        'bid_cooldown_hours' => 'setBidCooldownHours',
        'agent_mode' => 'setAgentMode',
        'agent_every_hours' => 'setAgentEveryHours',
        'agent_every_minutes' => 'setAgentEveryMinutes',
        'agent_interval_auto' => 'setAgentIntervalAuto',
        'agent_max_actions' => 'setAgentMaxActions',
        'agent_notes' => 'setAgentNotes',
        'control_for' => 'setControlFor',
        'start_preset' => 'setStartPreset',
        'test_phase' => 'setTestPhase',
        'budget_mode' => 'setBudgetMode',
        'budget_headroom_pct' => 'setBudgetHeadroomPct',
        'min_network_budget' => 'setMinNetworkBudget',
        'max_network_budget' => 'setMaxNetworkBudget',
        'budget_pace_hours' => 'setBudgetPaceHours',
        'segment_mode' => 'setSegmentMode',
        'segment_min_spend' => 'setSegmentMinSpend',
        'segment_window_days' => 'setSegmentWindowDays',
        'mirror_advertiser_blocks' => 'setMirrorAdvertiserBlocks',
        'zone_bid_mode' => 'setZoneBidMode',
        'zone_bid_step_pct' => 'setZoneBidStepPct',
        'zone_bid_band_pct' => 'setZoneBidBandPct',
        'zone_bid_floor_pct' => 'setZoneBidFloorPct',
        'zone_bid_ceiling_pct' => 'setZoneBidCeilingPct',
        'inherit_blocks' => 'setInheritBlocks'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'enabled' => 'getEnabled',
        'mode' => 'getMode',
        'zone_slot' => 'getZoneSlot',
        'revenue_metric' => 'getRevenueMetric',
        'window_days' => 'getWindowDays',
        'min_spend' => 'getMinSpend',
        'min_volume' => 'getMinVolume',
        'target_margin_pct' => 'getTargetMarginPct',
        'max_cpa' => 'getMaxCpa',
        'max_blocks_per_day' => 'getMaxBlocksPerDay',
        'judging' => 'getJudging',
        'confidence_pct' => 'getConfidencePct',
        'pooling' => 'getPooling',
        'pool_strength' => 'getPoolStrength',
        'ip_preblock_mode' => 'getIpPreblockMode',
        'ip_preblock_hosting' => 'getIpPreblockHosting',
        'ip_preblock_max' => 'getIpPreblockMax',
        'ip_preblock_ttl_days' => 'getIpPreblockTtlDays',
        'attribution' => 'getAttribution',
        'report' => 'getReport',
        'min_delivery_pct' => 'getMinDeliveryPct',
        'min_network_count' => 'getMinNetworkCount',
        'max_offgeo_pct' => 'getMaxOffgeoPct',
        'min_geo_volume' => 'getMinGeoVolume',
        'budget_reserve_pct' => 'getBudgetReservePct',
        'min_switch_minutes' => 'getMinSwitchMinutes',
        'max_daily_source_spend' => 'getMaxDailySourceSpend',
        'stop_loss' => 'getStopLoss',
        'bid_mode' => 'getBidMode',
        'bid_step_pct' => 'getBidStepPct',
        'bid_band_pct' => 'getBidBandPct',
        'bid_min_spend' => 'getBidMinSpend',
        'min_bid' => 'getMinBid',
        'max_bid' => 'getMaxBid',
        'bid_cooldown_hours' => 'getBidCooldownHours',
        'agent_mode' => 'getAgentMode',
        'agent_every_hours' => 'getAgentEveryHours',
        'agent_every_minutes' => 'getAgentEveryMinutes',
        'agent_interval_auto' => 'getAgentIntervalAuto',
        'agent_max_actions' => 'getAgentMaxActions',
        'agent_notes' => 'getAgentNotes',
        'control_for' => 'getControlFor',
        'start_preset' => 'getStartPreset',
        'test_phase' => 'getTestPhase',
        'budget_mode' => 'getBudgetMode',
        'budget_headroom_pct' => 'getBudgetHeadroomPct',
        'min_network_budget' => 'getMinNetworkBudget',
        'max_network_budget' => 'getMaxNetworkBudget',
        'budget_pace_hours' => 'getBudgetPaceHours',
        'segment_mode' => 'getSegmentMode',
        'segment_min_spend' => 'getSegmentMinSpend',
        'segment_window_days' => 'getSegmentWindowDays',
        'mirror_advertiser_blocks' => 'getMirrorAdvertiserBlocks',
        'zone_bid_mode' => 'getZoneBidMode',
        'zone_bid_step_pct' => 'getZoneBidStepPct',
        'zone_bid_band_pct' => 'getZoneBidBandPct',
        'zone_bid_floor_pct' => 'getZoneBidFloorPct',
        'zone_bid_ceiling_pct' => 'getZoneBidCeilingPct',
        'inherit_blocks' => 'getInheritBlocks'
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

    public const START_PRESET_CONSERVATIVE = 'conservative';
    public const START_PRESET_BALANCED = 'balanced';
    public const START_PRESET_AGGRESSIVE = 'aggressive';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getStartPresetAllowableValues()
    {
        return [
            self::START_PRESET_CONSERVATIVE,
            self::START_PRESET_BALANCED,
            self::START_PRESET_AGGRESSIVE,
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
        $this->setIfExists('enabled', $data ?? [], null);
        $this->setIfExists('mode', $data ?? [], null);
        $this->setIfExists('zone_slot', $data ?? [], null);
        $this->setIfExists('revenue_metric', $data ?? [], null);
        $this->setIfExists('window_days', $data ?? [], null);
        $this->setIfExists('min_spend', $data ?? [], null);
        $this->setIfExists('min_volume', $data ?? [], null);
        $this->setIfExists('target_margin_pct', $data ?? [], null);
        $this->setIfExists('max_cpa', $data ?? [], null);
        $this->setIfExists('max_blocks_per_day', $data ?? [], null);
        $this->setIfExists('judging', $data ?? [], null);
        $this->setIfExists('confidence_pct', $data ?? [], null);
        $this->setIfExists('pooling', $data ?? [], null);
        $this->setIfExists('pool_strength', $data ?? [], null);
        $this->setIfExists('ip_preblock_mode', $data ?? [], null);
        $this->setIfExists('ip_preblock_hosting', $data ?? [], null);
        $this->setIfExists('ip_preblock_max', $data ?? [], null);
        $this->setIfExists('ip_preblock_ttl_days', $data ?? [], null);
        $this->setIfExists('attribution', $data ?? [], null);
        $this->setIfExists('report', $data ?? [], null);
        $this->setIfExists('min_delivery_pct', $data ?? [], null);
        $this->setIfExists('min_network_count', $data ?? [], null);
        $this->setIfExists('max_offgeo_pct', $data ?? [], null);
        $this->setIfExists('min_geo_volume', $data ?? [], null);
        $this->setIfExists('budget_reserve_pct', $data ?? [], null);
        $this->setIfExists('min_switch_minutes', $data ?? [], null);
        $this->setIfExists('max_daily_source_spend', $data ?? [], null);
        $this->setIfExists('stop_loss', $data ?? [], null);
        $this->setIfExists('bid_mode', $data ?? [], null);
        $this->setIfExists('bid_step_pct', $data ?? [], null);
        $this->setIfExists('bid_band_pct', $data ?? [], null);
        $this->setIfExists('bid_min_spend', $data ?? [], null);
        $this->setIfExists('min_bid', $data ?? [], null);
        $this->setIfExists('max_bid', $data ?? [], null);
        $this->setIfExists('bid_cooldown_hours', $data ?? [], null);
        $this->setIfExists('agent_mode', $data ?? [], null);
        $this->setIfExists('agent_every_hours', $data ?? [], null);
        $this->setIfExists('agent_every_minutes', $data ?? [], null);
        $this->setIfExists('agent_interval_auto', $data ?? [], null);
        $this->setIfExists('agent_max_actions', $data ?? [], null);
        $this->setIfExists('agent_notes', $data ?? [], null);
        $this->setIfExists('control_for', $data ?? [], null);
        $this->setIfExists('start_preset', $data ?? [], null);
        $this->setIfExists('test_phase', $data ?? [], null);
        $this->setIfExists('budget_mode', $data ?? [], null);
        $this->setIfExists('budget_headroom_pct', $data ?? [], null);
        $this->setIfExists('min_network_budget', $data ?? [], null);
        $this->setIfExists('max_network_budget', $data ?? [], null);
        $this->setIfExists('budget_pace_hours', $data ?? [], null);
        $this->setIfExists('segment_mode', $data ?? [], null);
        $this->setIfExists('segment_min_spend', $data ?? [], null);
        $this->setIfExists('segment_window_days', $data ?? [], null);
        $this->setIfExists('mirror_advertiser_blocks', $data ?? [], null);
        $this->setIfExists('zone_bid_mode', $data ?? [], null);
        $this->setIfExists('zone_bid_step_pct', $data ?? [], null);
        $this->setIfExists('zone_bid_band_pct', $data ?? [], null);
        $this->setIfExists('zone_bid_floor_pct', $data ?? [], null);
        $this->setIfExists('zone_bid_ceiling_pct', $data ?? [], null);
        $this->setIfExists('inherit_blocks', $data ?? [], null);
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

        $allowedValues = $this->getStartPresetAllowableValues();
        if (!is_null($this->container['start_preset']) && !in_array($this->container['start_preset'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'start_preset', must be one of '%s'",
                $this->container['start_preset'],
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
     * Gets enabled
     *
     * @return bool|null
     */
    public function getEnabled()
    {
        return $this->container['enabled'];
    }

    /**
     * Sets enabled
     *
     * @param bool|null $enabled The zone's master switch. Off (the default): no scheduled job acts on the zone whatever the modes say; the report and your own actions still work.
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        if (is_null($enabled)) {
            throw new \InvalidArgumentException('non-nullable enabled cannot be null');
        }
        $this->container['enabled'] = $enabled;

        return $this;
    }

    /**
     * Gets mode
     *
     * @return string|null
     */
    public function getMode()
    {
        return $this->container['mode'];
    }

    /**
     * Sets mode
     *
     * @param string|null $mode off | advise | auto
     *
     * @return self
     */
    public function setMode($mode)
    {
        if (is_null($mode)) {
            throw new \InvalidArgumentException('non-nullable mode cannot be null');
        }
        $this->container['mode'] = $mode;

        return $this;
    }

    /**
     * Gets zone_slot
     *
     * @return string|null
     */
    public function getZoneSlot()
    {
        return $this->container['zone_slot'];
    }

    /**
     * Sets zone_slot
     *
     * @param string|null $zone_slot Stats slot holding the source zone id
     *
     * @return self
     */
    public function setZoneSlot($zone_slot)
    {
        if (is_null($zone_slot)) {
            throw new \InvalidArgumentException('non-nullable zone_slot cannot be null');
        }
        $this->container['zone_slot'] = $zone_slot;

        return $this;
    }

    /**
     * Gets revenue_metric
     *
     * @return string|null
     */
    public function getRevenueMetric()
    {
        return $this->container['revenue_metric'];
    }

    /**
     * Sets revenue_metric
     *
     * @param string|null $revenue_metric paid | earned
     *
     * @return self
     */
    public function setRevenueMetric($revenue_metric)
    {
        if (is_null($revenue_metric)) {
            throw new \InvalidArgumentException('non-nullable revenue_metric cannot be null');
        }
        $this->container['revenue_metric'] = $revenue_metric;

        return $this;
    }

    /**
     * Gets window_days
     *
     * @return int|null
     */
    public function getWindowDays()
    {
        return $this->container['window_days'];
    }

    /**
     * Sets window_days
     *
     * @param int|null $window_days Days judged, 1-30
     *
     * @return self
     */
    public function setWindowDays($window_days)
    {
        if (is_null($window_days)) {
            throw new \InvalidArgumentException('non-nullable window_days cannot be null');
        }
        $this->container['window_days'] = $window_days;

        return $this;
    }

    /**
     * Gets min_spend
     *
     * @return float|null
     */
    public function getMinSpend()
    {
        return $this->container['min_spend'];
    }

    /**
     * Sets min_spend
     *
     * @param float|null $min_spend Judge a zone only after this cost (main currency)
     *
     * @return self
     */
    public function setMinSpend($min_spend)
    {
        if (is_null($min_spend)) {
            throw new \InvalidArgumentException('non-nullable min_spend cannot be null');
        }
        $this->container['min_spend'] = $min_spend;

        return $this;
    }

    /**
     * Gets min_volume
     *
     * @return int|null
     */
    public function getMinVolume()
    {
        return $this->container['min_volume'];
    }

    /**
     * Sets min_volume
     *
     * @param int|null $min_volume Judge a zone only after this much traffic too (clicks or impressions, per ad type).
     *
     * @return self
     */
    public function setMinVolume($min_volume)
    {
        if (is_null($min_volume)) {
            throw new \InvalidArgumentException('non-nullable min_volume cannot be null');
        }
        $this->container['min_volume'] = $min_volume;

        return $this;
    }

    /**
     * Gets target_margin_pct
     *
     * @return float|null
     */
    public function getTargetMarginPct()
    {
        return $this->container['target_margin_pct'];
    }

    /**
     * Sets target_margin_pct
     *
     * @param float|null $target_margin_pct Block below this margin; null = not used
     *
     * @return self
     */
    public function setTargetMarginPct($target_margin_pct)
    {
        if (is_null($target_margin_pct)) {
            throw new \InvalidArgumentException('non-nullable target_margin_pct cannot be null');
        }
        $this->container['target_margin_pct'] = $target_margin_pct;

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
     * @param float|null $max_cpa Block above this cost per conversion; null = not used
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
     * Gets max_blocks_per_day
     *
     * @return int|null
     */
    public function getMaxBlocksPerDay()
    {
        return $this->container['max_blocks_per_day'];
    }

    /**
     * Sets max_blocks_per_day
     *
     * @param int|null $max_blocks_per_day Auto mode: blocks the policy may apply per day
     *
     * @return self
     */
    public function setMaxBlocksPerDay($max_blocks_per_day)
    {
        if (is_null($max_blocks_per_day)) {
            throw new \InvalidArgumentException('non-nullable max_blocks_per_day cannot be null');
        }
        $this->container['max_blocks_per_day'] = $max_blocks_per_day;

        return $this;
    }

    /**
     * Gets judging
     *
     * @return string|null
     */
    public function getJudging()
    {
        return $this->container['judging'];
    }

    /**
     * Sets judging
     *
     * @param string|null $judging fixed | confidence
     *
     * @return self
     */
    public function setJudging($judging)
    {
        if (is_null($judging)) {
            throw new \InvalidArgumentException('non-nullable judging cannot be null');
        }
        $this->container['judging'] = $judging;

        return $this;
    }

    /**
     * Gets confidence_pct
     *
     * @return float|null
     */
    public function getConfidencePct()
    {
        return $this->container['confidence_pct'];
    }

    /**
     * Sets confidence_pct
     *
     * @param float|null $confidence_pct 70-99
     *
     * @return self
     */
    public function setConfidencePct($confidence_pct)
    {
        if (is_null($confidence_pct)) {
            throw new \InvalidArgumentException('non-nullable confidence_pct cannot be null');
        }
        $this->container['confidence_pct'] = $confidence_pct;

        return $this;
    }

    /**
     * Gets pooling
     *
     * @return string|null
     */
    public function getPooling()
    {
        return $this->container['pooling'];
    }

    /**
     * Sets pooling
     *
     * @param string|null $pooling off | buy | country
     *
     * @return self
     */
    public function setPooling($pooling)
    {
        if (is_null($pooling)) {
            throw new \InvalidArgumentException('non-nullable pooling cannot be null');
        }
        $this->container['pooling'] = $pooling;

        return $this;
    }

    /**
     * Gets pool_strength
     *
     * @return float|null
     */
    public function getPoolStrength()
    {
        return $this->container['pool_strength'];
    }

    /**
     * Sets pool_strength
     *
     * @param float|null $pool_strength The group counts as this many paid events, 0-20
     *
     * @return self
     */
    public function setPoolStrength($pool_strength)
    {
        if (is_null($pool_strength)) {
            throw new \InvalidArgumentException('non-nullable pool_strength cannot be null');
        }
        $this->container['pool_strength'] = $pool_strength;

        return $this;
    }

    /**
     * Gets ip_preblock_mode
     *
     * @return string|null
     */
    public function getIpPreblockMode()
    {
        return $this->container['ip_preblock_mode'];
    }

    /**
     * Sets ip_preblock_mode
     *
     * @param string|null $ip_preblock_mode off | advise | auto
     *
     * @return self
     */
    public function setIpPreblockMode($ip_preblock_mode)
    {
        if (is_null($ip_preblock_mode)) {
            throw new \InvalidArgumentException('non-nullable ip_preblock_mode cannot be null');
        }
        $this->container['ip_preblock_mode'] = $ip_preblock_mode;

        return $this;
    }

    /**
     * Gets ip_preblock_hosting
     *
     * @return bool|null
     */
    public function getIpPreblockHosting()
    {
        return $this->container['ip_preblock_hosting'];
    }

    /**
     * Sets ip_preblock_hosting
     *
     * @param bool|null $ip_preblock_hosting Also data centre / VPN ranges and ASNs
     *
     * @return self
     */
    public function setIpPreblockHosting($ip_preblock_hosting)
    {
        if (is_null($ip_preblock_hosting)) {
            throw new \InvalidArgumentException('non-nullable ip_preblock_hosting cannot be null');
        }
        $this->container['ip_preblock_hosting'] = $ip_preblock_hosting;

        return $this;
    }

    /**
     * Gets ip_preblock_max
     *
     * @return int|null
     */
    public function getIpPreblockMax()
    {
        return $this->container['ip_preblock_max'];
    }

    /**
     * Sets ip_preblock_max
     *
     * @param int|null $ip_preblock_max Entries on the list, 1-1000
     *
     * @return self
     */
    public function setIpPreblockMax($ip_preblock_max)
    {
        if (is_null($ip_preblock_max)) {
            throw new \InvalidArgumentException('non-nullable ip_preblock_max cannot be null');
        }
        $this->container['ip_preblock_max'] = $ip_preblock_max;

        return $this;
    }

    /**
     * Gets ip_preblock_ttl_days
     *
     * @return int|null
     */
    public function getIpPreblockTtlDays()
    {
        return $this->container['ip_preblock_ttl_days'];
    }

    /**
     * Sets ip_preblock_ttl_days
     *
     * @param int|null $ip_preblock_ttl_days Take an entry off after this many days unseen
     *
     * @return self
     */
    public function setIpPreblockTtlDays($ip_preblock_ttl_days)
    {
        if (is_null($ip_preblock_ttl_days)) {
            throw new \InvalidArgumentException('non-nullable ip_preblock_ttl_days cannot be null');
        }
        $this->container['ip_preblock_ttl_days'] = $ip_preblock_ttl_days;

        return $this;
    }

    /**
     * Gets attribution
     *
     * @return string|null
     */
    public function getAttribution()
    {
        return $this->container['attribution'];
    }

    /**
     * Sets attribution
     *
     * @param string|null $attribution Which day a conversion's revenue counts on: `conversion` (the day it arrives, default) | `click` (the day of the visit that led to it; needs the source id in subid).
     *
     * @return self
     */
    public function setAttribution($attribution)
    {
        if (is_null($attribution)) {
            throw new \InvalidArgumentException('non-nullable attribution cannot be null');
        }
        $this->container['attribution'] = $attribution;

        return $this;
    }

    /**
     * Gets report
     *
     * @return string|null
     */
    public function getReport()
    {
        return $this->container['report'];
    }

    /**
     * Sets report
     *
     * @param string|null $report `off` | `daily` (08:00 tenant time) | `weekly` (Mondays 08:00) report by email / Telegram / Slack and the `media_buying.report` event.
     *
     * @return self
     */
    public function setReport($report)
    {
        if (is_null($report)) {
            throw new \InvalidArgumentException('non-nullable report cannot be null');
        }
        $this->container['report'] = $report;

        return $this;
    }

    /**
     * Gets min_delivery_pct
     *
     * @return float|null
     */
    public function getMinDeliveryPct()
    {
        return $this->container['min_delivery_pct'];
    }

    /**
     * Sets min_delivery_pct
     *
     * @param float|null $min_delivery_pct Block below this % delivered; null = off
     *
     * @return self
     */
    public function setMinDeliveryPct($min_delivery_pct)
    {
        if (is_null($min_delivery_pct)) {
            throw new \InvalidArgumentException('non-nullable min_delivery_pct cannot be null');
        }
        $this->container['min_delivery_pct'] = $min_delivery_pct;

        return $this;
    }

    /**
     * Gets min_network_count
     *
     * @return int|null
     */
    public function getMinNetworkCount()
    {
        return $this->container['min_network_count'];
    }

    /**
     * Sets min_network_count
     *
     * @param int|null $min_network_count Judge delivery only after this many network impressions/clicks
     *
     * @return self
     */
    public function setMinNetworkCount($min_network_count)
    {
        if (is_null($min_network_count)) {
            throw new \InvalidArgumentException('non-nullable min_network_count cannot be null');
        }
        $this->container['min_network_count'] = $min_network_count;

        return $this;
    }

    /**
     * Gets max_offgeo_pct
     *
     * @return float|null
     */
    public function getMaxOffgeoPct()
    {
        return $this->container['max_offgeo_pct'];
    }

    /**
     * Sets max_offgeo_pct
     *
     * @param float|null $max_offgeo_pct Block a zone whose visitors come from outside the campaign's countries more than this share; null = off.
     *
     * @return self
     */
    public function setMaxOffgeoPct($max_offgeo_pct)
    {
        if (is_null($max_offgeo_pct)) {
            throw new \InvalidArgumentException('non-nullable max_offgeo_pct cannot be null');
        }
        $this->container['max_offgeo_pct'] = $max_offgeo_pct;

        return $this;
    }

    /**
     * Gets min_geo_volume
     *
     * @return int|null
     */
    public function getMinGeoVolume()
    {
        return $this->container['min_geo_volume'];
    }

    /**
     * Sets min_geo_volume
     *
     * @param int|null $min_geo_volume Judge geo only after this many visits (or clicks)
     *
     * @return self
     */
    public function setMinGeoVolume($min_geo_volume)
    {
        if (is_null($min_geo_volume)) {
            throw new \InvalidArgumentException('non-nullable min_geo_volume cannot be null');
        }
        $this->container['min_geo_volume'] = $min_geo_volume;

        return $this;
    }

    /**
     * Gets budget_reserve_pct
     *
     * @return float|null
     */
    public function getBudgetReservePct()
    {
        return $this->container['budget_reserve_pct'];
    }

    /**
     * Sets budget_reserve_pct
     *
     * @param float|null $budget_reserve_pct Goadserver: stop buying when a linked campaign has < this % of its day budget left
     *
     * @return self
     */
    public function setBudgetReservePct($budget_reserve_pct)
    {
        if (is_null($budget_reserve_pct)) {
            throw new \InvalidArgumentException('non-nullable budget_reserve_pct cannot be null');
        }
        $this->container['budget_reserve_pct'] = $budget_reserve_pct;

        return $this;
    }

    /**
     * Gets min_switch_minutes
     *
     * @return int|null
     */
    public function getMinSwitchMinutes()
    {
        return $this->container['min_switch_minutes'];
    }

    /**
     * Sets min_switch_minutes
     *
     * @param int|null $min_switch_minutes Goadserver: no re-activation within this many minutes of the last switch
     *
     * @return self
     */
    public function setMinSwitchMinutes($min_switch_minutes)
    {
        if (is_null($min_switch_minutes)) {
            throw new \InvalidArgumentException('non-nullable min_switch_minutes cannot be null');
        }
        $this->container['min_switch_minutes'] = $min_switch_minutes;

        return $this;
    }

    /**
     * Gets max_daily_source_spend
     *
     * @return float|null
     */
    public function getMaxDailySourceSpend()
    {
        return $this->container['max_daily_source_spend'];
    }

    /**
     * Sets max_daily_source_spend
     *
     * @param float|null $max_daily_source_spend Hold the source once today's external cost reaches this
     *
     * @return self
     */
    public function setMaxDailySourceSpend($max_daily_source_spend)
    {
        if (is_null($max_daily_source_spend)) {
            throw new \InvalidArgumentException('non-nullable max_daily_source_spend cannot be null');
        }
        $this->container['max_daily_source_spend'] = $max_daily_source_spend;

        return $this;
    }

    /**
     * Gets stop_loss
     *
     * @return float|null
     */
    public function getStopLoss()
    {
        return $this->container['stop_loss'];
    }

    /**
     * Sets stop_loss
     *
     * @param float|null $stop_loss Hold the source once today's profit on the adzone is below -this
     *
     * @return self
     */
    public function setStopLoss($stop_loss)
    {
        if (is_null($stop_loss)) {
            throw new \InvalidArgumentException('non-nullable stop_loss cannot be null');
        }
        $this->container['stop_loss'] = $stop_loss;

        return $this;
    }

    /**
     * Gets bid_mode
     *
     * @return string|null
     */
    public function getBidMode()
    {
        return $this->container['bid_mode'];
    }

    /**
     * Sets bid_mode
     *
     * @param string|null $bid_mode off | advise | auto (auto only on networks with absolute bids)
     *
     * @return self
     */
    public function setBidMode($bid_mode)
    {
        if (is_null($bid_mode)) {
            throw new \InvalidArgumentException('non-nullable bid_mode cannot be null');
        }
        $this->container['bid_mode'] = $bid_mode;

        return $this;
    }

    /**
     * Gets bid_step_pct
     *
     * @return float|null
     */
    public function getBidStepPct()
    {
        return $this->container['bid_step_pct'];
    }

    /**
     * Sets bid_step_pct
     *
     * @param float|null $bid_step_pct Size of one change, 1-30 %
     *
     * @return self
     */
    public function setBidStepPct($bid_step_pct)
    {
        if (is_null($bid_step_pct)) {
            throw new \InvalidArgumentException('non-nullable bid_step_pct cannot be null');
        }
        $this->container['bid_step_pct'] = $bid_step_pct;

        return $this;
    }

    /**
     * Gets bid_band_pct
     *
     * @return float|null
     */
    public function getBidBandPct()
    {
        return $this->container['bid_band_pct'];
    }

    /**
     * Sets bid_band_pct
     *
     * @param float|null $bid_band_pct Margin dead-zone around the target, in points
     *
     * @return self
     */
    public function setBidBandPct($bid_band_pct)
    {
        if (is_null($bid_band_pct)) {
            throw new \InvalidArgumentException('non-nullable bid_band_pct cannot be null');
        }
        $this->container['bid_band_pct'] = $bid_band_pct;

        return $this;
    }

    /**
     * Gets bid_min_spend
     *
     * @return float|null
     */
    public function getBidMinSpend()
    {
        return $this->container['bid_min_spend'];
    }

    /**
     * Sets bid_min_spend
     *
     * @param float|null $bid_min_spend Judge only once the window cost reaches this
     *
     * @return self
     */
    public function setBidMinSpend($bid_min_spend)
    {
        if (is_null($bid_min_spend)) {
            throw new \InvalidArgumentException('non-nullable bid_min_spend cannot be null');
        }
        $this->container['bid_min_spend'] = $bid_min_spend;

        return $this;
    }

    /**
     * Gets min_bid
     *
     * @return float|null
     */
    public function getMinBid()
    {
        return $this->container['min_bid'];
    }

    /**
     * Sets min_bid
     *
     * @param float|null $min_bid Never lower below (network bid units)
     *
     * @return self
     */
    public function setMinBid($min_bid)
    {
        if (is_null($min_bid)) {
            throw new \InvalidArgumentException('non-nullable min_bid cannot be null');
        }
        $this->container['min_bid'] = $min_bid;

        return $this;
    }

    /**
     * Gets max_bid
     *
     * @return float|null
     */
    public function getMaxBid()
    {
        return $this->container['max_bid'];
    }

    /**
     * Sets max_bid
     *
     * @param float|null $max_bid Never raise above; no raise at all without it
     *
     * @return self
     */
    public function setMaxBid($max_bid)
    {
        if (is_null($max_bid)) {
            throw new \InvalidArgumentException('non-nullable max_bid cannot be null');
        }
        $this->container['max_bid'] = $max_bid;

        return $this;
    }

    /**
     * Gets bid_cooldown_hours
     *
     * @return int|null
     */
    public function getBidCooldownHours()
    {
        return $this->container['bid_cooldown_hours'];
    }

    /**
     * Sets bid_cooldown_hours
     *
     * @param int|null $bid_cooldown_hours At most one change (or open proposal) per this many hours
     *
     * @return self
     */
    public function setBidCooldownHours($bid_cooldown_hours)
    {
        if (is_null($bid_cooldown_hours)) {
            throw new \InvalidArgumentException('non-nullable bid_cooldown_hours cannot be null');
        }
        $this->container['bid_cooldown_hours'] = $bid_cooldown_hours;

        return $this;
    }

    /**
     * Gets agent_mode
     *
     * @return string|null
     */
    public function getAgentMode()
    {
        return $this->container['agent_mode'];
    }

    /**
     * Sets agent_mode
     *
     * @param string|null $agent_mode off | advise | auto
     *
     * @return self
     */
    public function setAgentMode($agent_mode)
    {
        if (is_null($agent_mode)) {
            throw new \InvalidArgumentException('non-nullable agent_mode cannot be null');
        }
        $this->container['agent_mode'] = $agent_mode;

        return $this;
    }

    /**
     * Gets agent_every_hours
     *
     * @return int|null
     */
    public function getAgentEveryHours()
    {
        return $this->container['agent_every_hours'];
    }

    /**
     * Sets agent_every_hours
     *
     * @param int|null $agent_every_hours Legacy: the agent interval in hours; read only when agent_every_minutes is unset.
     *
     * @return self
     */
    public function setAgentEveryHours($agent_every_hours)
    {
        if (is_null($agent_every_hours)) {
            throw new \InvalidArgumentException('non-nullable agent_every_hours cannot be null');
        }
        $this->container['agent_every_hours'] = $agent_every_hours;

        return $this;
    }

    /**
     * Gets agent_every_minutes
     *
     * @return int|null
     */
    public function getAgentEveryMinutes()
    {
        return $this->container['agent_every_minutes'];
    }

    /**
     * Sets agent_every_minutes
     *
     * @param int|null $agent_every_minutes How often the buying agent reviews the zone, 15-10080 minutes.
     *
     * @return self
     */
    public function setAgentEveryMinutes($agent_every_minutes)
    {
        if (is_null($agent_every_minutes)) {
            throw new \InvalidArgumentException('non-nullable agent_every_minutes cannot be null');
        }
        $this->container['agent_every_minutes'] = $agent_every_minutes;

        return $this;
    }

    /**
     * Gets agent_interval_auto
     *
     * @return bool|null
     */
    public function getAgentIntervalAuto()
    {
        return $this->container['agent_interval_auto'];
    }

    /**
     * Sets agent_interval_auto
     *
     * @param bool|null $agent_interval_auto Pick the agent interval from the zone's traffic instead of agent_every_minutes.
     *
     * @return self
     */
    public function setAgentIntervalAuto($agent_interval_auto)
    {
        if (is_null($agent_interval_auto)) {
            throw new \InvalidArgumentException('non-nullable agent_interval_auto cannot be null');
        }
        $this->container['agent_interval_auto'] = $agent_interval_auto;

        return $this;
    }

    /**
     * Gets agent_max_actions
     *
     * @return int|null
     */
    public function getAgentMaxActions()
    {
        return $this->container['agent_max_actions'];
    }

    /**
     * Sets agent_max_actions
     *
     * @param int|null $agent_max_actions Actions kept per run, 1-50
     *
     * @return self
     */
    public function setAgentMaxActions($agent_max_actions)
    {
        if (is_null($agent_max_actions)) {
            throw new \InvalidArgumentException('non-nullable agent_max_actions cannot be null');
        }
        $this->container['agent_max_actions'] = $agent_max_actions;

        return $this;
    }

    /**
     * Gets agent_notes
     *
     * @return string|null
     */
    public function getAgentNotes()
    {
        return $this->container['agent_notes'];
    }

    /**
     * Sets agent_notes
     *
     * @param string|null $agent_notes The buyer's own instructions, max 1000 characters
     *
     * @return self
     */
    public function setAgentNotes($agent_notes)
    {
        if (is_null($agent_notes)) {
            throw new \InvalidArgumentException('non-nullable agent_notes cannot be null');
        }
        $this->container['agent_notes'] = $agent_notes;

        return $this;
    }

    /**
     * Gets control_for
     *
     * @return int|null
     */
    public function getControlFor()
    {
        return $this->container['control_for'];
    }

    /**
     * Sets control_for
     *
     * @param int|null $control_for Make this buy the untouched control of buy N (another of your connected ad zones / tracking links), e.g. a campaign left to the network's own optimiser: saving it switches `enabled` off. 0 = not a control. Compare the two with GET …/media-buying/compare.
     *
     * @return self
     */
    public function setControlFor($control_for)
    {
        if (is_null($control_for)) {
            throw new \InvalidArgumentException('non-nullable control_for cannot be null');
        }
        $this->container['control_for'] = $control_for;

        return $this;
    }

    /**
     * Gets start_preset
     *
     * @return string|null
     */
    public function getStartPreset()
    {
        return $this->container['start_preset'];
    }

    /**
     * Sets start_preset
     *
     * @param string|null $start_preset Set by a launch with `preset`: the zone switches on with this preset when the network approves the campaign, then it is cleared. An unknown value is dropped.
     *
     * @return self
     */
    public function setStartPreset($start_preset)
    {
        if (is_null($start_preset)) {
            throw new \InvalidArgumentException('non-nullable start_preset cannot be null');
        }
        $allowedValues = $this->getStartPresetAllowableValues();
        if (!in_array($start_preset, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'start_preset', must be one of '%s'",
                    $start_preset,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['start_preset'] = $start_preset;

        return $this;
    }

    /**
     * Gets test_phase
     *
     * @return \GoAdServerApi\Model\MediaBuyingPolicyTestPhase|null
     */
    public function getTestPhase()
    {
        return $this->container['test_phase'];
    }

    /**
     * Sets test_phase
     *
     * @param \GoAdServerApi\Model\MediaBuyingPolicyTestPhase|null $test_phase test_phase
     *
     * @return self
     */
    public function setTestPhase($test_phase)
    {
        if (is_null($test_phase)) {
            throw new \InvalidArgumentException('non-nullable test_phase cannot be null');
        }
        $this->container['test_phase'] = $test_phase;

        return $this;
    }

    /**
     * Gets budget_mode
     *
     * @return string|null
     */
    public function getBudgetMode()
    {
        return $this->container['budget_mode'];
    }

    /**
     * Sets budget_mode
     *
     * @param string|null $budget_mode Network daily budget (level E): off | advise | auto. Decided once a day; written only where the network has a budget API.
     *
     * @return self
     */
    public function setBudgetMode($budget_mode)
    {
        if (is_null($budget_mode)) {
            throw new \InvalidArgumentException('non-nullable budget_mode cannot be null');
        }
        $this->container['budget_mode'] = $budget_mode;

        return $this;
    }

    /**
     * Gets budget_headroom_pct
     *
     * @return float|null
     */
    public function getBudgetHeadroomPct()
    {
        return $this->container['budget_headroom_pct'];
    }

    /**
     * Sets budget_headroom_pct
     *
     * @param float|null $budget_headroom_pct Added on top of the computed network budget, 0-100 %
     *
     * @return self
     */
    public function setBudgetHeadroomPct($budget_headroom_pct)
    {
        if (is_null($budget_headroom_pct)) {
            throw new \InvalidArgumentException('non-nullable budget_headroom_pct cannot be null');
        }
        $this->container['budget_headroom_pct'] = $budget_headroom_pct;

        return $this;
    }

    /**
     * Gets min_network_budget
     *
     * @return float|null
     */
    public function getMinNetworkBudget()
    {
        return $this->container['min_network_budget'];
    }

    /**
     * Sets min_network_budget
     *
     * @param float|null $min_network_budget Never set the network budget below this (network currency); null = no floor
     *
     * @return self
     */
    public function setMinNetworkBudget($min_network_budget)
    {
        if (is_null($min_network_budget)) {
            throw new \InvalidArgumentException('non-nullable min_network_budget cannot be null');
        }
        $this->container['min_network_budget'] = $min_network_budget;

        return $this;
    }

    /**
     * Gets max_network_budget
     *
     * @return float|null
     */
    public function getMaxNetworkBudget()
    {
        return $this->container['max_network_budget'];
    }

    /**
     * Sets max_network_budget
     *
     * @param float|null $max_network_budget Never set the network budget above this (network currency); null = no ceiling
     *
     * @return self
     */
    public function setMaxNetworkBudget($max_network_budget)
    {
        if (is_null($max_network_budget)) {
            throw new \InvalidArgumentException('non-nullable max_network_budget cannot be null');
        }
        $this->container['max_network_budget'] = $max_network_budget;

        return $this;
    }

    /**
     * Gets budget_pace_hours
     *
     * @return int|null
     */
    public function getBudgetPaceHours()
    {
        return $this->container['budget_pace_hours'];
    }

    /**
     * Sets budget_pace_hours
     *
     * @param int|null $budget_pace_hours Even pacing: spread the day budget over at least this many hours — the network's hourly budget is set to day budget / hours, in the same update as the daily budget (networks with `supports_hourly_budget` in the overview, today Adsterra). 0 = off (default), else 4-24. Switching it off leaves the network's hourly budget as it is.
     *
     * @return self
     */
    public function setBudgetPaceHours($budget_pace_hours)
    {
        if (is_null($budget_pace_hours)) {
            throw new \InvalidArgumentException('non-nullable budget_pace_hours cannot be null');
        }
        $this->container['budget_pace_hours'] = $budget_pace_hours;

        return $this;
    }

    /**
     * Gets segment_mode
     *
     * @return string|null
     */
    public function getSegmentMode()
    {
        return $this->container['segment_mode'];
    }

    /**
     * Sets segment_mode
     *
     * @param string|null $segment_mode Segment exclusions (levels B/C): off | advise | auto
     *
     * @return self
     */
    public function setSegmentMode($segment_mode)
    {
        if (is_null($segment_mode)) {
            throw new \InvalidArgumentException('non-nullable segment_mode cannot be null');
        }
        $this->container['segment_mode'] = $segment_mode;

        return $this;
    }

    /**
     * Gets segment_min_spend
     *
     * @return float|null
     */
    public function getSegmentMinSpend()
    {
        return $this->container['segment_min_spend'];
    }

    /**
     * Sets segment_min_spend
     *
     * @param float|null $segment_min_spend Judge a segment only after this cost (main currency); estimated segments need twice this
     *
     * @return self
     */
    public function setSegmentMinSpend($segment_min_spend)
    {
        if (is_null($segment_min_spend)) {
            throw new \InvalidArgumentException('non-nullable segment_min_spend cannot be null');
        }
        $this->container['segment_min_spend'] = $segment_min_spend;

        return $this;
    }

    /**
     * Gets segment_window_days
     *
     * @return int|null
     */
    public function getSegmentWindowDays()
    {
        return $this->container['segment_window_days'];
    }

    /**
     * Sets segment_window_days
     *
     * @param int|null $segment_window_days Full days a segment is judged on, 3-28 (weekday: always 28)
     *
     * @return self
     */
    public function setSegmentWindowDays($segment_window_days)
    {
        if (is_null($segment_window_days)) {
            throw new \InvalidArgumentException('non-nullable segment_window_days cannot be null');
        }
        $this->container['segment_window_days'] = $segment_window_days;

        return $this;
    }

    /**
     * Gets mirror_advertiser_blocks
     *
     * @return bool|null
     */
    public function getMirrorAdvertiserBlocks()
    {
        return $this->container['mirror_advertiser_blocks'];
    }

    /**
     * Sets mirror_advertiser_blocks
     *
     * @param bool|null $mirror_advertiser_blocks When the zone feeds exactly one campaign and its source ids arrive as subid: subids that campaign's advertiser blocks are blocked at the network too.
     *
     * @return self
     */
    public function setMirrorAdvertiserBlocks($mirror_advertiser_blocks)
    {
        if (is_null($mirror_advertiser_blocks)) {
            throw new \InvalidArgumentException('non-nullable mirror_advertiser_blocks cannot be null');
        }
        $this->container['mirror_advertiser_blocks'] = $mirror_advertiser_blocks;

        return $this;
    }

    /**
     * Gets zone_bid_mode
     *
     * @return string|null
     */
    public function getZoneBidMode()
    {
        return $this->container['zone_bid_mode'];
    }

    /**
     * Sets zone_bid_mode
     *
     * @param string|null $zone_bid_mode off | advise | auto
     *
     * @return self
     */
    public function setZoneBidMode($zone_bid_mode)
    {
        if (is_null($zone_bid_mode)) {
            throw new \InvalidArgumentException('non-nullable zone_bid_mode cannot be null');
        }
        $this->container['zone_bid_mode'] = $zone_bid_mode;

        return $this;
    }

    /**
     * Gets zone_bid_step_pct
     *
     * @return float|null
     */
    public function getZoneBidStepPct()
    {
        return $this->container['zone_bid_step_pct'];
    }

    /**
     * Sets zone_bid_step_pct
     *
     * @param float|null $zone_bid_step_pct Size of one change, 1-30 %
     *
     * @return self
     */
    public function setZoneBidStepPct($zone_bid_step_pct)
    {
        if (is_null($zone_bid_step_pct)) {
            throw new \InvalidArgumentException('non-nullable zone_bid_step_pct cannot be null');
        }
        $this->container['zone_bid_step_pct'] = $zone_bid_step_pct;

        return $this;
    }

    /**
     * Gets zone_bid_band_pct
     *
     * @return float|null
     */
    public function getZoneBidBandPct()
    {
        return $this->container['zone_bid_band_pct'];
    }

    /**
     * Sets zone_bid_band_pct
     *
     * @param float|null $zone_bid_band_pct Margin band above the target, in points
     *
     * @return self
     */
    public function setZoneBidBandPct($zone_bid_band_pct)
    {
        if (is_null($zone_bid_band_pct)) {
            throw new \InvalidArgumentException('non-nullable zone_bid_band_pct cannot be null');
        }
        $this->container['zone_bid_band_pct'] = $zone_bid_band_pct;

        return $this;
    }

    /**
     * Gets zone_bid_floor_pct
     *
     * @return float|null
     */
    public function getZoneBidFloorPct()
    {
        return $this->container['zone_bid_floor_pct'];
    }

    /**
     * Sets zone_bid_floor_pct
     *
     * @param float|null $zone_bid_floor_pct Never below this % of the country base bid
     *
     * @return self
     */
    public function setZoneBidFloorPct($zone_bid_floor_pct)
    {
        if (is_null($zone_bid_floor_pct)) {
            throw new \InvalidArgumentException('non-nullable zone_bid_floor_pct cannot be null');
        }
        $this->container['zone_bid_floor_pct'] = $zone_bid_floor_pct;

        return $this;
    }

    /**
     * Gets zone_bid_ceiling_pct
     *
     * @return float|null
     */
    public function getZoneBidCeilingPct()
    {
        return $this->container['zone_bid_ceiling_pct'];
    }

    /**
     * Sets zone_bid_ceiling_pct
     *
     * @param float|null $zone_bid_ceiling_pct Never above this % of the country base bid
     *
     * @return self
     */
    public function setZoneBidCeilingPct($zone_bid_ceiling_pct)
    {
        if (is_null($zone_bid_ceiling_pct)) {
            throw new \InvalidArgumentException('non-nullable zone_bid_ceiling_pct cannot be null');
        }
        $this->container['zone_bid_ceiling_pct'] = $zone_bid_ceiling_pct;

        return $this;
    }

    /**
     * Gets inherit_blocks
     *
     * @return object|null
     */
    public function getInheritBlocks()
    {
        return $this->container['inherit_blocks'];
    }

    /**
     * Sets inherit_blocks
     *
     * @param object|null $inherit_blocks Set by an ad zone clone: a pending carry-over of another zone's blocks (`from`, `kind`, `done`). Read-only; send it back unchanged or leave it out.
     *
     * @return self
     */
    public function setInheritBlocks($inherit_blocks)
    {
        if (is_null($inherit_blocks)) {
            throw new \InvalidArgumentException('non-nullable inherit_blocks cannot be null');
        }
        $this->container['inherit_blocks'] = $inherit_blocks;

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



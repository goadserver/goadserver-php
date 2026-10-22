<?php
/**
 * CampaignPanelFields
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
 * CampaignPanelFields Class Doc Comment
 *
 * @category Class
 * @description Campaign-form fields for &#x60;ron&#x60; / &#x60;cpm&#x60; / &#x60;rtb&#x60; campaigns, writable on &#x60;POST /api/v1/campaigns/{type}&#x60; and &#x60;PATCH /api/v1/campaigns/{type}/{id}&#x60; and returned by list / show (option fields when stored). Which of them a campaign takes depends on its type, the network&#39;s modules, its ad type and its pay type — &#x60;GET /api/v1/campaigns/{type}/_schema&#x60; lists exactly the ones available to you (&#x60;?ad_type&#x3D;&#x60; narrows it), with &#x60;ad_types&#x60; / &#x60;selling_types&#x60; restrictions. Sending one that is not available is a &#x60;422&#x60; naming the field (for example &#x60;\&quot;orientation applies only to ad type(s) 3, 22.\&quot;&#x60;), not a silent drop. Object fields merge on PATCH: keys you omit keep their stored value. Money is in main currency units (not cents).
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class CampaignPanelFields implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'CampaignPanelFields';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'groupid' => 'string',
        'display_options' => 'string',
        'min_rank' => 'int',
        'fx_rating' => 'int',
        'mail_campaign_budget' => 'bool',
        'use_dynamic_costs' => 'bool',
        'dynamic_costs_increase' => 'int',
        'conversion_capping' => '\GoAdServerApi\Model\CampaignPanelFieldsConversionCapping',
        'fix_cpa' => 'float',
        'fix_cpa_conv_types' => 'string',
        'booster' => 'bool',
        'booster_settings' => '\GoAdServerApi\Model\CampaignPanelFieldsBoosterSettings',
        'default_bid_type' => 'int',
        'sort_type' => 'int',
        'as_failover' => 'bool',
        'day_parting' => '\GoAdServerApi\Model\CampaignPanelFieldsDayParting',
        'screens' => '\GoAdServerApi\Model\CampaignPanelFieldsScreens',
        'display_times' => 'string',
        'display_days' => 'string',
        'site_languages' => 'string',
        'connection_types' => 'string',
        'carrier_ids' => 'string',
        'rating_id' => 'int',
        'orientation' => 'string',
        'site_mode' => 'int',
        'only_tagged_zones' => 'bool',
        'skip_low_quality' => 'int',
        'ip_whitelist' => 'string',
        'ip_blacklist' => 'string',
        'av_check' => '\GoAdServerApi\Model\CampaignPanelFieldsAvCheck',
        'email_type' => 'int',
        'use_subscription_age' => 'bool',
        'subscription_age' => 'int',
        'instant_impression' => 'bool',
        'subid_list' => 'string',
        'subid_list_mode' => 'string',
        'source_optimizer_ids' => 'string',
        'wl_zone_ids' => 'string',
        'feed_id' => 'int',
        'feed_settings' => '\GoAdServerApi\Model\CampaignPanelFieldsFeedSettings',
        'priority' => '\GoAdServerApi\Model\CampaignPanelFieldsPriority',
        'suggestions' => '\GoAdServerApi\Model\CampaignPanelFieldsSuggestions',
        'hide_referrer' => 'bool',
        'log_conv_as_paid' => 'bool',
        'about_settings' => '\GoAdServerApi\Model\CampaignPanelFieldsAboutSettings',
        'use_live_feed' => 'bool',
        'live_feed_parent_id' => 'int',
        'live_feed_settings' => 'array<string,mixed>',
        'offer_type_id' => 'int',
        'description' => 'string',
        'destination_url' => 'string',
        'preview_url' => 'string',
        'rtb_network_id' => 'int',
        'rtb_type' => 'string',
        'use_type' => 'int',
        'zone_urls' => 'object[]',
        'bid_action' => 'int',
        'use_qps' => 'bool',
        'qps' => 'int',
        'rtb_categories' => 'string',
        'use_api' => 'bool',
        'api_data' => 'array<string,mixed>',
        'log_skipped' => 'bool',
        'domain_mask' => '\GoAdServerApi\Model\CampaignPanelFieldsDomainMask',
        'rtb_xml_markup' => 'bool'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'groupid' => null,
        'display_options' => null,
        'min_rank' => null,
        'fx_rating' => null,
        'mail_campaign_budget' => null,
        'use_dynamic_costs' => null,
        'dynamic_costs_increase' => null,
        'conversion_capping' => null,
        'fix_cpa' => null,
        'fix_cpa_conv_types' => null,
        'booster' => null,
        'booster_settings' => null,
        'default_bid_type' => null,
        'sort_type' => null,
        'as_failover' => null,
        'day_parting' => null,
        'screens' => null,
        'display_times' => null,
        'display_days' => null,
        'site_languages' => null,
        'connection_types' => null,
        'carrier_ids' => null,
        'rating_id' => null,
        'orientation' => null,
        'site_mode' => null,
        'only_tagged_zones' => null,
        'skip_low_quality' => null,
        'ip_whitelist' => null,
        'ip_blacklist' => null,
        'av_check' => null,
        'email_type' => null,
        'use_subscription_age' => null,
        'subscription_age' => null,
        'instant_impression' => null,
        'subid_list' => null,
        'subid_list_mode' => null,
        'source_optimizer_ids' => null,
        'wl_zone_ids' => null,
        'feed_id' => null,
        'feed_settings' => null,
        'priority' => null,
        'suggestions' => null,
        'hide_referrer' => null,
        'log_conv_as_paid' => null,
        'about_settings' => null,
        'use_live_feed' => null,
        'live_feed_parent_id' => null,
        'live_feed_settings' => null,
        'offer_type_id' => null,
        'description' => null,
        'destination_url' => null,
        'preview_url' => null,
        'rtb_network_id' => null,
        'rtb_type' => null,
        'use_type' => null,
        'zone_urls' => null,
        'bid_action' => null,
        'use_qps' => null,
        'qps' => null,
        'rtb_categories' => null,
        'use_api' => null,
        'api_data' => null,
        'log_skipped' => null,
        'domain_mask' => null,
        'rtb_xml_markup' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'groupid' => false,
        'display_options' => false,
        'min_rank' => false,
        'fx_rating' => false,
        'mail_campaign_budget' => false,
        'use_dynamic_costs' => false,
        'dynamic_costs_increase' => false,
        'conversion_capping' => false,
        'fix_cpa' => false,
        'fix_cpa_conv_types' => false,
        'booster' => false,
        'booster_settings' => false,
        'default_bid_type' => false,
        'sort_type' => false,
        'as_failover' => false,
        'day_parting' => false,
        'screens' => false,
        'display_times' => false,
        'display_days' => false,
        'site_languages' => false,
        'connection_types' => false,
        'carrier_ids' => false,
        'rating_id' => false,
        'orientation' => false,
        'site_mode' => false,
        'only_tagged_zones' => false,
        'skip_low_quality' => false,
        'ip_whitelist' => false,
        'ip_blacklist' => false,
        'av_check' => false,
        'email_type' => false,
        'use_subscription_age' => false,
        'subscription_age' => false,
        'instant_impression' => false,
        'subid_list' => false,
        'subid_list_mode' => false,
        'source_optimizer_ids' => false,
        'wl_zone_ids' => false,
        'feed_id' => false,
        'feed_settings' => false,
        'priority' => false,
        'suggestions' => false,
        'hide_referrer' => false,
        'log_conv_as_paid' => false,
        'about_settings' => false,
        'use_live_feed' => false,
        'live_feed_parent_id' => false,
        'live_feed_settings' => false,
        'offer_type_id' => false,
        'description' => false,
        'destination_url' => false,
        'preview_url' => false,
        'rtb_network_id' => false,
        'rtb_type' => false,
        'use_type' => false,
        'zone_urls' => false,
        'bid_action' => false,
        'use_qps' => false,
        'qps' => false,
        'rtb_categories' => false,
        'use_api' => false,
        'api_data' => false,
        'log_skipped' => false,
        'domain_mask' => false,
        'rtb_xml_markup' => false
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
        'groupid' => 'groupid',
        'display_options' => 'display_options',
        'min_rank' => 'min_rank',
        'fx_rating' => 'fx_rating',
        'mail_campaign_budget' => 'mail_campaign_budget',
        'use_dynamic_costs' => 'use_dynamic_costs',
        'dynamic_costs_increase' => 'dynamic_costs_increase',
        'conversion_capping' => 'conversion_capping',
        'fix_cpa' => 'fix_cpa',
        'fix_cpa_conv_types' => 'fix_cpa_conv_types',
        'booster' => 'booster',
        'booster_settings' => 'booster_settings',
        'default_bid_type' => 'default_bid_type',
        'sort_type' => 'sort_type',
        'as_failover' => 'as_failover',
        'day_parting' => 'day_parting',
        'screens' => 'screens',
        'display_times' => 'display_times',
        'display_days' => 'display_days',
        'site_languages' => 'site_languages',
        'connection_types' => 'connection_types',
        'carrier_ids' => 'carrier_ids',
        'rating_id' => 'rating_id',
        'orientation' => 'orientation',
        'site_mode' => 'site_mode',
        'only_tagged_zones' => 'only_tagged_zones',
        'skip_low_quality' => 'skip_low_quality',
        'ip_whitelist' => 'ip_whitelist',
        'ip_blacklist' => 'ip_blacklist',
        'av_check' => 'av_check',
        'email_type' => 'email_type',
        'use_subscription_age' => 'use_subscription_age',
        'subscription_age' => 'subscription_age',
        'instant_impression' => 'instant_impression',
        'subid_list' => 'subid_list',
        'subid_list_mode' => 'subid_list_mode',
        'source_optimizer_ids' => 'source_optimizer_ids',
        'wl_zone_ids' => 'wl_zone_ids',
        'feed_id' => 'feed_id',
        'feed_settings' => 'feed_settings',
        'priority' => 'priority',
        'suggestions' => 'suggestions',
        'hide_referrer' => 'hide_referrer',
        'log_conv_as_paid' => 'log_conv_as_paid',
        'about_settings' => 'about_settings',
        'use_live_feed' => 'use_live_feed',
        'live_feed_parent_id' => 'live_feed_parent_id',
        'live_feed_settings' => 'live_feed_settings',
        'offer_type_id' => 'offer_type_id',
        'description' => 'description',
        'destination_url' => 'destination_url',
        'preview_url' => 'preview_url',
        'rtb_network_id' => 'rtb_network_id',
        'rtb_type' => 'rtb_type',
        'use_type' => 'use_type',
        'zone_urls' => 'zone_urls',
        'bid_action' => 'bid_action',
        'use_qps' => 'use_qps',
        'qps' => 'qps',
        'rtb_categories' => 'rtb_categories',
        'use_api' => 'use_api',
        'api_data' => 'api_data',
        'log_skipped' => 'log_skipped',
        'domain_mask' => 'domain_mask',
        'rtb_xml_markup' => 'rtb_xml_markup'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'groupid' => 'setGroupid',
        'display_options' => 'setDisplayOptions',
        'min_rank' => 'setMinRank',
        'fx_rating' => 'setFxRating',
        'mail_campaign_budget' => 'setMailCampaignBudget',
        'use_dynamic_costs' => 'setUseDynamicCosts',
        'dynamic_costs_increase' => 'setDynamicCostsIncrease',
        'conversion_capping' => 'setConversionCapping',
        'fix_cpa' => 'setFixCpa',
        'fix_cpa_conv_types' => 'setFixCpaConvTypes',
        'booster' => 'setBooster',
        'booster_settings' => 'setBoosterSettings',
        'default_bid_type' => 'setDefaultBidType',
        'sort_type' => 'setSortType',
        'as_failover' => 'setAsFailover',
        'day_parting' => 'setDayParting',
        'screens' => 'setScreens',
        'display_times' => 'setDisplayTimes',
        'display_days' => 'setDisplayDays',
        'site_languages' => 'setSiteLanguages',
        'connection_types' => 'setConnectionTypes',
        'carrier_ids' => 'setCarrierIds',
        'rating_id' => 'setRatingId',
        'orientation' => 'setOrientation',
        'site_mode' => 'setSiteMode',
        'only_tagged_zones' => 'setOnlyTaggedZones',
        'skip_low_quality' => 'setSkipLowQuality',
        'ip_whitelist' => 'setIpWhitelist',
        'ip_blacklist' => 'setIpBlacklist',
        'av_check' => 'setAvCheck',
        'email_type' => 'setEmailType',
        'use_subscription_age' => 'setUseSubscriptionAge',
        'subscription_age' => 'setSubscriptionAge',
        'instant_impression' => 'setInstantImpression',
        'subid_list' => 'setSubidList',
        'subid_list_mode' => 'setSubidListMode',
        'source_optimizer_ids' => 'setSourceOptimizerIds',
        'wl_zone_ids' => 'setWlZoneIds',
        'feed_id' => 'setFeedId',
        'feed_settings' => 'setFeedSettings',
        'priority' => 'setPriority',
        'suggestions' => 'setSuggestions',
        'hide_referrer' => 'setHideReferrer',
        'log_conv_as_paid' => 'setLogConvAsPaid',
        'about_settings' => 'setAboutSettings',
        'use_live_feed' => 'setUseLiveFeed',
        'live_feed_parent_id' => 'setLiveFeedParentId',
        'live_feed_settings' => 'setLiveFeedSettings',
        'offer_type_id' => 'setOfferTypeId',
        'description' => 'setDescription',
        'destination_url' => 'setDestinationUrl',
        'preview_url' => 'setPreviewUrl',
        'rtb_network_id' => 'setRtbNetworkId',
        'rtb_type' => 'setRtbType',
        'use_type' => 'setUseType',
        'zone_urls' => 'setZoneUrls',
        'bid_action' => 'setBidAction',
        'use_qps' => 'setUseQps',
        'qps' => 'setQps',
        'rtb_categories' => 'setRtbCategories',
        'use_api' => 'setUseApi',
        'api_data' => 'setApiData',
        'log_skipped' => 'setLogSkipped',
        'domain_mask' => 'setDomainMask',
        'rtb_xml_markup' => 'setRtbXmlMarkup'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'groupid' => 'getGroupid',
        'display_options' => 'getDisplayOptions',
        'min_rank' => 'getMinRank',
        'fx_rating' => 'getFxRating',
        'mail_campaign_budget' => 'getMailCampaignBudget',
        'use_dynamic_costs' => 'getUseDynamicCosts',
        'dynamic_costs_increase' => 'getDynamicCostsIncrease',
        'conversion_capping' => 'getConversionCapping',
        'fix_cpa' => 'getFixCpa',
        'fix_cpa_conv_types' => 'getFixCpaConvTypes',
        'booster' => 'getBooster',
        'booster_settings' => 'getBoosterSettings',
        'default_bid_type' => 'getDefaultBidType',
        'sort_type' => 'getSortType',
        'as_failover' => 'getAsFailover',
        'day_parting' => 'getDayParting',
        'screens' => 'getScreens',
        'display_times' => 'getDisplayTimes',
        'display_days' => 'getDisplayDays',
        'site_languages' => 'getSiteLanguages',
        'connection_types' => 'getConnectionTypes',
        'carrier_ids' => 'getCarrierIds',
        'rating_id' => 'getRatingId',
        'orientation' => 'getOrientation',
        'site_mode' => 'getSiteMode',
        'only_tagged_zones' => 'getOnlyTaggedZones',
        'skip_low_quality' => 'getSkipLowQuality',
        'ip_whitelist' => 'getIpWhitelist',
        'ip_blacklist' => 'getIpBlacklist',
        'av_check' => 'getAvCheck',
        'email_type' => 'getEmailType',
        'use_subscription_age' => 'getUseSubscriptionAge',
        'subscription_age' => 'getSubscriptionAge',
        'instant_impression' => 'getInstantImpression',
        'subid_list' => 'getSubidList',
        'subid_list_mode' => 'getSubidListMode',
        'source_optimizer_ids' => 'getSourceOptimizerIds',
        'wl_zone_ids' => 'getWlZoneIds',
        'feed_id' => 'getFeedId',
        'feed_settings' => 'getFeedSettings',
        'priority' => 'getPriority',
        'suggestions' => 'getSuggestions',
        'hide_referrer' => 'getHideReferrer',
        'log_conv_as_paid' => 'getLogConvAsPaid',
        'about_settings' => 'getAboutSettings',
        'use_live_feed' => 'getUseLiveFeed',
        'live_feed_parent_id' => 'getLiveFeedParentId',
        'live_feed_settings' => 'getLiveFeedSettings',
        'offer_type_id' => 'getOfferTypeId',
        'description' => 'getDescription',
        'destination_url' => 'getDestinationUrl',
        'preview_url' => 'getPreviewUrl',
        'rtb_network_id' => 'getRtbNetworkId',
        'rtb_type' => 'getRtbType',
        'use_type' => 'getUseType',
        'zone_urls' => 'getZoneUrls',
        'bid_action' => 'getBidAction',
        'use_qps' => 'getUseQps',
        'qps' => 'getQps',
        'rtb_categories' => 'getRtbCategories',
        'use_api' => 'getUseApi',
        'api_data' => 'getApiData',
        'log_skipped' => 'getLogSkipped',
        'domain_mask' => 'getDomainMask',
        'rtb_xml_markup' => 'getRtbXmlMarkup'
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

    public const DEFAULT_BID_TYPE_NUMBER_0 = 0;
    public const DEFAULT_BID_TYPE_NUMBER_1 = 1;
    public const ORIENTATION_LANDSCAPE = 'landscape';
    public const ORIENTATION_PORTRAIT = 'portrait';
    public const ORIENTATION_SQUARE = 'square';
    public const SITE_MODE_NUMBER_0 = 0;
    public const SITE_MODE_NUMBER_1 = 1;
    public const SKIP_LOW_QUALITY_NUMBER_0 = 0;
    public const SKIP_LOW_QUALITY_NUMBER_1 = 1;
    public const SKIP_LOW_QUALITY_NUMBER_2 = 2;
    public const EMAIL_TYPE_NUMBER_0 = 0;
    public const EMAIL_TYPE_NUMBER_1 = 1;
    public const EMAIL_TYPE_NUMBER_2 = 2;
    public const SUBID_LIST_MODE_WHITELIST = 'whitelist';
    public const SUBID_LIST_MODE_BLACKLIST = 'blacklist';
    public const RTB_TYPE_OPENRTB = 'openrtb';
    public const RTB_TYPE_JSON = 'json';
    public const RTB_TYPE_XML = 'xml';
    public const USE_TYPE_NUMBER_0 = 0;
    public const USE_TYPE_NUMBER_1 = 1;
    public const BID_ACTION_NUMBER_1 = 1;
    public const BID_ACTION_NUMBER_2 = 2;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getDefaultBidTypeAllowableValues()
    {
        return [
            self::DEFAULT_BID_TYPE_NUMBER_0,
            self::DEFAULT_BID_TYPE_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getOrientationAllowableValues()
    {
        return [
            self::ORIENTATION_LANDSCAPE,
            self::ORIENTATION_PORTRAIT,
            self::ORIENTATION_SQUARE,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSiteModeAllowableValues()
    {
        return [
            self::SITE_MODE_NUMBER_0,
            self::SITE_MODE_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSkipLowQualityAllowableValues()
    {
        return [
            self::SKIP_LOW_QUALITY_NUMBER_0,
            self::SKIP_LOW_QUALITY_NUMBER_1,
            self::SKIP_LOW_QUALITY_NUMBER_2,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getEmailTypeAllowableValues()
    {
        return [
            self::EMAIL_TYPE_NUMBER_0,
            self::EMAIL_TYPE_NUMBER_1,
            self::EMAIL_TYPE_NUMBER_2,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSubidListModeAllowableValues()
    {
        return [
            self::SUBID_LIST_MODE_WHITELIST,
            self::SUBID_LIST_MODE_BLACKLIST,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getRtbTypeAllowableValues()
    {
        return [
            self::RTB_TYPE_OPENRTB,
            self::RTB_TYPE_JSON,
            self::RTB_TYPE_XML,
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
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getBidActionAllowableValues()
    {
        return [
            self::BID_ACTION_NUMBER_1,
            self::BID_ACTION_NUMBER_2,
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
        $this->setIfExists('groupid', $data ?? [], null);
        $this->setIfExists('display_options', $data ?? [], null);
        $this->setIfExists('min_rank', $data ?? [], null);
        $this->setIfExists('fx_rating', $data ?? [], null);
        $this->setIfExists('mail_campaign_budget', $data ?? [], null);
        $this->setIfExists('use_dynamic_costs', $data ?? [], null);
        $this->setIfExists('dynamic_costs_increase', $data ?? [], null);
        $this->setIfExists('conversion_capping', $data ?? [], null);
        $this->setIfExists('fix_cpa', $data ?? [], null);
        $this->setIfExists('fix_cpa_conv_types', $data ?? [], null);
        $this->setIfExists('booster', $data ?? [], null);
        $this->setIfExists('booster_settings', $data ?? [], null);
        $this->setIfExists('default_bid_type', $data ?? [], null);
        $this->setIfExists('sort_type', $data ?? [], null);
        $this->setIfExists('as_failover', $data ?? [], null);
        $this->setIfExists('day_parting', $data ?? [], null);
        $this->setIfExists('screens', $data ?? [], null);
        $this->setIfExists('display_times', $data ?? [], null);
        $this->setIfExists('display_days', $data ?? [], null);
        $this->setIfExists('site_languages', $data ?? [], null);
        $this->setIfExists('connection_types', $data ?? [], null);
        $this->setIfExists('carrier_ids', $data ?? [], null);
        $this->setIfExists('rating_id', $data ?? [], null);
        $this->setIfExists('orientation', $data ?? [], null);
        $this->setIfExists('site_mode', $data ?? [], null);
        $this->setIfExists('only_tagged_zones', $data ?? [], null);
        $this->setIfExists('skip_low_quality', $data ?? [], null);
        $this->setIfExists('ip_whitelist', $data ?? [], null);
        $this->setIfExists('ip_blacklist', $data ?? [], null);
        $this->setIfExists('av_check', $data ?? [], null);
        $this->setIfExists('email_type', $data ?? [], null);
        $this->setIfExists('use_subscription_age', $data ?? [], null);
        $this->setIfExists('subscription_age', $data ?? [], null);
        $this->setIfExists('instant_impression', $data ?? [], null);
        $this->setIfExists('subid_list', $data ?? [], null);
        $this->setIfExists('subid_list_mode', $data ?? [], null);
        $this->setIfExists('source_optimizer_ids', $data ?? [], null);
        $this->setIfExists('wl_zone_ids', $data ?? [], null);
        $this->setIfExists('feed_id', $data ?? [], null);
        $this->setIfExists('feed_settings', $data ?? [], null);
        $this->setIfExists('priority', $data ?? [], null);
        $this->setIfExists('suggestions', $data ?? [], null);
        $this->setIfExists('hide_referrer', $data ?? [], null);
        $this->setIfExists('log_conv_as_paid', $data ?? [], null);
        $this->setIfExists('about_settings', $data ?? [], null);
        $this->setIfExists('use_live_feed', $data ?? [], null);
        $this->setIfExists('live_feed_parent_id', $data ?? [], null);
        $this->setIfExists('live_feed_settings', $data ?? [], null);
        $this->setIfExists('offer_type_id', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('destination_url', $data ?? [], null);
        $this->setIfExists('preview_url', $data ?? [], null);
        $this->setIfExists('rtb_network_id', $data ?? [], null);
        $this->setIfExists('rtb_type', $data ?? [], null);
        $this->setIfExists('use_type', $data ?? [], null);
        $this->setIfExists('zone_urls', $data ?? [], null);
        $this->setIfExists('bid_action', $data ?? [], null);
        $this->setIfExists('use_qps', $data ?? [], null);
        $this->setIfExists('qps', $data ?? [], null);
        $this->setIfExists('rtb_categories', $data ?? [], null);
        $this->setIfExists('use_api', $data ?? [], null);
        $this->setIfExists('api_data', $data ?? [], null);
        $this->setIfExists('log_skipped', $data ?? [], null);
        $this->setIfExists('domain_mask', $data ?? [], null);
        $this->setIfExists('rtb_xml_markup', $data ?? [], null);
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

        if (!is_null($this->container['dynamic_costs_increase']) && ($this->container['dynamic_costs_increase'] > 100)) {
            $invalidProperties[] = "invalid value for 'dynamic_costs_increase', must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['dynamic_costs_increase']) && ($this->container['dynamic_costs_increase'] < 0)) {
            $invalidProperties[] = "invalid value for 'dynamic_costs_increase', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['fix_cpa']) && ($this->container['fix_cpa'] < 0)) {
            $invalidProperties[] = "invalid value for 'fix_cpa', must be bigger than or equal to 0.";
        }

        $allowedValues = $this->getDefaultBidTypeAllowableValues();
        if (!is_null($this->container['default_bid_type']) && !in_array($this->container['default_bid_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'default_bid_type', must be one of '%s'",
                $this->container['default_bid_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getOrientationAllowableValues();
        if (!is_null($this->container['orientation']) && !in_array($this->container['orientation'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'orientation', must be one of '%s'",
                $this->container['orientation'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getSiteModeAllowableValues();
        if (!is_null($this->container['site_mode']) && !in_array($this->container['site_mode'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'site_mode', must be one of '%s'",
                $this->container['site_mode'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getSkipLowQualityAllowableValues();
        if (!is_null($this->container['skip_low_quality']) && !in_array($this->container['skip_low_quality'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'skip_low_quality', must be one of '%s'",
                $this->container['skip_low_quality'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getEmailTypeAllowableValues();
        if (!is_null($this->container['email_type']) && !in_array($this->container['email_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'email_type', must be one of '%s'",
                $this->container['email_type'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['subscription_age']) && ($this->container['subscription_age'] < 0)) {
            $invalidProperties[] = "invalid value for 'subscription_age', must be bigger than or equal to 0.";
        }

        $allowedValues = $this->getSubidListModeAllowableValues();
        if (!is_null($this->container['subid_list_mode']) && !in_array($this->container['subid_list_mode'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'subid_list_mode', must be one of '%s'",
                $this->container['subid_list_mode'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getRtbTypeAllowableValues();
        if (!is_null($this->container['rtb_type']) && !in_array($this->container['rtb_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'rtb_type', must be one of '%s'",
                $this->container['rtb_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getUseTypeAllowableValues();
        if (!is_null($this->container['use_type']) && !in_array($this->container['use_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'use_type', must be one of '%s'",
                $this->container['use_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getBidActionAllowableValues();
        if (!is_null($this->container['bid_action']) && !in_array($this->container['bid_action'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'bid_action', must be one of '%s'",
                $this->container['bid_action'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['qps']) && ($this->container['qps'] < 0)) {
            $invalidProperties[] = "invalid value for 'qps', must be bigger than or equal to 0.";
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
     * Gets groupid
     *
     * @return string|null
     */
    public function getGroupid()
    {
        return $this->container['groupid'];
    }

    /**
     * Sets groupid
     *
     * @param string|null $groupid Campaign tags / groups: comma-separated group ids (\"0\" = none). A JSON array is accepted.
     *
     * @return self
     */
    public function setGroupid($groupid)
    {
        if (is_null($groupid)) {
            throw new \InvalidArgumentException('non-nullable groupid cannot be null');
        }
        $this->container['groupid'] = $groupid;

        return $this;
    }

    /**
     * Gets display_options
     *
     * @return string|null
     */
    public function getDisplayOptions()
    {
        return $this->container['display_options'];
    }

    /**
     * Sets display_options
     *
     * @param string|null $display_options Display options for the ad type (e.g. video roll position), comma-separated ids.
     *
     * @return self
     */
    public function setDisplayOptions($display_options)
    {
        if (is_null($display_options)) {
            throw new \InvalidArgumentException('non-nullable display_options cannot be null');
        }
        $this->container['display_options'] = $display_options;

        return $this;
    }

    /**
     * Gets min_rank
     *
     * @return int|null
     */
    public function getMinRank()
    {
        return $this->container['min_rank'];
    }

    /**
     * Sets min_rank
     *
     * @param int|null $min_rank Minimum ad zone quality rank (flat campaigns on networks that rank ad zones). Not the same as `priority`.
     *
     * @return self
     */
    public function setMinRank($min_rank)
    {
        if (is_null($min_rank)) {
            throw new \InvalidArgumentException('non-nullable min_rank cannot be null');
        }
        $this->container['min_rank'] = $min_rank;

        return $this;
    }

    /**
     * Gets fx_rating
     *
     * @return int|null
     */
    public function getFxRating()
    {
        return $this->container['fx_rating'];
    }

    /**
     * Sets fx_rating
     *
     * @param int|null $fx_rating Fixed rating flag (advanced).
     *
     * @return self
     */
    public function setFxRating($fx_rating)
    {
        if (is_null($fx_rating)) {
            throw new \InvalidArgumentException('non-nullable fx_rating cannot be null');
        }
        $this->container['fx_rating'] = $fx_rating;

        return $this;
    }

    /**
     * Gets mail_campaign_budget
     *
     * @return bool|null
     */
    public function getMailCampaignBudget()
    {
        return $this->container['mail_campaign_budget'];
    }

    /**
     * Sets mail_campaign_budget
     *
     * @param bool|null $mail_campaign_budget ron/cpm: email the account when `total_budget` is reached. Where the network offers lifetime budgets.
     *
     * @return self
     */
    public function setMailCampaignBudget($mail_campaign_budget)
    {
        if (is_null($mail_campaign_budget)) {
            throw new \InvalidArgumentException('non-nullable mail_campaign_budget cannot be null');
        }
        $this->container['mail_campaign_budget'] = $mail_campaign_budget;

        return $this;
    }

    /**
     * Gets use_dynamic_costs
     *
     * @return bool|null
     */
    public function getUseDynamicCosts()
    {
        return $this->container['use_dynamic_costs'];
    }

    /**
     * Sets use_dynamic_costs
     *
     * @param bool|null $use_dynamic_costs ron CPC/CPM (`selling_type` 0/1): let the bid rise automatically by up to `dynamic_costs_increase` percent.
     *
     * @return self
     */
    public function setUseDynamicCosts($use_dynamic_costs)
    {
        if (is_null($use_dynamic_costs)) {
            throw new \InvalidArgumentException('non-nullable use_dynamic_costs cannot be null');
        }
        $this->container['use_dynamic_costs'] = $use_dynamic_costs;

        return $this;
    }

    /**
     * Gets dynamic_costs_increase
     *
     * @return int|null
     */
    public function getDynamicCostsIncrease()
    {
        return $this->container['dynamic_costs_increase'];
    }

    /**
     * Sets dynamic_costs_increase
     *
     * @param int|null $dynamic_costs_increase Maximum dynamic-cost increase in percent.
     *
     * @return self
     */
    public function setDynamicCostsIncrease($dynamic_costs_increase)
    {
        if (is_null($dynamic_costs_increase)) {
            throw new \InvalidArgumentException('non-nullable dynamic_costs_increase cannot be null');
        }

        if (($dynamic_costs_increase > 100)) {
            throw new \InvalidArgumentException('invalid value for $dynamic_costs_increase when calling CampaignPanelFields., must be smaller than or equal to 100.');
        }
        if (($dynamic_costs_increase < 0)) {
            throw new \InvalidArgumentException('invalid value for $dynamic_costs_increase when calling CampaignPanelFields., must be bigger than or equal to 0.');
        }

        $this->container['dynamic_costs_increase'] = $dynamic_costs_increase;

        return $this;
    }

    /**
     * Gets conversion_capping
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsConversionCapping|null
     */
    public function getConversionCapping()
    {
        return $this->container['conversion_capping'];
    }

    /**
     * Sets conversion_capping
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsConversionCapping|null $conversion_capping conversion_capping
     *
     * @return self
     */
    public function setConversionCapping($conversion_capping)
    {
        if (is_null($conversion_capping)) {
            throw new \InvalidArgumentException('non-nullable conversion_capping cannot be null');
        }
        $this->container['conversion_capping'] = $conversion_capping;

        return $this;
    }

    /**
     * Gets fix_cpa
     *
     * @return float|null
     */
    public function getFixCpa()
    {
        return $this->container['fix_cpa'];
    }

    /**
     * Sets fix_cpa
     *
     * @param float|null $fix_cpa ron/cpm CPA goal in main currency. After create only your account manager changes it, except on AdManager networks (a PATCH with a different value is a 422; the same value is a no-op).
     *
     * @return self
     */
    public function setFixCpa($fix_cpa)
    {
        if (is_null($fix_cpa)) {
            throw new \InvalidArgumentException('non-nullable fix_cpa cannot be null');
        }

        if (($fix_cpa < 0)) {
            throw new \InvalidArgumentException('invalid value for $fix_cpa when calling CampaignPanelFields., must be bigger than or equal to 0.');
        }

        $this->container['fix_cpa'] = $fix_cpa;

        return $this;
    }

    /**
     * Gets fix_cpa_conv_types
     *
     * @return string|null
     */
    public function getFixCpaConvTypes()
    {
        return $this->container['fix_cpa_conv_types'];
    }

    /**
     * Sets fix_cpa_conv_types
     *
     * @param string|null $fix_cpa_conv_types Conversion type ids the CPA goal counts (empty = all). A JSON array is accepted.
     *
     * @return self
     */
    public function setFixCpaConvTypes($fix_cpa_conv_types)
    {
        if (is_null($fix_cpa_conv_types)) {
            throw new \InvalidArgumentException('non-nullable fix_cpa_conv_types cannot be null');
        }
        $this->container['fix_cpa_conv_types'] = $fix_cpa_conv_types;

        return $this;
    }

    /**
     * Gets booster
     *
     * @return bool|null
     */
    public function getBooster()
    {
        return $this->container['booster'];
    }

    /**
     * Sets booster
     *
     * @param bool|null $booster ron CPA (`selling_type` 2): AdBooster, where the network licenses it.
     *
     * @return self
     */
    public function setBooster($booster)
    {
        if (is_null($booster)) {
            throw new \InvalidArgumentException('non-nullable booster cannot be null');
        }
        $this->container['booster'] = $booster;

        return $this;
    }

    /**
     * Gets booster_settings
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsBoosterSettings|null
     */
    public function getBoosterSettings()
    {
        return $this->container['booster_settings'];
    }

    /**
     * Sets booster_settings
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsBoosterSettings|null $booster_settings booster_settings
     *
     * @return self
     */
    public function setBoosterSettings($booster_settings)
    {
        if (is_null($booster_settings)) {
            throw new \InvalidArgumentException('non-nullable booster_settings cannot be null');
        }
        $this->container['booster_settings'] = $booster_settings;

        return $this;
    }

    /**
     * Gets default_bid_type
     *
     * @return int|null
     */
    public function getDefaultBidType()
    {
        return $this->container['default_bid_type'];
    }

    /**
     * Sets default_bid_type
     *
     * @param int|null $default_bid_type ron: smart bidding on (1) / off (0), for CPC or CPM where the network lets users switch it on.
     *
     * @return self
     */
    public function setDefaultBidType($default_bid_type)
    {
        if (is_null($default_bid_type)) {
            throw new \InvalidArgumentException('non-nullable default_bid_type cannot be null');
        }
        $allowedValues = $this->getDefaultBidTypeAllowableValues();
        if (!in_array($default_bid_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'default_bid_type', must be one of '%s'",
                    $default_bid_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['default_bid_type'] = $default_bid_type;

        return $this;
    }

    /**
     * Gets sort_type
     *
     * @return int|null
     */
    public function getSortType()
    {
        return $this->container['sort_type'];
    }

    /**
     * Sets sort_type
     *
     * @param int|null $sort_type ron: ad sorting — 0 = eCPM, 1 = random, 2 = weighted. Any pay type (CPC/CPM: random or weighted skips smart-bid repricing). Not for offers.
     *
     * @return self
     */
    public function setSortType($sort_type)
    {
        if (is_null($sort_type)) {
            throw new \InvalidArgumentException('non-nullable sort_type cannot be null');
        }
        $this->container['sort_type'] = $sort_type;

        return $this;
    }

    /**
     * Gets as_failover
     *
     * @return bool|null
     */
    public function getAsFailover()
    {
        return $this->container['as_failover'];
    }

    /**
     * Sets as_failover
     *
     * @param bool|null $as_failover ron (AdManager networks, not flat): serve only as failover.
     *
     * @return self
     */
    public function setAsFailover($as_failover)
    {
        if (is_null($as_failover)) {
            throw new \InvalidArgumentException('non-nullable as_failover cannot be null');
        }
        $this->container['as_failover'] = $as_failover;

        return $this;
    }

    /**
     * Gets day_parting
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsDayParting|null
     */
    public function getDayParting()
    {
        return $this->container['day_parting'];
    }

    /**
     * Sets day_parting
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsDayParting|null $day_parting day_parting
     *
     * @return self
     */
    public function setDayParting($day_parting)
    {
        if (is_null($day_parting)) {
            throw new \InvalidArgumentException('non-nullable day_parting cannot be null');
        }
        $this->container['day_parting'] = $day_parting;

        return $this;
    }

    /**
     * Gets screens
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsScreens|null
     */
    public function getScreens()
    {
        return $this->container['screens'];
    }

    /**
     * Sets screens
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsScreens|null $screens screens
     *
     * @return self
     */
    public function setScreens($screens)
    {
        if (is_null($screens)) {
            throw new \InvalidArgumentException('non-nullable screens cannot be null');
        }
        $this->container['screens'] = $screens;

        return $this;
    }

    /**
     * Gets display_times
     *
     * @return string|null
     */
    public function getDisplayTimes()
    {
        return $this->container['display_times'];
    }

    /**
     * Sets display_times
     *
     * @param string|null $display_times Legacy daily time windows (pipe-separated; overlaps merged). Superseded by `day_parting`.
     *
     * @return self
     */
    public function setDisplayTimes($display_times)
    {
        if (is_null($display_times)) {
            throw new \InvalidArgumentException('non-nullable display_times cannot be null');
        }
        $this->container['display_times'] = $display_times;

        return $this;
    }

    /**
     * Gets display_days
     *
     * @return string|null
     */
    public function getDisplayDays()
    {
        return $this->container['display_days'];
    }

    /**
     * Sets display_days
     *
     * @param string|null $display_days Legacy weekday list (\"0\" = every day). Superseded by `day_parting`.
     *
     * @return self
     */
    public function setDisplayDays($display_days)
    {
        if (is_null($display_days)) {
            throw new \InvalidArgumentException('non-nullable display_days cannot be null');
        }
        $this->container['display_days'] = $display_days;

        return $this;
    }

    /**
     * Gets site_languages
     *
     * @return string|null
     */
    public function getSiteLanguages()
    {
        return $this->container['site_languages'];
    }

    /**
     * Sets site_languages
     *
     * @param string|null $site_languages Website language codes (\"0\" = all). A JSON array is accepted.
     *
     * @return self
     */
    public function setSiteLanguages($site_languages)
    {
        if (is_null($site_languages)) {
            throw new \InvalidArgumentException('non-nullable site_languages cannot be null');
        }
        $this->container['site_languages'] = $site_languages;

        return $this;
    }

    /**
     * Gets connection_types
     *
     * @return string|null
     */
    public function getConnectionTypes()
    {
        return $this->container['connection_types'];
    }

    /**
     * Sets connection_types
     *
     * @param string|null $connection_types Connection type ids (\"0\" = all). A JSON array is accepted.
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
     * Gets carrier_ids
     *
     * @return string|null
     */
    public function getCarrierIds()
    {
        return $this->container['carrier_ids'];
    }

    /**
     * Sets carrier_ids
     *
     * @param string|null $carrier_ids ron: mobile carrier ids (\"0\" = all); meaningful with `connection_types` = \"2\" and no IP pool.
     *
     * @return self
     */
    public function setCarrierIds($carrier_ids)
    {
        if (is_null($carrier_ids)) {
            throw new \InvalidArgumentException('non-nullable carrier_ids cannot be null');
        }
        $this->container['carrier_ids'] = $carrier_ids;

        return $this;
    }

    /**
     * Gets rating_id
     *
     * @return int|null
     */
    public function getRatingId()
    {
        return $this->container['rating_id'];
    }

    /**
     * Sets rating_id
     *
     * @param int|null $rating_id Content rating id, where the network uses ratings.
     *
     * @return self
     */
    public function setRatingId($rating_id)
    {
        if (is_null($rating_id)) {
            throw new \InvalidArgumentException('non-nullable rating_id cannot be null');
        }
        $this->container['rating_id'] = $rating_id;

        return $this;
    }

    /**
     * Gets orientation
     *
     * @return string|null
     */
    public function getOrientation()
    {
        return $this->container['orientation'];
    }

    /**
     * Sets orientation
     *
     * @param string|null $orientation ron/cpm ad types 3 and 22.
     *
     * @return self
     */
    public function setOrientation($orientation)
    {
        if (is_null($orientation)) {
            throw new \InvalidArgumentException('non-nullable orientation cannot be null');
        }
        $allowedValues = $this->getOrientationAllowableValues();
        if (!in_array($orientation, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'orientation', must be one of '%s'",
                    $orientation,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['orientation'] = $orientation;

        return $this;
    }

    /**
     * Gets site_mode
     *
     * @return int|null
     */
    public function getSiteMode()
    {
        return $this->container['site_mode'];
    }

    /**
     * Sets site_mode
     *
     * @param int|null $site_mode ron: 0 = RON (all sites), 1 = Premium sites (display only; may need a minimum balance). Read back with the admin-set `sites_locked`.
     *
     * @return self
     */
    public function setSiteMode($site_mode)
    {
        if (is_null($site_mode)) {
            throw new \InvalidArgumentException('non-nullable site_mode cannot be null');
        }
        $allowedValues = $this->getSiteModeAllowableValues();
        if (!in_array($site_mode, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'site_mode', must be one of '%s'",
                    $site_mode,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['site_mode'] = $site_mode;

        return $this;
    }

    /**
     * Gets only_tagged_zones
     *
     * @return bool|null
     */
    public function getOnlyTaggedZones()
    {
        return $this->container['only_tagged_zones'];
    }

    /**
     * Sets only_tagged_zones
     *
     * @param bool|null $only_tagged_zones ron/cpm on AdManager networks: only run on ad zones sharing one of the campaign's groups.
     *
     * @return self
     */
    public function setOnlyTaggedZones($only_tagged_zones)
    {
        if (is_null($only_tagged_zones)) {
            throw new \InvalidArgumentException('non-nullable only_tagged_zones cannot be null');
        }
        $this->container['only_tagged_zones'] = $only_tagged_zones;

        return $this;
    }

    /**
     * Gets skip_low_quality
     *
     * @return int|null
     */
    public function getSkipLowQuality()
    {
        return $this->container['skip_low_quality'];
    }

    /**
     * Sets skip_low_quality
     *
     * @param int|null $skip_low_quality ron/cpm (not flat): low-quality traffic 0 = accept, 1 = block, 2 = target only.
     *
     * @return self
     */
    public function setSkipLowQuality($skip_low_quality)
    {
        if (is_null($skip_low_quality)) {
            throw new \InvalidArgumentException('non-nullable skip_low_quality cannot be null');
        }
        $allowedValues = $this->getSkipLowQualityAllowableValues();
        if (!in_array($skip_low_quality, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'skip_low_quality', must be one of '%s'",
                    $skip_low_quality,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['skip_low_quality'] = $skip_low_quality;

        return $this;
    }

    /**
     * Gets ip_whitelist
     *
     * @return string|null
     */
    public function getIpWhitelist()
    {
        return $this->container['ip_whitelist'];
    }

    /**
     * Sets ip_whitelist
     *
     * @param string|null $ip_whitelist IP allow list, one entry per line (IPv4/IPv6, CIDR or `a|b` ranges); each line is validated. A JSON array is accepted.
     *
     * @return self
     */
    public function setIpWhitelist($ip_whitelist)
    {
        if (is_null($ip_whitelist)) {
            throw new \InvalidArgumentException('non-nullable ip_whitelist cannot be null');
        }
        $this->container['ip_whitelist'] = $ip_whitelist;

        return $this;
    }

    /**
     * Gets ip_blacklist
     *
     * @return string|null
     */
    public function getIpBlacklist()
    {
        return $this->container['ip_blacklist'];
    }

    /**
     * Sets ip_blacklist
     *
     * @param string|null $ip_blacklist IP deny list, same syntax. An IP on both lists is blocked.
     *
     * @return self
     */
    public function setIpBlacklist($ip_blacklist)
    {
        if (is_null($ip_blacklist)) {
            throw new \InvalidArgumentException('non-nullable ip_blacklist cannot be null');
        }
        $this->container['ip_blacklist'] = $ip_blacklist;

        return $this;
    }

    /**
     * Gets av_check
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsAvCheck|null
     */
    public function getAvCheck()
    {
        return $this->container['av_check'];
    }

    /**
     * Sets av_check
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsAvCheck|null $av_check av_check
     *
     * @return self
     */
    public function setAvCheck($av_check)
    {
        if (is_null($av_check)) {
            throw new \InvalidArgumentException('non-nullable av_check cannot be null');
        }
        $this->container['av_check'] = $av_check;

        return $this;
    }

    /**
     * Gets email_type
     *
     * @return int|null
     */
    public function getEmailType()
    {
        return $this->container['email_type'];
    }

    /**
     * Sets email_type
     *
     * @param int|null $email_type ron/rtb email ads (ad type 15): 0 = accept all, 1 = email only, 2 = non-email only.
     *
     * @return self
     */
    public function setEmailType($email_type)
    {
        if (is_null($email_type)) {
            throw new \InvalidArgumentException('non-nullable email_type cannot be null');
        }
        $allowedValues = $this->getEmailTypeAllowableValues();
        if (!in_array($email_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'email_type', must be one of '%s'",
                    $email_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['email_type'] = $email_type;

        return $this;
    }

    /**
     * Gets use_subscription_age
     *
     * @return bool|null
     */
    public function getUseSubscriptionAge()
    {
        return $this->container['use_subscription_age'];
    }

    /**
     * Sets use_subscription_age
     *
     * @param bool|null $use_subscription_age ron push ads (ad type 7).
     *
     * @return self
     */
    public function setUseSubscriptionAge($use_subscription_age)
    {
        if (is_null($use_subscription_age)) {
            throw new \InvalidArgumentException('non-nullable use_subscription_age cannot be null');
        }
        $this->container['use_subscription_age'] = $use_subscription_age;

        return $this;
    }

    /**
     * Gets subscription_age
     *
     * @return int|null
     */
    public function getSubscriptionAge()
    {
        return $this->container['subscription_age'];
    }

    /**
     * Sets subscription_age
     *
     * @param int|null $subscription_age subscription_age
     *
     * @return self
     */
    public function setSubscriptionAge($subscription_age)
    {
        if (is_null($subscription_age)) {
            throw new \InvalidArgumentException('non-nullable subscription_age cannot be null');
        }

        if (($subscription_age < 0)) {
            throw new \InvalidArgumentException('invalid value for $subscription_age when calling CampaignPanelFields., must be bigger than or equal to 0.');
        }

        $this->container['subscription_age'] = $subscription_age;

        return $this;
    }

    /**
     * Gets instant_impression
     *
     * @return bool|null
     */
    public function getInstantImpression()
    {
        return $this->container['instant_impression'];
    }

    /**
     * Sets instant_impression
     *
     * @param bool|null $instant_impression ron video (ad type 4) on AdManager networks.
     *
     * @return self
     */
    public function setInstantImpression($instant_impression)
    {
        if (is_null($instant_impression)) {
            throw new \InvalidArgumentException('non-nullable instant_impression cannot be null');
        }
        $this->container['instant_impression'] = $instant_impression;

        return $this;
    }

    /**
     * Gets subid_list
     *
     * @return string|null
     */
    public function getSubidList()
    {
        return $this->container['subid_list'];
    }

    /**
     * Sets subid_list
     *
     * @param string|null $subid_list ron/cpm (not flat): SubIDs to filter on, one per line or comma-separated (empty clears). Stored as the campaign's SubID filter.
     *
     * @return self
     */
    public function setSubidList($subid_list)
    {
        if (is_null($subid_list)) {
            throw new \InvalidArgumentException('non-nullable subid_list cannot be null');
        }
        $this->container['subid_list'] = $subid_list;

        return $this;
    }

    /**
     * Gets subid_list_mode
     *
     * @return string|null
     */
    public function getSubidListMode()
    {
        return $this->container['subid_list_mode'];
    }

    /**
     * Sets subid_list_mode
     *
     * @param string|null $subid_list_mode subid_list_mode
     *
     * @return self
     */
    public function setSubidListMode($subid_list_mode)
    {
        if (is_null($subid_list_mode)) {
            throw new \InvalidArgumentException('non-nullable subid_list_mode cannot be null');
        }
        $allowedValues = $this->getSubidListModeAllowableValues();
        if (!in_array($subid_list_mode, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'subid_list_mode', must be one of '%s'",
                    $subid_list_mode,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['subid_list_mode'] = $subid_list_mode;

        return $this;
    }

    /**
     * Gets source_optimizer_ids
     *
     * @return string|null
     */
    public function getSourceOptimizerIds()
    {
        return $this->container['source_optimizer_ids'];
    }

    /**
     * Sets source_optimizer_ids
     *
     * @param string|null $source_optimizer_ids ron: your own active source optimizers, at most one per filter type; ids that are not yours are dropped. \"0\" detaches all.
     *
     * @return self
     */
    public function setSourceOptimizerIds($source_optimizer_ids)
    {
        if (is_null($source_optimizer_ids)) {
            throw new \InvalidArgumentException('non-nullable source_optimizer_ids cannot be null');
        }
        $this->container['source_optimizer_ids'] = $source_optimizer_ids;

        return $this;
    }

    /**
     * Gets wl_zone_ids
     *
     * @return string|null
     */
    public function getWlZoneIds()
    {
        return $this->container['wl_zone_ids'];
    }

    /**
     * Sets wl_zone_ids
     *
     * @param string|null $wl_zone_ids cpm: ad zone ids the campaign is pinned to (empty = all).
     *
     * @return self
     */
    public function setWlZoneIds($wl_zone_ids)
    {
        if (is_null($wl_zone_ids)) {
            throw new \InvalidArgumentException('non-nullable wl_zone_ids cannot be null');
        }
        $this->container['wl_zone_ids'] = $wl_zone_ids;

        return $this;
    }

    /**
     * Gets feed_id
     *
     * @return int|null
     */
    public function getFeedId()
    {
        return $this->container['feed_id'];
    }

    /**
     * Sets feed_id
     *
     * @param int|null $feed_id ron ad types 3 / 22: one of your XML feeds (0 = none).
     *
     * @return self
     */
    public function setFeedId($feed_id)
    {
        if (is_null($feed_id)) {
            throw new \InvalidArgumentException('non-nullable feed_id cannot be null');
        }
        $this->container['feed_id'] = $feed_id;

        return $this;
    }

    /**
     * Gets feed_settings
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsFeedSettings|null
     */
    public function getFeedSettings()
    {
        return $this->container['feed_settings'];
    }

    /**
     * Sets feed_settings
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsFeedSettings|null $feed_settings feed_settings
     *
     * @return self
     */
    public function setFeedSettings($feed_settings)
    {
        if (is_null($feed_settings)) {
            throw new \InvalidArgumentException('non-nullable feed_settings cannot be null');
        }
        $this->container['feed_settings'] = $feed_settings;

        return $this;
    }

    /**
     * Gets priority
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsPriority|null
     */
    public function getPriority()
    {
        return $this->container['priority'];
    }

    /**
     * Sets priority
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsPriority|null $priority priority
     *
     * @return self
     */
    public function setPriority($priority)
    {
        if (is_null($priority)) {
            throw new \InvalidArgumentException('non-nullable priority cannot be null');
        }
        $this->container['priority'] = $priority;

        return $this;
    }

    /**
     * Gets suggestions
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsSuggestions|null
     */
    public function getSuggestions()
    {
        return $this->container['suggestions'];
    }

    /**
     * Sets suggestions
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsSuggestions|null $suggestions suggestions
     *
     * @return self
     */
    public function setSuggestions($suggestions)
    {
        if (is_null($suggestions)) {
            throw new \InvalidArgumentException('non-nullable suggestions cannot be null');
        }
        $this->container['suggestions'] = $suggestions;

        return $this;
    }

    /**
     * Gets hide_referrer
     *
     * @return bool|null
     */
    public function getHideReferrer()
    {
        return $this->container['hide_referrer'];
    }

    /**
     * Sets hide_referrer
     *
     * @param bool|null $hide_referrer ron/rtb on AdManager networks (options): JS redirect so the landing page sees the ad server as referrer.
     *
     * @return self
     */
    public function setHideReferrer($hide_referrer)
    {
        if (is_null($hide_referrer)) {
            throw new \InvalidArgumentException('non-nullable hide_referrer cannot be null');
        }
        $this->container['hide_referrer'] = $hide_referrer;

        return $this;
    }

    /**
     * Gets log_conv_as_paid
     *
     * @return bool|null
     */
    public function getLogConvAsPaid()
    {
        return $this->container['log_conv_as_paid'];
    }

    /**
     * Sets log_conv_as_paid
     *
     * @param bool|null $log_conv_as_paid ron on AdManager networks where you own an SSP campaign (options): book spend as Traffic Cost and conversions as Paid. Defaults to true on new campaigns there.
     *
     * @return self
     */
    public function setLogConvAsPaid($log_conv_as_paid)
    {
        if (is_null($log_conv_as_paid)) {
            throw new \InvalidArgumentException('non-nullable log_conv_as_paid cannot be null');
        }
        $this->container['log_conv_as_paid'] = $log_conv_as_paid;

        return $this;
    }

    /**
     * Gets about_settings
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsAboutSettings|null
     */
    public function getAboutSettings()
    {
        return $this->container['about_settings'];
    }

    /**
     * Sets about_settings
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsAboutSettings|null $about_settings about_settings
     *
     * @return self
     */
    public function setAboutSettings($about_settings)
    {
        if (is_null($about_settings)) {
            throw new \InvalidArgumentException('non-nullable about_settings cannot be null');
        }
        $this->container['about_settings'] = $about_settings;

        return $this;
    }

    /**
     * Gets use_live_feed
     *
     * @return bool|null
     */
    public function getUseLiveFeed()
    {
        return $this->container['use_live_feed'];
    }

    /**
     * Sets use_live_feed
     *
     * @param bool|null $use_live_feed ron video (ad type 4): live HLS feed, where the network offers it (not on AdNetwork networks).
     *
     * @return self
     */
    public function setUseLiveFeed($use_live_feed)
    {
        if (is_null($use_live_feed)) {
            throw new \InvalidArgumentException('non-nullable use_live_feed cannot be null');
        }
        $this->container['use_live_feed'] = $use_live_feed;

        return $this;
    }

    /**
     * Gets live_feed_parent_id
     *
     * @return int|null
     */
    public function getLiveFeedParentId()
    {
        return $this->container['live_feed_parent_id'];
    }

    /**
     * Sets live_feed_parent_id
     *
     * @param int|null $live_feed_parent_id One of your master-feed video campaigns (0 = this campaign is a master feed).
     *
     * @return self
     */
    public function setLiveFeedParentId($live_feed_parent_id)
    {
        if (is_null($live_feed_parent_id)) {
            throw new \InvalidArgumentException('non-nullable live_feed_parent_id cannot be null');
        }
        $this->container['live_feed_parent_id'] = $live_feed_parent_id;

        return $this;
    }

    /**
     * Gets live_feed_settings
     *
     * @return array<string,mixed>|null
     */
    public function getLiveFeedSettings()
    {
        return $this->container['live_feed_settings'];
    }

    /**
     * Sets live_feed_settings
     *
     * @param array<string,mixed>|null $live_feed_settings {hls_provider, campaign, gender, resize_to, bid, desturl_macro, desturl_type, use_logo}. hls_provider: chaturbate, bongacams or camsoda; campaign: the provider's affiliate/campaign id (CamSoda: your affiliate id); gender: f, m, t or c; desturl_type (chat_room_url or chat_room_url_revshare) is Chaturbate only; desturl_macro fills the link's tracking slot (CamSoda: appended to the link as query text, e.g. sub_id={clickid}).
     *
     * @return self
     */
    public function setLiveFeedSettings($live_feed_settings)
    {
        if (is_null($live_feed_settings)) {
            throw new \InvalidArgumentException('non-nullable live_feed_settings cannot be null');
        }
        $this->container['live_feed_settings'] = $live_feed_settings;

        return $this;
    }

    /**
     * Gets offer_type_id
     *
     * @return int|null
     */
    public function getOfferTypeId()
    {
        return $this->container['offer_type_id'];
    }

    /**
     * Sets offer_type_id
     *
     * @param int|null $offer_type_id ron offers (ad type 21).
     *
     * @return self
     */
    public function setOfferTypeId($offer_type_id)
    {
        if (is_null($offer_type_id)) {
            throw new \InvalidArgumentException('non-nullable offer_type_id cannot be null');
        }
        $this->container['offer_type_id'] = $offer_type_id;

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
     * @param string|null $description ron offers (ad type 21): the offer description (stored on the offer's ad).
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
     * Gets destination_url
     *
     * @return string|null
     */
    public function getDestinationUrl()
    {
        return $this->container['destination_url'];
    }

    /**
     * Sets destination_url
     *
     * @param string|null $destination_url ron offers (ad type 21): http(s) destination URL (include `{tracking}` for CPA offers).
     *
     * @return self
     */
    public function setDestinationUrl($destination_url)
    {
        if (is_null($destination_url)) {
            throw new \InvalidArgumentException('non-nullable destination_url cannot be null');
        }
        $this->container['destination_url'] = $destination_url;

        return $this;
    }

    /**
     * Gets preview_url
     *
     * @return string|null
     */
    public function getPreviewUrl()
    {
        return $this->container['preview_url'];
    }

    /**
     * Sets preview_url
     *
     * @param string|null $preview_url ron offers (ad type 21): http(s) preview URL.
     *
     * @return self
     */
    public function setPreviewUrl($preview_url)
    {
        if (is_null($preview_url)) {
            throw new \InvalidArgumentException('non-nullable preview_url cannot be null');
        }
        $this->container['preview_url'] = $preview_url;

        return $this;
    }

    /**
     * Gets rtb_network_id
     *
     * @return int|null
     */
    public function getRtbNetworkId()
    {
        return $this->container['rtb_network_id'];
    }

    /**
     * Sets rtb_network_id
     *
     * @param int|null $rtb_network_id rtb: an active RTB network of this tenant. Ignored when your account is locked to one network.
     *
     * @return self
     */
    public function setRtbNetworkId($rtb_network_id)
    {
        if (is_null($rtb_network_id)) {
            throw new \InvalidArgumentException('non-nullable rtb_network_id cannot be null');
        }
        $this->container['rtb_network_id'] = $rtb_network_id;

        return $this;
    }

    /**
     * Gets rtb_type
     *
     * @return string|null
     */
    public function getRtbType()
    {
        return $this->container['rtb_type'];
    }

    /**
     * Sets rtb_type
     *
     * @param string|null $rtb_type rtb protocol; json / xml only for ad types 7, 11, 15.
     *
     * @return self
     */
    public function setRtbType($rtb_type)
    {
        if (is_null($rtb_type)) {
            throw new \InvalidArgumentException('non-nullable rtb_type cannot be null');
        }
        $allowedValues = $this->getRtbTypeAllowableValues();
        if (!in_array($rtb_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'rtb_type', must be one of '%s'",
                    $rtb_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['rtb_type'] = $rtb_type;

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
     * @param int|null $use_type rtb: 0 = one `request_url`; 1 = a URL per zone in `zone_urls` (then `request_url` is optional).
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
     * Gets zone_urls
     *
     * @return object[]|null
     */
    public function getZoneUrls()
    {
        return $this->container['zone_urls'];
    }

    /**
     * Sets zone_urls
     *
     * @param object[]|null $zone_urls rtb URLs per zone (use_type 1).
     *
     * @return self
     */
    public function setZoneUrls($zone_urls)
    {
        if (is_null($zone_urls)) {
            throw new \InvalidArgumentException('non-nullable zone_urls cannot be null');
        }
        $this->container['zone_urls'] = $zone_urls;

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
     * @param int|null $bid_action rtb OpenRTB auction: 1 = first price, 2 = second price.
     *
     * @return self
     */
    public function setBidAction($bid_action)
    {
        if (is_null($bid_action)) {
            throw new \InvalidArgumentException('non-nullable bid_action cannot be null');
        }
        $allowedValues = $this->getBidActionAllowableValues();
        if (!in_array($bid_action, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'bid_action', must be one of '%s'",
                    $bid_action,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['bid_action'] = $bid_action;

        return $this;
    }

    /**
     * Gets use_qps
     *
     * @return bool|null
     */
    public function getUseQps()
    {
        return $this->container['use_qps'];
    }

    /**
     * Sets use_qps
     *
     * @param bool|null $use_qps use_qps
     *
     * @return self
     */
    public function setUseQps($use_qps)
    {
        if (is_null($use_qps)) {
            throw new \InvalidArgumentException('non-nullable use_qps cannot be null');
        }
        $this->container['use_qps'] = $use_qps;

        return $this;
    }

    /**
     * Gets qps
     *
     * @return int|null
     */
    public function getQps()
    {
        return $this->container['qps'];
    }

    /**
     * Sets qps
     *
     * @param int|null $qps qps
     *
     * @return self
     */
    public function setQps($qps)
    {
        if (is_null($qps)) {
            throw new \InvalidArgumentException('non-nullable qps cannot be null');
        }

        if (($qps < 0)) {
            throw new \InvalidArgumentException('invalid value for $qps when calling CampaignPanelFields., must be bigger than or equal to 0.');
        }

        $this->container['qps'] = $qps;

        return $this;
    }

    /**
     * Gets rtb_categories
     *
     * @return string|null
     */
    public function getRtbCategories()
    {
        return $this->container['rtb_categories'];
    }

    /**
     * Sets rtb_categories
     *
     * @param string|null $rtb_categories rtb OpenRTB content categories. A JSON array is accepted.
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
     * Gets use_api
     *
     * @return bool|null
     */
    public function getUseApi()
    {
        return $this->container['use_api'];
    }

    /**
     * Sets use_api
     *
     * @param bool|null $use_api rtb: pull the partner's stats through its reporting API.
     *
     * @return self
     */
    public function setUseApi($use_api)
    {
        if (is_null($use_api)) {
            throw new \InvalidArgumentException('non-nullable use_api cannot be null');
        }
        $this->container['use_api'] = $use_api;

        return $this;
    }

    /**
     * Gets api_data
     *
     * @return array<string,mixed>|null
     */
    public function getApiData()
    {
        return $this->container['api_data'];
    }

    /**
     * Sets api_data
     *
     * @param array<string,mixed>|null $api_data rtb per-network stats-API credentials.
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
     * Gets log_skipped
     *
     * @return bool|null
     */
    public function getLogSkipped()
    {
        return $this->container['log_skipped'];
    }

    /**
     * Sets log_skipped
     *
     * @param bool|null $log_skipped rtb: log no-bid responses in the stats.
     *
     * @return self
     */
    public function setLogSkipped($log_skipped)
    {
        if (is_null($log_skipped)) {
            throw new \InvalidArgumentException('non-nullable log_skipped cannot be null');
        }
        $this->container['log_skipped'] = $log_skipped;

        return $this;
    }

    /**
     * Gets domain_mask
     *
     * @return \GoAdServerApi\Model\CampaignPanelFieldsDomainMask|null
     */
    public function getDomainMask()
    {
        return $this->container['domain_mask'];
    }

    /**
     * Sets domain_mask
     *
     * @param \GoAdServerApi\Model\CampaignPanelFieldsDomainMask|null $domain_mask domain_mask
     *
     * @return self
     */
    public function setDomainMask($domain_mask)
    {
        if (is_null($domain_mask)) {
            throw new \InvalidArgumentException('non-nullable domain_mask cannot be null');
        }
        $this->container['domain_mask'] = $domain_mask;

        return $this;
    }

    /**
     * Gets rtb_xml_markup
     *
     * @return bool|null
     */
    public function getRtbXmlMarkup()
    {
        return $this->container['rtb_xml_markup'];
    }

    /**
     * Sets rtb_xml_markup
     *
     * @param bool|null $rtb_xml_markup rtb banner (ad type 2), stored as options.rtb.xml_markup: ask the bidder for XML ad markup.
     *
     * @return self
     */
    public function setRtbXmlMarkup($rtb_xml_markup)
    {
        if (is_null($rtb_xml_markup)) {
            throw new \InvalidArgumentException('non-nullable rtb_xml_markup cannot be null');
        }
        $this->container['rtb_xml_markup'] = $rtb_xml_markup;

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



<?php
/**
 * Adspace
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
 * Adspace Class Doc Comment
 *
 * @category Class
 * @description Full adspace record returned by &#x60;GET /api/v1/adspaces/{id}&#x60; and the create/update endpoints. Shape mirrors &#x60;AdspaceCreate&#x60; so round-trips work without translation, plus a few read-only identity fields (&#x60;id&#x60;, &#x60;uniq_name&#x60;, &#x60;adspace_type&#x60;, &#x60;is_active&#x60;, &#x60;date_added&#x60;, &#x60;time_changed&#x60;) and the parsed &#x60;json_settings&#x60;.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class Adspace implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'Adspace';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'name' => 'string',
        'description' => 'string',
        'uniq_name' => 'string',
        'site' => '\GoAdServerApi\Model\AdspaceSite',
        'siteid' => 'int',
        'adspace_type' => 'string',
        'adzone_typeid' => 'int',
        'adtype_id' => 'int',
        'ad_type' => 'int',
        'adsizeid' => 'int',
        'display_type' => 'int',
        'ratingid' => 'int',
        'main_catid' => 'int',
        'catid' => 'int',
        'locationid' => 'int',
        'quality_rank' => 'int',
        'is_active' => 'bool',
        'autoapprove' => 'bool',
        'isvisible' => 'bool',
        'sendmails' => 'bool',
        'useownbanners' => 'bool',
        'usealtbanner' => 'bool',
        'usecpmbuys' => 'bool',
        'allow_iframes' => 'bool',
        'allow_animation' => 'bool',
        'run_rtb_after_smartzone' => 'bool',
        'use_pixel_log' => 'bool',
        'show_about' => 'bool',
        'skip_ron_if_cpm' => 'bool',
        'no_promotion' => 'bool',
        'hidecopy' => 'bool',
        'use_subid_list' => 'bool',
        'subid_list' => 'string',
        'use_domain_list' => 'bool',
        'domain_list' => 'string[]',
        'use_costs' => 'bool',
        'costs_data' => 'array<string,mixed>',
        'use_dynamic_costs' => 'bool',
        'use_fixed_payouts' => 'bool',
        'fixed_payout_data' => 'array<string,mixed>',
        'use_fallback_campaign' => 'bool',
        'fallback_campaignid' => 'int',
        'excl_catids' => 'string',
        'exclproperties' => 'string',
        'tags' => 'int[]',
        'rtb_name' => 'string',
        'date_added' => '\DateTime',
        'time_changed' => 'int',
        'useflatbuys' => 'bool',
        'only_wl_campaigns' => 'bool',
        'only_tagged_campaigns' => 'bool',
        'force_wl_targeting' => 'bool',
        'skip_rtb_when_wl' => 'bool',
        'parentid' => 'int',
        'new_zoneid' => 'int',
        'network_binding' => '\GoAdServerApi\Model\AdspaceNetworkBinding',
        'json_settings' => 'array<string,mixed>'
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
        'description' => null,
        'uniq_name' => null,
        'site' => null,
        'siteid' => null,
        'adspace_type' => null,
        'adzone_typeid' => null,
        'adtype_id' => null,
        'ad_type' => null,
        'adsizeid' => null,
        'display_type' => null,
        'ratingid' => null,
        'main_catid' => null,
        'catid' => null,
        'locationid' => null,
        'quality_rank' => null,
        'is_active' => null,
        'autoapprove' => null,
        'isvisible' => null,
        'sendmails' => null,
        'useownbanners' => null,
        'usealtbanner' => null,
        'usecpmbuys' => null,
        'allow_iframes' => null,
        'allow_animation' => null,
        'run_rtb_after_smartzone' => null,
        'use_pixel_log' => null,
        'show_about' => null,
        'skip_ron_if_cpm' => null,
        'no_promotion' => null,
        'hidecopy' => null,
        'use_subid_list' => null,
        'subid_list' => null,
        'use_domain_list' => null,
        'domain_list' => null,
        'use_costs' => null,
        'costs_data' => null,
        'use_dynamic_costs' => null,
        'use_fixed_payouts' => null,
        'fixed_payout_data' => null,
        'use_fallback_campaign' => null,
        'fallback_campaignid' => null,
        'excl_catids' => null,
        'exclproperties' => null,
        'tags' => null,
        'rtb_name' => null,
        'date_added' => 'date-time',
        'time_changed' => null,
        'useflatbuys' => null,
        'only_wl_campaigns' => null,
        'only_tagged_campaigns' => null,
        'force_wl_targeting' => null,
        'skip_rtb_when_wl' => null,
        'parentid' => null,
        'new_zoneid' => null,
        'network_binding' => null,
        'json_settings' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'name' => false,
        'description' => false,
        'uniq_name' => false,
        'site' => false,
        'siteid' => false,
        'adspace_type' => false,
        'adzone_typeid' => false,
        'adtype_id' => false,
        'ad_type' => false,
        'adsizeid' => false,
        'display_type' => false,
        'ratingid' => false,
        'main_catid' => false,
        'catid' => false,
        'locationid' => false,
        'quality_rank' => false,
        'is_active' => false,
        'autoapprove' => false,
        'isvisible' => false,
        'sendmails' => false,
        'useownbanners' => false,
        'usealtbanner' => false,
        'usecpmbuys' => false,
        'allow_iframes' => false,
        'allow_animation' => false,
        'run_rtb_after_smartzone' => false,
        'use_pixel_log' => false,
        'show_about' => false,
        'skip_ron_if_cpm' => false,
        'no_promotion' => false,
        'hidecopy' => false,
        'use_subid_list' => false,
        'subid_list' => false,
        'use_domain_list' => false,
        'domain_list' => false,
        'use_costs' => false,
        'costs_data' => false,
        'use_dynamic_costs' => false,
        'use_fixed_payouts' => false,
        'fixed_payout_data' => false,
        'use_fallback_campaign' => false,
        'fallback_campaignid' => false,
        'excl_catids' => false,
        'exclproperties' => false,
        'tags' => false,
        'rtb_name' => false,
        'date_added' => false,
        'time_changed' => false,
        'useflatbuys' => false,
        'only_wl_campaigns' => false,
        'only_tagged_campaigns' => false,
        'force_wl_targeting' => false,
        'skip_rtb_when_wl' => false,
        'parentid' => false,
        'new_zoneid' => false,
        'network_binding' => false,
        'json_settings' => false
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
        'description' => 'description',
        'uniq_name' => 'uniq_name',
        'site' => 'site',
        'siteid' => 'siteid',
        'adspace_type' => 'adspace_type',
        'adzone_typeid' => 'adzone_typeid',
        'adtype_id' => 'adtype_id',
        'ad_type' => 'ad_type',
        'adsizeid' => 'adsizeid',
        'display_type' => 'display_type',
        'ratingid' => 'ratingid',
        'main_catid' => 'main_catid',
        'catid' => 'catid',
        'locationid' => 'locationid',
        'quality_rank' => 'quality_rank',
        'is_active' => 'is_active',
        'autoapprove' => 'autoapprove',
        'isvisible' => 'isvisible',
        'sendmails' => 'sendmails',
        'useownbanners' => 'useownbanners',
        'usealtbanner' => 'usealtbanner',
        'usecpmbuys' => 'usecpmbuys',
        'allow_iframes' => 'allow_iframes',
        'allow_animation' => 'allow_animation',
        'run_rtb_after_smartzone' => 'run_rtb_after_smartzone',
        'use_pixel_log' => 'use_pixel_log',
        'show_about' => 'show_about',
        'skip_ron_if_cpm' => 'skip_ron_if_cpm',
        'no_promotion' => 'no_promotion',
        'hidecopy' => 'hidecopy',
        'use_subid_list' => 'use_subid_list',
        'subid_list' => 'subid_list',
        'use_domain_list' => 'use_domain_list',
        'domain_list' => 'domain_list',
        'use_costs' => 'use_costs',
        'costs_data' => 'costs_data',
        'use_dynamic_costs' => 'use_dynamic_costs',
        'use_fixed_payouts' => 'use_fixed_payouts',
        'fixed_payout_data' => 'fixed_payout_data',
        'use_fallback_campaign' => 'use_fallback_campaign',
        'fallback_campaignid' => 'fallback_campaignid',
        'excl_catids' => 'excl_catids',
        'exclproperties' => 'exclproperties',
        'tags' => 'tags',
        'rtb_name' => 'rtb_name',
        'date_added' => 'date_added',
        'time_changed' => 'time_changed',
        'useflatbuys' => 'useflatbuys',
        'only_wl_campaigns' => 'only_wl_campaigns',
        'only_tagged_campaigns' => 'only_tagged_campaigns',
        'force_wl_targeting' => 'force_wl_targeting',
        'skip_rtb_when_wl' => 'skip_rtb_when_wl',
        'parentid' => 'parentid',
        'new_zoneid' => 'new_zoneid',
        'network_binding' => 'network_binding',
        'json_settings' => 'json_settings'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'name' => 'setName',
        'description' => 'setDescription',
        'uniq_name' => 'setUniqName',
        'site' => 'setSite',
        'siteid' => 'setSiteid',
        'adspace_type' => 'setAdspaceType',
        'adzone_typeid' => 'setAdzoneTypeid',
        'adtype_id' => 'setAdtypeId',
        'ad_type' => 'setAdType',
        'adsizeid' => 'setAdsizeid',
        'display_type' => 'setDisplayType',
        'ratingid' => 'setRatingid',
        'main_catid' => 'setMainCatid',
        'catid' => 'setCatid',
        'locationid' => 'setLocationid',
        'quality_rank' => 'setQualityRank',
        'is_active' => 'setIsActive',
        'autoapprove' => 'setAutoapprove',
        'isvisible' => 'setIsvisible',
        'sendmails' => 'setSendmails',
        'useownbanners' => 'setUseownbanners',
        'usealtbanner' => 'setUsealtbanner',
        'usecpmbuys' => 'setUsecpmbuys',
        'allow_iframes' => 'setAllowIframes',
        'allow_animation' => 'setAllowAnimation',
        'run_rtb_after_smartzone' => 'setRunRtbAfterSmartzone',
        'use_pixel_log' => 'setUsePixelLog',
        'show_about' => 'setShowAbout',
        'skip_ron_if_cpm' => 'setSkipRonIfCpm',
        'no_promotion' => 'setNoPromotion',
        'hidecopy' => 'setHidecopy',
        'use_subid_list' => 'setUseSubidList',
        'subid_list' => 'setSubidList',
        'use_domain_list' => 'setUseDomainList',
        'domain_list' => 'setDomainList',
        'use_costs' => 'setUseCosts',
        'costs_data' => 'setCostsData',
        'use_dynamic_costs' => 'setUseDynamicCosts',
        'use_fixed_payouts' => 'setUseFixedPayouts',
        'fixed_payout_data' => 'setFixedPayoutData',
        'use_fallback_campaign' => 'setUseFallbackCampaign',
        'fallback_campaignid' => 'setFallbackCampaignid',
        'excl_catids' => 'setExclCatids',
        'exclproperties' => 'setExclproperties',
        'tags' => 'setTags',
        'rtb_name' => 'setRtbName',
        'date_added' => 'setDateAdded',
        'time_changed' => 'setTimeChanged',
        'useflatbuys' => 'setUseflatbuys',
        'only_wl_campaigns' => 'setOnlyWlCampaigns',
        'only_tagged_campaigns' => 'setOnlyTaggedCampaigns',
        'force_wl_targeting' => 'setForceWlTargeting',
        'skip_rtb_when_wl' => 'setSkipRtbWhenWl',
        'parentid' => 'setParentid',
        'new_zoneid' => 'setNewZoneid',
        'network_binding' => 'setNetworkBinding',
        'json_settings' => 'setJsonSettings'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'name' => 'getName',
        'description' => 'getDescription',
        'uniq_name' => 'getUniqName',
        'site' => 'getSite',
        'siteid' => 'getSiteid',
        'adspace_type' => 'getAdspaceType',
        'adzone_typeid' => 'getAdzoneTypeid',
        'adtype_id' => 'getAdtypeId',
        'ad_type' => 'getAdType',
        'adsizeid' => 'getAdsizeid',
        'display_type' => 'getDisplayType',
        'ratingid' => 'getRatingid',
        'main_catid' => 'getMainCatid',
        'catid' => 'getCatid',
        'locationid' => 'getLocationid',
        'quality_rank' => 'getQualityRank',
        'is_active' => 'getIsActive',
        'autoapprove' => 'getAutoapprove',
        'isvisible' => 'getIsvisible',
        'sendmails' => 'getSendmails',
        'useownbanners' => 'getUseownbanners',
        'usealtbanner' => 'getUsealtbanner',
        'usecpmbuys' => 'getUsecpmbuys',
        'allow_iframes' => 'getAllowIframes',
        'allow_animation' => 'getAllowAnimation',
        'run_rtb_after_smartzone' => 'getRunRtbAfterSmartzone',
        'use_pixel_log' => 'getUsePixelLog',
        'show_about' => 'getShowAbout',
        'skip_ron_if_cpm' => 'getSkipRonIfCpm',
        'no_promotion' => 'getNoPromotion',
        'hidecopy' => 'getHidecopy',
        'use_subid_list' => 'getUseSubidList',
        'subid_list' => 'getSubidList',
        'use_domain_list' => 'getUseDomainList',
        'domain_list' => 'getDomainList',
        'use_costs' => 'getUseCosts',
        'costs_data' => 'getCostsData',
        'use_dynamic_costs' => 'getUseDynamicCosts',
        'use_fixed_payouts' => 'getUseFixedPayouts',
        'fixed_payout_data' => 'getFixedPayoutData',
        'use_fallback_campaign' => 'getUseFallbackCampaign',
        'fallback_campaignid' => 'getFallbackCampaignid',
        'excl_catids' => 'getExclCatids',
        'exclproperties' => 'getExclproperties',
        'tags' => 'getTags',
        'rtb_name' => 'getRtbName',
        'date_added' => 'getDateAdded',
        'time_changed' => 'getTimeChanged',
        'useflatbuys' => 'getUseflatbuys',
        'only_wl_campaigns' => 'getOnlyWlCampaigns',
        'only_tagged_campaigns' => 'getOnlyTaggedCampaigns',
        'force_wl_targeting' => 'getForceWlTargeting',
        'skip_rtb_when_wl' => 'getSkipRtbWhenWl',
        'parentid' => 'getParentid',
        'new_zoneid' => 'getNewZoneid',
        'network_binding' => 'getNetworkBinding',
        'json_settings' => 'getJsonSettings'
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
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('uniq_name', $data ?? [], null);
        $this->setIfExists('site', $data ?? [], null);
        $this->setIfExists('siteid', $data ?? [], null);
        $this->setIfExists('adspace_type', $data ?? [], null);
        $this->setIfExists('adzone_typeid', $data ?? [], null);
        $this->setIfExists('adtype_id', $data ?? [], null);
        $this->setIfExists('ad_type', $data ?? [], null);
        $this->setIfExists('adsizeid', $data ?? [], null);
        $this->setIfExists('display_type', $data ?? [], null);
        $this->setIfExists('ratingid', $data ?? [], null);
        $this->setIfExists('main_catid', $data ?? [], null);
        $this->setIfExists('catid', $data ?? [], null);
        $this->setIfExists('locationid', $data ?? [], null);
        $this->setIfExists('quality_rank', $data ?? [], null);
        $this->setIfExists('is_active', $data ?? [], null);
        $this->setIfExists('autoapprove', $data ?? [], null);
        $this->setIfExists('isvisible', $data ?? [], null);
        $this->setIfExists('sendmails', $data ?? [], null);
        $this->setIfExists('useownbanners', $data ?? [], null);
        $this->setIfExists('usealtbanner', $data ?? [], null);
        $this->setIfExists('usecpmbuys', $data ?? [], null);
        $this->setIfExists('allow_iframes', $data ?? [], null);
        $this->setIfExists('allow_animation', $data ?? [], null);
        $this->setIfExists('run_rtb_after_smartzone', $data ?? [], null);
        $this->setIfExists('use_pixel_log', $data ?? [], null);
        $this->setIfExists('show_about', $data ?? [], null);
        $this->setIfExists('skip_ron_if_cpm', $data ?? [], null);
        $this->setIfExists('no_promotion', $data ?? [], null);
        $this->setIfExists('hidecopy', $data ?? [], null);
        $this->setIfExists('use_subid_list', $data ?? [], null);
        $this->setIfExists('subid_list', $data ?? [], null);
        $this->setIfExists('use_domain_list', $data ?? [], null);
        $this->setIfExists('domain_list', $data ?? [], null);
        $this->setIfExists('use_costs', $data ?? [], null);
        $this->setIfExists('costs_data', $data ?? [], null);
        $this->setIfExists('use_dynamic_costs', $data ?? [], null);
        $this->setIfExists('use_fixed_payouts', $data ?? [], null);
        $this->setIfExists('fixed_payout_data', $data ?? [], null);
        $this->setIfExists('use_fallback_campaign', $data ?? [], null);
        $this->setIfExists('fallback_campaignid', $data ?? [], null);
        $this->setIfExists('excl_catids', $data ?? [], null);
        $this->setIfExists('exclproperties', $data ?? [], null);
        $this->setIfExists('tags', $data ?? [], null);
        $this->setIfExists('rtb_name', $data ?? [], null);
        $this->setIfExists('date_added', $data ?? [], null);
        $this->setIfExists('time_changed', $data ?? [], null);
        $this->setIfExists('useflatbuys', $data ?? [], null);
        $this->setIfExists('only_wl_campaigns', $data ?? [], null);
        $this->setIfExists('only_tagged_campaigns', $data ?? [], null);
        $this->setIfExists('force_wl_targeting', $data ?? [], null);
        $this->setIfExists('skip_rtb_when_wl', $data ?? [], null);
        $this->setIfExists('parentid', $data ?? [], null);
        $this->setIfExists('new_zoneid', $data ?? [], null);
        $this->setIfExists('network_binding', $data ?? [], null);
        $this->setIfExists('json_settings', $data ?? [], null);
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
     * @param string|null $description description
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
     * Gets uniq_name
     *
     * @return string|null
     */
    public function getUniqName()
    {
        return $this->container['uniq_name'];
    }

    /**
     * Sets uniq_name
     *
     * @param string|null $uniq_name MD5 of (name, type, system, site) — internal dedupe key.
     *
     * @return self
     */
    public function setUniqName($uniq_name)
    {
        if (is_null($uniq_name)) {
            throw new \InvalidArgumentException('non-nullable uniq_name cannot be null');
        }
        $this->container['uniq_name'] = $uniq_name;

        return $this;
    }

    /**
     * Gets site
     *
     * @return \GoAdServerApi\Model\AdspaceSite|null
     */
    public function getSite()
    {
        return $this->container['site'];
    }

    /**
     * Sets site
     *
     * @param \GoAdServerApi\Model\AdspaceSite|null $site site
     *
     * @return self
     */
    public function setSite($site)
    {
        if (is_null($site)) {
            throw new \InvalidArgumentException('non-nullable site cannot be null');
        }
        $this->container['site'] = $site;

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
     * @param int|null $siteid siteid
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
     * Gets adspace_type
     *
     * @return string|null
     */
    public function getAdspaceType()
    {
        return $this->container['adspace_type'];
    }

    /**
     * Sets adspace_type
     *
     * @param string|null $adspace_type Type config key (e.g. bannerads).
     *
     * @return self
     */
    public function setAdspaceType($adspace_type)
    {
        if (is_null($adspace_type)) {
            throw new \InvalidArgumentException('non-nullable adspace_type cannot be null');
        }
        $this->container['adspace_type'] = $adspace_type;

        return $this;
    }

    /**
     * Gets adzone_typeid
     *
     * @return int|null
     */
    public function getAdzoneTypeid()
    {
        return $this->container['adzone_typeid'];
    }

    /**
     * Sets adzone_typeid
     *
     * @param int|null $adzone_typeid adzone_typeid
     *
     * @return self
     */
    public function setAdzoneTypeid($adzone_typeid)
    {
        if (is_null($adzone_typeid)) {
            throw new \InvalidArgumentException('non-nullable adzone_typeid cannot be null');
        }
        $this->container['adzone_typeid'] = $adzone_typeid;

        return $this;
    }

    /**
     * Gets adtype_id
     *
     * @return int|null
     */
    public function getAdtypeId()
    {
        return $this->container['adtype_id'];
    }

    /**
     * Sets adtype_id
     *
     * @param int|null $adtype_id adtype_id
     *
     * @return self
     */
    public function setAdtypeId($adtype_id)
    {
        if (is_null($adtype_id)) {
            throw new \InvalidArgumentException('non-nullable adtype_id cannot be null');
        }
        $this->container['adtype_id'] = $adtype_id;

        return $this;
    }

    /**
     * Gets ad_type
     *
     * @return int|null
     */
    public function getAdType()
    {
        return $this->container['ad_type'];
    }

    /**
     * Sets ad_type
     *
     * @param int|null $ad_type Backward-compat alias for adtype_id.
     *
     * @return self
     */
    public function setAdType($ad_type)
    {
        if (is_null($ad_type)) {
            throw new \InvalidArgumentException('non-nullable ad_type cannot be null');
        }
        $this->container['ad_type'] = $ad_type;

        return $this;
    }

    /**
     * Gets adsizeid
     *
     * @return int|null
     */
    public function getAdsizeid()
    {
        return $this->container['adsizeid'];
    }

    /**
     * Sets adsizeid
     *
     * @param int|null $adsizeid adsizeid
     *
     * @return self
     */
    public function setAdsizeid($adsizeid)
    {
        if (is_null($adsizeid)) {
            throw new \InvalidArgumentException('non-nullable adsizeid cannot be null');
        }
        $this->container['adsizeid'] = $adsizeid;

        return $this;
    }

    /**
     * Gets display_type
     *
     * @return int|null
     */
    public function getDisplayType()
    {
        return $this->container['display_type'];
    }

    /**
     * Sets display_type
     *
     * @param int|null $display_type display_type
     *
     * @return self
     */
    public function setDisplayType($display_type)
    {
        if (is_null($display_type)) {
            throw new \InvalidArgumentException('non-nullable display_type cannot be null');
        }
        $this->container['display_type'] = $display_type;

        return $this;
    }

    /**
     * Gets ratingid
     *
     * @return int|null
     */
    public function getRatingid()
    {
        return $this->container['ratingid'];
    }

    /**
     * Sets ratingid
     *
     * @param int|null $ratingid ratingid
     *
     * @return self
     */
    public function setRatingid($ratingid)
    {
        if (is_null($ratingid)) {
            throw new \InvalidArgumentException('non-nullable ratingid cannot be null');
        }
        $this->container['ratingid'] = $ratingid;

        return $this;
    }

    /**
     * Gets main_catid
     *
     * @return int|null
     */
    public function getMainCatid()
    {
        return $this->container['main_catid'];
    }

    /**
     * Sets main_catid
     *
     * @param int|null $main_catid main_catid
     *
     * @return self
     */
    public function setMainCatid($main_catid)
    {
        if (is_null($main_catid)) {
            throw new \InvalidArgumentException('non-nullable main_catid cannot be null');
        }
        $this->container['main_catid'] = $main_catid;

        return $this;
    }

    /**
     * Gets catid
     *
     * @return int|null
     */
    public function getCatid()
    {
        return $this->container['catid'];
    }

    /**
     * Sets catid
     *
     * @param int|null $catid catid
     *
     * @return self
     */
    public function setCatid($catid)
    {
        if (is_null($catid)) {
            throw new \InvalidArgumentException('non-nullable catid cannot be null');
        }
        $this->container['catid'] = $catid;

        return $this;
    }

    /**
     * Gets locationid
     *
     * @return int|null
     */
    public function getLocationid()
    {
        return $this->container['locationid'];
    }

    /**
     * Sets locationid
     *
     * @param int|null $locationid locationid
     *
     * @return self
     */
    public function setLocationid($locationid)
    {
        if (is_null($locationid)) {
            throw new \InvalidArgumentException('non-nullable locationid cannot be null');
        }
        $this->container['locationid'] = $locationid;

        return $this;
    }

    /**
     * Gets quality_rank
     *
     * @return int|null
     */
    public function getQualityRank()
    {
        return $this->container['quality_rank'];
    }

    /**
     * Sets quality_rank
     *
     * @param int|null $quality_rank quality_rank
     *
     * @return self
     */
    public function setQualityRank($quality_rank)
    {
        if (is_null($quality_rank)) {
            throw new \InvalidArgumentException('non-nullable quality_rank cannot be null');
        }
        $this->container['quality_rank'] = $quality_rank;

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
     * @param bool|null $is_active Derived from publisher_adzones.isinuse — true when at least one campaign-spot link exists.
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
     * Gets autoapprove
     *
     * @return bool|null
     */
    public function getAutoapprove()
    {
        return $this->container['autoapprove'];
    }

    /**
     * Sets autoapprove
     *
     * @param bool|null $autoapprove autoapprove
     *
     * @return self
     */
    public function setAutoapprove($autoapprove)
    {
        if (is_null($autoapprove)) {
            throw new \InvalidArgumentException('non-nullable autoapprove cannot be null');
        }
        $this->container['autoapprove'] = $autoapprove;

        return $this;
    }

    /**
     * Gets isvisible
     *
     * @return bool|null
     */
    public function getIsvisible()
    {
        return $this->container['isvisible'];
    }

    /**
     * Sets isvisible
     *
     * @param bool|null $isvisible isvisible
     *
     * @return self
     */
    public function setIsvisible($isvisible)
    {
        if (is_null($isvisible)) {
            throw new \InvalidArgumentException('non-nullable isvisible cannot be null');
        }
        $this->container['isvisible'] = $isvisible;

        return $this;
    }

    /**
     * Gets sendmails
     *
     * @return bool|null
     */
    public function getSendmails()
    {
        return $this->container['sendmails'];
    }

    /**
     * Sets sendmails
     *
     * @param bool|null $sendmails sendmails
     *
     * @return self
     */
    public function setSendmails($sendmails)
    {
        if (is_null($sendmails)) {
            throw new \InvalidArgumentException('non-nullable sendmails cannot be null');
        }
        $this->container['sendmails'] = $sendmails;

        return $this;
    }

    /**
     * Gets useownbanners
     *
     * @return bool|null
     */
    public function getUseownbanners()
    {
        return $this->container['useownbanners'];
    }

    /**
     * Sets useownbanners
     *
     * @param bool|null $useownbanners useownbanners
     *
     * @return self
     */
    public function setUseownbanners($useownbanners)
    {
        if (is_null($useownbanners)) {
            throw new \InvalidArgumentException('non-nullable useownbanners cannot be null');
        }
        $this->container['useownbanners'] = $useownbanners;

        return $this;
    }

    /**
     * Gets usealtbanner
     *
     * @return bool|null
     */
    public function getUsealtbanner()
    {
        return $this->container['usealtbanner'];
    }

    /**
     * Sets usealtbanner
     *
     * @param bool|null $usealtbanner usealtbanner
     *
     * @return self
     */
    public function setUsealtbanner($usealtbanner)
    {
        if (is_null($usealtbanner)) {
            throw new \InvalidArgumentException('non-nullable usealtbanner cannot be null');
        }
        $this->container['usealtbanner'] = $usealtbanner;

        return $this;
    }

    /**
     * Gets usecpmbuys
     *
     * @return bool|null
     */
    public function getUsecpmbuys()
    {
        return $this->container['usecpmbuys'];
    }

    /**
     * Sets usecpmbuys
     *
     * @param bool|null $usecpmbuys usecpmbuys
     *
     * @return self
     */
    public function setUsecpmbuys($usecpmbuys)
    {
        if (is_null($usecpmbuys)) {
            throw new \InvalidArgumentException('non-nullable usecpmbuys cannot be null');
        }
        $this->container['usecpmbuys'] = $usecpmbuys;

        return $this;
    }

    /**
     * Gets allow_iframes
     *
     * @return bool|null
     */
    public function getAllowIframes()
    {
        return $this->container['allow_iframes'];
    }

    /**
     * Sets allow_iframes
     *
     * @param bool|null $allow_iframes allow_iframes
     *
     * @return self
     */
    public function setAllowIframes($allow_iframes)
    {
        if (is_null($allow_iframes)) {
            throw new \InvalidArgumentException('non-nullable allow_iframes cannot be null');
        }
        $this->container['allow_iframes'] = $allow_iframes;

        return $this;
    }

    /**
     * Gets allow_animation
     *
     * @return bool|null
     */
    public function getAllowAnimation()
    {
        return $this->container['allow_animation'];
    }

    /**
     * Sets allow_animation
     *
     * @param bool|null $allow_animation allow_animation
     *
     * @return self
     */
    public function setAllowAnimation($allow_animation)
    {
        if (is_null($allow_animation)) {
            throw new \InvalidArgumentException('non-nullable allow_animation cannot be null');
        }
        $this->container['allow_animation'] = $allow_animation;

        return $this;
    }

    /**
     * Gets run_rtb_after_smartzone
     *
     * @return bool|null
     */
    public function getRunRtbAfterSmartzone()
    {
        return $this->container['run_rtb_after_smartzone'];
    }

    /**
     * Sets run_rtb_after_smartzone
     *
     * @param bool|null $run_rtb_after_smartzone run_rtb_after_smartzone
     *
     * @return self
     */
    public function setRunRtbAfterSmartzone($run_rtb_after_smartzone)
    {
        if (is_null($run_rtb_after_smartzone)) {
            throw new \InvalidArgumentException('non-nullable run_rtb_after_smartzone cannot be null');
        }
        $this->container['run_rtb_after_smartzone'] = $run_rtb_after_smartzone;

        return $this;
    }

    /**
     * Gets use_pixel_log
     *
     * @return bool|null
     */
    public function getUsePixelLog()
    {
        return $this->container['use_pixel_log'];
    }

    /**
     * Sets use_pixel_log
     *
     * @param bool|null $use_pixel_log use_pixel_log
     *
     * @return self
     */
    public function setUsePixelLog($use_pixel_log)
    {
        if (is_null($use_pixel_log)) {
            throw new \InvalidArgumentException('non-nullable use_pixel_log cannot be null');
        }
        $this->container['use_pixel_log'] = $use_pixel_log;

        return $this;
    }

    /**
     * Gets show_about
     *
     * @return bool|null
     */
    public function getShowAbout()
    {
        return $this->container['show_about'];
    }

    /**
     * Sets show_about
     *
     * @param bool|null $show_about show_about
     *
     * @return self
     */
    public function setShowAbout($show_about)
    {
        if (is_null($show_about)) {
            throw new \InvalidArgumentException('non-nullable show_about cannot be null');
        }
        $this->container['show_about'] = $show_about;

        return $this;
    }

    /**
     * Gets skip_ron_if_cpm
     *
     * @return bool|null
     */
    public function getSkipRonIfCpm()
    {
        return $this->container['skip_ron_if_cpm'];
    }

    /**
     * Sets skip_ron_if_cpm
     *
     * @param bool|null $skip_ron_if_cpm skip_ron_if_cpm
     *
     * @return self
     */
    public function setSkipRonIfCpm($skip_ron_if_cpm)
    {
        if (is_null($skip_ron_if_cpm)) {
            throw new \InvalidArgumentException('non-nullable skip_ron_if_cpm cannot be null');
        }
        $this->container['skip_ron_if_cpm'] = $skip_ron_if_cpm;

        return $this;
    }

    /**
     * Gets no_promotion
     *
     * @return bool|null
     */
    public function getNoPromotion()
    {
        return $this->container['no_promotion'];
    }

    /**
     * Sets no_promotion
     *
     * @param bool|null $no_promotion no_promotion
     *
     * @return self
     */
    public function setNoPromotion($no_promotion)
    {
        if (is_null($no_promotion)) {
            throw new \InvalidArgumentException('non-nullable no_promotion cannot be null');
        }
        $this->container['no_promotion'] = $no_promotion;

        return $this;
    }

    /**
     * Gets hidecopy
     *
     * @return bool|null
     */
    public function getHidecopy()
    {
        return $this->container['hidecopy'];
    }

    /**
     * Sets hidecopy
     *
     * @param bool|null $hidecopy hidecopy
     *
     * @return self
     */
    public function setHidecopy($hidecopy)
    {
        if (is_null($hidecopy)) {
            throw new \InvalidArgumentException('non-nullable hidecopy cannot be null');
        }
        $this->container['hidecopy'] = $hidecopy;

        return $this;
    }

    /**
     * Gets use_subid_list
     *
     * @return bool|null
     */
    public function getUseSubidList()
    {
        return $this->container['use_subid_list'];
    }

    /**
     * Sets use_subid_list
     *
     * @param bool|null $use_subid_list use_subid_list
     *
     * @return self
     */
    public function setUseSubidList($use_subid_list)
    {
        if (is_null($use_subid_list)) {
            throw new \InvalidArgumentException('non-nullable use_subid_list cannot be null');
        }
        $this->container['use_subid_list'] = $use_subid_list;

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
     * @param string|null $subid_list subid_list
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
     * Gets use_domain_list
     *
     * @return bool|null
     */
    public function getUseDomainList()
    {
        return $this->container['use_domain_list'];
    }

    /**
     * Sets use_domain_list
     *
     * @param bool|null $use_domain_list use_domain_list
     *
     * @return self
     */
    public function setUseDomainList($use_domain_list)
    {
        if (is_null($use_domain_list)) {
            throw new \InvalidArgumentException('non-nullable use_domain_list cannot be null');
        }
        $this->container['use_domain_list'] = $use_domain_list;

        return $this;
    }

    /**
     * Gets domain_list
     *
     * @return string[]|null
     */
    public function getDomainList()
    {
        return $this->container['domain_list'];
    }

    /**
     * Sets domain_list
     *
     * @param string[]|null $domain_list domain_list
     *
     * @return self
     */
    public function setDomainList($domain_list)
    {
        if (is_null($domain_list)) {
            throw new \InvalidArgumentException('non-nullable domain_list cannot be null');
        }
        $this->container['domain_list'] = $domain_list;

        return $this;
    }

    /**
     * Gets use_costs
     *
     * @return bool|null
     */
    public function getUseCosts()
    {
        return $this->container['use_costs'];
    }

    /**
     * Sets use_costs
     *
     * @param bool|null $use_costs use_costs
     *
     * @return self
     */
    public function setUseCosts($use_costs)
    {
        if (is_null($use_costs)) {
            throw new \InvalidArgumentException('non-nullable use_costs cannot be null');
        }
        $this->container['use_costs'] = $use_costs;

        return $this;
    }

    /**
     * Gets costs_data
     *
     * @return array<string,mixed>|null
     */
    public function getCostsData()
    {
        return $this->container['costs_data'];
    }

    /**
     * Sets costs_data
     *
     * @param array<string,mixed>|null $costs_data costs_data
     *
     * @return self
     */
    public function setCostsData($costs_data)
    {
        if (is_null($costs_data)) {
            throw new \InvalidArgumentException('non-nullable costs_data cannot be null');
        }
        $this->container['costs_data'] = $costs_data;

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
     * @param bool|null $use_dynamic_costs use_dynamic_costs
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
     * Gets use_fixed_payouts
     *
     * @return bool|null
     */
    public function getUseFixedPayouts()
    {
        return $this->container['use_fixed_payouts'];
    }

    /**
     * Sets use_fixed_payouts
     *
     * @param bool|null $use_fixed_payouts use_fixed_payouts
     *
     * @return self
     */
    public function setUseFixedPayouts($use_fixed_payouts)
    {
        if (is_null($use_fixed_payouts)) {
            throw new \InvalidArgumentException('non-nullable use_fixed_payouts cannot be null');
        }
        $this->container['use_fixed_payouts'] = $use_fixed_payouts;

        return $this;
    }

    /**
     * Gets fixed_payout_data
     *
     * @return array<string,mixed>|null
     */
    public function getFixedPayoutData()
    {
        return $this->container['fixed_payout_data'];
    }

    /**
     * Sets fixed_payout_data
     *
     * @param array<string,mixed>|null $fixed_payout_data fixed_payout_data
     *
     * @return self
     */
    public function setFixedPayoutData($fixed_payout_data)
    {
        if (is_null($fixed_payout_data)) {
            throw new \InvalidArgumentException('non-nullable fixed_payout_data cannot be null');
        }
        $this->container['fixed_payout_data'] = $fixed_payout_data;

        return $this;
    }

    /**
     * Gets use_fallback_campaign
     *
     * @return bool|null
     */
    public function getUseFallbackCampaign()
    {
        return $this->container['use_fallback_campaign'];
    }

    /**
     * Sets use_fallback_campaign
     *
     * @param bool|null $use_fallback_campaign use_fallback_campaign
     *
     * @return self
     */
    public function setUseFallbackCampaign($use_fallback_campaign)
    {
        if (is_null($use_fallback_campaign)) {
            throw new \InvalidArgumentException('non-nullable use_fallback_campaign cannot be null');
        }
        $this->container['use_fallback_campaign'] = $use_fallback_campaign;

        return $this;
    }

    /**
     * Gets fallback_campaignid
     *
     * @return int|null
     */
    public function getFallbackCampaignid()
    {
        return $this->container['fallback_campaignid'];
    }

    /**
     * Sets fallback_campaignid
     *
     * @param int|null $fallback_campaignid fallback_campaignid
     *
     * @return self
     */
    public function setFallbackCampaignid($fallback_campaignid)
    {
        if (is_null($fallback_campaignid)) {
            throw new \InvalidArgumentException('non-nullable fallback_campaignid cannot be null');
        }
        $this->container['fallback_campaignid'] = $fallback_campaignid;

        return $this;
    }

    /**
     * Gets excl_catids
     *
     * @return string|null
     */
    public function getExclCatids()
    {
        return $this->container['excl_catids'];
    }

    /**
     * Sets excl_catids
     *
     * @param string|null $excl_catids excl_catids
     *
     * @return self
     */
    public function setExclCatids($excl_catids)
    {
        if (is_null($excl_catids)) {
            throw new \InvalidArgumentException('non-nullable excl_catids cannot be null');
        }
        $this->container['excl_catids'] = $excl_catids;

        return $this;
    }

    /**
     * Gets exclproperties
     *
     * @return string|null
     */
    public function getExclproperties()
    {
        return $this->container['exclproperties'];
    }

    /**
     * Sets exclproperties
     *
     * @param string|null $exclproperties exclproperties
     *
     * @return self
     */
    public function setExclproperties($exclproperties)
    {
        if (is_null($exclproperties)) {
            throw new \InvalidArgumentException('non-nullable exclproperties cannot be null');
        }
        $this->container['exclproperties'] = $exclproperties;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return int[]|null
     */
    public function getTags()
    {
        return $this->container['tags'];
    }

    /**
     * Sets tags
     *
     * @param int[]|null $tags tags
     *
     * @return self
     */
    public function setTags($tags)
    {
        if (is_null($tags)) {
            throw new \InvalidArgumentException('non-nullable tags cannot be null');
        }
        $this->container['tags'] = $tags;

        return $this;
    }

    /**
     * Gets rtb_name
     *
     * @return string|null
     */
    public function getRtbName()
    {
        return $this->container['rtb_name'];
    }

    /**
     * Sets rtb_name
     *
     * @param string|null $rtb_name rtb_name
     *
     * @return self
     */
    public function setRtbName($rtb_name)
    {
        if (is_null($rtb_name)) {
            throw new \InvalidArgumentException('non-nullable rtb_name cannot be null');
        }
        $this->container['rtb_name'] = $rtb_name;

        return $this;
    }

    /**
     * Gets date_added
     *
     * @return \DateTime|null
     */
    public function getDateAdded()
    {
        return $this->container['date_added'];
    }

    /**
     * Sets date_added
     *
     * @param \DateTime|null $date_added date_added
     *
     * @return self
     */
    public function setDateAdded($date_added)
    {
        if (is_null($date_added)) {
            throw new \InvalidArgumentException('non-nullable date_added cannot be null');
        }
        $this->container['date_added'] = $date_added;

        return $this;
    }

    /**
     * Gets time_changed
     *
     * @return int|null
     */
    public function getTimeChanged()
    {
        return $this->container['time_changed'];
    }

    /**
     * Sets time_changed
     *
     * @param int|null $time_changed Unix seconds of last update.
     *
     * @return self
     */
    public function setTimeChanged($time_changed)
    {
        if (is_null($time_changed)) {
            throw new \InvalidArgumentException('non-nullable time_changed cannot be null');
        }
        $this->container['time_changed'] = $time_changed;

        return $this;
    }

    /**
     * Gets useflatbuys
     *
     * @return bool|null
     */
    public function getUseflatbuys()
    {
        return $this->container['useflatbuys'];
    }

    /**
     * Sets useflatbuys
     *
     * @param bool|null $useflatbuys useflatbuys
     *
     * @return self
     */
    public function setUseflatbuys($useflatbuys)
    {
        if (is_null($useflatbuys)) {
            throw new \InvalidArgumentException('non-nullable useflatbuys cannot be null');
        }
        $this->container['useflatbuys'] = $useflatbuys;

        return $this;
    }

    /**
     * Gets only_wl_campaigns
     *
     * @return bool|null
     */
    public function getOnlyWlCampaigns()
    {
        return $this->container['only_wl_campaigns'];
    }

    /**
     * Sets only_wl_campaigns
     *
     * @param bool|null $only_wl_campaigns only_wl_campaigns
     *
     * @return self
     */
    public function setOnlyWlCampaigns($only_wl_campaigns)
    {
        if (is_null($only_wl_campaigns)) {
            throw new \InvalidArgumentException('non-nullable only_wl_campaigns cannot be null');
        }
        $this->container['only_wl_campaigns'] = $only_wl_campaigns;

        return $this;
    }

    /**
     * Gets only_tagged_campaigns
     *
     * @return bool|null
     */
    public function getOnlyTaggedCampaigns()
    {
        return $this->container['only_tagged_campaigns'];
    }

    /**
     * Sets only_tagged_campaigns
     *
     * @param bool|null $only_tagged_campaigns only_tagged_campaigns
     *
     * @return self
     */
    public function setOnlyTaggedCampaigns($only_tagged_campaigns)
    {
        if (is_null($only_tagged_campaigns)) {
            throw new \InvalidArgumentException('non-nullable only_tagged_campaigns cannot be null');
        }
        $this->container['only_tagged_campaigns'] = $only_tagged_campaigns;

        return $this;
    }

    /**
     * Gets force_wl_targeting
     *
     * @return bool|null
     */
    public function getForceWlTargeting()
    {
        return $this->container['force_wl_targeting'];
    }

    /**
     * Sets force_wl_targeting
     *
     * @param bool|null $force_wl_targeting force_wl_targeting
     *
     * @return self
     */
    public function setForceWlTargeting($force_wl_targeting)
    {
        if (is_null($force_wl_targeting)) {
            throw new \InvalidArgumentException('non-nullable force_wl_targeting cannot be null');
        }
        $this->container['force_wl_targeting'] = $force_wl_targeting;

        return $this;
    }

    /**
     * Gets skip_rtb_when_wl
     *
     * @return bool|null
     */
    public function getSkipRtbWhenWl()
    {
        return $this->container['skip_rtb_when_wl'];
    }

    /**
     * Sets skip_rtb_when_wl
     *
     * @param bool|null $skip_rtb_when_wl From json_settings.options.
     *
     * @return self
     */
    public function setSkipRtbWhenWl($skip_rtb_when_wl)
    {
        if (is_null($skip_rtb_when_wl)) {
            throw new \InvalidArgumentException('non-nullable skip_rtb_when_wl cannot be null');
        }
        $this->container['skip_rtb_when_wl'] = $skip_rtb_when_wl;

        return $this;
    }

    /**
     * Gets parentid
     *
     * @return int|null
     */
    public function getParentid()
    {
        return $this->container['parentid'];
    }

    /**
     * Sets parentid
     *
     * @param int|null $parentid parentid
     *
     * @return self
     */
    public function setParentid($parentid)
    {
        if (is_null($parentid)) {
            throw new \InvalidArgumentException('non-nullable parentid cannot be null');
        }
        $this->container['parentid'] = $parentid;

        return $this;
    }

    /**
     * Gets new_zoneid
     *
     * @return int|null
     */
    public function getNewZoneid()
    {
        return $this->container['new_zoneid'];
    }

    /**
     * Sets new_zoneid
     *
     * @param int|null $new_zoneid new_zoneid
     *
     * @return self
     */
    public function setNewZoneid($new_zoneid)
    {
        if (is_null($new_zoneid)) {
            throw new \InvalidArgumentException('non-nullable new_zoneid cannot be null');
        }
        $this->container['new_zoneid'] = $new_zoneid;

        return $this;
    }

    /**
     * Gets network_binding
     *
     * @return \GoAdServerApi\Model\AdspaceNetworkBinding|null
     */
    public function getNetworkBinding()
    {
        return $this->container['network_binding'];
    }

    /**
     * Sets network_binding
     *
     * @param \GoAdServerApi\Model\AdspaceNetworkBinding|null $network_binding network_binding
     *
     * @return self
     */
    public function setNetworkBinding($network_binding)
    {
        if (is_null($network_binding)) {
            throw new \InvalidArgumentException('non-nullable network_binding cannot be null');
        }
        $this->container['network_binding'] = $network_binding;

        return $this;
    }

    /**
     * Gets json_settings
     *
     * @return array<string,mixed>|null
     */
    public function getJsonSettings()
    {
        return $this->container['json_settings'];
    }

    /**
     * Sets json_settings
     *
     * @param array<string,mixed>|null $json_settings Raw assembled blob — unioned hoisted to top level for convenience.
     *
     * @return self
     */
    public function setJsonSettings($json_settings)
    {
        if (is_null($json_settings)) {
            throw new \InvalidArgumentException('non-nullable json_settings cannot be null');
        }
        $this->container['json_settings'] = $json_settings;

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



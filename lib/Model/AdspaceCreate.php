<?php
/**
 * AdspaceCreate
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
 * AdspaceCreate Class Doc Comment
 *
 * @category Class
 * @description Adspace create body. The fields below are the common top-level keys every type accepts; the type-specific settings block goes under the body key returned in &#x60;settings_block.name&#x60; from &#x60;GET /api/v1/adspaces/{type}/_schema&#x60; (e.g. &#x60;banner_settings&#x60;, &#x60;pop_settings&#x60;, &#x60;native_settings&#x60;, &#x60;player_settings&#x60;).  Per-type details — including which &#x60;*_settings&#x60; block to send and the legal fields inside it — live at the runtime &#x60;_schema&#x60; endpoint, since they vary by tenant config.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class AdspaceCreate implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'AdspaceCreate';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'siteid' => 'int',
        'description' => 'string',
        'ratingid' => 'int',
        'main_catid' => 'int',
        'catid' => 'int',
        'locationid' => 'int',
        'adsizeid' => 'int',
        'autoapprove' => 'bool',
        'isvisible' => 'bool',
        'sendmails' => 'bool',
        'useownbanners' => 'bool',
        'usealtbanner' => 'bool',
        'usecpmbuys' => 'bool',
        'allow_iframes' => 'bool',
        'allow_animation' => 'bool',
        'skip_ron_if_cpm' => 'bool',
        'no_promotion' => 'bool',
        'run_rtb_after_smartzone' => 'bool',
        'use_pixel_log' => 'bool',
        'show_about' => 'bool',
        'hidecopy' => 'bool',
        'hideadsby' => 'bool',
        'useflatbuys' => 'bool',
        'parentid' => 'int',
        'display_type' => 'int',
        'new_zoneid' => 'int',
        'only_wl_campaigns' => 'bool',
        'only_tagged_campaigns' => 'bool',
        'force_wl_targeting' => 'bool',
        'skip_rtb_when_wl' => 'bool',
        'use_fixed_payouts' => 'bool',
        'fixed_payouts' => 'bool',
        'fixed_payout_data' => 'array<string,mixed>',
        'rtb_name' => 'string',
        'tags' => 'int[]',
        'excl_catids' => 'string',
        'exclproperties' => 'string',
        'use_subid_list' => 'bool',
        'subid_list' => 'string',
        'use_domain_list' => 'bool',
        'domain_list' => 'string[]',
        'use_costs' => 'bool',
        'costs_data' => 'array<string,mixed>',
        'use_dynamic_costs' => 'bool',
        'use_fallback_campaign' => 'bool',
        'fallback_campaignid' => 'int',
        'quality_rank' => 'int',
        'banner_settings' => '\GoAdServerApi\Model\AdspaceCreateBannerSettings',
        'native_settings' => 'array<string,mixed>',
        'search_settings' => '\GoAdServerApi\Model\AdspaceCreateSearchSettings',
        'email_settings' => '\GoAdServerApi\Model\AdspaceCreateEmailSettings',
        'bot_settings' => '\GoAdServerApi\Model\AdspaceCreateBotSettings',
        'screen_settings' => '\GoAdServerApi\Model\AdspaceCreateScreenSettings',
        'player_settings' => 'array<string,mixed>',
        'pop_settings' => 'array<string,mixed>',
        'fpa_settings' => 'array<string,mixed>',
        'redirect_settings' => 'array<string,mixed>',
        'im_settings' => 'array<string,mixed>',
        'fixim_banner_settings' => 'array<string,mixed>',
        'native_bar_settings' => 'array<string,mixed>',
        'native_webpush_settings' => 'array<string,mixed>',
        'text_settings' => 'array<string,mixed>',
        'floater_settings' => 'array<string,mixed>',
        'videoslider_settings' => 'array<string,mixed>',
        'invideo_settings' => 'array<string,mixed>',
        'dyn_adsettings' => 'array<string,mixed>',
        'inpage_videosettings' => 'array<string,mixed>',
        'carousel_videosettings' => 'array<string,mixed>'
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
        'siteid' => null,
        'description' => null,
        'ratingid' => null,
        'main_catid' => null,
        'catid' => null,
        'locationid' => null,
        'adsizeid' => null,
        'autoapprove' => null,
        'isvisible' => null,
        'sendmails' => null,
        'useownbanners' => null,
        'usealtbanner' => null,
        'usecpmbuys' => null,
        'allow_iframes' => null,
        'allow_animation' => null,
        'skip_ron_if_cpm' => null,
        'no_promotion' => null,
        'run_rtb_after_smartzone' => null,
        'use_pixel_log' => null,
        'show_about' => null,
        'hidecopy' => null,
        'hideadsby' => null,
        'useflatbuys' => null,
        'parentid' => null,
        'display_type' => null,
        'new_zoneid' => null,
        'only_wl_campaigns' => null,
        'only_tagged_campaigns' => null,
        'force_wl_targeting' => null,
        'skip_rtb_when_wl' => null,
        'use_fixed_payouts' => null,
        'fixed_payouts' => null,
        'fixed_payout_data' => null,
        'rtb_name' => null,
        'tags' => null,
        'excl_catids' => null,
        'exclproperties' => null,
        'use_subid_list' => null,
        'subid_list' => null,
        'use_domain_list' => null,
        'domain_list' => null,
        'use_costs' => null,
        'costs_data' => null,
        'use_dynamic_costs' => null,
        'use_fallback_campaign' => null,
        'fallback_campaignid' => null,
        'quality_rank' => null,
        'banner_settings' => null,
        'native_settings' => null,
        'search_settings' => null,
        'email_settings' => null,
        'bot_settings' => null,
        'screen_settings' => null,
        'player_settings' => null,
        'pop_settings' => null,
        'fpa_settings' => null,
        'redirect_settings' => null,
        'im_settings' => null,
        'fixim_banner_settings' => null,
        'native_bar_settings' => null,
        'native_webpush_settings' => null,
        'text_settings' => null,
        'floater_settings' => null,
        'videoslider_settings' => null,
        'invideo_settings' => null,
        'dyn_adsettings' => null,
        'inpage_videosettings' => null,
        'carousel_videosettings' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'siteid' => false,
        'description' => false,
        'ratingid' => false,
        'main_catid' => false,
        'catid' => false,
        'locationid' => false,
        'adsizeid' => false,
        'autoapprove' => false,
        'isvisible' => false,
        'sendmails' => false,
        'useownbanners' => false,
        'usealtbanner' => false,
        'usecpmbuys' => false,
        'allow_iframes' => false,
        'allow_animation' => false,
        'skip_ron_if_cpm' => false,
        'no_promotion' => false,
        'run_rtb_after_smartzone' => false,
        'use_pixel_log' => false,
        'show_about' => false,
        'hidecopy' => false,
        'hideadsby' => false,
        'useflatbuys' => false,
        'parentid' => false,
        'display_type' => false,
        'new_zoneid' => false,
        'only_wl_campaigns' => false,
        'only_tagged_campaigns' => false,
        'force_wl_targeting' => false,
        'skip_rtb_when_wl' => false,
        'use_fixed_payouts' => false,
        'fixed_payouts' => false,
        'fixed_payout_data' => false,
        'rtb_name' => false,
        'tags' => false,
        'excl_catids' => false,
        'exclproperties' => false,
        'use_subid_list' => false,
        'subid_list' => false,
        'use_domain_list' => false,
        'domain_list' => false,
        'use_costs' => false,
        'costs_data' => false,
        'use_dynamic_costs' => false,
        'use_fallback_campaign' => false,
        'fallback_campaignid' => false,
        'quality_rank' => false,
        'banner_settings' => false,
        'native_settings' => false,
        'search_settings' => false,
        'email_settings' => false,
        'bot_settings' => false,
        'screen_settings' => false,
        'player_settings' => false,
        'pop_settings' => false,
        'fpa_settings' => false,
        'redirect_settings' => false,
        'im_settings' => false,
        'fixim_banner_settings' => false,
        'native_bar_settings' => false,
        'native_webpush_settings' => false,
        'text_settings' => false,
        'floater_settings' => false,
        'videoslider_settings' => false,
        'invideo_settings' => false,
        'dyn_adsettings' => false,
        'inpage_videosettings' => false,
        'carousel_videosettings' => false
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
        'siteid' => 'siteid',
        'description' => 'description',
        'ratingid' => 'ratingid',
        'main_catid' => 'main_catid',
        'catid' => 'catid',
        'locationid' => 'locationid',
        'adsizeid' => 'adsizeid',
        'autoapprove' => 'autoapprove',
        'isvisible' => 'isvisible',
        'sendmails' => 'sendmails',
        'useownbanners' => 'useownbanners',
        'usealtbanner' => 'usealtbanner',
        'usecpmbuys' => 'usecpmbuys',
        'allow_iframes' => 'allow_iframes',
        'allow_animation' => 'allow_animation',
        'skip_ron_if_cpm' => 'skip_ron_if_cpm',
        'no_promotion' => 'no_promotion',
        'run_rtb_after_smartzone' => 'run_rtb_after_smartzone',
        'use_pixel_log' => 'use_pixel_log',
        'show_about' => 'show_about',
        'hidecopy' => 'hidecopy',
        'hideadsby' => 'hideadsby',
        'useflatbuys' => 'useflatbuys',
        'parentid' => 'parentid',
        'display_type' => 'display_type',
        'new_zoneid' => 'new_zoneid',
        'only_wl_campaigns' => 'only_wl_campaigns',
        'only_tagged_campaigns' => 'only_tagged_campaigns',
        'force_wl_targeting' => 'force_wl_targeting',
        'skip_rtb_when_wl' => 'skip_rtb_when_wl',
        'use_fixed_payouts' => 'use_fixed_payouts',
        'fixed_payouts' => 'fixed_payouts',
        'fixed_payout_data' => 'fixed_payout_data',
        'rtb_name' => 'rtb_name',
        'tags' => 'tags',
        'excl_catids' => 'excl_catids',
        'exclproperties' => 'exclproperties',
        'use_subid_list' => 'use_subid_list',
        'subid_list' => 'subid_list',
        'use_domain_list' => 'use_domain_list',
        'domain_list' => 'domain_list',
        'use_costs' => 'use_costs',
        'costs_data' => 'costs_data',
        'use_dynamic_costs' => 'use_dynamic_costs',
        'use_fallback_campaign' => 'use_fallback_campaign',
        'fallback_campaignid' => 'fallback_campaignid',
        'quality_rank' => 'quality_rank',
        'banner_settings' => 'banner_settings',
        'native_settings' => 'native_settings',
        'search_settings' => 'search_settings',
        'email_settings' => 'email_settings',
        'bot_settings' => 'bot_settings',
        'screen_settings' => 'screen_settings',
        'player_settings' => 'player_settings',
        'pop_settings' => 'pop_settings',
        'fpa_settings' => 'fpa_settings',
        'redirect_settings' => 'redirect_settings',
        'im_settings' => 'im_settings',
        'fixim_banner_settings' => 'fixim_banner_settings',
        'native_bar_settings' => 'native_bar_settings',
        'native_webpush_settings' => 'native_webpush_settings',
        'text_settings' => 'text_settings',
        'floater_settings' => 'floater_settings',
        'videoslider_settings' => 'videoslider_settings',
        'invideo_settings' => 'invideo_settings',
        'dyn_adsettings' => 'dyn_adsettings',
        'inpage_videosettings' => 'inpage_videosettings',
        'carousel_videosettings' => 'carousel_videosettings'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'siteid' => 'setSiteid',
        'description' => 'setDescription',
        'ratingid' => 'setRatingid',
        'main_catid' => 'setMainCatid',
        'catid' => 'setCatid',
        'locationid' => 'setLocationid',
        'adsizeid' => 'setAdsizeid',
        'autoapprove' => 'setAutoapprove',
        'isvisible' => 'setIsvisible',
        'sendmails' => 'setSendmails',
        'useownbanners' => 'setUseownbanners',
        'usealtbanner' => 'setUsealtbanner',
        'usecpmbuys' => 'setUsecpmbuys',
        'allow_iframes' => 'setAllowIframes',
        'allow_animation' => 'setAllowAnimation',
        'skip_ron_if_cpm' => 'setSkipRonIfCpm',
        'no_promotion' => 'setNoPromotion',
        'run_rtb_after_smartzone' => 'setRunRtbAfterSmartzone',
        'use_pixel_log' => 'setUsePixelLog',
        'show_about' => 'setShowAbout',
        'hidecopy' => 'setHidecopy',
        'hideadsby' => 'setHideadsby',
        'useflatbuys' => 'setUseflatbuys',
        'parentid' => 'setParentid',
        'display_type' => 'setDisplayType',
        'new_zoneid' => 'setNewZoneid',
        'only_wl_campaigns' => 'setOnlyWlCampaigns',
        'only_tagged_campaigns' => 'setOnlyTaggedCampaigns',
        'force_wl_targeting' => 'setForceWlTargeting',
        'skip_rtb_when_wl' => 'setSkipRtbWhenWl',
        'use_fixed_payouts' => 'setUseFixedPayouts',
        'fixed_payouts' => 'setFixedPayouts',
        'fixed_payout_data' => 'setFixedPayoutData',
        'rtb_name' => 'setRtbName',
        'tags' => 'setTags',
        'excl_catids' => 'setExclCatids',
        'exclproperties' => 'setExclproperties',
        'use_subid_list' => 'setUseSubidList',
        'subid_list' => 'setSubidList',
        'use_domain_list' => 'setUseDomainList',
        'domain_list' => 'setDomainList',
        'use_costs' => 'setUseCosts',
        'costs_data' => 'setCostsData',
        'use_dynamic_costs' => 'setUseDynamicCosts',
        'use_fallback_campaign' => 'setUseFallbackCampaign',
        'fallback_campaignid' => 'setFallbackCampaignid',
        'quality_rank' => 'setQualityRank',
        'banner_settings' => 'setBannerSettings',
        'native_settings' => 'setNativeSettings',
        'search_settings' => 'setSearchSettings',
        'email_settings' => 'setEmailSettings',
        'bot_settings' => 'setBotSettings',
        'screen_settings' => 'setScreenSettings',
        'player_settings' => 'setPlayerSettings',
        'pop_settings' => 'setPopSettings',
        'fpa_settings' => 'setFpaSettings',
        'redirect_settings' => 'setRedirectSettings',
        'im_settings' => 'setImSettings',
        'fixim_banner_settings' => 'setFiximBannerSettings',
        'native_bar_settings' => 'setNativeBarSettings',
        'native_webpush_settings' => 'setNativeWebpushSettings',
        'text_settings' => 'setTextSettings',
        'floater_settings' => 'setFloaterSettings',
        'videoslider_settings' => 'setVideosliderSettings',
        'invideo_settings' => 'setInvideoSettings',
        'dyn_adsettings' => 'setDynAdsettings',
        'inpage_videosettings' => 'setInpageVideosettings',
        'carousel_videosettings' => 'setCarouselVideosettings'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'siteid' => 'getSiteid',
        'description' => 'getDescription',
        'ratingid' => 'getRatingid',
        'main_catid' => 'getMainCatid',
        'catid' => 'getCatid',
        'locationid' => 'getLocationid',
        'adsizeid' => 'getAdsizeid',
        'autoapprove' => 'getAutoapprove',
        'isvisible' => 'getIsvisible',
        'sendmails' => 'getSendmails',
        'useownbanners' => 'getUseownbanners',
        'usealtbanner' => 'getUsealtbanner',
        'usecpmbuys' => 'getUsecpmbuys',
        'allow_iframes' => 'getAllowIframes',
        'allow_animation' => 'getAllowAnimation',
        'skip_ron_if_cpm' => 'getSkipRonIfCpm',
        'no_promotion' => 'getNoPromotion',
        'run_rtb_after_smartzone' => 'getRunRtbAfterSmartzone',
        'use_pixel_log' => 'getUsePixelLog',
        'show_about' => 'getShowAbout',
        'hidecopy' => 'getHidecopy',
        'hideadsby' => 'getHideadsby',
        'useflatbuys' => 'getUseflatbuys',
        'parentid' => 'getParentid',
        'display_type' => 'getDisplayType',
        'new_zoneid' => 'getNewZoneid',
        'only_wl_campaigns' => 'getOnlyWlCampaigns',
        'only_tagged_campaigns' => 'getOnlyTaggedCampaigns',
        'force_wl_targeting' => 'getForceWlTargeting',
        'skip_rtb_when_wl' => 'getSkipRtbWhenWl',
        'use_fixed_payouts' => 'getUseFixedPayouts',
        'fixed_payouts' => 'getFixedPayouts',
        'fixed_payout_data' => 'getFixedPayoutData',
        'rtb_name' => 'getRtbName',
        'tags' => 'getTags',
        'excl_catids' => 'getExclCatids',
        'exclproperties' => 'getExclproperties',
        'use_subid_list' => 'getUseSubidList',
        'subid_list' => 'getSubidList',
        'use_domain_list' => 'getUseDomainList',
        'domain_list' => 'getDomainList',
        'use_costs' => 'getUseCosts',
        'costs_data' => 'getCostsData',
        'use_dynamic_costs' => 'getUseDynamicCosts',
        'use_fallback_campaign' => 'getUseFallbackCampaign',
        'fallback_campaignid' => 'getFallbackCampaignid',
        'quality_rank' => 'getQualityRank',
        'banner_settings' => 'getBannerSettings',
        'native_settings' => 'getNativeSettings',
        'search_settings' => 'getSearchSettings',
        'email_settings' => 'getEmailSettings',
        'bot_settings' => 'getBotSettings',
        'screen_settings' => 'getScreenSettings',
        'player_settings' => 'getPlayerSettings',
        'pop_settings' => 'getPopSettings',
        'fpa_settings' => 'getFpaSettings',
        'redirect_settings' => 'getRedirectSettings',
        'im_settings' => 'getImSettings',
        'fixim_banner_settings' => 'getFiximBannerSettings',
        'native_bar_settings' => 'getNativeBarSettings',
        'native_webpush_settings' => 'getNativeWebpushSettings',
        'text_settings' => 'getTextSettings',
        'floater_settings' => 'getFloaterSettings',
        'videoslider_settings' => 'getVideosliderSettings',
        'invideo_settings' => 'getInvideoSettings',
        'dyn_adsettings' => 'getDynAdsettings',
        'inpage_videosettings' => 'getInpageVideosettings',
        'carousel_videosettings' => 'getCarouselVideosettings'
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

    public const DISPLAY_TYPE_NUMBER_0 = 0;
    public const DISPLAY_TYPE_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getDisplayTypeAllowableValues()
    {
        return [
            self::DISPLAY_TYPE_NUMBER_0,
            self::DISPLAY_TYPE_NUMBER_1,
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
        $this->setIfExists('siteid', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('ratingid', $data ?? [], 0);
        $this->setIfExists('main_catid', $data ?? [], 0);
        $this->setIfExists('catid', $data ?? [], 0);
        $this->setIfExists('locationid', $data ?? [], 0);
        $this->setIfExists('adsizeid', $data ?? [], 0);
        $this->setIfExists('autoapprove', $data ?? [], false);
        $this->setIfExists('isvisible', $data ?? [], true);
        $this->setIfExists('sendmails', $data ?? [], false);
        $this->setIfExists('useownbanners', $data ?? [], false);
        $this->setIfExists('usealtbanner', $data ?? [], false);
        $this->setIfExists('usecpmbuys', $data ?? [], true);
        $this->setIfExists('allow_iframes', $data ?? [], true);
        $this->setIfExists('allow_animation', $data ?? [], true);
        $this->setIfExists('skip_ron_if_cpm', $data ?? [], false);
        $this->setIfExists('no_promotion', $data ?? [], false);
        $this->setIfExists('run_rtb_after_smartzone', $data ?? [], false);
        $this->setIfExists('use_pixel_log', $data ?? [], false);
        $this->setIfExists('show_about', $data ?? [], false);
        $this->setIfExists('hidecopy', $data ?? [], false);
        $this->setIfExists('hideadsby', $data ?? [], null);
        $this->setIfExists('useflatbuys', $data ?? [], true);
        $this->setIfExists('parentid', $data ?? [], 0);
        $this->setIfExists('display_type', $data ?? [], self::DISPLAY_TYPE_NUMBER_0);
        $this->setIfExists('new_zoneid', $data ?? [], 0);
        $this->setIfExists('only_wl_campaigns', $data ?? [], false);
        $this->setIfExists('only_tagged_campaigns', $data ?? [], false);
        $this->setIfExists('force_wl_targeting', $data ?? [], false);
        $this->setIfExists('skip_rtb_when_wl', $data ?? [], false);
        $this->setIfExists('use_fixed_payouts', $data ?? [], false);
        $this->setIfExists('fixed_payouts', $data ?? [], null);
        $this->setIfExists('fixed_payout_data', $data ?? [], null);
        $this->setIfExists('rtb_name', $data ?? [], null);
        $this->setIfExists('tags', $data ?? [], null);
        $this->setIfExists('excl_catids', $data ?? [], null);
        $this->setIfExists('exclproperties', $data ?? [], null);
        $this->setIfExists('use_subid_list', $data ?? [], false);
        $this->setIfExists('subid_list', $data ?? [], null);
        $this->setIfExists('use_domain_list', $data ?? [], false);
        $this->setIfExists('domain_list', $data ?? [], null);
        $this->setIfExists('use_costs', $data ?? [], false);
        $this->setIfExists('costs_data', $data ?? [], null);
        $this->setIfExists('use_dynamic_costs', $data ?? [], false);
        $this->setIfExists('use_fallback_campaign', $data ?? [], false);
        $this->setIfExists('fallback_campaignid', $data ?? [], 0);
        $this->setIfExists('quality_rank', $data ?? [], 0);
        $this->setIfExists('banner_settings', $data ?? [], null);
        $this->setIfExists('native_settings', $data ?? [], null);
        $this->setIfExists('search_settings', $data ?? [], null);
        $this->setIfExists('email_settings', $data ?? [], null);
        $this->setIfExists('bot_settings', $data ?? [], null);
        $this->setIfExists('screen_settings', $data ?? [], null);
        $this->setIfExists('player_settings', $data ?? [], null);
        $this->setIfExists('pop_settings', $data ?? [], null);
        $this->setIfExists('fpa_settings', $data ?? [], null);
        $this->setIfExists('redirect_settings', $data ?? [], null);
        $this->setIfExists('im_settings', $data ?? [], null);
        $this->setIfExists('fixim_banner_settings', $data ?? [], null);
        $this->setIfExists('native_bar_settings', $data ?? [], null);
        $this->setIfExists('native_webpush_settings', $data ?? [], null);
        $this->setIfExists('text_settings', $data ?? [], null);
        $this->setIfExists('floater_settings', $data ?? [], null);
        $this->setIfExists('videoslider_settings', $data ?? [], null);
        $this->setIfExists('invideo_settings', $data ?? [], null);
        $this->setIfExists('dyn_adsettings', $data ?? [], null);
        $this->setIfExists('inpage_videosettings', $data ?? [], null);
        $this->setIfExists('carousel_videosettings', $data ?? [], null);
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
        if ($this->container['siteid'] === null) {
            $invalidProperties[] = "'siteid' can't be null";
        }
        $allowedValues = $this->getDisplayTypeAllowableValues();
        if (!is_null($this->container['display_type']) && !in_array($this->container['display_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'display_type', must be one of '%s'",
                $this->container['display_type'],
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
     * Gets siteid
     *
     * @return int
     */
    public function getSiteid()
    {
        return $this->container['siteid'];
    }

    /**
     * Sets siteid
     *
     * @param int $siteid Parent website id from /api/v1/sites.
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
     * @param int|null $adsizeid Banner size id (banner-flavored types only). Also accepted as banner_settings.sizeid.
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
     * @param bool|null $hidecopy Hide the \"Ads by\" copy. Stored as json_settings.hideadsby; `hideadsby` is accepted as an alias and wins when both are sent.
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
     * Gets hideadsby
     *
     * @return bool|null
     */
    public function getHideadsby()
    {
        return $this->container['hideadsby'];
    }

    /**
     * Sets hideadsby
     *
     * @param bool|null $hideadsby Alias of hidecopy (the stored name).
     *
     * @return self
     */
    public function setHideadsby($hideadsby)
    {
        if (is_null($hideadsby)) {
            throw new \InvalidArgumentException('non-nullable hideadsby cannot be null');
        }
        $this->container['hideadsby'] = $hideadsby;

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
     * @param bool|null $useflatbuys Accept flat bookings on this zone.
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
     * @param int|null $parentid Parent ad zone (another live zone of yours) — in-video sub-zones hang under their player zone. 0 = top level.
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
     * @param int|null $display_type Banner zones: 0 = inline, 1 = floater (send floater_settings). Forced to 1 for types 17, 19, 20, 22.
     *
     * @return self
     */
    public function setDisplayType($display_type)
    {
        if (is_null($display_type)) {
            throw new \InvalidArgumentException('non-nullable display_type cannot be null');
        }
        $allowedValues = $this->getDisplayTypeAllowableValues();
        if (!in_array($display_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'display_type', must be one of '%s'",
                    $display_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['display_type'] = $display_type;

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
     * @param int|null $new_zoneid Banner zones: forward unfilled traffic to this ad zone id. 0 = off.
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
     * @param bool|null $only_wl_campaigns Serve only the whitelisted campaigns. Ad Manager tenants only — dropped elsewhere.
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
     * @param bool|null $only_tagged_campaigns Serve only campaigns in the zone's campaign groups (`tags`). Only where campaign-group targeting is enabled — dropped elsewhere.
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
     * @param bool|null $force_wl_targeting Apply the whitelisted campaigns' own targeting. Also written by PUT /wl-campaigns.
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
     * @param bool|null $skip_rtb_when_wl Skip RTB when a whitelisted campaign can fill. Stored in json_settings.options.
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
     * @param bool|null $use_fixed_payouts Fixed payout instead of revenue share (alias `fixed_payouts`). Only where modules.publisher.adzones.options.use_fixed_payouts is on and the tenant is Ad Manager; dropped silently otherwise.
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
     * Gets fixed_payouts
     *
     * @return bool|null
     */
    public function getFixedPayouts()
    {
        return $this->container['fixed_payouts'];
    }

    /**
     * Sets fixed_payouts
     *
     * @param bool|null $fixed_payouts Alias of use_fixed_payouts (the name the panel sends).
     *
     * @return self
     */
    public function setFixedPayouts($fixed_payouts)
    {
        if (is_null($fixed_payouts)) {
            throw new \InvalidArgumentException('non-nullable fixed_payouts cannot be null');
        }
        $this->container['fixed_payouts'] = $fixed_payouts;

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
     * @param array<string,mixed>|null $fixed_payout_data {type: cpc|cpm, amount (currency units), enddate, amounts: per-country overrides}. Only with use_fixed_payouts.
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
     * @param string|null $rtb_name Name the zone is reported under to RTB partners.
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
     * @param int[]|null $tags Campaign-group ids (campaign_groups). Empty = inherit the site's groups.
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
     * @param string|null $excl_catids Comma-separated category ids to exclude.
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
     * @param string|null $subid_list Comma-separated subid allowlist (use_subid_list=true required).
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
     * Gets banner_settings
     *
     * @return \GoAdServerApi\Model\AdspaceCreateBannerSettings|null
     */
    public function getBannerSettings()
    {
        return $this->container['banner_settings'];
    }

    /**
     * Sets banner_settings
     *
     * @param \GoAdServerApi\Model\AdspaceCreateBannerSettings|null $banner_settings banner_settings
     *
     * @return self
     */
    public function setBannerSettings($banner_settings)
    {
        if (is_null($banner_settings)) {
            throw new \InvalidArgumentException('non-nullable banner_settings cannot be null');
        }
        $this->container['banner_settings'] = $banner_settings;

        return $this;
    }

    /**
     * Gets native_settings
     *
     * @return array<string,mixed>|null
     */
    public function getNativeSettings()
    {
        return $this->container['native_settings'];
    }

    /**
     * Sets native_settings
     *
     * @param array<string,mixed>|null $native_settings Native-flavored zones (nativeads, interstitial_native, creator_widget, search_ads, bot_ads — search_ads and bot_ads read only `orientation`). nativeads also takes the infinite content feed: `infinite` (boolean; switches GET /api/v1/adspaces/{id}/code to goCtrl's feed plugin), `feed_rss` (your RSS feed: http(s) URL or a path such as /feed/; anything else 422 `native_settings.feed_rss`), `feed_ad_every` (organic cards before each ad, 0-10, default 2), `feed_max_pages` (1-30, default 10), `feed_title`, `feed_label` (plain text). Ad impressions in the feed are counted when the card is at least half on screen. Full field list: GET /api/v1/adspaces/nativeads/_schema.
     *
     * @return self
     */
    public function setNativeSettings($native_settings)
    {
        if (is_null($native_settings)) {
            throw new \InvalidArgumentException('non-nullable native_settings cannot be null');
        }
        $this->container['native_settings'] = $native_settings;

        return $this;
    }

    /**
     * Gets search_settings
     *
     * @return \GoAdServerApi\Model\AdspaceCreateSearchSettings|null
     */
    public function getSearchSettings()
    {
        return $this->container['search_settings'];
    }

    /**
     * Sets search_settings
     *
     * @param \GoAdServerApi\Model\AdspaceCreateSearchSettings|null $search_settings search_settings
     *
     * @return self
     */
    public function setSearchSettings($search_settings)
    {
        if (is_null($search_settings)) {
            throw new \InvalidArgumentException('non-nullable search_settings cannot be null');
        }
        $this->container['search_settings'] = $search_settings;

        return $this;
    }

    /**
     * Gets email_settings
     *
     * @return \GoAdServerApi\Model\AdspaceCreateEmailSettings|null
     */
    public function getEmailSettings()
    {
        return $this->container['email_settings'];
    }

    /**
     * Sets email_settings
     *
     * @param \GoAdServerApi\Model\AdspaceCreateEmailSettings|null $email_settings email_settings
     *
     * @return self
     */
    public function setEmailSettings($email_settings)
    {
        if (is_null($email_settings)) {
            throw new \InvalidArgumentException('non-nullable email_settings cannot be null');
        }
        $this->container['email_settings'] = $email_settings;

        return $this;
    }

    /**
     * Gets bot_settings
     *
     * @return \GoAdServerApi\Model\AdspaceCreateBotSettings|null
     */
    public function getBotSettings()
    {
        return $this->container['bot_settings'];
    }

    /**
     * Sets bot_settings
     *
     * @param \GoAdServerApi\Model\AdspaceCreateBotSettings|null $bot_settings bot_settings
     *
     * @return self
     */
    public function setBotSettings($bot_settings)
    {
        if (is_null($bot_settings)) {
            throw new \InvalidArgumentException('non-nullable bot_settings cannot be null');
        }
        $this->container['bot_settings'] = $bot_settings;

        return $this;
    }

    /**
     * Gets screen_settings
     *
     * @return \GoAdServerApi\Model\AdspaceCreateScreenSettings|null
     */
    public function getScreenSettings()
    {
        return $this->container['screen_settings'];
    }

    /**
     * Sets screen_settings
     *
     * @param \GoAdServerApi\Model\AdspaceCreateScreenSettings|null $screen_settings screen_settings
     *
     * @return self
     */
    public function setScreenSettings($screen_settings)
    {
        if (is_null($screen_settings)) {
            throw new \InvalidArgumentException('non-nullable screen_settings cannot be null');
        }
        $this->container['screen_settings'] = $screen_settings;

        return $this;
    }

    /**
     * Gets player_settings
     *
     * @return array<string,mixed>|null
     */
    public function getPlayerSettings()
    {
        return $this->container['player_settings'];
    }

    /**
     * Sets player_settings
     *
     * @param array<string,mixed>|null $player_settings Video zones (videoads, interstitial_video, videoslider). videoads also takes rewarded web ads: `rewarded` (boolean; GET /api/v1/adspaces/{id}/code then returns goCtrl's rewarded plugin plus an example button), `reward_min_seconds` (5-120, default 10), `reward_callback` (your server's http(s) URL, GET with {uid} {sid} {campaignid} {ts} {token}; anything else 422 `player_settings.reward_callback`), `reward_secret` (64 hex, generated when rewarded is switched on; the reward token is `<base64url(payload)>.<hex HMAC-SHA256(reward_secret, payload)>`, payload `<sid>.<campaignid>.<uid>.<unix time>`). CTV / streaming (videoads and audioads): `pod_size` (1-6, ads per VAST answer as an ad pod, never the same campaign or advertiser twice; a player can also send &pod=N), `pod_max_seconds` (0-600, 0 = no limit), `midroll_offsets` (up to 20 seconds into the stream, array or \"600, 1200\"; out of range 422), `vmap_no_preroll`, `vmap_postroll`, `pause_zone_id` (0 or one of your own banner zones, else 422 `player_settings.pause_zone_id`), `ssai` (allow server-side ad insertion). The player URLs are in the code answer's `ctv` block. Full field list: GET /api/v1/adspaces/videoads/_schema.
     *
     * @return self
     */
    public function setPlayerSettings($player_settings)
    {
        if (is_null($player_settings)) {
            throw new \InvalidArgumentException('non-nullable player_settings cannot be null');
        }
        $this->container['player_settings'] = $player_settings;

        return $this;
    }

    /**
     * Gets pop_settings
     *
     * @return array<string,mixed>|null
     */
    public function getPopSettings()
    {
        return $this->container['pop_settings'];
    }

    /**
     * Sets pop_settings
     *
     * @param array<string,mixed>|null $pop_settings Pop zones (popads). `fpa_zoneid` is the full-page-ad zone served instead of the pop to visitors whose browser is in `bl_browsers`: 0 = none, otherwise one of YOUR OWN full-page zones (`fullpageads`, type 12) — another publisher's zone, a deleted zone or another type is refused with 422 (`pop_settings.fpa_zoneid`). Full field list: GET /api/v1/adspaces/popads/_schema.
     *
     * @return self
     */
    public function setPopSettings($pop_settings)
    {
        if (is_null($pop_settings)) {
            throw new \InvalidArgumentException('non-nullable pop_settings cannot be null');
        }
        $this->container['pop_settings'] = $pop_settings;

        return $this;
    }

    /**
     * Gets fpa_settings
     *
     * @return array<string,mixed>|null
     */
    public function getFpaSettings()
    {
        return $this->container['fpa_settings'];
    }

    /**
     * Sets fpa_settings
     *
     * @param array<string,mixed>|null $fpa_settings Full-page ad zones (fullpageads). Shown when the visitor clicks a link, before the link opens. `link_scope`: empty = any link, `internal` = vignette (only links to the same site; anything else is refused with 422 `fpa_settings.link_scope`). `start_at` / `repeat_at` count link clicks, `refreshtime` caps it to once per N seconds. Full field list: GET /api/v1/adspaces/fullpageads/_schema.
     *
     * @return self
     */
    public function setFpaSettings($fpa_settings)
    {
        if (is_null($fpa_settings)) {
            throw new \InvalidArgumentException('non-nullable fpa_settings cannot be null');
        }
        $this->container['fpa_settings'] = $fpa_settings;

        return $this;
    }

    /**
     * Gets redirect_settings
     *
     * @return array<string,mixed>|null
     */
    public function getRedirectSettings()
    {
        return $this->container['redirect_settings'];
    }

    /**
     * Sets redirect_settings
     *
     * @param array<string,mixed>|null $redirect_settings Direct-URL zones: redirects (10), legacy types 14/15 and hard links 1-5 (35-39).
     *
     * @return self
     */
    public function setRedirectSettings($redirect_settings)
    {
        if (is_null($redirect_settings)) {
            throw new \InvalidArgumentException('non-nullable redirect_settings cannot be null');
        }
        $this->container['redirect_settings'] = $redirect_settings;

        return $this;
    }

    /**
     * Gets im_settings
     *
     * @return array<string,mixed>|null
     */
    public function getImSettings()
    {
        return $this->container['im_settings'];
    }

    /**
     * Sets im_settings
     *
     * @param array<string,mixed>|null $im_settings In-message popup zones (impopup).
     *
     * @return self
     */
    public function setImSettings($im_settings)
    {
        if (is_null($im_settings)) {
            throw new \InvalidArgumentException('non-nullable im_settings cannot be null');
        }
        $this->container['im_settings'] = $im_settings;

        return $this;
    }

    /**
     * Gets fixim_banner_settings
     *
     * @return array<string,mixed>|null
     */
    public function getFiximBannerSettings()
    {
        return $this->container['fixim_banner_settings'];
    }

    /**
     * Sets fixim_banner_settings
     *
     * @param array<string,mixed>|null $fixim_banner_settings Fixed-position banner zones (fixim_banner).
     *
     * @return self
     */
    public function setFiximBannerSettings($fixim_banner_settings)
    {
        if (is_null($fixim_banner_settings)) {
            throw new \InvalidArgumentException('non-nullable fixim_banner_settings cannot be null');
        }
        $this->container['fixim_banner_settings'] = $fixim_banner_settings;

        return $this;
    }

    /**
     * Gets native_bar_settings
     *
     * @return array<string,mixed>|null
     */
    public function getNativeBarSettings()
    {
        return $this->container['native_bar_settings'];
    }

    /**
     * Sets native_bar_settings
     *
     * @param array<string,mixed>|null $native_bar_settings Native bar zones (native_bar). position: top_left | top_right | left_middle | right_middle (the positions the native bar plugin draws); totalitems; topmargin (px, top positions); css. Full field list: GET /api/v1/adspaces/native_bar/_schema.
     *
     * @return self
     */
    public function setNativeBarSettings($native_bar_settings)
    {
        if (is_null($native_bar_settings)) {
            throw new \InvalidArgumentException('non-nullable native_bar_settings cannot be null');
        }
        $this->container['native_bar_settings'] = $native_bar_settings;

        return $this;
    }

    /**
     * Gets native_webpush_settings
     *
     * @return array<string,mixed>|null
     */
    public function getNativeWebpushSettings()
    {
        return $this->container['native_webpush_settings'];
    }

    /**
     * Sets native_webpush_settings
     *
     * @param array<string,mixed>|null $native_webpush_settings Native webpush zones (native_webpush). position: top_left | top_right | center | bottom_left | bottom_right; totalitems; topmargin; css (classic card only); skin: \"\" (classic card) | chat | call | alert — the social bar styles; delay_seconds 0-120 before the first message. Other skins or delays are refused with 422. Full field list: GET /api/v1/adspaces/native_webpush/_schema.
     *
     * @return self
     */
    public function setNativeWebpushSettings($native_webpush_settings)
    {
        if (is_null($native_webpush_settings)) {
            throw new \InvalidArgumentException('non-nullable native_webpush_settings cannot be null');
        }
        $this->container['native_webpush_settings'] = $native_webpush_settings;

        return $this;
    }

    /**
     * Gets text_settings
     *
     * @return array<string,mixed>|null
     */
    public function getTextSettings()
    {
        return $this->container['text_settings'];
    }

    /**
     * Sets text_settings
     *
     * @param array<string,mixed>|null $text_settings Text-list zones (textads).
     *
     * @return self
     */
    public function setTextSettings($text_settings)
    {
        if (is_null($text_settings)) {
            throw new \InvalidArgumentException('non-nullable text_settings cannot be null');
        }
        $this->container['text_settings'] = $text_settings;

        return $this;
    }

    /**
     * Gets floater_settings
     *
     * @return array<string,mixed>|null
     */
    public function getFloaterSettings()
    {
        return $this->container['floater_settings'];
    }

    /**
     * Sets floater_settings
     *
     * @param array<string,mixed>|null $floater_settings Floater wrapper for interstitial-flavored zones (interstitialads, interstitial_banner, interstitial_native, interstitial_video). `trigger`: empty = as usual, `exit` = exit intent (desktop: the mouse leaves the page towards the tab bar; touch: a fast scroll back up after reading at least a screen; never in the first 5 seconds) — banner and native interstitials (17, 20, 21); anything else is refused with 422 `floater_settings.trigger`. Floating banners (bannerads with display_type 1, interstitialads) also take `anchor` (full-width bottom/top strip with a collapse tab), `refresh_seconds` (0, or 30-600 seconds on screen), `min_width` / `max_width` (screen-width range in px, 0 = no limit; e.g. side rails = left-middle / right-middle with min_width 1200) — out-of-range values are refused with 422. Full field list: GET /api/v1/adspaces/interstitial_banner/_schema.
     *
     * @return self
     */
    public function setFloaterSettings($floater_settings)
    {
        if (is_null($floater_settings)) {
            throw new \InvalidArgumentException('non-nullable floater_settings cannot be null');
        }
        $this->container['floater_settings'] = $floater_settings;

        return $this;
    }

    /**
     * Gets videoslider_settings
     *
     * @return array<string,mixed>|null
     */
    public function getVideosliderSettings()
    {
        return $this->container['videoslider_settings'];
    }

    /**
     * Sets videoslider_settings
     *
     * @param array<string,mixed>|null $videoslider_settings Video-slider zones (videoslider).
     *
     * @return self
     */
    public function setVideosliderSettings($videoslider_settings)
    {
        if (is_null($videoslider_settings)) {
            throw new \InvalidArgumentException('non-nullable videoslider_settings cannot be null');
        }
        $this->container['videoslider_settings'] = $videoslider_settings;

        return $this;
    }

    /**
     * Gets invideo_settings
     *
     * @return array<string,mixed>|null
     */
    public function getInvideoSettings()
    {
        return $this->container['invideo_settings'];
    }

    /**
     * Sets invideo_settings
     *
     * @param array<string,mixed>|null $invideo_settings In-video plugin zones (invideo_plugin, type 16): roll slots, the zone each serves, pop-on-close/play. See `_schema`.
     *
     * @return self
     */
    public function setInvideoSettings($invideo_settings)
    {
        if (is_null($invideo_settings)) {
            throw new \InvalidArgumentException('non-nullable invideo_settings cannot be null');
        }
        $this->container['invideo_settings'] = $invideo_settings;

        return $this;
    }

    /**
     * Gets dyn_adsettings
     *
     * @return array<string,mixed>|null
     */
    public function getDynAdsettings()
    {
        return $this->container['dyn_adsettings'];
    }

    /**
     * Sets dyn_adsettings
     *
     * @param array<string,mixed>|null $dyn_adsettings Dynamic iframe zones (dyn_iframes).
     *
     * @return self
     */
    public function setDynAdsettings($dyn_adsettings)
    {
        if (is_null($dyn_adsettings)) {
            throw new \InvalidArgumentException('non-nullable dyn_adsettings cannot be null');
        }
        $this->container['dyn_adsettings'] = $dyn_adsettings;

        return $this;
    }

    /**
     * Gets inpage_videosettings
     *
     * @return array<string,mixed>|null
     */
    public function getInpageVideosettings()
    {
        return $this->container['inpage_videosettings'];
    }

    /**
     * Sets inpage_videosettings
     *
     * @param array<string,mixed>|null $inpage_videosettings In-page video zones (inpage_video).
     *
     * @return self
     */
    public function setInpageVideosettings($inpage_videosettings)
    {
        if (is_null($inpage_videosettings)) {
            throw new \InvalidArgumentException('non-nullable inpage_videosettings cannot be null');
        }
        $this->container['inpage_videosettings'] = $inpage_videosettings;

        return $this;
    }

    /**
     * Gets carousel_videosettings
     *
     * @return array<string,mixed>|null
     */
    public function getCarouselVideosettings()
    {
        return $this->container['carousel_videosettings'];
    }

    /**
     * Sets carousel_videosettings
     *
     * @param array<string,mixed>|null $carousel_videosettings Video carousel zones (video_carousel).
     *
     * @return self
     */
    public function setCarouselVideosettings($carousel_videosettings)
    {
        if (is_null($carousel_videosettings)) {
            throw new \InvalidArgumentException('non-nullable carousel_videosettings cannot be null');
        }
        $this->container['carousel_videosettings'] = $carousel_videosettings;

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



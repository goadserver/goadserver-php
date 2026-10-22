<?php
/**
 * StatsAdvertiserApi
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

namespace GoAdServerApi\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GoAdServerApi\ApiException;
use GoAdServerApi\Configuration;
use GoAdServerApi\FormDataProcessor;
use GoAdServerApi\HeaderSelector;
use GoAdServerApi\ObjectSerializer;

/**
 * StatsAdvertiserApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class StatsAdvertiserApi
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    /**
     * @var int Host index
     */
    protected $hostIndex;

    /** @var string[] $contentTypes **/
    public const contentTypes = [
        'apiV1AdvertiserStatsGet' => [
            'application/json',
        ],
    ];

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     * @param int             $hostIndex (Optional) host index to select the list of hosts if defined in the OpenAPI spec
     */
    public function __construct(
        ?ClientInterface $client = null,
        ?Configuration $config = null,
        ?HeaderSelector $selector = null,
        int $hostIndex = 0
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: Configuration::getDefaultConfiguration();
        $this->headerSelector = $selector ?: new HeaderSelector();
        $this->hostIndex = $hostIndex;
    }

    /**
     * Set the host index
     *
     * @param int $hostIndex Host index (required)
     */
    public function setHostIndex($hostIndex): void
    {
        $this->hostIndex = $hostIndex;
    }

    /**
     * Get the host index
     *
     * @return int Host index
     */
    public function getHostIndex()
    {
        return $this->hostIndex;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Operation apiV1AdvertiserStatsGet
     *
     * Display, native and CPM/RON campaign stats.
     *
     * @param  \DateTime|null $from ISO date — inclusive start. Defaults to 7 days ago. (optional)
     * @param  \DateTime|null $to ISO date — inclusive end. Defaults to today. Max range 92 days. (optional)
     * @param  string|null $interval interval (optional, default to 'sum')
     * @param  string[]|null $group_by Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. (optional)
     * @param  string[]|null $metrics Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. (optional)
     * @param  string|null $filter_campaign_id Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. (optional)
     * @param  string|null $filter_ad_id Ad ID, or a comma-separated list. (optional)
     * @param  string|null $filter_tag_id Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. (optional)
     * @param  int|null $filter_ad_type Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. (optional)
     * @param  string|null $filter_selling_type Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) (optional)
     * @param  string|null $filter_adzone_type Adzone (placement) type id, or a comma-separated list. (optional)
     * @param  int|null $filter_device Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | (optional)
     * @param  string|null $filter_country ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). (optional)
     * @param  string|null $filter_adzone_id Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. (optional)
     * @param  string|null $filter_site_id Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. (optional)
     * @param  int|null $filter_network_id RTB network ID. (optional)
     * @param  int|null $filter_bidruleset_id Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). (optional)
     * @param  int|null $filter_bidrule_id Bid rule ID inside a ruleset — only traffic that rule priced. (optional)
     * @param  int|null $filter_creative_pool_id Creative pool ID. (optional)
     * @param  int|null $filter_creative_pool_ad_id Creative pool item ID. (optional)
     * @param  string|null $format format (optional, default to 'json')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 100)
     * @param  string|null $sort Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. (optional, default to '-paid')
     * @param  array<string,float>|null $min Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. (optional)
     * @param  array<string,float>|null $max Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. (optional)
     * @param  string|null $strict &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdvertiserStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\StatsResponse|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1AdvertiserStatsGet($from = null, $to = null, $interval = 'sum', $group_by = null, $metrics = null, $filter_campaign_id = null, $filter_ad_id = null, $filter_tag_id = null, $filter_ad_type = null, $filter_selling_type = null, $filter_adzone_type = null, $filter_device = null, $filter_country = null, $filter_adzone_id = null, $filter_site_id = null, $filter_network_id = null, $filter_bidruleset_id = null, $filter_bidrule_id = null, $filter_creative_pool_id = null, $filter_creative_pool_ad_id = null, $format = 'json', $page = 1, $per_page = 100, $sort = '-paid', $min = null, $max = null, $strict = null, string $contentType = self::contentTypes['apiV1AdvertiserStatsGet'][0])
    {
        list($response) = $this->apiV1AdvertiserStatsGetWithHttpInfo($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdvertiserStatsGetWithHttpInfo
     *
     * Display, native and CPM/RON campaign stats.
     *
     * @param  \DateTime|null $from ISO date — inclusive start. Defaults to 7 days ago. (optional)
     * @param  \DateTime|null $to ISO date — inclusive end. Defaults to today. Max range 92 days. (optional)
     * @param  string|null $interval (optional, default to 'sum')
     * @param  string[]|null $group_by Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. (optional)
     * @param  string[]|null $metrics Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. (optional)
     * @param  string|null $filter_campaign_id Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. (optional)
     * @param  string|null $filter_ad_id Ad ID, or a comma-separated list. (optional)
     * @param  string|null $filter_tag_id Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. (optional)
     * @param  int|null $filter_ad_type Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. (optional)
     * @param  string|null $filter_selling_type Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) (optional)
     * @param  string|null $filter_adzone_type Adzone (placement) type id, or a comma-separated list. (optional)
     * @param  int|null $filter_device Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | (optional)
     * @param  string|null $filter_country ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). (optional)
     * @param  string|null $filter_adzone_id Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. (optional)
     * @param  string|null $filter_site_id Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. (optional)
     * @param  int|null $filter_network_id RTB network ID. (optional)
     * @param  int|null $filter_bidruleset_id Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). (optional)
     * @param  int|null $filter_bidrule_id Bid rule ID inside a ruleset — only traffic that rule priced. (optional)
     * @param  int|null $filter_creative_pool_id Creative pool ID. (optional)
     * @param  int|null $filter_creative_pool_ad_id Creative pool item ID. (optional)
     * @param  string|null $format (optional, default to 'json')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string|null $sort Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. (optional, default to '-paid')
     * @param  array<string,float>|null $min Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. (optional)
     * @param  array<string,float>|null $max Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. (optional)
     * @param  string|null $strict &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdvertiserStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\StatsResponse|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdvertiserStatsGetWithHttpInfo($from = null, $to = null, $interval = 'sum', $group_by = null, $metrics = null, $filter_campaign_id = null, $filter_ad_id = null, $filter_tag_id = null, $filter_ad_type = null, $filter_selling_type = null, $filter_adzone_type = null, $filter_device = null, $filter_country = null, $filter_adzone_id = null, $filter_site_id = null, $filter_network_id = null, $filter_bidruleset_id = null, $filter_bidrule_id = null, $filter_creative_pool_id = null, $filter_creative_pool_ad_id = null, $format = 'json', $page = 1, $per_page = 100, $sort = '-paid', $min = null, $max = null, $strict = null, string $contentType = self::contentTypes['apiV1AdvertiserStatsGet'][0])
    {
        $request = $this->apiV1AdvertiserStatsGetRequest($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\StatsResponse',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 429:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\StatsResponse',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\StatsResponse',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 429:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AdvertiserStatsGetAsync
     *
     * Display, native and CPM/RON campaign stats.
     *
     * @param  \DateTime|null $from ISO date — inclusive start. Defaults to 7 days ago. (optional)
     * @param  \DateTime|null $to ISO date — inclusive end. Defaults to today. Max range 92 days. (optional)
     * @param  string|null $interval (optional, default to 'sum')
     * @param  string[]|null $group_by Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. (optional)
     * @param  string[]|null $metrics Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. (optional)
     * @param  string|null $filter_campaign_id Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. (optional)
     * @param  string|null $filter_ad_id Ad ID, or a comma-separated list. (optional)
     * @param  string|null $filter_tag_id Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. (optional)
     * @param  int|null $filter_ad_type Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. (optional)
     * @param  string|null $filter_selling_type Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) (optional)
     * @param  string|null $filter_adzone_type Adzone (placement) type id, or a comma-separated list. (optional)
     * @param  int|null $filter_device Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | (optional)
     * @param  string|null $filter_country ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). (optional)
     * @param  string|null $filter_adzone_id Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. (optional)
     * @param  string|null $filter_site_id Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. (optional)
     * @param  int|null $filter_network_id RTB network ID. (optional)
     * @param  int|null $filter_bidruleset_id Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). (optional)
     * @param  int|null $filter_bidrule_id Bid rule ID inside a ruleset — only traffic that rule priced. (optional)
     * @param  int|null $filter_creative_pool_id Creative pool ID. (optional)
     * @param  int|null $filter_creative_pool_ad_id Creative pool item ID. (optional)
     * @param  string|null $format (optional, default to 'json')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string|null $sort Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. (optional, default to '-paid')
     * @param  array<string,float>|null $min Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. (optional)
     * @param  array<string,float>|null $max Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. (optional)
     * @param  string|null $strict &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdvertiserStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdvertiserStatsGetAsync($from = null, $to = null, $interval = 'sum', $group_by = null, $metrics = null, $filter_campaign_id = null, $filter_ad_id = null, $filter_tag_id = null, $filter_ad_type = null, $filter_selling_type = null, $filter_adzone_type = null, $filter_device = null, $filter_country = null, $filter_adzone_id = null, $filter_site_id = null, $filter_network_id = null, $filter_bidruleset_id = null, $filter_bidrule_id = null, $filter_creative_pool_id = null, $filter_creative_pool_ad_id = null, $format = 'json', $page = 1, $per_page = 100, $sort = '-paid', $min = null, $max = null, $strict = null, string $contentType = self::contentTypes['apiV1AdvertiserStatsGet'][0])
    {
        return $this->apiV1AdvertiserStatsGetAsyncWithHttpInfo($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdvertiserStatsGetAsyncWithHttpInfo
     *
     * Display, native and CPM/RON campaign stats.
     *
     * @param  \DateTime|null $from ISO date — inclusive start. Defaults to 7 days ago. (optional)
     * @param  \DateTime|null $to ISO date — inclusive end. Defaults to today. Max range 92 days. (optional)
     * @param  string|null $interval (optional, default to 'sum')
     * @param  string[]|null $group_by Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. (optional)
     * @param  string[]|null $metrics Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. (optional)
     * @param  string|null $filter_campaign_id Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. (optional)
     * @param  string|null $filter_ad_id Ad ID, or a comma-separated list. (optional)
     * @param  string|null $filter_tag_id Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. (optional)
     * @param  int|null $filter_ad_type Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. (optional)
     * @param  string|null $filter_selling_type Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) (optional)
     * @param  string|null $filter_adzone_type Adzone (placement) type id, or a comma-separated list. (optional)
     * @param  int|null $filter_device Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | (optional)
     * @param  string|null $filter_country ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). (optional)
     * @param  string|null $filter_adzone_id Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. (optional)
     * @param  string|null $filter_site_id Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. (optional)
     * @param  int|null $filter_network_id RTB network ID. (optional)
     * @param  int|null $filter_bidruleset_id Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). (optional)
     * @param  int|null $filter_bidrule_id Bid rule ID inside a ruleset — only traffic that rule priced. (optional)
     * @param  int|null $filter_creative_pool_id Creative pool ID. (optional)
     * @param  int|null $filter_creative_pool_ad_id Creative pool item ID. (optional)
     * @param  string|null $format (optional, default to 'json')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string|null $sort Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. (optional, default to '-paid')
     * @param  array<string,float>|null $min Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. (optional)
     * @param  array<string,float>|null $max Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. (optional)
     * @param  string|null $strict &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdvertiserStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdvertiserStatsGetAsyncWithHttpInfo($from = null, $to = null, $interval = 'sum', $group_by = null, $metrics = null, $filter_campaign_id = null, $filter_ad_id = null, $filter_tag_id = null, $filter_ad_type = null, $filter_selling_type = null, $filter_adzone_type = null, $filter_device = null, $filter_country = null, $filter_adzone_id = null, $filter_site_id = null, $filter_network_id = null, $filter_bidruleset_id = null, $filter_bidrule_id = null, $filter_creative_pool_id = null, $filter_creative_pool_ad_id = null, $format = 'json', $page = 1, $per_page = 100, $sort = '-paid', $min = null, $max = null, $strict = null, string $contentType = self::contentTypes['apiV1AdvertiserStatsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\StatsResponse';
        $request = $this->apiV1AdvertiserStatsGetRequest($from, $to, $interval, $group_by, $metrics, $filter_campaign_id, $filter_ad_id, $filter_tag_id, $filter_ad_type, $filter_selling_type, $filter_adzone_type, $filter_device, $filter_country, $filter_adzone_id, $filter_site_id, $filter_network_id, $filter_bidruleset_id, $filter_bidrule_id, $filter_creative_pool_id, $filter_creative_pool_ad_id, $format, $page, $per_page, $sort, $min, $max, $strict, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1AdvertiserStatsGet'
     *
     * @param  \DateTime|null $from ISO date — inclusive start. Defaults to 7 days ago. (optional)
     * @param  \DateTime|null $to ISO date — inclusive end. Defaults to today. Max range 92 days. (optional)
     * @param  string|null $interval (optional, default to 'sum')
     * @param  string[]|null $group_by Dimensions to aggregate by, in priority order. Multi-pick up to 8. High-cardinality dims (&#x60;subid&#x60;, &#x60;sid1&#x60;–&#x60;sid5&#x60;, &#x60;domain&#x60;) explode row count — pair with &#x60;from&#x60;/&#x60;to&#x60;/pagination. An unknown dimension is a 422.  &#x60;sid1&#x60;–&#x60;sid5&#x60; are the named custom-parameter slots the tracker records per click; their labels are whatever this account configured. On a Search Ads zone &#x60;sid1&#x60; holds the visitor&#39;s search; a network can keep those terms admin-only, and then Search Ads rows report an empty &#x60;sid1&#x60;. &#x60;creative_pool&#x60; / &#x60;url_pool&#x60; group by the pool an ad drew from, &#x60;creative_pool_ad&#x60; / &#x60;url_pool_ad&#x60; by the individual pool item. &#x60;adzone_type&#x60; is the ad placement (e.g. in-page video vs VAST pre-roll on the same video ad type). &#x60;selling_type&#x60; groups by pay type (see &#x60;filter[selling_type]&#x60; for the ids).  Each grouped dimension comes back as an &#x60;{id, name}&#x60; object, except &#x60;country&#x60;, &#x60;domain&#x60; and &#x60;subid&#x60;, which are plain strings. Without the &#x60;stats_source_names&#x60; right &#x60;name&#x60; equals the id. (optional)
     * @param  string[]|null $metrics Metrics to return (comma-separated). **Empty** returns the default set &#x60;views, clicks, ctr, ecpm, paid, conv_a, conv_b, conv_c&#x60; (unchanged since launch). &#x60;all&#x60; returns every metric this caller may read. Everything else is opt-in by name:  | Metric | Meaning | |---|---| | &#x60;ecpm&#x60; | blended: &#x60;paid&#x60; / &#x60;views&#x60; × 1000 | | &#x60;ecpc&#x60; | CPC spend / CPC clicks (click-billed ad types only) | | &#x60;cpm_views&#x60;, &#x60;cpm_paid&#x60; | views / spend on impression-billed ad types | | &#x60;cpc_clicks&#x60;, &#x60;cpc_paid&#x60; | clicks / spend on click-billed ad types | | &#x60;conv_a_earned&#x60; … &#x60;conv_c_earned&#x60; | conversion revenue per conversion type (the postback value) | | &#x60;revenue&#x60; | conversion revenue over the selected conversion types | | &#x60;profit&#x60; | &#x60;revenue&#x60; − cost (cost &#x3D; &#x60;traffic_cost&#x60; when the row carries one, else &#x60;paid&#x60;) | | &#x60;roi&#x60; | &#x60;profit&#x60; / cost × 100; &#x60;null&#x60; when cost &lt; 1.00 | | &#x60;cpa&#x60; | &#x60;paid&#x60; / conversions of the selected types | | &#x60;cr&#x60; | conversions of the selected types / &#x60;clicks&#x60; × 100 | | &#x60;traffic_cost&#x60; | traffic-acquisition cost pulled from a bound ad network — **ad manager accounts only** (on an ad network it is the network&#39;s own buying cost: network admins only, an advertiser key gets a 422 and &#x60;all&#x60; leaves it out) | | &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; | the ad network&#39;s own counters for that traffic — same rule as &#x60;traffic_cost&#x60; | | &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;, &#x60;bot_views_pct&#x60;, &#x60;bot_clicks_pct&#x60;, &#x60;bot_unknown&#x60;, &#x60;bot_click&#x60;, &#x60;bot_impression&#x60;, &#x60;bot_conversion&#x60;, &#x60;bot_web&#x60;, &#x60;bot_high_frequency&#x60;, &#x60;bot_artificial_ip&#x60;, &#x60;bot_adspace_abuse&#x60;, &#x60;bot_suspicious&#x60;, &#x60;bot_datacenter&#x60;, &#x60;bot_vpn&#x60; | bot breakdown — **network admins only** (as in the panel); an advertiser key asking for one gets a 422 |  \&quot;Selected conversion types\&quot; are the &#x60;conv_a&#x60;/&#x60;conv_b&#x60;/&#x60;conv_c&#x60; metrics in this request; with none of them requested, &#x60;revenue&#x60;/&#x60;cpa&#x60;/&#x60;cr&#x60; cover all three (same rule as the panel).  An unknown metric name is ignored and listed in the response&#39;s &#x60;ignored_metrics&#x60;; with &#x60;strict&#x3D;1&#x60; it is a 422. (optional)
     * @param  string|null $filter_campaign_id Campaign ID, or a comma-separated list. Look up via &#x60;GET /api/v1/campaigns&#x60;. (optional)
     * @param  string|null $filter_ad_id Ad ID, or a comma-separated list. (optional)
     * @param  string|null $filter_tag_id Campaign group (tag) ID, or a comma-separated list — resolves to the campaigns in those groups, sub-groups included. A group with no campaigns returns nothing. (optional)
     * @param  int|null $filter_ad_type Ad-type ID. Available types are tenant-specific (banner, popunder, native, video, push, …); the dropdown shows the types active on your system. (optional)
     * @param  string|null $filter_selling_type Pay type (the campaign&#39;s pricing model), one id or a comma-separated list:  | ID | Pay type | |----|----------| | 1  | CPC | | 2  | CPM | | 3  | CPA | | 5  | SmartAds | | 6  | Flat rate |  (Pay-type id &#x3D; the tenant&#39;s &#x60;bid_types&#x60; id + 1; the ids above are the standard set.) (optional)
     * @param  string|null $filter_adzone_type Adzone (placement) type id, or a comma-separated list. (optional)
     * @param  int|null $filter_device Device type:  | ID | Device | |----|--------| | 1  | Computer | | 2  | Tablet | | 3  | Phone | | 4  | Console | | 5  | Wearable | | 6  | TV | (optional)
     * @param  string|null $filter_country ISO-3166 alpha-2 country code (e.g. &#x60;US&#x60;, &#x60;DE&#x60;, &#x60;BR&#x60;). (optional)
     * @param  string|null $filter_adzone_id Adspace ID, or comma-separated list. Look up via &#x60;GET /api/v1/adspaces&#x60;. (optional)
     * @param  string|null $filter_site_id Comma-separated site IDs. Look up via &#x60;GET /api/v1/sites&#x60;. (optional)
     * @param  int|null $filter_network_id RTB network ID. (optional)
     * @param  int|null $filter_bidruleset_id Bid ruleset ID — only traffic bid through that ruleset (&#x60;GET /api/v1/bid-rulesets&#x60;). (optional)
     * @param  int|null $filter_bidrule_id Bid rule ID inside a ruleset — only traffic that rule priced. (optional)
     * @param  int|null $filter_creative_pool_id Creative pool ID. (optional)
     * @param  int|null $filter_creative_pool_ad_id Creative pool item ID. (optional)
     * @param  string|null $format (optional, default to 'json')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string|null $sort Sort key, &#x60;-&#x60; prefix for descending. One of &#x60;views&#x60;, &#x60;clicks&#x60;, &#x60;ctr&#x60;, &#x60;paid&#x60;, &#x60;ecpm&#x60;, &#x60;ecpc&#x60;, &#x60;conv_a&#x60;, &#x60;conv_b&#x60;, &#x60;conv_c&#x60;, &#x60;conv_a_earned&#x60;, &#x60;conv_b_earned&#x60;, &#x60;conv_c_earned&#x60;, &#x60;revenue&#x60;, &#x60;traffic_cost&#x60;, &#x60;nw_views&#x60;, &#x60;nw_clicks&#x60; (where those metrics are available, see &#x60;metrics&#x60;), &#x60;date&#x60; (only with a non-&#x60;sum&#x60; interval); admins also &#x60;bot_views&#x60;, &#x60;bot_clicks&#x60;. Any other value falls back to the default, &#x60;-paid&#x60;. (optional, default to '-paid')
     * @param  array<string,float>|null $min Keep only rows whose metric is at least this value, as &#x60;min[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60; — e.g. &#x60;min[cpa]&#x3D;10&#x60;. Pair with &#x60;max[&lt;metric&gt;]&#x60; for a range (both ends inclusive). Any metric &#x60;metrics&#x60; accepts for this caller can be bounded, whether or not it is returned; several metrics combine with AND. A row with no value for the metric (&#x60;roi&#x60; below 1.00 of spend) is dropped. Money in main currency units, &#x60;ctr&#x60; in percent. Same as the comparisons (&#x60;&gt;10&#x60;, &#x60;&lt;200&#x60;, &#x60;10-200&#x60;) in the panel&#39;s statistics details column search.  With a bound set, up to 10,000 grouped rows (in &#x60;sort&#x60; order) are tested and the survivors paginated; &#x60;total_rows&#x60; counts the survivors, &#x60;totals&#x60; stay over the whole selection, and &#x60;range_scan_truncated: true&#x60; says more rows existed than were tested. Unknown metric, non-number or min &gt; max → 422. (optional)
     * @param  array<string,float>|null $max Keep only rows whose metric is at most this value, as &#x60;max[&lt;metric&gt;]&#x3D;&lt;number&gt;&#x60;. See &#x60;min&#x60;. (optional)
     * @param  string|null $strict &#x60;1&#x60; turns an unknown metric name into a 422 instead of ignoring it. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdvertiserStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdvertiserStatsGetRequest($from = null, $to = null, $interval = 'sum', $group_by = null, $metrics = null, $filter_campaign_id = null, $filter_ad_id = null, $filter_tag_id = null, $filter_ad_type = null, $filter_selling_type = null, $filter_adzone_type = null, $filter_device = null, $filter_country = null, $filter_adzone_id = null, $filter_site_id = null, $filter_network_id = null, $filter_bidruleset_id = null, $filter_bidrule_id = null, $filter_creative_pool_id = null, $filter_creative_pool_ad_id = null, $format = 'json', $page = 1, $per_page = 100, $sort = '-paid', $min = null, $max = null, $strict = null, string $contentType = self::contentTypes['apiV1AdvertiserStatsGet'][0])
    {




        if ($group_by !== null && count($group_by) > 8) {
            throw new \InvalidArgumentException('invalid value for "$group_by" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, number of items must be less than or equal to 8.');
        }
        




        if ($filter_ad_type !== null && $filter_ad_type < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_ad_type" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        



        if ($filter_country !== null && strlen($filter_country) > 2) {
            throw new \InvalidArgumentException('invalid length for "$filter_country" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be smaller than or equal to 2.');
        }
        if ($filter_country !== null && strlen($filter_country) < 2) {
            throw new \InvalidArgumentException('invalid length for "$filter_country" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 2.');
        }
        


        if ($filter_network_id !== null && $filter_network_id < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_network_id" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        
        if ($filter_bidruleset_id !== null && $filter_bidruleset_id < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_bidruleset_id" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        
        if ($filter_bidrule_id !== null && $filter_bidrule_id < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_bidrule_id" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        
        if ($filter_creative_pool_id !== null && $filter_creative_pool_id < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_creative_pool_id" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        
        if ($filter_creative_pool_ad_id !== null && $filter_creative_pool_ad_id < 1) {
            throw new \InvalidArgumentException('invalid value for "$filter_creative_pool_ad_id" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 1000) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be smaller than or equal to 1000.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling StatsAdvertiserApi.apiV1AdvertiserStatsGet, must be bigger than or equal to 1.');
        }
        





        $resourcePath = '/api/v1/advertiser/stats';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $from,
            'from', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $to,
            'to', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $interval,
            'interval', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $group_by,
            'group_by', // param base name
            'array', // openApiType
            'form', // style
            false, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $metrics,
            'metrics', // param base name
            'array', // openApiType
            'form', // style
            false, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_campaign_id,
            'filter[campaign_id]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_ad_id,
            'filter[ad_id]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_tag_id,
            'filter[tag_id]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_ad_type,
            'filter[ad_type]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_selling_type,
            'filter[selling_type]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_adzone_type,
            'filter[adzone_type]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_device,
            'filter[device]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_country,
            'filter[country]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_adzone_id,
            'filter[adzone_id]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_site_id,
            'filter[site_id]', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_network_id,
            'filter[network_id]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_bidruleset_id,
            'filter[bidruleset_id]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_bidrule_id,
            'filter[bidrule_id]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_creative_pool_id,
            'filter[creative_pool_id]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_creative_pool_ad_id,
            'filter[creative_pool_ad_id]', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $format,
            'format', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $page,
            'page', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $per_page,
            'per_page', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sort,
            'sort', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $min,
            'min', // param base name
            'object', // openApiType
            'deepObject', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $max,
            'max', // param base name
            'object', // openApiType
            'deepObject', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $strict,
            'strict', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Create http client option
     *
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        if ($this->config->getCertFile()) {
            $options[RequestOptions::CERT] = $this->config->getCertFile();
        }

        if ($this->config->getKeyFile()) {
            $options[RequestOptions::SSL_KEY] = $this->config->getKeyFile();
        }

        return $options;
    }

    private function handleResponseWithDataType(
        string $dataType,
        RequestInterface $request,
        ResponseInterface $response
    ): array {
        if ($dataType === '\SplFileObject') {
            $content = $response->getBody(); //stream goes to serializer
        } else {
            $content = (string) $response->getBody();
            if ($dataType !== 'string') {
                try {
                    $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new ApiException(
                        sprintf(
                            'Error JSON decoding server response (%s)',
                            $request->getUri()
                        ),
                        $response->getStatusCode(),
                        $response->getHeaders(),
                        $content
                    );
                }
            }
        }

        return [
            ObjectSerializer::deserialize($content, $dataType, []),
            $response->getStatusCode(),
            $response->getHeaders()
        ];
    }

    private function responseWithinRangeCode(
        string $rangeCode,
        int $statusCode
    ): bool {
        $left = (int) ($rangeCode[0].'00');
        $right = (int) ($rangeCode[0].'99');

        return $statusCode >= $left && $statusCode <= $right;
    }
}

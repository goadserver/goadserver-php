<?php
/**
 * AgenticMediaBuyingApiTest
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
 * Please update the test case below to test the endpoint.
 */

namespace GoAdServerApi\Test\Api;

use \GoAdServerApi\Configuration;
use \GoAdServerApi\ApiException;
use \GoAdServerApi\ObjectSerializer;
use PHPUnit\Framework\TestCase;

/**
 * AgenticMediaBuyingApiTest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class AgenticMediaBuyingApiTest extends TestCase
{

    /**
     * Setup before running any test cases
     */
    public static function setUpBeforeClass(): void
    {
    }

    /**
     * Setup before running each test case
     */
    public function setUp(): void
    {
    }

    /**
     * Clean up after running each test case
     */
    public function tearDown(): void
    {
    }

    /**
     * Clean up after running all test cases
     */
    public static function tearDownAfterClass(): void
    {
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost
     *
     * Approve or reject a proposal..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingActionsActionIdDecidePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingActionsGet
     *
     * Activity log: every block, bid, budget and other change, and open proposals..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingActionsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingAgentRunPost
     *
     * Run the buying agent now..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingAgentRunPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingAgentRunsGet
     *
     * The buying agent's recent runs with their summaries and proposals..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingAgentRunsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingBlockPost
     *
     * Block or unblock one source zone at the network..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingBlockPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingCompareGet
     *
     * Compare with another buy (A/B, e.g. against a control left to the network's own optimiser)..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingCompareGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingGet
     *
     * Overview: the connected network, what it supports, the policy and open proposals..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingIpsGet
     *
     * The IP preblock list: ranges on the network's exclusion list and proposals..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingIpsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingIpsPost
     *
     * Change the IP preblock list..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingIpsPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingLaunchGet
     *
     * What a new campaign can be launched with (unconnected ad zones)..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingLaunchGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingLaunchPost
     *
     * Launch a new campaign at the network for this ad zone..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingLaunchPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingLaunchTemplatesGet
     *
     * Existing campaigns that can be cloned for a launch..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingLaunchTemplatesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingNetworkBudgetGet
     *
     * The campaign's daily budget at the network..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingNetworkBudgetGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingPolicyPut
     *
     * Save the buying policy (any subset of its fields)..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingPolicyPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingPolicySuggestGet
     *
     * Suggested policy from the zone's own traffic, with a reason per field..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingPolicySuggestGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingReportPost
     *
     * Send the media buying report now..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingReportPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingSegmentsGet
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingSegmentsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingSourceBidGet
     *
     * The campaign's current bid at the network..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingSourceBidGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingSourceBidPut
     *
     * Set the campaign's bid at the network..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingSourceBidPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingSourceRetryPost
     *
     * Retry the connection to the source campaign..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingSourceRetryPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingTargetingGet
     *
     * The campaign's placement targeting (all, or an allow-list)..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingTargetingGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingTargetingPut
     *
     * Switch to / edit the allow-list, or back to all placements..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingTargetingPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingTargetingSuggestGet
     *
     * Suggested allow-list: the profitable placements of the policy window..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingTargetingSuggestGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingTrackRecordGet
     *
     * Track record: did each change do what it was meant to?.
     *
     */
    public function testApiV1AdspacesIdMediaBuyingTrackRecordGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1AdspacesIdMediaBuyingZonesGet
     *
     * Source-zone report: revenue against cost per placement..
     *
     */
    public function testApiV1AdspacesIdMediaBuyingZonesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1MediaBuyingPortfolioGet
     *
     * Every buy side by side: margin against target, spend, proposals, the agent's latest summary..
     *
     */
    public function testApiV1MediaBuyingPortfolioGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerMediaBuyingPortfolioGet
     *
     * Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent's latest summary..
     *
     */
    public function testApiV1TrackerMediaBuyingPortfolioGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost
     *
     * Approve or reject a proposal..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingActionsGet
     *
     * Activity log: every block, bid, budget and other change, and open proposals..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingActionsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost
     *
     * Run the buying agent now..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet
     *
     * The buying agent's recent runs with their summaries and proposals..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingBlockPost
     *
     * Block or unblock one source zone at the network..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingBlockPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingCompareGet
     *
     * Compare with another buy (A/B, e.g. against a control left to the network's own optimiser)..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingCompareGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingGet
     *
     * Overview: the connected network, what it supports, the policy and open proposals..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingIpsGet
     *
     * The IP preblock list: ranges on the network's exclusion list and proposals..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingIpsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingIpsPost
     *
     * Change the IP preblock list..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingIpsPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet
     *
     * The campaign's daily budget at the network..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut
     *
     * Save the buying policy (any subset of its fields)..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingPolicyPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet
     *
     * Suggested policy from the zone's own traffic, with a reason per field..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingReportPost
     *
     * Send the media buying report now..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingReportPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet
     *
     * The campaign's current bid at the network..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut
     *
     * Set the campaign's bid at the network..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost
     *
     * Retry the connection to the source campaign..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet
     *
     * The campaign's placement targeting (all, or an allow-list)..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingTargetingGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut
     *
     * Switch to / edit the allow-list, or back to all placements..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingTargetingPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet
     *
     * Suggested allow-list: the profitable placements of the policy window..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet
     *
     * Track record: did each change do what it was meant to?.
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1TrackerTrackingLinksIdMediaBuyingZonesGet
     *
     * Source-zone report: revenue against cost per placement..
     *
     */
    public function testApiV1TrackerTrackingLinksIdMediaBuyingZonesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }
}

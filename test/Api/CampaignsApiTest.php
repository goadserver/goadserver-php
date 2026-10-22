<?php
/**
 * CampaignsApiTest
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
 * CampaignsApiTest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class CampaignsApiTest extends TestCase
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
     * Test case for apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete
     *
     * Remove an ad zone from a CPM campaign..
     *
     */
    public function testApiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost
     *
     * Add an ad zone to a CPM campaign (or re-price it)..
     *
     */
    public function testApiV1CampaignsCpmCampaignIdSpotsSpaceIdPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut
     *
     * Switch a CPM campaign's zone spot on or off..
     *
     */
    public function testApiV1CampaignsCpmCampaignIdSpotsSpaceIdPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut
     *
     * Set a CPM zone spot's bid or state..
     *
     */
    public function testApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsGet
     *
     * List the caller's campaigns across all types (or one type)..
     *
     */
    public function testApiV1CampaignsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsMassDeletePost
     *
     * Soft-delete many campaigns..
     *
     */
    public function testApiV1CampaignsMassDeletePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsMassTogglePut
     *
     * Pause or resume many campaigns at once..
     *
     */
    public function testApiV1CampaignsMassTogglePut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsMassUndeletePost
     *
     * Restore soft-deleted campaigns..
     *
     */
    public function testApiV1CampaignsMassUndeletePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsPurgePost
     *
     * Permanently delete soft-deleted campaigns and their ads..
     *
     */
    public function testApiV1CampaignsPurgePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet
     *
     * List per-zone bid overrides for a RON ad..
     *
     */
    public function testApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut
     *
     * Replace the per-zone bid overrides on a RON ad..
     *
     */
    public function testApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete
     *
     * Clear a single per-zone bid override..
     *
     */
    public function testApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut
     *
     * Switch \"only these zones\" mode for a RON ad's per-zone bids..
     *
     */
    public function testApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonIdSiteListGet
     *
     * The Premium-sites box of a RON campaign..
     *
     */
    public function testApiV1CampaignsRonIdSiteListGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonIdSiteListSiteIdPut
     *
     * Set one site of the Premium-sites box active or blocked..
     *
     */
    public function testApiV1CampaignsRonIdSiteListSiteIdPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRonIdZonesGet
     *
     * List the zones a RON campaign is linked to..
     *
     */
    public function testApiV1CampaignsRonIdZonesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRtbIdTestRtbGet
     *
     * Context for testing an RTB campaign's endpoint..
     *
     */
    public function testApiV1CampaignsRtbIdTestRtbGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsRtbIdTestRtbPost
     *
     * Fire a test bid request at an RTB campaign's endpoint..
     *
     */
    public function testApiV1CampaignsRtbIdTestRtbPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsSaveCategoriesPut
     *
     * Assign categories to many campaigns..
     *
     */
    public function testApiV1CampaignsSaveCategoriesPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsSaveTagsPut
     *
     * Assign tags (campaign groups) to many campaigns..
     *
     */
    public function testApiV1CampaignsSaveTagsPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch
     *
     * Pause or unpause a single ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch
     *
     * Set a single ad's bid..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut
     *
     * Set per-country bids for a RON ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdClonePost
     *
     * Clone a single ad within a campaign..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdClonePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut
     *
     * Set per-ad country targeting for a RON ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdDelete
     *
     * Soft-delete an ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdGet
     *
     * Show one ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet
     *
     * Shoppable video — shown and clicks per overlay..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdPut
     *
     * Update an ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut
     *
     * Restore a deleted RON ad..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsAdIdRestorePut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet
     *
     * Campaigns this campaign's ads can be copied into..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsCopyTargetsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsCsvImportPost
     *
     * Import native ads from a CSV — one ad per row, or one ad per group..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsCsvImportPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsFromPoolPost
     *
     * Create ads from a creative pool..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsFromPoolPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsGet
     *
     * List ads on a campaign..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsMassClonePost
     *
     * Copy many ads of a campaign..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsMassClonePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsMassDeletePost
     *
     * Soft-delete many ads of a campaign..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsMassDeletePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsMassTogglePut
     *
     * Activate or pause many ads of a campaign at once..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsMassTogglePut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsMultiUploadPost
     *
     * Bulk-upload ads — one ad per creative file..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsMultiUploadPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsPost
     *
     * Create an ad on a campaign (smart-default)..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut
     *
     * Point many ads at a different creative pool..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsUpdatePoolPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut
     *
     * Point many ads at a URL pool, or detach it..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut
     *
     * Rewrite the destination URL of many ads at once..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsUpdateUrlPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut
     *
     * Set the rotation weight of one or more RON ads..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdAdsUpdateWeightPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeCampaignIdSchemaAdsGet
     *
     * Field metadata for ad creation, scoped to a campaign..
     *
     */
    public function testApiV1CampaignsTypeCampaignIdSchemaAdsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdActivePatch
     *
     * Pause or unpause a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdActivePatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdAvailableZonesGet
     *
     * List your ad zones that can be attached to a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdAvailableZonesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdBidsPatch
     *
     * Mass update bids on every ad in a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdBidsPatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdBudgetPatch
     *
     * Update a campaign's total and/or daily budget..
     *
     */
    public function testApiV1CampaignsTypeIdBudgetPatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdClonePost
     *
     * Clone a campaign — everything, or the parts you pick..
     *
     */
    public function testApiV1CampaignsTypeIdClonePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdDelete
     *
     * Soft-delete a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete
     *
     * Detach a filter from this campaign..
     *
     */
    public function testApiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdFiltersGet
     *
     * List filters attached to this campaign..
     *
     */
    public function testApiV1CampaignsTypeIdFiltersGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdFiltersPost
     *
     * Attach a filter to this campaign..
     *
     */
    public function testApiV1CampaignsTypeIdFiltersPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdGet
     *
     * Get a single campaign..
     *
     */
    public function testApiV1CampaignsTypeIdGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdMinBidGet
     *
     * Get the campaign's minimum acceptable CPC bid..
     *
     */
    public function testApiV1CampaignsTypeIdMinBidGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdPatch
     *
     * Partially update a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdPatch()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdResetMaxBudgetPut
     *
     * Reset a campaign's lifetime-budget spend..
     *
     */
    public function testApiV1CampaignsTypeIdResetMaxBudgetPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdSitesGet
     *
     * Your sites that have zones attachable to a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdSitesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdWhitelistZonesGet
     *
     * List the ad zones attached to a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdWhitelistZonesGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdZonesPost
     *
     * Attach ad zones to a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdZonesPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdZonesZoneIdDelete
     *
     * Detach an ad zone from a campaign..
     *
     */
    public function testApiV1CampaignsTypeIdZonesZoneIdDelete()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeIdZonesZoneIdWeightPut
     *
     * Set an attached zone's weight for this campaign..
     *
     */
    public function testApiV1CampaignsTypeIdZonesZoneIdWeightPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypePost
     *
     * Create a new campaign (smart-default)..
     *
     */
    public function testApiV1CampaignsTypePost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeSchemaAdsGet
     *
     * Field metadata for ad creation..
     *
     */
    public function testApiV1CampaignsTypeSchemaAdsGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1CampaignsTypeSchemaGet
     *
     * Field metadata for create/update..
     *
     */
    public function testApiV1CampaignsTypeSchemaGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1RtbTestRtbContextGet
     *
     * Context for testing an endpoint before the campaign exists..
     *
     */
    public function testApiV1RtbTestRtbContextGet()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1RtbTestRtbPost
     *
     * Fire a test bid request at any endpoint URL..
     *
     */
    public function testApiV1RtbTestRtbPost()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }

    /**
     * Test case for apiV1ToolsReplaceDomainPut
     *
     * Replace the landing domain across your RON ads..
     *
     */
    public function testApiV1ToolsReplaceDomainPut()
    {
        // TODO: implement
        self::markTestIncomplete('Not implemented');
    }
}

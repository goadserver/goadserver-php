<?php
/**
 * CampaignsApi
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
 * CampaignsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class CampaignsApi
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
        'apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost' => [
            'application/json',
        ],
        'apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut' => [
            'application/json',
        ],
        'apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut' => [
            'application/json',
        ],
        'apiV1CampaignsGet' => [
            'application/json',
        ],
        'apiV1CampaignsMassDeletePost' => [
            'application/json',
        ],
        'apiV1CampaignsMassTogglePut' => [
            'application/json',
        ],
        'apiV1CampaignsMassUndeletePost' => [
            'application/json',
        ],
        'apiV1CampaignsPurgePost' => [
            'application/json',
        ],
        'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet' => [
            'application/json',
        ],
        'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut' => [
            'application/json',
        ],
        'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut' => [
            'application/json',
        ],
        'apiV1CampaignsRonIdSiteListGet' => [
            'application/json',
        ],
        'apiV1CampaignsRonIdSiteListSiteIdPut' => [
            'application/json',
        ],
        'apiV1CampaignsRonIdZonesGet' => [
            'application/json',
        ],
        'apiV1CampaignsRtbIdTestRtbGet' => [
            'application/json',
        ],
        'apiV1CampaignsRtbIdTestRtbPost' => [
            'application/json',
        ],
        'apiV1CampaignsSaveCategoriesPut' => [
            'application/json',
        ],
        'apiV1CampaignsSaveTagsPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdClonePost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsCsvImportPost' => [
            'multipart/form-data',
        ],
        'apiV1CampaignsTypeCampaignIdAdsFromPoolPost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsMassClonePost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsMassDeletePost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsMassTogglePut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsMultiUploadPost' => [
            'multipart/form-data',
        ],
        'apiV1CampaignsTypeCampaignIdAdsPost' => [
            'application/json',
            'multipart/form-data',
        ],
        'apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeCampaignIdSchemaAdsGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdActivePatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdAvailableZonesGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdBidsPatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdBudgetPatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdClonePost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdFiltersGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdFiltersPost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdMinBidGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdPatch' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdResetMaxBudgetPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdSitesGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdWhitelistZonesGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdZonesPost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdZonesZoneIdDelete' => [
            'application/json',
        ],
        'apiV1CampaignsTypeIdZonesZoneIdWeightPut' => [
            'application/json',
        ],
        'apiV1CampaignsTypePost' => [
            'application/json',
        ],
        'apiV1CampaignsTypeSchemaAdsGet' => [
            'application/json',
        ],
        'apiV1CampaignsTypeSchemaGet' => [
            'application/json',
        ],
        'apiV1RtbTestRtbContextGet' => [
            'application/json',
        ],
        'apiV1RtbTestRtbPost' => [
            'application/json',
        ],
        'apiV1ToolsReplaceDomainPut' => [
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
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete
     *
     * Remove an ad zone from a CPM campaign.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $space_id space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'][0])
    {
        $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteWithHttpInfo($campaign_id, $space_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteWithHttpInfo
     *
     * Remove an ad zone from a CPM campaign.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteWithHttpInfo($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'][0])
    {
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteRequest($campaign_id, $space_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteAsync
     *
     * Remove an ad zone from a CPM campaign.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteAsync($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'][0])
    {
        return $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteAsyncWithHttpInfo($campaign_id, $space_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteAsyncWithHttpInfo
     *
     * Remove an ad zone from a CPM campaign.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteAsyncWithHttpInfo($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteRequest($campaign_id, $space_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdDeleteRequest($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'
            );
        }

        // verify the required parameter 'space_id' is set
        if ($space_id === null || (is_array($space_id) && count($space_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $space_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/cpm/{campaignId}/spots/{spaceId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($space_id !== null) {
            $resourcePath = str_replace(
                '{' . 'spaceId' . '}',
                ObjectSerializer::toPathValue($space_id),
                $resourcePath
            );
        }


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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost
     *
     * Add an ad zone to a CPM campaign (or re-price it).
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $space_id Ad zone id. (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest|null $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'][0])
    {
        $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostWithHttpInfo($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostWithHttpInfo
     *
     * Add an ad zone to a CPM campaign (or re-price it).
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id Ad zone id. (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest|null $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostWithHttpInfo($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'][0])
    {
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostAsync
     *
     * Add an ad zone to a CPM campaign (or re-price it).
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id Ad zone id. (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest|null $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostAsync($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'][0])
    {
        return $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostAsyncWithHttpInfo($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostAsyncWithHttpInfo
     *
     * Add an ad zone to a CPM campaign (or re-price it).
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id Ad zone id. (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest|null $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostAsyncWithHttpInfo($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id Ad zone id. (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest|null $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest($campaign_id, $space_id, $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'
            );
        }

        // verify the required parameter 'space_id' is set
        if ($space_id === null || (is_array($space_id) && count($space_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $space_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost'
            );
        }



        $resourcePath = '/api/v1/campaigns/cpm/{campaignId}/spots/{spaceId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($space_id !== null) {
            $resourcePath = str_replace(
                '{' . 'spaceId' . '}',
                ObjectSerializer::toPathValue($space_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request));
            } else {
                $httpBody = $api_v1_campaigns_cpm_campaign_id_spots_space_id_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut
     *
     * Switch a CPM campaign&#39;s zone spot on or off.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $space_id space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'][0])
    {
        $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutWithHttpInfo($campaign_id, $space_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutWithHttpInfo
     *
     * Switch a CPM campaign&#39;s zone spot on or off.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutWithHttpInfo($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'][0])
    {
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutRequest($campaign_id, $space_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutAsync
     *
     * Switch a CPM campaign&#39;s zone spot on or off.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutAsync($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'][0])
    {
        return $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutAsyncWithHttpInfo($campaign_id, $space_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutAsyncWithHttpInfo
     *
     * Switch a CPM campaign&#39;s zone spot on or off.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutAsyncWithHttpInfo($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutRequest($campaign_id, $space_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdPutRequest($campaign_id, $space_id, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'
            );
        }

        // verify the required parameter 'space_id' is set
        if ($space_id === null || (is_array($space_id) && count($space_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $space_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/cpm/{campaignId}/spots/{spaceId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($space_id !== null) {
            $resourcePath = str_replace(
                '{' . 'spaceId' . '}',
                ObjectSerializer::toPathValue($space_id),
                $resourcePath
            );
        }


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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut
     *
     * Set a CPM zone spot&#39;s bid or state.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $space_id space_id (required)
     * @param  string $type type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'][0])
    {
        $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutWithHttpInfo($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutWithHttpInfo
     *
     * Set a CPM zone spot&#39;s bid or state.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutWithHttpInfo($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'][0])
    {
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutAsync
     *
     * Set a CPM zone spot&#39;s bid or state.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutAsync($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'][0])
    {
        return $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutAsyncWithHttpInfo($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutAsyncWithHttpInfo
     *
     * Set a CPM zone spot&#39;s bid or state.
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutAsyncWithHttpInfo($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'
     *
     * @param  int $campaign_id (required)
     * @param  int $space_id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest($campaign_id, $space_id, $type, $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request, string $contentType = self::contentTypes['apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'
            );
        }

        // verify the required parameter 'space_id' is set
        if ($space_id === null || (is_array($space_id) && count($space_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $space_id when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'
            );
        }

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request' is set
        if ($api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request === null || (is_array($api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request) && count($api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request when calling apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut'
            );
        }


        $resourcePath = '/api/v1/campaigns/cpm/{campaignId}/spots/{spaceId}/{type}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($space_id !== null) {
            $resourcePath = str_replace(
                '{' . 'spaceId' . '}',
                ObjectSerializer::toPathValue($space_id),
                $resourcePath
            );
        }
        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request));
            } else {
                $httpBody = $api_v1_campaigns_cpm_campaign_id_spots_space_id_type_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsGet
     *
     * List the caller&#39;s campaigns across all types (or one type).
     *
     * @param  string|null $type type (optional, default to 'all')
     * @param  bool|null $deleted true / 1 &#x3D; list the soft-deleted campaigns instead (the panel&#39;s Deleted view); restore them with POST /api/v1/campaigns/mass-undelete. (optional)
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 50)
     * @param  string|null $fields CSV of fields to return; &#x60;*&#x60; returns everything available. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsGet($type = 'all', $deleted = null, $page = 1, $per_page = 50, $fields = null, string $contentType = self::contentTypes['apiV1CampaignsGet'][0])
    {
        list($response) = $this->apiV1CampaignsGetWithHttpInfo($type, $deleted, $page, $per_page, $fields, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsGetWithHttpInfo
     *
     * List the caller&#39;s campaigns across all types (or one type).
     *
     * @param  string|null $type (optional, default to 'all')
     * @param  bool|null $deleted true / 1 &#x3D; list the soft-deleted campaigns instead (the panel&#39;s Deleted view); restore them with POST /api/v1/campaigns/mass-undelete. (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $fields CSV of fields to return; &#x60;*&#x60; returns everything available. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsGetWithHttpInfo($type = 'all', $deleted = null, $page = 1, $per_page = 50, $fields = null, string $contentType = self::contentTypes['apiV1CampaignsGet'][0])
    {
        $request = $this->apiV1CampaignsGetRequest($type, $deleted, $page, $per_page, $fields, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsGet200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsGetAsync
     *
     * List the caller&#39;s campaigns across all types (or one type).
     *
     * @param  string|null $type (optional, default to 'all')
     * @param  bool|null $deleted true / 1 &#x3D; list the soft-deleted campaigns instead (the panel&#39;s Deleted view); restore them with POST /api/v1/campaigns/mass-undelete. (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $fields CSV of fields to return; &#x60;*&#x60; returns everything available. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsGetAsync($type = 'all', $deleted = null, $page = 1, $per_page = 50, $fields = null, string $contentType = self::contentTypes['apiV1CampaignsGet'][0])
    {
        return $this->apiV1CampaignsGetAsyncWithHttpInfo($type, $deleted, $page, $per_page, $fields, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsGetAsyncWithHttpInfo
     *
     * List the caller&#39;s campaigns across all types (or one type).
     *
     * @param  string|null $type (optional, default to 'all')
     * @param  bool|null $deleted true / 1 &#x3D; list the soft-deleted campaigns instead (the panel&#39;s Deleted view); restore them with POST /api/v1/campaigns/mass-undelete. (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $fields CSV of fields to return; &#x60;*&#x60; returns everything available. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsGetAsyncWithHttpInfo($type = 'all', $deleted = null, $page = 1, $per_page = 50, $fields = null, string $contentType = self::contentTypes['apiV1CampaignsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsGet200Response';
        $request = $this->apiV1CampaignsGetRequest($type, $deleted, $page, $per_page, $fields, $contentType);

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
     * Create request for operation 'apiV1CampaignsGet'
     *
     * @param  string|null $type (optional, default to 'all')
     * @param  bool|null $deleted true / 1 &#x3D; list the soft-deleted campaigns instead (the panel&#39;s Deleted view); restore them with POST /api/v1/campaigns/mass-undelete. (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $fields CSV of fields to return; &#x60;*&#x60; returns everything available. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsGetRequest($type = 'all', $deleted = null, $page = 1, $per_page = 50, $fields = null, string $contentType = self::contentTypes['apiV1CampaignsGet'][0])
    {



        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling CampaignsApi.apiV1CampaignsGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 200) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CampaignsApi.apiV1CampaignsGet, must be smaller than or equal to 200.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CampaignsApi.apiV1CampaignsGet, must be bigger than or equal to 1.');
        }
        


        $resourcePath = '/api/v1/campaigns';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $type,
            'type', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $deleted,
            'deleted', // param base name
            'boolean', // openApiType
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
            $fields,
            'fields', // param base name
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
     * Operation apiV1CampaignsMassDeletePost
     *
     * Soft-delete many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsMassDeletePost($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassDeletePost'][0])
    {
        $this->apiV1CampaignsMassDeletePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsMassDeletePostWithHttpInfo
     *
     * Soft-delete many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsMassDeletePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassDeletePost'][0])
    {
        $request = $this->apiV1CampaignsMassDeletePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsMassDeletePostAsync
     *
     * Soft-delete many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassDeletePostAsync($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassDeletePost'][0])
    {
        return $this->apiV1CampaignsMassDeletePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsMassDeletePostAsyncWithHttpInfo
     *
     * Soft-delete many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassDeletePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassDeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsMassDeletePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsMassDeletePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsMassDeletePostRequest($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassDeletePost'][0])
    {

        // verify the required parameter 'api_v1_campaigns_mass_delete_post_request' is set
        if ($api_v1_campaigns_mass_delete_post_request === null || (is_array($api_v1_campaigns_mass_delete_post_request) && count($api_v1_campaigns_mass_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_mass_delete_post_request when calling apiV1CampaignsMassDeletePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/mass-delete';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_mass_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_mass_delete_post_request));
            } else {
                $httpBody = $api_v1_campaigns_mass_delete_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsMassTogglePut
     *
     * Pause or resume many campaigns at once.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassTogglePutRequest $api_v1_campaigns_mass_toggle_put_request api_v1_campaigns_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsMassTogglePut($api_v1_campaigns_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsMassTogglePut'][0])
    {
        $this->apiV1CampaignsMassTogglePutWithHttpInfo($api_v1_campaigns_mass_toggle_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsMassTogglePutWithHttpInfo
     *
     * Pause or resume many campaigns at once.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassTogglePutRequest $api_v1_campaigns_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsMassTogglePutWithHttpInfo($api_v1_campaigns_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsMassTogglePut'][0])
    {
        $request = $this->apiV1CampaignsMassTogglePutRequest($api_v1_campaigns_mass_toggle_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsMassTogglePutAsync
     *
     * Pause or resume many campaigns at once.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassTogglePutRequest $api_v1_campaigns_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassTogglePutAsync($api_v1_campaigns_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsMassTogglePut'][0])
    {
        return $this->apiV1CampaignsMassTogglePutAsyncWithHttpInfo($api_v1_campaigns_mass_toggle_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsMassTogglePutAsyncWithHttpInfo
     *
     * Pause or resume many campaigns at once.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassTogglePutRequest $api_v1_campaigns_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassTogglePutAsyncWithHttpInfo($api_v1_campaigns_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsMassTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsMassTogglePutRequest($api_v1_campaigns_mass_toggle_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsMassTogglePut'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassTogglePutRequest $api_v1_campaigns_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsMassTogglePutRequest($api_v1_campaigns_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsMassTogglePut'][0])
    {

        // verify the required parameter 'api_v1_campaigns_mass_toggle_put_request' is set
        if ($api_v1_campaigns_mass_toggle_put_request === null || (is_array($api_v1_campaigns_mass_toggle_put_request) && count($api_v1_campaigns_mass_toggle_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_mass_toggle_put_request when calling apiV1CampaignsMassTogglePut'
            );
        }


        $resourcePath = '/api/v1/campaigns/mass-toggle';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_mass_toggle_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_mass_toggle_put_request));
            } else {
                $httpBody = $api_v1_campaigns_mass_toggle_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsMassUndeletePost
     *
     * Restore soft-deleted campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassUndeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsMassUndeletePost($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassUndeletePost'][0])
    {
        $this->apiV1CampaignsMassUndeletePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsMassUndeletePostWithHttpInfo
     *
     * Restore soft-deleted campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassUndeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsMassUndeletePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassUndeletePost'][0])
    {
        $request = $this->apiV1CampaignsMassUndeletePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsMassUndeletePostAsync
     *
     * Restore soft-deleted campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassUndeletePostAsync($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassUndeletePost'][0])
    {
        return $this->apiV1CampaignsMassUndeletePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsMassUndeletePostAsyncWithHttpInfo
     *
     * Restore soft-deleted campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsMassUndeletePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassUndeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsMassUndeletePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsMassUndeletePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsMassUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsMassUndeletePostRequest($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsMassUndeletePost'][0])
    {

        // verify the required parameter 'api_v1_campaigns_mass_delete_post_request' is set
        if ($api_v1_campaigns_mass_delete_post_request === null || (is_array($api_v1_campaigns_mass_delete_post_request) && count($api_v1_campaigns_mass_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_mass_delete_post_request when calling apiV1CampaignsMassUndeletePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/mass-undelete';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_mass_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_mass_delete_post_request));
            } else {
                $httpBody = $api_v1_campaigns_mass_delete_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsPurgePost
     *
     * Permanently delete soft-deleted campaigns and their ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsPurgePost($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsPurgePost'][0])
    {
        $this->apiV1CampaignsPurgePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsPurgePostWithHttpInfo
     *
     * Permanently delete soft-deleted campaigns and their ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsPurgePostWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsPurgePost'][0])
    {
        $request = $this->apiV1CampaignsPurgePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsPurgePostAsync
     *
     * Permanently delete soft-deleted campaigns and their ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsPurgePostAsync($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsPurgePost'][0])
    {
        return $this->apiV1CampaignsPurgePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsPurgePostAsyncWithHttpInfo
     *
     * Permanently delete soft-deleted campaigns and their ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsPurgePostAsyncWithHttpInfo($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsPurgePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsPurgePostRequest($api_v1_campaigns_mass_delete_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsPurgePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsMassDeletePostRequest $api_v1_campaigns_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsPurgePostRequest($api_v1_campaigns_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsPurgePost'][0])
    {

        // verify the required parameter 'api_v1_campaigns_mass_delete_post_request' is set
        if ($api_v1_campaigns_mass_delete_post_request === null || (is_array($api_v1_campaigns_mass_delete_post_request) && count($api_v1_campaigns_mass_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_mass_delete_post_request when calling apiV1CampaignsPurgePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/purge';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_mass_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_mass_delete_post_request));
            } else {
                $httpBody = $api_v1_campaigns_mass_delete_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet
     *
     * List per-zone bid overrides for a RON ad.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet($campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'][0])
    {
        list($response) = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetWithHttpInfo($campaign_id, $ad_id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetWithHttpInfo
     *
     * List per-zone bid overrides for a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetWithHttpInfo($campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'][0])
    {
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetRequest($campaign_id, $ad_id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetAsync
     *
     * List per-zone bid overrides for a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetAsync($campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'][0])
    {
        return $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetAsyncWithHttpInfo($campaign_id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetAsyncWithHttpInfo
     *
     * List per-zone bid overrides for a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetAsyncWithHttpInfo($campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response';
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetRequest($campaign_id, $ad_id, $contentType);

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
     * Create request for operation 'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGetRequest($campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut
     *
     * Replace the per-zone bid overrides on a RON ad.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'][0])
    {
        list($response) = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutWithHttpInfo
     *
     * Replace the per-zone bid overrides on a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'][0])
    {
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutAsync
     *
     * Replace the per-zone bid overrides on a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutAsync($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'][0])
    {
        return $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutAsyncWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutAsyncWithHttpInfo
     *
     * Replace the per-zone bid overrides on a RON ad.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutAsyncWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response';
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request' is set
        if ($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request === null || (is_array($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request) && count($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request));
            } else {
                $httpBody = $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete
     *
     * Clear a single per-zone bid override.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  int $zone_id zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete($campaign_id, $ad_id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'][0])
    {
        list($response) = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteWithHttpInfo($campaign_id, $ad_id, $zone_id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteWithHttpInfo
     *
     * Clear a single per-zone bid override.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteWithHttpInfo($campaign_id, $ad_id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'][0])
    {
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteRequest($campaign_id, $ad_id, $zone_id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteAsync
     *
     * Clear a single per-zone bid override.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteAsync($campaign_id, $ad_id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'][0])
    {
        return $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteAsyncWithHttpInfo($campaign_id, $ad_id, $zone_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteAsyncWithHttpInfo
     *
     * Clear a single per-zone bid override.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteAsyncWithHttpInfo($campaign_id, $ad_id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response';
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteRequest($campaign_id, $ad_id, $zone_id, $contentType);

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
     * Create request for operation 'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDeleteRequest($campaign_id, $ad_id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'
            );
        }

        // verify the required parameter 'zone_id' is set
        if ($zone_id === null || (is_array($zone_id) && count($zone_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $zone_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/{zoneId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }
        // path params
        if ($zone_id !== null) {
            $resourcePath = str_replace(
                '{' . 'zoneId' . '}',
                ObjectSerializer::toPathValue($zone_id),
                $resourcePath
            );
        }


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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut
     *
     * Switch \&quot;only these zones\&quot; mode for a RON ad&#39;s per-zone bids.
     *
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'][0])
    {
        $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutWithHttpInfo
     *
     * Switch \&quot;only these zones\&quot; mode for a RON ad&#39;s per-zone bids.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'][0])
    {
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutAsync
     *
     * Switch \&quot;only these zones\&quot; mode for a RON ad&#39;s per-zone bids.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutAsync($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'][0])
    {
        return $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutAsyncWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutAsyncWithHttpInfo
     *
     * Switch \&quot;only these zones\&quot; mode for a RON ad&#39;s per-zone bids.
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutAsyncWithHttpInfo($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'
     *
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest($campaign_id, $ad_id, $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'][0])
    {

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request' is set
        if ($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request === null || (is_array($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request) && count($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request when calling apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/zones-only';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request));
            } else {
                $httpBody = $api_v1_campaigns_ron_campaign_id_ads_ad_id_zone_bids_zones_only_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListGet
     *
     * The Premium-sites box of a RON campaign.
     *
     * @param  int $id id (required)
     * @param  int|null $main_catid main_catid (optional)
     * @param  string|null $catids Comma-separated category ids, with main_catid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsRonIdSiteListGet($id, $main_catid = null, $catids = null, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListGet'][0])
    {
        $this->apiV1CampaignsRonIdSiteListGetWithHttpInfo($id, $main_catid, $catids, $contentType);
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListGetWithHttpInfo
     *
     * The Premium-sites box of a RON campaign.
     *
     * @param  int $id (required)
     * @param  int|null $main_catid (optional)
     * @param  string|null $catids Comma-separated category ids, with main_catid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonIdSiteListGetWithHttpInfo($id, $main_catid = null, $catids = null, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListGet'][0])
    {
        $request = $this->apiV1CampaignsRonIdSiteListGetRequest($id, $main_catid, $catids, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsRonIdSiteListGetAsync
     *
     * The Premium-sites box of a RON campaign.
     *
     * @param  int $id (required)
     * @param  int|null $main_catid (optional)
     * @param  string|null $catids Comma-separated category ids, with main_catid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdSiteListGetAsync($id, $main_catid = null, $catids = null, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListGet'][0])
    {
        return $this->apiV1CampaignsRonIdSiteListGetAsyncWithHttpInfo($id, $main_catid, $catids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListGetAsyncWithHttpInfo
     *
     * The Premium-sites box of a RON campaign.
     *
     * @param  int $id (required)
     * @param  int|null $main_catid (optional)
     * @param  string|null $catids Comma-separated category ids, with main_catid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdSiteListGetAsyncWithHttpInfo($id, $main_catid = null, $catids = null, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsRonIdSiteListGetRequest($id, $main_catid, $catids, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsRonIdSiteListGet'
     *
     * @param  int $id (required)
     * @param  int|null $main_catid (optional)
     * @param  string|null $catids Comma-separated category ids, with main_catid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonIdSiteListGetRequest($id, $main_catid = null, $catids = null, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsRonIdSiteListGet'
            );
        }




        $resourcePath = '/api/v1/campaigns/ron/{id}/site-list';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $main_catid,
            'main_catid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $catids,
            'catids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsRonIdSiteListSiteIdPut
     *
     * Set one site of the Premium-sites box active or blocked.
     *
     * @param  int $id id (required)
     * @param  int $site_id site_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonIdSiteListSiteIdPutRequest $api_v1_campaigns_ron_id_site_list_site_id_put_request api_v1_campaigns_ron_id_site_list_site_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsRonIdSiteListSiteIdPut($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'][0])
    {
        $this->apiV1CampaignsRonIdSiteListSiteIdPutWithHttpInfo($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListSiteIdPutWithHttpInfo
     *
     * Set one site of the Premium-sites box active or blocked.
     *
     * @param  int $id (required)
     * @param  int $site_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonIdSiteListSiteIdPutRequest $api_v1_campaigns_ron_id_site_list_site_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonIdSiteListSiteIdPutWithHttpInfo($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'][0])
    {
        $request = $this->apiV1CampaignsRonIdSiteListSiteIdPutRequest($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListSiteIdPutAsync
     *
     * Set one site of the Premium-sites box active or blocked.
     *
     * @param  int $id (required)
     * @param  int $site_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonIdSiteListSiteIdPutRequest $api_v1_campaigns_ron_id_site_list_site_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdSiteListSiteIdPutAsync($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'][0])
    {
        return $this->apiV1CampaignsRonIdSiteListSiteIdPutAsyncWithHttpInfo($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonIdSiteListSiteIdPutAsyncWithHttpInfo
     *
     * Set one site of the Premium-sites box active or blocked.
     *
     * @param  int $id (required)
     * @param  int $site_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonIdSiteListSiteIdPutRequest $api_v1_campaigns_ron_id_site_list_site_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdSiteListSiteIdPutAsyncWithHttpInfo($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsRonIdSiteListSiteIdPutRequest($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsRonIdSiteListSiteIdPut'
     *
     * @param  int $id (required)
     * @param  int $site_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsRonIdSiteListSiteIdPutRequest $api_v1_campaigns_ron_id_site_list_site_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonIdSiteListSiteIdPutRequest($id, $site_id, $api_v1_campaigns_ron_id_site_list_site_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsRonIdSiteListSiteIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsRonIdSiteListSiteIdPut'
            );
        }

        // verify the required parameter 'site_id' is set
        if ($site_id === null || (is_array($site_id) && count($site_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $site_id when calling apiV1CampaignsRonIdSiteListSiteIdPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_ron_id_site_list_site_id_put_request' is set
        if ($api_v1_campaigns_ron_id_site_list_site_id_put_request === null || (is_array($api_v1_campaigns_ron_id_site_list_site_id_put_request) && count($api_v1_campaigns_ron_id_site_list_site_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_ron_id_site_list_site_id_put_request when calling apiV1CampaignsRonIdSiteListSiteIdPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{id}/site-list/{siteId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }
        // path params
        if ($site_id !== null) {
            $resourcePath = str_replace(
                '{' . 'siteId' . '}',
                ObjectSerializer::toPathValue($site_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_ron_id_site_list_site_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_ron_id_site_list_site_id_put_request));
            } else {
                $httpBody = $api_v1_campaigns_ron_id_site_list_site_id_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsRonIdZonesGet
     *
     * List the zones a RON campaign is linked to.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsRonIdZonesGet($id, string $contentType = self::contentTypes['apiV1CampaignsRonIdZonesGet'][0])
    {
        list($response) = $this->apiV1CampaignsRonIdZonesGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsRonIdZonesGetWithHttpInfo
     *
     * List the zones a RON campaign is linked to.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRonIdZonesGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CampaignsRonIdZonesGet'][0])
    {
        $request = $this->apiV1CampaignsRonIdZonesGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRonIdZonesGetAsync
     *
     * List the zones a RON campaign is linked to.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdZonesGetAsync($id, string $contentType = self::contentTypes['apiV1CampaignsRonIdZonesGet'][0])
    {
        return $this->apiV1CampaignsRonIdZonesGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRonIdZonesGetAsyncWithHttpInfo
     *
     * List the zones a RON campaign is linked to.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRonIdZonesGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CampaignsRonIdZonesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsRonIdZonesGet200Response';
        $request = $this->apiV1CampaignsRonIdZonesGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CampaignsRonIdZonesGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRonIdZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRonIdZonesGetRequest($id, string $contentType = self::contentTypes['apiV1CampaignsRonIdZonesGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsRonIdZonesGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/ron/{id}/zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsRtbIdTestRtbGet
     *
     * Context for testing an RTB campaign&#39;s endpoint.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsRtbIdTestRtbGet($id, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'][0])
    {
        $this->apiV1CampaignsRtbIdTestRtbGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CampaignsRtbIdTestRtbGetWithHttpInfo
     *
     * Context for testing an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRtbIdTestRtbGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'][0])
    {
        $request = $this->apiV1CampaignsRtbIdTestRtbGetRequest($id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsRtbIdTestRtbGetAsync
     *
     * Context for testing an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRtbIdTestRtbGetAsync($id, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'][0])
    {
        return $this->apiV1CampaignsRtbIdTestRtbGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRtbIdTestRtbGetAsyncWithHttpInfo
     *
     * Context for testing an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRtbIdTestRtbGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsRtbIdTestRtbGetRequest($id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsRtbIdTestRtbGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRtbIdTestRtbGetRequest($id, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsRtbIdTestRtbGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/rtb/{id}/test-rtb';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsRtbIdTestRtbPost
     *
     * Fire a test bid request at an RTB campaign&#39;s endpoint.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\RtbTestRequest|null $rtb_test_request rtb_test_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\RtbTestResult|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsRtbIdTestRtbPost($id, $rtb_test_request = null, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'][0])
    {
        list($response) = $this->apiV1CampaignsRtbIdTestRtbPostWithHttpInfo($id, $rtb_test_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsRtbIdTestRtbPostWithHttpInfo
     *
     * Fire a test bid request at an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\RtbTestRequest|null $rtb_test_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\RtbTestResult|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsRtbIdTestRtbPostWithHttpInfo($id, $rtb_test_request = null, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'][0])
    {
        $request = $this->apiV1CampaignsRtbIdTestRtbPostRequest($id, $rtb_test_request, $contentType);

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
                        '\GoAdServerApi\Model\RtbTestResult',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\RtbTestResult',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\RtbTestResult',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsRtbIdTestRtbPostAsync
     *
     * Fire a test bid request at an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\RtbTestRequest|null $rtb_test_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRtbIdTestRtbPostAsync($id, $rtb_test_request = null, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'][0])
    {
        return $this->apiV1CampaignsRtbIdTestRtbPostAsyncWithHttpInfo($id, $rtb_test_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsRtbIdTestRtbPostAsyncWithHttpInfo
     *
     * Fire a test bid request at an RTB campaign&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\RtbTestRequest|null $rtb_test_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsRtbIdTestRtbPostAsyncWithHttpInfo($id, $rtb_test_request = null, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\RtbTestResult';
        $request = $this->apiV1CampaignsRtbIdTestRtbPostRequest($id, $rtb_test_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsRtbIdTestRtbPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\RtbTestRequest|null $rtb_test_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsRtbIdTestRtbPostRequest($id, $rtb_test_request = null, string $contentType = self::contentTypes['apiV1CampaignsRtbIdTestRtbPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsRtbIdTestRtbPost'
            );
        }



        $resourcePath = '/api/v1/campaigns/rtb/{id}/test-rtb';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($rtb_test_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($rtb_test_request));
            } else {
                $httpBody = $rtb_test_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsSaveCategoriesPut
     *
     * Assign categories to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveCategoriesPutRequest $api_v1_campaigns_save_categories_put_request api_v1_campaigns_save_categories_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveCategoriesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsSaveCategoriesPut($api_v1_campaigns_save_categories_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveCategoriesPut'][0])
    {
        $this->apiV1CampaignsSaveCategoriesPutWithHttpInfo($api_v1_campaigns_save_categories_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsSaveCategoriesPutWithHttpInfo
     *
     * Assign categories to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveCategoriesPutRequest $api_v1_campaigns_save_categories_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveCategoriesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsSaveCategoriesPutWithHttpInfo($api_v1_campaigns_save_categories_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveCategoriesPut'][0])
    {
        $request = $this->apiV1CampaignsSaveCategoriesPutRequest($api_v1_campaigns_save_categories_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsSaveCategoriesPutAsync
     *
     * Assign categories to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveCategoriesPutRequest $api_v1_campaigns_save_categories_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveCategoriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsSaveCategoriesPutAsync($api_v1_campaigns_save_categories_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveCategoriesPut'][0])
    {
        return $this->apiV1CampaignsSaveCategoriesPutAsyncWithHttpInfo($api_v1_campaigns_save_categories_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsSaveCategoriesPutAsyncWithHttpInfo
     *
     * Assign categories to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveCategoriesPutRequest $api_v1_campaigns_save_categories_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveCategoriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsSaveCategoriesPutAsyncWithHttpInfo($api_v1_campaigns_save_categories_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveCategoriesPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsSaveCategoriesPutRequest($api_v1_campaigns_save_categories_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsSaveCategoriesPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveCategoriesPutRequest $api_v1_campaigns_save_categories_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveCategoriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsSaveCategoriesPutRequest($api_v1_campaigns_save_categories_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveCategoriesPut'][0])
    {

        // verify the required parameter 'api_v1_campaigns_save_categories_put_request' is set
        if ($api_v1_campaigns_save_categories_put_request === null || (is_array($api_v1_campaigns_save_categories_put_request) && count($api_v1_campaigns_save_categories_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_save_categories_put_request when calling apiV1CampaignsSaveCategoriesPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/save-categories';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_save_categories_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_save_categories_put_request));
            } else {
                $httpBody = $api_v1_campaigns_save_categories_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsSaveTagsPut
     *
     * Assign tags (campaign groups) to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveTagsPutRequest $api_v1_campaigns_save_tags_put_request api_v1_campaigns_save_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveTagsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsSaveTagsPut($api_v1_campaigns_save_tags_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveTagsPut'][0])
    {
        $this->apiV1CampaignsSaveTagsPutWithHttpInfo($api_v1_campaigns_save_tags_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsSaveTagsPutWithHttpInfo
     *
     * Assign tags (campaign groups) to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveTagsPutRequest $api_v1_campaigns_save_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveTagsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsSaveTagsPutWithHttpInfo($api_v1_campaigns_save_tags_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveTagsPut'][0])
    {
        $request = $this->apiV1CampaignsSaveTagsPutRequest($api_v1_campaigns_save_tags_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsSaveTagsPutAsync
     *
     * Assign tags (campaign groups) to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveTagsPutRequest $api_v1_campaigns_save_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsSaveTagsPutAsync($api_v1_campaigns_save_tags_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveTagsPut'][0])
    {
        return $this->apiV1CampaignsSaveTagsPutAsyncWithHttpInfo($api_v1_campaigns_save_tags_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsSaveTagsPutAsyncWithHttpInfo
     *
     * Assign tags (campaign groups) to many campaigns.
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveTagsPutRequest $api_v1_campaigns_save_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsSaveTagsPutAsyncWithHttpInfo($api_v1_campaigns_save_tags_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveTagsPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsSaveTagsPutRequest($api_v1_campaigns_save_tags_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsSaveTagsPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1CampaignsSaveTagsPutRequest $api_v1_campaigns_save_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsSaveTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsSaveTagsPutRequest($api_v1_campaigns_save_tags_put_request, string $contentType = self::contentTypes['apiV1CampaignsSaveTagsPut'][0])
    {

        // verify the required parameter 'api_v1_campaigns_save_tags_put_request' is set
        if ($api_v1_campaigns_save_tags_put_request === null || (is_array($api_v1_campaigns_save_tags_put_request) && count($api_v1_campaigns_save_tags_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_save_tags_put_request when calling apiV1CampaignsSaveTagsPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/save-tags';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_save_tags_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_save_tags_put_request));
            } else {
                $httpBody = $api_v1_campaigns_save_tags_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch
     *
     * Pause or unpause a single ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'][0])
    {
        list($response) = $this->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchWithHttpInfo
     *
     * Pause or unpause a single ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchAsync
     *
     * Pause or unpause a single ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchAsync($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchAsyncWithHttpInfo
     *
     * Pause or unpause a single ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdActivePatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_active_patch_request' is set
        if ($api_v1_campaigns_type_id_active_patch_request === null || (is_array($api_v1_campaigns_type_id_active_patch_request) && count($api_v1_campaigns_type_id_active_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_active_patch_request when calling apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/active';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_active_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_active_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_active_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch
     *
     * Set a single ad&#39;s bid.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'][0])
    {
        list($response) = $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchWithHttpInfo
     *
     * Set a single ad&#39;s bid.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchAsync
     *
     * Set a single ad&#39;s bid.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchAsync($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchAsyncWithHttpInfo
     *
     * Set a single ad&#39;s bid.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request) && count($api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bid';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_ad_id_bid_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut
     *
     * Set per-country bids for a RON ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutWithHttpInfo
     *
     * Set per-country bids for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutAsync
     *
     * Set per-country bids for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutAsync($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutAsyncWithHttpInfo
     *
     * Set per-country bids for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request) && count($api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request when calling apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bids-per-geo';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_ad_id_bids_per_geo_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdClonePost
     *
     * Clone a single ad within a campaign.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdClonePost($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'][0])
    {
        list($response) = $this->apiV1CampaignsTypeCampaignIdAdsAdIdClonePostWithHttpInfo($type, $campaign_id, $ad_id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdClonePostWithHttpInfo
     *
     * Clone a single ad within a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdClonePostWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdClonePostRequest($type, $campaign_id, $ad_id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdClonePostAsync
     *
     * Clone a single ad within a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdClonePostAsync($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdClonePostAsyncWithHttpInfo($type, $campaign_id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdClonePostAsyncWithHttpInfo
     *
     * Clone a single ad within a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdClonePostAsyncWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdClonePostRequest($type, $campaign_id, $ad_id, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdClonePostRequest($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdClonePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/clone';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut
     *
     * Set per-ad country targeting for a RON ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutWithHttpInfo
     *
     * Set per-ad country targeting for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutAsync
     *
     * Set per-ad country targeting for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutAsync($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutAsyncWithHttpInfo
     *
     * Set per-ad country targeting for a RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request) && count($api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request when calling apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/countries';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_ad_id_countries_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdDelete
     *
     * Soft-delete an ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdDelete($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdDeleteWithHttpInfo($type, $campaign_id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdDeleteWithHttpInfo
     *
     * Soft-delete an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdDeleteWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdDeleteRequest($type, $campaign_id, $ad_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdDeleteAsync
     *
     * Soft-delete an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdDeleteAsync($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdDeleteAsyncWithHttpInfo($type, $campaign_id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdDeleteAsyncWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdDeleteRequest($type, $campaign_id, $ad_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdDelete'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdDeleteRequest($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdDelete'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdDelete'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            [],
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdGet
     *
     * Show one ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdGet($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdGetWithHttpInfo($type, $campaign_id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdGetWithHttpInfo
     *
     * Show one ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdGetWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdGetRequest($type, $campaign_id, $ad_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdGetAsync
     *
     * Show one ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdGetAsync($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdGetAsyncWithHttpInfo($type, $campaign_id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdGetAsyncWithHttpInfo
     *
     * Show one ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdGetAsyncWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdGetRequest($type, $campaign_id, $ad_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdGet'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdGetRequest($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdGet'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdGet'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            [],
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
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet
     *
     * Shoppable video — shown and clicks per overlay.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \DateTime|null $from from (optional)
     * @param  \DateTime|null $to to (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet($type, $campaign_id, $ad_id, $from = null, $to = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetWithHttpInfo($type, $campaign_id, $ad_id, $from, $to, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetWithHttpInfo
     *
     * Shoppable video — shown and clicks per overlay.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetWithHttpInfo($type, $campaign_id, $ad_id, $from = null, $to = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetRequest($type, $campaign_id, $ad_id, $from, $to, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetAsync
     *
     * Shoppable video — shown and clicks per overlay.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetAsync($type, $campaign_id, $ad_id, $from = null, $to = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetAsyncWithHttpInfo($type, $campaign_id, $ad_id, $from, $to, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetAsyncWithHttpInfo
     *
     * Shoppable video — shown and clicks per overlay.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetAsyncWithHttpInfo($type, $campaign_id, $ad_id, $from = null, $to = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetRequest($type, $campaign_id, $ad_id, $from, $to, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGetRequest($type, $campaign_id, $ad_id, $from = null, $to = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet'
            );
        }




        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/overlay-stats';
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


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdPut
     *
     * Update an ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request api_v1_campaigns_type_campaign_id_ads_ad_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdPut($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdPutWithHttpInfo
     *
     * Update an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdPutWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdPutAsync
     *
     * Update an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdPutAsync($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdPutAsyncWithHttpInfo
     *
     * Update an ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdPutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdPutRequest($type, $campaign_id, $ad_id, $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_ad_id_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_ad_id_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_ad_id_put_request) && count($api_v1_campaigns_type_campaign_id_ads_ad_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request when calling apiV1CampaignsTypeCampaignIdAdsAdIdPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_ad_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_ad_id_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_ad_id_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut
     *
     * Restore a deleted RON ad.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutWithHttpInfo($type, $campaign_id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutWithHttpInfo
     *
     * Restore a deleted RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutRequest($type, $campaign_id, $ad_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutAsync
     *
     * Restore a deleted RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutAsync($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutAsyncWithHttpInfo($type, $campaign_id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutAsyncWithHttpInfo
     *
     * Restore a deleted RON ad.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutAsyncWithHttpInfo($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutRequest($type, $campaign_id, $ad_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsAdIdRestorePutRequest($type, $campaign_id, $ad_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/{adId}/restore';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }
        // path params
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet
     *
     * Campaigns this campaign&#39;s ads can be copied into.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetWithHttpInfo($type, $campaign_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetWithHttpInfo
     *
     * Campaigns this campaign&#39;s ads can be copied into.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetWithHttpInfo($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetRequest($type, $campaign_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetAsync
     *
     * Campaigns this campaign&#39;s ads can be copied into.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetAsync($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetAsyncWithHttpInfo($type, $campaign_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetAsyncWithHttpInfo
     *
     * Campaigns this campaign&#39;s ads can be copied into.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetAsyncWithHttpInfo($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetRequest($type, $campaign_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsCopyTargetsGetRequest($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/copy-targets';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeCampaignIdAdsCsvImportPost
     *
     * Import native ads from a CSV — one ad per row, or one ad per group.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \SplFileObject $file file (required)
     * @param  string|null $image_column image_column (optional)
     * @param  string|null $url_column url_column (optional)
     * @param  string|null $name_column name_column (optional)
     * @param  string|null $group_column Header to group rows by. Omit for one ad per row. Never guessed. (optional)
     * @param  string|null $groups The previous request&#39;s &#x60;groups&#x60; answer, verbatim, when a group spans requests. (optional)
     * @param  int|null $folder_id Existing media-bank folder to file the creatives into. 0/absent &#x3D; unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder of this name under folder_id and file into it. (optional)
     * @param  string|null $folder_per_group Grouped mode only: a subfolder per group that has more than one creative. A one-image group stays in the parent. (optional)
     * @param  string|null $name_prefix Prepended to every ad name, separated by a space. (optional)
     * @param  string|null $square_mode How each image is squared. pad keeps the whole image and adds bars; crop keeps the centre square; none leaves it alone. (optional, default to 'pad')
     * @param  string|null $pad_color Bar colour for pad on a source with no alpha. PNG/GIF sources pad transparent instead. (optional, default to '#FFFFFF')
     * @param  string|null $crop_square Legacy: 0 &#x3D; none, anything else &#x3D; crop. square_mode takes precedence. (optional)
     * @param  string|null $set_active set_active (optional, default to '1')
     * @param  float|null $bid bid (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response
     */
    public function apiV1CampaignsTypeCampaignIdAdsCsvImportPost($type, $campaign_id, $file, $image_column = null, $url_column = null, $name_column = null, $group_column = null, $groups = null, $folder_id = null, $folder_name = null, $folder_per_group = null, $name_prefix = null, $square_mode = 'pad', $pad_color = '#FFFFFF', $crop_square = null, $set_active = '1', $bid = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'][0])
    {
        list($response) = $this->apiV1CampaignsTypeCampaignIdAdsCsvImportPostWithHttpInfo($type, $campaign_id, $file, $image_column, $url_column, $name_column, $group_column, $groups, $folder_id, $folder_name, $folder_per_group, $name_prefix, $square_mode, $pad_color, $crop_square, $set_active, $bid, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCsvImportPostWithHttpInfo
     *
     * Import native ads from a CSV — one ad per row, or one ad per group.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject $file (required)
     * @param  string|null $image_column (optional)
     * @param  string|null $url_column (optional)
     * @param  string|null $name_column (optional)
     * @param  string|null $group_column Header to group rows by. Omit for one ad per row. Never guessed. (optional)
     * @param  string|null $groups The previous request&#39;s &#x60;groups&#x60; answer, verbatim, when a group spans requests. (optional)
     * @param  int|null $folder_id Existing media-bank folder to file the creatives into. 0/absent &#x3D; unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder of this name under folder_id and file into it. (optional)
     * @param  string|null $folder_per_group Grouped mode only: a subfolder per group that has more than one creative. A one-image group stays in the parent. (optional)
     * @param  string|null $name_prefix Prepended to every ad name, separated by a space. (optional)
     * @param  string|null $square_mode How each image is squared. pad keeps the whole image and adds bars; crop keeps the centre square; none leaves it alone. (optional, default to 'pad')
     * @param  string|null $pad_color Bar colour for pad on a source with no alpha. PNG/GIF sources pad transparent instead. (optional, default to '#FFFFFF')
     * @param  string|null $crop_square Legacy: 0 &#x3D; none, anything else &#x3D; crop. square_mode takes precedence. (optional)
     * @param  string|null $set_active (optional, default to '1')
     * @param  float|null $bid (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsCsvImportPostWithHttpInfo($type, $campaign_id, $file, $image_column = null, $url_column = null, $name_column = null, $group_column = null, $groups = null, $folder_id = null, $folder_name = null, $folder_per_group = null, $name_prefix = null, $square_mode = 'pad', $pad_color = '#FFFFFF', $crop_square = null, $set_active = '1', $bid = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsCsvImportPostRequest($type, $campaign_id, $file, $image_column, $url_column, $name_column, $group_column, $groups, $folder_id, $folder_name, $folder_per_group, $name_prefix, $square_mode, $pad_color, $crop_square, $set_active, $bid, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCsvImportPostAsync
     *
     * Import native ads from a CSV — one ad per row, or one ad per group.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject $file (required)
     * @param  string|null $image_column (optional)
     * @param  string|null $url_column (optional)
     * @param  string|null $name_column (optional)
     * @param  string|null $group_column Header to group rows by. Omit for one ad per row. Never guessed. (optional)
     * @param  string|null $groups The previous request&#39;s &#x60;groups&#x60; answer, verbatim, when a group spans requests. (optional)
     * @param  int|null $folder_id Existing media-bank folder to file the creatives into. 0/absent &#x3D; unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder of this name under folder_id and file into it. (optional)
     * @param  string|null $folder_per_group Grouped mode only: a subfolder per group that has more than one creative. A one-image group stays in the parent. (optional)
     * @param  string|null $name_prefix Prepended to every ad name, separated by a space. (optional)
     * @param  string|null $square_mode How each image is squared. pad keeps the whole image and adds bars; crop keeps the centre square; none leaves it alone. (optional, default to 'pad')
     * @param  string|null $pad_color Bar colour for pad on a source with no alpha. PNG/GIF sources pad transparent instead. (optional, default to '#FFFFFF')
     * @param  string|null $crop_square Legacy: 0 &#x3D; none, anything else &#x3D; crop. square_mode takes precedence. (optional)
     * @param  string|null $set_active (optional, default to '1')
     * @param  float|null $bid (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsCsvImportPostAsync($type, $campaign_id, $file, $image_column = null, $url_column = null, $name_column = null, $group_column = null, $groups = null, $folder_id = null, $folder_name = null, $folder_per_group = null, $name_prefix = null, $square_mode = 'pad', $pad_color = '#FFFFFF', $crop_square = null, $set_active = '1', $bid = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsCsvImportPostAsyncWithHttpInfo($type, $campaign_id, $file, $image_column, $url_column, $name_column, $group_column, $groups, $folder_id, $folder_name, $folder_per_group, $name_prefix, $square_mode, $pad_color, $crop_square, $set_active, $bid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsCsvImportPostAsyncWithHttpInfo
     *
     * Import native ads from a CSV — one ad per row, or one ad per group.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject $file (required)
     * @param  string|null $image_column (optional)
     * @param  string|null $url_column (optional)
     * @param  string|null $name_column (optional)
     * @param  string|null $group_column Header to group rows by. Omit for one ad per row. Never guessed. (optional)
     * @param  string|null $groups The previous request&#39;s &#x60;groups&#x60; answer, verbatim, when a group spans requests. (optional)
     * @param  int|null $folder_id Existing media-bank folder to file the creatives into. 0/absent &#x3D; unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder of this name under folder_id and file into it. (optional)
     * @param  string|null $folder_per_group Grouped mode only: a subfolder per group that has more than one creative. A one-image group stays in the parent. (optional)
     * @param  string|null $name_prefix Prepended to every ad name, separated by a space. (optional)
     * @param  string|null $square_mode How each image is squared. pad keeps the whole image and adds bars; crop keeps the centre square; none leaves it alone. (optional, default to 'pad')
     * @param  string|null $pad_color Bar colour for pad on a source with no alpha. PNG/GIF sources pad transparent instead. (optional, default to '#FFFFFF')
     * @param  string|null $crop_square Legacy: 0 &#x3D; none, anything else &#x3D; crop. square_mode takes precedence. (optional)
     * @param  string|null $set_active (optional, default to '1')
     * @param  float|null $bid (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsCsvImportPostAsyncWithHttpInfo($type, $campaign_id, $file, $image_column = null, $url_column = null, $name_column = null, $group_column = null, $groups = null, $folder_id = null, $folder_name = null, $folder_per_group = null, $name_prefix = null, $square_mode = 'pad', $pad_color = '#FFFFFF', $crop_square = null, $set_active = '1', $bid = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsCsvImportPostRequest($type, $campaign_id, $file, $image_column, $url_column, $name_column, $group_column, $groups, $folder_id, $folder_name, $folder_per_group, $name_prefix, $square_mode, $pad_color, $crop_square, $set_active, $bid, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsCsvImportPost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject $file (required)
     * @param  string|null $image_column (optional)
     * @param  string|null $url_column (optional)
     * @param  string|null $name_column (optional)
     * @param  string|null $group_column Header to group rows by. Omit for one ad per row. Never guessed. (optional)
     * @param  string|null $groups The previous request&#39;s &#x60;groups&#x60; answer, verbatim, when a group spans requests. (optional)
     * @param  int|null $folder_id Existing media-bank folder to file the creatives into. 0/absent &#x3D; unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder of this name under folder_id and file into it. (optional)
     * @param  string|null $folder_per_group Grouped mode only: a subfolder per group that has more than one creative. A one-image group stays in the parent. (optional)
     * @param  string|null $name_prefix Prepended to every ad name, separated by a space. (optional)
     * @param  string|null $square_mode How each image is squared. pad keeps the whole image and adds bars; crop keeps the centre square; none leaves it alone. (optional, default to 'pad')
     * @param  string|null $pad_color Bar colour for pad on a source with no alpha. PNG/GIF sources pad transparent instead. (optional, default to '#FFFFFF')
     * @param  string|null $crop_square Legacy: 0 &#x3D; none, anything else &#x3D; crop. square_mode takes precedence. (optional)
     * @param  string|null $set_active (optional, default to '1')
     * @param  float|null $bid (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsCsvImportPostRequest($type, $campaign_id, $file, $image_column = null, $url_column = null, $name_column = null, $group_column = null, $groups = null, $folder_id = null, $folder_name = null, $folder_per_group = null, $name_prefix = null, $square_mode = 'pad', $pad_color = '#FFFFFF', $crop_square = null, $set_active = '1', $bid = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsCsvImportPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsCsvImportPost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsCsvImportPost'
            );
        }

        // verify the required parameter 'file' is set
        if ($file === null || (is_array($file) && count($file) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $file when calling apiV1CampaignsTypeCampaignIdAdsCsvImportPost'
            );
        }
















        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/csv-import';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }

        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'file' => $file,
            'image_column' => $image_column,
            'url_column' => $url_column,
            'name_column' => $name_column,
            'group_column' => $group_column,
            'groups' => $groups,
            'folder_id' => $folder_id,
            'folder_name' => $folder_name,
            'folder_per_group' => $folder_per_group,
            'name_prefix' => $name_prefix,
            'square_mode' => $square_mode,
            'pad_color' => $pad_color,
            'crop_square' => $crop_square,
            'set_active' => $set_active,
            'bid' => $bid,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

        $multipart = true;
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsFromPoolPost
     *
     * Create ads from a creative pool.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request api_v1_campaigns_type_campaign_id_ads_from_pool_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsFromPoolPost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsFromPoolPostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsFromPoolPostWithHttpInfo
     *
     * Create ads from a creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsFromPoolPostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsFromPoolPostAsync
     *
     * Create ads from a creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsFromPoolPostAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsFromPoolPostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsFromPoolPostAsyncWithHttpInfo
     *
     * Create ads from a creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsFromPoolPostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsFromPoolPost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsFromPoolPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsFromPoolPost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsFromPoolPost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_from_pool_post_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_from_pool_post_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_from_pool_post_request) && count($api_v1_campaigns_type_campaign_id_ads_from_pool_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request when calling apiV1CampaignsTypeCampaignIdAdsFromPoolPost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/from-pool';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_from_pool_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_from_pool_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_from_pool_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsGet
     *
     * List ads on a campaign.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  string|null $ad_state 1 &#x3D; active only, 2 &#x3D; inactive only, 3 &#x3D; deleted only. Omit for every ad that isn&#39;t deleted. (optional)
     * @param  string|null $search Matches the ad id (exact, when numeric), title, destination URL and display URL. (optional)
     * @param  string|null $sort_by Omit for the panel&#39;s order: active first, then by position. (optional)
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response
     */
    public function apiV1CampaignsTypeCampaignIdAdsGet($type, $campaign_id, $ad_state = null, $search = null, $sort_by = null, $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeCampaignIdAdsGetWithHttpInfo($type, $campaign_id, $ad_state, $search, $sort_by, $sort_dir, $page, $per_page, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsGetWithHttpInfo
     *
     * List ads on a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string|null $ad_state 1 &#x3D; active only, 2 &#x3D; inactive only, 3 &#x3D; deleted only. Omit for every ad that isn&#39;t deleted. (optional)
     * @param  string|null $search Matches the ad id (exact, when numeric), title, destination URL and display URL. (optional)
     * @param  string|null $sort_by Omit for the panel&#39;s order: active first, then by position. (optional)
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsGetWithHttpInfo($type, $campaign_id, $ad_state = null, $search = null, $sort_by = null, $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsGetRequest($type, $campaign_id, $ad_state, $search, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsGetAsync
     *
     * List ads on a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string|null $ad_state 1 &#x3D; active only, 2 &#x3D; inactive only, 3 &#x3D; deleted only. Omit for every ad that isn&#39;t deleted. (optional)
     * @param  string|null $search Matches the ad id (exact, when numeric), title, destination URL and display URL. (optional)
     * @param  string|null $sort_by Omit for the panel&#39;s order: active first, then by position. (optional)
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsGetAsync($type, $campaign_id, $ad_state = null, $search = null, $sort_by = null, $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsGetAsyncWithHttpInfo($type, $campaign_id, $ad_state, $search, $sort_by, $sort_dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsGetAsyncWithHttpInfo
     *
     * List ads on a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string|null $ad_state 1 &#x3D; active only, 2 &#x3D; inactive only, 3 &#x3D; deleted only. Omit for every ad that isn&#39;t deleted. (optional)
     * @param  string|null $search Matches the ad id (exact, when numeric), title, destination URL and display URL. (optional)
     * @param  string|null $sort_by Omit for the panel&#39;s order: active first, then by position. (optional)
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsGetAsyncWithHttpInfo($type, $campaign_id, $ad_state = null, $search = null, $sort_by = null, $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsGet200Response';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsGetRequest($type, $campaign_id, $ad_state, $search, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsGet'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string|null $ad_state 1 &#x3D; active only, 2 &#x3D; inactive only, 3 &#x3D; deleted only. Omit for every ad that isn&#39;t deleted. (optional)
     * @param  string|null $search Matches the ad id (exact, when numeric), title, destination URL and display URL. (optional)
     * @param  string|null $sort_by Omit for the panel&#39;s order: active first, then by position. (optional)
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsGetRequest($type, $campaign_id, $ad_state = null, $search = null, $sort_by = null, $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsGet'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsGet'
            );
        }






        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CampaignsApi.apiV1CampaignsTypeCampaignIdAdsGet, must be smaller than or equal to 100.');
        }
        

        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ad_state,
            'ad_state', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $search,
            'search', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sort_by,
            'sort_by', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sort_dir,
            'sort_dir', // param base name
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


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeCampaignIdAdsMassClonePost
     *
     * Copy many ads of a campaign.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassClonePost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsMassClonePostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassClonePostWithHttpInfo
     *
     * Copy many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassClonePostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassClonePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsMassClonePostAsync
     *
     * Copy many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassClonePostAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsMassClonePostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassClonePostAsyncWithHttpInfo
     *
     * Copy many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassClonePostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassClonePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsMassClonePost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassClonePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassClonePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsMassClonePost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsMassClonePost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request) && count($api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request when calling apiV1CampaignsTypeCampaignIdAdsMassClonePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/mass-clone';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_mass_clone_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassDeletePost
     *
     * Soft-delete many ads of a campaign.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassDeletePost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsMassDeletePostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassDeletePostWithHttpInfo
     *
     * Soft-delete many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassDeletePostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsMassDeletePostAsync
     *
     * Soft-delete many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassDeletePostAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsMassDeletePostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassDeletePostAsyncWithHttpInfo
     *
     * Soft-delete many ads of a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassDeletePostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsMassDeletePost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassDeletePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsMassDeletePost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsMassDeletePost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request) && count($api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request when calling apiV1CampaignsTypeCampaignIdAdsMassDeletePost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/mass-delete';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_mass_delete_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassTogglePut
     *
     * Activate or pause many ads of a campaign at once.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassTogglePut($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsMassTogglePutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassTogglePutWithHttpInfo
     *
     * Activate or pause many ads of a campaign at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassTogglePutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsMassTogglePutAsync
     *
     * Activate or pause many ads of a campaign at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassTogglePutAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsMassTogglePutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMassTogglePutAsyncWithHttpInfo
     *
     * Activate or pause many ads of a campaign at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassTogglePutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsMassTogglePut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMassTogglePut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsMassTogglePut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsMassTogglePut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request) && count($api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request when calling apiV1CampaignsTypeCampaignIdAdsMassTogglePut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/mass-toggle';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_mass_toggle_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMultiUploadPost
     *
     * Bulk-upload ads — one ad per creative file.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \SplFileObject[] $files One creative file per ad (also accepted as files[]). (required)
     * @param  string|null $title Native campaigns only: headline for every ad in the batch. Empty &#x3D; each file name. (optional)
     * @param  string|null $description Native campaigns only: description for every ad in the batch. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsMultiUploadPost($type, $campaign_id, $files, $title = null, $description = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsMultiUploadPostWithHttpInfo($type, $campaign_id, $files, $title, $description, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMultiUploadPostWithHttpInfo
     *
     * Bulk-upload ads — one ad per creative file.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject[] $files One creative file per ad (also accepted as files[]). (required)
     * @param  string|null $title Native campaigns only: headline for every ad in the batch. Empty &#x3D; each file name. (optional)
     * @param  string|null $description Native campaigns only: description for every ad in the batch. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsMultiUploadPostWithHttpInfo($type, $campaign_id, $files, $title = null, $description = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMultiUploadPostRequest($type, $campaign_id, $files, $title, $description, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMultiUploadPostAsync
     *
     * Bulk-upload ads — one ad per creative file.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject[] $files One creative file per ad (also accepted as files[]). (required)
     * @param  string|null $title Native campaigns only: headline for every ad in the batch. Empty &#x3D; each file name. (optional)
     * @param  string|null $description Native campaigns only: description for every ad in the batch. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMultiUploadPostAsync($type, $campaign_id, $files, $title = null, $description = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsMultiUploadPostAsyncWithHttpInfo($type, $campaign_id, $files, $title, $description, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsMultiUploadPostAsyncWithHttpInfo
     *
     * Bulk-upload ads — one ad per creative file.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject[] $files One creative file per ad (also accepted as files[]). (required)
     * @param  string|null $title Native campaigns only: headline for every ad in the batch. Empty &#x3D; each file name. (optional)
     * @param  string|null $description Native campaigns only: description for every ad in the batch. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsMultiUploadPostAsyncWithHttpInfo($type, $campaign_id, $files, $title = null, $description = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsMultiUploadPostRequest($type, $campaign_id, $files, $title, $description, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \SplFileObject[] $files One creative file per ad (also accepted as files[]). (required)
     * @param  string|null $title Native campaigns only: headline for every ad in the batch. Empty &#x3D; each file name. (optional)
     * @param  string|null $description Native campaigns only: description for every ad in the batch. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsMultiUploadPostRequest($type, $campaign_id, $files, $title = null, $description = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'
            );
        }

        // verify the required parameter 'files' is set
        if ($files === null || (is_array($files) && count($files) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $files when calling apiV1CampaignsTypeCampaignIdAdsMultiUploadPost'
            );
        }








        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/multi-upload';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }

        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'files' => $files,
            'title' => $title,
            'description' => $description,
            'use_overlays' => $use_overlays,
            'clone_per_overlay' => $clone_per_overlay,
            'overlay' => $overlay,
            'overlays_zip' => $overlays_zip,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

        $multipart = true;
        $headers = $this->headerSelector->selectHeaders(
            [],
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsPost
     *
     * Create an ad on a campaign (smart-default).
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest $api_v1_campaigns_type_campaign_id_ads_post_request api_v1_campaigns_type_campaign_id_ads_post_request (required)
     * @param  string|null $dry_run dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsPost($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsPostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsPostWithHttpInfo
     *
     * Create an ad on a campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest $api_v1_campaigns_type_campaign_id_ads_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsPostWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsPostAsync
     *
     * Create an ad on a campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest $api_v1_campaigns_type_campaign_id_ads_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsPostAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsPostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsPostAsyncWithHttpInfo
     *
     * Create an ad on a campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest $api_v1_campaigns_type_campaign_id_ads_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsPostAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run, $strict, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsPost'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequest $api_v1_campaigns_type_campaign_id_ads_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsPostRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsPost'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsPost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_post_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_post_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_post_request) && count($api_v1_campaigns_type_campaign_id_ads_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_post_request when calling apiV1CampaignsTypeCampaignIdAdsPost'
            );
        }




        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dry_run,
            'dry_run', // param base name
            'string', // openApiType
            'form', // style
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


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut
     *
     * Point many ads at a different creative pool.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request api_v1_campaigns_type_campaign_id_ads_update_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutWithHttpInfo
     *
     * Point many ads at a different creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutAsync
     *
     * Point many ads at a different creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutAsyncWithHttpInfo
     *
     * Point many ads at a different creative pool.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_update_pool_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_update_pool_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_update_pool_put_request) && count($api_v1_campaigns_type_campaign_id_ads_update_pool_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request when calling apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/update-pool';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_update_pool_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_update_pool_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_update_pool_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut
     *
     * Point many ads at a URL pool, or detach it.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutWithHttpInfo
     *
     * Point many ads at a URL pool, or detach it.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutAsync
     *
     * Point many ads at a URL pool, or detach it.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutAsyncWithHttpInfo
     *
     * Point many ads at a URL pool, or detach it.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request) && count($api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/update-url-pool';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_update_url_pool_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut
     *
     * Rewrite the destination URL of many ads at once.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_put_request api_v1_campaigns_type_campaign_id_ads_update_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutWithHttpInfo
     *
     * Rewrite the destination URL of many ads at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutAsync
     *
     * Rewrite the destination URL of many ads at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutAsyncWithHttpInfo
     *
     * Rewrite the destination URL of many ads at once.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest $api_v1_campaigns_type_campaign_id_ads_update_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_url_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_update_url_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_update_url_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_update_url_put_request) && count($api_v1_campaigns_type_campaign_id_ads_update_url_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_update_url_put_request when calling apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/update-url';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_update_url_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_update_url_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_update_url_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut
     *
     * Set the rotation weight of one or more RON ads.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request api_v1_campaigns_type_campaign_id_ads_update_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutWithHttpInfo
     *
     * Set the rotation weight of one or more RON ads.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutAsync
     *
     * Set the rotation weight of one or more RON ads.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutAsync($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutAsyncWithHttpInfo
     *
     * Set the rotation weight of one or more RON ads.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutAsyncWithHttpInfo($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest($type, $campaign_id, $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_campaign_id_ads_update_weight_put_request' is set
        if ($api_v1_campaigns_type_campaign_id_ads_update_weight_put_request === null || (is_array($api_v1_campaigns_type_campaign_id_ads_update_weight_put_request) && count($api_v1_campaigns_type_campaign_id_ads_update_weight_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request when calling apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/ads/update-weight';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_campaign_id_ads_update_weight_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_campaign_id_ads_update_weight_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_campaign_id_ads_update_weight_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdSchemaAdsGet
     *
     * Field metadata for ad creation, scoped to a campaign.
     *
     * @param  string $type type (required)
     * @param  int $campaign_id campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeCampaignIdSchemaAdsGet($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'][0])
    {
        $this->apiV1CampaignsTypeCampaignIdSchemaAdsGetWithHttpInfo($type, $campaign_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdSchemaAdsGetWithHttpInfo
     *
     * Field metadata for ad creation, scoped to a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeCampaignIdSchemaAdsGetWithHttpInfo($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'][0])
    {
        $request = $this->apiV1CampaignsTypeCampaignIdSchemaAdsGetRequest($type, $campaign_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdSchemaAdsGetAsync
     *
     * Field metadata for ad creation, scoped to a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdSchemaAdsGetAsync($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'][0])
    {
        return $this->apiV1CampaignsTypeCampaignIdSchemaAdsGetAsyncWithHttpInfo($type, $campaign_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeCampaignIdSchemaAdsGetAsyncWithHttpInfo
     *
     * Field metadata for ad creation, scoped to a campaign.
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeCampaignIdSchemaAdsGetAsyncWithHttpInfo($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeCampaignIdSchemaAdsGetRequest($type, $campaign_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeCampaignIdSchemaAdsGet'
     *
     * @param  string $type (required)
     * @param  int $campaign_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeCampaignIdSchemaAdsGetRequest($type, $campaign_id, string $contentType = self::contentTypes['apiV1CampaignsTypeCampaignIdSchemaAdsGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeCampaignIdSchemaAdsGet'
            );
        }

        // verify the required parameter 'campaign_id' is set
        if ($campaign_id === null || (is_array($campaign_id) && count($campaign_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $campaign_id when calling apiV1CampaignsTypeCampaignIdSchemaAdsGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{campaignId}/_schema/ads';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($campaign_id !== null) {
            $resourcePath = str_replace(
                '{' . 'campaignId' . '}',
                ObjectSerializer::toPathValue($campaign_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            [],
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
     * Operation apiV1CampaignsTypeIdActivePatch
     *
     * Pause or unpause a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdActivePatch'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdActivePatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdActivePatchWithHttpInfo
     *
     * Pause or unpause a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdActivePatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdActivePatch'][0])
    {
        $request = $this->apiV1CampaignsTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdActivePatchAsync
     *
     * Pause or unpause a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdActivePatchAsync($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdActivePatch'][0])
    {
        return $this->apiV1CampaignsTypeIdActivePatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdActivePatchAsyncWithHttpInfo
     *
     * Pause or unpause a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdActivePatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdActivePatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatch200Response';
        $request = $this->apiV1CampaignsTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdActivePatch'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdActivePatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdActivePatch'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdActivePatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_active_patch_request' is set
        if ($api_v1_campaigns_type_id_active_patch_request === null || (is_array($api_v1_campaigns_type_id_active_patch_request) && count($api_v1_campaigns_type_id_active_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_active_patch_request when calling apiV1CampaignsTypeIdActivePatch'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/active';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_active_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_active_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_active_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdAvailableZonesGet
     *
     * List your ad zones that can be attached to a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 50)
     * @param  string|null $search Matches zone name, site name or zone id. (optional)
     * @param  int|null $site_id Only zones of this site (GET …/sites). (optional)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out. (optional)
     * @param  bool|null $only_used_sizes Banner campaigns: only zones whose size one of the campaign&#39;s live ads has. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdAvailableZonesGet($type, $id, $page = 1, $per_page = 50, $search = null, $site_id = null, $exclude_ids = null, $only_used_sizes = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'][0])
    {
        $this->apiV1CampaignsTypeIdAvailableZonesGetWithHttpInfo($type, $id, $page, $per_page, $search, $site_id, $exclude_ids, $only_used_sizes, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdAvailableZonesGetWithHttpInfo
     *
     * List your ad zones that can be attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $search Matches zone name, site name or zone id. (optional)
     * @param  int|null $site_id Only zones of this site (GET …/sites). (optional)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out. (optional)
     * @param  bool|null $only_used_sizes Banner campaigns: only zones whose size one of the campaign&#39;s live ads has. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdAvailableZonesGetWithHttpInfo($type, $id, $page = 1, $per_page = 50, $search = null, $site_id = null, $exclude_ids = null, $only_used_sizes = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdAvailableZonesGetRequest($type, $id, $page, $per_page, $search, $site_id, $exclude_ids, $only_used_sizes, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdAvailableZonesGetAsync
     *
     * List your ad zones that can be attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $search Matches zone name, site name or zone id. (optional)
     * @param  int|null $site_id Only zones of this site (GET …/sites). (optional)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out. (optional)
     * @param  bool|null $only_used_sizes Banner campaigns: only zones whose size one of the campaign&#39;s live ads has. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdAvailableZonesGetAsync($type, $id, $page = 1, $per_page = 50, $search = null, $site_id = null, $exclude_ids = null, $only_used_sizes = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'][0])
    {
        return $this->apiV1CampaignsTypeIdAvailableZonesGetAsyncWithHttpInfo($type, $id, $page, $per_page, $search, $site_id, $exclude_ids, $only_used_sizes, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdAvailableZonesGetAsyncWithHttpInfo
     *
     * List your ad zones that can be attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $search Matches zone name, site name or zone id. (optional)
     * @param  int|null $site_id Only zones of this site (GET …/sites). (optional)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out. (optional)
     * @param  bool|null $only_used_sizes Banner campaigns: only zones whose size one of the campaign&#39;s live ads has. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdAvailableZonesGetAsyncWithHttpInfo($type, $id, $page = 1, $per_page = 50, $search = null, $site_id = null, $exclude_ids = null, $only_used_sizes = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdAvailableZonesGetRequest($type, $id, $page, $per_page, $search, $site_id, $exclude_ids, $only_used_sizes, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdAvailableZonesGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 50)
     * @param  string|null $search Matches zone name, site name or zone id. (optional)
     * @param  int|null $site_id Only zones of this site (GET …/sites). (optional)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out. (optional)
     * @param  bool|null $only_used_sizes Banner campaigns: only zones whose size one of the campaign&#39;s live ads has. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdAvailableZonesGetRequest($type, $id, $page = 1, $per_page = 50, $search = null, $site_id = null, $exclude_ids = null, $only_used_sizes = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdAvailableZonesGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdAvailableZonesGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdAvailableZonesGet'
            );
        }

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling CampaignsApi.apiV1CampaignsTypeIdAvailableZonesGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 200) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CampaignsApi.apiV1CampaignsTypeIdAvailableZonesGet, must be smaller than or equal to 200.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CampaignsApi.apiV1CampaignsTypeIdAvailableZonesGet, must be bigger than or equal to 1.');
        }
        





        $resourcePath = '/api/v1/campaigns/{type}/{id}/available-zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $search,
            'search', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $site_id,
            'site_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $exclude_ids,
            'exclude_ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $only_used_sizes,
            'only_used_sizes', // param base name
            'boolean', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdBidsPatch
     *
     * Mass update bids on every ad in a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest $api_v1_campaigns_type_id_bids_patch_request api_v1_campaigns_type_id_bids_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBidsPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeIdBidsPatch($type, $id, $api_v1_campaigns_type_id_bids_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBidsPatch'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdBidsPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_bids_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdBidsPatchWithHttpInfo
     *
     * Mass update bids on every ad in a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest $api_v1_campaigns_type_id_bids_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBidsPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdBidsPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_bids_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBidsPatch'][0])
    {
        $request = $this->apiV1CampaignsTypeIdBidsPatchRequest($type, $id, $api_v1_campaigns_type_id_bids_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdBidsPatchAsync
     *
     * Mass update bids on every ad in a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest $api_v1_campaigns_type_id_bids_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBidsPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdBidsPatchAsync($type, $id, $api_v1_campaigns_type_id_bids_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBidsPatch'][0])
    {
        return $this->apiV1CampaignsTypeIdBidsPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_bids_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdBidsPatchAsyncWithHttpInfo
     *
     * Mass update bids on every ad in a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest $api_v1_campaigns_type_id_bids_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBidsPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdBidsPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_bids_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBidsPatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatch200Response';
        $request = $this->apiV1CampaignsTypeIdBidsPatchRequest($type, $id, $api_v1_campaigns_type_id_bids_patch_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdBidsPatch'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBidsPatchRequest $api_v1_campaigns_type_id_bids_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBidsPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdBidsPatchRequest($type, $id, $api_v1_campaigns_type_id_bids_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBidsPatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdBidsPatch'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdBidsPatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_bids_patch_request' is set
        if ($api_v1_campaigns_type_id_bids_patch_request === null || (is_array($api_v1_campaigns_type_id_bids_patch_request) && count($api_v1_campaigns_type_id_bids_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_bids_patch_request when calling apiV1CampaignsTypeIdBidsPatch'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/bids';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_bids_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_bids_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_bids_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdBudgetPatch
     *
     * Update a campaign&#39;s total and/or daily budget.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatchRequest $api_v1_campaigns_type_id_budget_patch_request api_v1_campaigns_type_id_budget_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeIdBudgetPatch($type, $id, $api_v1_campaigns_type_id_budget_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdBudgetPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_budget_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdBudgetPatchWithHttpInfo
     *
     * Update a campaign&#39;s total and/or daily budget.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatchRequest $api_v1_campaigns_type_id_budget_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdBudgetPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_budget_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'][0])
    {
        $request = $this->apiV1CampaignsTypeIdBudgetPatchRequest($type, $id, $api_v1_campaigns_type_id_budget_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdBudgetPatchAsync
     *
     * Update a campaign&#39;s total and/or daily budget.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatchRequest $api_v1_campaigns_type_id_budget_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdBudgetPatchAsync($type, $id, $api_v1_campaigns_type_id_budget_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'][0])
    {
        return $this->apiV1CampaignsTypeIdBudgetPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_budget_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdBudgetPatchAsyncWithHttpInfo
     *
     * Update a campaign&#39;s total and/or daily budget.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatchRequest $api_v1_campaigns_type_id_budget_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdBudgetPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_budget_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatch200Response';
        $request = $this->apiV1CampaignsTypeIdBudgetPatchRequest($type, $id, $api_v1_campaigns_type_id_budget_patch_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdBudgetPatch'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdBudgetPatchRequest $api_v1_campaigns_type_id_budget_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdBudgetPatchRequest($type, $id, $api_v1_campaigns_type_id_budget_patch_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdBudgetPatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdBudgetPatch'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdBudgetPatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_budget_patch_request' is set
        if ($api_v1_campaigns_type_id_budget_patch_request === null || (is_array($api_v1_campaigns_type_id_budget_patch_request) && count($api_v1_campaigns_type_id_budget_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_budget_patch_request when calling apiV1CampaignsTypeIdBudgetPatch'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/budget';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_budget_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_budget_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_budget_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdClonePost
     *
     * Clone a campaign — everything, or the parts you pick.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  int|null $strict 1 &#x3D; reject unknown body keys (422). (optional)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest|null $api_v1_campaigns_type_id_clone_post_request api_v1_campaigns_type_id_clone_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response
     */
    public function apiV1CampaignsTypeIdClonePost($type, $id, $strict = null, $api_v1_campaigns_type_id_clone_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdClonePost'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdClonePostWithHttpInfo($type, $id, $strict, $api_v1_campaigns_type_id_clone_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdClonePostWithHttpInfo
     *
     * Clone a campaign — everything, or the parts you pick.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $strict 1 &#x3D; reject unknown body keys (422). (optional)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest|null $api_v1_campaigns_type_id_clone_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdClonePostWithHttpInfo($type, $id, $strict = null, $api_v1_campaigns_type_id_clone_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdClonePost'][0])
    {
        $request = $this->apiV1CampaignsTypeIdClonePostRequest($type, $id, $strict, $api_v1_campaigns_type_id_clone_post_request, $contentType);

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
                case 201:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdClonePostAsync
     *
     * Clone a campaign — everything, or the parts you pick.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $strict 1 &#x3D; reject unknown body keys (422). (optional)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest|null $api_v1_campaigns_type_id_clone_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdClonePostAsync($type, $id, $strict = null, $api_v1_campaigns_type_id_clone_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdClonePost'][0])
    {
        return $this->apiV1CampaignsTypeIdClonePostAsyncWithHttpInfo($type, $id, $strict, $api_v1_campaigns_type_id_clone_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdClonePostAsyncWithHttpInfo
     *
     * Clone a campaign — everything, or the parts you pick.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $strict 1 &#x3D; reject unknown body keys (422). (optional)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest|null $api_v1_campaigns_type_id_clone_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdClonePostAsyncWithHttpInfo($type, $id, $strict = null, $api_v1_campaigns_type_id_clone_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdClonePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePost201Response';
        $request = $this->apiV1CampaignsTypeIdClonePostRequest($type, $id, $strict, $api_v1_campaigns_type_id_clone_post_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdClonePost'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int|null $strict 1 &#x3D; reject unknown body keys (422). (optional)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdClonePostRequest|null $api_v1_campaigns_type_id_clone_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdClonePostRequest($type, $id, $strict = null, $api_v1_campaigns_type_id_clone_post_request = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdClonePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdClonePost'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdClonePost'
            );
        }




        $resourcePath = '/api/v1/campaigns/{type}/{id}/clone';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $strict,
            'strict', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_clone_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_clone_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_clone_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdDelete
     *
     * Soft-delete a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeIdDelete($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdDelete'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdDeleteWithHttpInfo($type, $id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdDeleteWithHttpInfo
     *
     * Soft-delete a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdDeleteWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdDelete'][0])
    {
        $request = $this->apiV1CampaignsTypeIdDeleteRequest($type, $id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdDeleteAsync
     *
     * Soft-delete a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdDeleteAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdDelete'][0])
    {
        return $this->apiV1CampaignsTypeIdDeleteAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdDeleteAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdDelete200Response';
        $request = $this->apiV1CampaignsTypeIdDeleteRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdDelete'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdDeleteRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdDelete'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete
     *
     * Detach a filter from this campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $filter_type filter_type (required)
     * @param  int $filter_id filter_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete($type, $id, $filter_type, $filter_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'][0])
    {
        $this->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteWithHttpInfo($type, $id, $filter_type, $filter_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteWithHttpInfo
     *
     * Detach a filter from this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $filter_type (required)
     * @param  int $filter_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteWithHttpInfo($type, $id, $filter_type, $filter_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'][0])
    {
        $request = $this->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteRequest($type, $id, $filter_type, $filter_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteAsync
     *
     * Detach a filter from this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $filter_type (required)
     * @param  int $filter_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteAsync($type, $id, $filter_type, $filter_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'][0])
    {
        return $this->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteAsyncWithHttpInfo($type, $id, $filter_type, $filter_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteAsyncWithHttpInfo
     *
     * Detach a filter from this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $filter_type (required)
     * @param  int $filter_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteAsyncWithHttpInfo($type, $id, $filter_type, $filter_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteRequest($type, $id, $filter_type, $filter_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $filter_type (required)
     * @param  int $filter_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDeleteRequest($type, $id, $filter_type, $filter_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'
            );
        }

        // verify the required parameter 'filter_type' is set
        if ($filter_type === null || (is_array($filter_type) && count($filter_type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $filter_type when calling apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'
            );
        }

        // verify the required parameter 'filter_id' is set
        if ($filter_id === null || (is_array($filter_id) && count($filter_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $filter_id when calling apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/filters/{filter_type}/{filter_id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }
        // path params
        if ($filter_type !== null) {
            $resourcePath = str_replace(
                '{' . 'filter_type' . '}',
                ObjectSerializer::toPathValue($filter_type),
                $resourcePath
            );
        }
        // path params
        if ($filter_id !== null) {
            $resourcePath = str_replace(
                '{' . 'filter_id' . '}',
                ObjectSerializer::toPathValue($filter_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            [],
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersGet
     *
     * List filters attached to this campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response
     */
    public function apiV1CampaignsTypeIdFiltersGet($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdFiltersGetWithHttpInfo($type, $id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersGetWithHttpInfo
     *
     * List filters attached to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdFiltersGetWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdFiltersGetRequest($type, $id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersGetAsync
     *
     * List filters attached to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersGetAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersGet'][0])
    {
        return $this->apiV1CampaignsTypeIdFiltersGetAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersGetAsyncWithHttpInfo
     *
     * List filters attached to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersGetAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersGet200Response';
        $request = $this->apiV1CampaignsTypeIdFiltersGetRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdFiltersGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdFiltersGetRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdFiltersGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdFiltersGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/filters';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdFiltersPost
     *
     * Attach a filter to this campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest $api_v1_campaigns_type_id_filters_post_request api_v1_campaigns_type_id_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response
     */
    public function apiV1CampaignsTypeIdFiltersPost($type, $id, $api_v1_campaigns_type_id_filters_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersPost'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdFiltersPostWithHttpInfo($type, $id, $api_v1_campaigns_type_id_filters_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersPostWithHttpInfo
     *
     * Attach a filter to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest $api_v1_campaigns_type_id_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdFiltersPostWithHttpInfo($type, $id, $api_v1_campaigns_type_id_filters_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersPost'][0])
    {
        $request = $this->apiV1CampaignsTypeIdFiltersPostRequest($type, $id, $api_v1_campaigns_type_id_filters_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersPostAsync
     *
     * Attach a filter to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest $api_v1_campaigns_type_id_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersPostAsync($type, $id, $api_v1_campaigns_type_id_filters_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersPost'][0])
    {
        return $this->apiV1CampaignsTypeIdFiltersPostAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_filters_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdFiltersPostAsyncWithHttpInfo
     *
     * Attach a filter to this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest $api_v1_campaigns_type_id_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdFiltersPostAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_filters_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPost200Response';
        $request = $this->apiV1CampaignsTypeIdFiltersPostRequest($type, $id, $api_v1_campaigns_type_id_filters_post_request, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdFiltersPost'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdFiltersPostRequest $api_v1_campaigns_type_id_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdFiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdFiltersPostRequest($type, $id, $api_v1_campaigns_type_id_filters_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdFiltersPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdFiltersPost'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdFiltersPost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_filters_post_request' is set
        if ($api_v1_campaigns_type_id_filters_post_request === null || (is_array($api_v1_campaigns_type_id_filters_post_request) && count($api_v1_campaigns_type_id_filters_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_filters_post_request when calling apiV1CampaignsTypeIdFiltersPost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/filters';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_filters_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_filters_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_filters_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdGet
     *
     * Get a single campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return object
     */
    public function apiV1CampaignsTypeIdGet($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdGetWithHttpInfo($type, $id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdGetWithHttpInfo
     *
     * Get a single campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of object, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdGetWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdGetRequest($type, $id, $contentType);

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
                        'object',
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
                'object',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        'object',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdGetAsync
     *
     * Get a single campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdGetAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdGet'][0])
    {
        return $this->apiV1CampaignsTypeIdGetAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdGetAsyncWithHttpInfo
     *
     * Get a single campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdGetAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdGet'][0])
    {
        $returnType = 'object';
        $request = $this->apiV1CampaignsTypeIdGetRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdGetRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdMinBidGet
     *
     * Get the campaign&#39;s minimum acceptable CPC bid.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdMinBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CampaignsTypeIdMinBidGet($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdMinBidGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeIdMinBidGetWithHttpInfo($type, $id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeIdMinBidGetWithHttpInfo
     *
     * Get the campaign&#39;s minimum acceptable CPC bid.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdMinBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdMinBidGetWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdMinBidGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdMinBidGetRequest($type, $id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response',
                        $request,
                        $response,
                    );
                case 403:
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response',
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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdMinBidGetAsync
     *
     * Get the campaign&#39;s minimum acceptable CPC bid.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdMinBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdMinBidGetAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdMinBidGet'][0])
    {
        return $this->apiV1CampaignsTypeIdMinBidGetAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdMinBidGetAsyncWithHttpInfo
     *
     * Get the campaign&#39;s minimum acceptable CPC bid.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdMinBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdMinBidGetAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdMinBidGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeIdMinBidGet200Response';
        $request = $this->apiV1CampaignsTypeIdMinBidGetRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeIdMinBidGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdMinBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdMinBidGetRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdMinBidGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdMinBidGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdMinBidGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/min-bid';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdPatch
     *
     * Partially update a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdPatchRequest $api_v1_campaigns_type_id_patch_request api_v1_campaigns_type_id_patch_request (required)
     * @param  string|null $dry_run dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdPatch($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdPatch'][0])
    {
        $this->apiV1CampaignsTypeIdPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run, $strict, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdPatchWithHttpInfo
     *
     * Partially update a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdPatchRequest $api_v1_campaigns_type_id_patch_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdPatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdPatch'][0])
    {
        $request = $this->apiV1CampaignsTypeIdPatchRequest($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run, $strict, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdPatchAsync
     *
     * Partially update a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdPatchRequest $api_v1_campaigns_type_id_patch_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdPatchAsync($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdPatch'][0])
    {
        return $this->apiV1CampaignsTypeIdPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run, $strict, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdPatchAsyncWithHttpInfo
     *
     * Partially update a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdPatchRequest $api_v1_campaigns_type_id_patch_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdPatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdPatch'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdPatchRequest($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run, $strict, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdPatch'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdPatchRequest $api_v1_campaigns_type_id_patch_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdPatchRequest($type, $id, $api_v1_campaigns_type_id_patch_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdPatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdPatch'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdPatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_patch_request' is set
        if ($api_v1_campaigns_type_id_patch_request === null || (is_array($api_v1_campaigns_type_id_patch_request) && count($api_v1_campaigns_type_id_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_patch_request when calling apiV1CampaignsTypeIdPatch'
            );
        }




        $resourcePath = '/api/v1/campaigns/{type}/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dry_run,
            'dry_run', // param base name
            'string', // openApiType
            'form', // style
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


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_patch_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_patch_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_patch_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PATCH',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdResetMaxBudgetPut
     *
     * Reset a campaign&#39;s lifetime-budget spend.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdResetMaxBudgetPut($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'][0])
    {
        $this->apiV1CampaignsTypeIdResetMaxBudgetPutWithHttpInfo($type, $id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdResetMaxBudgetPutWithHttpInfo
     *
     * Reset a campaign&#39;s lifetime-budget spend.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdResetMaxBudgetPutWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'][0])
    {
        $request = $this->apiV1CampaignsTypeIdResetMaxBudgetPutRequest($type, $id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdResetMaxBudgetPutAsync
     *
     * Reset a campaign&#39;s lifetime-budget spend.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdResetMaxBudgetPutAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'][0])
    {
        return $this->apiV1CampaignsTypeIdResetMaxBudgetPutAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdResetMaxBudgetPutAsyncWithHttpInfo
     *
     * Reset a campaign&#39;s lifetime-budget spend.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdResetMaxBudgetPutAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdResetMaxBudgetPutRequest($type, $id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdResetMaxBudgetPut'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdResetMaxBudgetPutRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdResetMaxBudgetPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdResetMaxBudgetPut'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdResetMaxBudgetPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/reset-max-budget';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdSitesGet
     *
     * Your sites that have zones attachable to a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out of the counts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdSitesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdSitesGet($type, $id, $exclude_ids = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdSitesGet'][0])
    {
        $this->apiV1CampaignsTypeIdSitesGetWithHttpInfo($type, $id, $exclude_ids, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdSitesGetWithHttpInfo
     *
     * Your sites that have zones attachable to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out of the counts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdSitesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdSitesGetWithHttpInfo($type, $id, $exclude_ids = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdSitesGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdSitesGetRequest($type, $id, $exclude_ids, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdSitesGetAsync
     *
     * Your sites that have zones attachable to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out of the counts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdSitesGetAsync($type, $id, $exclude_ids = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdSitesGet'][0])
    {
        return $this->apiV1CampaignsTypeIdSitesGetAsyncWithHttpInfo($type, $id, $exclude_ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdSitesGetAsyncWithHttpInfo
     *
     * Your sites that have zones attachable to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out of the counts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdSitesGetAsyncWithHttpInfo($type, $id, $exclude_ids = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdSitesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdSitesGetRequest($type, $id, $exclude_ids, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdSitesGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string|null $exclude_ids Comma-separated zone ids to leave out of the counts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdSitesGetRequest($type, $id, $exclude_ids = null, string $contentType = self::contentTypes['apiV1CampaignsTypeIdSitesGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdSitesGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdSitesGet'
            );
        }



        $resourcePath = '/api/v1/campaigns/{type}/{id}/sites';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $exclude_ids,
            'exclude_ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdWhitelistZonesGet
     *
     * List the ad zones attached to a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdWhitelistZonesGet($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'][0])
    {
        $this->apiV1CampaignsTypeIdWhitelistZonesGetWithHttpInfo($type, $id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdWhitelistZonesGetWithHttpInfo
     *
     * List the ad zones attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdWhitelistZonesGetWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'][0])
    {
        $request = $this->apiV1CampaignsTypeIdWhitelistZonesGetRequest($type, $id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdWhitelistZonesGetAsync
     *
     * List the ad zones attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdWhitelistZonesGetAsync($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'][0])
    {
        return $this->apiV1CampaignsTypeIdWhitelistZonesGetAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdWhitelistZonesGetAsyncWithHttpInfo
     *
     * List the ad zones attached to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdWhitelistZonesGetAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdWhitelistZonesGetRequest($type, $id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdWhitelistZonesGet'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdWhitelistZonesGetRequest($type, $id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdWhitelistZonesGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdWhitelistZonesGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdWhitelistZonesGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/whitelist-zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeIdZonesPost
     *
     * Attach ad zones to a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesPostRequest $api_v1_campaigns_type_id_zones_post_request api_v1_campaigns_type_id_zones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdZonesPost($type, $id, $api_v1_campaigns_type_id_zones_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesPost'][0])
    {
        $this->apiV1CampaignsTypeIdZonesPostWithHttpInfo($type, $id, $api_v1_campaigns_type_id_zones_post_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesPostWithHttpInfo
     *
     * Attach ad zones to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesPostRequest $api_v1_campaigns_type_id_zones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdZonesPostWithHttpInfo($type, $id, $api_v1_campaigns_type_id_zones_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesPost'][0])
    {
        $request = $this->apiV1CampaignsTypeIdZonesPostRequest($type, $id, $api_v1_campaigns_type_id_zones_post_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesPostAsync
     *
     * Attach ad zones to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesPostRequest $api_v1_campaigns_type_id_zones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesPostAsync($type, $id, $api_v1_campaigns_type_id_zones_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesPost'][0])
    {
        return $this->apiV1CampaignsTypeIdZonesPostAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_zones_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesPostAsyncWithHttpInfo
     *
     * Attach ad zones to a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesPostRequest $api_v1_campaigns_type_id_zones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesPostAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_zones_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdZonesPostRequest($type, $id, $api_v1_campaigns_type_id_zones_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdZonesPost'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesPostRequest $api_v1_campaigns_type_id_zones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdZonesPostRequest($type, $id, $api_v1_campaigns_type_id_zones_post_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdZonesPost'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdZonesPost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_zones_post_request' is set
        if ($api_v1_campaigns_type_id_zones_post_request === null || (is_array($api_v1_campaigns_type_id_zones_post_request) && count($api_v1_campaigns_type_id_zones_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_zones_post_request when calling apiV1CampaignsTypeIdZonesPost'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_zones_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_zones_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_zones_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdDelete
     *
     * Detach an ad zone from a campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  int $zone_id zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdZonesZoneIdDelete($type, $id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'][0])
    {
        $this->apiV1CampaignsTypeIdZonesZoneIdDeleteWithHttpInfo($type, $id, $zone_id, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdDeleteWithHttpInfo
     *
     * Detach an ad zone from a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdZonesZoneIdDeleteWithHttpInfo($type, $id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'][0])
    {
        $request = $this->apiV1CampaignsTypeIdZonesZoneIdDeleteRequest($type, $id, $zone_id, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdZonesZoneIdDeleteAsync
     *
     * Detach an ad zone from a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesZoneIdDeleteAsync($type, $id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'][0])
    {
        return $this->apiV1CampaignsTypeIdZonesZoneIdDeleteAsyncWithHttpInfo($type, $id, $zone_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdDeleteAsyncWithHttpInfo
     *
     * Detach an ad zone from a campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesZoneIdDeleteAsyncWithHttpInfo($type, $id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdZonesZoneIdDeleteRequest($type, $id, $zone_id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdZonesZoneIdDelete'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdZonesZoneIdDeleteRequest($type, $id, $zone_id, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdZonesZoneIdDelete'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdZonesZoneIdDelete'
            );
        }

        // verify the required parameter 'zone_id' is set
        if ($zone_id === null || (is_array($zone_id) && count($zone_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $zone_id when calling apiV1CampaignsTypeIdZonesZoneIdDelete'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/zones/{zoneId}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }
        // path params
        if ($zone_id !== null) {
            $resourcePath = str_replace(
                '{' . 'zoneId' . '}',
                ObjectSerializer::toPathValue($zone_id),
                $resourcePath
            );
        }


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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdWeightPut
     *
     * Set an attached zone&#39;s weight for this campaign.
     *
     * @param  string $type type (required)
     * @param  int $id id (required)
     * @param  int $zone_id zone_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest $api_v1_campaigns_type_id_zones_zone_id_weight_put_request api_v1_campaigns_type_id_zones_zone_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypeIdZonesZoneIdWeightPut($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'][0])
    {
        $this->apiV1CampaignsTypeIdZonesZoneIdWeightPutWithHttpInfo($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdWeightPutWithHttpInfo
     *
     * Set an attached zone&#39;s weight for this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest $api_v1_campaigns_type_id_zones_zone_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeIdZonesZoneIdWeightPutWithHttpInfo($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'][0])
    {
        $request = $this->apiV1CampaignsTypeIdZonesZoneIdWeightPutRequest($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypeIdZonesZoneIdWeightPutAsync
     *
     * Set an attached zone&#39;s weight for this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest $api_v1_campaigns_type_id_zones_zone_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesZoneIdWeightPutAsync($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'][0])
    {
        return $this->apiV1CampaignsTypeIdZonesZoneIdWeightPutAsyncWithHttpInfo($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeIdZonesZoneIdWeightPutAsyncWithHttpInfo
     *
     * Set an attached zone&#39;s weight for this campaign.
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest $api_v1_campaigns_type_id_zones_zone_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeIdZonesZoneIdWeightPutAsyncWithHttpInfo($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypeIdZonesZoneIdWeightPutRequest($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypeIdZonesZoneIdWeightPut'
     *
     * @param  string $type (required)
     * @param  int $id (required)
     * @param  int $zone_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest $api_v1_campaigns_type_id_zones_zone_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeIdZonesZoneIdWeightPutRequest($type, $id, $zone_id, $api_v1_campaigns_type_id_zones_zone_id_weight_put_request, string $contentType = self::contentTypes['apiV1CampaignsTypeIdZonesZoneIdWeightPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeIdZonesZoneIdWeightPut'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CampaignsTypeIdZonesZoneIdWeightPut'
            );
        }

        // verify the required parameter 'zone_id' is set
        if ($zone_id === null || (is_array($zone_id) && count($zone_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $zone_id when calling apiV1CampaignsTypeIdZonesZoneIdWeightPut'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_zones_zone_id_weight_put_request' is set
        if ($api_v1_campaigns_type_id_zones_zone_id_weight_put_request === null || (is_array($api_v1_campaigns_type_id_zones_zone_id_weight_put_request) && count($api_v1_campaigns_type_id_zones_zone_id_weight_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_zones_zone_id_weight_put_request when calling apiV1CampaignsTypeIdZonesZoneIdWeightPut'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/{id}/zones/{zoneId}/weight';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }
        // path params
        if ($zone_id !== null) {
            $resourcePath = str_replace(
                '{' . 'zoneId' . '}',
                ObjectSerializer::toPathValue($zone_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_id_zones_zone_id_weight_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_id_zones_zone_id_weight_put_request));
            } else {
                $httpBody = $api_v1_campaigns_type_id_zones_zone_id_weight_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypePost
     *
     * Create a new campaign (smart-default).
     *
     * @param  string $type type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest $api_v1_campaigns_type_post_request api_v1_campaigns_type_post_request (required)
     * @param  string|null $dry_run dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CampaignsTypePost($type, $api_v1_campaigns_type_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypePost'][0])
    {
        $this->apiV1CampaignsTypePostWithHttpInfo($type, $api_v1_campaigns_type_post_request, $dry_run, $strict, $contentType);
    }

    /**
     * Operation apiV1CampaignsTypePostWithHttpInfo
     *
     * Create a new campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest $api_v1_campaigns_type_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypePostWithHttpInfo($type, $api_v1_campaigns_type_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypePost'][0])
    {
        $request = $this->apiV1CampaignsTypePostRequest($type, $api_v1_campaigns_type_post_request, $dry_run, $strict, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1CampaignsTypePostAsync
     *
     * Create a new campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest $api_v1_campaigns_type_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypePostAsync($type, $api_v1_campaigns_type_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypePost'][0])
    {
        return $this->apiV1CampaignsTypePostAsyncWithHttpInfo($type, $api_v1_campaigns_type_post_request, $dry_run, $strict, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypePostAsyncWithHttpInfo
     *
     * Create a new campaign (smart-default).
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest $api_v1_campaigns_type_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypePostAsyncWithHttpInfo($type, $api_v1_campaigns_type_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CampaignsTypePostRequest($type, $api_v1_campaigns_type_post_request, $dry_run, $strict, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1CampaignsTypePost'
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypePostRequest $api_v1_campaigns_type_post_request (required)
     * @param  string|null $dry_run (optional)
     * @param  string|null $strict &#x60;1&#x60; rejects unknown body fields (422 &#x60;unknown_fields&#x60;), &#x60;0&#x60; ignores them, for this request only. Omitted: the key&#39;s &#x60;strict_fields&#x60; setting (strict for keys created from 2026-10-12). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypePostRequest($type, $api_v1_campaigns_type_post_request, $dry_run = null, $strict = null, string $contentType = self::contentTypes['apiV1CampaignsTypePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypePost'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_post_request' is set
        if ($api_v1_campaigns_type_post_request === null || (is_array($api_v1_campaigns_type_post_request) && count($api_v1_campaigns_type_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_post_request when calling apiV1CampaignsTypePost'
            );
        }




        $resourcePath = '/api/v1/campaigns/{type}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dry_run,
            'dry_run', // param base name
            'string', // openApiType
            'form', // style
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


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_campaigns_type_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_campaigns_type_post_request));
            } else {
                $httpBody = $api_v1_campaigns_type_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CampaignsTypeSchemaAdsGet
     *
     * Field metadata for ad creation.
     *
     * @param  string $type type (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response
     */
    public function apiV1CampaignsTypeSchemaAdsGet($type, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeSchemaAdsGetWithHttpInfo($type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeSchemaAdsGetWithHttpInfo
     *
     * Field metadata for ad creation.
     *
     * @param  string $type (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeSchemaAdsGetWithHttpInfo($type, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'][0])
    {
        $request = $this->apiV1CampaignsTypeSchemaAdsGetRequest($type, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeSchemaAdsGetAsync
     *
     * Field metadata for ad creation.
     *
     * @param  string $type (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeSchemaAdsGetAsync($type, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'][0])
    {
        return $this->apiV1CampaignsTypeSchemaAdsGetAsyncWithHttpInfo($type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeSchemaAdsGetAsyncWithHttpInfo
     *
     * Field metadata for ad creation.
     *
     * @param  string $type (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeSchemaAdsGetAsyncWithHttpInfo($type, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaAdsGet200Response';
        $request = $this->apiV1CampaignsTypeSchemaAdsGetRequest($type, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeSchemaAdsGet'
     *
     * @param  string $type (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeSchemaAdsGetRequest($type, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaAdsGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeSchemaAdsGet'
            );
        }


        $resourcePath = '/api/v1/campaigns/{type}/_schema/ads';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


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
     * Operation apiV1CampaignsTypeSchemaGet
     *
     * Field metadata for create/update.
     *
     * @param  string $type type (required)
     * @param  int|null $ad_type Only list the fields that apply to this ad type. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response
     */
    public function apiV1CampaignsTypeSchemaGet($type, $ad_type = null, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaGet'][0])
    {
        list($response) = $this->apiV1CampaignsTypeSchemaGetWithHttpInfo($type, $ad_type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CampaignsTypeSchemaGetWithHttpInfo
     *
     * Field metadata for create/update.
     *
     * @param  string $type (required)
     * @param  int|null $ad_type Only list the fields that apply to this ad type. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CampaignsTypeSchemaGetWithHttpInfo($type, $ad_type = null, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaGet'][0])
    {
        $request = $this->apiV1CampaignsTypeSchemaGetRequest($type, $ad_type, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CampaignsTypeSchemaGetAsync
     *
     * Field metadata for create/update.
     *
     * @param  string $type (required)
     * @param  int|null $ad_type Only list the fields that apply to this ad type. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeSchemaGetAsync($type, $ad_type = null, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaGet'][0])
    {
        return $this->apiV1CampaignsTypeSchemaGetAsyncWithHttpInfo($type, $ad_type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CampaignsTypeSchemaGetAsyncWithHttpInfo
     *
     * Field metadata for create/update.
     *
     * @param  string $type (required)
     * @param  int|null $ad_type Only list the fields that apply to this ad type. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CampaignsTypeSchemaGetAsyncWithHttpInfo($type, $ad_type = null, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeSchemaGet200Response';
        $request = $this->apiV1CampaignsTypeSchemaGetRequest($type, $ad_type, $contentType);

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
     * Create request for operation 'apiV1CampaignsTypeSchemaGet'
     *
     * @param  string $type (required)
     * @param  int|null $ad_type Only list the fields that apply to this ad type. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CampaignsTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CampaignsTypeSchemaGetRequest($type, $ad_type = null, string $contentType = self::contentTypes['apiV1CampaignsTypeSchemaGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1CampaignsTypeSchemaGet'
            );
        }



        $resourcePath = '/api/v1/campaigns/{type}/_schema';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ad_type,
            'ad_type', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


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
     * Operation apiV1RtbTestRtbContextGet
     *
     * Context for testing an endpoint before the campaign exists.
     *
     * @param  int|null $adtypeid adtypeid (optional, default to 1)
     * @param  string|null $rtbtype rtbtype (optional, default to 'openrtb')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbContextGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1RtbTestRtbContextGet($adtypeid = 1, $rtbtype = 'openrtb', string $contentType = self::contentTypes['apiV1RtbTestRtbContextGet'][0])
    {
        $this->apiV1RtbTestRtbContextGetWithHttpInfo($adtypeid, $rtbtype, $contentType);
    }

    /**
     * Operation apiV1RtbTestRtbContextGetWithHttpInfo
     *
     * Context for testing an endpoint before the campaign exists.
     *
     * @param  int|null $adtypeid (optional, default to 1)
     * @param  string|null $rtbtype (optional, default to 'openrtb')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbContextGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1RtbTestRtbContextGetWithHttpInfo($adtypeid = 1, $rtbtype = 'openrtb', string $contentType = self::contentTypes['apiV1RtbTestRtbContextGet'][0])
    {
        $request = $this->apiV1RtbTestRtbContextGetRequest($adtypeid, $rtbtype, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 404:
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
     * Operation apiV1RtbTestRtbContextGetAsync
     *
     * Context for testing an endpoint before the campaign exists.
     *
     * @param  int|null $adtypeid (optional, default to 1)
     * @param  string|null $rtbtype (optional, default to 'openrtb')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbContextGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1RtbTestRtbContextGetAsync($adtypeid = 1, $rtbtype = 'openrtb', string $contentType = self::contentTypes['apiV1RtbTestRtbContextGet'][0])
    {
        return $this->apiV1RtbTestRtbContextGetAsyncWithHttpInfo($adtypeid, $rtbtype, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1RtbTestRtbContextGetAsyncWithHttpInfo
     *
     * Context for testing an endpoint before the campaign exists.
     *
     * @param  int|null $adtypeid (optional, default to 1)
     * @param  string|null $rtbtype (optional, default to 'openrtb')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbContextGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1RtbTestRtbContextGetAsyncWithHttpInfo($adtypeid = 1, $rtbtype = 'openrtb', string $contentType = self::contentTypes['apiV1RtbTestRtbContextGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1RtbTestRtbContextGetRequest($adtypeid, $rtbtype, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1RtbTestRtbContextGet'
     *
     * @param  int|null $adtypeid (optional, default to 1)
     * @param  string|null $rtbtype (optional, default to 'openrtb')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbContextGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1RtbTestRtbContextGetRequest($adtypeid = 1, $rtbtype = 'openrtb', string $contentType = self::contentTypes['apiV1RtbTestRtbContextGet'][0])
    {




        $resourcePath = '/api/v1/rtb/test-rtb/context';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adtypeid,
            'adtypeid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $rtbtype,
            'rtbtype', // param base name
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
     * Operation apiV1RtbTestRtbPost
     *
     * Fire a test bid request at any endpoint URL.
     *
     * @param  \GoAdServerApi\Model\ApiV1RtbTestRtbPostRequest $api_v1_rtb_test_rtb_post_request api_v1_rtb_test_rtb_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\RtbTestResult|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1RtbTestRtbPost($api_v1_rtb_test_rtb_post_request, string $contentType = self::contentTypes['apiV1RtbTestRtbPost'][0])
    {
        list($response) = $this->apiV1RtbTestRtbPostWithHttpInfo($api_v1_rtb_test_rtb_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1RtbTestRtbPostWithHttpInfo
     *
     * Fire a test bid request at any endpoint URL.
     *
     * @param  \GoAdServerApi\Model\ApiV1RtbTestRtbPostRequest $api_v1_rtb_test_rtb_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\RtbTestResult|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1RtbTestRtbPostWithHttpInfo($api_v1_rtb_test_rtb_post_request, string $contentType = self::contentTypes['apiV1RtbTestRtbPost'][0])
    {
        $request = $this->apiV1RtbTestRtbPostRequest($api_v1_rtb_test_rtb_post_request, $contentType);

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
                        '\GoAdServerApi\Model\RtbTestResult',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\RtbTestResult',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\RtbTestResult',
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
                case 404:
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
     * Operation apiV1RtbTestRtbPostAsync
     *
     * Fire a test bid request at any endpoint URL.
     *
     * @param  \GoAdServerApi\Model\ApiV1RtbTestRtbPostRequest $api_v1_rtb_test_rtb_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1RtbTestRtbPostAsync($api_v1_rtb_test_rtb_post_request, string $contentType = self::contentTypes['apiV1RtbTestRtbPost'][0])
    {
        return $this->apiV1RtbTestRtbPostAsyncWithHttpInfo($api_v1_rtb_test_rtb_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1RtbTestRtbPostAsyncWithHttpInfo
     *
     * Fire a test bid request at any endpoint URL.
     *
     * @param  \GoAdServerApi\Model\ApiV1RtbTestRtbPostRequest $api_v1_rtb_test_rtb_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1RtbTestRtbPostAsyncWithHttpInfo($api_v1_rtb_test_rtb_post_request, string $contentType = self::contentTypes['apiV1RtbTestRtbPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\RtbTestResult';
        $request = $this->apiV1RtbTestRtbPostRequest($api_v1_rtb_test_rtb_post_request, $contentType);

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
     * Create request for operation 'apiV1RtbTestRtbPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1RtbTestRtbPostRequest $api_v1_rtb_test_rtb_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1RtbTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1RtbTestRtbPostRequest($api_v1_rtb_test_rtb_post_request, string $contentType = self::contentTypes['apiV1RtbTestRtbPost'][0])
    {

        // verify the required parameter 'api_v1_rtb_test_rtb_post_request' is set
        if ($api_v1_rtb_test_rtb_post_request === null || (is_array($api_v1_rtb_test_rtb_post_request) && count($api_v1_rtb_test_rtb_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_rtb_test_rtb_post_request when calling apiV1RtbTestRtbPost'
            );
        }


        $resourcePath = '/api/v1/rtb/test-rtb';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_rtb_test_rtb_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_rtb_test_rtb_post_request));
            } else {
                $httpBody = $api_v1_rtb_test_rtb_post_request;
            }
        } elseif (count($formParams) > 0) {
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1ToolsReplaceDomainPut
     *
     * Replace the landing domain across your RON ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1ToolsReplaceDomainPutRequest $api_v1_tools_replace_domain_put_request api_v1_tools_replace_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1ToolsReplaceDomainPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1ToolsReplaceDomainPut($api_v1_tools_replace_domain_put_request, string $contentType = self::contentTypes['apiV1ToolsReplaceDomainPut'][0])
    {
        $this->apiV1ToolsReplaceDomainPutWithHttpInfo($api_v1_tools_replace_domain_put_request, $contentType);
    }

    /**
     * Operation apiV1ToolsReplaceDomainPutWithHttpInfo
     *
     * Replace the landing domain across your RON ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1ToolsReplaceDomainPutRequest $api_v1_tools_replace_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1ToolsReplaceDomainPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1ToolsReplaceDomainPutWithHttpInfo($api_v1_tools_replace_domain_put_request, string $contentType = self::contentTypes['apiV1ToolsReplaceDomainPut'][0])
    {
        $request = $this->apiV1ToolsReplaceDomainPutRequest($api_v1_tools_replace_domain_put_request, $contentType);

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


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
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
     * Operation apiV1ToolsReplaceDomainPutAsync
     *
     * Replace the landing domain across your RON ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1ToolsReplaceDomainPutRequest $api_v1_tools_replace_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1ToolsReplaceDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1ToolsReplaceDomainPutAsync($api_v1_tools_replace_domain_put_request, string $contentType = self::contentTypes['apiV1ToolsReplaceDomainPut'][0])
    {
        return $this->apiV1ToolsReplaceDomainPutAsyncWithHttpInfo($api_v1_tools_replace_domain_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1ToolsReplaceDomainPutAsyncWithHttpInfo
     *
     * Replace the landing domain across your RON ads.
     *
     * @param  \GoAdServerApi\Model\ApiV1ToolsReplaceDomainPutRequest $api_v1_tools_replace_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1ToolsReplaceDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1ToolsReplaceDomainPutAsyncWithHttpInfo($api_v1_tools_replace_domain_put_request, string $contentType = self::contentTypes['apiV1ToolsReplaceDomainPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1ToolsReplaceDomainPutRequest($api_v1_tools_replace_domain_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'apiV1ToolsReplaceDomainPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1ToolsReplaceDomainPutRequest $api_v1_tools_replace_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1ToolsReplaceDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1ToolsReplaceDomainPutRequest($api_v1_tools_replace_domain_put_request, string $contentType = self::contentTypes['apiV1ToolsReplaceDomainPut'][0])
    {

        // verify the required parameter 'api_v1_tools_replace_domain_put_request' is set
        if ($api_v1_tools_replace_domain_put_request === null || (is_array($api_v1_tools_replace_domain_put_request) && count($api_v1_tools_replace_domain_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tools_replace_domain_put_request when calling apiV1ToolsReplaceDomainPut'
            );
        }


        $resourcePath = '/api/v1/tools/replace-domain';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_tools_replace_domain_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tools_replace_domain_put_request));
            } else {
                $httpBody = $api_v1_tools_replace_domain_put_request;
            }
        } elseif (count($formParams) > 0) {
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
            'PUT',
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

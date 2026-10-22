<?php
/**
 * AgenticMediaBuyingApi
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
 * AgenticMediaBuyingApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class AgenticMediaBuyingApi
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
        'apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingActionsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingAgentRunPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingAgentRunsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingBlockPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingCompareGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingIpsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingIpsPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingLaunchGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingLaunchPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingLaunchTemplatesGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingNetworkBudgetGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingPolicyPut' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingPolicySuggestGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingReportPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingSegmentsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingSourceBidGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingSourceBidPut' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingSourceRetryPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingTargetingGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingTargetingPut' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingTargetingSuggestGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingTrackRecordGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdMediaBuyingZonesGet' => [
            'application/json',
        ],
        'apiV1MediaBuyingPortfolioGet' => [
            'application/json',
        ],
        'apiV1TrackerMediaBuyingPortfolioGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingActionsGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingBlockPost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingCompareGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingIpsGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingIpsPost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingReportPost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet' => [
            'application/json',
        ],
        'apiV1TrackerTrackingLinksIdMediaBuyingZonesGet' => [
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
     * Operation apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The ad space id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostWithHttpInfo($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostWithHttpInfo
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The ad space id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostWithHttpInfo($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostAsync
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The ad space id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostAsync($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The ad space id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'
     *
     * @param  int $id The ad space id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'
            );
        }

        // verify the required parameter 'action_id' is set
        if ($action_id === null || (is_array($action_id) && count($action_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $action_id when calling apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request' is set
        if ($api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request === null || (is_array($api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request) && count($api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request when calling apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/actions/{actionId}/decide';
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
        if ($action_id !== null) {
            $resourcePath = str_replace(
                '{' . 'actionId' . '}',
                ObjectSerializer::toPathValue($action_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_actions_action_id_decide_post_request;
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
     * Operation apiV1AdspacesIdMediaBuyingActionsGet
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingActionsGet($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingActionsGetWithHttpInfo($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingActionsGetWithHttpInfo
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingActionsGetWithHttpInfo($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingActionsGetRequest($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingActionsGetAsync
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingActionsGetAsync($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingActionsGetAsyncWithHttpInfo($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingActionsGetAsyncWithHttpInfo
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingActionsGetAsyncWithHttpInfo($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingActionsGetRequest($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingActionsGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingActionsGetRequest($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingActionsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingActionsGet'
            );
        }










        $resourcePath = '/api/v1/adspaces/{id}/media-buying/actions';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $status,
            'status', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $actor,
            'actor', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $action,
            'action', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $zone,
            'zone', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
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
            $limit,
            'limit', // param base name
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
     * Operation apiV1AdspacesIdMediaBuyingAgentRunPost
     *
     * Run the buying agent now.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunPost($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingAgentRunPostWithHttpInfo($id, $body, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingAgentRunPostWithHttpInfo
     *
     * Run the buying agent now.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunPostWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingAgentRunPostRequest($id, $body, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingAgentRunPostAsync
     *
     * Run the buying agent now.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunPostAsync($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingAgentRunPostAsyncWithHttpInfo($id, $body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingAgentRunPostAsyncWithHttpInfo
     *
     * Run the buying agent now.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunPostAsyncWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingAgentRunPostRequest($id, $body, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingAgentRunPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunPostRequest($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingAgentRunPost'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/media-buying/agent/run';
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
        if (isset($body)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($body));
            } else {
                $httpBody = $body;
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
     * Operation apiV1AdspacesIdMediaBuyingAgentRunsGet
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunsGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingAgentRunsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingAgentRunsGetWithHttpInfo
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingAgentRunsGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingAgentRunsGetAsync
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunsGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingAgentRunsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingAgentRunsGetAsyncWithHttpInfo
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingAgentRunsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingAgentRunsGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingAgentRunsGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingAgentRunsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingAgentRunsGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/agent/runs';
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
     * Operation apiV1AdspacesIdMediaBuyingBlockPost
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest $api_v1_adspaces_id_media_buying_block_post_request api_v1_adspaces_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingBlockPost($id, $api_v1_adspaces_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingBlockPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_block_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingBlockPostWithHttpInfo
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest $api_v1_adspaces_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingBlockPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingBlockPostRequest($id, $api_v1_adspaces_id_media_buying_block_post_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingBlockPostAsync
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest $api_v1_adspaces_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingBlockPostAsync($id, $api_v1_adspaces_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingBlockPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_block_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingBlockPostAsyncWithHttpInfo
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest $api_v1_adspaces_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingBlockPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingBlockPostRequest($id, $api_v1_adspaces_id_media_buying_block_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingBlockPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingBlockPostRequest $api_v1_adspaces_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingBlockPostRequest($id, $api_v1_adspaces_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingBlockPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingBlockPost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_block_post_request' is set
        if ($api_v1_adspaces_id_media_buying_block_post_request === null || (is_array($api_v1_adspaces_id_media_buying_block_post_request) && count($api_v1_adspaces_id_media_buying_block_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_block_post_request when calling apiV1AdspacesIdMediaBuyingBlockPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/block';
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
        if (isset($api_v1_adspaces_id_media_buying_block_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_block_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_block_post_request;
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
     * Operation apiV1AdspacesIdMediaBuyingCompareGet
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingCompareGet($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingCompareGetWithHttpInfo($id, $with, $days, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingCompareGetWithHttpInfo
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingCompareGetWithHttpInfo($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingCompareGetRequest($id, $with, $days, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingCompareGetAsync
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingCompareGetAsync($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingCompareGetAsyncWithHttpInfo($id, $with, $days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingCompareGetAsyncWithHttpInfo
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingCompareGetAsyncWithHttpInfo($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingCompareGetRequest($id, $with, $days, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingCompareGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingCompareGetRequest($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingCompareGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingCompareGet'
            );
        }


        if ($days !== null && $days > 90) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1AdspacesIdMediaBuyingCompareGet, must be smaller than or equal to 90.');
        }
        if ($days !== null && $days < 5) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1AdspacesIdMediaBuyingCompareGet, must be bigger than or equal to 5.');
        }
        

        $resourcePath = '/api/v1/adspaces/{id}/media-buying/compare';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $with,
            'with', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
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
     * Operation apiV1AdspacesIdMediaBuyingGet
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingGetWithHttpInfo
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingGetAsync
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingGetAsyncWithHttpInfo
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying';
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
     * Operation apiV1AdspacesIdMediaBuyingIpsGet
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingIpsGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingIpsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingIpsGetWithHttpInfo
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingIpsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingIpsGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingIpsGetAsync
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingIpsGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingIpsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingIpsGetAsyncWithHttpInfo
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingIpsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingIpsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingIpsGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingIpsGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingIpsGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/ips';
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
     * Operation apiV1AdspacesIdMediaBuyingIpsPost
     *
     * Change the IP preblock list.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest $api_v1_adspaces_id_media_buying_ips_post_request api_v1_adspaces_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingIpsPost($id, $api_v1_adspaces_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingIpsPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_ips_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingIpsPostWithHttpInfo
     *
     * Change the IP preblock list.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest $api_v1_adspaces_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingIpsPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingIpsPostRequest($id, $api_v1_adspaces_id_media_buying_ips_post_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingIpsPostAsync
     *
     * Change the IP preblock list.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest $api_v1_adspaces_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingIpsPostAsync($id, $api_v1_adspaces_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingIpsPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_ips_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingIpsPostAsyncWithHttpInfo
     *
     * Change the IP preblock list.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest $api_v1_adspaces_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingIpsPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingIpsPostRequest($id, $api_v1_adspaces_id_media_buying_ips_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingIpsPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingIpsPostRequest $api_v1_adspaces_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingIpsPostRequest($id, $api_v1_adspaces_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingIpsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingIpsPost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_ips_post_request' is set
        if ($api_v1_adspaces_id_media_buying_ips_post_request === null || (is_array($api_v1_adspaces_id_media_buying_ips_post_request) && count($api_v1_adspaces_id_media_buying_ips_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_ips_post_request when calling apiV1AdspacesIdMediaBuyingIpsPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/ips';
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
        if (isset($api_v1_adspaces_id_media_buying_ips_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_ips_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_ips_post_request;
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
     * Operation apiV1AdspacesIdMediaBuyingLaunchGet
     *
     * What a new campaign can be launched with (unconnected ad zones).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id One of the owner&#39;s network accounts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingLaunchGet($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingLaunchGetWithHttpInfo($id, $adnetwork_id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchGetWithHttpInfo
     *
     * What a new campaign can be launched with (unconnected ad zones).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id One of the owner&#39;s network accounts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingLaunchGetWithHttpInfo($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchGetRequest($id, $adnetwork_id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingLaunchGetAsync
     *
     * What a new campaign can be launched with (unconnected ad zones).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id One of the owner&#39;s network accounts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchGetAsync($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingLaunchGetAsyncWithHttpInfo($id, $adnetwork_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchGetAsyncWithHttpInfo
     *
     * What a new campaign can be launched with (unconnected ad zones).
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id One of the owner&#39;s network accounts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchGetAsyncWithHttpInfo($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchGetRequest($id, $adnetwork_id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingLaunchGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id One of the owner&#39;s network accounts. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingLaunchGetRequest($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingLaunchGet'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/media-buying/launch';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adnetwork_id,
            'adnetwork_id', // param base name
            'integer', // openApiType
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
     * Operation apiV1AdspacesIdMediaBuyingLaunchPost
     *
     * Launch a new campaign at the network for this ad zone.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest $api_v1_adspaces_id_media_buying_launch_post_request api_v1_adspaces_id_media_buying_launch_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingLaunchPost($id, $api_v1_adspaces_id_media_buying_launch_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingLaunchPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_launch_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchPostWithHttpInfo
     *
     * Launch a new campaign at the network for this ad zone.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest $api_v1_adspaces_id_media_buying_launch_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingLaunchPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_launch_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchPostRequest($id, $api_v1_adspaces_id_media_buying_launch_post_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingLaunchPostAsync
     *
     * Launch a new campaign at the network for this ad zone.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest $api_v1_adspaces_id_media_buying_launch_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchPostAsync($id, $api_v1_adspaces_id_media_buying_launch_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingLaunchPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_launch_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchPostAsyncWithHttpInfo
     *
     * Launch a new campaign at the network for this ad zone.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest $api_v1_adspaces_id_media_buying_launch_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_launch_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchPostRequest($id, $api_v1_adspaces_id_media_buying_launch_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingLaunchPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingLaunchPostRequest $api_v1_adspaces_id_media_buying_launch_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingLaunchPostRequest($id, $api_v1_adspaces_id_media_buying_launch_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingLaunchPost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_launch_post_request' is set
        if ($api_v1_adspaces_id_media_buying_launch_post_request === null || (is_array($api_v1_adspaces_id_media_buying_launch_post_request) && count($api_v1_adspaces_id_media_buying_launch_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_launch_post_request when calling apiV1AdspacesIdMediaBuyingLaunchPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/launch';
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
        if (isset($api_v1_adspaces_id_media_buying_launch_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_launch_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_launch_post_request;
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
     * Operation apiV1AdspacesIdMediaBuyingLaunchTemplatesGet
     *
     * Existing campaigns that can be cloned for a launch.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id The network account. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingLaunchTemplatesGet($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingLaunchTemplatesGetWithHttpInfo($id, $adnetwork_id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchTemplatesGetWithHttpInfo
     *
     * Existing campaigns that can be cloned for a launch.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id The network account. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingLaunchTemplatesGetWithHttpInfo($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchTemplatesGetRequest($id, $adnetwork_id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingLaunchTemplatesGetAsync
     *
     * Existing campaigns that can be cloned for a launch.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id The network account. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchTemplatesGetAsync($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingLaunchTemplatesGetAsyncWithHttpInfo($id, $adnetwork_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingLaunchTemplatesGetAsyncWithHttpInfo
     *
     * Existing campaigns that can be cloned for a launch.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id The network account. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingLaunchTemplatesGetAsyncWithHttpInfo($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingLaunchTemplatesGetRequest($id, $adnetwork_id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $adnetwork_id The network account. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingLaunchTemplatesGetRequest($id, $adnetwork_id = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingLaunchTemplatesGet'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/media-buying/launch/templates';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adnetwork_id,
            'adnetwork_id', // param base name
            'integer', // openApiType
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
     * Operation apiV1AdspacesIdMediaBuyingNetworkBudgetGet
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingNetworkBudgetGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingNetworkBudgetGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingNetworkBudgetGetWithHttpInfo
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingNetworkBudgetGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingNetworkBudgetGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingNetworkBudgetGetAsync
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingNetworkBudgetGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingNetworkBudgetGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingNetworkBudgetGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingNetworkBudgetGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingNetworkBudgetGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingNetworkBudgetGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/network-budget';
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
     * Operation apiV1AdspacesIdMediaBuyingPolicyPut
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingPolicyPut($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingPolicyPutWithHttpInfo($id, $media_buying_policy, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingPolicyPutWithHttpInfo
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingPolicyPutWithHttpInfo($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingPolicyPutAsync
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingPolicyPutAsync($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingPolicyPutAsyncWithHttpInfo($id, $media_buying_policy, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingPolicyPutAsyncWithHttpInfo
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingPolicyPutAsyncWithHttpInfo($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingPolicyPut'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicyPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingPolicyPut'
            );
        }

        // verify the required parameter 'media_buying_policy' is set
        if ($media_buying_policy === null || (is_array($media_buying_policy) && count($media_buying_policy) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $media_buying_policy when calling apiV1AdspacesIdMediaBuyingPolicyPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/policy';
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
        if (isset($media_buying_policy)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($media_buying_policy));
            } else {
                $httpBody = $media_buying_policy;
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
     * Operation apiV1AdspacesIdMediaBuyingPolicySuggestGet
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingPolicySuggestGet($id, $preset = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingPolicySuggestGetWithHttpInfo($id, $preset, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingPolicySuggestGetWithHttpInfo
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingPolicySuggestGetWithHttpInfo($id, $preset = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingPolicySuggestGetRequest($id, $preset, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingPolicySuggestGetAsync
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingPolicySuggestGetAsync($id, $preset = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo($id, $preset, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo($id, $preset = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingPolicySuggestGetRequest($id, $preset, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingPolicySuggestGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingPolicySuggestGetRequest($id, $preset = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingPolicySuggestGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingPolicySuggestGet'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/media-buying/policy/suggest';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $preset,
            'preset', // param base name
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
     * Operation apiV1AdspacesIdMediaBuyingReportPost
     *
     * Send the media buying report now.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest $api_v1_adspaces_id_media_buying_report_post_request api_v1_adspaces_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingReportPost($id, $api_v1_adspaces_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingReportPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_report_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingReportPostWithHttpInfo
     *
     * Send the media buying report now.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest $api_v1_adspaces_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingReportPostWithHttpInfo($id, $api_v1_adspaces_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingReportPostRequest($id, $api_v1_adspaces_id_media_buying_report_post_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingReportPostAsync
     *
     * Send the media buying report now.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest $api_v1_adspaces_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingReportPostAsync($id, $api_v1_adspaces_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingReportPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_report_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingReportPostAsyncWithHttpInfo
     *
     * Send the media buying report now.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest $api_v1_adspaces_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingReportPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingReportPostRequest($id, $api_v1_adspaces_id_media_buying_report_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingReportPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingReportPostRequest $api_v1_adspaces_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingReportPostRequest($id, $api_v1_adspaces_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingReportPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingReportPost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_report_post_request' is set
        if ($api_v1_adspaces_id_media_buying_report_post_request === null || (is_array($api_v1_adspaces_id_media_buying_report_post_request) && count($api_v1_adspaces_id_media_buying_report_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_report_post_request when calling apiV1AdspacesIdMediaBuyingReportPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/report';
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
        if (isset($api_v1_adspaces_id_media_buying_report_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_report_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_report_post_request;
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
     * Operation apiV1AdspacesIdMediaBuyingSegmentsGet
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingSegmentsGet($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingSegmentsGetWithHttpInfo($id, $dim, $refresh, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSegmentsGetWithHttpInfo
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingSegmentsGetWithHttpInfo($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingSegmentsGetRequest($id, $dim, $refresh, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingSegmentsGetAsync
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSegmentsGetAsync($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingSegmentsGetAsyncWithHttpInfo($id, $dim, $refresh, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSegmentsGetAsyncWithHttpInfo
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSegmentsGetAsyncWithHttpInfo($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingSegmentsGetRequest($id, $dim, $refresh, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingSegmentsGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingSegmentsGetRequest($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSegmentsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingSegmentsGet'
            );
        }




        $resourcePath = '/api/v1/adspaces/{id}/media-buying/segments';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dim,
            'dim', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $refresh,
            'refresh', // param base name
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
     * Operation apiV1AdspacesIdMediaBuyingSourceBidGet
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingSourceBidGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceBidGetWithHttpInfo
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingSourceBidGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingSourceBidGetAsync
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingSourceBidGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceBidGetAsyncWithHttpInfo
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingSourceBidGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingSourceBidGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingSourceBidGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/source/bid';
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
     * Operation apiV1AdspacesIdMediaBuyingSourceBidPut
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest $api_v1_adspaces_id_media_buying_source_bid_put_request api_v1_adspaces_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidPut($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingSourceBidPutWithHttpInfo($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceBidPutWithHttpInfo
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest $api_v1_adspaces_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidPutWithHttpInfo($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingSourceBidPutRequest($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingSourceBidPutAsync
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest $api_v1_adspaces_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidPutAsync($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingSourceBidPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceBidPutAsyncWithHttpInfo
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest $api_v1_adspaces_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingSourceBidPutRequest($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingSourceBidPut'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingSourceBidPutRequest $api_v1_adspaces_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingSourceBidPutRequest($id, $api_v1_adspaces_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceBidPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingSourceBidPut'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_source_bid_put_request' is set
        if ($api_v1_adspaces_id_media_buying_source_bid_put_request === null || (is_array($api_v1_adspaces_id_media_buying_source_bid_put_request) && count($api_v1_adspaces_id_media_buying_source_bid_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_source_bid_put_request when calling apiV1AdspacesIdMediaBuyingSourceBidPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/source/bid';
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
        if (isset($api_v1_adspaces_id_media_buying_source_bid_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_source_bid_put_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_source_bid_put_request;
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
     * Operation apiV1AdspacesIdMediaBuyingSourceRetryPost
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingSourceRetryPost($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingSourceRetryPostWithHttpInfo($id, $body, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceRetryPostWithHttpInfo
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingSourceRetryPostWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingSourceRetryPostRequest($id, $body, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingSourceRetryPostAsync
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceRetryPostAsync($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingSourceRetryPostAsyncWithHttpInfo($id, $body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingSourceRetryPostAsyncWithHttpInfo
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingSourceRetryPostAsyncWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingSourceRetryPostRequest($id, $body, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingSourceRetryPost'
     *
     * @param  int $id The ad space id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingSourceRetryPostRequest($id, $body = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingSourceRetryPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingSourceRetryPost'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/media-buying/source/retry';
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
        if (isset($body)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($body));
            } else {
                $httpBody = $body;
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
     * Operation apiV1AdspacesIdMediaBuyingTargetingGet
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingTargetingGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingTargetingGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingGetWithHttpInfo
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingTargetingGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingTargetingGetAsync
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingTargetingGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingGetAsyncWithHttpInfo
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingTargetingGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingTargetingGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingTargetingGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/targeting';
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
     * Operation apiV1AdspacesIdMediaBuyingTargetingPut
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest $api_v1_adspaces_id_media_buying_targeting_put_request api_v1_adspaces_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingTargetingPut($id, $api_v1_adspaces_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingTargetingPutWithHttpInfo($id, $api_v1_adspaces_id_media_buying_targeting_put_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingPutWithHttpInfo
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest $api_v1_adspaces_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingTargetingPutWithHttpInfo($id, $api_v1_adspaces_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingPutRequest($id, $api_v1_adspaces_id_media_buying_targeting_put_request, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingTargetingPutAsync
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest $api_v1_adspaces_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingPutAsync($id, $api_v1_adspaces_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingTargetingPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_targeting_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingPutAsyncWithHttpInfo
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest $api_v1_adspaces_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingPutRequest($id, $api_v1_adspaces_id_media_buying_targeting_put_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingTargetingPut'
     *
     * @param  int $id The ad space id. (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdMediaBuyingTargetingPutRequest $api_v1_adspaces_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingTargetingPutRequest($id, $api_v1_adspaces_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingTargetingPut'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_media_buying_targeting_put_request' is set
        if ($api_v1_adspaces_id_media_buying_targeting_put_request === null || (is_array($api_v1_adspaces_id_media_buying_targeting_put_request) && count($api_v1_adspaces_id_media_buying_targeting_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_media_buying_targeting_put_request when calling apiV1AdspacesIdMediaBuyingTargetingPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/targeting';
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
        if (isset($api_v1_adspaces_id_media_buying_targeting_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_media_buying_targeting_put_request));
            } else {
                $httpBody = $api_v1_adspaces_id_media_buying_targeting_put_request;
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
     * Operation apiV1AdspacesIdMediaBuyingTargetingSuggestGet
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingTargetingSuggestGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingTargetingSuggestGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingSuggestGetWithHttpInfo
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingTargetingSuggestGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingSuggestGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingTargetingSuggestGetAsync
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingSuggestGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingTargetingSuggestGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingTargetingSuggestGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingTargetingSuggestGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTargetingSuggestGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingTargetingSuggestGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/targeting/suggest';
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
     * Operation apiV1AdspacesIdMediaBuyingTrackRecordGet
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingTrackRecordGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingTrackRecordGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTrackRecordGetWithHttpInfo
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingTrackRecordGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingTrackRecordGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingTrackRecordGetAsync
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTrackRecordGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingTrackRecordGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingTrackRecordGetAsyncWithHttpInfo
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingTrackRecordGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingTrackRecordGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingTrackRecordGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingTrackRecordGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingTrackRecordGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingTrackRecordGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/media-buying/track-record';
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
     * Operation apiV1AdspacesIdMediaBuyingZonesGet
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdMediaBuyingZonesGet($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'][0])
    {
        $this->apiV1AdspacesIdMediaBuyingZonesGetWithHttpInfo($id, $days, $revenue_metric, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingZonesGetWithHttpInfo
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdMediaBuyingZonesGetWithHttpInfo($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'][0])
    {
        $request = $this->apiV1AdspacesIdMediaBuyingZonesGetRequest($id, $days, $revenue_metric, $contentType);

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
     * Operation apiV1AdspacesIdMediaBuyingZonesGetAsync
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingZonesGetAsync($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'][0])
    {
        return $this->apiV1AdspacesIdMediaBuyingZonesGetAsyncWithHttpInfo($id, $days, $revenue_metric, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdMediaBuyingZonesGetAsyncWithHttpInfo
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdMediaBuyingZonesGetAsyncWithHttpInfo($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdMediaBuyingZonesGetRequest($id, $days, $revenue_metric, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdMediaBuyingZonesGet'
     *
     * @param  int $id The ad space id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdMediaBuyingZonesGetRequest($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1AdspacesIdMediaBuyingZonesGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdMediaBuyingZonesGet'
            );
        }




        $resourcePath = '/api/v1/adspaces/{id}/media-buying/zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $revenue_metric,
            'revenue_metric', // param base name
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
     * Operation apiV1MediaBuyingPortfolioGet
     *
     * Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1MediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1MediaBuyingPortfolioGet($days = 7, string $contentType = self::contentTypes['apiV1MediaBuyingPortfolioGet'][0])
    {
        $this->apiV1MediaBuyingPortfolioGetWithHttpInfo($days, $contentType);
    }

    /**
     * Operation apiV1MediaBuyingPortfolioGetWithHttpInfo
     *
     * Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1MediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1MediaBuyingPortfolioGetWithHttpInfo($days = 7, string $contentType = self::contentTypes['apiV1MediaBuyingPortfolioGet'][0])
    {
        $request = $this->apiV1MediaBuyingPortfolioGetRequest($days, $contentType);

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
     * Operation apiV1MediaBuyingPortfolioGetAsync
     *
     * Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1MediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1MediaBuyingPortfolioGetAsync($days = 7, string $contentType = self::contentTypes['apiV1MediaBuyingPortfolioGet'][0])
    {
        return $this->apiV1MediaBuyingPortfolioGetAsyncWithHttpInfo($days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1MediaBuyingPortfolioGetAsyncWithHttpInfo
     *
     * Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1MediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1MediaBuyingPortfolioGetAsyncWithHttpInfo($days = 7, string $contentType = self::contentTypes['apiV1MediaBuyingPortfolioGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1MediaBuyingPortfolioGetRequest($days, $contentType);

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
     * Create request for operation 'apiV1MediaBuyingPortfolioGet'
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1MediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1MediaBuyingPortfolioGetRequest($days = 7, string $contentType = self::contentTypes['apiV1MediaBuyingPortfolioGet'][0])
    {

        if ($days !== null && $days > 90) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1MediaBuyingPortfolioGet, must be smaller than or equal to 90.');
        }
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1MediaBuyingPortfolioGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/media-buying/portfolio';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
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
     * Operation apiV1TrackerMediaBuyingPortfolioGet
     *
     * Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerMediaBuyingPortfolioGet($days = 7, string $contentType = self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'][0])
    {
        $this->apiV1TrackerMediaBuyingPortfolioGetWithHttpInfo($days, $contentType);
    }

    /**
     * Operation apiV1TrackerMediaBuyingPortfolioGetWithHttpInfo
     *
     * Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerMediaBuyingPortfolioGetWithHttpInfo($days = 7, string $contentType = self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'][0])
    {
        $request = $this->apiV1TrackerMediaBuyingPortfolioGetRequest($days, $contentType);

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
     * Operation apiV1TrackerMediaBuyingPortfolioGetAsync
     *
     * Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerMediaBuyingPortfolioGetAsync($days = 7, string $contentType = self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'][0])
    {
        return $this->apiV1TrackerMediaBuyingPortfolioGetAsyncWithHttpInfo($days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerMediaBuyingPortfolioGetAsyncWithHttpInfo
     *
     * Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary.
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerMediaBuyingPortfolioGetAsyncWithHttpInfo($days = 7, string $contentType = self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerMediaBuyingPortfolioGetRequest($days, $contentType);

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
     * Create request for operation 'apiV1TrackerMediaBuyingPortfolioGet'
     *
     * @param  int|null $days Full days, ending yesterday. (optional, default to 7)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerMediaBuyingPortfolioGetRequest($days = 7, string $contentType = self::contentTypes['apiV1TrackerMediaBuyingPortfolioGet'][0])
    {

        if ($days !== null && $days > 90) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1TrackerMediaBuyingPortfolioGet, must be smaller than or equal to 90.');
        }
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1TrackerMediaBuyingPortfolioGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/tracker/media-buying/portfolio';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostWithHttpInfo($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostWithHttpInfo
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostWithHttpInfo($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostAsync
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostAsync($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo
     *
     * Approve or reject a proposal.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostAsyncWithHttpInfo($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  int $action_id The proposal id from the activity log. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest($id, $action_id, $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'
            );
        }

        // verify the required parameter 'action_id' is set
        if ($action_id === null || (is_array($action_id) && count($action_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $action_id when calling apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request) && count($api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request when calling apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/actions/{actionId}/decide';
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
        if ($action_id !== null) {
            $resourcePath = str_replace(
                '{' . 'actionId' . '}',
                ObjectSerializer::toPathValue($action_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_actions_action_id_decide_post_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsGet
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsGet($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsGetWithHttpInfo($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsGetWithHttpInfo
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsGetWithHttpInfo($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsGetRequest($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsGetAsync
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsGetAsync($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsGetAsyncWithHttpInfo($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingActionsGetAsyncWithHttpInfo
     *
     * Activity log: every block, bid, budget and other change, and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsGetAsyncWithHttpInfo($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingActionsGetRequest($id, $status, $actor, $action, $zone, $from, $to, $limit, $format, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $status &#x60;proposed&#x60; | &#x60;applied&#x60; | &#x60;rejected&#x60; | &#x60;failed&#x60; | &#x60;expired&#x60;. (optional)
     * @param  string|null $actor &#x60;policy&#x60; | &#x60;agent&#x60; | &#x60;advertiser&#x60; | &#x60;manual&#x60; | &#x60;inherited&#x60;. (optional)
     * @param  string|null $action e.g. &#x60;block&#x60;, &#x60;unblock&#x60;, &#x60;zone_bid&#x60;, &#x60;bid_change&#x60;, &#x60;net_budget&#x60;, &#x60;exclude_segment&#x60;, &#x60;expand_country&#x60;, &#x60;ip_block&#x60;, &#x60;report&#x60;. (optional)
     * @param  string|null $zone One source zone id. (optional)
     * @param  string|null $from YYYY-MM-DD. (optional)
     * @param  string|null $to YYYY-MM-DD. (optional)
     * @param  int|null $limit 1-1000, default 200. (optional)
     * @param  string|null $format &#x60;csv&#x60; for a CSV download (up to 5000 rows). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingActionsGetRequest($id, $status = null, $actor = null, $action = null, $zone = null, $from = null, $to = null, $limit = null, $format = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingActionsGet'
            );
        }










        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/actions';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $status,
            'status', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $actor,
            'actor', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $action,
            'action', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $zone,
            'zone', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
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
            $limit,
            'limit', // param base name
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost
     *
     * Run the buying agent now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostWithHttpInfo($id, $body, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostWithHttpInfo
     *
     * Run the buying agent now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostRequest($id, $body, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostAsync
     *
     * Run the buying agent now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostAsync($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostAsyncWithHttpInfo($id, $body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostAsyncWithHttpInfo
     *
     * Run the buying agent now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostAsyncWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostRequest($id, $body, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPostRequest($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost'
            );
        }



        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/agent/run';
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
        if (isset($body)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($body));
            } else {
                $httpBody = $body;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetWithHttpInfo
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetAsync
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetAsyncWithHttpInfo
     *
     * The buying agent&#39;s recent runs with their summaries and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/agent/runs';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingBlockPost
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest $api_v1_tracker_tracking_links_id_media_buying_block_post_request api_v1_tracker_tracking_links_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingBlockPost($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingBlockPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingBlockPostWithHttpInfo
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest $api_v1_tracker_tracking_links_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingBlockPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingBlockPostAsync
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest $api_v1_tracker_tracking_links_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingBlockPostAsync($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingBlockPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingBlockPostAsyncWithHttpInfo
     *
     * Block or unblock one source zone at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest $api_v1_tracker_tracking_links_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingBlockPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest $api_v1_tracker_tracking_links_id_media_buying_block_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_block_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_block_post_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_block_post_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_block_post_request) && count($api_v1_tracker_tracking_links_id_media_buying_block_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_block_post_request when calling apiV1TrackerTrackingLinksIdMediaBuyingBlockPost'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/block';
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
        if (isset($api_v1_tracker_tracking_links_id_media_buying_block_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_block_post_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_block_post_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingCompareGet
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingCompareGet($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingCompareGetWithHttpInfo($id, $with, $days, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingCompareGetWithHttpInfo
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingCompareGetWithHttpInfo($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingCompareGetRequest($id, $with, $days, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingCompareGetAsync
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingCompareGetAsync($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingCompareGetAsyncWithHttpInfo($id, $with, $days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingCompareGetAsyncWithHttpInfo
     *
     * Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingCompareGetAsyncWithHttpInfo($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingCompareGetRequest($id, $with, $days, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $with The other buy (ad zone or tracking link id); default its control. (optional)
     * @param  int|null $days Full days, ending yesterday. (optional, default to 14)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingCompareGetRequest($id, $with = null, $days = 14, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingCompareGet'
            );
        }


        if ($days !== null && $days > 90) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1TrackerTrackingLinksIdMediaBuyingCompareGet, must be smaller than or equal to 90.');
        }
        if ($days !== null && $days < 5) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling AgenticMediaBuyingApi.apiV1TrackerTrackingLinksIdMediaBuyingCompareGet, must be bigger than or equal to 5.');
        }
        

        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/compare';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $with,
            'with', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingGet
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingGetWithHttpInfo
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingGetAsync
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingGetAsyncWithHttpInfo
     *
     * Overview: the connected network, what it supports, the policy and open proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsGet
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsGetWithHttpInfo
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsGetAsync
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsGetAsyncWithHttpInfo
     *
     * The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingIpsGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/ips';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsPost
     *
     * Change the IP preblock list.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest $api_v1_tracker_tracking_links_id_media_buying_ips_post_request api_v1_tracker_tracking_links_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsPost($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsPostWithHttpInfo
     *
     * Change the IP preblock list.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest $api_v1_tracker_tracking_links_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsPostAsync
     *
     * Change the IP preblock list.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest $api_v1_tracker_tracking_links_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsPostAsync($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingIpsPostAsyncWithHttpInfo
     *
     * Change the IP preblock list.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest $api_v1_tracker_tracking_links_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest $api_v1_tracker_tracking_links_id_media_buying_ips_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_ips_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_ips_post_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_ips_post_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_ips_post_request) && count($api_v1_tracker_tracking_links_id_media_buying_ips_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_ips_post_request when calling apiV1TrackerTrackingLinksIdMediaBuyingIpsPost'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/ips';
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
        if (isset($api_v1_tracker_tracking_links_id_media_buying_ips_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_ips_post_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_ips_post_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetWithHttpInfo
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetAsync
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo
     *
     * The campaign&#39;s daily budget at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/network-budget';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutWithHttpInfo($id, $media_buying_policy, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutWithHttpInfo
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutWithHttpInfo($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutAsync
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutAsync($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutAsyncWithHttpInfo($id, $media_buying_policy, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutAsyncWithHttpInfo
     *
     * Save the buying policy (any subset of its fields).
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutAsyncWithHttpInfo($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\MediaBuyingPolicy $media_buying_policy (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicyPutRequest($id, $media_buying_policy, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'
            );
        }

        // verify the required parameter 'media_buying_policy' is set
        if ($media_buying_policy === null || (is_array($media_buying_policy) && count($media_buying_policy) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $media_buying_policy when calling apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/policy';
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
        if (isset($media_buying_policy)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($media_buying_policy));
            } else {
                $httpBody = $media_buying_policy;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet($id, $preset = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetWithHttpInfo($id, $preset, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetWithHttpInfo
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetWithHttpInfo($id, $preset = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetRequest($id, $preset, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetAsync
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetAsync($id, $preset = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo($id, $preset, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo
     *
     * Suggested policy from the zone&#39;s own traffic, with a reason per field.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetAsyncWithHttpInfo($id, $preset = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetRequest($id, $preset, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $preset On top of the suggestion: conservative (every lever proposes, 95% certainty, twice the minimum cost and traffic, 5% bid steps, at most 20 changes a day), balanced (the suggestion as it is) or aggressive (placement blocks, bids per placement and segment exclusions in auto, the agent in auto, 80% certainty, 15% bid steps). No preset touches the network budget, the campaign bid or the IP preblock. Unknown value: 422. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGetRequest($id, $preset = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet'
            );
        }



        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/policy/suggest';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $preset,
            'preset', // param base name
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingReportPost
     *
     * Send the media buying report now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest $api_v1_tracker_tracking_links_id_media_buying_report_post_request api_v1_tracker_tracking_links_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingReportPost($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingReportPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingReportPostWithHttpInfo
     *
     * Send the media buying report now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest $api_v1_tracker_tracking_links_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingReportPostWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingReportPostAsync
     *
     * Send the media buying report now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest $api_v1_tracker_tracking_links_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingReportPostAsync($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingReportPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingReportPostAsyncWithHttpInfo
     *
     * Send the media buying report now.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest $api_v1_tracker_tracking_links_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingReportPostAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingReportPost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest $api_v1_tracker_tracking_links_id_media_buying_report_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest($id, $api_v1_tracker_tracking_links_id_media_buying_report_post_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingReportPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingReportPost'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_report_post_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_report_post_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_report_post_request) && count($api_v1_tracker_tracking_links_id_media_buying_report_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_report_post_request when calling apiV1TrackerTrackingLinksIdMediaBuyingReportPost'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/report';
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
        if (isset($api_v1_tracker_tracking_links_id_media_buying_report_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_report_post_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_report_post_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetWithHttpInfo($id, $dim, $refresh, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetWithHttpInfo
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetWithHttpInfo($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetRequest($id, $dim, $refresh, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetAsync
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetAsync($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetAsyncWithHttpInfo($id, $dim, $refresh, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetAsyncWithHttpInfo
     *
     * Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetAsyncWithHttpInfo($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetRequest($id, $dim, $refresh, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string|null $dim &#x60;country&#x60; (default where the network reports cost per segment) | &#x60;os&#x60; | &#x60;hour&#x60; | &#x60;weekday&#x60; (default otherwise) | &#x60;browser&#x60; | &#x60;region&#x60; | &#x60;carrier&#x60; | &#x60;language&#x60;. The overview&#39;s &#x60;segment_dims&#x60; lists the ones this zone has. (optional)
     * @param  string|null $refresh &#x60;1&#x60; re-reads the network instead of the cache. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGetRequest($id, $dim = null, $refresh = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet'
            );
        }




        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/segments';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dim,
            'dim', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $refresh,
            'refresh', // param base name
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetWithHttpInfo
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetAsync
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetAsyncWithHttpInfo
     *
     * The campaign&#39;s current bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/source/bid';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutWithHttpInfo
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutAsync
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutAsync($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutAsyncWithHttpInfo
     *
     * Set the campaign&#39;s bid at the network.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request) && count($api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request when calling apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/source/bid';
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
        if (isset($api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_source_bid_put_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostWithHttpInfo($id, $body, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostWithHttpInfo
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostRequest($id, $body, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostAsync
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostAsync($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostAsyncWithHttpInfo($id, $body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostAsyncWithHttpInfo
     *
     * Retry the connection to the source campaign.
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostAsyncWithHttpInfo($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostRequest($id, $body, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'
     *
     * @param  int $id The tracking link id. (required)
     * @param  object|null $body (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPostRequest($id, $body = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost'
            );
        }



        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/source/retry';
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
        if (isset($body)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($body));
            } else {
                $httpBody = $body;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetWithHttpInfo
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetAsync
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetAsyncWithHttpInfo
     *
     * The campaign&#39;s placement targeting (all, or an allow-list).
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/targeting';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request api_v1_tracker_tracking_links_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutWithHttpInfo
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutAsync
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutAsync($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutAsyncWithHttpInfo
     *
     * Switch to / edit the allow-list, or back to all placements.
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutAsyncWithHttpInfo($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'
     *
     * @param  int $id The tracking link id. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest($id, $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_tracking_links_id_media_buying_targeting_put_request' is set
        if ($api_v1_tracker_tracking_links_id_media_buying_targeting_put_request === null || (is_array($api_v1_tracker_tracking_links_id_media_buying_targeting_put_request) && count($api_v1_tracker_tracking_links_id_media_buying_targeting_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request when calling apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/targeting';
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
        if (isset($api_v1_tracker_tracking_links_id_media_buying_targeting_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_tracking_links_id_media_buying_targeting_put_request));
            } else {
                $httpBody = $api_v1_tracker_tracking_links_id_media_buying_targeting_put_request;
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetWithHttpInfo
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetAsync
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo
     *
     * Suggested allow-list: the profitable placements of the policy window.
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/targeting/suggest';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetWithHttpInfo
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetRequest($id, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetAsync
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetAsyncWithHttpInfo
     *
     * Track record: did each change do what it was meant to?
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet'
            );
        }


        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/track-record';
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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingZonesGet
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingZonesGet($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'][0])
    {
        $this->apiV1TrackerTrackingLinksIdMediaBuyingZonesGetWithHttpInfo($id, $days, $revenue_metric, $contentType);
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingZonesGetWithHttpInfo
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingZonesGetWithHttpInfo($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'][0])
    {
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingZonesGetRequest($id, $days, $revenue_metric, $contentType);

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
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingZonesGetAsync
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingZonesGetAsync($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'][0])
    {
        return $this->apiV1TrackerTrackingLinksIdMediaBuyingZonesGetAsyncWithHttpInfo($id, $days, $revenue_metric, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerTrackingLinksIdMediaBuyingZonesGetAsyncWithHttpInfo
     *
     * Source-zone report: revenue against cost per placement.
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingZonesGetAsyncWithHttpInfo($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1TrackerTrackingLinksIdMediaBuyingZonesGetRequest($id, $days, $revenue_metric, $contentType);

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
     * Create request for operation 'apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'
     *
     * @param  int $id The tracking link id. (required)
     * @param  int|null $days Days to report (1-30); default the policy window. (optional)
     * @param  string|null $revenue_metric &#x60;paid&#x60; or &#x60;earned&#x60;; default the policy&#39;s. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerTrackingLinksIdMediaBuyingZonesGetRequest($id, $days = null, $revenue_metric = null, string $contentType = self::contentTypes['apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerTrackingLinksIdMediaBuyingZonesGet'
            );
        }




        $resourcePath = '/api/v1/tracker/tracking-links/{id}/media-buying/zones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $days,
            'days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $revenue_metric,
            'revenue_metric', // param base name
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

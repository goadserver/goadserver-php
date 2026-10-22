<?php
/**
 * URLPoolsApi
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
 * URLPoolsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class URLPoolsApi
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
        'apiV1UrlPoolsGet' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdClonePost' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdDelete' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdGet' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdPut' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdRecoverPost' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsGet' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsPost' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdClonePost' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdDelete' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdGet' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdPut' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdTogglePut' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUrlsUrlIdWeightPut' => [
            'application/json',
        ],
        'apiV1UrlPoolsIdUsedInGet' => [
            'application/json',
        ],
        'apiV1UrlPoolsPost' => [
            'application/json',
        ],
        'apiV1UrlPoolsReplaceUrlPut' => [
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
     * Operation apiV1UrlPoolsGet
     *
     * List URL pools.
     *
     * @param  string|null $select_stats 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativesGet200Response
     */
    public function apiV1UrlPoolsGet($select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_id = null, $filter_title = null, $filter_name = null, $state = 'live', $search = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1UrlPoolsGet'][0])
    {
        list($response) = $this->apiV1UrlPoolsGetWithHttpInfo($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir, $contentType);
        return $response;
    }

    /**
     * Operation apiV1UrlPoolsGetWithHttpInfo
     *
     * List URL pools.
     *
     * @param  string|null $select_stats 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsGetWithHttpInfo($select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_id = null, $filter_title = null, $filter_name = null, $state = 'live', $search = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1UrlPoolsGet'][0])
    {
        $request = $this->apiV1UrlPoolsGetRequest($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsGetAsync
     *
     * List URL pools.
     *
     * @param  string|null $select_stats 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsGetAsync($select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_id = null, $filter_title = null, $filter_name = null, $state = 'live', $search = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1UrlPoolsGet'][0])
    {
        return $this->apiV1UrlPoolsGetAsyncWithHttpInfo($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsGetAsyncWithHttpInfo
     *
     * List URL pools.
     *
     * @param  string|null $select_stats 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsGetAsyncWithHttpInfo($select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_id = null, $filter_title = null, $filter_name = null, $state = 'live', $search = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1UrlPoolsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativesGet200Response';
        $request = $this->apiV1UrlPoolsGetRequest($select_stats, $from, $to, $page, $per_page, $filter_id, $filter_title, $filter_name, $state, $search, $sort_by, $sort_dir, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsGet'
     *
     * @param  string|null $select_stats 1 &#x3D; add clicks per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsGetRequest($select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_id = null, $filter_title = null, $filter_name = null, $state = 'live', $search = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1UrlPoolsGet'][0])
    {





        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling URLPoolsApi.apiV1UrlPoolsGet, must be smaller than or equal to 100.');
        }
        








        $resourcePath = '/api/v1/url-pools';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $select_stats,
            'select_stats', // param base name
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
            $filter_id,
            'filter_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_title,
            'filter_title', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_name,
            'filter_name', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $state,
            'state', // param base name
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
     * Operation apiV1UrlPoolsIdClonePost
     *
     * Clone a URL pool with all its URLs.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
     */
    public function apiV1UrlPoolsIdClonePost($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdClonePost'][0])
    {
        list($response) = $this->apiV1UrlPoolsIdClonePostWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1UrlPoolsIdClonePostWithHttpInfo
     *
     * Clone a URL pool with all its URLs.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdClonePostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdClonePost'][0])
    {
        $request = $this->apiV1UrlPoolsIdClonePostRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsIdClonePostAsync
     *
     * Clone a URL pool with all its URLs.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdClonePostAsync($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdClonePost'][0])
    {
        return $this->apiV1UrlPoolsIdClonePostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdClonePostAsyncWithHttpInfo
     *
     * Clone a URL pool with all its URLs.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdClonePostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdClonePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1UrlPoolsIdClonePostRequest($id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdClonePost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdClonePostRequest($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdClonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdClonePost'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/clone';
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1UrlPoolsIdDelete
     *
     * Soft-delete a URL pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdDelete($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdDelete'][0])
    {
        $this->apiV1UrlPoolsIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdDeleteWithHttpInfo
     *
     * Soft-delete a URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdDelete'][0])
    {
        $request = $this->apiV1UrlPoolsIdDeleteRequest($id, $contentType);

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
     * Operation apiV1UrlPoolsIdDeleteAsync
     *
     * Soft-delete a URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdDelete'][0])
    {
        return $this->apiV1UrlPoolsIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete a URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdDelete'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}';
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
     * Operation apiV1UrlPoolsIdGet
     *
     * Show one URL pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdGet($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdGet'][0])
    {
        $this->apiV1UrlPoolsIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdGetWithHttpInfo
     *
     * Show one URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdGet'][0])
    {
        $request = $this->apiV1UrlPoolsIdGetRequest($id, $contentType);

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
     * Operation apiV1UrlPoolsIdGetAsync
     *
     * Show one URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdGetAsync($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdGet'][0])
    {
        return $this->apiV1UrlPoolsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdGetAsyncWithHttpInfo
     *
     * Show one URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdGetRequest($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdGet'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}';
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
     * Operation apiV1UrlPoolsIdPut
     *
     * Update URL pool meta.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest $api_v1_url_pools_id_put_request api_v1_url_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdPut($id, $api_v1_url_pools_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdPut'][0])
    {
        $this->apiV1UrlPoolsIdPutWithHttpInfo($id, $api_v1_url_pools_id_put_request, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdPutWithHttpInfo
     *
     * Update URL pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest $api_v1_url_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdPutWithHttpInfo($id, $api_v1_url_pools_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdPut'][0])
    {
        $request = $this->apiV1UrlPoolsIdPutRequest($id, $api_v1_url_pools_id_put_request, $contentType);

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
     * Operation apiV1UrlPoolsIdPutAsync
     *
     * Update URL pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest $api_v1_url_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdPutAsync($id, $api_v1_url_pools_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdPut'][0])
    {
        return $this->apiV1UrlPoolsIdPutAsyncWithHttpInfo($id, $api_v1_url_pools_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdPutAsyncWithHttpInfo
     *
     * Update URL pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest $api_v1_url_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdPutAsyncWithHttpInfo($id, $api_v1_url_pools_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdPutRequest($id, $api_v1_url_pools_id_put_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdPutRequest $api_v1_url_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdPutRequest($id, $api_v1_url_pools_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdPut'
            );
        }

        // verify the required parameter 'api_v1_url_pools_id_put_request' is set
        if ($api_v1_url_pools_id_put_request === null || (is_array($api_v1_url_pools_id_put_request) && count($api_v1_url_pools_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_id_put_request when calling apiV1UrlPoolsIdPut'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}';
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
            [],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_url_pools_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_id_put_request));
            } else {
                $httpBody = $api_v1_url_pools_id_put_request;
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
     * Operation apiV1UrlPoolsIdRecoverPost
     *
     * Restore an archived url pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdRecoverPost($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdRecoverPost'][0])
    {
        $this->apiV1UrlPoolsIdRecoverPostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdRecoverPostWithHttpInfo
     *
     * Restore an archived url pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdRecoverPostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdRecoverPost'][0])
    {
        $request = $this->apiV1UrlPoolsIdRecoverPostRequest($id, $contentType);

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
     * Operation apiV1UrlPoolsIdRecoverPostAsync
     *
     * Restore an archived url pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdRecoverPostAsync($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdRecoverPost'][0])
    {
        return $this->apiV1UrlPoolsIdRecoverPostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdRecoverPostAsyncWithHttpInfo
     *
     * Restore an archived url pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdRecoverPostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdRecoverPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdRecoverPostRequest($id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdRecoverPost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdRecoverPostRequest($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdRecoverPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdRecoverPost'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/recover';
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsGet
     *
     * List URLs in a pool.
     *
     * @param  int $id id (required)
     * @param  string|null $filter_url URL contains. (optional)
     * @param  int|null $filter_isactive filter_isactive (optional)
     * @param  string|null $search Matches URL, title or keywords. (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string|null $select_stats 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsGet($id, $filter_url = null, $filter_isactive = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, $select_stats = null, $from = null, $to = null, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsGet'][0])
    {
        $this->apiV1UrlPoolsIdUrlsGetWithHttpInfo($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsGetWithHttpInfo
     *
     * List URLs in a pool.
     *
     * @param  int $id (required)
     * @param  string|null $filter_url URL contains. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $search Matches URL, title or keywords. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $select_stats 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsGetWithHttpInfo($id, $filter_url = null, $filter_isactive = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, $select_stats = null, $from = null, $to = null, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsGet'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsGetRequest($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to, $contentType);

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
     * Operation apiV1UrlPoolsIdUrlsGetAsync
     *
     * List URLs in a pool.
     *
     * @param  int $id (required)
     * @param  string|null $filter_url URL contains. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $search Matches URL, title or keywords. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $select_stats 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsGetAsync($id, $filter_url = null, $filter_isactive = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, $select_stats = null, $from = null, $to = null, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsGet'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsGetAsyncWithHttpInfo($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsGetAsyncWithHttpInfo
     *
     * List URLs in a pool.
     *
     * @param  int $id (required)
     * @param  string|null $filter_url URL contains. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $search Matches URL, title or keywords. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $select_stats 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsGetAsyncWithHttpInfo($id, $filter_url = null, $filter_isactive = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, $select_stats = null, $from = null, $to = null, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsGetRequest($id, $filter_url, $filter_isactive, $search, $sort_by, $sort_dir, $page, $per_page, $select_stats, $from, $to, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsGet'
     *
     * @param  int $id (required)
     * @param  string|null $filter_url URL contains. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $search Matches URL, title or keywords. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $select_stats 1 &#x3D; add stats per URL for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsGetRequest($id, $filter_url = null, $filter_isactive = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, $select_stats = null, $from = null, $to = null, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsGet'
            );
        }







        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling URLPoolsApi.apiV1UrlPoolsIdUrlsGet, must be smaller than or equal to 100.');
        }
        




        $resourcePath = '/api/v1/url-pools/{id}/urls';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_url,
            'filter_url', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_isactive,
            'filter_isactive', // param base name
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
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $select_stats,
            'select_stats', // param base name
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
     * Operation apiV1UrlPoolsIdUrlsPost
     *
     * Add a URL to a pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest $api_v1_url_pools_id_urls_post_request api_v1_url_pools_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return |\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
     */
    public function apiV1UrlPoolsIdUrlsPost($id, $api_v1_url_pools_id_urls_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsPost'][0])
    {
        list($response) = $this->apiV1UrlPoolsIdUrlsPostWithHttpInfo($id, $api_v1_url_pools_id_urls_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsPostWithHttpInfo
     *
     * Add a URL to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest $api_v1_url_pools_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of |\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsPostWithHttpInfo($id, $api_v1_url_pools_id_urls_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsPost'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsPostRequest($id, $api_v1_url_pools_id_urls_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsPostAsync
     *
     * Add a URL to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest $api_v1_url_pools_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsPostAsync($id, $api_v1_url_pools_id_urls_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsPost'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsPostAsyncWithHttpInfo($id, $api_v1_url_pools_id_urls_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsPostAsyncWithHttpInfo
     *
     * Add a URL to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest $api_v1_url_pools_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsPostAsyncWithHttpInfo($id, $api_v1_url_pools_id_urls_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1UrlPoolsIdUrlsPostRequest($id, $api_v1_url_pools_id_urls_post_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsPostRequest $api_v1_url_pools_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsPostRequest($id, $api_v1_url_pools_id_urls_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsPost'
            );
        }

        // verify the required parameter 'api_v1_url_pools_id_urls_post_request' is set
        if ($api_v1_url_pools_id_urls_post_request === null || (is_array($api_v1_url_pools_id_urls_post_request) && count($api_v1_url_pools_id_urls_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_id_urls_post_request when calling apiV1UrlPoolsIdUrlsPost'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls';
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
        if (isset($api_v1_url_pools_id_urls_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_id_urls_post_request));
            } else {
                $httpBody = $api_v1_url_pools_id_urls_post_request;
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
     * Operation apiV1UrlPoolsIdUrlsUrlIdClonePost
     *
     * Clone a single URL within the same pool.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
     */
    public function apiV1UrlPoolsIdUrlsUrlIdClonePost($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'][0])
    {
        list($response) = $this->apiV1UrlPoolsIdUrlsUrlIdClonePostWithHttpInfo($id, $url_id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdClonePostWithHttpInfo
     *
     * Clone a single URL within the same pool.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdClonePostWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdClonePostRequest($id, $url_id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdClonePostAsync
     *
     * Clone a single URL within the same pool.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdClonePostAsync($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdClonePostAsyncWithHttpInfo($id, $url_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdClonePostAsyncWithHttpInfo
     *
     * Clone a single URL within the same pool.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdClonePostAsyncWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdClonePostRequest($id, $url_id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdClonePost'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdClonePostRequest($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdClonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdClonePost'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdClonePost'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}/clone';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
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
     * Operation apiV1UrlPoolsIdUrlsUrlIdDelete
     *
     * Soft-delete a single URL.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsUrlIdDelete($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'][0])
    {
        $this->apiV1UrlPoolsIdUrlsUrlIdDeleteWithHttpInfo($id, $url_id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdDeleteWithHttpInfo
     *
     * Soft-delete a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdDeleteWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdDeleteRequest($id, $url_id, $contentType);

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
     * Operation apiV1UrlPoolsIdUrlsUrlIdDeleteAsync
     *
     * Soft-delete a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdDeleteAsync($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdDeleteAsyncWithHttpInfo($id, $url_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdDeleteAsyncWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdDeleteRequest($id, $url_id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdDelete'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdDeleteRequest($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdDelete'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdDelete'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
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
     * Operation apiV1UrlPoolsIdUrlsUrlIdGet
     *
     * Show a single URL.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsUrlIdGet($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'][0])
    {
        $this->apiV1UrlPoolsIdUrlsUrlIdGetWithHttpInfo($id, $url_id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdGetWithHttpInfo
     *
     * Show a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdGetWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdGetRequest($id, $url_id, $contentType);

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
     * Operation apiV1UrlPoolsIdUrlsUrlIdGetAsync
     *
     * Show a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdGetAsync($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdGetAsyncWithHttpInfo($id, $url_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdGetAsyncWithHttpInfo
     *
     * Show a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdGetAsyncWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdGetRequest($id, $url_id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdGet'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdGetRequest($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdGet'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdGet'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
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
     * Operation apiV1UrlPoolsIdUrlsUrlIdPut
     *
     * Update a single URL.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest $api_v1_url_pools_id_urls_url_id_put_request api_v1_url_pools_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsUrlIdPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'][0])
    {
        $this->apiV1UrlPoolsIdUrlsUrlIdPutWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdPutWithHttpInfo
     *
     * Update a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest $api_v1_url_pools_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdPutWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, $contentType);

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
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPut409Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdPutAsync
     *
     * Update a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest $api_v1_url_pools_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdPutAsync($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdPutAsyncWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdPutAsyncWithHttpInfo
     *
     * Update a single URL.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest $api_v1_url_pools_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdPutAsyncWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdPut'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdPutRequest $api_v1_url_pools_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdPut'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdPut'
            );
        }

        // verify the required parameter 'api_v1_url_pools_id_urls_url_id_put_request' is set
        if ($api_v1_url_pools_id_urls_url_id_put_request === null || (is_array($api_v1_url_pools_id_urls_url_id_put_request) && count($api_v1_url_pools_id_urls_url_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_id_urls_url_id_put_request when calling apiV1UrlPoolsIdUrlsUrlIdPut'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_url_pools_id_urls_url_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_id_urls_url_id_put_request));
            } else {
                $httpBody = $api_v1_url_pools_id_urls_url_id_put_request;
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
     * Operation apiV1UrlPoolsIdUrlsUrlIdTogglePut
     *
     * Toggle a URL on / off.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsUrlIdTogglePut($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'][0])
    {
        $this->apiV1UrlPoolsIdUrlsUrlIdTogglePutWithHttpInfo($id, $url_id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdTogglePutWithHttpInfo
     *
     * Toggle a URL on / off.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdTogglePutWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdTogglePutRequest($id, $url_id, $contentType);

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
     * Operation apiV1UrlPoolsIdUrlsUrlIdTogglePutAsync
     *
     * Toggle a URL on / off.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdTogglePutAsync($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdTogglePutAsyncWithHttpInfo($id, $url_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdTogglePutAsyncWithHttpInfo
     *
     * Toggle a URL on / off.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdTogglePutAsyncWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdTogglePutRequest($id, $url_id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdTogglePut'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdTogglePutRequest($id, $url_id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdTogglePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdTogglePut'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdTogglePut'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}/toggle';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
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
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdWeightPut
     *
     * Set a URL&#39;s rotation weight.
     *
     * @param  int $id id (required)
     * @param  int $url_id url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest $api_v1_url_pools_id_urls_url_id_weight_put_request api_v1_url_pools_id_urls_url_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUrlsUrlIdWeightPut($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'][0])
    {
        $this->apiV1UrlPoolsIdUrlsUrlIdWeightPutWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdWeightPutWithHttpInfo
     *
     * Set a URL&#39;s rotation weight.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest $api_v1_url_pools_id_urls_url_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUrlsUrlIdWeightPutWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'][0])
    {
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdWeightPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, $contentType);

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
     * Operation apiV1UrlPoolsIdUrlsUrlIdWeightPutAsync
     *
     * Set a URL&#39;s rotation weight.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest $api_v1_url_pools_id_urls_url_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdWeightPutAsync($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'][0])
    {
        return $this->apiV1UrlPoolsIdUrlsUrlIdWeightPutAsyncWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUrlsUrlIdWeightPutAsyncWithHttpInfo
     *
     * Set a URL&#39;s rotation weight.
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest $api_v1_url_pools_id_urls_url_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUrlsUrlIdWeightPutAsyncWithHttpInfo($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUrlsUrlIdWeightPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUrlsUrlIdWeightPut'
     *
     * @param  int $id (required)
     * @param  int $url_id (required)
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest $api_v1_url_pools_id_urls_url_id_weight_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUrlsUrlIdWeightPutRequest($id, $url_id, $api_v1_url_pools_id_urls_url_id_weight_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsIdUrlsUrlIdWeightPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUrlsUrlIdWeightPut'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1UrlPoolsIdUrlsUrlIdWeightPut'
            );
        }

        // verify the required parameter 'api_v1_url_pools_id_urls_url_id_weight_put_request' is set
        if ($api_v1_url_pools_id_urls_url_id_weight_put_request === null || (is_array($api_v1_url_pools_id_urls_url_id_weight_put_request) && count($api_v1_url_pools_id_urls_url_id_weight_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_id_urls_url_id_weight_put_request when calling apiV1UrlPoolsIdUrlsUrlIdWeightPut'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/urls/{urlId}/weight';
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
        if ($url_id !== null) {
            $resourcePath = str_replace(
                '{' . 'urlId' . '}',
                ObjectSerializer::toPathValue($url_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_url_pools_id_urls_url_id_weight_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_id_urls_url_id_weight_put_request));
            } else {
                $httpBody = $api_v1_url_pools_id_urls_url_id_weight_put_request;
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
     * Operation apiV1UrlPoolsIdUsedInGet
     *
     * List campaigns / ads that reference this URL pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsIdUsedInGet($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUsedInGet'][0])
    {
        $this->apiV1UrlPoolsIdUsedInGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsIdUsedInGetWithHttpInfo
     *
     * List campaigns / ads that reference this URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsIdUsedInGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUsedInGet'][0])
    {
        $request = $this->apiV1UrlPoolsIdUsedInGetRequest($id, $contentType);

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
     * Operation apiV1UrlPoolsIdUsedInGetAsync
     *
     * List campaigns / ads that reference this URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUsedInGetAsync($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUsedInGet'][0])
    {
        return $this->apiV1UrlPoolsIdUsedInGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsIdUsedInGetAsyncWithHttpInfo
     *
     * List campaigns / ads that reference this URL pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsIdUsedInGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUsedInGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsIdUsedInGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsIdUsedInGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsIdUsedInGetRequest($id, string $contentType = self::contentTypes['apiV1UrlPoolsIdUsedInGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1UrlPoolsIdUsedInGet'
            );
        }


        $resourcePath = '/api/v1/url-pools/{id}/used-in';
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
     * Operation apiV1UrlPoolsPost
     *
     * Create a URL pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest $api_v1_url_pools_post_request api_v1_url_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
     */
    public function apiV1UrlPoolsPost($api_v1_url_pools_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsPost'][0])
    {
        list($response) = $this->apiV1UrlPoolsPostWithHttpInfo($api_v1_url_pools_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1UrlPoolsPostWithHttpInfo
     *
     * Create a URL pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest $api_v1_url_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsPostWithHttpInfo($api_v1_url_pools_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsPost'][0])
    {
        $request = $this->apiV1UrlPoolsPostRequest($api_v1_url_pools_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1UrlPoolsPostAsync
     *
     * Create a URL pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest $api_v1_url_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsPostAsync($api_v1_url_pools_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsPost'][0])
    {
        return $this->apiV1UrlPoolsPostAsyncWithHttpInfo($api_v1_url_pools_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsPostAsyncWithHttpInfo
     *
     * Create a URL pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest $api_v1_url_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsPostAsyncWithHttpInfo($api_v1_url_pools_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1UrlPoolsPostRequest($api_v1_url_pools_post_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsPostRequest $api_v1_url_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsPostRequest($api_v1_url_pools_post_request, string $contentType = self::contentTypes['apiV1UrlPoolsPost'][0])
    {

        // verify the required parameter 'api_v1_url_pools_post_request' is set
        if ($api_v1_url_pools_post_request === null || (is_array($api_v1_url_pools_post_request) && count($api_v1_url_pools_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_post_request when calling apiV1UrlPoolsPost'
            );
        }


        $resourcePath = '/api/v1/url-pools';
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
        if (isset($api_v1_url_pools_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_post_request));
            } else {
                $httpBody = $api_v1_url_pools_post_request;
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
     * Operation apiV1UrlPoolsReplaceUrlPut
     *
     * Search &amp; replace text across your URL-pool URLs.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest $api_v1_url_pools_replace_url_put_request api_v1_url_pools_replace_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsReplaceUrlPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1UrlPoolsReplaceUrlPut($api_v1_url_pools_replace_url_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsReplaceUrlPut'][0])
    {
        $this->apiV1UrlPoolsReplaceUrlPutWithHttpInfo($api_v1_url_pools_replace_url_put_request, $contentType);
    }

    /**
     * Operation apiV1UrlPoolsReplaceUrlPutWithHttpInfo
     *
     * Search &amp; replace text across your URL-pool URLs.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest $api_v1_url_pools_replace_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsReplaceUrlPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1UrlPoolsReplaceUrlPutWithHttpInfo($api_v1_url_pools_replace_url_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsReplaceUrlPut'][0])
    {
        $request = $this->apiV1UrlPoolsReplaceUrlPutRequest($api_v1_url_pools_replace_url_put_request, $contentType);

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
     * Operation apiV1UrlPoolsReplaceUrlPutAsync
     *
     * Search &amp; replace text across your URL-pool URLs.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest $api_v1_url_pools_replace_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsReplaceUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsReplaceUrlPutAsync($api_v1_url_pools_replace_url_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsReplaceUrlPut'][0])
    {
        return $this->apiV1UrlPoolsReplaceUrlPutAsyncWithHttpInfo($api_v1_url_pools_replace_url_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1UrlPoolsReplaceUrlPutAsyncWithHttpInfo
     *
     * Search &amp; replace text across your URL-pool URLs.
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest $api_v1_url_pools_replace_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsReplaceUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1UrlPoolsReplaceUrlPutAsyncWithHttpInfo($api_v1_url_pools_replace_url_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsReplaceUrlPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1UrlPoolsReplaceUrlPutRequest($api_v1_url_pools_replace_url_put_request, $contentType);

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
     * Create request for operation 'apiV1UrlPoolsReplaceUrlPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1UrlPoolsReplaceUrlPutRequest $api_v1_url_pools_replace_url_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1UrlPoolsReplaceUrlPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1UrlPoolsReplaceUrlPutRequest($api_v1_url_pools_replace_url_put_request, string $contentType = self::contentTypes['apiV1UrlPoolsReplaceUrlPut'][0])
    {

        // verify the required parameter 'api_v1_url_pools_replace_url_put_request' is set
        if ($api_v1_url_pools_replace_url_put_request === null || (is_array($api_v1_url_pools_replace_url_put_request) && count($api_v1_url_pools_replace_url_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_url_pools_replace_url_put_request when calling apiV1UrlPoolsReplaceUrlPut'
            );
        }


        $resourcePath = '/api/v1/url-pools/replace-url';
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
        if (isset($api_v1_url_pools_replace_url_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_url_pools_replace_url_put_request));
            } else {
                $httpBody = $api_v1_url_pools_replace_url_put_request;
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

<?php
/**
 * CleanupApi
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
 * CleanupApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class CleanupApi
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
        'apiV1CleanupAdvertiserArchivedGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserExportGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserLinkedGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserPurgePost' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserRunsGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserRunsIdGet' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserRunsIdUndonePost' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserRunsPost' => [
            'application/json',
        ],
        'apiV1CleanupAdvertiserSummaryGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherArchivedGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherExportGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherLinkedGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherPurgePost' => [
            'application/json',
        ],
        'apiV1CleanupPublisherRunsGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherRunsIdGet' => [
            'application/json',
        ],
        'apiV1CleanupPublisherRunsIdUndonePost' => [
            'application/json',
        ],
        'apiV1CleanupPublisherRunsPost' => [
            'application/json',
        ],
        'apiV1CleanupPublisherSummaryGet' => [
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
     * Operation apiV1CleanupAdvertiserArchivedGet
     *
     * Archived advertiser items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserArchivedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserArchivedGet(string $contentType = self::contentTypes['apiV1CleanupAdvertiserArchivedGet'][0])
    {
        $this->apiV1CleanupAdvertiserArchivedGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserArchivedGetWithHttpInfo
     *
     * Archived advertiser items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserArchivedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserArchivedGetWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserArchivedGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserArchivedGetRequest($contentType);

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
     * Operation apiV1CleanupAdvertiserArchivedGetAsync
     *
     * Archived advertiser items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserArchivedGetAsync(string $contentType = self::contentTypes['apiV1CleanupAdvertiserArchivedGet'][0])
    {
        return $this->apiV1CleanupAdvertiserArchivedGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserArchivedGetAsyncWithHttpInfo
     *
     * Archived advertiser items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserArchivedGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserArchivedGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserArchivedGetRequest($contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserArchivedGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserArchivedGetRequest(string $contentType = self::contentTypes['apiV1CleanupAdvertiserArchivedGet'][0])
    {


        $resourcePath = '/api/v1/cleanup/advertiser/archived';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





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
     * Operation apiV1CleanupAdvertiserExportGet
     *
     * Download the advertiser cleanup candidates.
     *
     * @param  string|null $format format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. (optional)
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserExportGet($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserExportGet'][0])
    {
        $this->apiV1CleanupAdvertiserExportGetWithHttpInfo($format, $kinds, $inactive_days, $never_served, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserExportGetWithHttpInfo
     *
     * Download the advertiser cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserExportGetWithHttpInfo($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserExportGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserExportGetRequest($format, $kinds, $inactive_days, $never_served, $contentType);

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
     * Operation apiV1CleanupAdvertiserExportGetAsync
     *
     * Download the advertiser cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserExportGetAsync($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserExportGet'][0])
    {
        return $this->apiV1CleanupAdvertiserExportGetAsyncWithHttpInfo($format, $kinds, $inactive_days, $never_served, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserExportGetAsyncWithHttpInfo
     *
     * Download the advertiser cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserExportGetAsyncWithHttpInfo($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserExportGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserExportGetRequest($format, $kinds, $inactive_days, $never_served, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserExportGet'
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: campaign,ad,creative,creative_pool,url_pool. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserExportGetRequest($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserExportGet'][0])
    {



        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserExportGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserExportGet, must be bigger than or equal to 0.');
        }
        


        $resourcePath = '/api/v1/cleanup/advertiser/export';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $kinds,
            'kinds', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




        $headers = $this->headerSelector->selectHeaders(
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/json', ],
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
     * Operation apiV1CleanupAdvertiserGet
     *
     * List advertiser items that no longer serve.
     *
     * @param  string|null $kind kind (optional)
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir dir (optional, default to 'asc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserGet($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupAdvertiserGet'][0])
    {
        $this->apiV1CleanupAdvertiserGetWithHttpInfo($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserGetWithHttpInfo
     *
     * List advertiser items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserGetWithHttpInfo($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupAdvertiserGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserGetRequest($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);

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
     * Operation apiV1CleanupAdvertiserGetAsync
     *
     * List advertiser items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserGetAsync($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupAdvertiserGet'][0])
    {
        return $this->apiV1CleanupAdvertiserGetAsyncWithHttpInfo($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserGetAsyncWithHttpInfo
     *
     * List advertiser items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserGetAsyncWithHttpInfo($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupAdvertiserGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserGetRequest($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserGet'
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserGetRequest($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupAdvertiserGet'][0])
    {


        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserGet, must be bigger than or equal to 0.');
        }
        




        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling CleanupApi.apiV1CleanupAdvertiserGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 1000) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CleanupApi.apiV1CleanupAdvertiserGet, must be smaller than or equal to 1000.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CleanupApi.apiV1CleanupAdvertiserGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/cleanup/advertiser';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $kind,
            'kind', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
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
            $sort,
            'sort', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dir,
            'dir', // param base name
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
     * Operation apiV1CleanupAdvertiserLinkedGet
     *
     * What still uses advertiser cleanup rows.
     *
     * @param  string $kind kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserLinkedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserLinkedGet($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupAdvertiserLinkedGet'][0])
    {
        $this->apiV1CleanupAdvertiserLinkedGetWithHttpInfo($kind, $ids, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserLinkedGetWithHttpInfo
     *
     * What still uses advertiser cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserLinkedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserLinkedGetWithHttpInfo($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupAdvertiserLinkedGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserLinkedGetRequest($kind, $ids, $contentType);

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
     * Operation apiV1CleanupAdvertiserLinkedGetAsync
     *
     * What still uses advertiser cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserLinkedGetAsync($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupAdvertiserLinkedGet'][0])
    {
        return $this->apiV1CleanupAdvertiserLinkedGetAsyncWithHttpInfo($kind, $ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserLinkedGetAsyncWithHttpInfo
     *
     * What still uses advertiser cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserLinkedGetAsyncWithHttpInfo($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupAdvertiserLinkedGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserLinkedGetRequest($kind, $ids, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserLinkedGet'
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserLinkedGetRequest($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupAdvertiserLinkedGet'][0])
    {

        // verify the required parameter 'kind' is set
        if ($kind === null || (is_array($kind) && count($kind) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $kind when calling apiV1CleanupAdvertiserLinkedGet'
            );
        }

        // verify the required parameter 'ids' is set
        if ($ids === null || (is_array($ids) && count($ids) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ids when calling apiV1CleanupAdvertiserLinkedGet'
            );
        }


        $resourcePath = '/api/v1/cleanup/advertiser/linked';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $kind,
            'kind', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ids,
            'ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
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
     * Operation apiV1CleanupAdvertiserPurgePost
     *
     * Purge archived advertiser items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserPurgePost(string $contentType = self::contentTypes['apiV1CleanupAdvertiserPurgePost'][0])
    {
        $this->apiV1CleanupAdvertiserPurgePostWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserPurgePostWithHttpInfo
     *
     * Purge archived advertiser items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserPurgePostWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserPurgePost'][0])
    {
        $request = $this->apiV1CleanupAdvertiserPurgePostRequest($contentType);

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
     * Operation apiV1CleanupAdvertiserPurgePostAsync
     *
     * Purge archived advertiser items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserPurgePostAsync(string $contentType = self::contentTypes['apiV1CleanupAdvertiserPurgePost'][0])
    {
        return $this->apiV1CleanupAdvertiserPurgePostAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserPurgePostAsyncWithHttpInfo
     *
     * Purge archived advertiser items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserPurgePostAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserPurgePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserPurgePostRequest($contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserPurgePost'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserPurgePostRequest(string $contentType = self::contentTypes['apiV1CleanupAdvertiserPurgePost'][0])
    {


        $resourcePath = '/api/v1/cleanup/advertiser/purge';
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
     * Operation apiV1CleanupAdvertiserRunsGet
     *
     * Recent advertiser cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserRunsGet(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsGet'][0])
    {
        $this->apiV1CleanupAdvertiserRunsGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsGetWithHttpInfo
     *
     * Recent advertiser cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserRunsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserRunsGetRequest($contentType);

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
     * Operation apiV1CleanupAdvertiserRunsGetAsync
     *
     * Recent advertiser cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsGetAsync(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsGet'][0])
    {
        return $this->apiV1CleanupAdvertiserRunsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsGetAsyncWithHttpInfo
     *
     * Recent advertiser cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserRunsGetRequest($contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserRunsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserRunsGetRequest(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsGet'][0])
    {


        $resourcePath = '/api/v1/cleanup/advertiser/runs';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





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
     * Operation apiV1CleanupAdvertiserRunsIdGet
     *
     * One advertiser cleanup run with its items.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserRunsIdGet($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'][0])
    {
        $this->apiV1CleanupAdvertiserRunsIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsIdGetWithHttpInfo
     *
     * One advertiser cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserRunsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserRunsIdGetRequest($id, $contentType);

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
     * Operation apiV1CleanupAdvertiserRunsIdGetAsync
     *
     * One advertiser cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsIdGetAsync($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'][0])
    {
        return $this->apiV1CleanupAdvertiserRunsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsIdGetAsyncWithHttpInfo
     *
     * One advertiser cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserRunsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserRunsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserRunsIdGetRequest($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CleanupAdvertiserRunsIdGet'
            );
        }


        $resourcePath = '/api/v1/cleanup/advertiser/runs/{id}';
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
     * Operation apiV1CleanupAdvertiserRunsIdUndonePost
     *
     * Mark a advertiser cleanup run as undone.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserRunsIdUndonePost($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'][0])
    {
        $this->apiV1CleanupAdvertiserRunsIdUndonePostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsIdUndonePostWithHttpInfo
     *
     * Mark a advertiser cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserRunsIdUndonePostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'][0])
    {
        $request = $this->apiV1CleanupAdvertiserRunsIdUndonePostRequest($id, $contentType);

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
     * Operation apiV1CleanupAdvertiserRunsIdUndonePostAsync
     *
     * Mark a advertiser cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsIdUndonePostAsync($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'][0])
    {
        return $this->apiV1CleanupAdvertiserRunsIdUndonePostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsIdUndonePostAsyncWithHttpInfo
     *
     * Mark a advertiser cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsIdUndonePostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserRunsIdUndonePostRequest($id, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserRunsIdUndonePost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserRunsIdUndonePostRequest($id, string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsIdUndonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CleanupAdvertiserRunsIdUndonePost'
            );
        }


        $resourcePath = '/api/v1/cleanup/advertiser/runs/{id}/undone';
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsPost
     *
     * Record a advertiser cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserRunsPost(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsPost'][0])
    {
        $this->apiV1CleanupAdvertiserRunsPostWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsPostWithHttpInfo
     *
     * Record a advertiser cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserRunsPostWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsPost'][0])
    {
        $request = $this->apiV1CleanupAdvertiserRunsPostRequest($contentType);

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
     * Operation apiV1CleanupAdvertiserRunsPostAsync
     *
     * Record a advertiser cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsPostAsync(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsPost'][0])
    {
        return $this->apiV1CleanupAdvertiserRunsPostAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserRunsPostAsyncWithHttpInfo
     *
     * Record a advertiser cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserRunsPostAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserRunsPostRequest($contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserRunsPost'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserRunsPostRequest(string $contentType = self::contentTypes['apiV1CleanupAdvertiserRunsPost'][0])
    {


        $resourcePath = '/api/v1/cleanup/advertiser/runs';
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
     * Operation apiV1CleanupAdvertiserSummaryGet
     *
     * Count the advertiser cleanup candidates per kind.
     *
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupAdvertiserSummaryGet($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserSummaryGet'][0])
    {
        $this->apiV1CleanupAdvertiserSummaryGetWithHttpInfo($inactive_days, $never_served, $contentType);
    }

    /**
     * Operation apiV1CleanupAdvertiserSummaryGetWithHttpInfo
     *
     * Count the advertiser cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupAdvertiserSummaryGetWithHttpInfo($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserSummaryGet'][0])
    {
        $request = $this->apiV1CleanupAdvertiserSummaryGetRequest($inactive_days, $never_served, $contentType);

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
     * Operation apiV1CleanupAdvertiserSummaryGetAsync
     *
     * Count the advertiser cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserSummaryGetAsync($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserSummaryGet'][0])
    {
        return $this->apiV1CleanupAdvertiserSummaryGetAsyncWithHttpInfo($inactive_days, $never_served, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupAdvertiserSummaryGetAsyncWithHttpInfo
     *
     * Count the advertiser cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupAdvertiserSummaryGetAsyncWithHttpInfo($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserSummaryGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupAdvertiserSummaryGetRequest($inactive_days, $never_served, $contentType);

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
     * Create request for operation 'apiV1CleanupAdvertiserSummaryGet'
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupAdvertiserSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupAdvertiserSummaryGetRequest($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupAdvertiserSummaryGet'][0])
    {

        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserSummaryGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupAdvertiserSummaryGet, must be bigger than or equal to 0.');
        }
        


        $resourcePath = '/api/v1/cleanup/advertiser/summary';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
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
     * Operation apiV1CleanupPublisherArchivedGet
     *
     * Archived publisher items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherArchivedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherArchivedGet(string $contentType = self::contentTypes['apiV1CleanupPublisherArchivedGet'][0])
    {
        $this->apiV1CleanupPublisherArchivedGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupPublisherArchivedGetWithHttpInfo
     *
     * Archived publisher items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherArchivedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherArchivedGetWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherArchivedGet'][0])
    {
        $request = $this->apiV1CleanupPublisherArchivedGetRequest($contentType);

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
     * Operation apiV1CleanupPublisherArchivedGetAsync
     *
     * Archived publisher items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherArchivedGetAsync(string $contentType = self::contentTypes['apiV1CleanupPublisherArchivedGet'][0])
    {
        return $this->apiV1CleanupPublisherArchivedGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherArchivedGetAsyncWithHttpInfo
     *
     * Archived publisher items that can be purged.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherArchivedGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherArchivedGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherArchivedGetRequest($contentType);

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
     * Create request for operation 'apiV1CleanupPublisherArchivedGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherArchivedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherArchivedGetRequest(string $contentType = self::contentTypes['apiV1CleanupPublisherArchivedGet'][0])
    {


        $resourcePath = '/api/v1/cleanup/publisher/archived';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





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
     * Operation apiV1CleanupPublisherExportGet
     *
     * Download the publisher cleanup candidates.
     *
     * @param  string|null $format format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: adzone,website. (optional)
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherExportGet($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherExportGet'][0])
    {
        $this->apiV1CleanupPublisherExportGetWithHttpInfo($format, $kinds, $inactive_days, $never_served, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherExportGetWithHttpInfo
     *
     * Download the publisher cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: adzone,website. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherExportGetWithHttpInfo($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherExportGet'][0])
    {
        $request = $this->apiV1CleanupPublisherExportGetRequest($format, $kinds, $inactive_days, $never_served, $contentType);

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
     * Operation apiV1CleanupPublisherExportGetAsync
     *
     * Download the publisher cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: adzone,website. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherExportGetAsync($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherExportGet'][0])
    {
        return $this->apiV1CleanupPublisherExportGetAsyncWithHttpInfo($format, $kinds, $inactive_days, $never_served, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherExportGetAsyncWithHttpInfo
     *
     * Download the publisher cleanup candidates.
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: adzone,website. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherExportGetAsyncWithHttpInfo($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherExportGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherExportGetRequest($format, $kinds, $inactive_days, $never_served, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherExportGet'
     *
     * @param  string|null $format (optional, default to 'xlsx')
     * @param  string|null $kinds Comma-separated subset of: adzone,website. (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherExportGetRequest($format = 'xlsx', $kinds = null, $inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherExportGet'][0])
    {



        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherExportGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherExportGet, must be bigger than or equal to 0.');
        }
        


        $resourcePath = '/api/v1/cleanup/publisher/export';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $kinds,
            'kinds', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




        $headers = $this->headerSelector->selectHeaders(
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/json', ],
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
     * Operation apiV1CleanupPublisherGet
     *
     * List publisher items that no longer serve.
     *
     * @param  string|null $kind kind (optional)
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir dir (optional, default to 'asc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherGet($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupPublisherGet'][0])
    {
        $this->apiV1CleanupPublisherGetWithHttpInfo($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherGetWithHttpInfo
     *
     * List publisher items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherGetWithHttpInfo($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupPublisherGet'][0])
    {
        $request = $this->apiV1CleanupPublisherGetRequest($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);

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
     * Operation apiV1CleanupPublisherGetAsync
     *
     * List publisher items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherGetAsync($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupPublisherGet'][0])
    {
        return $this->apiV1CleanupPublisherGetAsyncWithHttpInfo($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherGetAsyncWithHttpInfo
     *
     * List publisher items that no longer serve.
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherGetAsyncWithHttpInfo($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupPublisherGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherGetRequest($kind, $inactive_days, $never_served, $search, $sort, $dir, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherGet'
     *
     * @param  string|null $kind (optional)
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string|null $search Substring of the name. (optional)
     * @param  string|null $sort last_served ascending &#x3D; never served first, then the longest idle. (optional, default to 'last_served')
     * @param  string|null $dir (optional, default to 'asc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherGetRequest($kind = null, $inactive_days = 90, $never_served = false, $search = null, $sort = 'last_served', $dir = 'asc', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1CleanupPublisherGet'][0])
    {


        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherGet, must be bigger than or equal to 0.');
        }
        




        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling CleanupApi.apiV1CleanupPublisherGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 1000) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CleanupApi.apiV1CleanupPublisherGet, must be smaller than or equal to 1000.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CleanupApi.apiV1CleanupPublisherGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/cleanup/publisher';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $kind,
            'kind', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
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
            $sort,
            'sort', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dir,
            'dir', // param base name
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
     * Operation apiV1CleanupPublisherLinkedGet
     *
     * What still uses publisher cleanup rows.
     *
     * @param  string $kind kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherLinkedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherLinkedGet($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupPublisherLinkedGet'][0])
    {
        $this->apiV1CleanupPublisherLinkedGetWithHttpInfo($kind, $ids, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherLinkedGetWithHttpInfo
     *
     * What still uses publisher cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherLinkedGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherLinkedGetWithHttpInfo($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupPublisherLinkedGet'][0])
    {
        $request = $this->apiV1CleanupPublisherLinkedGetRequest($kind, $ids, $contentType);

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
     * Operation apiV1CleanupPublisherLinkedGetAsync
     *
     * What still uses publisher cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherLinkedGetAsync($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupPublisherLinkedGet'][0])
    {
        return $this->apiV1CleanupPublisherLinkedGetAsyncWithHttpInfo($kind, $ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherLinkedGetAsyncWithHttpInfo
     *
     * What still uses publisher cleanup rows.
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherLinkedGetAsyncWithHttpInfo($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupPublisherLinkedGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherLinkedGetRequest($kind, $ids, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherLinkedGet'
     *
     * @param  string $kind (required)
     * @param  string $ids Comma-separated row ids, 1 to 5000. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherLinkedGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherLinkedGetRequest($kind, $ids, string $contentType = self::contentTypes['apiV1CleanupPublisherLinkedGet'][0])
    {

        // verify the required parameter 'kind' is set
        if ($kind === null || (is_array($kind) && count($kind) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $kind when calling apiV1CleanupPublisherLinkedGet'
            );
        }

        // verify the required parameter 'ids' is set
        if ($ids === null || (is_array($ids) && count($ids) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ids when calling apiV1CleanupPublisherLinkedGet'
            );
        }


        $resourcePath = '/api/v1/cleanup/publisher/linked';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $kind,
            'kind', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ids,
            'ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
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
     * Operation apiV1CleanupPublisherPurgePost
     *
     * Purge archived publisher items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherPurgePost(string $contentType = self::contentTypes['apiV1CleanupPublisherPurgePost'][0])
    {
        $this->apiV1CleanupPublisherPurgePostWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupPublisherPurgePostWithHttpInfo
     *
     * Purge archived publisher items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherPurgePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherPurgePostWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherPurgePost'][0])
    {
        $request = $this->apiV1CleanupPublisherPurgePostRequest($contentType);

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
     * Operation apiV1CleanupPublisherPurgePostAsync
     *
     * Purge archived publisher items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherPurgePostAsync(string $contentType = self::contentTypes['apiV1CleanupPublisherPurgePost'][0])
    {
        return $this->apiV1CleanupPublisherPurgePostAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherPurgePostAsyncWithHttpInfo
     *
     * Purge archived publisher items for good.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherPurgePostAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherPurgePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherPurgePostRequest($contentType);

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
     * Create request for operation 'apiV1CleanupPublisherPurgePost'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherPurgePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherPurgePostRequest(string $contentType = self::contentTypes['apiV1CleanupPublisherPurgePost'][0])
    {


        $resourcePath = '/api/v1/cleanup/publisher/purge';
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
     * Operation apiV1CleanupPublisherRunsGet
     *
     * Recent publisher cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherRunsGet(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsGet'][0])
    {
        $this->apiV1CleanupPublisherRunsGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupPublisherRunsGetWithHttpInfo
     *
     * Recent publisher cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherRunsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsGet'][0])
    {
        $request = $this->apiV1CleanupPublisherRunsGetRequest($contentType);

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
     * Operation apiV1CleanupPublisherRunsGetAsync
     *
     * Recent publisher cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsGetAsync(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsGet'][0])
    {
        return $this->apiV1CleanupPublisherRunsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherRunsGetAsyncWithHttpInfo
     *
     * Recent publisher cleanup runs.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherRunsGetRequest($contentType);

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
     * Create request for operation 'apiV1CleanupPublisherRunsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherRunsGetRequest(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsGet'][0])
    {


        $resourcePath = '/api/v1/cleanup/publisher/runs';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





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
     * Operation apiV1CleanupPublisherRunsIdGet
     *
     * One publisher cleanup run with its items.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherRunsIdGet($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdGet'][0])
    {
        $this->apiV1CleanupPublisherRunsIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherRunsIdGetWithHttpInfo
     *
     * One publisher cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherRunsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdGet'][0])
    {
        $request = $this->apiV1CleanupPublisherRunsIdGetRequest($id, $contentType);

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
     * Operation apiV1CleanupPublisherRunsIdGetAsync
     *
     * One publisher cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsIdGetAsync($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdGet'][0])
    {
        return $this->apiV1CleanupPublisherRunsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherRunsIdGetAsyncWithHttpInfo
     *
     * One publisher cleanup run with its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherRunsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherRunsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherRunsIdGetRequest($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CleanupPublisherRunsIdGet'
            );
        }


        $resourcePath = '/api/v1/cleanup/publisher/runs/{id}';
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
     * Operation apiV1CleanupPublisherRunsIdUndonePost
     *
     * Mark a publisher cleanup run as undone.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherRunsIdUndonePost($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'][0])
    {
        $this->apiV1CleanupPublisherRunsIdUndonePostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherRunsIdUndonePostWithHttpInfo
     *
     * Mark a publisher cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherRunsIdUndonePostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'][0])
    {
        $request = $this->apiV1CleanupPublisherRunsIdUndonePostRequest($id, $contentType);

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
     * Operation apiV1CleanupPublisherRunsIdUndonePostAsync
     *
     * Mark a publisher cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsIdUndonePostAsync($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'][0])
    {
        return $this->apiV1CleanupPublisherRunsIdUndonePostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherRunsIdUndonePostAsyncWithHttpInfo
     *
     * Mark a publisher cleanup run as undone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsIdUndonePostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherRunsIdUndonePostRequest($id, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherRunsIdUndonePost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherRunsIdUndonePostRequest($id, string $contentType = self::contentTypes['apiV1CleanupPublisherRunsIdUndonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CleanupPublisherRunsIdUndonePost'
            );
        }


        $resourcePath = '/api/v1/cleanup/publisher/runs/{id}/undone';
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CleanupPublisherRunsPost
     *
     * Record a publisher cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherRunsPost(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsPost'][0])
    {
        $this->apiV1CleanupPublisherRunsPostWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1CleanupPublisherRunsPostWithHttpInfo
     *
     * Record a publisher cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherRunsPostWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsPost'][0])
    {
        $request = $this->apiV1CleanupPublisherRunsPostRequest($contentType);

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
     * Operation apiV1CleanupPublisherRunsPostAsync
     *
     * Record a publisher cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsPostAsync(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsPost'][0])
    {
        return $this->apiV1CleanupPublisherRunsPostAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherRunsPostAsyncWithHttpInfo
     *
     * Record a publisher cleanup run.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherRunsPostAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherRunsPostRequest($contentType);

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
     * Create request for operation 'apiV1CleanupPublisherRunsPost'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherRunsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherRunsPostRequest(string $contentType = self::contentTypes['apiV1CleanupPublisherRunsPost'][0])
    {


        $resourcePath = '/api/v1/cleanup/publisher/runs';
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
     * Operation apiV1CleanupPublisherSummaryGet
     *
     * Count the publisher cleanup candidates per kind.
     *
     * @param  int|null $inactive_days inactive_days (optional, default to 90)
     * @param  bool|null $never_served never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CleanupPublisherSummaryGet($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherSummaryGet'][0])
    {
        $this->apiV1CleanupPublisherSummaryGetWithHttpInfo($inactive_days, $never_served, $contentType);
    }

    /**
     * Operation apiV1CleanupPublisherSummaryGetWithHttpInfo
     *
     * Count the publisher cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CleanupPublisherSummaryGetWithHttpInfo($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherSummaryGet'][0])
    {
        $request = $this->apiV1CleanupPublisherSummaryGetRequest($inactive_days, $never_served, $contentType);

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
     * Operation apiV1CleanupPublisherSummaryGetAsync
     *
     * Count the publisher cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherSummaryGetAsync($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherSummaryGet'][0])
    {
        return $this->apiV1CleanupPublisherSummaryGetAsyncWithHttpInfo($inactive_days, $never_served, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CleanupPublisherSummaryGetAsyncWithHttpInfo
     *
     * Count the publisher cleanup candidates per kind.
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CleanupPublisherSummaryGetAsyncWithHttpInfo($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherSummaryGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CleanupPublisherSummaryGetRequest($inactive_days, $never_served, $contentType);

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
     * Create request for operation 'apiV1CleanupPublisherSummaryGet'
     *
     * @param  int|null $inactive_days (optional, default to 90)
     * @param  bool|null $never_served (optional, default to false)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CleanupPublisherSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CleanupPublisherSummaryGetRequest($inactive_days = 90, $never_served = false, string $contentType = self::contentTypes['apiV1CleanupPublisherSummaryGet'][0])
    {

        if ($inactive_days !== null && $inactive_days > 3650) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherSummaryGet, must be smaller than or equal to 3650.');
        }
        if ($inactive_days !== null && $inactive_days < 0) {
            throw new \InvalidArgumentException('invalid value for "$inactive_days" when calling CleanupApi.apiV1CleanupPublisherSummaryGet, must be bigger than or equal to 0.');
        }
        


        $resourcePath = '/api/v1/cleanup/publisher/summary';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $inactive_days,
            'inactive_days', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $never_served,
            'never_served', // param base name
            'boolean', // openApiType
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

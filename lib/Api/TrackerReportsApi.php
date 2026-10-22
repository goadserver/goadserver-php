<?php
/**
 * TrackerReportsApi
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
 * TrackerReportsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class TrackerReportsApi
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
        'apiV1TrackerCostSummaryGet' => [
            'application/json',
        ],
        'apiV1TrackerDailyStatsGet' => [
            'application/json',
        ],
        'apiV1TrackerInlineStatsGet' => [
            'application/json',
        ],
        'apiV1TrackerPlayerLtvGet' => [
            'application/json',
        ],
        'apiV1TrackerPostbacksStatsGet' => [
            'application/json',
        ],
        'apiV1TrackerRecentClicksGet' => [
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
     * Operation apiV1TrackerCostSummaryGet
     *
     * Spend against revenue.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerCostSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerCostSummaryGet($from = null, $to = null, string $contentType = self::contentTypes['apiV1TrackerCostSummaryGet'][0])
    {
        list($response) = $this->apiV1TrackerCostSummaryGetWithHttpInfo($from, $to, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerCostSummaryGetWithHttpInfo
     *
     * Spend against revenue.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerCostSummaryGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerCostSummaryGetWithHttpInfo($from = null, $to = null, string $contentType = self::contentTypes['apiV1TrackerCostSummaryGet'][0])
    {
        $request = $this->apiV1TrackerCostSummaryGetRequest($from, $to, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerCostSummaryGetAsync
     *
     * Spend against revenue.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerCostSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerCostSummaryGetAsync($from = null, $to = null, string $contentType = self::contentTypes['apiV1TrackerCostSummaryGet'][0])
    {
        return $this->apiV1TrackerCostSummaryGetAsyncWithHttpInfo($from, $to, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerCostSummaryGetAsyncWithHttpInfo
     *
     * Spend against revenue.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerCostSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerCostSummaryGetAsyncWithHttpInfo($from = null, $to = null, string $contentType = self::contentTypes['apiV1TrackerCostSummaryGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerCostSummaryGetRequest($from, $to, $contentType);

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
     * Create request for operation 'apiV1TrackerCostSummaryGet'
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerCostSummaryGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerCostSummaryGetRequest($from = null, $to = null, string $contentType = self::contentTypes['apiV1TrackerCostSummaryGet'][0])
    {




        $resourcePath = '/api/v1/tracker/cost-summary';
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
     * Operation apiV1TrackerDailyStatsGet
     *
     * Daily performance, filtered.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $offer_ids Comma-separated offer ids. (optional)
     * @param  string|null $link_ids Comma-separated tracking-link ids. (optional)
     * @param  string|null $source_ids Comma-separated source ids. (optional)
     * @param  string|null $countries Comma-separated country codes. (optional)
     * @param  string|null $device_ids Comma-separated device-type ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerDailyStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerDailyStatsGet($from = null, $to = null, $offer_ids = null, $link_ids = null, $source_ids = null, $countries = null, $device_ids = null, string $contentType = self::contentTypes['apiV1TrackerDailyStatsGet'][0])
    {
        list($response) = $this->apiV1TrackerDailyStatsGetWithHttpInfo($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerDailyStatsGetWithHttpInfo
     *
     * Daily performance, filtered.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $offer_ids Comma-separated offer ids. (optional)
     * @param  string|null $link_ids Comma-separated tracking-link ids. (optional)
     * @param  string|null $source_ids Comma-separated source ids. (optional)
     * @param  string|null $countries Comma-separated country codes. (optional)
     * @param  string|null $device_ids Comma-separated device-type ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerDailyStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerDailyStatsGetWithHttpInfo($from = null, $to = null, $offer_ids = null, $link_ids = null, $source_ids = null, $countries = null, $device_ids = null, string $contentType = self::contentTypes['apiV1TrackerDailyStatsGet'][0])
    {
        $request = $this->apiV1TrackerDailyStatsGetRequest($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerDailyStatsGetAsync
     *
     * Daily performance, filtered.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $offer_ids Comma-separated offer ids. (optional)
     * @param  string|null $link_ids Comma-separated tracking-link ids. (optional)
     * @param  string|null $source_ids Comma-separated source ids. (optional)
     * @param  string|null $countries Comma-separated country codes. (optional)
     * @param  string|null $device_ids Comma-separated device-type ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerDailyStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerDailyStatsGetAsync($from = null, $to = null, $offer_ids = null, $link_ids = null, $source_ids = null, $countries = null, $device_ids = null, string $contentType = self::contentTypes['apiV1TrackerDailyStatsGet'][0])
    {
        return $this->apiV1TrackerDailyStatsGetAsyncWithHttpInfo($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerDailyStatsGetAsyncWithHttpInfo
     *
     * Daily performance, filtered.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $offer_ids Comma-separated offer ids. (optional)
     * @param  string|null $link_ids Comma-separated tracking-link ids. (optional)
     * @param  string|null $source_ids Comma-separated source ids. (optional)
     * @param  string|null $countries Comma-separated country codes. (optional)
     * @param  string|null $device_ids Comma-separated device-type ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerDailyStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerDailyStatsGetAsyncWithHttpInfo($from = null, $to = null, $offer_ids = null, $link_ids = null, $source_ids = null, $countries = null, $device_ids = null, string $contentType = self::contentTypes['apiV1TrackerDailyStatsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerDailyStatsGetRequest($from, $to, $offer_ids, $link_ids, $source_ids, $countries, $device_ids, $contentType);

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
     * Create request for operation 'apiV1TrackerDailyStatsGet'
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $offer_ids Comma-separated offer ids. (optional)
     * @param  string|null $link_ids Comma-separated tracking-link ids. (optional)
     * @param  string|null $source_ids Comma-separated source ids. (optional)
     * @param  string|null $countries Comma-separated country codes. (optional)
     * @param  string|null $device_ids Comma-separated device-type ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerDailyStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerDailyStatsGetRequest($from = null, $to = null, $offer_ids = null, $link_ids = null, $source_ids = null, $countries = null, $device_ids = null, string $contentType = self::contentTypes['apiV1TrackerDailyStatsGet'][0])
    {









        $resourcePath = '/api/v1/tracker/daily-stats';
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
            $offer_ids,
            'offer_ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $link_ids,
            'link_ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $source_ids,
            'source_ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $countries,
            'countries', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $device_ids,
            'device_ids', // param base name
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
     * Operation apiV1TrackerInlineStatsGet
     *
     * Per-row performance for a listing.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $type Which listing to size. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerInlineStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerInlineStatsGet($from = null, $to = null, $type = null, string $contentType = self::contentTypes['apiV1TrackerInlineStatsGet'][0])
    {
        list($response) = $this->apiV1TrackerInlineStatsGetWithHttpInfo($from, $to, $type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerInlineStatsGetWithHttpInfo
     *
     * Per-row performance for a listing.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $type Which listing to size. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerInlineStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerInlineStatsGetWithHttpInfo($from = null, $to = null, $type = null, string $contentType = self::contentTypes['apiV1TrackerInlineStatsGet'][0])
    {
        $request = $this->apiV1TrackerInlineStatsGetRequest($from, $to, $type, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerInlineStatsGetAsync
     *
     * Per-row performance for a listing.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $type Which listing to size. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerInlineStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerInlineStatsGetAsync($from = null, $to = null, $type = null, string $contentType = self::contentTypes['apiV1TrackerInlineStatsGet'][0])
    {
        return $this->apiV1TrackerInlineStatsGetAsyncWithHttpInfo($from, $to, $type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerInlineStatsGetAsyncWithHttpInfo
     *
     * Per-row performance for a listing.
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $type Which listing to size. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerInlineStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerInlineStatsGetAsyncWithHttpInfo($from = null, $to = null, $type = null, string $contentType = self::contentTypes['apiV1TrackerInlineStatsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerInlineStatsGetRequest($from, $to, $type, $contentType);

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
     * Create request for operation 'apiV1TrackerInlineStatsGet'
     *
     * @param  string|null $from Start date, YYYY-MM-DD. (optional)
     * @param  string|null $to End date, YYYY-MM-DD. (optional)
     * @param  string|null $type Which listing to size. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerInlineStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerInlineStatsGetRequest($from = null, $to = null, $type = null, string $contentType = self::contentTypes['apiV1TrackerInlineStatsGet'][0])
    {





        $resourcePath = '/api/v1/tracker/inline-stats';
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
            $type,
            'type', // param base name
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
     * Operation apiV1TrackerPlayerLtvGet
     *
     * Lifetime value by cohort.
     *
     * @param  int|null $days Cohort window in days. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPlayerLtvGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerPlayerLtvGet($days = 30, string $contentType = self::contentTypes['apiV1TrackerPlayerLtvGet'][0])
    {
        list($response) = $this->apiV1TrackerPlayerLtvGetWithHttpInfo($days, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerPlayerLtvGetWithHttpInfo
     *
     * Lifetime value by cohort.
     *
     * @param  int|null $days Cohort window in days. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPlayerLtvGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerPlayerLtvGetWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1TrackerPlayerLtvGet'][0])
    {
        $request = $this->apiV1TrackerPlayerLtvGetRequest($days, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerPlayerLtvGetAsync
     *
     * Lifetime value by cohort.
     *
     * @param  int|null $days Cohort window in days. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPlayerLtvGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerPlayerLtvGetAsync($days = 30, string $contentType = self::contentTypes['apiV1TrackerPlayerLtvGet'][0])
    {
        return $this->apiV1TrackerPlayerLtvGetAsyncWithHttpInfo($days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerPlayerLtvGetAsyncWithHttpInfo
     *
     * Lifetime value by cohort.
     *
     * @param  int|null $days Cohort window in days. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPlayerLtvGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerPlayerLtvGetAsyncWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1TrackerPlayerLtvGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerPlayerLtvGetRequest($days, $contentType);

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
     * Create request for operation 'apiV1TrackerPlayerLtvGet'
     *
     * @param  int|null $days Cohort window in days. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPlayerLtvGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerPlayerLtvGetRequest($days = 30, string $contentType = self::contentTypes['apiV1TrackerPlayerLtvGet'][0])
    {



        $resourcePath = '/api/v1/tracker/player-ltv';
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
     * Operation apiV1TrackerPostbacksStatsGet
     *
     * Outbound postback firing log.
     *
     * @param  string|null $datestart Start date, YYYY-MM-DD. (optional)
     * @param  string|null $dateend End date, YYYY-MM-DD. (optional)
     * @param  int|null $siteid Narrow to one traffic source. (optional)
     * @param  string|null $conv_type Narrow to one conversion-type letter. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPostbacksStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerPostbacksStatsGet($datestart = null, $dateend = null, $siteid = null, $conv_type = null, string $contentType = self::contentTypes['apiV1TrackerPostbacksStatsGet'][0])
    {
        list($response) = $this->apiV1TrackerPostbacksStatsGetWithHttpInfo($datestart, $dateend, $siteid, $conv_type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerPostbacksStatsGetWithHttpInfo
     *
     * Outbound postback firing log.
     *
     * @param  string|null $datestart Start date, YYYY-MM-DD. (optional)
     * @param  string|null $dateend End date, YYYY-MM-DD. (optional)
     * @param  int|null $siteid Narrow to one traffic source. (optional)
     * @param  string|null $conv_type Narrow to one conversion-type letter. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPostbacksStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerPostbacksStatsGetWithHttpInfo($datestart = null, $dateend = null, $siteid = null, $conv_type = null, string $contentType = self::contentTypes['apiV1TrackerPostbacksStatsGet'][0])
    {
        $request = $this->apiV1TrackerPostbacksStatsGetRequest($datestart, $dateend, $siteid, $conv_type, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerPostbacksStatsGetAsync
     *
     * Outbound postback firing log.
     *
     * @param  string|null $datestart Start date, YYYY-MM-DD. (optional)
     * @param  string|null $dateend End date, YYYY-MM-DD. (optional)
     * @param  int|null $siteid Narrow to one traffic source. (optional)
     * @param  string|null $conv_type Narrow to one conversion-type letter. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPostbacksStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerPostbacksStatsGetAsync($datestart = null, $dateend = null, $siteid = null, $conv_type = null, string $contentType = self::contentTypes['apiV1TrackerPostbacksStatsGet'][0])
    {
        return $this->apiV1TrackerPostbacksStatsGetAsyncWithHttpInfo($datestart, $dateend, $siteid, $conv_type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerPostbacksStatsGetAsyncWithHttpInfo
     *
     * Outbound postback firing log.
     *
     * @param  string|null $datestart Start date, YYYY-MM-DD. (optional)
     * @param  string|null $dateend End date, YYYY-MM-DD. (optional)
     * @param  int|null $siteid Narrow to one traffic source. (optional)
     * @param  string|null $conv_type Narrow to one conversion-type letter. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPostbacksStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerPostbacksStatsGetAsyncWithHttpInfo($datestart = null, $dateend = null, $siteid = null, $conv_type = null, string $contentType = self::contentTypes['apiV1TrackerPostbacksStatsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerPostbacksStatsGetRequest($datestart, $dateend, $siteid, $conv_type, $contentType);

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
     * Create request for operation 'apiV1TrackerPostbacksStatsGet'
     *
     * @param  string|null $datestart Start date, YYYY-MM-DD. (optional)
     * @param  string|null $dateend End date, YYYY-MM-DD. (optional)
     * @param  int|null $siteid Narrow to one traffic source. (optional)
     * @param  string|null $conv_type Narrow to one conversion-type letter. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerPostbacksStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerPostbacksStatsGetRequest($datestart = null, $dateend = null, $siteid = null, $conv_type = null, string $contentType = self::contentTypes['apiV1TrackerPostbacksStatsGet'][0])
    {






        $resourcePath = '/api/v1/tracker/postbacks/stats';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $datestart,
            'datestart', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $dateend,
            'dateend', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $siteid,
            'siteid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $conv_type,
            'conv_type', // param base name
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
     * Operation apiV1TrackerRecentClicksGet
     *
     * The most recent clicks.
     *
     * @param  int|null $limit Rows to return. (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRecentClicksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerRecentClicksGet($limit = 100, string $contentType = self::contentTypes['apiV1TrackerRecentClicksGet'][0])
    {
        list($response) = $this->apiV1TrackerRecentClicksGetWithHttpInfo($limit, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRecentClicksGetWithHttpInfo
     *
     * The most recent clicks.
     *
     * @param  int|null $limit Rows to return. (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRecentClicksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRecentClicksGetWithHttpInfo($limit = 100, string $contentType = self::contentTypes['apiV1TrackerRecentClicksGet'][0])
    {
        $request = $this->apiV1TrackerRecentClicksGetRequest($limit, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerRecentClicksGetAsync
     *
     * The most recent clicks.
     *
     * @param  int|null $limit Rows to return. (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRecentClicksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRecentClicksGetAsync($limit = 100, string $contentType = self::contentTypes['apiV1TrackerRecentClicksGet'][0])
    {
        return $this->apiV1TrackerRecentClicksGetAsyncWithHttpInfo($limit, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRecentClicksGetAsyncWithHttpInfo
     *
     * The most recent clicks.
     *
     * @param  int|null $limit Rows to return. (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRecentClicksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRecentClicksGetAsyncWithHttpInfo($limit = 100, string $contentType = self::contentTypes['apiV1TrackerRecentClicksGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerRecentClicksGetRequest($limit, $contentType);

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
     * Create request for operation 'apiV1TrackerRecentClicksGet'
     *
     * @param  int|null $limit Rows to return. (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRecentClicksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRecentClicksGetRequest($limit = 100, string $contentType = self::contentTypes['apiV1TrackerRecentClicksGet'][0])
    {



        $resourcePath = '/api/v1/tracker/recent-clicks';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $limit,
            'limit', // param base name
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

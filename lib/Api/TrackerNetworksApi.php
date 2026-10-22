<?php
/**
 * TrackerNetworksApi
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
 * TrackerNetworksApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class TrackerNetworksApi
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
        'apiV1TrackerAdNetworksDeleteReportsPut' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksGet' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksIdCampaignsGet' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksIdGet' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksIdPut' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksPost' => [
            'application/json',
        ],
        'apiV1TrackerAdNetworksReportsGet' => [
            'application/json',
        ],
        'apiV1TrackerAffiliateNetworkTemplatesGet' => [
            'application/json',
        ],
        'apiV1TrackerAffiliateNetworksGet' => [
            'application/json',
        ],
        'apiV1TrackerAffiliateNetworksIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerAffiliateNetworksIdPut' => [
            'application/json',
        ],
        'apiV1TrackerAffiliateNetworksPost' => [
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
     * Operation apiV1TrackerAdNetworksDeleteReportsPut
     *
     * Delete pulled spend reports.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest $api_v1_tracker_ad_networks_delete_reports_put_request api_v1_tracker_ad_networks_delete_reports_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerAdNetworksDeleteReportsPut($api_v1_tracker_ad_networks_delete_reports_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksDeleteReportsPutWithHttpInfo($api_v1_tracker_ad_networks_delete_reports_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksDeleteReportsPutWithHttpInfo
     *
     * Delete pulled spend reports.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest $api_v1_tracker_ad_networks_delete_reports_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksDeleteReportsPutWithHttpInfo($api_v1_tracker_ad_networks_delete_reports_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'][0])
    {
        $request = $this->apiV1TrackerAdNetworksDeleteReportsPutRequest($api_v1_tracker_ad_networks_delete_reports_put_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAdNetworksDeleteReportsPutAsync
     *
     * Delete pulled spend reports.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest $api_v1_tracker_ad_networks_delete_reports_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksDeleteReportsPutAsync($api_v1_tracker_ad_networks_delete_reports_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'][0])
    {
        return $this->apiV1TrackerAdNetworksDeleteReportsPutAsyncWithHttpInfo($api_v1_tracker_ad_networks_delete_reports_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksDeleteReportsPutAsyncWithHttpInfo
     *
     * Delete pulled spend reports.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest $api_v1_tracker_ad_networks_delete_reports_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksDeleteReportsPutAsyncWithHttpInfo($api_v1_tracker_ad_networks_delete_reports_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerAdNetworksDeleteReportsPutRequest($api_v1_tracker_ad_networks_delete_reports_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksDeleteReportsPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest $api_v1_tracker_ad_networks_delete_reports_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksDeleteReportsPutRequest($api_v1_tracker_ad_networks_delete_reports_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksDeleteReportsPut'][0])
    {

        // verify the required parameter 'api_v1_tracker_ad_networks_delete_reports_put_request' is set
        if ($api_v1_tracker_ad_networks_delete_reports_put_request === null || (is_array($api_v1_tracker_ad_networks_delete_reports_put_request) && count($api_v1_tracker_ad_networks_delete_reports_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_ad_networks_delete_reports_put_request when calling apiV1TrackerAdNetworksDeleteReportsPut'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks/delete-reports';
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
        if (isset($api_v1_tracker_ad_networks_delete_reports_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_ad_networks_delete_reports_put_request));
            } else {
                $httpBody = $api_v1_tracker_ad_networks_delete_reports_put_request;
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
     * Operation apiV1TrackerAdNetworksGet
     *
     * List ad networks.
     *
     * @param  string|null $status status (optional)
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerAdNetworksGet($status = null, $filter_id = null, $filter_name = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksGet'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksGetWithHttpInfo($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksGetWithHttpInfo
     *
     * List ad networks.
     *
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksGetWithHttpInfo($status = null, $filter_id = null, $filter_name = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksGet'][0])
    {
        $request = $this->apiV1TrackerAdNetworksGetRequest($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
     * Operation apiV1TrackerAdNetworksGetAsync
     *
     * List ad networks.
     *
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksGetAsync($status = null, $filter_id = null, $filter_name = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksGet'][0])
    {
        return $this->apiV1TrackerAdNetworksGetAsyncWithHttpInfo($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksGetAsyncWithHttpInfo
     *
     * List ad networks.
     *
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksGetAsyncWithHttpInfo($status = null, $filter_id = null, $filter_name = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerAdNetworksGetRequest($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksGet'
     *
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksGetRequest($status = null, $filter_id = null, $filter_name = null, $sort_by = 'id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksGet'][0])
    {







        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling TrackerNetworksApi.apiV1TrackerAdNetworksGet, must be smaller than or equal to 100.');
        }
        

        $resourcePath = '/api/v1/tracker/ad-networks';
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
            $filter_id,
            'filter_id', // param base name
            'integer', // openApiType
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
     * Operation apiV1TrackerAdNetworksIdCampaignsGet
     *
     * Campaigns known on one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerAdNetworksIdCampaignsGet($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksIdCampaignsGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksIdCampaignsGetWithHttpInfo
     *
     * Campaigns known on one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksIdCampaignsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'][0])
    {
        $request = $this->apiV1TrackerAdNetworksIdCampaignsGetRequest($id, $contentType);

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
     * Operation apiV1TrackerAdNetworksIdCampaignsGetAsync
     *
     * Campaigns known on one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdCampaignsGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'][0])
    {
        return $this->apiV1TrackerAdNetworksIdCampaignsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksIdCampaignsGetAsyncWithHttpInfo
     *
     * Campaigns known on one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdCampaignsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerAdNetworksIdCampaignsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksIdCampaignsGet'
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksIdCampaignsGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdCampaignsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAdNetworksIdCampaignsGet'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks/{id}/campaigns';
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
     * Operation apiV1TrackerAdNetworksIdDelete
     *
     * Delete an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerAdNetworksIdDelete($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksIdDeleteWithHttpInfo
     *
     * Delete an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdDelete'][0])
    {
        $request = $this->apiV1TrackerAdNetworksIdDeleteRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAdNetworksIdDeleteAsync
     *
     * Delete an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdDelete'][0])
    {
        return $this->apiV1TrackerAdNetworksIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksIdDeleteAsyncWithHttpInfo
     *
     * Delete an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerAdNetworksIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksIdDelete'
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAdNetworksIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks/{id}';
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerAdNetworksIdGet
     *
     * Get one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerAdNetworksIdGet($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdGet'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksIdGetWithHttpInfo
     *
     * Get one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdGet'][0])
    {
        $request = $this->apiV1TrackerAdNetworksIdGetRequest($id, $contentType);

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
     * Operation apiV1TrackerAdNetworksIdGetAsync
     *
     * Get one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdGet'][0])
    {
        return $this->apiV1TrackerAdNetworksIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksIdGetAsyncWithHttpInfo
     *
     * Get one ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerAdNetworksIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksIdGet'
     *
     * @param  int $id Ad network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksIdGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAdNetworksIdGet'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks/{id}';
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
     * Operation apiV1TrackerAdNetworksIdPut
     *
     * Update an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest $api_v1_tracker_ad_networks_id_put_request api_v1_tracker_ad_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerAdNetworksIdPut($id, $api_v1_tracker_ad_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdPut'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksIdPutWithHttpInfo($id, $api_v1_tracker_ad_networks_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksIdPutWithHttpInfo
     *
     * Update an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest $api_v1_tracker_ad_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksIdPutWithHttpInfo($id, $api_v1_tracker_ad_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdPut'][0])
    {
        $request = $this->apiV1TrackerAdNetworksIdPutRequest($id, $api_v1_tracker_ad_networks_id_put_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAdNetworksIdPutAsync
     *
     * Update an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest $api_v1_tracker_ad_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdPutAsync($id, $api_v1_tracker_ad_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdPut'][0])
    {
        return $this->apiV1TrackerAdNetworksIdPutAsyncWithHttpInfo($id, $api_v1_tracker_ad_networks_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksIdPutAsyncWithHttpInfo
     *
     * Update an ad network.
     *
     * @param  int $id Ad network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest $api_v1_tracker_ad_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksIdPutAsyncWithHttpInfo($id, $api_v1_tracker_ad_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerAdNetworksIdPutRequest($id, $api_v1_tracker_ad_networks_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksIdPut'
     *
     * @param  int $id Ad network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest $api_v1_tracker_ad_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksIdPutRequest($id, $api_v1_tracker_ad_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAdNetworksIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_ad_networks_id_put_request' is set
        if ($api_v1_tracker_ad_networks_id_put_request === null || (is_array($api_v1_tracker_ad_networks_id_put_request) && count($api_v1_tracker_ad_networks_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_ad_networks_id_put_request when calling apiV1TrackerAdNetworksIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks/{id}';
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
        if (isset($api_v1_tracker_ad_networks_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_ad_networks_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_ad_networks_id_put_request;
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
     * Operation apiV1TrackerAdNetworksPost
     *
     * Create an ad network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest $api_v1_tracker_ad_networks_post_request api_v1_tracker_ad_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerAdNetworksPost($api_v1_tracker_ad_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksPost'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksPostWithHttpInfo($api_v1_tracker_ad_networks_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksPostWithHttpInfo
     *
     * Create an ad network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest $api_v1_tracker_ad_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksPostWithHttpInfo($api_v1_tracker_ad_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksPost'][0])
    {
        $request = $this->apiV1TrackerAdNetworksPostRequest($api_v1_tracker_ad_networks_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAdNetworksPostAsync
     *
     * Create an ad network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest $api_v1_tracker_ad_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksPostAsync($api_v1_tracker_ad_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksPost'][0])
    {
        return $this->apiV1TrackerAdNetworksPostAsyncWithHttpInfo($api_v1_tracker_ad_networks_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksPostAsyncWithHttpInfo
     *
     * Create an ad network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest $api_v1_tracker_ad_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksPostAsyncWithHttpInfo($api_v1_tracker_ad_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerAdNetworksPostRequest($api_v1_tracker_ad_networks_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest $api_v1_tracker_ad_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksPostRequest($api_v1_tracker_ad_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAdNetworksPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_ad_networks_post_request' is set
        if ($api_v1_tracker_ad_networks_post_request === null || (is_array($api_v1_tracker_ad_networks_post_request) && count($api_v1_tracker_ad_networks_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_ad_networks_post_request when calling apiV1TrackerAdNetworksPost'
            );
        }


        $resourcePath = '/api/v1/tracker/ad-networks';
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
        if (isset($api_v1_tracker_ad_networks_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_ad_networks_post_request));
            } else {
                $httpBody = $api_v1_tracker_ad_networks_post_request;
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
     * Operation apiV1TrackerAdNetworksReportsGet
     *
     * Spend reports pulled from the ad networks.
     *
     * @param  int|null $filter_id filter_id (optional)
     * @param  int|null $filter_ad_networkid Reports for one ad network. (optional)
     * @param  int|null $filter_state filter_state (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'r.id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksReportsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerAdNetworksReportsGet($filter_id = null, $filter_ad_networkid = null, $filter_state = null, $sort_by = 'r.id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksReportsGet'][0])
    {
        list($response) = $this->apiV1TrackerAdNetworksReportsGetWithHttpInfo($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAdNetworksReportsGetWithHttpInfo
     *
     * Spend reports pulled from the ad networks.
     *
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ad_networkid Reports for one ad network. (optional)
     * @param  int|null $filter_state (optional)
     * @param  string|null $sort_by (optional, default to 'r.id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksReportsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAdNetworksReportsGetWithHttpInfo($filter_id = null, $filter_ad_networkid = null, $filter_state = null, $sort_by = 'r.id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksReportsGet'][0])
    {
        $request = $this->apiV1TrackerAdNetworksReportsGetRequest($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
     * Operation apiV1TrackerAdNetworksReportsGetAsync
     *
     * Spend reports pulled from the ad networks.
     *
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ad_networkid Reports for one ad network. (optional)
     * @param  int|null $filter_state (optional)
     * @param  string|null $sort_by (optional, default to 'r.id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksReportsGetAsync($filter_id = null, $filter_ad_networkid = null, $filter_state = null, $sort_by = 'r.id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksReportsGet'][0])
    {
        return $this->apiV1TrackerAdNetworksReportsGetAsyncWithHttpInfo($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAdNetworksReportsGetAsyncWithHttpInfo
     *
     * Spend reports pulled from the ad networks.
     *
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ad_networkid Reports for one ad network. (optional)
     * @param  int|null $filter_state (optional)
     * @param  string|null $sort_by (optional, default to 'r.id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAdNetworksReportsGetAsyncWithHttpInfo($filter_id = null, $filter_ad_networkid = null, $filter_state = null, $sort_by = 'r.id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksReportsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerAdNetworksReportsGetRequest($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1TrackerAdNetworksReportsGet'
     *
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ad_networkid Reports for one ad network. (optional)
     * @param  int|null $filter_state (optional)
     * @param  string|null $sort_by (optional, default to 'r.id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAdNetworksReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAdNetworksReportsGetRequest($filter_id = null, $filter_ad_networkid = null, $filter_state = null, $sort_by = 'r.id', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1TrackerAdNetworksReportsGet'][0])
    {







        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling TrackerNetworksApi.apiV1TrackerAdNetworksReportsGet, must be smaller than or equal to 100.');
        }
        

        $resourcePath = '/api/v1/tracker/ad-networks/reports';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $filter_ad_networkid,
            'filter_ad_networkid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_state,
            'filter_state', // param base name
            'integer', // openApiType
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
     * Operation apiV1TrackerAffiliateNetworkTemplatesGet
     *
     * List affiliate-network templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerAffiliateNetworkTemplatesGet(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'][0])
    {
        list($response) = $this->apiV1TrackerAffiliateNetworkTemplatesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAffiliateNetworkTemplatesGetWithHttpInfo
     *
     * List affiliate-network templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAffiliateNetworkTemplatesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'][0])
    {
        $request = $this->apiV1TrackerAffiliateNetworkTemplatesGetRequest($contentType);

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
     * Operation apiV1TrackerAffiliateNetworkTemplatesGetAsync
     *
     * List affiliate-network templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworkTemplatesGetAsync(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'][0])
    {
        return $this->apiV1TrackerAffiliateNetworkTemplatesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworkTemplatesGetAsyncWithHttpInfo
     *
     * List affiliate-network templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworkTemplatesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerAffiliateNetworkTemplatesGetRequest($contentType);

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
     * Create request for operation 'apiV1TrackerAffiliateNetworkTemplatesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAffiliateNetworkTemplatesGetRequest(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworkTemplatesGet'][0])
    {


        $resourcePath = '/api/v1/tracker/affiliate-network-templates';
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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksGet
     *
     * List affiliate networks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response
     */
    public function apiV1TrackerAffiliateNetworksGet(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksGet'][0])
    {
        list($response) = $this->apiV1TrackerAffiliateNetworksGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksGetWithHttpInfo
     *
     * List affiliate networks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAffiliateNetworksGetWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksGet'][0])
    {
        $request = $this->apiV1TrackerAffiliateNetworksGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksGetAsync
     *
     * List affiliate networks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksGetAsync(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksGet'][0])
    {
        return $this->apiV1TrackerAffiliateNetworksGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksGetAsyncWithHttpInfo
     *
     * List affiliate networks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response';
        $request = $this->apiV1TrackerAffiliateNetworksGetRequest($contentType);

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
     * Create request for operation 'apiV1TrackerAffiliateNetworksGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAffiliateNetworksGetRequest(string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksGet'][0])
    {


        $resourcePath = '/api/v1/tracker/affiliate-networks';
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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdDelete
     *
     * Delete an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerAffiliateNetworksIdDelete($id, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerAffiliateNetworksIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdDeleteWithHttpInfo
     *
     * Delete an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAffiliateNetworksIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'][0])
    {
        $request = $this->apiV1TrackerAffiliateNetworksIdDeleteRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdDeleteAsync
     *
     * Delete an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'][0])
    {
        return $this->apiV1TrackerAffiliateNetworksIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdDeleteAsyncWithHttpInfo
     *
     * Delete an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerAffiliateNetworksIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerAffiliateNetworksIdDelete'
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAffiliateNetworksIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAffiliateNetworksIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/affiliate-networks/{id}';
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdPut
     *
     * Update an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest $api_v1_tracker_affiliate_networks_id_put_request api_v1_tracker_affiliate_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerAffiliateNetworksIdPut($id, $api_v1_tracker_affiliate_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'][0])
    {
        list($response) = $this->apiV1TrackerAffiliateNetworksIdPutWithHttpInfo($id, $api_v1_tracker_affiliate_networks_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdPutWithHttpInfo
     *
     * Update an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest $api_v1_tracker_affiliate_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAffiliateNetworksIdPutWithHttpInfo($id, $api_v1_tracker_affiliate_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'][0])
    {
        $request = $this->apiV1TrackerAffiliateNetworksIdPutRequest($id, $api_v1_tracker_affiliate_networks_id_put_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdPutAsync
     *
     * Update an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest $api_v1_tracker_affiliate_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksIdPutAsync($id, $api_v1_tracker_affiliate_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'][0])
    {
        return $this->apiV1TrackerAffiliateNetworksIdPutAsyncWithHttpInfo($id, $api_v1_tracker_affiliate_networks_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksIdPutAsyncWithHttpInfo
     *
     * Update an affiliate network.
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest $api_v1_tracker_affiliate_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksIdPutAsyncWithHttpInfo($id, $api_v1_tracker_affiliate_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerAffiliateNetworksIdPutRequest($id, $api_v1_tracker_affiliate_networks_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerAffiliateNetworksIdPut'
     *
     * @param  int $id Affiliate network ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest $api_v1_tracker_affiliate_networks_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAffiliateNetworksIdPutRequest($id, $api_v1_tracker_affiliate_networks_id_put_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerAffiliateNetworksIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_affiliate_networks_id_put_request' is set
        if ($api_v1_tracker_affiliate_networks_id_put_request === null || (is_array($api_v1_tracker_affiliate_networks_id_put_request) && count($api_v1_tracker_affiliate_networks_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_affiliate_networks_id_put_request when calling apiV1TrackerAffiliateNetworksIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/affiliate-networks/{id}';
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
        if (isset($api_v1_tracker_affiliate_networks_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_affiliate_networks_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_affiliate_networks_id_put_request;
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
     * Operation apiV1TrackerAffiliateNetworksPost
     *
     * Create an affiliate network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest $api_v1_tracker_affiliate_networks_post_request api_v1_tracker_affiliate_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerAffiliateNetworksPost($api_v1_tracker_affiliate_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksPost'][0])
    {
        list($response) = $this->apiV1TrackerAffiliateNetworksPostWithHttpInfo($api_v1_tracker_affiliate_networks_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksPostWithHttpInfo
     *
     * Create an affiliate network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest $api_v1_tracker_affiliate_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerAffiliateNetworksPostWithHttpInfo($api_v1_tracker_affiliate_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksPost'][0])
    {
        $request = $this->apiV1TrackerAffiliateNetworksPostRequest($api_v1_tracker_affiliate_networks_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksPostAsync
     *
     * Create an affiliate network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest $api_v1_tracker_affiliate_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksPostAsync($api_v1_tracker_affiliate_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksPost'][0])
    {
        return $this->apiV1TrackerAffiliateNetworksPostAsyncWithHttpInfo($api_v1_tracker_affiliate_networks_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerAffiliateNetworksPostAsyncWithHttpInfo
     *
     * Create an affiliate network.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest $api_v1_tracker_affiliate_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerAffiliateNetworksPostAsyncWithHttpInfo($api_v1_tracker_affiliate_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerAffiliateNetworksPostRequest($api_v1_tracker_affiliate_networks_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerAffiliateNetworksPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest $api_v1_tracker_affiliate_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerAffiliateNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerAffiliateNetworksPostRequest($api_v1_tracker_affiliate_networks_post_request, string $contentType = self::contentTypes['apiV1TrackerAffiliateNetworksPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_affiliate_networks_post_request' is set
        if ($api_v1_tracker_affiliate_networks_post_request === null || (is_array($api_v1_tracker_affiliate_networks_post_request) && count($api_v1_tracker_affiliate_networks_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_affiliate_networks_post_request when calling apiV1TrackerAffiliateNetworksPost'
            );
        }


        $resourcePath = '/api/v1/tracker/affiliate-networks';
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
        if (isset($api_v1_tracker_affiliate_networks_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_affiliate_networks_post_request));
            } else {
                $httpBody = $api_v1_tracker_affiliate_networks_post_request;
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

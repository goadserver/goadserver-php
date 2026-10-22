<?php
/**
 * AdScannerApi
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
 * AdScannerApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class AdScannerApi
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
        'apiV1AdScannerGet' => [
            'application/json',
        ],
        'apiV1AdScannerIdDelete' => [
            'application/json',
        ],
        'apiV1AdScannerIdIgnoreDomainPut' => [
            'application/json',
        ],
        'apiV1AdScannerIdManualItemsGet' => [
            'application/json',
        ],
        'apiV1AdScannerIdManualItemsItemIdStatePut' => [
            'application/json',
        ],
        'apiV1AdScannerIdManualItemsPost' => [
            'application/json',
        ],
        'apiV1AdScannerIdPut' => [
            'application/json',
        ],
        'apiV1AdScannerIdToggleActivePut' => [
            'application/json',
        ],
        'apiV1AdScannerPost' => [
            'application/json',
        ],
        'apiV1AdScannerReportHashGet' => [
            'application/json',
        ],
        'apiV1AdScannerReportsGet' => [
            'application/json',
        ],
        'apiV1AdScannerStatsGet' => [
            'application/json',
        ],
        'apiV1AdScannerTagsGet' => [
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
     * Operation apiV1AdScannerGet
     *
     * List scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerGet(string $contentType = self::contentTypes['apiV1AdScannerGet'][0])
    {
        $this->apiV1AdScannerGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1AdScannerGetWithHttpInfo
     *
     * List scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AdScannerGet'][0])
    {
        $request = $this->apiV1AdScannerGetRequest($contentType);

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
     * Operation apiV1AdScannerGetAsync
     *
     * List scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerGetAsync(string $contentType = self::contentTypes['apiV1AdScannerGet'][0])
    {
        return $this->apiV1AdScannerGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerGetAsyncWithHttpInfo
     *
     * List scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AdScannerGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerGetRequest($contentType);

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
     * Create request for operation 'apiV1AdScannerGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerGetRequest(string $contentType = self::contentTypes['apiV1AdScannerGet'][0])
    {


        $resourcePath = '/api/v1/ad-scanner';
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
     * Operation apiV1AdScannerIdDelete
     *
     * Delete a scan task.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdDelete($id, string $contentType = self::contentTypes['apiV1AdScannerIdDelete'][0])
    {
        $this->apiV1AdScannerIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdDeleteWithHttpInfo
     *
     * Delete a scan task.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdScannerIdDelete'][0])
    {
        $request = $this->apiV1AdScannerIdDeleteRequest($id, $contentType);

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
     * Operation apiV1AdScannerIdDeleteAsync
     *
     * Delete a scan task.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1AdScannerIdDelete'][0])
    {
        return $this->apiV1AdScannerIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdDeleteAsyncWithHttpInfo
     *
     * Delete a scan task.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdScannerIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1AdScannerIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdDelete'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}';
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
     * Operation apiV1AdScannerIdIgnoreDomainPut
     *
     * Ignore (or un-ignore) a flagged domain on a task.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest $api_v1_ad_scanner_id_ignore_domain_put_request api_v1_ad_scanner_id_ignore_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdIgnoreDomainPut($id, $api_v1_ad_scanner_id_ignore_domain_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'][0])
    {
        $this->apiV1AdScannerIdIgnoreDomainPutWithHttpInfo($id, $api_v1_ad_scanner_id_ignore_domain_put_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdIgnoreDomainPutWithHttpInfo
     *
     * Ignore (or un-ignore) a flagged domain on a task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest $api_v1_ad_scanner_id_ignore_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdIgnoreDomainPutWithHttpInfo($id, $api_v1_ad_scanner_id_ignore_domain_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'][0])
    {
        $request = $this->apiV1AdScannerIdIgnoreDomainPutRequest($id, $api_v1_ad_scanner_id_ignore_domain_put_request, $contentType);

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
     * Operation apiV1AdScannerIdIgnoreDomainPutAsync
     *
     * Ignore (or un-ignore) a flagged domain on a task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest $api_v1_ad_scanner_id_ignore_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdIgnoreDomainPutAsync($id, $api_v1_ad_scanner_id_ignore_domain_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'][0])
    {
        return $this->apiV1AdScannerIdIgnoreDomainPutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_ignore_domain_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdIgnoreDomainPutAsyncWithHttpInfo
     *
     * Ignore (or un-ignore) a flagged domain on a task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest $api_v1_ad_scanner_id_ignore_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdIgnoreDomainPutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_ignore_domain_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdIgnoreDomainPutRequest($id, $api_v1_ad_scanner_id_ignore_domain_put_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdIgnoreDomainPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdIgnoreDomainPutRequest $api_v1_ad_scanner_id_ignore_domain_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdIgnoreDomainPutRequest($id, $api_v1_ad_scanner_id_ignore_domain_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdIgnoreDomainPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdIgnoreDomainPut'
            );
        }

        // verify the required parameter 'api_v1_ad_scanner_id_ignore_domain_put_request' is set
        if ($api_v1_ad_scanner_id_ignore_domain_put_request === null || (is_array($api_v1_ad_scanner_id_ignore_domain_put_request) && count($api_v1_ad_scanner_id_ignore_domain_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_ad_scanner_id_ignore_domain_put_request when calling apiV1AdScannerIdIgnoreDomainPut'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}/ignore-domain';
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
        if (isset($api_v1_ad_scanner_id_ignore_domain_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_id_ignore_domain_put_request));
            } else {
                $httpBody = $api_v1_ad_scanner_id_ignore_domain_put_request;
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
     * Operation apiV1AdScannerIdManualItemsGet
     *
     * List a manual task&#39;s items.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdManualItemsGet($id, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsGet'][0])
    {
        $this->apiV1AdScannerIdManualItemsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdManualItemsGetWithHttpInfo
     *
     * List a manual task&#39;s items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdManualItemsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsGet'][0])
    {
        $request = $this->apiV1AdScannerIdManualItemsGetRequest($id, $contentType);

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
     * Operation apiV1AdScannerIdManualItemsGetAsync
     *
     * List a manual task&#39;s items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsGetAsync($id, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsGet'][0])
    {
        return $this->apiV1AdScannerIdManualItemsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdManualItemsGetAsyncWithHttpInfo
     *
     * List a manual task&#39;s items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdManualItemsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdManualItemsGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdManualItemsGetRequest($id, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdManualItemsGet'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}/manual-items';
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
     * Operation apiV1AdScannerIdManualItemsItemIdStatePut
     *
     * Pause, resume or delete a manual item.
     *
     * @param  int $id id (required)
     * @param  int $item_id item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest $api_v1_ad_scanner_id_manual_items_item_id_state_put_request api_v1_ad_scanner_id_manual_items_item_id_state_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdManualItemsItemIdStatePut($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'][0])
    {
        $this->apiV1AdScannerIdManualItemsItemIdStatePutWithHttpInfo($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdManualItemsItemIdStatePutWithHttpInfo
     *
     * Pause, resume or delete a manual item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest $api_v1_ad_scanner_id_manual_items_item_id_state_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdManualItemsItemIdStatePutWithHttpInfo($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'][0])
    {
        $request = $this->apiV1AdScannerIdManualItemsItemIdStatePutRequest($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, $contentType);

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
     * Operation apiV1AdScannerIdManualItemsItemIdStatePutAsync
     *
     * Pause, resume or delete a manual item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest $api_v1_ad_scanner_id_manual_items_item_id_state_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsItemIdStatePutAsync($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'][0])
    {
        return $this->apiV1AdScannerIdManualItemsItemIdStatePutAsyncWithHttpInfo($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdManualItemsItemIdStatePutAsyncWithHttpInfo
     *
     * Pause, resume or delete a manual item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest $api_v1_ad_scanner_id_manual_items_item_id_state_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsItemIdStatePutAsyncWithHttpInfo($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdManualItemsItemIdStatePutRequest($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdManualItemsItemIdStatePut'
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsItemIdStatePutRequest $api_v1_ad_scanner_id_manual_items_item_id_state_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdManualItemsItemIdStatePutRequest($id, $item_id, $api_v1_ad_scanner_id_manual_items_item_id_state_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsItemIdStatePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdManualItemsItemIdStatePut'
            );
        }

        // verify the required parameter 'item_id' is set
        if ($item_id === null || (is_array($item_id) && count($item_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $item_id when calling apiV1AdScannerIdManualItemsItemIdStatePut'
            );
        }

        // verify the required parameter 'api_v1_ad_scanner_id_manual_items_item_id_state_put_request' is set
        if ($api_v1_ad_scanner_id_manual_items_item_id_state_put_request === null || (is_array($api_v1_ad_scanner_id_manual_items_item_id_state_put_request) && count($api_v1_ad_scanner_id_manual_items_item_id_state_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_ad_scanner_id_manual_items_item_id_state_put_request when calling apiV1AdScannerIdManualItemsItemIdStatePut'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}/manual-items/{itemId}/state';
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
        if ($item_id !== null) {
            $resourcePath = str_replace(
                '{' . 'itemId' . '}',
                ObjectSerializer::toPathValue($item_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_ad_scanner_id_manual_items_item_id_state_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_id_manual_items_item_id_state_put_request));
            } else {
                $httpBody = $api_v1_ad_scanner_id_manual_items_item_id_state_put_request;
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
     * Operation apiV1AdScannerIdManualItemsPost
     *
     * Add or edit a manual item.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest $api_v1_ad_scanner_id_manual_items_post_request api_v1_ad_scanner_id_manual_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdManualItemsPost($id, $api_v1_ad_scanner_id_manual_items_post_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsPost'][0])
    {
        $this->apiV1AdScannerIdManualItemsPostWithHttpInfo($id, $api_v1_ad_scanner_id_manual_items_post_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdManualItemsPostWithHttpInfo
     *
     * Add or edit a manual item.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest $api_v1_ad_scanner_id_manual_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdManualItemsPostWithHttpInfo($id, $api_v1_ad_scanner_id_manual_items_post_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsPost'][0])
    {
        $request = $this->apiV1AdScannerIdManualItemsPostRequest($id, $api_v1_ad_scanner_id_manual_items_post_request, $contentType);

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
     * Operation apiV1AdScannerIdManualItemsPostAsync
     *
     * Add or edit a manual item.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest $api_v1_ad_scanner_id_manual_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsPostAsync($id, $api_v1_ad_scanner_id_manual_items_post_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsPost'][0])
    {
        return $this->apiV1AdScannerIdManualItemsPostAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_manual_items_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdManualItemsPostAsyncWithHttpInfo
     *
     * Add or edit a manual item.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest $api_v1_ad_scanner_id_manual_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdManualItemsPostAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_manual_items_post_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdManualItemsPostRequest($id, $api_v1_ad_scanner_id_manual_items_post_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdManualItemsPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdManualItemsPostRequest $api_v1_ad_scanner_id_manual_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdManualItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdManualItemsPostRequest($id, $api_v1_ad_scanner_id_manual_items_post_request, string $contentType = self::contentTypes['apiV1AdScannerIdManualItemsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdManualItemsPost'
            );
        }

        // verify the required parameter 'api_v1_ad_scanner_id_manual_items_post_request' is set
        if ($api_v1_ad_scanner_id_manual_items_post_request === null || (is_array($api_v1_ad_scanner_id_manual_items_post_request) && count($api_v1_ad_scanner_id_manual_items_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_ad_scanner_id_manual_items_post_request when calling apiV1AdScannerIdManualItemsPost'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}/manual-items';
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
        if (isset($api_v1_ad_scanner_id_manual_items_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_id_manual_items_post_request));
            } else {
                $httpBody = $api_v1_ad_scanner_id_manual_items_post_request;
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
     * Operation apiV1AdScannerIdPut
     *
     * Replace a task&#39;s name, tags and config.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest $api_v1_ad_scanner_id_put_request api_v1_ad_scanner_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdPut($id, $api_v1_ad_scanner_id_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdPut'][0])
    {
        $this->apiV1AdScannerIdPutWithHttpInfo($id, $api_v1_ad_scanner_id_put_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdPutWithHttpInfo
     *
     * Replace a task&#39;s name, tags and config.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest $api_v1_ad_scanner_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdPutWithHttpInfo($id, $api_v1_ad_scanner_id_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdPut'][0])
    {
        $request = $this->apiV1AdScannerIdPutRequest($id, $api_v1_ad_scanner_id_put_request, $contentType);

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
     * Operation apiV1AdScannerIdPutAsync
     *
     * Replace a task&#39;s name, tags and config.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest $api_v1_ad_scanner_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdPutAsync($id, $api_v1_ad_scanner_id_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdPut'][0])
    {
        return $this->apiV1AdScannerIdPutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdPutAsyncWithHttpInfo
     *
     * Replace a task&#39;s name, tags and config.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest $api_v1_ad_scanner_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdPutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdPutRequest($id, $api_v1_ad_scanner_id_put_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdPutRequest $api_v1_ad_scanner_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdPutRequest($id, $api_v1_ad_scanner_id_put_request, string $contentType = self::contentTypes['apiV1AdScannerIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdPut'
            );
        }

        // verify the required parameter 'api_v1_ad_scanner_id_put_request' is set
        if ($api_v1_ad_scanner_id_put_request === null || (is_array($api_v1_ad_scanner_id_put_request) && count($api_v1_ad_scanner_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_ad_scanner_id_put_request when calling apiV1AdScannerIdPut'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/{id}';
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
        if (isset($api_v1_ad_scanner_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_id_put_request));
            } else {
                $httpBody = $api_v1_ad_scanner_id_put_request;
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
     * Operation apiV1AdScannerIdToggleActivePut
     *
     * Start or stop a scan task.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest|null $api_v1_ad_scanner_id_toggle_active_put_request api_v1_ad_scanner_id_toggle_active_put_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdToggleActivePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerIdToggleActivePut($id, $api_v1_ad_scanner_id_toggle_active_put_request = null, string $contentType = self::contentTypes['apiV1AdScannerIdToggleActivePut'][0])
    {
        $this->apiV1AdScannerIdToggleActivePutWithHttpInfo($id, $api_v1_ad_scanner_id_toggle_active_put_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerIdToggleActivePutWithHttpInfo
     *
     * Start or stop a scan task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest|null $api_v1_ad_scanner_id_toggle_active_put_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdToggleActivePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerIdToggleActivePutWithHttpInfo($id, $api_v1_ad_scanner_id_toggle_active_put_request = null, string $contentType = self::contentTypes['apiV1AdScannerIdToggleActivePut'][0])
    {
        $request = $this->apiV1AdScannerIdToggleActivePutRequest($id, $api_v1_ad_scanner_id_toggle_active_put_request, $contentType);

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
     * Operation apiV1AdScannerIdToggleActivePutAsync
     *
     * Start or stop a scan task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest|null $api_v1_ad_scanner_id_toggle_active_put_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdToggleActivePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdToggleActivePutAsync($id, $api_v1_ad_scanner_id_toggle_active_put_request = null, string $contentType = self::contentTypes['apiV1AdScannerIdToggleActivePut'][0])
    {
        return $this->apiV1AdScannerIdToggleActivePutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_toggle_active_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerIdToggleActivePutAsyncWithHttpInfo
     *
     * Start or stop a scan task.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest|null $api_v1_ad_scanner_id_toggle_active_put_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdToggleActivePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerIdToggleActivePutAsyncWithHttpInfo($id, $api_v1_ad_scanner_id_toggle_active_put_request = null, string $contentType = self::contentTypes['apiV1AdScannerIdToggleActivePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerIdToggleActivePutRequest($id, $api_v1_ad_scanner_id_toggle_active_put_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerIdToggleActivePut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdScannerIdToggleActivePutRequest|null $api_v1_ad_scanner_id_toggle_active_put_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerIdToggleActivePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerIdToggleActivePutRequest($id, $api_v1_ad_scanner_id_toggle_active_put_request = null, string $contentType = self::contentTypes['apiV1AdScannerIdToggleActivePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdScannerIdToggleActivePut'
            );
        }



        $resourcePath = '/api/v1/ad-scanner/{id}/toggle-active';
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
        if (isset($api_v1_ad_scanner_id_toggle_active_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_id_toggle_active_put_request));
            } else {
                $httpBody = $api_v1_ad_scanner_id_toggle_active_put_request;
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
     * Operation apiV1AdScannerPost
     *
     * Create a scan task.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdScannerPostRequest $api_v1_ad_scanner_post_request api_v1_ad_scanner_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerPost($api_v1_ad_scanner_post_request, string $contentType = self::contentTypes['apiV1AdScannerPost'][0])
    {
        $this->apiV1AdScannerPostWithHttpInfo($api_v1_ad_scanner_post_request, $contentType);
    }

    /**
     * Operation apiV1AdScannerPostWithHttpInfo
     *
     * Create a scan task.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdScannerPostRequest $api_v1_ad_scanner_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerPostWithHttpInfo($api_v1_ad_scanner_post_request, string $contentType = self::contentTypes['apiV1AdScannerPost'][0])
    {
        $request = $this->apiV1AdScannerPostRequest($api_v1_ad_scanner_post_request, $contentType);

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
     * Operation apiV1AdScannerPostAsync
     *
     * Create a scan task.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdScannerPostRequest $api_v1_ad_scanner_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerPostAsync($api_v1_ad_scanner_post_request, string $contentType = self::contentTypes['apiV1AdScannerPost'][0])
    {
        return $this->apiV1AdScannerPostAsyncWithHttpInfo($api_v1_ad_scanner_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerPostAsyncWithHttpInfo
     *
     * Create a scan task.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdScannerPostRequest $api_v1_ad_scanner_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerPostAsyncWithHttpInfo($api_v1_ad_scanner_post_request, string $contentType = self::contentTypes['apiV1AdScannerPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerPostRequest($api_v1_ad_scanner_post_request, $contentType);

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
     * Create request for operation 'apiV1AdScannerPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AdScannerPostRequest $api_v1_ad_scanner_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerPostRequest($api_v1_ad_scanner_post_request, string $contentType = self::contentTypes['apiV1AdScannerPost'][0])
    {

        // verify the required parameter 'api_v1_ad_scanner_post_request' is set
        if ($api_v1_ad_scanner_post_request === null || (is_array($api_v1_ad_scanner_post_request) && count($api_v1_ad_scanner_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_ad_scanner_post_request when calling apiV1AdScannerPost'
            );
        }


        $resourcePath = '/api/v1/ad-scanner';
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
        if (isset($api_v1_ad_scanner_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_ad_scanner_post_request));
            } else {
                $httpBody = $api_v1_ad_scanner_post_request;
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
     * Operation apiV1AdScannerReportHashGet
     *
     * One scan&#39;s full report.
     *
     * @param  string $hash hash (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportHashGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerReportHashGet($hash, string $contentType = self::contentTypes['apiV1AdScannerReportHashGet'][0])
    {
        $this->apiV1AdScannerReportHashGetWithHttpInfo($hash, $contentType);
    }

    /**
     * Operation apiV1AdScannerReportHashGetWithHttpInfo
     *
     * One scan&#39;s full report.
     *
     * @param  string $hash (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportHashGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerReportHashGetWithHttpInfo($hash, string $contentType = self::contentTypes['apiV1AdScannerReportHashGet'][0])
    {
        $request = $this->apiV1AdScannerReportHashGetRequest($hash, $contentType);

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
     * Operation apiV1AdScannerReportHashGetAsync
     *
     * One scan&#39;s full report.
     *
     * @param  string $hash (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportHashGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerReportHashGetAsync($hash, string $contentType = self::contentTypes['apiV1AdScannerReportHashGet'][0])
    {
        return $this->apiV1AdScannerReportHashGetAsyncWithHttpInfo($hash, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerReportHashGetAsyncWithHttpInfo
     *
     * One scan&#39;s full report.
     *
     * @param  string $hash (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportHashGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerReportHashGetAsyncWithHttpInfo($hash, string $contentType = self::contentTypes['apiV1AdScannerReportHashGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerReportHashGetRequest($hash, $contentType);

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
     * Create request for operation 'apiV1AdScannerReportHashGet'
     *
     * @param  string $hash (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportHashGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerReportHashGetRequest($hash, string $contentType = self::contentTypes['apiV1AdScannerReportHashGet'][0])
    {

        // verify the required parameter 'hash' is set
        if ($hash === null || (is_array($hash) && count($hash) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $hash when calling apiV1AdScannerReportHashGet'
            );
        }


        $resourcePath = '/api/v1/ad-scanner/report/{hash}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($hash !== null) {
            $resourcePath = str_replace(
                '{' . 'hash' . '}',
                ObjectSerializer::toPathValue($hash),
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
     * Operation apiV1AdScannerReportsGet
     *
     * List scan results.
     *
     * @param  \DateTime|null $datestart datestart (optional)
     * @param  \DateTime|null $dateend dateend (optional)
     * @param  string|null $scan_type scan_type (optional)
     * @param  string|null $scan_target scan_target (optional)
     * @param  int|null $scan_state scan_state (optional)
     * @param  int|null $page page (optional, default to 1)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerReportsGet($datestart = null, $dateend = null, $scan_type = null, $scan_target = null, $scan_state = null, $page = 1, string $contentType = self::contentTypes['apiV1AdScannerReportsGet'][0])
    {
        $this->apiV1AdScannerReportsGetWithHttpInfo($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page, $contentType);
    }

    /**
     * Operation apiV1AdScannerReportsGetWithHttpInfo
     *
     * List scan results.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $scan_type (optional)
     * @param  string|null $scan_target (optional)
     * @param  int|null $scan_state (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerReportsGetWithHttpInfo($datestart = null, $dateend = null, $scan_type = null, $scan_target = null, $scan_state = null, $page = 1, string $contentType = self::contentTypes['apiV1AdScannerReportsGet'][0])
    {
        $request = $this->apiV1AdScannerReportsGetRequest($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page, $contentType);

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
     * Operation apiV1AdScannerReportsGetAsync
     *
     * List scan results.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $scan_type (optional)
     * @param  string|null $scan_target (optional)
     * @param  int|null $scan_state (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerReportsGetAsync($datestart = null, $dateend = null, $scan_type = null, $scan_target = null, $scan_state = null, $page = 1, string $contentType = self::contentTypes['apiV1AdScannerReportsGet'][0])
    {
        return $this->apiV1AdScannerReportsGetAsyncWithHttpInfo($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerReportsGetAsyncWithHttpInfo
     *
     * List scan results.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $scan_type (optional)
     * @param  string|null $scan_target (optional)
     * @param  int|null $scan_state (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerReportsGetAsyncWithHttpInfo($datestart = null, $dateend = null, $scan_type = null, $scan_target = null, $scan_state = null, $page = 1, string $contentType = self::contentTypes['apiV1AdScannerReportsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerReportsGetRequest($datestart, $dateend, $scan_type, $scan_target, $scan_state, $page, $contentType);

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
     * Create request for operation 'apiV1AdScannerReportsGet'
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $scan_type (optional)
     * @param  string|null $scan_target (optional)
     * @param  int|null $scan_state (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerReportsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerReportsGetRequest($datestart = null, $dateend = null, $scan_type = null, $scan_target = null, $scan_state = null, $page = 1, string $contentType = self::contentTypes['apiV1AdScannerReportsGet'][0])
    {








        $resourcePath = '/api/v1/ad-scanner/reports';
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
            $scan_type,
            'scan_type', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $scan_target,
            'scan_target', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $scan_state,
            'scan_state', // param base name
            'integer', // openApiType
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
     * Operation apiV1AdScannerStatsGet
     *
     * Scan volume and proxy bandwidth.
     *
     * @param  \DateTime|null $datestart datestart (optional)
     * @param  \DateTime|null $dateend dateend (optional)
     * @param  string|null $stats_selcols stats_selcols (optional)
     * @param  int|null $config_id config_id (optional)
     * @param  string|null $country country (optional)
     * @param  int|null $proxy_used proxy_used (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerStatsGet($datestart = null, $dateend = null, $stats_selcols = null, $config_id = null, $country = null, $proxy_used = null, string $contentType = self::contentTypes['apiV1AdScannerStatsGet'][0])
    {
        $this->apiV1AdScannerStatsGetWithHttpInfo($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used, $contentType);
    }

    /**
     * Operation apiV1AdScannerStatsGetWithHttpInfo
     *
     * Scan volume and proxy bandwidth.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $stats_selcols (optional)
     * @param  int|null $config_id (optional)
     * @param  string|null $country (optional)
     * @param  int|null $proxy_used (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerStatsGetWithHttpInfo($datestart = null, $dateend = null, $stats_selcols = null, $config_id = null, $country = null, $proxy_used = null, string $contentType = self::contentTypes['apiV1AdScannerStatsGet'][0])
    {
        $request = $this->apiV1AdScannerStatsGetRequest($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used, $contentType);

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
     * Operation apiV1AdScannerStatsGetAsync
     *
     * Scan volume and proxy bandwidth.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $stats_selcols (optional)
     * @param  int|null $config_id (optional)
     * @param  string|null $country (optional)
     * @param  int|null $proxy_used (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerStatsGetAsync($datestart = null, $dateend = null, $stats_selcols = null, $config_id = null, $country = null, $proxy_used = null, string $contentType = self::contentTypes['apiV1AdScannerStatsGet'][0])
    {
        return $this->apiV1AdScannerStatsGetAsyncWithHttpInfo($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerStatsGetAsyncWithHttpInfo
     *
     * Scan volume and proxy bandwidth.
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $stats_selcols (optional)
     * @param  int|null $config_id (optional)
     * @param  string|null $country (optional)
     * @param  int|null $proxy_used (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerStatsGetAsyncWithHttpInfo($datestart = null, $dateend = null, $stats_selcols = null, $config_id = null, $country = null, $proxy_used = null, string $contentType = self::contentTypes['apiV1AdScannerStatsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerStatsGetRequest($datestart, $dateend, $stats_selcols, $config_id, $country, $proxy_used, $contentType);

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
     * Create request for operation 'apiV1AdScannerStatsGet'
     *
     * @param  \DateTime|null $datestart (optional)
     * @param  \DateTime|null $dateend (optional)
     * @param  string|null $stats_selcols (optional)
     * @param  int|null $config_id (optional)
     * @param  string|null $country (optional)
     * @param  int|null $proxy_used (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerStatsGetRequest($datestart = null, $dateend = null, $stats_selcols = null, $config_id = null, $country = null, $proxy_used = null, string $contentType = self::contentTypes['apiV1AdScannerStatsGet'][0])
    {








        $resourcePath = '/api/v1/ad-scanner/stats';
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
            $stats_selcols,
            'stats_selcols', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $config_id,
            'config_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $country,
            'country', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $proxy_used,
            'proxy_used', // param base name
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
     * Operation apiV1AdScannerTagsGet
     *
     * Tags used on the account&#39;s scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerTagsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdScannerTagsGet(string $contentType = self::contentTypes['apiV1AdScannerTagsGet'][0])
    {
        $this->apiV1AdScannerTagsGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1AdScannerTagsGetWithHttpInfo
     *
     * Tags used on the account&#39;s scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerTagsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdScannerTagsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AdScannerTagsGet'][0])
    {
        $request = $this->apiV1AdScannerTagsGetRequest($contentType);

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
     * Operation apiV1AdScannerTagsGetAsync
     *
     * Tags used on the account&#39;s scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerTagsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerTagsGetAsync(string $contentType = self::contentTypes['apiV1AdScannerTagsGet'][0])
    {
        return $this->apiV1AdScannerTagsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdScannerTagsGetAsyncWithHttpInfo
     *
     * Tags used on the account&#39;s scan tasks.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerTagsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdScannerTagsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AdScannerTagsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdScannerTagsGetRequest($contentType);

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
     * Create request for operation 'apiV1AdScannerTagsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdScannerTagsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdScannerTagsGetRequest(string $contentType = self::contentTypes['apiV1AdScannerTagsGet'][0])
    {


        $resourcePath = '/api/v1/ad-scanner/tags';
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

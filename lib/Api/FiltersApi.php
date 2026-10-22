<?php
/**
 * FiltersApi
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
 * FiltersApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class FiltersApi
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
        'apiV1FiltersGet' => [
            'application/json',
        ],
        'apiV1FiltersIpIdRangesRangeIdDelete' => [
            'application/json',
        ],
        'apiV1FiltersIpIdRangesRangeIdTogglePut' => [
            'application/json',
        ],
        'apiV1FiltersMassActionPost' => [
            'application/json',
        ],
        'apiV1FiltersPost' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdActivePatch' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdDelete' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdGet' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdItemsItemDelete' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdItemsPost' => [
            'application/json',
        ],
        'apiV1FiltersTypeIdPut' => [
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
     * Operation apiV1FiltersGet
     *
     * List all filters across types.
     *
     * @param  \GoAdServerApi\Model\FilterType|null $type Filter to one type. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FiltersGet200Response
     */
    public function apiV1FiltersGet($type = null, $search = null, string $contentType = self::contentTypes['apiV1FiltersGet'][0])
    {
        list($response) = $this->apiV1FiltersGetWithHttpInfo($type, $search, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersGetWithHttpInfo
     *
     * List all filters across types.
     *
     * @param  \GoAdServerApi\Model\FilterType|null $type Filter to one type. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FiltersGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersGetWithHttpInfo($type = null, $search = null, string $contentType = self::contentTypes['apiV1FiltersGet'][0])
    {
        $request = $this->apiV1FiltersGetRequest($type, $search, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1FiltersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1FiltersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FiltersGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersGetAsync
     *
     * List all filters across types.
     *
     * @param  \GoAdServerApi\Model\FilterType|null $type Filter to one type. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersGetAsync($type = null, $search = null, string $contentType = self::contentTypes['apiV1FiltersGet'][0])
    {
        return $this->apiV1FiltersGetAsyncWithHttpInfo($type, $search, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersGetAsyncWithHttpInfo
     *
     * List all filters across types.
     *
     * @param  \GoAdServerApi\Model\FilterType|null $type Filter to one type. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersGetAsyncWithHttpInfo($type = null, $search = null, string $contentType = self::contentTypes['apiV1FiltersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FiltersGet200Response';
        $request = $this->apiV1FiltersGetRequest($type, $search, $contentType);

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
     * Create request for operation 'apiV1FiltersGet'
     *
     * @param  \GoAdServerApi\Model\FilterType|null $type Filter to one type. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersGetRequest($type = null, $search = null, string $contentType = self::contentTypes['apiV1FiltersGet'][0])
    {




        $resourcePath = '/api/v1/filters';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $type,
            'type', // param base name
            'FilterType', // openApiType
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
     * Operation apiV1FiltersIpIdRangesRangeIdDelete
     *
     * Delete one range from an IP pool.
     *
     * @param  int $id id (required)
     * @param  int $range_id range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FiltersIpIdRangesRangeIdDelete($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'][0])
    {
        $this->apiV1FiltersIpIdRangesRangeIdDeleteWithHttpInfo($id, $range_id, $contentType);
    }

    /**
     * Operation apiV1FiltersIpIdRangesRangeIdDeleteWithHttpInfo
     *
     * Delete one range from an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersIpIdRangesRangeIdDeleteWithHttpInfo($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'][0])
    {
        $request = $this->apiV1FiltersIpIdRangesRangeIdDeleteRequest($id, $range_id, $contentType);

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
     * Operation apiV1FiltersIpIdRangesRangeIdDeleteAsync
     *
     * Delete one range from an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersIpIdRangesRangeIdDeleteAsync($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'][0])
    {
        return $this->apiV1FiltersIpIdRangesRangeIdDeleteAsyncWithHttpInfo($id, $range_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersIpIdRangesRangeIdDeleteAsyncWithHttpInfo
     *
     * Delete one range from an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersIpIdRangesRangeIdDeleteAsyncWithHttpInfo($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1FiltersIpIdRangesRangeIdDeleteRequest($id, $range_id, $contentType);

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
     * Create request for operation 'apiV1FiltersIpIdRangesRangeIdDelete'
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersIpIdRangesRangeIdDeleteRequest($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersIpIdRangesRangeIdDelete'
            );
        }

        // verify the required parameter 'range_id' is set
        if ($range_id === null || (is_array($range_id) && count($range_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $range_id when calling apiV1FiltersIpIdRangesRangeIdDelete'
            );
        }


        $resourcePath = '/api/v1/filters/ip/{id}/ranges/{rangeId}';
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
        if ($range_id !== null) {
            $resourcePath = str_replace(
                '{' . 'rangeId' . '}',
                ObjectSerializer::toPathValue($range_id),
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
     * Operation apiV1FiltersIpIdRangesRangeIdTogglePut
     *
     * Pause / resume one range of an IP pool.
     *
     * @param  int $id id (required)
     * @param  int $range_id range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FiltersIpIdRangesRangeIdTogglePut($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'][0])
    {
        $this->apiV1FiltersIpIdRangesRangeIdTogglePutWithHttpInfo($id, $range_id, $contentType);
    }

    /**
     * Operation apiV1FiltersIpIdRangesRangeIdTogglePutWithHttpInfo
     *
     * Pause / resume one range of an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersIpIdRangesRangeIdTogglePutWithHttpInfo($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'][0])
    {
        $request = $this->apiV1FiltersIpIdRangesRangeIdTogglePutRequest($id, $range_id, $contentType);

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
     * Operation apiV1FiltersIpIdRangesRangeIdTogglePutAsync
     *
     * Pause / resume one range of an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersIpIdRangesRangeIdTogglePutAsync($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'][0])
    {
        return $this->apiV1FiltersIpIdRangesRangeIdTogglePutAsyncWithHttpInfo($id, $range_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersIpIdRangesRangeIdTogglePutAsyncWithHttpInfo
     *
     * Pause / resume one range of an IP pool.
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersIpIdRangesRangeIdTogglePutAsyncWithHttpInfo($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1FiltersIpIdRangesRangeIdTogglePutRequest($id, $range_id, $contentType);

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
     * Create request for operation 'apiV1FiltersIpIdRangesRangeIdTogglePut'
     *
     * @param  int $id (required)
     * @param  int $range_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersIpIdRangesRangeIdTogglePutRequest($id, $range_id, string $contentType = self::contentTypes['apiV1FiltersIpIdRangesRangeIdTogglePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersIpIdRangesRangeIdTogglePut'
            );
        }

        // verify the required parameter 'range_id' is set
        if ($range_id === null || (is_array($range_id) && count($range_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $range_id when calling apiV1FiltersIpIdRangesRangeIdTogglePut'
            );
        }


        $resourcePath = '/api/v1/filters/ip/{id}/ranges/{rangeId}/toggle';
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
        if ($range_id !== null) {
            $resourcePath = str_replace(
                '{' . 'rangeId' . '}',
                ObjectSerializer::toPathValue($range_id),
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
     * Operation apiV1FiltersMassActionPost
     *
     * Activate, deactivate or delete many filters.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest $api_v1_filters_mass_action_post_request api_v1_filters_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersMassActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FiltersMassActionPost($api_v1_filters_mass_action_post_request, string $contentType = self::contentTypes['apiV1FiltersMassActionPost'][0])
    {
        $this->apiV1FiltersMassActionPostWithHttpInfo($api_v1_filters_mass_action_post_request, $contentType);
    }

    /**
     * Operation apiV1FiltersMassActionPostWithHttpInfo
     *
     * Activate, deactivate or delete many filters.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest $api_v1_filters_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersMassActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersMassActionPostWithHttpInfo($api_v1_filters_mass_action_post_request, string $contentType = self::contentTypes['apiV1FiltersMassActionPost'][0])
    {
        $request = $this->apiV1FiltersMassActionPostRequest($api_v1_filters_mass_action_post_request, $contentType);

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
     * Operation apiV1FiltersMassActionPostAsync
     *
     * Activate, deactivate or delete many filters.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest $api_v1_filters_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersMassActionPostAsync($api_v1_filters_mass_action_post_request, string $contentType = self::contentTypes['apiV1FiltersMassActionPost'][0])
    {
        return $this->apiV1FiltersMassActionPostAsyncWithHttpInfo($api_v1_filters_mass_action_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersMassActionPostAsyncWithHttpInfo
     *
     * Activate, deactivate or delete many filters.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest $api_v1_filters_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersMassActionPostAsyncWithHttpInfo($api_v1_filters_mass_action_post_request, string $contentType = self::contentTypes['apiV1FiltersMassActionPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1FiltersMassActionPostRequest($api_v1_filters_mass_action_post_request, $contentType);

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
     * Create request for operation 'apiV1FiltersMassActionPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest $api_v1_filters_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersMassActionPostRequest($api_v1_filters_mass_action_post_request, string $contentType = self::contentTypes['apiV1FiltersMassActionPost'][0])
    {

        // verify the required parameter 'api_v1_filters_mass_action_post_request' is set
        if ($api_v1_filters_mass_action_post_request === null || (is_array($api_v1_filters_mass_action_post_request) && count($api_v1_filters_mass_action_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_filters_mass_action_post_request when calling apiV1FiltersMassActionPost'
            );
        }


        $resourcePath = '/api/v1/filters/mass-action';
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
        if (isset($api_v1_filters_mass_action_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_filters_mass_action_post_request));
            } else {
                $httpBody = $api_v1_filters_mass_action_post_request;
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
     * Operation apiV1FiltersPost
     *
     * Create a filter and populate it with items.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersPostRequest $api_v1_filters_post_request api_v1_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\FilterDetail
     */
    public function apiV1FiltersPost($api_v1_filters_post_request, string $contentType = self::contentTypes['apiV1FiltersPost'][0])
    {
        list($response) = $this->apiV1FiltersPostWithHttpInfo($api_v1_filters_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersPostWithHttpInfo
     *
     * Create a filter and populate it with items.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersPostRequest $api_v1_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\FilterDetail, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersPostWithHttpInfo($api_v1_filters_post_request, string $contentType = self::contentTypes['apiV1FiltersPost'][0])
    {
        $request = $this->apiV1FiltersPostRequest($api_v1_filters_post_request, $contentType);

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
                        '\GoAdServerApi\Model\FilterDetail',
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
                '\GoAdServerApi\Model\FilterDetail',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\FilterDetail',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersPostAsync
     *
     * Create a filter and populate it with items.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersPostRequest $api_v1_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersPostAsync($api_v1_filters_post_request, string $contentType = self::contentTypes['apiV1FiltersPost'][0])
    {
        return $this->apiV1FiltersPostAsyncWithHttpInfo($api_v1_filters_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersPostAsyncWithHttpInfo
     *
     * Create a filter and populate it with items.
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersPostRequest $api_v1_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersPostAsyncWithHttpInfo($api_v1_filters_post_request, string $contentType = self::contentTypes['apiV1FiltersPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\FilterDetail';
        $request = $this->apiV1FiltersPostRequest($api_v1_filters_post_request, $contentType);

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
     * Create request for operation 'apiV1FiltersPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1FiltersPostRequest $api_v1_filters_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersPostRequest($api_v1_filters_post_request, string $contentType = self::contentTypes['apiV1FiltersPost'][0])
    {

        // verify the required parameter 'api_v1_filters_post_request' is set
        if ($api_v1_filters_post_request === null || (is_array($api_v1_filters_post_request) && count($api_v1_filters_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_filters_post_request when calling apiV1FiltersPost'
            );
        }


        $resourcePath = '/api/v1/filters';
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
        if (isset($api_v1_filters_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_filters_post_request));
            } else {
                $httpBody = $api_v1_filters_post_request;
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
     * Operation apiV1FiltersTypeIdActivePatch
     *
     * Activate or deactivate a filter (exclusion types only).
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response
     */
    public function apiV1FiltersTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdActivePatch'][0])
    {
        list($response) = $this->apiV1FiltersTypeIdActivePatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersTypeIdActivePatchWithHttpInfo
     *
     * Activate or deactivate a filter (exclusion types only).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdActivePatchWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdActivePatch'][0])
    {
        $request = $this->apiV1FiltersTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response',
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
                '\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersTypeIdActivePatchAsync
     *
     * Activate or deactivate a filter (exclusion types only).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdActivePatchAsync($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdActivePatch'][0])
    {
        return $this->apiV1FiltersTypeIdActivePatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdActivePatchAsyncWithHttpInfo
     *
     * Activate or deactivate a filter (exclusion types only).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdActivePatchAsyncWithHttpInfo($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdActivePatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response';
        $request = $this->apiV1FiltersTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdActivePatch'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest $api_v1_campaigns_type_id_active_patch_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdActivePatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdActivePatchRequest($type, $id, $api_v1_campaigns_type_id_active_patch_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdActivePatch'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdActivePatch'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdActivePatch'
            );
        }

        // verify the required parameter 'api_v1_campaigns_type_id_active_patch_request' is set
        if ($api_v1_campaigns_type_id_active_patch_request === null || (is_array($api_v1_campaigns_type_id_active_patch_request) && count($api_v1_campaigns_type_id_active_patch_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_campaigns_type_id_active_patch_request when calling apiV1FiltersTypeIdActivePatch'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}/active';
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
     * Operation apiV1FiltersTypeIdDelete
     *
     * Delete a filter (and its items, for IP pools).
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FiltersTypeIdDelete($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdDelete'][0])
    {
        $this->apiV1FiltersTypeIdDeleteWithHttpInfo($type, $id, $contentType);
    }

    /**
     * Operation apiV1FiltersTypeIdDeleteWithHttpInfo
     *
     * Delete a filter (and its items, for IP pools).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdDeleteWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdDelete'][0])
    {
        $request = $this->apiV1FiltersTypeIdDeleteRequest($type, $id, $contentType);

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
     * Operation apiV1FiltersTypeIdDeleteAsync
     *
     * Delete a filter (and its items, for IP pools).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdDeleteAsync($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdDelete'][0])
    {
        return $this->apiV1FiltersTypeIdDeleteAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdDeleteAsyncWithHttpInfo
     *
     * Delete a filter (and its items, for IP pools).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdDeleteAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1FiltersTypeIdDeleteRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdDelete'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdDeleteRequest($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdDelete'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdDelete'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}';
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
     * Operation apiV1FiltersTypeIdGet
     *
     * Get a single filter with its items.
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\FilterDetail
     */
    public function apiV1FiltersTypeIdGet($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdGet'][0])
    {
        list($response) = $this->apiV1FiltersTypeIdGetWithHttpInfo($type, $id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersTypeIdGetWithHttpInfo
     *
     * Get a single filter with its items.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\FilterDetail, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdGetWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdGet'][0])
    {
        $request = $this->apiV1FiltersTypeIdGetRequest($type, $id, $contentType);

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
                        '\GoAdServerApi\Model\FilterDetail',
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
                '\GoAdServerApi\Model\FilterDetail',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\FilterDetail',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersTypeIdGetAsync
     *
     * Get a single filter with its items.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdGetAsync($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdGet'][0])
    {
        return $this->apiV1FiltersTypeIdGetAsyncWithHttpInfo($type, $id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdGetAsyncWithHttpInfo
     *
     * Get a single filter with its items.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdGetAsyncWithHttpInfo($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\FilterDetail';
        $request = $this->apiV1FiltersTypeIdGetRequest($type, $id, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdGet'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdGetRequest($type, $id, string $contentType = self::contentTypes['apiV1FiltersTypeIdGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdGet'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdGet'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}';
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
     * Operation apiV1FiltersTypeIdItemsItemDelete
     *
     * Remove a single item from a filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  string $item item (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response
     */
    public function apiV1FiltersTypeIdItemsItemDelete($type, $id, $item, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'][0])
    {
        list($response) = $this->apiV1FiltersTypeIdItemsItemDeleteWithHttpInfo($type, $id, $item, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersTypeIdItemsItemDeleteWithHttpInfo
     *
     * Remove a single item from a filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $item (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdItemsItemDeleteWithHttpInfo($type, $id, $item, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'][0])
    {
        $request = $this->apiV1FiltersTypeIdItemsItemDeleteRequest($type, $id, $item, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersTypeIdItemsItemDeleteAsync
     *
     * Remove a single item from a filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $item (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdItemsItemDeleteAsync($type, $id, $item, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'][0])
    {
        return $this->apiV1FiltersTypeIdItemsItemDeleteAsyncWithHttpInfo($type, $id, $item, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdItemsItemDeleteAsyncWithHttpInfo
     *
     * Remove a single item from a filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $item (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdItemsItemDeleteAsyncWithHttpInfo($type, $id, $item, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response';
        $request = $this->apiV1FiltersTypeIdItemsItemDeleteRequest($type, $id, $item, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdItemsItemDelete'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  string $item (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdItemsItemDeleteRequest($type, $id, $item, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsItemDelete'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdItemsItemDelete'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdItemsItemDelete'
            );
        }

        // verify the required parameter 'item' is set
        if ($item === null || (is_array($item) && count($item) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $item when calling apiV1FiltersTypeIdItemsItemDelete'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}/items/{item}';
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
        if ($item !== null) {
            $resourcePath = str_replace(
                '{' . 'item' . '}',
                ObjectSerializer::toPathValue($item),
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
     * Operation apiV1FiltersTypeIdItemsPost
     *
     * Add items to an existing filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest $api_v1_filters_type_id_items_post_request api_v1_filters_type_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response
     */
    public function apiV1FiltersTypeIdItemsPost($type, $id, $api_v1_filters_type_id_items_post_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsPost'][0])
    {
        list($response) = $this->apiV1FiltersTypeIdItemsPostWithHttpInfo($type, $id, $api_v1_filters_type_id_items_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersTypeIdItemsPostWithHttpInfo
     *
     * Add items to an existing filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest $api_v1_filters_type_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdItemsPostWithHttpInfo($type, $id, $api_v1_filters_type_id_items_post_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsPost'][0])
    {
        $request = $this->apiV1FiltersTypeIdItemsPostRequest($type, $id, $api_v1_filters_type_id_items_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response',
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
                '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersTypeIdItemsPostAsync
     *
     * Add items to an existing filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest $api_v1_filters_type_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdItemsPostAsync($type, $id, $api_v1_filters_type_id_items_post_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsPost'][0])
    {
        return $this->apiV1FiltersTypeIdItemsPostAsyncWithHttpInfo($type, $id, $api_v1_filters_type_id_items_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdItemsPostAsyncWithHttpInfo
     *
     * Add items to an existing filter.
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest $api_v1_filters_type_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdItemsPostAsyncWithHttpInfo($type, $id, $api_v1_filters_type_id_items_post_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response';
        $request = $this->apiV1FiltersTypeIdItemsPostRequest($type, $id, $api_v1_filters_type_id_items_post_request, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdItemsPost'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest $api_v1_filters_type_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdItemsPostRequest($type, $id, $api_v1_filters_type_id_items_post_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdItemsPost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdItemsPost'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdItemsPost'
            );
        }

        // verify the required parameter 'api_v1_filters_type_id_items_post_request' is set
        if ($api_v1_filters_type_id_items_post_request === null || (is_array($api_v1_filters_type_id_items_post_request) && count($api_v1_filters_type_id_items_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_filters_type_id_items_post_request when calling apiV1FiltersTypeIdItemsPost'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}/items';
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
        if (isset($api_v1_filters_type_id_items_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_filters_type_id_items_post_request));
            } else {
                $httpBody = $api_v1_filters_type_id_items_post_request;
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
     * Operation apiV1FiltersTypeIdPut
     *
     * Edit a filter (rename, replace items, change scope).
     *
     * @param  \GoAdServerApi\Model\FilterType $type type (required)
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest $api_v1_filters_type_id_put_request api_v1_filters_type_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\FilterDetail
     */
    public function apiV1FiltersTypeIdPut($type, $id, $api_v1_filters_type_id_put_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdPut'][0])
    {
        list($response) = $this->apiV1FiltersTypeIdPutWithHttpInfo($type, $id, $api_v1_filters_type_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FiltersTypeIdPutWithHttpInfo
     *
     * Edit a filter (rename, replace items, change scope).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest $api_v1_filters_type_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\FilterDetail, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FiltersTypeIdPutWithHttpInfo($type, $id, $api_v1_filters_type_id_put_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdPut'][0])
    {
        $request = $this->apiV1FiltersTypeIdPutRequest($type, $id, $api_v1_filters_type_id_put_request, $contentType);

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
                        '\GoAdServerApi\Model\FilterDetail',
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
                '\GoAdServerApi\Model\FilterDetail',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\FilterDetail',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FiltersTypeIdPutAsync
     *
     * Edit a filter (rename, replace items, change scope).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest $api_v1_filters_type_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdPutAsync($type, $id, $api_v1_filters_type_id_put_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdPut'][0])
    {
        return $this->apiV1FiltersTypeIdPutAsyncWithHttpInfo($type, $id, $api_v1_filters_type_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FiltersTypeIdPutAsyncWithHttpInfo
     *
     * Edit a filter (rename, replace items, change scope).
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest $api_v1_filters_type_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FiltersTypeIdPutAsyncWithHttpInfo($type, $id, $api_v1_filters_type_id_put_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\FilterDetail';
        $request = $this->apiV1FiltersTypeIdPutRequest($type, $id, $api_v1_filters_type_id_put_request, $contentType);

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
     * Create request for operation 'apiV1FiltersTypeIdPut'
     *
     * @param  \GoAdServerApi\Model\FilterType $type (required)
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest $api_v1_filters_type_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FiltersTypeIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FiltersTypeIdPutRequest($type, $id, $api_v1_filters_type_id_put_request, string $contentType = self::contentTypes['apiV1FiltersTypeIdPut'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FiltersTypeIdPut'
            );
        }

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FiltersTypeIdPut'
            );
        }

        // verify the required parameter 'api_v1_filters_type_id_put_request' is set
        if ($api_v1_filters_type_id_put_request === null || (is_array($api_v1_filters_type_id_put_request) && count($api_v1_filters_type_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_filters_type_id_put_request when calling apiV1FiltersTypeIdPut'
            );
        }


        $resourcePath = '/api/v1/filters/{type}/{id}';
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
        if (isset($api_v1_filters_type_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_filters_type_id_put_request));
            } else {
                $httpBody = $api_v1_filters_type_id_put_request;
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

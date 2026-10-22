<?php
/**
 * LookupsApi
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
 * LookupsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class LookupsApi
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
        'apiV1LookupsAdspaceTypesGet' => [
            'application/json',
        ],
        'apiV1LookupsAdzoneLocationsGet' => [
            'application/json',
        ],
        'apiV1LookupsAdzonesGet' => [
            'application/json',
        ],
        'apiV1LookupsBannerSizesGet' => [
            'application/json',
        ],
        'apiV1LookupsBrowsersGet' => [
            'application/json',
        ],
        'apiV1LookupsCarriersGet' => [
            'application/json',
        ],
        'apiV1LookupsCategoriesGet' => [
            'application/json',
        ],
        'apiV1LookupsDeviceTypesGet' => [
            'application/json',
        ],
        'apiV1LookupsDomainsGet' => [
            'application/json',
        ],
        'apiV1LookupsIspsGet' => [
            'application/json',
        ],
        'apiV1LookupsLinkTypesGet' => [
            'application/json',
        ],
        'apiV1LookupsMainCategoriesGet' => [
            'application/json',
        ],
        'apiV1LookupsOperatingSystemsGet' => [
            'application/json',
        ],
        'apiV1LookupsPublishersGet' => [
            'application/json',
        ],
        'apiV1LookupsRatingsGet' => [
            'application/json',
        ],
        'apiV1LookupsReelTypesGet' => [
            'application/json',
        ],
        'apiV1LookupsRegionsGet' => [
            'application/json',
        ],
        'apiV1LookupsWebsiteLanguagesGet' => [
            'application/json',
        ],
        'apiV1LookupsWebsiteTypesGet' => [
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
     * Operation apiV1LookupsAdspaceTypesGet
     *
     * Adspace types the caller can publish on this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdspaceTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsAdspaceTypesGet(string $contentType = self::contentTypes['apiV1LookupsAdspaceTypesGet'][0])
    {
        list($response) = $this->apiV1LookupsAdspaceTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsAdspaceTypesGetWithHttpInfo
     *
     * Adspace types the caller can publish on this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdspaceTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsAdspaceTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsAdspaceTypesGet'][0])
    {
        $request = $this->apiV1LookupsAdspaceTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response',
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
     * Operation apiV1LookupsAdspaceTypesGetAsync
     *
     * Adspace types the caller can publish on this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdspaceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdspaceTypesGetAsync(string $contentType = self::contentTypes['apiV1LookupsAdspaceTypesGet'][0])
    {
        return $this->apiV1LookupsAdspaceTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsAdspaceTypesGetAsyncWithHttpInfo
     *
     * Adspace types the caller can publish on this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdspaceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdspaceTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsAdspaceTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response';
        $request = $this->apiV1LookupsAdspaceTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsAdspaceTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdspaceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsAdspaceTypesGetRequest(string $contentType = self::contentTypes['apiV1LookupsAdspaceTypesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/adspace-types';
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
     * Operation apiV1LookupsAdzoneLocationsGet
     *
     * Adzone locations for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzoneLocationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsAdzoneLocationsGet(string $contentType = self::contentTypes['apiV1LookupsAdzoneLocationsGet'][0])
    {
        list($response) = $this->apiV1LookupsAdzoneLocationsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsAdzoneLocationsGetWithHttpInfo
     *
     * Adzone locations for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzoneLocationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsAdzoneLocationsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsAdzoneLocationsGet'][0])
    {
        $request = $this->apiV1LookupsAdzoneLocationsGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsAdzoneLocationsGetAsync
     *
     * Adzone locations for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzoneLocationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdzoneLocationsGetAsync(string $contentType = self::contentTypes['apiV1LookupsAdzoneLocationsGet'][0])
    {
        return $this->apiV1LookupsAdzoneLocationsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsAdzoneLocationsGetAsyncWithHttpInfo
     *
     * Adzone locations for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzoneLocationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdzoneLocationsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsAdzoneLocationsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsAdzoneLocationsGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsAdzoneLocationsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzoneLocationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsAdzoneLocationsGetRequest(string $contentType = self::contentTypes['apiV1LookupsAdzoneLocationsGet'][0])
    {


        $resourcePath = '/api/v1/lookups/adzone-locations';
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
     * Operation apiV1LookupsAdzonesGet
     *
     * Find adzone ids (for adzone filters).
     *
     * @param  string|null $term term (optional)
     * @param  string|null $bulk Newline-separated adzone ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1LookupsAdzonesGet($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsAdzonesGet'][0])
    {
        $this->apiV1LookupsAdzonesGetWithHttpInfo($term, $bulk, $contentType);
    }

    /**
     * Operation apiV1LookupsAdzonesGetWithHttpInfo
     *
     * Find adzone ids (for adzone filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated adzone ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsAdzonesGetWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsAdzonesGet'][0])
    {
        $request = $this->apiV1LookupsAdzonesGetRequest($term, $bulk, $contentType);

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
     * Operation apiV1LookupsAdzonesGetAsync
     *
     * Find adzone ids (for adzone filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated adzone ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdzonesGetAsync($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsAdzonesGet'][0])
    {
        return $this->apiV1LookupsAdzonesGetAsyncWithHttpInfo($term, $bulk, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsAdzonesGetAsyncWithHttpInfo
     *
     * Find adzone ids (for adzone filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated adzone ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsAdzonesGetAsyncWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsAdzonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1LookupsAdzonesGetRequest($term, $bulk, $contentType);

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
     * Create request for operation 'apiV1LookupsAdzonesGet'
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated adzone ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsAdzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsAdzonesGetRequest($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsAdzonesGet'][0])
    {

        if ($term !== null && strlen($term) < 2) {
            throw new \InvalidArgumentException('invalid length for "$term" when calling LookupsApi.apiV1LookupsAdzonesGet, must be bigger than or equal to 2.');
        }
        


        $resourcePath = '/api/v1/lookups/adzones';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $term,
            'term', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $bulk,
            'bulk', // param base name
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
     * Operation apiV1LookupsBannerSizesGet
     *
     * Banner sizes for banner-flavored adspaces.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBannerSizesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsBannerSizesGet(string $contentType = self::contentTypes['apiV1LookupsBannerSizesGet'][0])
    {
        list($response) = $this->apiV1LookupsBannerSizesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsBannerSizesGetWithHttpInfo
     *
     * Banner sizes for banner-flavored adspaces.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBannerSizesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsBannerSizesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsBannerSizesGet'][0])
    {
        $request = $this->apiV1LookupsBannerSizesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response',
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
     * Operation apiV1LookupsBannerSizesGetAsync
     *
     * Banner sizes for banner-flavored adspaces.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBannerSizesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsBannerSizesGetAsync(string $contentType = self::contentTypes['apiV1LookupsBannerSizesGet'][0])
    {
        return $this->apiV1LookupsBannerSizesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsBannerSizesGetAsyncWithHttpInfo
     *
     * Banner sizes for banner-flavored adspaces.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBannerSizesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsBannerSizesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsBannerSizesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response';
        $request = $this->apiV1LookupsBannerSizesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsBannerSizesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBannerSizesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsBannerSizesGetRequest(string $contentType = self::contentTypes['apiV1LookupsBannerSizesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/banner-sizes';
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
     * Operation apiV1LookupsBrowsersGet
     *
     * Browser ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBrowsersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsBrowsersGet($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsBrowsersGet'][0])
    {
        list($response) = $this->apiV1LookupsBrowsersGetWithHttpInfo($device_type_ids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsBrowsersGetWithHttpInfo
     *
     * Browser ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBrowsersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsBrowsersGetWithHttpInfo($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsBrowsersGet'][0])
    {
        $request = $this->apiV1LookupsBrowsersGetRequest($device_type_ids, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
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
     * Operation apiV1LookupsBrowsersGetAsync
     *
     * Browser ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBrowsersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsBrowsersGetAsync($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsBrowsersGet'][0])
    {
        return $this->apiV1LookupsBrowsersGetAsyncWithHttpInfo($device_type_ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsBrowsersGetAsyncWithHttpInfo
     *
     * Browser ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBrowsersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsBrowsersGetAsyncWithHttpInfo($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsBrowsersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response';
        $request = $this->apiV1LookupsBrowsersGetRequest($device_type_ids, $contentType);

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
     * Create request for operation 'apiV1LookupsBrowsersGet'
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsBrowsersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsBrowsersGetRequest($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsBrowsersGet'][0])
    {



        $resourcePath = '/api/v1/lookups/browsers';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $device_type_ids,
            'device_type_ids', // param base name
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
     * Operation apiV1LookupsCarriersGet
     *
     * Mobile carriers (ids for carrier targeting).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCarriersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsCarriersGet(string $contentType = self::contentTypes['apiV1LookupsCarriersGet'][0])
    {
        list($response) = $this->apiV1LookupsCarriersGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsCarriersGetWithHttpInfo
     *
     * Mobile carriers (ids for carrier targeting).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCarriersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsCarriersGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsCarriersGet'][0])
    {
        $request = $this->apiV1LookupsCarriersGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response',
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
     * Operation apiV1LookupsCarriersGetAsync
     *
     * Mobile carriers (ids for carrier targeting).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCarriersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsCarriersGetAsync(string $contentType = self::contentTypes['apiV1LookupsCarriersGet'][0])
    {
        return $this->apiV1LookupsCarriersGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsCarriersGetAsyncWithHttpInfo
     *
     * Mobile carriers (ids for carrier targeting).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCarriersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsCarriersGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsCarriersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response';
        $request = $this->apiV1LookupsCarriersGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsCarriersGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCarriersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsCarriersGetRequest(string $contentType = self::contentTypes['apiV1LookupsCarriersGet'][0])
    {


        $resourcePath = '/api/v1/lookups/carriers';
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
     * Operation apiV1LookupsCategoriesGet
     *
     * Active sub-categories (optionally scoped to a main).
     *
     * @param  int|null $main_catid Filter to categories under this main. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCategoriesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsCategoriesGet($main_catid = null, string $contentType = self::contentTypes['apiV1LookupsCategoriesGet'][0])
    {
        list($response) = $this->apiV1LookupsCategoriesGetWithHttpInfo($main_catid, $contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsCategoriesGetWithHttpInfo
     *
     * Active sub-categories (optionally scoped to a main).
     *
     * @param  int|null $main_catid Filter to categories under this main. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCategoriesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsCategoriesGetWithHttpInfo($main_catid = null, string $contentType = self::contentTypes['apiV1LookupsCategoriesGet'][0])
    {
        $request = $this->apiV1LookupsCategoriesGetRequest($main_catid, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response',
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
     * Operation apiV1LookupsCategoriesGetAsync
     *
     * Active sub-categories (optionally scoped to a main).
     *
     * @param  int|null $main_catid Filter to categories under this main. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsCategoriesGetAsync($main_catid = null, string $contentType = self::contentTypes['apiV1LookupsCategoriesGet'][0])
    {
        return $this->apiV1LookupsCategoriesGetAsyncWithHttpInfo($main_catid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsCategoriesGetAsyncWithHttpInfo
     *
     * Active sub-categories (optionally scoped to a main).
     *
     * @param  int|null $main_catid Filter to categories under this main. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsCategoriesGetAsyncWithHttpInfo($main_catid = null, string $contentType = self::contentTypes['apiV1LookupsCategoriesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response';
        $request = $this->apiV1LookupsCategoriesGetRequest($main_catid, $contentType);

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
     * Create request for operation 'apiV1LookupsCategoriesGet'
     *
     * @param  int|null $main_catid Filter to categories under this main. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsCategoriesGetRequest($main_catid = null, string $contentType = self::contentTypes['apiV1LookupsCategoriesGet'][0])
    {



        $resourcePath = '/api/v1/lookups/categories';
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
     * Operation apiV1LookupsDeviceTypesGet
     *
     * Active device types for this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDeviceTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsDeviceTypesGet(string $contentType = self::contentTypes['apiV1LookupsDeviceTypesGet'][0])
    {
        list($response) = $this->apiV1LookupsDeviceTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsDeviceTypesGetWithHttpInfo
     *
     * Active device types for this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDeviceTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsDeviceTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsDeviceTypesGet'][0])
    {
        $request = $this->apiV1LookupsDeviceTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsDeviceTypesGetAsync
     *
     * Active device types for this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDeviceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsDeviceTypesGetAsync(string $contentType = self::contentTypes['apiV1LookupsDeviceTypesGet'][0])
    {
        return $this->apiV1LookupsDeviceTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsDeviceTypesGetAsyncWithHttpInfo
     *
     * Active device types for this tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDeviceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsDeviceTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsDeviceTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsDeviceTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsDeviceTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDeviceTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsDeviceTypesGetRequest(string $contentType = self::contentTypes['apiV1LookupsDeviceTypesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/device-types';
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
     * Operation apiV1LookupsDomainsGet
     *
     * Resolve domains to the ids domain filters store.
     *
     * @param  string $bulk bulk (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDomainsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1LookupsDomainsGet($bulk, string $contentType = self::contentTypes['apiV1LookupsDomainsGet'][0])
    {
        $this->apiV1LookupsDomainsGetWithHttpInfo($bulk, $contentType);
    }

    /**
     * Operation apiV1LookupsDomainsGetWithHttpInfo
     *
     * Resolve domains to the ids domain filters store.
     *
     * @param  string $bulk (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDomainsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsDomainsGetWithHttpInfo($bulk, string $contentType = self::contentTypes['apiV1LookupsDomainsGet'][0])
    {
        $request = $this->apiV1LookupsDomainsGetRequest($bulk, $contentType);

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
     * Operation apiV1LookupsDomainsGetAsync
     *
     * Resolve domains to the ids domain filters store.
     *
     * @param  string $bulk (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDomainsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsDomainsGetAsync($bulk, string $contentType = self::contentTypes['apiV1LookupsDomainsGet'][0])
    {
        return $this->apiV1LookupsDomainsGetAsyncWithHttpInfo($bulk, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsDomainsGetAsyncWithHttpInfo
     *
     * Resolve domains to the ids domain filters store.
     *
     * @param  string $bulk (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDomainsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsDomainsGetAsyncWithHttpInfo($bulk, string $contentType = self::contentTypes['apiV1LookupsDomainsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1LookupsDomainsGetRequest($bulk, $contentType);

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
     * Create request for operation 'apiV1LookupsDomainsGet'
     *
     * @param  string $bulk (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsDomainsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsDomainsGetRequest($bulk, string $contentType = self::contentTypes['apiV1LookupsDomainsGet'][0])
    {

        // verify the required parameter 'bulk' is set
        if ($bulk === null || (is_array($bulk) && count($bulk) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $bulk when calling apiV1LookupsDomainsGet'
            );
        }


        $resourcePath = '/api/v1/lookups/domains';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $bulk,
            'bulk', // param base name
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
     * Operation apiV1LookupsIspsGet
     *
     * Find ISP ids (for ISP filters).
     *
     * @param  string|null $term term (optional)
     * @param  string|null $bulk Newline-separated name fragments; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsIspsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1LookupsIspsGet($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsIspsGet'][0])
    {
        $this->apiV1LookupsIspsGetWithHttpInfo($term, $bulk, $contentType);
    }

    /**
     * Operation apiV1LookupsIspsGetWithHttpInfo
     *
     * Find ISP ids (for ISP filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated name fragments; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsIspsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsIspsGetWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsIspsGet'][0])
    {
        $request = $this->apiV1LookupsIspsGetRequest($term, $bulk, $contentType);

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
     * Operation apiV1LookupsIspsGetAsync
     *
     * Find ISP ids (for ISP filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated name fragments; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsIspsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsIspsGetAsync($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsIspsGet'][0])
    {
        return $this->apiV1LookupsIspsGetAsyncWithHttpInfo($term, $bulk, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsIspsGetAsyncWithHttpInfo
     *
     * Find ISP ids (for ISP filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated name fragments; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsIspsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsIspsGetAsyncWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsIspsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1LookupsIspsGetRequest($term, $bulk, $contentType);

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
     * Create request for operation 'apiV1LookupsIspsGet'
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated name fragments; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsIspsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsIspsGetRequest($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsIspsGet'][0])
    {

        if ($term !== null && strlen($term) < 2) {
            throw new \InvalidArgumentException('invalid length for "$term" when calling LookupsApi.apiV1LookupsIspsGet, must be bigger than or equal to 2.');
        }
        


        $resourcePath = '/api/v1/lookups/isps';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $term,
            'term', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $bulk,
            'bulk', // param base name
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
     * Operation apiV1LookupsLinkTypesGet
     *
     * Active link types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsLinkTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsLinkTypesGet(string $contentType = self::contentTypes['apiV1LookupsLinkTypesGet'][0])
    {
        list($response) = $this->apiV1LookupsLinkTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsLinkTypesGetWithHttpInfo
     *
     * Active link types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsLinkTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsLinkTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsLinkTypesGet'][0])
    {
        $request = $this->apiV1LookupsLinkTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsLinkTypesGetAsync
     *
     * Active link types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsLinkTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsLinkTypesGetAsync(string $contentType = self::contentTypes['apiV1LookupsLinkTypesGet'][0])
    {
        return $this->apiV1LookupsLinkTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsLinkTypesGetAsyncWithHttpInfo
     *
     * Active link types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsLinkTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsLinkTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsLinkTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsLinkTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsLinkTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsLinkTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsLinkTypesGetRequest(string $contentType = self::contentTypes['apiV1LookupsLinkTypesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/link-types';
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
     * Operation apiV1LookupsMainCategoriesGet
     *
     * Active main categories for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsMainCategoriesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsMainCategoriesGet(string $contentType = self::contentTypes['apiV1LookupsMainCategoriesGet'][0])
    {
        list($response) = $this->apiV1LookupsMainCategoriesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsMainCategoriesGetWithHttpInfo
     *
     * Active main categories for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsMainCategoriesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsMainCategoriesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsMainCategoriesGet'][0])
    {
        $request = $this->apiV1LookupsMainCategoriesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsMainCategoriesGetAsync
     *
     * Active main categories for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsMainCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsMainCategoriesGetAsync(string $contentType = self::contentTypes['apiV1LookupsMainCategoriesGet'][0])
    {
        return $this->apiV1LookupsMainCategoriesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsMainCategoriesGetAsyncWithHttpInfo
     *
     * Active main categories for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsMainCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsMainCategoriesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsMainCategoriesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsMainCategoriesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsMainCategoriesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsMainCategoriesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsMainCategoriesGetRequest(string $contentType = self::contentTypes['apiV1LookupsMainCategoriesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/main-categories';
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
     * Operation apiV1LookupsOperatingSystemsGet
     *
     * Operating-system ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsOperatingSystemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsOperatingSystemsGet($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsOperatingSystemsGet'][0])
    {
        list($response) = $this->apiV1LookupsOperatingSystemsGetWithHttpInfo($device_type_ids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsOperatingSystemsGetWithHttpInfo
     *
     * Operating-system ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsOperatingSystemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsOperatingSystemsGetWithHttpInfo($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsOperatingSystemsGet'][0])
    {
        $request = $this->apiV1LookupsOperatingSystemsGetRequest($device_type_ids, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response',
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
     * Operation apiV1LookupsOperatingSystemsGetAsync
     *
     * Operating-system ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsOperatingSystemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsOperatingSystemsGetAsync($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsOperatingSystemsGet'][0])
    {
        return $this->apiV1LookupsOperatingSystemsGetAsyncWithHttpInfo($device_type_ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsOperatingSystemsGetAsyncWithHttpInfo
     *
     * Operating-system ids (optionally scoped by device type).
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsOperatingSystemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsOperatingSystemsGetAsyncWithHttpInfo($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsOperatingSystemsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response';
        $request = $this->apiV1LookupsOperatingSystemsGetRequest($device_type_ids, $contentType);

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
     * Create request for operation 'apiV1LookupsOperatingSystemsGet'
     *
     * @param  string|null $device_type_ids Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsOperatingSystemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsOperatingSystemsGetRequest($device_type_ids = null, string $contentType = self::contentTypes['apiV1LookupsOperatingSystemsGet'][0])
    {



        $resourcePath = '/api/v1/lookups/operating-systems';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $device_type_ids,
            'device_type_ids', // param base name
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
     * Operation apiV1LookupsPublishersGet
     *
     * Find publisher ids (for publisher_excl filters).
     *
     * @param  string|null $term term (optional)
     * @param  string|null $bulk Newline-separated publisher ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsPublishersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1LookupsPublishersGet($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsPublishersGet'][0])
    {
        $this->apiV1LookupsPublishersGetWithHttpInfo($term, $bulk, $contentType);
    }

    /**
     * Operation apiV1LookupsPublishersGetWithHttpInfo
     *
     * Find publisher ids (for publisher_excl filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated publisher ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsPublishersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsPublishersGetWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsPublishersGet'][0])
    {
        $request = $this->apiV1LookupsPublishersGetRequest($term, $bulk, $contentType);

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
     * Operation apiV1LookupsPublishersGetAsync
     *
     * Find publisher ids (for publisher_excl filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated publisher ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsPublishersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsPublishersGetAsync($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsPublishersGet'][0])
    {
        return $this->apiV1LookupsPublishersGetAsyncWithHttpInfo($term, $bulk, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsPublishersGetAsyncWithHttpInfo
     *
     * Find publisher ids (for publisher_excl filters).
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated publisher ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsPublishersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsPublishersGetAsyncWithHttpInfo($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsPublishersGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1LookupsPublishersGetRequest($term, $bulk, $contentType);

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
     * Create request for operation 'apiV1LookupsPublishersGet'
     *
     * @param  string|null $term (optional)
     * @param  string|null $bulk Newline-separated publisher ids; wins over term. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsPublishersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsPublishersGetRequest($term = null, $bulk = null, string $contentType = self::contentTypes['apiV1LookupsPublishersGet'][0])
    {




        $resourcePath = '/api/v1/lookups/publishers';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $term,
            'term', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $bulk,
            'bulk', // param base name
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
     * Operation apiV1LookupsRatingsGet
     *
     * Active content ratings for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRatingsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsRatingsGet(string $contentType = self::contentTypes['apiV1LookupsRatingsGet'][0])
    {
        list($response) = $this->apiV1LookupsRatingsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsRatingsGetWithHttpInfo
     *
     * Active content ratings for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRatingsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsRatingsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsRatingsGet'][0])
    {
        $request = $this->apiV1LookupsRatingsGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsRatingsGetAsync
     *
     * Active content ratings for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRatingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsRatingsGetAsync(string $contentType = self::contentTypes['apiV1LookupsRatingsGet'][0])
    {
        return $this->apiV1LookupsRatingsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsRatingsGetAsyncWithHttpInfo
     *
     * Active content ratings for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRatingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsRatingsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsRatingsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsRatingsGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsRatingsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRatingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsRatingsGetRequest(string $contentType = self::contentTypes['apiV1LookupsRatingsGet'][0])
    {


        $resourcePath = '/api/v1/lookups/ratings';
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
     * Operation apiV1LookupsReelTypesGet
     *
     * Active reel types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsReelTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsReelTypesGet(string $contentType = self::contentTypes['apiV1LookupsReelTypesGet'][0])
    {
        list($response) = $this->apiV1LookupsReelTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsReelTypesGetWithHttpInfo
     *
     * Active reel types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsReelTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsReelTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsReelTypesGet'][0])
    {
        $request = $this->apiV1LookupsReelTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsReelTypesGetAsync
     *
     * Active reel types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsReelTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsReelTypesGetAsync(string $contentType = self::contentTypes['apiV1LookupsReelTypesGet'][0])
    {
        return $this->apiV1LookupsReelTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsReelTypesGetAsyncWithHttpInfo
     *
     * Active reel types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsReelTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsReelTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsReelTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsReelTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsReelTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsReelTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsReelTypesGetRequest(string $contentType = self::contentTypes['apiV1LookupsReelTypesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/reel-types';
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
     * Operation apiV1LookupsRegionsGet
     *
     * Walk the geo region tree (one level per call).
     *
     * @param  int|null $parent_id Parent region id to list children of. 0 (default) returns continents. (optional, default to 0)
     * @param  string|null $ids Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRegionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsRegionsGet($parent_id = 0, $ids = null, string $contentType = self::contentTypes['apiV1LookupsRegionsGet'][0])
    {
        list($response) = $this->apiV1LookupsRegionsGetWithHttpInfo($parent_id, $ids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsRegionsGetWithHttpInfo
     *
     * Walk the geo region tree (one level per call).
     *
     * @param  int|null $parent_id Parent region id to list children of. 0 (default) returns continents. (optional, default to 0)
     * @param  string|null $ids Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRegionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsRegionsGetWithHttpInfo($parent_id = 0, $ids = null, string $contentType = self::contentTypes['apiV1LookupsRegionsGet'][0])
    {
        $request = $this->apiV1LookupsRegionsGetRequest($parent_id, $ids, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response',
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
     * Operation apiV1LookupsRegionsGetAsync
     *
     * Walk the geo region tree (one level per call).
     *
     * @param  int|null $parent_id Parent region id to list children of. 0 (default) returns continents. (optional, default to 0)
     * @param  string|null $ids Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRegionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsRegionsGetAsync($parent_id = 0, $ids = null, string $contentType = self::contentTypes['apiV1LookupsRegionsGet'][0])
    {
        return $this->apiV1LookupsRegionsGetAsyncWithHttpInfo($parent_id, $ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsRegionsGetAsyncWithHttpInfo
     *
     * Walk the geo region tree (one level per call).
     *
     * @param  int|null $parent_id Parent region id to list children of. 0 (default) returns continents. (optional, default to 0)
     * @param  string|null $ids Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRegionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsRegionsGetAsyncWithHttpInfo($parent_id = 0, $ids = null, string $contentType = self::contentTypes['apiV1LookupsRegionsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response';
        $request = $this->apiV1LookupsRegionsGetRequest($parent_id, $ids, $contentType);

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
     * Create request for operation 'apiV1LookupsRegionsGet'
     *
     * @param  int|null $parent_id Parent region id to list children of. 0 (default) returns continents. (optional, default to 0)
     * @param  string|null $ids Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsRegionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsRegionsGetRequest($parent_id = 0, $ids = null, string $contentType = self::contentTypes['apiV1LookupsRegionsGet'][0])
    {




        $resourcePath = '/api/v1/lookups/regions';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $parent_id,
            'parent_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ids,
            'ids', // param base name
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
     * Operation apiV1LookupsWebsiteLanguagesGet
     *
     * Supported website languages (ISO codes).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteLanguagesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsWebsiteLanguagesGet(string $contentType = self::contentTypes['apiV1LookupsWebsiteLanguagesGet'][0])
    {
        list($response) = $this->apiV1LookupsWebsiteLanguagesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsWebsiteLanguagesGetWithHttpInfo
     *
     * Supported website languages (ISO codes).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteLanguagesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsWebsiteLanguagesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsWebsiteLanguagesGet'][0])
    {
        $request = $this->apiV1LookupsWebsiteLanguagesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response',
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
     * Operation apiV1LookupsWebsiteLanguagesGetAsync
     *
     * Supported website languages (ISO codes).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteLanguagesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsWebsiteLanguagesGetAsync(string $contentType = self::contentTypes['apiV1LookupsWebsiteLanguagesGet'][0])
    {
        return $this->apiV1LookupsWebsiteLanguagesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsWebsiteLanguagesGetAsyncWithHttpInfo
     *
     * Supported website languages (ISO codes).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteLanguagesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsWebsiteLanguagesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsWebsiteLanguagesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response';
        $request = $this->apiV1LookupsWebsiteLanguagesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsWebsiteLanguagesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteLanguagesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsWebsiteLanguagesGetRequest(string $contentType = self::contentTypes['apiV1LookupsWebsiteLanguagesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/website-languages';
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
     * Operation apiV1LookupsWebsiteTypesGet
     *
     * Active website types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1LookupsWebsiteTypesGet(string $contentType = self::contentTypes['apiV1LookupsWebsiteTypesGet'][0])
    {
        list($response) = $this->apiV1LookupsWebsiteTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1LookupsWebsiteTypesGetWithHttpInfo
     *
     * Active website types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1LookupsWebsiteTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsWebsiteTypesGet'][0])
    {
        $request = $this->apiV1LookupsWebsiteTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response',
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
     * Operation apiV1LookupsWebsiteTypesGetAsync
     *
     * Active website types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsWebsiteTypesGetAsync(string $contentType = self::contentTypes['apiV1LookupsWebsiteTypesGet'][0])
    {
        return $this->apiV1LookupsWebsiteTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1LookupsWebsiteTypesGetAsyncWithHttpInfo
     *
     * Active website types for the tenant.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1LookupsWebsiteTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1LookupsWebsiteTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response';
        $request = $this->apiV1LookupsWebsiteTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1LookupsWebsiteTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1LookupsWebsiteTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1LookupsWebsiteTypesGetRequest(string $contentType = self::contentTypes['apiV1LookupsWebsiteTypesGet'][0])
    {


        $resourcePath = '/api/v1/lookups/website-types';
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

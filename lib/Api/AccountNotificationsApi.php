<?php
/**
 * AccountNotificationsApi
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
 * AccountNotificationsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class AccountNotificationsApi
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
        'apiV1AccountNotificationDestinationsGet' => [
            'application/json',
        ],
        'apiV1AccountNotificationDestinationsIdDelete' => [
            'application/json',
        ],
        'apiV1AccountNotificationDestinationsIdStatusGet' => [
            'application/json',
        ],
        'apiV1AccountNotificationDestinationsIdTestPost' => [
            'application/json',
        ],
        'apiV1AccountNotificationDestinationsPost' => [
            'application/json',
        ],
        'apiV1AccountNotificationPreferencesGet' => [
            'application/json',
        ],
        'apiV1AccountNotificationPreferencesPut' => [
            'application/json',
        ],
        'apiV1AccountNotificationsGet' => [
            'application/json',
        ],
        'apiV1AccountNotificationsReadPost' => [
            'application/json',
        ],
        'apiV1AccountNotificationsUnreadCountGet' => [
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
     * Operation apiV1AccountNotificationDestinationsGet
     *
     * The account&#39;s notification destinations.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationDestinationsGet(string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsGet'][0])
    {
        list($response) = $this->apiV1AccountNotificationDestinationsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationDestinationsGetWithHttpInfo
     *
     * The account&#39;s notification destinations.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationDestinationsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsGet'][0])
    {
        $request = $this->apiV1AccountNotificationDestinationsGetRequest($contentType);

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
     * Operation apiV1AccountNotificationDestinationsGetAsync
     *
     * The account&#39;s notification destinations.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsGetAsync(string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsGet'][0])
    {
        return $this->apiV1AccountNotificationDestinationsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationDestinationsGetAsyncWithHttpInfo
     *
     * The account&#39;s notification destinations.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationDestinationsGetRequest($contentType);

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
     * Create request for operation 'apiV1AccountNotificationDestinationsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationDestinationsGetRequest(string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsGet'][0])
    {


        $resourcePath = '/api/v1/account/notification-destinations';
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
     * Operation apiV1AccountNotificationDestinationsIdDelete
     *
     * Delete a notification destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationDestinationsIdDelete($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'][0])
    {
        list($response) = $this->apiV1AccountNotificationDestinationsIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdDeleteWithHttpInfo
     *
     * Delete a notification destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationDestinationsIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'][0])
    {
        $request = $this->apiV1AccountNotificationDestinationsIdDeleteRequest($id, $contentType);

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
     * Operation apiV1AccountNotificationDestinationsIdDeleteAsync
     *
     * Delete a notification destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'][0])
    {
        return $this->apiV1AccountNotificationDestinationsIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdDeleteAsyncWithHttpInfo
     *
     * Delete a notification destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationDestinationsIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationDestinationsIdDelete'
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationDestinationsIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AccountNotificationDestinationsIdDelete'
            );
        }


        $resourcePath = '/api/v1/account/notification-destinations/{id}';
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
     * Operation apiV1AccountNotificationDestinationsIdStatusGet
     *
     * Whether a destination is linked.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationDestinationsIdStatusGet($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'][0])
    {
        list($response) = $this->apiV1AccountNotificationDestinationsIdStatusGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdStatusGetWithHttpInfo
     *
     * Whether a destination is linked.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationDestinationsIdStatusGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'][0])
    {
        $request = $this->apiV1AccountNotificationDestinationsIdStatusGetRequest($id, $contentType);

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
     * Operation apiV1AccountNotificationDestinationsIdStatusGetAsync
     *
     * Whether a destination is linked.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdStatusGetAsync($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'][0])
    {
        return $this->apiV1AccountNotificationDestinationsIdStatusGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdStatusGetAsyncWithHttpInfo
     *
     * Whether a destination is linked.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdStatusGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationDestinationsIdStatusGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationDestinationsIdStatusGet'
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationDestinationsIdStatusGetRequest($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdStatusGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AccountNotificationDestinationsIdStatusGet'
            );
        }


        $resourcePath = '/api/v1/account/notification-destinations/{id}/status';
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
     * Operation apiV1AccountNotificationDestinationsIdTestPost
     *
     * Send a test message to a destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationDestinationsIdTestPost($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'][0])
    {
        list($response) = $this->apiV1AccountNotificationDestinationsIdTestPostWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdTestPostWithHttpInfo
     *
     * Send a test message to a destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationDestinationsIdTestPostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'][0])
    {
        $request = $this->apiV1AccountNotificationDestinationsIdTestPostRequest($id, $contentType);

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
     * Operation apiV1AccountNotificationDestinationsIdTestPostAsync
     *
     * Send a test message to a destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdTestPostAsync($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'][0])
    {
        return $this->apiV1AccountNotificationDestinationsIdTestPostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationDestinationsIdTestPostAsyncWithHttpInfo
     *
     * Send a test message to a destination.
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsIdTestPostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationDestinationsIdTestPostRequest($id, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationDestinationsIdTestPost'
     *
     * @param  int $id Destination ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationDestinationsIdTestPostRequest($id, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsIdTestPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AccountNotificationDestinationsIdTestPost'
            );
        }


        $resourcePath = '/api/v1/account/notification-destinations/{id}/test';
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
     * Operation apiV1AccountNotificationDestinationsPost
     *
     * Add a notification destination.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest $api_v1_account_notification_destinations_post_request api_v1_account_notification_destinations_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return |\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationDestinationsPost($api_v1_account_notification_destinations_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsPost'][0])
    {
        list($response) = $this->apiV1AccountNotificationDestinationsPostWithHttpInfo($api_v1_account_notification_destinations_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationDestinationsPostWithHttpInfo
     *
     * Add a notification destination.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest $api_v1_account_notification_destinations_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of |\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationDestinationsPostWithHttpInfo($api_v1_account_notification_destinations_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsPost'][0])
    {
        $request = $this->apiV1AccountNotificationDestinationsPostRequest($api_v1_account_notification_destinations_post_request, $contentType);

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
     * Operation apiV1AccountNotificationDestinationsPostAsync
     *
     * Add a notification destination.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest $api_v1_account_notification_destinations_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsPostAsync($api_v1_account_notification_destinations_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsPost'][0])
    {
        return $this->apiV1AccountNotificationDestinationsPostAsyncWithHttpInfo($api_v1_account_notification_destinations_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationDestinationsPostAsyncWithHttpInfo
     *
     * Add a notification destination.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest $api_v1_account_notification_destinations_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationDestinationsPostAsyncWithHttpInfo($api_v1_account_notification_destinations_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationDestinationsPostRequest($api_v1_account_notification_destinations_post_request, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationDestinationsPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationDestinationsPostRequest $api_v1_account_notification_destinations_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationDestinationsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationDestinationsPostRequest($api_v1_account_notification_destinations_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationDestinationsPost'][0])
    {

        // verify the required parameter 'api_v1_account_notification_destinations_post_request' is set
        if ($api_v1_account_notification_destinations_post_request === null || (is_array($api_v1_account_notification_destinations_post_request) && count($api_v1_account_notification_destinations_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_account_notification_destinations_post_request when calling apiV1AccountNotificationDestinationsPost'
            );
        }


        $resourcePath = '/api/v1/account/notification-destinations';
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
        if (isset($api_v1_account_notification_destinations_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_account_notification_destinations_post_request));
            } else {
                $httpBody = $api_v1_account_notification_destinations_post_request;
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
     * Operation apiV1AccountNotificationPreferencesGet
     *
     * Per-event channel preferences.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationPreferencesGet(string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesGet'][0])
    {
        list($response) = $this->apiV1AccountNotificationPreferencesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationPreferencesGetWithHttpInfo
     *
     * Per-event channel preferences.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationPreferencesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesGet'][0])
    {
        $request = $this->apiV1AccountNotificationPreferencesGetRequest($contentType);

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
     * Operation apiV1AccountNotificationPreferencesGetAsync
     *
     * Per-event channel preferences.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationPreferencesGetAsync(string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesGet'][0])
    {
        return $this->apiV1AccountNotificationPreferencesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationPreferencesGetAsyncWithHttpInfo
     *
     * Per-event channel preferences.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationPreferencesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationPreferencesGetRequest($contentType);

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
     * Create request for operation 'apiV1AccountNotificationPreferencesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationPreferencesGetRequest(string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesGet'][0])
    {


        $resourcePath = '/api/v1/account/notification-preferences';
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
     * Operation apiV1AccountNotificationPreferencesPut
     *
     * Save channel preferences.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest $api_v1_account_notification_preferences_put_request api_v1_account_notification_preferences_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
     */
    public function apiV1AccountNotificationPreferencesPut($api_v1_account_notification_preferences_put_request, string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesPut'][0])
    {
        list($response) = $this->apiV1AccountNotificationPreferencesPutWithHttpInfo($api_v1_account_notification_preferences_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationPreferencesPutWithHttpInfo
     *
     * Save channel preferences.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest $api_v1_account_notification_preferences_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationPreferencesPutWithHttpInfo($api_v1_account_notification_preferences_put_request, string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesPut'][0])
    {
        $request = $this->apiV1AccountNotificationPreferencesPutRequest($api_v1_account_notification_preferences_put_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AccountNotificationPreferencesPutAsync
     *
     * Save channel preferences.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest $api_v1_account_notification_preferences_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationPreferencesPutAsync($api_v1_account_notification_preferences_put_request, string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesPut'][0])
    {
        return $this->apiV1AccountNotificationPreferencesPutAsyncWithHttpInfo($api_v1_account_notification_preferences_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationPreferencesPutAsyncWithHttpInfo
     *
     * Save channel preferences.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest $api_v1_account_notification_preferences_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationPreferencesPutAsyncWithHttpInfo($api_v1_account_notification_preferences_put_request, string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response';
        $request = $this->apiV1AccountNotificationPreferencesPutRequest($api_v1_account_notification_preferences_put_request, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationPreferencesPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationPreferencesPutRequest $api_v1_account_notification_preferences_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationPreferencesPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationPreferencesPutRequest($api_v1_account_notification_preferences_put_request, string $contentType = self::contentTypes['apiV1AccountNotificationPreferencesPut'][0])
    {

        // verify the required parameter 'api_v1_account_notification_preferences_put_request' is set
        if ($api_v1_account_notification_preferences_put_request === null || (is_array($api_v1_account_notification_preferences_put_request) && count($api_v1_account_notification_preferences_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_account_notification_preferences_put_request when calling apiV1AccountNotificationPreferencesPut'
            );
        }


        $resourcePath = '/api/v1/account/notification-preferences';
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
        if (isset($api_v1_account_notification_preferences_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_account_notification_preferences_put_request));
            } else {
                $httpBody = $api_v1_account_notification_preferences_put_request;
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
     * Operation apiV1AccountNotificationsGet
     *
     * The in-app notification feed.
     *
     * @param  int|null $limit Rows to return. (optional)
     * @param  string|null $unread Pass 1 to return only unread items. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationsGet($limit = null, $unread = null, string $contentType = self::contentTypes['apiV1AccountNotificationsGet'][0])
    {
        list($response) = $this->apiV1AccountNotificationsGetWithHttpInfo($limit, $unread, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationsGetWithHttpInfo
     *
     * The in-app notification feed.
     *
     * @param  int|null $limit Rows to return. (optional)
     * @param  string|null $unread Pass 1 to return only unread items. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationsGetWithHttpInfo($limit = null, $unread = null, string $contentType = self::contentTypes['apiV1AccountNotificationsGet'][0])
    {
        $request = $this->apiV1AccountNotificationsGetRequest($limit, $unread, $contentType);

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
     * Operation apiV1AccountNotificationsGetAsync
     *
     * The in-app notification feed.
     *
     * @param  int|null $limit Rows to return. (optional)
     * @param  string|null $unread Pass 1 to return only unread items. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsGetAsync($limit = null, $unread = null, string $contentType = self::contentTypes['apiV1AccountNotificationsGet'][0])
    {
        return $this->apiV1AccountNotificationsGetAsyncWithHttpInfo($limit, $unread, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationsGetAsyncWithHttpInfo
     *
     * The in-app notification feed.
     *
     * @param  int|null $limit Rows to return. (optional)
     * @param  string|null $unread Pass 1 to return only unread items. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsGetAsyncWithHttpInfo($limit = null, $unread = null, string $contentType = self::contentTypes['apiV1AccountNotificationsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationsGetRequest($limit, $unread, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationsGet'
     *
     * @param  int|null $limit Rows to return. (optional)
     * @param  string|null $unread Pass 1 to return only unread items. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationsGetRequest($limit = null, $unread = null, string $contentType = self::contentTypes['apiV1AccountNotificationsGet'][0])
    {




        $resourcePath = '/api/v1/account/notifications';
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
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $unread,
            'unread', // param base name
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
     * Operation apiV1AccountNotificationsReadPost
     *
     * Mark notifications read.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest $api_v1_account_notifications_read_post_request api_v1_account_notifications_read_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsReadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
     */
    public function apiV1AccountNotificationsReadPost($api_v1_account_notifications_read_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationsReadPost'][0])
    {
        list($response) = $this->apiV1AccountNotificationsReadPostWithHttpInfo($api_v1_account_notifications_read_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationsReadPostWithHttpInfo
     *
     * Mark notifications read.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest $api_v1_account_notifications_read_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsReadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationsReadPostWithHttpInfo($api_v1_account_notifications_read_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationsReadPost'][0])
    {
        $request = $this->apiV1AccountNotificationsReadPostRequest($api_v1_account_notifications_read_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
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
                '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AccountNotificationsReadPostAsync
     *
     * Mark notifications read.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest $api_v1_account_notifications_read_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsReadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsReadPostAsync($api_v1_account_notifications_read_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationsReadPost'][0])
    {
        return $this->apiV1AccountNotificationsReadPostAsyncWithHttpInfo($api_v1_account_notifications_read_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationsReadPostAsyncWithHttpInfo
     *
     * Mark notifications read.
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest $api_v1_account_notifications_read_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsReadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsReadPostAsyncWithHttpInfo($api_v1_account_notifications_read_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationsReadPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response';
        $request = $this->apiV1AccountNotificationsReadPostRequest($api_v1_account_notifications_read_post_request, $contentType);

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
     * Create request for operation 'apiV1AccountNotificationsReadPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AccountNotificationsReadPostRequest $api_v1_account_notifications_read_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsReadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationsReadPostRequest($api_v1_account_notifications_read_post_request, string $contentType = self::contentTypes['apiV1AccountNotificationsReadPost'][0])
    {

        // verify the required parameter 'api_v1_account_notifications_read_post_request' is set
        if ($api_v1_account_notifications_read_post_request === null || (is_array($api_v1_account_notifications_read_post_request) && count($api_v1_account_notifications_read_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_account_notifications_read_post_request when calling apiV1AccountNotificationsReadPost'
            );
        }


        $resourcePath = '/api/v1/account/notifications/read';
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
        if (isset($api_v1_account_notifications_read_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_account_notifications_read_post_request));
            } else {
                $httpBody = $api_v1_account_notifications_read_post_request;
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
     * Operation apiV1AccountNotificationsUnreadCountGet
     *
     * Unread notification count.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsUnreadCountGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1AccountNotificationsUnreadCountGet(string $contentType = self::contentTypes['apiV1AccountNotificationsUnreadCountGet'][0])
    {
        list($response) = $this->apiV1AccountNotificationsUnreadCountGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1AccountNotificationsUnreadCountGetWithHttpInfo
     *
     * Unread notification count.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsUnreadCountGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AccountNotificationsUnreadCountGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationsUnreadCountGet'][0])
    {
        $request = $this->apiV1AccountNotificationsUnreadCountGetRequest($contentType);

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
     * Operation apiV1AccountNotificationsUnreadCountGetAsync
     *
     * Unread notification count.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsUnreadCountGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsUnreadCountGetAsync(string $contentType = self::contentTypes['apiV1AccountNotificationsUnreadCountGet'][0])
    {
        return $this->apiV1AccountNotificationsUnreadCountGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AccountNotificationsUnreadCountGetAsyncWithHttpInfo
     *
     * Unread notification count.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsUnreadCountGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AccountNotificationsUnreadCountGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AccountNotificationsUnreadCountGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1AccountNotificationsUnreadCountGetRequest($contentType);

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
     * Create request for operation 'apiV1AccountNotificationsUnreadCountGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AccountNotificationsUnreadCountGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AccountNotificationsUnreadCountGetRequest(string $contentType = self::contentTypes['apiV1AccountNotificationsUnreadCountGet'][0])
    {


        $resourcePath = '/api/v1/account/notifications/unread-count';
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

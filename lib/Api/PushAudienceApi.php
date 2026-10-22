<?php
/**
 * PushAudienceApi
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
 * PushAudienceApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class PushAudienceApi
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
        'apiV1PublisherPushDeliveryRulesGet' => [
            'application/json',
        ],
        'apiV1PublisherPushDeliveryRulesIdDelete' => [
            'application/json',
        ],
        'apiV1PublisherPushDeliveryRulesIdPut' => [
            'application/json',
        ],
        'apiV1PublisherPushDeliveryRulesPost' => [
            'application/json',
        ],
        'apiV1PublisherPushSegmentsGet' => [
            'application/json',
        ],
        'apiV1PublisherPushSegmentsIdDelete' => [
            'application/json',
        ],
        'apiV1PublisherPushSegmentsIdPut' => [
            'application/json',
        ],
        'apiV1PublisherPushSegmentsPost' => [
            'application/json',
        ],
        'apiV1PublisherPushSetupGet' => [
            'application/json',
        ],
        'apiV1PublisherPushStatsGet' => [
            'application/json',
        ],
        'apiV1PublisherPushSubscribersActionPost' => [
            'application/json',
        ],
        'apiV1PublisherPushSubscribersGet' => [
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
     * Operation apiV1PublisherPushDeliveryRulesGet
     *
     * List standing delivery rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response
     */
    public function apiV1PublisherPushDeliveryRulesGet(string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesGet'][0])
    {
        list($response) = $this->apiV1PublisherPushDeliveryRulesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesGetWithHttpInfo
     *
     * List standing delivery rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushDeliveryRulesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesGet'][0])
    {
        $request = $this->apiV1PublisherPushDeliveryRulesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesGetAsync
     *
     * List standing delivery rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesGetAsync(string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesGet'][0])
    {
        return $this->apiV1PublisherPushDeliveryRulesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesGetAsyncWithHttpInfo
     *
     * List standing delivery rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesGet200Response';
        $request = $this->apiV1PublisherPushDeliveryRulesGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherPushDeliveryRulesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushDeliveryRulesGetRequest(string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesGet'][0])
    {


        $resourcePath = '/api/v1/publisher/push-delivery-rules';
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
     * Operation apiV1PublisherPushDeliveryRulesIdDelete
     *
     * Delete a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1PublisherPushDeliveryRulesIdDelete($id, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'][0])
    {
        list($response) = $this->apiV1PublisherPushDeliveryRulesIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesIdDeleteWithHttpInfo
     *
     * Delete a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushDeliveryRulesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'][0])
    {
        $request = $this->apiV1PublisherPushDeliveryRulesIdDeleteRequest($id, $contentType);

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
     * Operation apiV1PublisherPushDeliveryRulesIdDeleteAsync
     *
     * Delete a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'][0])
    {
        return $this->apiV1PublisherPushDeliveryRulesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesIdDeleteAsyncWithHttpInfo
     *
     * Delete a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1PublisherPushDeliveryRulesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherPushDeliveryRulesIdDelete'
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushDeliveryRulesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherPushDeliveryRulesIdDelete'
            );
        }


        $resourcePath = '/api/v1/publisher/push-delivery-rules/{id}';
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
     * Operation apiV1PublisherPushDeliveryRulesIdPut
     *
     * Update a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest $api_v1_publisher_push_delivery_rules_id_put_request api_v1_publisher_push_delivery_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1PublisherPushDeliveryRulesIdPut($id, $api_v1_publisher_push_delivery_rules_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'][0])
    {
        list($response) = $this->apiV1PublisherPushDeliveryRulesIdPutWithHttpInfo($id, $api_v1_publisher_push_delivery_rules_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesIdPutWithHttpInfo
     *
     * Update a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest $api_v1_publisher_push_delivery_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushDeliveryRulesIdPutWithHttpInfo($id, $api_v1_publisher_push_delivery_rules_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'][0])
    {
        $request = $this->apiV1PublisherPushDeliveryRulesIdPutRequest($id, $api_v1_publisher_push_delivery_rules_id_put_request, $contentType);

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
     * Operation apiV1PublisherPushDeliveryRulesIdPutAsync
     *
     * Update a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest $api_v1_publisher_push_delivery_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesIdPutAsync($id, $api_v1_publisher_push_delivery_rules_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'][0])
    {
        return $this->apiV1PublisherPushDeliveryRulesIdPutAsyncWithHttpInfo($id, $api_v1_publisher_push_delivery_rules_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesIdPutAsyncWithHttpInfo
     *
     * Update a delivery rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest $api_v1_publisher_push_delivery_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesIdPutAsyncWithHttpInfo($id, $api_v1_publisher_push_delivery_rules_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1PublisherPushDeliveryRulesIdPutRequest($id, $api_v1_publisher_push_delivery_rules_id_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherPushDeliveryRulesIdPut'
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesIdPutRequest $api_v1_publisher_push_delivery_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushDeliveryRulesIdPutRequest($id, $api_v1_publisher_push_delivery_rules_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherPushDeliveryRulesIdPut'
            );
        }

        // verify the required parameter 'api_v1_publisher_push_delivery_rules_id_put_request' is set
        if ($api_v1_publisher_push_delivery_rules_id_put_request === null || (is_array($api_v1_publisher_push_delivery_rules_id_put_request) && count($api_v1_publisher_push_delivery_rules_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_push_delivery_rules_id_put_request when calling apiV1PublisherPushDeliveryRulesIdPut'
            );
        }


        $resourcePath = '/api/v1/publisher/push-delivery-rules/{id}';
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
        if (isset($api_v1_publisher_push_delivery_rules_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_push_delivery_rules_id_put_request));
            } else {
                $httpBody = $api_v1_publisher_push_delivery_rules_id_put_request;
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
     * Operation apiV1PublisherPushDeliveryRulesPost
     *
     * Create a delivery rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest $api_v1_publisher_push_delivery_rules_post_request api_v1_publisher_push_delivery_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1PublisherPushDeliveryRulesPost($api_v1_publisher_push_delivery_rules_post_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesPost'][0])
    {
        list($response) = $this->apiV1PublisherPushDeliveryRulesPostWithHttpInfo($api_v1_publisher_push_delivery_rules_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesPostWithHttpInfo
     *
     * Create a delivery rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest $api_v1_publisher_push_delivery_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushDeliveryRulesPostWithHttpInfo($api_v1_publisher_push_delivery_rules_post_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesPost'][0])
    {
        $request = $this->apiV1PublisherPushDeliveryRulesPostRequest($api_v1_publisher_push_delivery_rules_post_request, $contentType);

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
     * Operation apiV1PublisherPushDeliveryRulesPostAsync
     *
     * Create a delivery rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest $api_v1_publisher_push_delivery_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesPostAsync($api_v1_publisher_push_delivery_rules_post_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesPost'][0])
    {
        return $this->apiV1PublisherPushDeliveryRulesPostAsyncWithHttpInfo($api_v1_publisher_push_delivery_rules_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushDeliveryRulesPostAsyncWithHttpInfo
     *
     * Create a delivery rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest $api_v1_publisher_push_delivery_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushDeliveryRulesPostAsyncWithHttpInfo($api_v1_publisher_push_delivery_rules_post_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1PublisherPushDeliveryRulesPostRequest($api_v1_publisher_push_delivery_rules_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherPushDeliveryRulesPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushDeliveryRulesPostRequest $api_v1_publisher_push_delivery_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushDeliveryRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushDeliveryRulesPostRequest($api_v1_publisher_push_delivery_rules_post_request, string $contentType = self::contentTypes['apiV1PublisherPushDeliveryRulesPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_push_delivery_rules_post_request' is set
        if ($api_v1_publisher_push_delivery_rules_post_request === null || (is_array($api_v1_publisher_push_delivery_rules_post_request) && count($api_v1_publisher_push_delivery_rules_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_push_delivery_rules_post_request when calling apiV1PublisherPushDeliveryRulesPost'
            );
        }


        $resourcePath = '/api/v1/publisher/push-delivery-rules';
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
        if (isset($api_v1_publisher_push_delivery_rules_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_push_delivery_rules_post_request));
            } else {
                $httpBody = $api_v1_publisher_push_delivery_rules_post_request;
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
     * Operation apiV1PublisherPushSegmentsGet
     *
     * List saved audience segments.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response
     */
    public function apiV1PublisherPushSegmentsGet(string $contentType = self::contentTypes['apiV1PublisherPushSegmentsGet'][0])
    {
        list($response) = $this->apiV1PublisherPushSegmentsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSegmentsGetWithHttpInfo
     *
     * List saved audience segments.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSegmentsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushSegmentsGet'][0])
    {
        $request = $this->apiV1PublisherPushSegmentsGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushSegmentsGetAsync
     *
     * List saved audience segments.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsGetAsync(string $contentType = self::contentTypes['apiV1PublisherPushSegmentsGet'][0])
    {
        return $this->apiV1PublisherPushSegmentsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSegmentsGetAsyncWithHttpInfo
     *
     * List saved audience segments.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushSegmentsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushSegmentsGet200Response';
        $request = $this->apiV1PublisherPushSegmentsGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherPushSegmentsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSegmentsGetRequest(string $contentType = self::contentTypes['apiV1PublisherPushSegmentsGet'][0])
    {


        $resourcePath = '/api/v1/publisher/push-segments';
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
     * Operation apiV1PublisherPushSegmentsIdDelete
     *
     * Delete an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1PublisherPushSegmentsIdDelete($id, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdDelete'][0])
    {
        list($response) = $this->apiV1PublisherPushSegmentsIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSegmentsIdDeleteWithHttpInfo
     *
     * Delete an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSegmentsIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdDelete'][0])
    {
        $request = $this->apiV1PublisherPushSegmentsIdDeleteRequest($id, $contentType);

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
     * Operation apiV1PublisherPushSegmentsIdDeleteAsync
     *
     * Delete an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdDelete'][0])
    {
        return $this->apiV1PublisherPushSegmentsIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSegmentsIdDeleteAsyncWithHttpInfo
     *
     * Delete an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1PublisherPushSegmentsIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherPushSegmentsIdDelete'
     *
     * @param  int $id Segment ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSegmentsIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherPushSegmentsIdDelete'
            );
        }


        $resourcePath = '/api/v1/publisher/push-segments/{id}';
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
     * Operation apiV1PublisherPushSegmentsIdPut
     *
     * Update an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest $api_v1_publisher_push_segments_id_put_request api_v1_publisher_push_segments_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1PublisherPushSegmentsIdPut($id, $api_v1_publisher_push_segments_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdPut'][0])
    {
        list($response) = $this->apiV1PublisherPushSegmentsIdPutWithHttpInfo($id, $api_v1_publisher_push_segments_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSegmentsIdPutWithHttpInfo
     *
     * Update an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest $api_v1_publisher_push_segments_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSegmentsIdPutWithHttpInfo($id, $api_v1_publisher_push_segments_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdPut'][0])
    {
        $request = $this->apiV1PublisherPushSegmentsIdPutRequest($id, $api_v1_publisher_push_segments_id_put_request, $contentType);

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
     * Operation apiV1PublisherPushSegmentsIdPutAsync
     *
     * Update an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest $api_v1_publisher_push_segments_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsIdPutAsync($id, $api_v1_publisher_push_segments_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdPut'][0])
    {
        return $this->apiV1PublisherPushSegmentsIdPutAsyncWithHttpInfo($id, $api_v1_publisher_push_segments_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSegmentsIdPutAsyncWithHttpInfo
     *
     * Update an audience segment.
     *
     * @param  int $id Segment ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest $api_v1_publisher_push_segments_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsIdPutAsyncWithHttpInfo($id, $api_v1_publisher_push_segments_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1PublisherPushSegmentsIdPutRequest($id, $api_v1_publisher_push_segments_id_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherPushSegmentsIdPut'
     *
     * @param  int $id Segment ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsIdPutRequest $api_v1_publisher_push_segments_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSegmentsIdPutRequest($id, $api_v1_publisher_push_segments_id_put_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherPushSegmentsIdPut'
            );
        }

        // verify the required parameter 'api_v1_publisher_push_segments_id_put_request' is set
        if ($api_v1_publisher_push_segments_id_put_request === null || (is_array($api_v1_publisher_push_segments_id_put_request) && count($api_v1_publisher_push_segments_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_push_segments_id_put_request when calling apiV1PublisherPushSegmentsIdPut'
            );
        }


        $resourcePath = '/api/v1/publisher/push-segments/{id}';
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
        if (isset($api_v1_publisher_push_segments_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_push_segments_id_put_request));
            } else {
                $httpBody = $api_v1_publisher_push_segments_id_put_request;
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
     * Operation apiV1PublisherPushSegmentsPost
     *
     * Create an audience segment.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest $api_v1_publisher_push_segments_post_request api_v1_publisher_push_segments_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1PublisherPushSegmentsPost($api_v1_publisher_push_segments_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsPost'][0])
    {
        list($response) = $this->apiV1PublisherPushSegmentsPostWithHttpInfo($api_v1_publisher_push_segments_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSegmentsPostWithHttpInfo
     *
     * Create an audience segment.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest $api_v1_publisher_push_segments_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSegmentsPostWithHttpInfo($api_v1_publisher_push_segments_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsPost'][0])
    {
        $request = $this->apiV1PublisherPushSegmentsPostRequest($api_v1_publisher_push_segments_post_request, $contentType);

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
     * Operation apiV1PublisherPushSegmentsPostAsync
     *
     * Create an audience segment.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest $api_v1_publisher_push_segments_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsPostAsync($api_v1_publisher_push_segments_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsPost'][0])
    {
        return $this->apiV1PublisherPushSegmentsPostAsyncWithHttpInfo($api_v1_publisher_push_segments_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSegmentsPostAsyncWithHttpInfo
     *
     * Create an audience segment.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest $api_v1_publisher_push_segments_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSegmentsPostAsyncWithHttpInfo($api_v1_publisher_push_segments_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1PublisherPushSegmentsPostRequest($api_v1_publisher_push_segments_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherPushSegmentsPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSegmentsPostRequest $api_v1_publisher_push_segments_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSegmentsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSegmentsPostRequest($api_v1_publisher_push_segments_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSegmentsPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_push_segments_post_request' is set
        if ($api_v1_publisher_push_segments_post_request === null || (is_array($api_v1_publisher_push_segments_post_request) && count($api_v1_publisher_push_segments_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_push_segments_post_request when calling apiV1PublisherPushSegmentsPost'
            );
        }


        $resourcePath = '/api/v1/publisher/push-segments';
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
        if (isset($api_v1_publisher_push_segments_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_push_segments_post_request));
            } else {
                $httpBody = $api_v1_publisher_push_segments_post_request;
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
     * Operation apiV1PublisherPushSetupGet
     *
     * Collector setup facts for the opt-in script.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSetupGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response
     */
    public function apiV1PublisherPushSetupGet(string $contentType = self::contentTypes['apiV1PublisherPushSetupGet'][0])
    {
        list($response) = $this->apiV1PublisherPushSetupGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSetupGetWithHttpInfo
     *
     * Collector setup facts for the opt-in script.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSetupGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSetupGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushSetupGet'][0])
    {
        $request = $this->apiV1PublisherPushSetupGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushSetupGetAsync
     *
     * Collector setup facts for the opt-in script.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSetupGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSetupGetAsync(string $contentType = self::contentTypes['apiV1PublisherPushSetupGet'][0])
    {
        return $this->apiV1PublisherPushSetupGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSetupGetAsyncWithHttpInfo
     *
     * Collector setup facts for the opt-in script.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSetupGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSetupGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherPushSetupGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushSetupGet200Response';
        $request = $this->apiV1PublisherPushSetupGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherPushSetupGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSetupGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSetupGetRequest(string $contentType = self::contentTypes['apiV1PublisherPushSetupGet'][0])
    {


        $resourcePath = '/api/v1/publisher/push-setup';
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
     * Operation apiV1PublisherPushStatsGet
     *
     * Push delivery performance.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response
     */
    public function apiV1PublisherPushStatsGet($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushStatsGet'][0])
    {
        list($response) = $this->apiV1PublisherPushStatsGetWithHttpInfo($days, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushStatsGetWithHttpInfo
     *
     * Push delivery performance.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushStatsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushStatsGetWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushStatsGet'][0])
    {
        $request = $this->apiV1PublisherPushStatsGetRequest($days, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushStatsGetAsync
     *
     * Push delivery performance.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushStatsGetAsync($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushStatsGet'][0])
    {
        return $this->apiV1PublisherPushStatsGetAsyncWithHttpInfo($days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushStatsGetAsyncWithHttpInfo
     *
     * Push delivery performance.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushStatsGetAsyncWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushStatsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushStatsGet200Response';
        $request = $this->apiV1PublisherPushStatsGetRequest($days, $contentType);

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
     * Create request for operation 'apiV1PublisherPushStatsGet'
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushStatsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushStatsGetRequest($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushStatsGet'][0])
    {

        if ($days !== null && $days > 365) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling PushAudienceApi.apiV1PublisherPushStatsGet, must be smaller than or equal to 365.');
        }
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling PushAudienceApi.apiV1PublisherPushStatsGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/publisher/push-stats';
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
     * Operation apiV1PublisherPushSubscribersActionPost
     *
     * Activate, deactivate or delete subscribers in bulk.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest $api_v1_publisher_push_subscribers_action_post_request api_v1_publisher_push_subscribers_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response
     */
    public function apiV1PublisherPushSubscribersActionPost($api_v1_publisher_push_subscribers_action_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersActionPost'][0])
    {
        list($response) = $this->apiV1PublisherPushSubscribersActionPostWithHttpInfo($api_v1_publisher_push_subscribers_action_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSubscribersActionPostWithHttpInfo
     *
     * Activate, deactivate or delete subscribers in bulk.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest $api_v1_publisher_push_subscribers_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSubscribersActionPostWithHttpInfo($api_v1_publisher_push_subscribers_action_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersActionPost'][0])
    {
        $request = $this->apiV1PublisherPushSubscribersActionPostRequest($api_v1_publisher_push_subscribers_action_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushSubscribersActionPostAsync
     *
     * Activate, deactivate or delete subscribers in bulk.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest $api_v1_publisher_push_subscribers_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSubscribersActionPostAsync($api_v1_publisher_push_subscribers_action_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersActionPost'][0])
    {
        return $this->apiV1PublisherPushSubscribersActionPostAsyncWithHttpInfo($api_v1_publisher_push_subscribers_action_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSubscribersActionPostAsyncWithHttpInfo
     *
     * Activate, deactivate or delete subscribers in bulk.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest $api_v1_publisher_push_subscribers_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSubscribersActionPostAsyncWithHttpInfo($api_v1_publisher_push_subscribers_action_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersActionPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response';
        $request = $this->apiV1PublisherPushSubscribersActionPostRequest($api_v1_publisher_push_subscribers_action_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherPushSubscribersActionPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPostRequest $api_v1_publisher_push_subscribers_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSubscribersActionPostRequest($api_v1_publisher_push_subscribers_action_post_request, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersActionPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_push_subscribers_action_post_request' is set
        if ($api_v1_publisher_push_subscribers_action_post_request === null || (is_array($api_v1_publisher_push_subscribers_action_post_request) && count($api_v1_publisher_push_subscribers_action_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_push_subscribers_action_post_request when calling apiV1PublisherPushSubscribersActionPost'
            );
        }


        $resourcePath = '/api/v1/publisher/push-subscribers/action';
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
        if (isset($api_v1_publisher_push_subscribers_action_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_push_subscribers_action_post_request));
            } else {
                $httpBody = $api_v1_publisher_push_subscribers_action_post_request;
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
     * Operation apiV1PublisherPushSubscribersGet
     *
     * List captured push subscribers.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response
     */
    public function apiV1PublisherPushSubscribersGet($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersGet'][0])
    {
        list($response) = $this->apiV1PublisherPushSubscribersGetWithHttpInfo($days, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherPushSubscribersGetWithHttpInfo
     *
     * List captured push subscribers.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherPushSubscribersGetWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersGet'][0])
    {
        $request = $this->apiV1PublisherPushSubscribersGetRequest($days, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherPushSubscribersGetAsync
     *
     * List captured push subscribers.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSubscribersGetAsync($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersGet'][0])
    {
        return $this->apiV1PublisherPushSubscribersGetAsyncWithHttpInfo($days, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherPushSubscribersGetAsyncWithHttpInfo
     *
     * List captured push subscribers.
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherPushSubscribersGetAsyncWithHttpInfo($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersGet200Response';
        $request = $this->apiV1PublisherPushSubscribersGetRequest($days, $contentType);

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
     * Create request for operation 'apiV1PublisherPushSubscribersGet'
     *
     * @param  int|null $days Trailing window in days. Default 30, capped at 365. (optional, default to 30)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherPushSubscribersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherPushSubscribersGetRequest($days = 30, string $contentType = self::contentTypes['apiV1PublisherPushSubscribersGet'][0])
    {

        if ($days !== null && $days > 365) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling PushAudienceApi.apiV1PublisherPushSubscribersGet, must be smaller than or equal to 365.');
        }
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException('invalid value for "$days" when calling PushAudienceApi.apiV1PublisherPushSubscribersGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/publisher/push-subscribers';
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

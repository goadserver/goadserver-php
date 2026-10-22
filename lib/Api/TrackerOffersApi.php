<?php
/**
 * TrackerOffersApi
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
 * TrackerOffersApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class TrackerOffersApi
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
        'apiV1TrackerOffersBulkPost' => [
            'application/json',
        ],
        'apiV1TrackerOffersGet' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdGet' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdPut' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdUrlsPost' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdUrlsUrlIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerOffersIdUrlsUrlIdPut' => [
            'application/json',
        ],
        'apiV1TrackerOffersPost' => [
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
     * Operation apiV1TrackerOffersBulkPost
     *
     * Pause, activate or delete many offers.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest $api_v1_tracker_offers_bulk_post_request api_v1_tracker_offers_bulk_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersBulkPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response
     */
    public function apiV1TrackerOffersBulkPost($api_v1_tracker_offers_bulk_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersBulkPost'][0])
    {
        list($response) = $this->apiV1TrackerOffersBulkPostWithHttpInfo($api_v1_tracker_offers_bulk_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersBulkPostWithHttpInfo
     *
     * Pause, activate or delete many offers.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest $api_v1_tracker_offers_bulk_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersBulkPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersBulkPostWithHttpInfo($api_v1_tracker_offers_bulk_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersBulkPost'][0])
    {
        $request = $this->apiV1TrackerOffersBulkPostRequest($api_v1_tracker_offers_bulk_post_request, $contentType);

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
     * Operation apiV1TrackerOffersBulkPostAsync
     *
     * Pause, activate or delete many offers.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest $api_v1_tracker_offers_bulk_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersBulkPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersBulkPostAsync($api_v1_tracker_offers_bulk_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersBulkPost'][0])
    {
        return $this->apiV1TrackerOffersBulkPostAsyncWithHttpInfo($api_v1_tracker_offers_bulk_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersBulkPostAsyncWithHttpInfo
     *
     * Pause, activate or delete many offers.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest $api_v1_tracker_offers_bulk_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersBulkPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersBulkPostAsyncWithHttpInfo($api_v1_tracker_offers_bulk_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersBulkPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response';
        $request = $this->apiV1TrackerOffersBulkPostRequest($api_v1_tracker_offers_bulk_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersBulkPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest $api_v1_tracker_offers_bulk_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersBulkPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersBulkPostRequest($api_v1_tracker_offers_bulk_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersBulkPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_offers_bulk_post_request' is set
        if ($api_v1_tracker_offers_bulk_post_request === null || (is_array($api_v1_tracker_offers_bulk_post_request) && count($api_v1_tracker_offers_bulk_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_offers_bulk_post_request when calling apiV1TrackerOffersBulkPost'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/bulk';
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
        if (isset($api_v1_tracker_offers_bulk_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_offers_bulk_post_request));
            } else {
                $httpBody = $api_v1_tracker_offers_bulk_post_request;
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
     * Operation apiV1TrackerOffersGet
     *
     * List tracker offers.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerOffersGet200Response
     */
    public function apiV1TrackerOffersGet(string $contentType = self::contentTypes['apiV1TrackerOffersGet'][0])
    {
        list($response) = $this->apiV1TrackerOffersGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersGetWithHttpInfo
     *
     * List tracker offers.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerOffersGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersGetWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerOffersGet'][0])
    {
        $request = $this->apiV1TrackerOffersGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerOffersGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerOffersGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerOffersGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerOffersGetAsync
     *
     * List tracker offers.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersGetAsync(string $contentType = self::contentTypes['apiV1TrackerOffersGet'][0])
    {
        return $this->apiV1TrackerOffersGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersGetAsyncWithHttpInfo
     *
     * List tracker offers.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerOffersGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerOffersGet200Response';
        $request = $this->apiV1TrackerOffersGetRequest($contentType);

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
     * Create request for operation 'apiV1TrackerOffersGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersGetRequest(string $contentType = self::contentTypes['apiV1TrackerOffersGet'][0])
    {


        $resourcePath = '/api/v1/tracker/offers';
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
     * Operation apiV1TrackerOffersIdDelete
     *
     * Delete an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerOffersIdDelete($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdDeleteWithHttpInfo
     *
     * Delete an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdDelete'][0])
    {
        $request = $this->apiV1TrackerOffersIdDeleteRequest($id, $contentType);

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
     * Operation apiV1TrackerOffersIdDeleteAsync
     *
     * Delete an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdDelete'][0])
    {
        return $this->apiV1TrackerOffersIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdDeleteAsyncWithHttpInfo
     *
     * Delete an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerOffersIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdDelete'
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}';
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
     * Operation apiV1TrackerOffersIdGet
     *
     * Get one offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response
     */
    public function apiV1TrackerOffersIdGet($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdGet'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdGetWithHttpInfo
     *
     * Get one offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdGet'][0])
    {
        $request = $this->apiV1TrackerOffersIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerOffersIdGetAsync
     *
     * Get one offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdGet'][0])
    {
        return $this->apiV1TrackerOffersIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdGetAsyncWithHttpInfo
     *
     * Get one offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response';
        $request = $this->apiV1TrackerOffersIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdGet'
     *
     * @param  int $id Offer ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerOffersIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdGet'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}';
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
     * Operation apiV1TrackerOffersIdPut
     *
     * Update an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest $api_v1_tracker_offers_id_put_request api_v1_tracker_offers_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerOffersIdPut($id, $api_v1_tracker_offers_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdPut'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdPutWithHttpInfo($id, $api_v1_tracker_offers_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdPutWithHttpInfo
     *
     * Update an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest $api_v1_tracker_offers_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdPutWithHttpInfo($id, $api_v1_tracker_offers_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdPut'][0])
    {
        $request = $this->apiV1TrackerOffersIdPutRequest($id, $api_v1_tracker_offers_id_put_request, $contentType);

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
     * Operation apiV1TrackerOffersIdPutAsync
     *
     * Update an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest $api_v1_tracker_offers_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdPutAsync($id, $api_v1_tracker_offers_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdPut'][0])
    {
        return $this->apiV1TrackerOffersIdPutAsyncWithHttpInfo($id, $api_v1_tracker_offers_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdPutAsyncWithHttpInfo
     *
     * Update an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest $api_v1_tracker_offers_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdPutAsyncWithHttpInfo($id, $api_v1_tracker_offers_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerOffersIdPutRequest($id, $api_v1_tracker_offers_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdPut'
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest $api_v1_tracker_offers_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdPutRequest($id, $api_v1_tracker_offers_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_offers_id_put_request' is set
        if ($api_v1_tracker_offers_id_put_request === null || (is_array($api_v1_tracker_offers_id_put_request) && count($api_v1_tracker_offers_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_offers_id_put_request when calling apiV1TrackerOffersIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}';
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
        if (isset($api_v1_tracker_offers_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_offers_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_offers_id_put_request;
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
     * Operation apiV1TrackerOffersIdUrlsPost
     *
     * Add a landing URL to an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest $api_v1_tracker_offers_id_urls_post_request api_v1_tracker_offers_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerOffersIdUrlsPost($id, $api_v1_tracker_offers_id_urls_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsPost'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdUrlsPostWithHttpInfo($id, $api_v1_tracker_offers_id_urls_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsPostWithHttpInfo
     *
     * Add a landing URL to an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest $api_v1_tracker_offers_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdUrlsPostWithHttpInfo($id, $api_v1_tracker_offers_id_urls_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsPost'][0])
    {
        $request = $this->apiV1TrackerOffersIdUrlsPostRequest($id, $api_v1_tracker_offers_id_urls_post_request, $contentType);

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
     * Operation apiV1TrackerOffersIdUrlsPostAsync
     *
     * Add a landing URL to an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest $api_v1_tracker_offers_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsPostAsync($id, $api_v1_tracker_offers_id_urls_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsPost'][0])
    {
        return $this->apiV1TrackerOffersIdUrlsPostAsyncWithHttpInfo($id, $api_v1_tracker_offers_id_urls_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsPostAsyncWithHttpInfo
     *
     * Add a landing URL to an offer.
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest $api_v1_tracker_offers_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsPostAsyncWithHttpInfo($id, $api_v1_tracker_offers_id_urls_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerOffersIdUrlsPostRequest($id, $api_v1_tracker_offers_id_urls_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdUrlsPost'
     *
     * @param  int $id Offer ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest $api_v1_tracker_offers_id_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdUrlsPostRequest($id, $api_v1_tracker_offers_id_urls_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdUrlsPost'
            );
        }

        // verify the required parameter 'api_v1_tracker_offers_id_urls_post_request' is set
        if ($api_v1_tracker_offers_id_urls_post_request === null || (is_array($api_v1_tracker_offers_id_urls_post_request) && count($api_v1_tracker_offers_id_urls_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_offers_id_urls_post_request when calling apiV1TrackerOffersIdUrlsPost'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}/urls';
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
        if (isset($api_v1_tracker_offers_id_urls_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_offers_id_urls_post_request));
            } else {
                $httpBody = $api_v1_tracker_offers_id_urls_post_request;
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
     * Operation apiV1TrackerOffersIdUrlsUrlIdDelete
     *
     * Delete one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerOffersIdUrlsUrlIdDelete($id, $url_id, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdUrlsUrlIdDeleteWithHttpInfo($id, $url_id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsUrlIdDeleteWithHttpInfo
     *
     * Delete one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdUrlsUrlIdDeleteWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'][0])
    {
        $request = $this->apiV1TrackerOffersIdUrlsUrlIdDeleteRequest($id, $url_id, $contentType);

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
     * Operation apiV1TrackerOffersIdUrlsUrlIdDeleteAsync
     *
     * Delete one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsUrlIdDeleteAsync($id, $url_id, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'][0])
    {
        return $this->apiV1TrackerOffersIdUrlsUrlIdDeleteAsyncWithHttpInfo($id, $url_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsUrlIdDeleteAsyncWithHttpInfo
     *
     * Delete one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsUrlIdDeleteAsyncWithHttpInfo($id, $url_id, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerOffersIdUrlsUrlIdDeleteRequest($id, $url_id, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdUrlsUrlIdDelete'
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdUrlsUrlIdDeleteRequest($id, $url_id, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdUrlsUrlIdDelete'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1TrackerOffersIdUrlsUrlIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}/urls/{urlId}';
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsUrlIdPut
     *
     * Update one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest $api_v1_tracker_offers_id_urls_url_id_put_request api_v1_tracker_offers_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerOffersIdUrlsUrlIdPut($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'][0])
    {
        list($response) = $this->apiV1TrackerOffersIdUrlsUrlIdPutWithHttpInfo($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsUrlIdPutWithHttpInfo
     *
     * Update one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest $api_v1_tracker_offers_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersIdUrlsUrlIdPutWithHttpInfo($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'][0])
    {
        $request = $this->apiV1TrackerOffersIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, $contentType);

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
     * Operation apiV1TrackerOffersIdUrlsUrlIdPutAsync
     *
     * Update one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest $api_v1_tracker_offers_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsUrlIdPutAsync($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'][0])
    {
        return $this->apiV1TrackerOffersIdUrlsUrlIdPutAsyncWithHttpInfo($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersIdUrlsUrlIdPutAsyncWithHttpInfo
     *
     * Update one landing URL.
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest $api_v1_tracker_offers_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersIdUrlsUrlIdPutAsyncWithHttpInfo($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerOffersIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersIdUrlsUrlIdPut'
     *
     * @param  int $id Offer ID. (required)
     * @param  int $url_id URL row ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest $api_v1_tracker_offers_id_urls_url_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersIdUrlsUrlIdPutRequest($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request, string $contentType = self::contentTypes['apiV1TrackerOffersIdUrlsUrlIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerOffersIdUrlsUrlIdPut'
            );
        }

        // verify the required parameter 'url_id' is set
        if ($url_id === null || (is_array($url_id) && count($url_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $url_id when calling apiV1TrackerOffersIdUrlsUrlIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_offers_id_urls_url_id_put_request' is set
        if ($api_v1_tracker_offers_id_urls_url_id_put_request === null || (is_array($api_v1_tracker_offers_id_urls_url_id_put_request) && count($api_v1_tracker_offers_id_urls_url_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_offers_id_urls_url_id_put_request when calling apiV1TrackerOffersIdUrlsUrlIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/offers/{id}/urls/{urlId}';
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
        if (isset($api_v1_tracker_offers_id_urls_url_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_offers_id_urls_url_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_offers_id_urls_url_id_put_request;
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
     * Operation apiV1TrackerOffersPost
     *
     * Create an offer.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest $api_v1_tracker_offers_post_request api_v1_tracker_offers_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerOffersPost($api_v1_tracker_offers_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersPost'][0])
    {
        list($response) = $this->apiV1TrackerOffersPostWithHttpInfo($api_v1_tracker_offers_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerOffersPostWithHttpInfo
     *
     * Create an offer.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest $api_v1_tracker_offers_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerOffersPostWithHttpInfo($api_v1_tracker_offers_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersPost'][0])
    {
        $request = $this->apiV1TrackerOffersPostRequest($api_v1_tracker_offers_post_request, $contentType);

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
     * Operation apiV1TrackerOffersPostAsync
     *
     * Create an offer.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest $api_v1_tracker_offers_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersPostAsync($api_v1_tracker_offers_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersPost'][0])
    {
        return $this->apiV1TrackerOffersPostAsyncWithHttpInfo($api_v1_tracker_offers_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerOffersPostAsyncWithHttpInfo
     *
     * Create an offer.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest $api_v1_tracker_offers_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerOffersPostAsyncWithHttpInfo($api_v1_tracker_offers_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerOffersPostRequest($api_v1_tracker_offers_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerOffersPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest $api_v1_tracker_offers_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerOffersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerOffersPostRequest($api_v1_tracker_offers_post_request, string $contentType = self::contentTypes['apiV1TrackerOffersPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_offers_post_request' is set
        if ($api_v1_tracker_offers_post_request === null || (is_array($api_v1_tracker_offers_post_request) && count($api_v1_tracker_offers_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_offers_post_request when calling apiV1TrackerOffersPost'
            );
        }


        $resourcePath = '/api/v1/tracker/offers';
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
        if (isset($api_v1_tracker_offers_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_offers_post_request));
            } else {
                $httpBody = $api_v1_tracker_offers_post_request;
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

<?php
/**
 * SSPCampaignsApi
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
 * SSPCampaignsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class SSPCampaignsApi
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
        'apiV1PublisherSspCampaignsGet' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdClonePost' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdGet' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdPut' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdTestRtbGet' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdTestRtbPost' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdToggleDeletePut' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdTogglePausePut' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsIdWlRonCampaignsGet' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsPost' => [
            'application/json',
        ],
        'apiV1PublisherSspCampaignsSetupDataGet' => [
            'application/json',
        ],
        'apiV1PublisherSspFiltersGet' => [
            'application/json',
        ],
        'apiV1PublisherSspNetworksGet' => [
            'application/json',
        ],
        'apiV1PublisherSspSellerDisclosurePut' => [
            'application/json',
        ],
        'apiV1PublisherSspSitesIdAdsTxtCheckPost' => [
            'application/json',
        ],
        'apiV1PublisherSspSitesIdAdsTxtStatusGet' => [
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
     * Operation apiV1PublisherSspCampaignsGet
     *
     * List SSP campaigns (feeds).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsGet(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsGet'][0])
    {
        $this->apiV1PublisherSspCampaignsGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsGetWithHttpInfo
     *
     * List SSP campaigns (feeds).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsGet'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsGetRequest($contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherSspCampaignsGetAsync
     *
     * List SSP campaigns (feeds).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsGetAsync(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsGet'][0])
    {
        return $this->apiV1PublisherSspCampaignsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsGetAsyncWithHttpInfo
     *
     * List SSP campaigns (feeds).
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsGetRequest(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsGet'][0])
    {


        $resourcePath = '/api/v1/publisher/ssp/campaigns';
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
     * Operation apiV1PublisherSspCampaignsIdClonePost
     *
     * Clone an SSP campaign.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdClonePost($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'][0])
    {
        $this->apiV1PublisherSspCampaignsIdClonePostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdClonePostWithHttpInfo
     *
     * Clone an SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdClonePostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdClonePostRequest($id, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdClonePostAsync
     *
     * Clone an SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdClonePostAsync($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdClonePostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdClonePostAsyncWithHttpInfo
     *
     * Clone an SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdClonePostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdClonePostRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdClonePost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdClonePostRequest($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdClonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdClonePost'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/clone';
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
     * Operation apiV1PublisherSspCampaignsIdGet
     *
     * Get one SSP campaign.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdGet($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdGet'][0])
    {
        $this->apiV1PublisherSspCampaignsIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdGetWithHttpInfo
     *
     * Get one SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdGet'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdGetRequest($id, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdGetAsync
     *
     * Get one SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdGet'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdGetAsyncWithHttpInfo
     *
     * Get one SSP campaign.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdGet'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}';
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
     * Operation apiV1PublisherSspCampaignsIdPut
     *
     * Update an SSP campaign.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdPut($id, $ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdPut'][0])
    {
        $this->apiV1PublisherSspCampaignsIdPutWithHttpInfo($id, $ssp_campaign_write, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdPutWithHttpInfo
     *
     * Update an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdPutWithHttpInfo($id, $ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdPut'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdPutRequest($id, $ssp_campaign_write, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdPutAsync
     *
     * Update an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdPutAsync($id, $ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdPut'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdPutAsyncWithHttpInfo($id, $ssp_campaign_write, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdPutAsyncWithHttpInfo
     *
     * Update an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdPutAsyncWithHttpInfo($id, $ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdPutRequest($id, $ssp_campaign_write, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdPutRequest($id, $ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdPut'
            );
        }

        // verify the required parameter 'ssp_campaign_write' is set
        if ($ssp_campaign_write === null || (is_array($ssp_campaign_write) && count($ssp_campaign_write) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ssp_campaign_write when calling apiV1PublisherSspCampaignsIdPut'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}';
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
        if (isset($ssp_campaign_write)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($ssp_campaign_write));
            } else {
                $httpBody = $ssp_campaign_write;
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
     * Operation apiV1PublisherSspCampaignsIdTestRtbGet
     *
     * Context for an RTB test request.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdTestRtbGet($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'][0])
    {
        $this->apiV1PublisherSspCampaignsIdTestRtbGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTestRtbGetWithHttpInfo
     *
     * Context for an RTB test request.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdTestRtbGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdTestRtbGetRequest($id, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdTestRtbGetAsync
     *
     * Context for an RTB test request.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTestRtbGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdTestRtbGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTestRtbGetAsyncWithHttpInfo
     *
     * Context for an RTB test request.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTestRtbGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdTestRtbGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdTestRtbGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdTestRtbGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdTestRtbGet'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/test-rtb';
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
     * Operation apiV1PublisherSspCampaignsIdTestRtbPost
     *
     * Send one test bid request to the feed&#39;s endpoint.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest|null $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request api_v1_publisher_ssp_campaigns_id_test_rtb_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdTestRtbPost($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'][0])
    {
        $this->apiV1PublisherSspCampaignsIdTestRtbPostWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTestRtbPostWithHttpInfo
     *
     * Send one test bid request to the feed&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest|null $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdTestRtbPostWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdTestRtbPostRequest($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdTestRtbPostAsync
     *
     * Send one test bid request to the feed&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest|null $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTestRtbPostAsync($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdTestRtbPostAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTestRtbPostAsyncWithHttpInfo
     *
     * Send one test bid request to the feed&#39;s endpoint.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest|null $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTestRtbPostAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdTestRtbPostRequest($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdTestRtbPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTestRtbPostRequest|null $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdTestRtbPostRequest($id, $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTestRtbPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdTestRtbPost'
            );
        }



        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/test-rtb';
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
        if (isset($api_v1_publisher_ssp_campaigns_id_test_rtb_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_ssp_campaigns_id_test_rtb_post_request));
            } else {
                $httpBody = $api_v1_publisher_ssp_campaigns_id_test_rtb_post_request;
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
     * Operation apiV1PublisherSspCampaignsIdToggleDeletePut
     *
     * Delete or restore an SSP campaign.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdToggleDeletePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'][0])
    {
        $this->apiV1PublisherSspCampaignsIdToggleDeletePutWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdToggleDeletePutWithHttpInfo
     *
     * Delete or restore an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdToggleDeletePutWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdToggleDeletePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdToggleDeletePutAsync
     *
     * Delete or restore an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdToggleDeletePutAsync($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdToggleDeletePutAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdToggleDeletePutAsyncWithHttpInfo
     *
     * Delete or restore an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdToggleDeletePutAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdToggleDeletePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdToggleDeletePut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdToggleDeletePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdToggleDeletePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdToggleDeletePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdToggleDeletePut'
            );
        }

        // verify the required parameter 'api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request' is set
        if ($api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request === null || (is_array($api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request) && count($api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request when calling apiV1PublisherSspCampaignsIdToggleDeletePut'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/toggle-delete';
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
        if (isset($api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request));
            } else {
                $httpBody = $api_v1_publisher_ssp_campaigns_id_toggle_delete_put_request;
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
     * Operation apiV1PublisherSspCampaignsIdTogglePausePut
     *
     * Pause or resume an SSP campaign.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdTogglePausePut($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'][0])
    {
        $this->apiV1PublisherSspCampaignsIdTogglePausePutWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTogglePausePutWithHttpInfo
     *
     * Pause or resume an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdTogglePausePutWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdTogglePausePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdTogglePausePutAsync
     *
     * Pause or resume an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTogglePausePutAsync($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdTogglePausePutAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdTogglePausePutAsyncWithHttpInfo
     *
     * Pause or resume an SSP campaign.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdTogglePausePutAsyncWithHttpInfo($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdTogglePausePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdTogglePausePut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspCampaignsIdTogglePausePutRequest $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdTogglePausePutRequest($id, $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdTogglePausePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdTogglePausePut'
            );
        }

        // verify the required parameter 'api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request' is set
        if ($api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request === null || (is_array($api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request) && count($api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request when calling apiV1PublisherSspCampaignsIdTogglePausePut'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/toggle-pause';
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
        if (isset($api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request));
            } else {
                $httpBody = $api_v1_publisher_ssp_campaigns_id_toggle_pause_put_request;
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
     * Operation apiV1PublisherSspCampaignsIdWlRonCampaignsGet
     *
     * RON campaigns that can be whitelisted on a feed.
     *
     * @param  int $id SSP campaign id, or 0. (required)
     * @param  int|null $adtype Ad type when &#x60;id&#x60; is 0. (optional)
     * @param  int|null $siteid Override the site used for the category match (must be yours). (optional)
     * @param  string|null $search search (optional)
     * @param  string|null $ids Comma-separated currently-selected ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsIdWlRonCampaignsGet($id, $adtype = null, $siteid = null, $search = null, $ids = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'][0])
    {
        $this->apiV1PublisherSspCampaignsIdWlRonCampaignsGetWithHttpInfo($id, $adtype, $siteid, $search, $ids, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdWlRonCampaignsGetWithHttpInfo
     *
     * RON campaigns that can be whitelisted on a feed.
     *
     * @param  int $id SSP campaign id, or 0. (required)
     * @param  int|null $adtype Ad type when &#x60;id&#x60; is 0. (optional)
     * @param  int|null $siteid Override the site used for the category match (must be yours). (optional)
     * @param  string|null $search (optional)
     * @param  string|null $ids Comma-separated currently-selected ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsIdWlRonCampaignsGetWithHttpInfo($id, $adtype = null, $siteid = null, $search = null, $ids = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsIdWlRonCampaignsGetRequest($id, $adtype, $siteid, $search, $ids, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsIdWlRonCampaignsGetAsync
     *
     * RON campaigns that can be whitelisted on a feed.
     *
     * @param  int $id SSP campaign id, or 0. (required)
     * @param  int|null $adtype Ad type when &#x60;id&#x60; is 0. (optional)
     * @param  int|null $siteid Override the site used for the category match (must be yours). (optional)
     * @param  string|null $search (optional)
     * @param  string|null $ids Comma-separated currently-selected ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdWlRonCampaignsGetAsync($id, $adtype = null, $siteid = null, $search = null, $ids = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'][0])
    {
        return $this->apiV1PublisherSspCampaignsIdWlRonCampaignsGetAsyncWithHttpInfo($id, $adtype, $siteid, $search, $ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsIdWlRonCampaignsGetAsyncWithHttpInfo
     *
     * RON campaigns that can be whitelisted on a feed.
     *
     * @param  int $id SSP campaign id, or 0. (required)
     * @param  int|null $adtype Ad type when &#x60;id&#x60; is 0. (optional)
     * @param  int|null $siteid Override the site used for the category match (must be yours). (optional)
     * @param  string|null $search (optional)
     * @param  string|null $ids Comma-separated currently-selected ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsIdWlRonCampaignsGetAsyncWithHttpInfo($id, $adtype = null, $siteid = null, $search = null, $ids = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsIdWlRonCampaignsGetRequest($id, $adtype, $siteid, $search, $ids, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsIdWlRonCampaignsGet'
     *
     * @param  int $id SSP campaign id, or 0. (required)
     * @param  int|null $adtype Ad type when &#x60;id&#x60; is 0. (optional)
     * @param  int|null $siteid Override the site used for the category match (must be yours). (optional)
     * @param  string|null $search (optional)
     * @param  string|null $ids Comma-separated currently-selected ids. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsIdWlRonCampaignsGetRequest($id, $adtype = null, $siteid = null, $search = null, $ids = null, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsIdWlRonCampaignsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspCampaignsIdWlRonCampaignsGet'
            );
        }






        $resourcePath = '/api/v1/publisher/ssp/campaigns/{id}/wl-ron-campaigns';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adtype,
            'adtype', // param base name
            'integer', // openApiType
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
            $search,
            'search', // param base name
            'string', // openApiType
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
     * Operation apiV1PublisherSspCampaignsPost
     *
     * Create an SSP campaign.
     *
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsPost($ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsPost'][0])
    {
        $this->apiV1PublisherSspCampaignsPostWithHttpInfo($ssp_campaign_write, $contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsPostWithHttpInfo
     *
     * Create an SSP campaign.
     *
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsPostWithHttpInfo($ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsPost'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsPostRequest($ssp_campaign_write, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsPostAsync
     *
     * Create an SSP campaign.
     *
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsPostAsync($ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsPost'][0])
    {
        return $this->apiV1PublisherSspCampaignsPostAsyncWithHttpInfo($ssp_campaign_write, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsPostAsyncWithHttpInfo
     *
     * Create an SSP campaign.
     *
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsPostAsyncWithHttpInfo($ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsPostRequest($ssp_campaign_write, $contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsPost'
     *
     * @param  \GoAdServerApi\Model\SspCampaignWrite $ssp_campaign_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsPostRequest($ssp_campaign_write, string $contentType = self::contentTypes['apiV1PublisherSspCampaignsPost'][0])
    {

        // verify the required parameter 'ssp_campaign_write' is set
        if ($ssp_campaign_write === null || (is_array($ssp_campaign_write) && count($ssp_campaign_write) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ssp_campaign_write when calling apiV1PublisherSspCampaignsPost'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/campaigns';
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
        if (isset($ssp_campaign_write)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($ssp_campaign_write));
            } else {
                $httpBody = $ssp_campaign_write;
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
     * Operation apiV1PublisherSspCampaignsSetupDataGet
     *
     * Lookups for the SSP campaign form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspCampaignsSetupDataGet(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'][0])
    {
        $this->apiV1PublisherSspCampaignsSetupDataGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSspCampaignsSetupDataGetWithHttpInfo
     *
     * Lookups for the SSP campaign form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspCampaignsSetupDataGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'][0])
    {
        $request = $this->apiV1PublisherSspCampaignsSetupDataGetRequest($contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspCampaignsSetupDataGetAsync
     *
     * Lookups for the SSP campaign form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsSetupDataGetAsync(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'][0])
    {
        return $this->apiV1PublisherSspCampaignsSetupDataGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspCampaignsSetupDataGetAsyncWithHttpInfo
     *
     * Lookups for the SSP campaign form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspCampaignsSetupDataGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspCampaignsSetupDataGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSspCampaignsSetupDataGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspCampaignsSetupDataGetRequest(string $contentType = self::contentTypes['apiV1PublisherSspCampaignsSetupDataGet'][0])
    {


        $resourcePath = '/api/v1/publisher/ssp/campaigns/setup-data';
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
     * Operation apiV1PublisherSspFiltersGet
     *
     * Filter values for the SSP campaign list.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspFiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspFiltersGet(string $contentType = self::contentTypes['apiV1PublisherSspFiltersGet'][0])
    {
        $this->apiV1PublisherSspFiltersGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSspFiltersGetWithHttpInfo
     *
     * Filter values for the SSP campaign list.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspFiltersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspFiltersGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspFiltersGet'][0])
    {
        $request = $this->apiV1PublisherSspFiltersGetRequest($contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspFiltersGetAsync
     *
     * Filter values for the SSP campaign list.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspFiltersGetAsync(string $contentType = self::contentTypes['apiV1PublisherSspFiltersGet'][0])
    {
        return $this->apiV1PublisherSspFiltersGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspFiltersGetAsyncWithHttpInfo
     *
     * Filter values for the SSP campaign list.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspFiltersGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSspFiltersGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspFiltersGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSspFiltersGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspFiltersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspFiltersGetRequest(string $contentType = self::contentTypes['apiV1PublisherSspFiltersGet'][0])
    {


        $resourcePath = '/api/v1/publisher/ssp/filters';
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
     * Operation apiV1PublisherSspNetworksGet
     *
     * RTB networks your SSP campaigns use.
     *
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_name filter_name (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspNetworksGet($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_name = null, string $contentType = self::contentTypes['apiV1PublisherSspNetworksGet'][0])
    {
        $this->apiV1PublisherSspNetworksGetWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name, $contentType);
    }

    /**
     * Operation apiV1PublisherSspNetworksGetWithHttpInfo
     *
     * RTB networks your SSP campaigns use.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspNetworksGetWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_name = null, string $contentType = self::contentTypes['apiV1PublisherSspNetworksGet'][0])
    {
        $request = $this->apiV1PublisherSspNetworksGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspNetworksGetAsync
     *
     * RTB networks your SSP campaigns use.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspNetworksGetAsync($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_name = null, string $contentType = self::contentTypes['apiV1PublisherSspNetworksGet'][0])
    {
        return $this->apiV1PublisherSspNetworksGetAsyncWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspNetworksGetAsyncWithHttpInfo
     *
     * RTB networks your SSP campaigns use.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspNetworksGetAsyncWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_name = null, string $contentType = self::contentTypes['apiV1PublisherSspNetworksGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspNetworksGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_name, $contentType);

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
     * Create request for operation 'apiV1PublisherSspNetworksGet'
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspNetworksGetRequest($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_name = null, string $contentType = self::contentTypes['apiV1PublisherSspNetworksGet'][0])
    {

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling SSPCampaignsApi.apiV1PublisherSspNetworksGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling SSPCampaignsApi.apiV1PublisherSspNetworksGet, must be smaller than or equal to 100.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling SSPCampaignsApi.apiV1PublisherSspNetworksGet, must be bigger than or equal to 1.');
        }
        





        $resourcePath = '/api/v1/publisher/ssp/networks';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
     * Operation apiV1PublisherSspSellerDisclosurePut
     *
     * Publish or hide your name in sellers.json.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest $api_v1_publisher_ssp_seller_disclosure_put_request api_v1_publisher_ssp_seller_disclosure_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSellerDisclosurePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspSellerDisclosurePut($api_v1_publisher_ssp_seller_disclosure_put_request, string $contentType = self::contentTypes['apiV1PublisherSspSellerDisclosurePut'][0])
    {
        $this->apiV1PublisherSspSellerDisclosurePutWithHttpInfo($api_v1_publisher_ssp_seller_disclosure_put_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSspSellerDisclosurePutWithHttpInfo
     *
     * Publish or hide your name in sellers.json.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest $api_v1_publisher_ssp_seller_disclosure_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSellerDisclosurePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspSellerDisclosurePutWithHttpInfo($api_v1_publisher_ssp_seller_disclosure_put_request, string $contentType = self::contentTypes['apiV1PublisherSspSellerDisclosurePut'][0])
    {
        $request = $this->apiV1PublisherSspSellerDisclosurePutRequest($api_v1_publisher_ssp_seller_disclosure_put_request, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspSellerDisclosurePutAsync
     *
     * Publish or hide your name in sellers.json.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest $api_v1_publisher_ssp_seller_disclosure_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSellerDisclosurePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSellerDisclosurePutAsync($api_v1_publisher_ssp_seller_disclosure_put_request, string $contentType = self::contentTypes['apiV1PublisherSspSellerDisclosurePut'][0])
    {
        return $this->apiV1PublisherSspSellerDisclosurePutAsyncWithHttpInfo($api_v1_publisher_ssp_seller_disclosure_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspSellerDisclosurePutAsyncWithHttpInfo
     *
     * Publish or hide your name in sellers.json.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest $api_v1_publisher_ssp_seller_disclosure_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSellerDisclosurePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSellerDisclosurePutAsyncWithHttpInfo($api_v1_publisher_ssp_seller_disclosure_put_request, string $contentType = self::contentTypes['apiV1PublisherSspSellerDisclosurePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspSellerDisclosurePutRequest($api_v1_publisher_ssp_seller_disclosure_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSspSellerDisclosurePut'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSspSellerDisclosurePutRequest $api_v1_publisher_ssp_seller_disclosure_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSellerDisclosurePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspSellerDisclosurePutRequest($api_v1_publisher_ssp_seller_disclosure_put_request, string $contentType = self::contentTypes['apiV1PublisherSspSellerDisclosurePut'][0])
    {

        // verify the required parameter 'api_v1_publisher_ssp_seller_disclosure_put_request' is set
        if ($api_v1_publisher_ssp_seller_disclosure_put_request === null || (is_array($api_v1_publisher_ssp_seller_disclosure_put_request) && count($api_v1_publisher_ssp_seller_disclosure_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_ssp_seller_disclosure_put_request when calling apiV1PublisherSspSellerDisclosurePut'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/seller-disclosure';
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
        if (isset($api_v1_publisher_ssp_seller_disclosure_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_ssp_seller_disclosure_put_request));
            } else {
                $httpBody = $api_v1_publisher_ssp_seller_disclosure_put_request;
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
     * Operation apiV1PublisherSspSitesIdAdsTxtCheckPost
     *
     * Re-check a site&#39;s ads.txt now.
     *
     * @param  int $id Your site id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspSitesIdAdsTxtCheckPost($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'][0])
    {
        $this->apiV1PublisherSspSitesIdAdsTxtCheckPostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSspSitesIdAdsTxtCheckPostWithHttpInfo
     *
     * Re-check a site&#39;s ads.txt now.
     *
     * @param  int $id Your site id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspSitesIdAdsTxtCheckPostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'][0])
    {
        $request = $this->apiV1PublisherSspSitesIdAdsTxtCheckPostRequest($id, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspSitesIdAdsTxtCheckPostAsync
     *
     * Re-check a site&#39;s ads.txt now.
     *
     * @param  int $id Your site id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSitesIdAdsTxtCheckPostAsync($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'][0])
    {
        return $this->apiV1PublisherSspSitesIdAdsTxtCheckPostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspSitesIdAdsTxtCheckPostAsyncWithHttpInfo
     *
     * Re-check a site&#39;s ads.txt now.
     *
     * @param  int $id Your site id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSitesIdAdsTxtCheckPostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspSitesIdAdsTxtCheckPostRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSspSitesIdAdsTxtCheckPost'
     *
     * @param  int $id Your site id. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspSitesIdAdsTxtCheckPostRequest($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtCheckPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspSitesIdAdsTxtCheckPost'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/sites/{id}/ads-txt-check';
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
     * Operation apiV1PublisherSspSitesIdAdsTxtStatusGet
     *
     * Last stored ads.txt check for a site.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSspSitesIdAdsTxtStatusGet($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'][0])
    {
        $this->apiV1PublisherSspSitesIdAdsTxtStatusGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSspSitesIdAdsTxtStatusGetWithHttpInfo
     *
     * Last stored ads.txt check for a site.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSspSitesIdAdsTxtStatusGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'][0])
    {
        $request = $this->apiV1PublisherSspSitesIdAdsTxtStatusGetRequest($id, $contentType);

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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
     * Operation apiV1PublisherSspSitesIdAdsTxtStatusGetAsync
     *
     * Last stored ads.txt check for a site.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSitesIdAdsTxtStatusGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'][0])
    {
        return $this->apiV1PublisherSspSitesIdAdsTxtStatusGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSspSitesIdAdsTxtStatusGetAsyncWithHttpInfo
     *
     * Last stored ads.txt check for a site.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSspSitesIdAdsTxtStatusGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSspSitesIdAdsTxtStatusGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSspSitesIdAdsTxtStatusGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSspSitesIdAdsTxtStatusGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherSspSitesIdAdsTxtStatusGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSspSitesIdAdsTxtStatusGet'
            );
        }


        $resourcePath = '/api/v1/publisher/ssp/sites/{id}/ads-txt-status';
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

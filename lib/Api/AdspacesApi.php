<?php
/**
 * AdspacesApi
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
 * AdspacesApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class AdspacesApi
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
        'apiV1AdspacesAddMultiplePost' => [
            'application/json',
        ],
        'apiV1AdspacesExportCodesGet' => [
            'application/json',
        ],
        'apiV1AdspacesGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdBlockedAdvertisersGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdChildrenGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdClonePost' => [
            'application/json',
        ],
        'apiV1AdspacesIdCodeGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdDelete' => [
            'application/json',
        ],
        'apiV1AdspacesIdGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdNetworkBindingDelete' => [
            'application/json',
        ],
        'apiV1AdspacesIdNetworkBindingPut' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsAdIdDelete' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsAdIdGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsAdIdPut' => [
            'application/json',
            'multipart/form-data',
        ],
        'apiV1AdspacesIdOwnAdsAdIdTogglePut' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsMassActionPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdOwnAdsPost' => [
            'application/json',
            'multipart/form-data',
        ],
        'apiV1AdspacesIdPatch' => [
            'application/json',
        ],
        'apiV1AdspacesIdPricesTypeGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdPricesTypePost' => [
            'application/json',
        ],
        'apiV1AdspacesIdRecoverPost' => [
            'application/json',
        ],
        'apiV1AdspacesIdWlCampaignsGet' => [
            'application/json',
        ],
        'apiV1AdspacesIdWlCampaignsPut' => [
            'application/json',
        ],
        'apiV1AdspacesMassDeletePost' => [
            'application/json',
        ],
        'apiV1AdspacesMassRecoverPost' => [
            'application/json',
        ],
        'apiV1AdspacesTemplatesGet' => [
            'application/json',
        ],
        'apiV1AdspacesTemplatesIdDelete' => [
            'application/json',
        ],
        'apiV1AdspacesTemplatesIdPut' => [
            'application/json',
        ],
        'apiV1AdspacesTemplatesPost' => [
            'application/json',
        ],
        'apiV1AdspacesTypePost' => [
            'application/json',
        ],
        'apiV1AdspacesTypeSchemaGet' => [
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
     * Operation apiV1AdspacesAddMultiplePost
     *
     * Create several ad zones of one type on one site.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest $api_v1_adspaces_add_multiple_post_request api_v1_adspaces_add_multiple_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesAddMultiplePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return int[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesAddMultiplePost($api_v1_adspaces_add_multiple_post_request, string $contentType = self::contentTypes['apiV1AdspacesAddMultiplePost'][0])
    {
        list($response) = $this->apiV1AdspacesAddMultiplePostWithHttpInfo($api_v1_adspaces_add_multiple_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesAddMultiplePostWithHttpInfo
     *
     * Create several ad zones of one type on one site.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest $api_v1_adspaces_add_multiple_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesAddMultiplePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of int[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesAddMultiplePostWithHttpInfo($api_v1_adspaces_add_multiple_post_request, string $contentType = self::contentTypes['apiV1AdspacesAddMultiplePost'][0])
    {
        $request = $this->apiV1AdspacesAddMultiplePostRequest($api_v1_adspaces_add_multiple_post_request, $contentType);

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
                        'int[]',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
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
                'int[]',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        'int[]',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
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
     * Operation apiV1AdspacesAddMultiplePostAsync
     *
     * Create several ad zones of one type on one site.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest $api_v1_adspaces_add_multiple_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesAddMultiplePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesAddMultiplePostAsync($api_v1_adspaces_add_multiple_post_request, string $contentType = self::contentTypes['apiV1AdspacesAddMultiplePost'][0])
    {
        return $this->apiV1AdspacesAddMultiplePostAsyncWithHttpInfo($api_v1_adspaces_add_multiple_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesAddMultiplePostAsyncWithHttpInfo
     *
     * Create several ad zones of one type on one site.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest $api_v1_adspaces_add_multiple_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesAddMultiplePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesAddMultiplePostAsyncWithHttpInfo($api_v1_adspaces_add_multiple_post_request, string $contentType = self::contentTypes['apiV1AdspacesAddMultiplePost'][0])
    {
        $returnType = 'int[]';
        $request = $this->apiV1AdspacesAddMultiplePostRequest($api_v1_adspaces_add_multiple_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesAddMultiplePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesAddMultiplePostRequest $api_v1_adspaces_add_multiple_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesAddMultiplePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesAddMultiplePostRequest($api_v1_adspaces_add_multiple_post_request, string $contentType = self::contentTypes['apiV1AdspacesAddMultiplePost'][0])
    {

        // verify the required parameter 'api_v1_adspaces_add_multiple_post_request' is set
        if ($api_v1_adspaces_add_multiple_post_request === null || (is_array($api_v1_adspaces_add_multiple_post_request) && count($api_v1_adspaces_add_multiple_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_add_multiple_post_request when calling apiV1AdspacesAddMultiplePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/add-multiple';
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
        if (isset($api_v1_adspaces_add_multiple_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_add_multiple_post_request));
            } else {
                $httpBody = $api_v1_adspaces_add_multiple_post_request;
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
     * Operation apiV1AdspacesExportCodesGet
     *
     * Embed codes for many ad zones at once, grouped by site.
     *
     * @param  int|null $siteid Only this site&#39;s zones. (optional)
     * @param  int|null $all 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. (optional, default to 1)
     * @param  string|null $sids Comma-separated ad zone ids (used with all&#x3D;0). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesExportCodesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesExportCodesGet($siteid = null, $all = 1, $sids = null, string $contentType = self::contentTypes['apiV1AdspacesExportCodesGet'][0])
    {
        list($response) = $this->apiV1AdspacesExportCodesGetWithHttpInfo($siteid, $all, $sids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesExportCodesGetWithHttpInfo
     *
     * Embed codes for many ad zones at once, grouped by site.
     *
     * @param  int|null $siteid Only this site&#39;s zones. (optional)
     * @param  int|null $all 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. (optional, default to 1)
     * @param  string|null $sids Comma-separated ad zone ids (used with all&#x3D;0). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesExportCodesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesExportCodesGetWithHttpInfo($siteid = null, $all = 1, $sids = null, string $contentType = self::contentTypes['apiV1AdspacesExportCodesGet'][0])
    {
        $request = $this->apiV1AdspacesExportCodesGetRequest($siteid, $all, $sids, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
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
                '\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
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
     * Operation apiV1AdspacesExportCodesGetAsync
     *
     * Embed codes for many ad zones at once, grouped by site.
     *
     * @param  int|null $siteid Only this site&#39;s zones. (optional)
     * @param  int|null $all 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. (optional, default to 1)
     * @param  string|null $sids Comma-separated ad zone ids (used with all&#x3D;0). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesExportCodesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesExportCodesGetAsync($siteid = null, $all = 1, $sids = null, string $contentType = self::contentTypes['apiV1AdspacesExportCodesGet'][0])
    {
        return $this->apiV1AdspacesExportCodesGetAsyncWithHttpInfo($siteid, $all, $sids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesExportCodesGetAsyncWithHttpInfo
     *
     * Embed codes for many ad zones at once, grouped by site.
     *
     * @param  int|null $siteid Only this site&#39;s zones. (optional)
     * @param  int|null $all 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. (optional, default to 1)
     * @param  string|null $sids Comma-separated ad zone ids (used with all&#x3D;0). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesExportCodesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesExportCodesGetAsyncWithHttpInfo($siteid = null, $all = 1, $sids = null, string $contentType = self::contentTypes['apiV1AdspacesExportCodesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInner[]';
        $request = $this->apiV1AdspacesExportCodesGetRequest($siteid, $all, $sids, $contentType);

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
     * Create request for operation 'apiV1AdspacesExportCodesGet'
     *
     * @param  int|null $siteid Only this site&#39;s zones. (optional)
     * @param  int|null $all 1 &#x3D; every zone; 0 &#x3D; only the zones in &#x60;sids&#x60;. (optional, default to 1)
     * @param  string|null $sids Comma-separated ad zone ids (used with all&#x3D;0). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesExportCodesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesExportCodesGetRequest($siteid = null, $all = 1, $sids = null, string $contentType = self::contentTypes['apiV1AdspacesExportCodesGet'][0])
    {





        $resourcePath = '/api/v1/adspaces/export-codes';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $all,
            'all', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sids,
            'sids', // param base name
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
     * Operation apiV1AdspacesGet
     *
     * List ad zones owned by the caller.
     *
     * @param  int|null $site_id Filter to one site. (optional)
     * @param  string|null $state &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). (optional, default to 'live')
     * @param  string|null $type Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. (optional)
     * @param  int|null $parent_id Children of this zone; 0 &#x3D; top-level zones only. (optional)
     * @param  string|null $search Substring of the name, or an exact zone id. (optional)
     * @param  string|null $sort sort (optional, default to '-id')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesGet200Response
     */
    public function apiV1AdspacesGet($site_id = null, $state = 'live', $type = null, $parent_id = null, $search = null, $sort = '-id', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1AdspacesGet'][0])
    {
        list($response) = $this->apiV1AdspacesGetWithHttpInfo($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesGetWithHttpInfo
     *
     * List ad zones owned by the caller.
     *
     * @param  int|null $site_id Filter to one site. (optional)
     * @param  string|null $state &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). (optional, default to 'live')
     * @param  string|null $type Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. (optional)
     * @param  int|null $parent_id Children of this zone; 0 &#x3D; top-level zones only. (optional)
     * @param  string|null $search Substring of the name, or an exact zone id. (optional)
     * @param  string|null $sort (optional, default to '-id')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesGetWithHttpInfo($site_id = null, $state = 'live', $type = null, $parent_id = null, $search = null, $sort = '-id', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1AdspacesGet'][0])
    {
        $request = $this->apiV1AdspacesGetRequest($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1AdspacesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AdspacesGetAsync
     *
     * List ad zones owned by the caller.
     *
     * @param  int|null $site_id Filter to one site. (optional)
     * @param  string|null $state &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). (optional, default to 'live')
     * @param  string|null $type Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. (optional)
     * @param  int|null $parent_id Children of this zone; 0 &#x3D; top-level zones only. (optional)
     * @param  string|null $search Substring of the name, or an exact zone id. (optional)
     * @param  string|null $sort (optional, default to '-id')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesGetAsync($site_id = null, $state = 'live', $type = null, $parent_id = null, $search = null, $sort = '-id', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1AdspacesGet'][0])
    {
        return $this->apiV1AdspacesGetAsyncWithHttpInfo($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesGetAsyncWithHttpInfo
     *
     * List ad zones owned by the caller.
     *
     * @param  int|null $site_id Filter to one site. (optional)
     * @param  string|null $state &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). (optional, default to 'live')
     * @param  string|null $type Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. (optional)
     * @param  int|null $parent_id Children of this zone; 0 &#x3D; top-level zones only. (optional)
     * @param  string|null $search Substring of the name, or an exact zone id. (optional)
     * @param  string|null $sort (optional, default to '-id')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesGetAsyncWithHttpInfo($site_id = null, $state = 'live', $type = null, $parent_id = null, $search = null, $sort = '-id', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1AdspacesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesGet200Response';
        $request = $this->apiV1AdspacesGetRequest($site_id, $state, $type, $parent_id, $search, $sort, $page, $per_page, $contentType);

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
     * Create request for operation 'apiV1AdspacesGet'
     *
     * @param  int|null $site_id Filter to one site. (optional)
     * @param  string|null $state &#x60;deleted&#x60; lists soft-deleted zones (for recover / mass-recover). (optional, default to 'live')
     * @param  string|null $type Adspace type key (e.g. &#x60;popads&#x60;) or numeric adzone_typeid. Unknown key → 422. (optional)
     * @param  int|null $parent_id Children of this zone; 0 &#x3D; top-level zones only. (optional)
     * @param  string|null $search Substring of the name, or an exact zone id. (optional)
     * @param  string|null $sort (optional, default to '-id')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 100)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesGetRequest($site_id = null, $state = 'live', $type = null, $parent_id = null, $search = null, $sort = '-id', $page = 1, $per_page = 100, string $contentType = self::contentTypes['apiV1AdspacesGet'][0])
    {







        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling AdspacesApi.apiV1AdspacesGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 500) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling AdspacesApi.apiV1AdspacesGet, must be smaller than or equal to 500.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling AdspacesApi.apiV1AdspacesGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/adspaces';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $site_id,
            'site_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $state,
            'state', // param base name
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
     * Operation apiV1AdspacesIdBlockedAdvertisersGet
     *
     * Advertiser block filters that exclude this ad zone.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdBlockedAdvertisersGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'][0])
    {
        $this->apiV1AdspacesIdBlockedAdvertisersGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdBlockedAdvertisersGetWithHttpInfo
     *
     * Advertiser block filters that exclude this ad zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdBlockedAdvertisersGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'][0])
    {
        $request = $this->apiV1AdspacesIdBlockedAdvertisersGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdBlockedAdvertisersGetAsync
     *
     * Advertiser block filters that exclude this ad zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdBlockedAdvertisersGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'][0])
    {
        return $this->apiV1AdspacesIdBlockedAdvertisersGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdBlockedAdvertisersGetAsyncWithHttpInfo
     *
     * Advertiser block filters that exclude this ad zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdBlockedAdvertisersGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdBlockedAdvertisersGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdBlockedAdvertisersGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdBlockedAdvertisersGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdBlockedAdvertisersGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdBlockedAdvertisersGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/blocked-advertisers';
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
     * Operation apiV1AdspacesIdChildrenGet
     *
     * List an ad zone&#39;s child zones.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdChildrenGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdChildrenGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdChildrenGet'][0])
    {
        $this->apiV1AdspacesIdChildrenGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdChildrenGetWithHttpInfo
     *
     * List an ad zone&#39;s child zones.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdChildrenGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdChildrenGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdChildrenGet'][0])
    {
        $request = $this->apiV1AdspacesIdChildrenGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdChildrenGetAsync
     *
     * List an ad zone&#39;s child zones.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdChildrenGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdChildrenGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdChildrenGet'][0])
    {
        return $this->apiV1AdspacesIdChildrenGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdChildrenGetAsyncWithHttpInfo
     *
     * List an ad zone&#39;s child zones.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdChildrenGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdChildrenGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdChildrenGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdChildrenGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdChildrenGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdChildrenGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdChildrenGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdChildrenGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdChildrenGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/children';
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
     * Operation apiV1AdspacesIdClonePost
     *
     * Duplicate an ad zone.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest $api_v1_adspaces_id_clone_post_request api_v1_adspaces_id_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdClonePost($id, $api_v1_adspaces_id_clone_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdClonePost'][0])
    {
        $this->apiV1AdspacesIdClonePostWithHttpInfo($id, $api_v1_adspaces_id_clone_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdClonePostWithHttpInfo
     *
     * Duplicate an ad zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest $api_v1_adspaces_id_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdClonePostWithHttpInfo($id, $api_v1_adspaces_id_clone_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdClonePost'][0])
    {
        $request = $this->apiV1AdspacesIdClonePostRequest($id, $api_v1_adspaces_id_clone_post_request, $contentType);

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
     * Operation apiV1AdspacesIdClonePostAsync
     *
     * Duplicate an ad zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest $api_v1_adspaces_id_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdClonePostAsync($id, $api_v1_adspaces_id_clone_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdClonePost'][0])
    {
        return $this->apiV1AdspacesIdClonePostAsyncWithHttpInfo($id, $api_v1_adspaces_id_clone_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdClonePostAsyncWithHttpInfo
     *
     * Duplicate an ad zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest $api_v1_adspaces_id_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdClonePostAsyncWithHttpInfo($id, $api_v1_adspaces_id_clone_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdClonePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdClonePostRequest($id, $api_v1_adspaces_id_clone_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdClonePost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdClonePostRequest $api_v1_adspaces_id_clone_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdClonePostRequest($id, $api_v1_adspaces_id_clone_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdClonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdClonePost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_clone_post_request' is set
        if ($api_v1_adspaces_id_clone_post_request === null || (is_array($api_v1_adspaces_id_clone_post_request) && count($api_v1_adspaces_id_clone_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_clone_post_request when calling apiV1AdspacesIdClonePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/clone';
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
        if (isset($api_v1_adspaces_id_clone_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_clone_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_clone_post_request;
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
     * Operation apiV1AdspacesIdCodeGet
     *
     * Embed-code snippets for an adspace.
     *
     * @param  int $id id (required)
     * @param  string|null $layout Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdCodeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesIdCodeGet($id, $layout = null, string $contentType = self::contentTypes['apiV1AdspacesIdCodeGet'][0])
    {
        list($response) = $this->apiV1AdspacesIdCodeGetWithHttpInfo($id, $layout, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesIdCodeGetWithHttpInfo
     *
     * Embed-code snippets for an adspace.
     *
     * @param  int $id (required)
     * @param  string|null $layout Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdCodeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdCodeGetWithHttpInfo($id, $layout = null, string $contentType = self::contentTypes['apiV1AdspacesIdCodeGet'][0])
    {
        $request = $this->apiV1AdspacesIdCodeGetRequest($id, $layout, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response',
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
                '\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response',
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
     * Operation apiV1AdspacesIdCodeGetAsync
     *
     * Embed-code snippets for an adspace.
     *
     * @param  int $id (required)
     * @param  string|null $layout Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdCodeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdCodeGetAsync($id, $layout = null, string $contentType = self::contentTypes['apiV1AdspacesIdCodeGet'][0])
    {
        return $this->apiV1AdspacesIdCodeGetAsyncWithHttpInfo($id, $layout, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdCodeGetAsyncWithHttpInfo
     *
     * Embed-code snippets for an adspace.
     *
     * @param  int $id (required)
     * @param  string|null $layout Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdCodeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdCodeGetAsyncWithHttpInfo($id, $layout = null, string $contentType = self::contentTypes['apiV1AdspacesIdCodeGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200Response';
        $request = $this->apiV1AdspacesIdCodeGetRequest($id, $layout, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdCodeGet'
     *
     * @param  int $id (required)
     * @param  string|null $layout Native (cards…links) and creator widget (caption…clip) zones: ready-made layout to render the code with (see above). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdCodeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdCodeGetRequest($id, $layout = null, string $contentType = self::contentTypes['apiV1AdspacesIdCodeGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdCodeGet'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/code';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $layout,
            'layout', // param base name
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
     * Operation apiV1AdspacesIdDelete
     *
     * Soft-delete an adspace.
     *
     * @param  int $id id (required)
     * @param  int|null $confirm_smart_zones 1 &#x3D; also close the zone&#39;s smart zones. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdDelete($id, $confirm_smart_zones = null, string $contentType = self::contentTypes['apiV1AdspacesIdDelete'][0])
    {
        $this->apiV1AdspacesIdDeleteWithHttpInfo($id, $confirm_smart_zones, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdDeleteWithHttpInfo
     *
     * Soft-delete an adspace.
     *
     * @param  int $id (required)
     * @param  int|null $confirm_smart_zones 1 &#x3D; also close the zone&#39;s smart zones. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdDeleteWithHttpInfo($id, $confirm_smart_zones = null, string $contentType = self::contentTypes['apiV1AdspacesIdDelete'][0])
    {
        $request = $this->apiV1AdspacesIdDeleteRequest($id, $confirm_smart_zones, $contentType);

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
     * Operation apiV1AdspacesIdDeleteAsync
     *
     * Soft-delete an adspace.
     *
     * @param  int $id (required)
     * @param  int|null $confirm_smart_zones 1 &#x3D; also close the zone&#39;s smart zones. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdDeleteAsync($id, $confirm_smart_zones = null, string $contentType = self::contentTypes['apiV1AdspacesIdDelete'][0])
    {
        return $this->apiV1AdspacesIdDeleteAsyncWithHttpInfo($id, $confirm_smart_zones, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete an adspace.
     *
     * @param  int $id (required)
     * @param  int|null $confirm_smart_zones 1 &#x3D; also close the zone&#39;s smart zones. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdDeleteAsyncWithHttpInfo($id, $confirm_smart_zones = null, string $contentType = self::contentTypes['apiV1AdspacesIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdDeleteRequest($id, $confirm_smart_zones, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdDelete'
     *
     * @param  int $id (required)
     * @param  int|null $confirm_smart_zones 1 &#x3D; also close the zone&#39;s smart zones. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdDeleteRequest($id, $confirm_smart_zones = null, string $contentType = self::contentTypes['apiV1AdspacesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdDelete'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $confirm_smart_zones,
            'confirm_smart_zones', // param base name
            'integer', // openApiType
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
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1AdspacesIdGet
     *
     * Get a single adspace.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\Adspace
     */
    public function apiV1AdspacesIdGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdGet'][0])
    {
        list($response) = $this->apiV1AdspacesIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesIdGetWithHttpInfo
     *
     * Get a single adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\Adspace, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdGet'][0])
    {
        $request = $this->apiV1AdspacesIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\Adspace',
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
                '\GoAdServerApi\Model\Adspace',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Adspace',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AdspacesIdGetAsync
     *
     * Get a single adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdGet'][0])
    {
        return $this->apiV1AdspacesIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdGetAsyncWithHttpInfo
     *
     * Get a single adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\Adspace';
        $request = $this->apiV1AdspacesIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}';
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
     * Operation apiV1AdspacesIdNetworkBindingDelete
     *
     * Remove the zone&#39;s network binding.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdNetworkBindingDelete($id, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'][0])
    {
        $this->apiV1AdspacesIdNetworkBindingDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdNetworkBindingDeleteWithHttpInfo
     *
     * Remove the zone&#39;s network binding.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdNetworkBindingDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'][0])
    {
        $request = $this->apiV1AdspacesIdNetworkBindingDeleteRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdNetworkBindingDeleteAsync
     *
     * Remove the zone&#39;s network binding.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdNetworkBindingDeleteAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'][0])
    {
        return $this->apiV1AdspacesIdNetworkBindingDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdNetworkBindingDeleteAsyncWithHttpInfo
     *
     * Remove the zone&#39;s network binding.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdNetworkBindingDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdNetworkBindingDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdNetworkBindingDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdNetworkBindingDeleteRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdNetworkBindingDelete'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/network-binding';
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
     * Operation apiV1AdspacesIdNetworkBindingPut
     *
     * Bind the zone to a campaign on an external ad network (the Source tab).
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest $api_v1_adspaces_id_network_binding_put_request api_v1_adspaces_id_network_binding_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdNetworkBindingPut($id, $api_v1_adspaces_id_network_binding_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingPut'][0])
    {
        $this->apiV1AdspacesIdNetworkBindingPutWithHttpInfo($id, $api_v1_adspaces_id_network_binding_put_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdNetworkBindingPutWithHttpInfo
     *
     * Bind the zone to a campaign on an external ad network (the Source tab).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest $api_v1_adspaces_id_network_binding_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdNetworkBindingPutWithHttpInfo($id, $api_v1_adspaces_id_network_binding_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingPut'][0])
    {
        $request = $this->apiV1AdspacesIdNetworkBindingPutRequest($id, $api_v1_adspaces_id_network_binding_put_request, $contentType);

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
     * Operation apiV1AdspacesIdNetworkBindingPutAsync
     *
     * Bind the zone to a campaign on an external ad network (the Source tab).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest $api_v1_adspaces_id_network_binding_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdNetworkBindingPutAsync($id, $api_v1_adspaces_id_network_binding_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingPut'][0])
    {
        return $this->apiV1AdspacesIdNetworkBindingPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_network_binding_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdNetworkBindingPutAsyncWithHttpInfo
     *
     * Bind the zone to a campaign on an external ad network (the Source tab).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest $api_v1_adspaces_id_network_binding_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdNetworkBindingPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_network_binding_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdNetworkBindingPutRequest($id, $api_v1_adspaces_id_network_binding_put_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdNetworkBindingPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdNetworkBindingPutRequest $api_v1_adspaces_id_network_binding_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdNetworkBindingPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdNetworkBindingPutRequest($id, $api_v1_adspaces_id_network_binding_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdNetworkBindingPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdNetworkBindingPut'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_network_binding_put_request' is set
        if ($api_v1_adspaces_id_network_binding_put_request === null || (is_array($api_v1_adspaces_id_network_binding_put_request) && count($api_v1_adspaces_id_network_binding_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_network_binding_put_request when calling apiV1AdspacesIdNetworkBindingPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/network-binding';
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
        if (isset($api_v1_adspaces_id_network_binding_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_network_binding_put_request));
            } else {
                $httpBody = $api_v1_adspaces_id_network_binding_put_request;
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
     * Operation apiV1AdspacesIdOwnAdsAdIdDelete
     *
     * Delete an own ad.
     *
     * @param  int $id id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsAdIdDelete($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'][0])
    {
        $this->apiV1AdspacesIdOwnAdsAdIdDeleteWithHttpInfo($id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdDeleteWithHttpInfo
     *
     * Delete an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsAdIdDeleteWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsAdIdDeleteRequest($id, $ad_id, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsAdIdDeleteAsync
     *
     * Delete an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdDeleteAsync($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsAdIdDeleteAsyncWithHttpInfo($id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdDeleteAsyncWithHttpInfo
     *
     * Delete an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdDeleteAsyncWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsAdIdDeleteRequest($id, $ad_id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsAdIdDelete'
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsAdIdDeleteRequest($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsAdIdDelete'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1AdspacesIdOwnAdsAdIdDelete'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads/{adId}';
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
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
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
     * Operation apiV1AdspacesIdOwnAdsAdIdGet
     *
     * Get one own ad.
     *
     * @param  int $id id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsAdIdGet($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'][0])
    {
        $this->apiV1AdspacesIdOwnAdsAdIdGetWithHttpInfo($id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdGetWithHttpInfo
     *
     * Get one own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsAdIdGetWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsAdIdGetRequest($id, $ad_id, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsAdIdGetAsync
     *
     * Get one own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdGetAsync($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsAdIdGetAsyncWithHttpInfo($id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdGetAsyncWithHttpInfo
     *
     * Get one own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdGetAsyncWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsAdIdGetRequest($id, $ad_id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsAdIdGet'
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsAdIdGetRequest($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsAdIdGet'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1AdspacesIdOwnAdsAdIdGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads/{adId}';
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
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
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
     * Operation apiV1AdspacesIdOwnAdsAdIdPut
     *
     * Update an own ad.
     *
     * @param  int $id id (required)
     * @param  int $ad_id ad_id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsAdIdPut($id, $ad_id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'][0])
    {
        $this->apiV1AdspacesIdOwnAdsAdIdPutWithHttpInfo($id, $ad_id, $own_ad_write, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdPutWithHttpInfo
     *
     * Update an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsAdIdPutWithHttpInfo($id, $ad_id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsAdIdPutRequest($id, $ad_id, $own_ad_write, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsAdIdPutAsync
     *
     * Update an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdPutAsync($id, $ad_id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsAdIdPutAsyncWithHttpInfo($id, $ad_id, $own_ad_write, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdPutAsyncWithHttpInfo
     *
     * Update an own ad.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdPutAsyncWithHttpInfo($id, $ad_id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsAdIdPutRequest($id, $ad_id, $own_ad_write, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsAdIdPut'
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsAdIdPutRequest($id, $ad_id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsAdIdPut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1AdspacesIdOwnAdsAdIdPut'
            );
        }

        // verify the required parameter 'own_ad_write' is set
        if ($own_ad_write === null || (is_array($own_ad_write) && count($own_ad_write) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $own_ad_write when calling apiV1AdspacesIdOwnAdsAdIdPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads/{adId}';
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
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($own_ad_write)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($own_ad_write));
            } else {
                $httpBody = $own_ad_write;
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
     * Operation apiV1AdspacesIdOwnAdsAdIdTogglePut
     *
     * Switch an own ad on/off.
     *
     * @param  int $id id (required)
     * @param  int $ad_id ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsAdIdTogglePut($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'][0])
    {
        $this->apiV1AdspacesIdOwnAdsAdIdTogglePutWithHttpInfo($id, $ad_id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdTogglePutWithHttpInfo
     *
     * Switch an own ad on/off.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsAdIdTogglePutWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsAdIdTogglePutRequest($id, $ad_id, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsAdIdTogglePutAsync
     *
     * Switch an own ad on/off.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdTogglePutAsync($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsAdIdTogglePutAsyncWithHttpInfo($id, $ad_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsAdIdTogglePutAsyncWithHttpInfo
     *
     * Switch an own ad on/off.
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsAdIdTogglePutAsyncWithHttpInfo($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsAdIdTogglePutRequest($id, $ad_id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsAdIdTogglePut'
     *
     * @param  int $id (required)
     * @param  int $ad_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsAdIdTogglePutRequest($id, $ad_id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsAdIdTogglePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsAdIdTogglePut'
            );
        }

        // verify the required parameter 'ad_id' is set
        if ($ad_id === null || (is_array($ad_id) && count($ad_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ad_id when calling apiV1AdspacesIdOwnAdsAdIdTogglePut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads/{adId}/toggle';
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
        if ($ad_id !== null) {
            $resourcePath = str_replace(
                '{' . 'adId' . '}',
                ObjectSerializer::toPathValue($ad_id),
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
     * Operation apiV1AdspacesIdOwnAdsGet
     *
     * List the zone&#39;s own (house / fallback) ads.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsGet'][0])
    {
        $this->apiV1AdspacesIdOwnAdsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsGetWithHttpInfo
     *
     * List the zone&#39;s own (house / fallback) ads.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsGet'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsGetAsync
     *
     * List the zone&#39;s own (house / fallback) ads.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsGet'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsGetAsyncWithHttpInfo
     *
     * List the zone&#39;s own (house / fallback) ads.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads';
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
     * Operation apiV1AdspacesIdOwnAdsMassActionPost
     *
     * Activate, deactivate or delete several own ads.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest $api_v1_adspaces_id_own_ads_mass_action_post_request api_v1_adspaces_id_own_ads_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsMassActionPost($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'][0])
    {
        $this->apiV1AdspacesIdOwnAdsMassActionPostWithHttpInfo($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsMassActionPostWithHttpInfo
     *
     * Activate, deactivate or delete several own ads.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest $api_v1_adspaces_id_own_ads_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsMassActionPostWithHttpInfo($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsMassActionPostRequest($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsMassActionPostAsync
     *
     * Activate, deactivate or delete several own ads.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest $api_v1_adspaces_id_own_ads_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsMassActionPostAsync($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsMassActionPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsMassActionPostAsyncWithHttpInfo
     *
     * Activate, deactivate or delete several own ads.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest $api_v1_adspaces_id_own_ads_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsMassActionPostAsyncWithHttpInfo($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsMassActionPostRequest($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsMassActionPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdOwnAdsMassActionPostRequest $api_v1_adspaces_id_own_ads_mass_action_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsMassActionPostRequest($id, $api_v1_adspaces_id_own_ads_mass_action_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsMassActionPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsMassActionPost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_own_ads_mass_action_post_request' is set
        if ($api_v1_adspaces_id_own_ads_mass_action_post_request === null || (is_array($api_v1_adspaces_id_own_ads_mass_action_post_request) && count($api_v1_adspaces_id_own_ads_mass_action_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_own_ads_mass_action_post_request when calling apiV1AdspacesIdOwnAdsMassActionPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads/mass-action';
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
        if (isset($api_v1_adspaces_id_own_ads_mass_action_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_own_ads_mass_action_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_own_ads_mass_action_post_request;
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
     * Operation apiV1AdspacesIdOwnAdsPost
     *
     * Add an own ad.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdOwnAdsPost($id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsPost'][0])
    {
        $this->apiV1AdspacesIdOwnAdsPostWithHttpInfo($id, $own_ad_write, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsPostWithHttpInfo
     *
     * Add an own ad.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdOwnAdsPostWithHttpInfo($id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsPost'][0])
    {
        $request = $this->apiV1AdspacesIdOwnAdsPostRequest($id, $own_ad_write, $contentType);

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
     * Operation apiV1AdspacesIdOwnAdsPostAsync
     *
     * Add an own ad.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsPostAsync($id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsPost'][0])
    {
        return $this->apiV1AdspacesIdOwnAdsPostAsyncWithHttpInfo($id, $own_ad_write, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdOwnAdsPostAsyncWithHttpInfo
     *
     * Add an own ad.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdOwnAdsPostAsyncWithHttpInfo($id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdOwnAdsPostRequest($id, $own_ad_write, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdOwnAdsPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\OwnAdWrite $own_ad_write (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdOwnAdsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdOwnAdsPostRequest($id, $own_ad_write, string $contentType = self::contentTypes['apiV1AdspacesIdOwnAdsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdOwnAdsPost'
            );
        }

        // verify the required parameter 'own_ad_write' is set
        if ($own_ad_write === null || (is_array($own_ad_write) && count($own_ad_write) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $own_ad_write when calling apiV1AdspacesIdOwnAdsPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/own-ads';
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
        if (isset($own_ad_write)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($own_ad_write));
            } else {
                $httpBody = $own_ad_write;
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
     * Operation apiV1AdspacesIdPatch
     *
     * Update an existing adspace.
     *
     * @param  int $id id (required)
     * @param  array<string,mixed> $request_body request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesIdPatch($id, $request_body, string $contentType = self::contentTypes['apiV1AdspacesIdPatch'][0])
    {
        list($response) = $this->apiV1AdspacesIdPatchWithHttpInfo($id, $request_body, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesIdPatchWithHttpInfo
     *
     * Update an existing adspace.
     *
     * @param  int $id (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPatch'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdPatchWithHttpInfo($id, $request_body, string $contentType = self::contentTypes['apiV1AdspacesIdPatch'][0])
    {
        $request = $this->apiV1AdspacesIdPatchRequest($id, $request_body, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
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
                '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
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
     * Operation apiV1AdspacesIdPatchAsync
     *
     * Update an existing adspace.
     *
     * @param  int $id (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPatchAsync($id, $request_body, string $contentType = self::contentTypes['apiV1AdspacesIdPatch'][0])
    {
        return $this->apiV1AdspacesIdPatchAsyncWithHttpInfo($id, $request_body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdPatchAsyncWithHttpInfo
     *
     * Update an existing adspace.
     *
     * @param  int $id (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPatchAsyncWithHttpInfo($id, $request_body, string $contentType = self::contentTypes['apiV1AdspacesIdPatch'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response';
        $request = $this->apiV1AdspacesIdPatchRequest($id, $request_body, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdPatch'
     *
     * @param  int $id (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPatch'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdPatchRequest($id, $request_body, string $contentType = self::contentTypes['apiV1AdspacesIdPatch'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdPatch'
            );
        }

        // verify the required parameter 'request_body' is set
        if ($request_body === null || (is_array($request_body) && count($request_body) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $request_body when calling apiV1AdspacesIdPatch'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}';
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
        if (isset($request_body)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($request_body));
            } else {
                $httpBody = $request_body;
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
     * Operation apiV1AdspacesIdPricesTypeGet
     *
     * Minimum (floor) prices per country for one device type.
     *
     * @param  int $id id (required)
     * @param  string $type type (required)
     * @param  int|null $device_typeid Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. (optional, default to 0)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdPricesTypeGet($id, $type, $device_typeid = 0, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypeGet'][0])
    {
        $this->apiV1AdspacesIdPricesTypeGetWithHttpInfo($id, $type, $device_typeid, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdPricesTypeGetWithHttpInfo
     *
     * Minimum (floor) prices per country for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  int|null $device_typeid Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. (optional, default to 0)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdPricesTypeGetWithHttpInfo($id, $type, $device_typeid = 0, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypeGet'][0])
    {
        $request = $this->apiV1AdspacesIdPricesTypeGetRequest($id, $type, $device_typeid, $contentType);

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
     * Operation apiV1AdspacesIdPricesTypeGetAsync
     *
     * Minimum (floor) prices per country for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  int|null $device_typeid Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. (optional, default to 0)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPricesTypeGetAsync($id, $type, $device_typeid = 0, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypeGet'][0])
    {
        return $this->apiV1AdspacesIdPricesTypeGetAsyncWithHttpInfo($id, $type, $device_typeid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdPricesTypeGetAsyncWithHttpInfo
     *
     * Minimum (floor) prices per country for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  int|null $device_typeid Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. (optional, default to 0)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPricesTypeGetAsyncWithHttpInfo($id, $type, $device_typeid = 0, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypeGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdPricesTypeGetRequest($id, $type, $device_typeid, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdPricesTypeGet'
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  int|null $device_typeid Device type (see /api/v1/lookups/device-types); 0 &#x3D; all devices. (optional, default to 0)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdPricesTypeGetRequest($id, $type, $device_typeid = 0, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypeGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdPricesTypeGet'
            );
        }

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1AdspacesIdPricesTypeGet'
            );
        }



        $resourcePath = '/api/v1/adspaces/{id}/prices/{type}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $device_typeid,
            'device_typeid', // param base name
            'integer', // openApiType
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
        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
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
     * Operation apiV1AdspacesIdPricesTypePost
     *
     * Replace the floor prices for one device type.
     *
     * @param  int $id id (required)
     * @param  string $type type (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest $api_v1_adspaces_id_prices_type_post_request api_v1_adspaces_id_prices_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdPricesTypePost($id, $type, $api_v1_adspaces_id_prices_type_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypePost'][0])
    {
        $this->apiV1AdspacesIdPricesTypePostWithHttpInfo($id, $type, $api_v1_adspaces_id_prices_type_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdPricesTypePostWithHttpInfo
     *
     * Replace the floor prices for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest $api_v1_adspaces_id_prices_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdPricesTypePostWithHttpInfo($id, $type, $api_v1_adspaces_id_prices_type_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypePost'][0])
    {
        $request = $this->apiV1AdspacesIdPricesTypePostRequest($id, $type, $api_v1_adspaces_id_prices_type_post_request, $contentType);

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
     * Operation apiV1AdspacesIdPricesTypePostAsync
     *
     * Replace the floor prices for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest $api_v1_adspaces_id_prices_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPricesTypePostAsync($id, $type, $api_v1_adspaces_id_prices_type_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypePost'][0])
    {
        return $this->apiV1AdspacesIdPricesTypePostAsyncWithHttpInfo($id, $type, $api_v1_adspaces_id_prices_type_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdPricesTypePostAsyncWithHttpInfo
     *
     * Replace the floor prices for one device type.
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest $api_v1_adspaces_id_prices_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdPricesTypePostAsyncWithHttpInfo($id, $type, $api_v1_adspaces_id_prices_type_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdPricesTypePostRequest($id, $type, $api_v1_adspaces_id_prices_type_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdPricesTypePost'
     *
     * @param  int $id (required)
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdPricesTypePostRequest $api_v1_adspaces_id_prices_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdPricesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdPricesTypePostRequest($id, $type, $api_v1_adspaces_id_prices_type_post_request, string $contentType = self::contentTypes['apiV1AdspacesIdPricesTypePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdPricesTypePost'
            );
        }

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1AdspacesIdPricesTypePost'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_prices_type_post_request' is set
        if ($api_v1_adspaces_id_prices_type_post_request === null || (is_array($api_v1_adspaces_id_prices_type_post_request) && count($api_v1_adspaces_id_prices_type_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_prices_type_post_request when calling apiV1AdspacesIdPricesTypePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/prices/{type}';
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
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_adspaces_id_prices_type_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_prices_type_post_request));
            } else {
                $httpBody = $api_v1_adspaces_id_prices_type_post_request;
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
     * Operation apiV1AdspacesIdRecoverPost
     *
     * Reverse a soft-delete on an adspace.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdRecoverPost($id, string $contentType = self::contentTypes['apiV1AdspacesIdRecoverPost'][0])
    {
        $this->apiV1AdspacesIdRecoverPostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdRecoverPostWithHttpInfo
     *
     * Reverse a soft-delete on an adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdRecoverPostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdRecoverPost'][0])
    {
        $request = $this->apiV1AdspacesIdRecoverPostRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdRecoverPostAsync
     *
     * Reverse a soft-delete on an adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdRecoverPostAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdRecoverPost'][0])
    {
        return $this->apiV1AdspacesIdRecoverPostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdRecoverPostAsyncWithHttpInfo
     *
     * Reverse a soft-delete on an adspace.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdRecoverPostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdRecoverPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdRecoverPostRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdRecoverPost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdRecoverPostRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdRecoverPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdRecoverPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/recover';
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
     * Operation apiV1AdspacesIdWlCampaignsGet
     *
     * Campaigns that can be whitelisted on this zone, and the current whitelist.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdWlCampaignsGet($id, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsGet'][0])
    {
        $this->apiV1AdspacesIdWlCampaignsGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdWlCampaignsGetWithHttpInfo
     *
     * Campaigns that can be whitelisted on this zone, and the current whitelist.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdWlCampaignsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsGet'][0])
    {
        $request = $this->apiV1AdspacesIdWlCampaignsGetRequest($id, $contentType);

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
     * Operation apiV1AdspacesIdWlCampaignsGetAsync
     *
     * Campaigns that can be whitelisted on this zone, and the current whitelist.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdWlCampaignsGetAsync($id, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsGet'][0])
    {
        return $this->apiV1AdspacesIdWlCampaignsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdWlCampaignsGetAsyncWithHttpInfo
     *
     * Campaigns that can be whitelisted on this zone, and the current whitelist.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdWlCampaignsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdWlCampaignsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdWlCampaignsGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdWlCampaignsGetRequest($id, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdWlCampaignsGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/wl-campaigns';
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
     * Operation apiV1AdspacesIdWlCampaignsPut
     *
     * Replace the zone&#39;s whitelisted campaigns.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest $api_v1_adspaces_id_wl_campaigns_put_request api_v1_adspaces_id_wl_campaigns_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesIdWlCampaignsPut($id, $api_v1_adspaces_id_wl_campaigns_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsPut'][0])
    {
        $this->apiV1AdspacesIdWlCampaignsPutWithHttpInfo($id, $api_v1_adspaces_id_wl_campaigns_put_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesIdWlCampaignsPutWithHttpInfo
     *
     * Replace the zone&#39;s whitelisted campaigns.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest $api_v1_adspaces_id_wl_campaigns_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesIdWlCampaignsPutWithHttpInfo($id, $api_v1_adspaces_id_wl_campaigns_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsPut'][0])
    {
        $request = $this->apiV1AdspacesIdWlCampaignsPutRequest($id, $api_v1_adspaces_id_wl_campaigns_put_request, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AdspacesIdWlCampaignsPutAsync
     *
     * Replace the zone&#39;s whitelisted campaigns.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest $api_v1_adspaces_id_wl_campaigns_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdWlCampaignsPutAsync($id, $api_v1_adspaces_id_wl_campaigns_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsPut'][0])
    {
        return $this->apiV1AdspacesIdWlCampaignsPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_wl_campaigns_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesIdWlCampaignsPutAsyncWithHttpInfo
     *
     * Replace the zone&#39;s whitelisted campaigns.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest $api_v1_adspaces_id_wl_campaigns_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesIdWlCampaignsPutAsyncWithHttpInfo($id, $api_v1_adspaces_id_wl_campaigns_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesIdWlCampaignsPutRequest($id, $api_v1_adspaces_id_wl_campaigns_put_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesIdWlCampaignsPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesIdWlCampaignsPutRequest $api_v1_adspaces_id_wl_campaigns_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesIdWlCampaignsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesIdWlCampaignsPutRequest($id, $api_v1_adspaces_id_wl_campaigns_put_request, string $contentType = self::contentTypes['apiV1AdspacesIdWlCampaignsPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesIdWlCampaignsPut'
            );
        }

        // verify the required parameter 'api_v1_adspaces_id_wl_campaigns_put_request' is set
        if ($api_v1_adspaces_id_wl_campaigns_put_request === null || (is_array($api_v1_adspaces_id_wl_campaigns_put_request) && count($api_v1_adspaces_id_wl_campaigns_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_id_wl_campaigns_put_request when calling apiV1AdspacesIdWlCampaignsPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/{id}/wl-campaigns';
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
        if (isset($api_v1_adspaces_id_wl_campaigns_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_id_wl_campaigns_put_request));
            } else {
                $httpBody = $api_v1_adspaces_id_wl_campaigns_put_request;
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
     * Operation apiV1AdspacesMassDeletePost
     *
     * Soft-delete several ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest $api_v1_adspaces_mass_delete_post_request api_v1_adspaces_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesMassDeletePost($api_v1_adspaces_mass_delete_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassDeletePost'][0])
    {
        $this->apiV1AdspacesMassDeletePostWithHttpInfo($api_v1_adspaces_mass_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesMassDeletePostWithHttpInfo
     *
     * Soft-delete several ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest $api_v1_adspaces_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesMassDeletePostWithHttpInfo($api_v1_adspaces_mass_delete_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassDeletePost'][0])
    {
        $request = $this->apiV1AdspacesMassDeletePostRequest($api_v1_adspaces_mass_delete_post_request, $contentType);

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
     * Operation apiV1AdspacesMassDeletePostAsync
     *
     * Soft-delete several ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest $api_v1_adspaces_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesMassDeletePostAsync($api_v1_adspaces_mass_delete_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassDeletePost'][0])
    {
        return $this->apiV1AdspacesMassDeletePostAsyncWithHttpInfo($api_v1_adspaces_mass_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesMassDeletePostAsyncWithHttpInfo
     *
     * Soft-delete several ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest $api_v1_adspaces_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesMassDeletePostAsyncWithHttpInfo($api_v1_adspaces_mass_delete_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassDeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesMassDeletePostRequest($api_v1_adspaces_mass_delete_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesMassDeletePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassDeletePostRequest $api_v1_adspaces_mass_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesMassDeletePostRequest($api_v1_adspaces_mass_delete_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassDeletePost'][0])
    {

        // verify the required parameter 'api_v1_adspaces_mass_delete_post_request' is set
        if ($api_v1_adspaces_mass_delete_post_request === null || (is_array($api_v1_adspaces_mass_delete_post_request) && count($api_v1_adspaces_mass_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_mass_delete_post_request when calling apiV1AdspacesMassDeletePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/mass-delete';
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
        if (isset($api_v1_adspaces_mass_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_mass_delete_post_request));
            } else {
                $httpBody = $api_v1_adspaces_mass_delete_post_request;
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
     * Operation apiV1AdspacesMassRecoverPost
     *
     * Recover several soft-deleted ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest $api_v1_adspaces_mass_recover_post_request api_v1_adspaces_mass_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesMassRecoverPost($api_v1_adspaces_mass_recover_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassRecoverPost'][0])
    {
        $this->apiV1AdspacesMassRecoverPostWithHttpInfo($api_v1_adspaces_mass_recover_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesMassRecoverPostWithHttpInfo
     *
     * Recover several soft-deleted ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest $api_v1_adspaces_mass_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesMassRecoverPostWithHttpInfo($api_v1_adspaces_mass_recover_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassRecoverPost'][0])
    {
        $request = $this->apiV1AdspacesMassRecoverPostRequest($api_v1_adspaces_mass_recover_post_request, $contentType);

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
     * Operation apiV1AdspacesMassRecoverPostAsync
     *
     * Recover several soft-deleted ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest $api_v1_adspaces_mass_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesMassRecoverPostAsync($api_v1_adspaces_mass_recover_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassRecoverPost'][0])
    {
        return $this->apiV1AdspacesMassRecoverPostAsyncWithHttpInfo($api_v1_adspaces_mass_recover_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesMassRecoverPostAsyncWithHttpInfo
     *
     * Recover several soft-deleted ad zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest $api_v1_adspaces_mass_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesMassRecoverPostAsyncWithHttpInfo($api_v1_adspaces_mass_recover_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassRecoverPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesMassRecoverPostRequest($api_v1_adspaces_mass_recover_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesMassRecoverPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesMassRecoverPostRequest $api_v1_adspaces_mass_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesMassRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesMassRecoverPostRequest($api_v1_adspaces_mass_recover_post_request, string $contentType = self::contentTypes['apiV1AdspacesMassRecoverPost'][0])
    {

        // verify the required parameter 'api_v1_adspaces_mass_recover_post_request' is set
        if ($api_v1_adspaces_mass_recover_post_request === null || (is_array($api_v1_adspaces_mass_recover_post_request) && count($api_v1_adspaces_mass_recover_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_mass_recover_post_request when calling apiV1AdspacesMassRecoverPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/mass-recover';
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
        if (isset($api_v1_adspaces_mass_recover_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_mass_recover_post_request));
            } else {
                $httpBody = $api_v1_adspaces_mass_recover_post_request;
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
     * Operation apiV1AdspacesTemplatesGet
     *
     * List your saved ad zone templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesTemplatesGet(string $contentType = self::contentTypes['apiV1AdspacesTemplatesGet'][0])
    {
        list($response) = $this->apiV1AdspacesTemplatesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesTemplatesGetWithHttpInfo
     *
     * List your saved ad zone templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTemplatesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1AdspacesTemplatesGet'][0])
    {
        $request = $this->apiV1AdspacesTemplatesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
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
                '\GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
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
     * Operation apiV1AdspacesTemplatesGetAsync
     *
     * List your saved ad zone templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesGetAsync(string $contentType = self::contentTypes['apiV1AdspacesTemplatesGet'][0])
    {
        return $this->apiV1AdspacesTemplatesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTemplatesGetAsyncWithHttpInfo
     *
     * List your saved ad zone templates.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1AdspacesTemplatesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesTemplatesGet200ResponseInner[]';
        $request = $this->apiV1AdspacesTemplatesGetRequest($contentType);

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
     * Create request for operation 'apiV1AdspacesTemplatesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTemplatesGetRequest(string $contentType = self::contentTypes['apiV1AdspacesTemplatesGet'][0])
    {


        $resourcePath = '/api/v1/adspaces/templates';
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
     * Operation apiV1AdspacesTemplatesIdDelete
     *
     * Delete a template.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesTemplatesIdDelete($id, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdDelete'][0])
    {
        $this->apiV1AdspacesTemplatesIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1AdspacesTemplatesIdDeleteWithHttpInfo
     *
     * Delete a template.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTemplatesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdDelete'][0])
    {
        $request = $this->apiV1AdspacesTemplatesIdDeleteRequest($id, $contentType);

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
     * Operation apiV1AdspacesTemplatesIdDeleteAsync
     *
     * Delete a template.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdDelete'][0])
    {
        return $this->apiV1AdspacesTemplatesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTemplatesIdDeleteAsyncWithHttpInfo
     *
     * Delete a template.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesTemplatesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1AdspacesTemplatesIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTemplatesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesTemplatesIdDelete'
            );
        }


        $resourcePath = '/api/v1/adspaces/templates/{id}';
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
     * Operation apiV1AdspacesTemplatesIdPut
     *
     * Replace a template&#39;s data.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest $api_v1_adspaces_templates_id_put_request api_v1_adspaces_templates_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesTemplatesIdPut($id, $api_v1_adspaces_templates_id_put_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdPut'][0])
    {
        $this->apiV1AdspacesTemplatesIdPutWithHttpInfo($id, $api_v1_adspaces_templates_id_put_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesTemplatesIdPutWithHttpInfo
     *
     * Replace a template&#39;s data.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest $api_v1_adspaces_templates_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTemplatesIdPutWithHttpInfo($id, $api_v1_adspaces_templates_id_put_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdPut'][0])
    {
        $request = $this->apiV1AdspacesTemplatesIdPutRequest($id, $api_v1_adspaces_templates_id_put_request, $contentType);

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
     * Operation apiV1AdspacesTemplatesIdPutAsync
     *
     * Replace a template&#39;s data.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest $api_v1_adspaces_templates_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesIdPutAsync($id, $api_v1_adspaces_templates_id_put_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdPut'][0])
    {
        return $this->apiV1AdspacesTemplatesIdPutAsyncWithHttpInfo($id, $api_v1_adspaces_templates_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTemplatesIdPutAsyncWithHttpInfo
     *
     * Replace a template&#39;s data.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest $api_v1_adspaces_templates_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesIdPutAsyncWithHttpInfo($id, $api_v1_adspaces_templates_id_put_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesTemplatesIdPutRequest($id, $api_v1_adspaces_templates_id_put_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesTemplatesIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesIdPutRequest $api_v1_adspaces_templates_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTemplatesIdPutRequest($id, $api_v1_adspaces_templates_id_put_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1AdspacesTemplatesIdPut'
            );
        }

        // verify the required parameter 'api_v1_adspaces_templates_id_put_request' is set
        if ($api_v1_adspaces_templates_id_put_request === null || (is_array($api_v1_adspaces_templates_id_put_request) && count($api_v1_adspaces_templates_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_templates_id_put_request when calling apiV1AdspacesTemplatesIdPut'
            );
        }


        $resourcePath = '/api/v1/adspaces/templates/{id}';
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
        if (isset($api_v1_adspaces_templates_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_templates_id_put_request));
            } else {
                $httpBody = $api_v1_adspaces_templates_id_put_request;
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
     * Operation apiV1AdspacesTemplatesPost
     *
     * Save an ad zone template.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest $api_v1_adspaces_templates_post_request api_v1_adspaces_templates_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1AdspacesTemplatesPost($api_v1_adspaces_templates_post_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesPost'][0])
    {
        $this->apiV1AdspacesTemplatesPostWithHttpInfo($api_v1_adspaces_templates_post_request, $contentType);
    }

    /**
     * Operation apiV1AdspacesTemplatesPostWithHttpInfo
     *
     * Save an ad zone template.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest $api_v1_adspaces_templates_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTemplatesPostWithHttpInfo($api_v1_adspaces_templates_post_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesPost'][0])
    {
        $request = $this->apiV1AdspacesTemplatesPostRequest($api_v1_adspaces_templates_post_request, $contentType);

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
     * Operation apiV1AdspacesTemplatesPostAsync
     *
     * Save an ad zone template.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest $api_v1_adspaces_templates_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesPostAsync($api_v1_adspaces_templates_post_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesPost'][0])
    {
        return $this->apiV1AdspacesTemplatesPostAsyncWithHttpInfo($api_v1_adspaces_templates_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTemplatesPostAsyncWithHttpInfo
     *
     * Save an ad zone template.
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest $api_v1_adspaces_templates_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTemplatesPostAsyncWithHttpInfo($api_v1_adspaces_templates_post_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1AdspacesTemplatesPostRequest($api_v1_adspaces_templates_post_request, $contentType);

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
     * Create request for operation 'apiV1AdspacesTemplatesPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1AdspacesTemplatesPostRequest $api_v1_adspaces_templates_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTemplatesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTemplatesPostRequest($api_v1_adspaces_templates_post_request, string $contentType = self::contentTypes['apiV1AdspacesTemplatesPost'][0])
    {

        // verify the required parameter 'api_v1_adspaces_templates_post_request' is set
        if ($api_v1_adspaces_templates_post_request === null || (is_array($api_v1_adspaces_templates_post_request) && count($api_v1_adspaces_templates_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_adspaces_templates_post_request when calling apiV1AdspacesTemplatesPost'
            );
        }


        $resourcePath = '/api/v1/adspaces/templates';
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
        if (isset($api_v1_adspaces_templates_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_adspaces_templates_post_request));
            } else {
                $httpBody = $api_v1_adspaces_templates_post_request;
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
     * Operation apiV1AdspacesTypePost
     *
     * Create a new adspace.
     *
     * @param  string $type type (required)
     * @param  \GoAdServerApi\Model\AdspaceCreate $adspace_create adspace_create (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1AdspacesTypePost($type, $adspace_create, string $contentType = self::contentTypes['apiV1AdspacesTypePost'][0])
    {
        list($response) = $this->apiV1AdspacesTypePostWithHttpInfo($type, $adspace_create, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesTypePostWithHttpInfo
     *
     * Create a new adspace.
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\AdspaceCreate $adspace_create (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTypePostWithHttpInfo($type, $adspace_create, string $contentType = self::contentTypes['apiV1AdspacesTypePost'][0])
    {
        $request = $this->apiV1AdspacesTypePostRequest($type, $adspace_create, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
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
                '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response',
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
     * Operation apiV1AdspacesTypePostAsync
     *
     * Create a new adspace.
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\AdspaceCreate $adspace_create (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTypePostAsync($type, $adspace_create, string $contentType = self::contentTypes['apiV1AdspacesTypePost'][0])
    {
        return $this->apiV1AdspacesTypePostAsyncWithHttpInfo($type, $adspace_create, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTypePostAsyncWithHttpInfo
     *
     * Create a new adspace.
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\AdspaceCreate $adspace_create (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTypePostAsyncWithHttpInfo($type, $adspace_create, string $contentType = self::contentTypes['apiV1AdspacesTypePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1AdspacesIdPatch200Response';
        $request = $this->apiV1AdspacesTypePostRequest($type, $adspace_create, $contentType);

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
     * Create request for operation 'apiV1AdspacesTypePost'
     *
     * @param  string $type (required)
     * @param  \GoAdServerApi\Model\AdspaceCreate $adspace_create (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTypePostRequest($type, $adspace_create, string $contentType = self::contentTypes['apiV1AdspacesTypePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1AdspacesTypePost'
            );
        }

        // verify the required parameter 'adspace_create' is set
        if ($adspace_create === null || (is_array($adspace_create) && count($adspace_create) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $adspace_create when calling apiV1AdspacesTypePost'
            );
        }


        $resourcePath = '/api/v1/adspaces/{type}';
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


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($adspace_create)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($adspace_create));
            } else {
                $httpBody = $adspace_create;
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
     * Operation apiV1AdspacesTypeSchemaGet
     *
     * Field metadata for create/update of one adspace type.
     *
     * @param  string $type Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\AdspaceSchema
     */
    public function apiV1AdspacesTypeSchemaGet($type, string $contentType = self::contentTypes['apiV1AdspacesTypeSchemaGet'][0])
    {
        list($response) = $this->apiV1AdspacesTypeSchemaGetWithHttpInfo($type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1AdspacesTypeSchemaGetWithHttpInfo
     *
     * Field metadata for create/update of one adspace type.
     *
     * @param  string $type Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\AdspaceSchema, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1AdspacesTypeSchemaGetWithHttpInfo($type, string $contentType = self::contentTypes['apiV1AdspacesTypeSchemaGet'][0])
    {
        $request = $this->apiV1AdspacesTypeSchemaGetRequest($type, $contentType);

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
                        '\GoAdServerApi\Model\AdspaceSchema',
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
                '\GoAdServerApi\Model\AdspaceSchema',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\AdspaceSchema',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1AdspacesTypeSchemaGetAsync
     *
     * Field metadata for create/update of one adspace type.
     *
     * @param  string $type Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTypeSchemaGetAsync($type, string $contentType = self::contentTypes['apiV1AdspacesTypeSchemaGet'][0])
    {
        return $this->apiV1AdspacesTypeSchemaGetAsyncWithHttpInfo($type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1AdspacesTypeSchemaGetAsyncWithHttpInfo
     *
     * Field metadata for create/update of one adspace type.
     *
     * @param  string $type Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1AdspacesTypeSchemaGetAsyncWithHttpInfo($type, string $contentType = self::contentTypes['apiV1AdspacesTypeSchemaGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\AdspaceSchema';
        $request = $this->apiV1AdspacesTypeSchemaGetRequest($type, $contentType);

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
     * Create request for operation 'apiV1AdspacesTypeSchemaGet'
     *
     * @param  string $type Adspace type config key — see &#x60;GET /api/v1/lookups/adspace-types&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1AdspacesTypeSchemaGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1AdspacesTypeSchemaGetRequest($type, string $contentType = self::contentTypes['apiV1AdspacesTypeSchemaGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1AdspacesTypeSchemaGet'
            );
        }


        $resourcePath = '/api/v1/adspaces/{type}/_schema';
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

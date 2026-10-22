<?php
/**
 * CreativesApi
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
 * CreativesApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class CreativesApi
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
        'apiV1CreativeFoldersGet' => [
            'application/json',
        ],
        'apiV1CreativeFoldersIdDelete' => [
            'application/json',
        ],
        'apiV1CreativeFoldersIdPut' => [
            'application/json',
        ],
        'apiV1CreativeFoldersPost' => [
            'application/json',
        ],
        'apiV1CreativesBulkMovePost' => [
            'application/json',
        ],
        'apiV1CreativesGet' => [
            'application/json',
        ],
        'apiV1CreativesIdDelete' => [
            'application/json',
        ],
        'apiV1CreativesIdGet' => [
            'application/json',
        ],
        'apiV1CreativesIdNamePut' => [
            'application/json',
        ],
        'apiV1CreativesIdPut' => [
            'application/json',
        ],
        'apiV1CreativesIdTagsPut' => [
            'application/json',
        ],
        'apiV1CreativesIdUsedInGet' => [
            'application/json',
        ],
        'apiV1CreativesUploadPost' => [
            'multipart/form-data',
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
     * Operation apiV1CreativeFoldersGet
     *
     * List media-bank folders.
     *
     * @param  string|null $with_counts Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. (optional)
     * @param  string|null $creative_type Narrow the counts to one creative type (comma-separated list accepted). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativeFoldersGet($with_counts = null, $creative_type = null, string $contentType = self::contentTypes['apiV1CreativeFoldersGet'][0])
    {
        $this->apiV1CreativeFoldersGetWithHttpInfo($with_counts, $creative_type, $contentType);
    }

    /**
     * Operation apiV1CreativeFoldersGetWithHttpInfo
     *
     * List media-bank folders.
     *
     * @param  string|null $with_counts Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. (optional)
     * @param  string|null $creative_type Narrow the counts to one creative type (comma-separated list accepted). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativeFoldersGetWithHttpInfo($with_counts = null, $creative_type = null, string $contentType = self::contentTypes['apiV1CreativeFoldersGet'][0])
    {
        $request = $this->apiV1CreativeFoldersGetRequest($with_counts, $creative_type, $contentType);

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
     * Operation apiV1CreativeFoldersGetAsync
     *
     * List media-bank folders.
     *
     * @param  string|null $with_counts Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. (optional)
     * @param  string|null $creative_type Narrow the counts to one creative type (comma-separated list accepted). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersGetAsync($with_counts = null, $creative_type = null, string $contentType = self::contentTypes['apiV1CreativeFoldersGet'][0])
    {
        return $this->apiV1CreativeFoldersGetAsyncWithHttpInfo($with_counts, $creative_type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativeFoldersGetAsyncWithHttpInfo
     *
     * List media-bank folders.
     *
     * @param  string|null $with_counts Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. (optional)
     * @param  string|null $creative_type Narrow the counts to one creative type (comma-separated list accepted). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersGetAsyncWithHttpInfo($with_counts = null, $creative_type = null, string $contentType = self::contentTypes['apiV1CreativeFoldersGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativeFoldersGetRequest($with_counts, $creative_type, $contentType);

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
     * Create request for operation 'apiV1CreativeFoldersGet'
     *
     * @param  string|null $with_counts Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. (optional)
     * @param  string|null $creative_type Narrow the counts to one creative type (comma-separated list accepted). (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativeFoldersGetRequest($with_counts = null, $creative_type = null, string $contentType = self::contentTypes['apiV1CreativeFoldersGet'][0])
    {




        $resourcePath = '/api/v1/creative-folders';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $with_counts,
            'with_counts', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $creative_type,
            'creative_type', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CreativeFoldersIdDelete
     *
     * Delete a folder.
     *
     * @param  int $id id (required)
     * @param  int|null $move_to File the folder&#39;s creatives here instead of unfiling them. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativeFoldersIdDelete($id, $move_to = null, string $contentType = self::contentTypes['apiV1CreativeFoldersIdDelete'][0])
    {
        $this->apiV1CreativeFoldersIdDeleteWithHttpInfo($id, $move_to, $contentType);
    }

    /**
     * Operation apiV1CreativeFoldersIdDeleteWithHttpInfo
     *
     * Delete a folder.
     *
     * @param  int $id (required)
     * @param  int|null $move_to File the folder&#39;s creatives here instead of unfiling them. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativeFoldersIdDeleteWithHttpInfo($id, $move_to = null, string $contentType = self::contentTypes['apiV1CreativeFoldersIdDelete'][0])
    {
        $request = $this->apiV1CreativeFoldersIdDeleteRequest($id, $move_to, $contentType);

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
     * Operation apiV1CreativeFoldersIdDeleteAsync
     *
     * Delete a folder.
     *
     * @param  int $id (required)
     * @param  int|null $move_to File the folder&#39;s creatives here instead of unfiling them. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersIdDeleteAsync($id, $move_to = null, string $contentType = self::contentTypes['apiV1CreativeFoldersIdDelete'][0])
    {
        return $this->apiV1CreativeFoldersIdDeleteAsyncWithHttpInfo($id, $move_to, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativeFoldersIdDeleteAsyncWithHttpInfo
     *
     * Delete a folder.
     *
     * @param  int $id (required)
     * @param  int|null $move_to File the folder&#39;s creatives here instead of unfiling them. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersIdDeleteAsyncWithHttpInfo($id, $move_to = null, string $contentType = self::contentTypes['apiV1CreativeFoldersIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativeFoldersIdDeleteRequest($id, $move_to, $contentType);

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
     * Create request for operation 'apiV1CreativeFoldersIdDelete'
     *
     * @param  int $id (required)
     * @param  int|null $move_to File the folder&#39;s creatives here instead of unfiling them. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativeFoldersIdDeleteRequest($id, $move_to = null, string $contentType = self::contentTypes['apiV1CreativeFoldersIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativeFoldersIdDelete'
            );
        }



        $resourcePath = '/api/v1/creative-folders/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $move_to,
            'move_to', // param base name
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
     * Operation apiV1CreativeFoldersIdPut
     *
     * Rename or move a folder.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest $api_v1_creative_folders_id_put_request api_v1_creative_folders_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativeFoldersIdPut($id, $api_v1_creative_folders_id_put_request, string $contentType = self::contentTypes['apiV1CreativeFoldersIdPut'][0])
    {
        $this->apiV1CreativeFoldersIdPutWithHttpInfo($id, $api_v1_creative_folders_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativeFoldersIdPutWithHttpInfo
     *
     * Rename or move a folder.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest $api_v1_creative_folders_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativeFoldersIdPutWithHttpInfo($id, $api_v1_creative_folders_id_put_request, string $contentType = self::contentTypes['apiV1CreativeFoldersIdPut'][0])
    {
        $request = $this->apiV1CreativeFoldersIdPutRequest($id, $api_v1_creative_folders_id_put_request, $contentType);

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
     * Operation apiV1CreativeFoldersIdPutAsync
     *
     * Rename or move a folder.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest $api_v1_creative_folders_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersIdPutAsync($id, $api_v1_creative_folders_id_put_request, string $contentType = self::contentTypes['apiV1CreativeFoldersIdPut'][0])
    {
        return $this->apiV1CreativeFoldersIdPutAsyncWithHttpInfo($id, $api_v1_creative_folders_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativeFoldersIdPutAsyncWithHttpInfo
     *
     * Rename or move a folder.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest $api_v1_creative_folders_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersIdPutAsyncWithHttpInfo($id, $api_v1_creative_folders_id_put_request, string $contentType = self::contentTypes['apiV1CreativeFoldersIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativeFoldersIdPutRequest($id, $api_v1_creative_folders_id_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativeFoldersIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest $api_v1_creative_folders_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativeFoldersIdPutRequest($id, $api_v1_creative_folders_id_put_request, string $contentType = self::contentTypes['apiV1CreativeFoldersIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativeFoldersIdPut'
            );
        }

        // verify the required parameter 'api_v1_creative_folders_id_put_request' is set
        if ($api_v1_creative_folders_id_put_request === null || (is_array($api_v1_creative_folders_id_put_request) && count($api_v1_creative_folders_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_folders_id_put_request when calling apiV1CreativeFoldersIdPut'
            );
        }


        $resourcePath = '/api/v1/creative-folders/{id}';
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
            [],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_creative_folders_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_folders_id_put_request));
            } else {
                $httpBody = $api_v1_creative_folders_id_put_request;
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
     * Operation apiV1CreativeFoldersPost
     *
     * Create a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest $api_v1_creative_folders_post_request api_v1_creative_folders_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativeFoldersPost($api_v1_creative_folders_post_request, string $contentType = self::contentTypes['apiV1CreativeFoldersPost'][0])
    {
        $this->apiV1CreativeFoldersPostWithHttpInfo($api_v1_creative_folders_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativeFoldersPostWithHttpInfo
     *
     * Create a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest $api_v1_creative_folders_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativeFoldersPostWithHttpInfo($api_v1_creative_folders_post_request, string $contentType = self::contentTypes['apiV1CreativeFoldersPost'][0])
    {
        $request = $this->apiV1CreativeFoldersPostRequest($api_v1_creative_folders_post_request, $contentType);

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
     * Operation apiV1CreativeFoldersPostAsync
     *
     * Create a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest $api_v1_creative_folders_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersPostAsync($api_v1_creative_folders_post_request, string $contentType = self::contentTypes['apiV1CreativeFoldersPost'][0])
    {
        return $this->apiV1CreativeFoldersPostAsyncWithHttpInfo($api_v1_creative_folders_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativeFoldersPostAsyncWithHttpInfo
     *
     * Create a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest $api_v1_creative_folders_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativeFoldersPostAsyncWithHttpInfo($api_v1_creative_folders_post_request, string $contentType = self::contentTypes['apiV1CreativeFoldersPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativeFoldersPostRequest($api_v1_creative_folders_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativeFoldersPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest $api_v1_creative_folders_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativeFoldersPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativeFoldersPostRequest($api_v1_creative_folders_post_request, string $contentType = self::contentTypes['apiV1CreativeFoldersPost'][0])
    {

        // verify the required parameter 'api_v1_creative_folders_post_request' is set
        if ($api_v1_creative_folders_post_request === null || (is_array($api_v1_creative_folders_post_request) && count($api_v1_creative_folders_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_folders_post_request when calling apiV1CreativeFoldersPost'
            );
        }


        $resourcePath = '/api/v1/creative-folders';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            [],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_creative_folders_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_folders_post_request));
            } else {
                $httpBody = $api_v1_creative_folders_post_request;
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
     * Operation apiV1CreativesBulkMovePost
     *
     * File creatives into a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest $api_v1_creatives_bulk_move_post_request api_v1_creatives_bulk_move_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkMovePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesBulkMovePost($api_v1_creatives_bulk_move_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkMovePost'][0])
    {
        $this->apiV1CreativesBulkMovePostWithHttpInfo($api_v1_creatives_bulk_move_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativesBulkMovePostWithHttpInfo
     *
     * File creatives into a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest $api_v1_creatives_bulk_move_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkMovePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesBulkMovePostWithHttpInfo($api_v1_creatives_bulk_move_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkMovePost'][0])
    {
        $request = $this->apiV1CreativesBulkMovePostRequest($api_v1_creatives_bulk_move_post_request, $contentType);

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
     * Operation apiV1CreativesBulkMovePostAsync
     *
     * File creatives into a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest $api_v1_creatives_bulk_move_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkMovePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkMovePostAsync($api_v1_creatives_bulk_move_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkMovePost'][0])
    {
        return $this->apiV1CreativesBulkMovePostAsyncWithHttpInfo($api_v1_creatives_bulk_move_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesBulkMovePostAsyncWithHttpInfo
     *
     * File creatives into a folder.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest $api_v1_creatives_bulk_move_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkMovePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkMovePostAsyncWithHttpInfo($api_v1_creatives_bulk_move_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkMovePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesBulkMovePostRequest($api_v1_creatives_bulk_move_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativesBulkMovePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest $api_v1_creatives_bulk_move_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkMovePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesBulkMovePostRequest($api_v1_creatives_bulk_move_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkMovePost'][0])
    {

        // verify the required parameter 'api_v1_creatives_bulk_move_post_request' is set
        if ($api_v1_creatives_bulk_move_post_request === null || (is_array($api_v1_creatives_bulk_move_post_request) && count($api_v1_creatives_bulk_move_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_bulk_move_post_request when calling apiV1CreativesBulkMovePost'
            );
        }


        $resourcePath = '/api/v1/creatives/bulk-move';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            [],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_creatives_bulk_move_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_bulk_move_post_request));
            } else {
                $httpBody = $api_v1_creatives_bulk_move_post_request;
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
     * Operation apiV1CreativesGet
     *
     * List the creative library.
     *
     * @param  string|null $creative_type One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) (optional)
     * @param  int|null $sizeid Banner size id. (optional)
     * @param  string|null $orientation orientation (optional)
     * @param  string|null $deleted 1 &#x3D; only deleted creatives. (optional)
     * @param  string|null $all 1 &#x3D; live and deleted creatives together. (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $folder_id Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. (optional)
     * @param  string|null $include_subfolders With folder_id, also return creatives filed in that folder&#39;s subfolders. (optional, default to '1')
     * @param  int|null $page page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter|null $per_page Page size (max 100), or &#x60;all&#x60; for the whole library in one page. (optional)
     * @param  int|null $limit Offset paging: page size (per_page wins when both are sent). (optional)
     * @param  int|null $offset Offset paging: rows to skip. (optional)
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_type Exactly one type; creative_type also takes a list. (optional)
     * @param  int|null $filter_sizeid Same as sizeid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativesGet200Response
     */
    public function apiV1CreativesGet($creative_type = null, $sizeid = null, $orientation = null, $deleted = null, $all = null, $filter_name = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $folder_id = null, $include_subfolders = '1', $page = 1, $per_page = null, $limit = null, $offset = null, $filter_id = null, $filter_type = null, $filter_sizeid = null, string $contentType = self::contentTypes['apiV1CreativesGet'][0])
    {
        list($response) = $this->apiV1CreativesGetWithHttpInfo($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativesGetWithHttpInfo
     *
     * List the creative library.
     *
     * @param  string|null $creative_type One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) (optional)
     * @param  int|null $sizeid Banner size id. (optional)
     * @param  string|null $orientation (optional)
     * @param  string|null $deleted 1 &#x3D; only deleted creatives. (optional)
     * @param  string|null $all 1 &#x3D; live and deleted creatives together. (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $folder_id Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. (optional)
     * @param  string|null $include_subfolders With folder_id, also return creatives filed in that folder&#39;s subfolders. (optional, default to '1')
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter|null $per_page Page size (max 100), or &#x60;all&#x60; for the whole library in one page. (optional)
     * @param  int|null $limit Offset paging: page size (per_page wins when both are sent). (optional)
     * @param  int|null $offset Offset paging: rows to skip. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_type Exactly one type; creative_type also takes a list. (optional)
     * @param  int|null $filter_sizeid Same as sizeid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesGetWithHttpInfo($creative_type = null, $sizeid = null, $orientation = null, $deleted = null, $all = null, $filter_name = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $folder_id = null, $include_subfolders = '1', $page = 1, $per_page = null, $limit = null, $offset = null, $filter_id = null, $filter_type = null, $filter_sizeid = null, string $contentType = self::contentTypes['apiV1CreativesGet'][0])
    {
        $request = $this->apiV1CreativesGetRequest($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CreativesGetAsync
     *
     * List the creative library.
     *
     * @param  string|null $creative_type One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) (optional)
     * @param  int|null $sizeid Banner size id. (optional)
     * @param  string|null $orientation (optional)
     * @param  string|null $deleted 1 &#x3D; only deleted creatives. (optional)
     * @param  string|null $all 1 &#x3D; live and deleted creatives together. (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $folder_id Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. (optional)
     * @param  string|null $include_subfolders With folder_id, also return creatives filed in that folder&#39;s subfolders. (optional, default to '1')
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter|null $per_page Page size (max 100), or &#x60;all&#x60; for the whole library in one page. (optional)
     * @param  int|null $limit Offset paging: page size (per_page wins when both are sent). (optional)
     * @param  int|null $offset Offset paging: rows to skip. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_type Exactly one type; creative_type also takes a list. (optional)
     * @param  int|null $filter_sizeid Same as sizeid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesGetAsync($creative_type = null, $sizeid = null, $orientation = null, $deleted = null, $all = null, $filter_name = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $folder_id = null, $include_subfolders = '1', $page = 1, $per_page = null, $limit = null, $offset = null, $filter_id = null, $filter_type = null, $filter_sizeid = null, string $contentType = self::contentTypes['apiV1CreativesGet'][0])
    {
        return $this->apiV1CreativesGetAsyncWithHttpInfo($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesGetAsyncWithHttpInfo
     *
     * List the creative library.
     *
     * @param  string|null $creative_type One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) (optional)
     * @param  int|null $sizeid Banner size id. (optional)
     * @param  string|null $orientation (optional)
     * @param  string|null $deleted 1 &#x3D; only deleted creatives. (optional)
     * @param  string|null $all 1 &#x3D; live and deleted creatives together. (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $folder_id Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. (optional)
     * @param  string|null $include_subfolders With folder_id, also return creatives filed in that folder&#39;s subfolders. (optional, default to '1')
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter|null $per_page Page size (max 100), or &#x60;all&#x60; for the whole library in one page. (optional)
     * @param  int|null $limit Offset paging: page size (per_page wins when both are sent). (optional)
     * @param  int|null $offset Offset paging: rows to skip. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_type Exactly one type; creative_type also takes a list. (optional)
     * @param  int|null $filter_sizeid Same as sizeid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesGetAsyncWithHttpInfo($creative_type = null, $sizeid = null, $orientation = null, $deleted = null, $all = null, $filter_name = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $folder_id = null, $include_subfolders = '1', $page = 1, $per_page = null, $limit = null, $offset = null, $filter_id = null, $filter_type = null, $filter_sizeid = null, string $contentType = self::contentTypes['apiV1CreativesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativesGet200Response';
        $request = $this->apiV1CreativesGetRequest($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid, $contentType);

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
     * Create request for operation 'apiV1CreativesGet'
     *
     * @param  string|null $creative_type One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) (optional)
     * @param  int|null $sizeid Banner size id. (optional)
     * @param  string|null $orientation (optional)
     * @param  string|null $deleted 1 &#x3D; only deleted creatives. (optional)
     * @param  string|null $all 1 &#x3D; live and deleted creatives together. (optional)
     * @param  string|null $filter_name Name contains. (optional)
     * @param  string|null $search Name contains. (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $folder_id Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. (optional)
     * @param  string|null $include_subfolders With folder_id, also return creatives filed in that folder&#39;s subfolders. (optional, default to '1')
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter|null $per_page Page size (max 100), or &#x60;all&#x60; for the whole library in one page. (optional)
     * @param  int|null $limit Offset paging: page size (per_page wins when both are sent). (optional)
     * @param  int|null $offset Offset paging: rows to skip. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_type Exactly one type; creative_type also takes a list. (optional)
     * @param  int|null $filter_sizeid Same as sizeid. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesGetRequest($creative_type = null, $sizeid = null, $orientation = null, $deleted = null, $all = null, $filter_name = null, $search = null, $sort_by = 'id', $sort_dir = 'desc', $folder_id = null, $include_subfolders = '1', $page = 1, $per_page = null, $limit = null, $offset = null, $filter_id = null, $filter_type = null, $filter_sizeid = null, string $contentType = self::contentTypes['apiV1CreativesGet'][0])
    {




















        $resourcePath = '/api/v1/creatives';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $creative_type,
            'creative_type', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sizeid,
            'sizeid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $orientation,
            'orientation', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $deleted,
            'deleted', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $all,
            'all', // param base name
            'string', // openApiType
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
            $search,
            'search', // param base name
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
            $folder_id,
            'folder_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $include_subfolders,
            'include_subfolders', // param base name
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
            'int|string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
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
            $offset,
            'offset', // param base name
            'integer', // openApiType
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
            $filter_type,
            'filter_type', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_sizeid,
            'filter_sizeid', // param base name
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
     * Operation apiV1CreativesIdDelete
     *
     * Delete a creative.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdDelete($id, string $contentType = self::contentTypes['apiV1CreativesIdDelete'][0])
    {
        $this->apiV1CreativesIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CreativesIdDeleteWithHttpInfo
     *
     * Delete a creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdDelete'][0])
    {
        $request = $this->apiV1CreativesIdDeleteRequest($id, $contentType);

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
     * Operation apiV1CreativesIdDeleteAsync
     *
     * Delete a creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1CreativesIdDelete'][0])
    {
        return $this->apiV1CreativesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdDeleteAsyncWithHttpInfo
     *
     * Delete a creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativesIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1CreativesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdDelete'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}';
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
     * Operation apiV1CreativesIdGet
     *
     * Show one creative.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdGet($id, string $contentType = self::contentTypes['apiV1CreativesIdGet'][0])
    {
        $this->apiV1CreativesIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CreativesIdGetWithHttpInfo
     *
     * Show one creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdGet'][0])
    {
        $request = $this->apiV1CreativesIdGetRequest($id, $contentType);

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
     * Operation apiV1CreativesIdGetAsync
     *
     * Show one creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdGetAsync($id, string $contentType = self::contentTypes['apiV1CreativesIdGet'][0])
    {
        return $this->apiV1CreativesIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdGetAsyncWithHttpInfo
     *
     * Show one creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativesIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdGetRequest($id, string $contentType = self::contentTypes['apiV1CreativesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdGet'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}';
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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CreativesIdNamePut
     *
     * Rename a creative (panel inline edit).
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest $api_v1_creatives_id_name_put_request api_v1_creatives_id_name_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdNamePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdNamePut($id, $api_v1_creatives_id_name_put_request, string $contentType = self::contentTypes['apiV1CreativesIdNamePut'][0])
    {
        $this->apiV1CreativesIdNamePutWithHttpInfo($id, $api_v1_creatives_id_name_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativesIdNamePutWithHttpInfo
     *
     * Rename a creative (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest $api_v1_creatives_id_name_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdNamePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdNamePutWithHttpInfo($id, $api_v1_creatives_id_name_put_request, string $contentType = self::contentTypes['apiV1CreativesIdNamePut'][0])
    {
        $request = $this->apiV1CreativesIdNamePutRequest($id, $api_v1_creatives_id_name_put_request, $contentType);

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
     * Operation apiV1CreativesIdNamePutAsync
     *
     * Rename a creative (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest $api_v1_creatives_id_name_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdNamePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdNamePutAsync($id, $api_v1_creatives_id_name_put_request, string $contentType = self::contentTypes['apiV1CreativesIdNamePut'][0])
    {
        return $this->apiV1CreativesIdNamePutAsyncWithHttpInfo($id, $api_v1_creatives_id_name_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdNamePutAsyncWithHttpInfo
     *
     * Rename a creative (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest $api_v1_creatives_id_name_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdNamePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdNamePutAsyncWithHttpInfo($id, $api_v1_creatives_id_name_put_request, string $contentType = self::contentTypes['apiV1CreativesIdNamePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdNamePutRequest($id, $api_v1_creatives_id_name_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativesIdNamePut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest $api_v1_creatives_id_name_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdNamePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdNamePutRequest($id, $api_v1_creatives_id_name_put_request, string $contentType = self::contentTypes['apiV1CreativesIdNamePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdNamePut'
            );
        }

        // verify the required parameter 'api_v1_creatives_id_name_put_request' is set
        if ($api_v1_creatives_id_name_put_request === null || (is_array($api_v1_creatives_id_name_put_request) && count($api_v1_creatives_id_name_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_id_name_put_request when calling apiV1CreativesIdNamePut'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}/name';
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
        if (isset($api_v1_creatives_id_name_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_id_name_put_request));
            } else {
                $httpBody = $api_v1_creatives_id_name_put_request;
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
     * Operation apiV1CreativesIdPut
     *
     * Rename and/or re-tag a creative.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdPutRequest $api_v1_creatives_id_put_request api_v1_creatives_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdPut($id, $api_v1_creatives_id_put_request, string $contentType = self::contentTypes['apiV1CreativesIdPut'][0])
    {
        $this->apiV1CreativesIdPutWithHttpInfo($id, $api_v1_creatives_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativesIdPutWithHttpInfo
     *
     * Rename and/or re-tag a creative.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdPutRequest $api_v1_creatives_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdPutWithHttpInfo($id, $api_v1_creatives_id_put_request, string $contentType = self::contentTypes['apiV1CreativesIdPut'][0])
    {
        $request = $this->apiV1CreativesIdPutRequest($id, $api_v1_creatives_id_put_request, $contentType);

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
     * Operation apiV1CreativesIdPutAsync
     *
     * Rename and/or re-tag a creative.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdPutRequest $api_v1_creatives_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdPutAsync($id, $api_v1_creatives_id_put_request, string $contentType = self::contentTypes['apiV1CreativesIdPut'][0])
    {
        return $this->apiV1CreativesIdPutAsyncWithHttpInfo($id, $api_v1_creatives_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdPutAsyncWithHttpInfo
     *
     * Rename and/or re-tag a creative.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdPutRequest $api_v1_creatives_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdPutAsyncWithHttpInfo($id, $api_v1_creatives_id_put_request, string $contentType = self::contentTypes['apiV1CreativesIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdPutRequest($id, $api_v1_creatives_id_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativesIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdPutRequest $api_v1_creatives_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdPutRequest($id, $api_v1_creatives_id_put_request, string $contentType = self::contentTypes['apiV1CreativesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdPut'
            );
        }

        // verify the required parameter 'api_v1_creatives_id_put_request' is set
        if ($api_v1_creatives_id_put_request === null || (is_array($api_v1_creatives_id_put_request) && count($api_v1_creatives_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_id_put_request when calling apiV1CreativesIdPut'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}';
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
        if (isset($api_v1_creatives_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_id_put_request));
            } else {
                $httpBody = $api_v1_creatives_id_put_request;
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
     * Operation apiV1CreativesIdTagsPut
     *
     * Replace a creative&#39;s tags (panel inline edit).
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest $api_v1_creatives_id_tags_put_request api_v1_creatives_id_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdTagsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdTagsPut($id, $api_v1_creatives_id_tags_put_request, string $contentType = self::contentTypes['apiV1CreativesIdTagsPut'][0])
    {
        $this->apiV1CreativesIdTagsPutWithHttpInfo($id, $api_v1_creatives_id_tags_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativesIdTagsPutWithHttpInfo
     *
     * Replace a creative&#39;s tags (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest $api_v1_creatives_id_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdTagsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdTagsPutWithHttpInfo($id, $api_v1_creatives_id_tags_put_request, string $contentType = self::contentTypes['apiV1CreativesIdTagsPut'][0])
    {
        $request = $this->apiV1CreativesIdTagsPutRequest($id, $api_v1_creatives_id_tags_put_request, $contentType);

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
     * Operation apiV1CreativesIdTagsPutAsync
     *
     * Replace a creative&#39;s tags (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest $api_v1_creatives_id_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdTagsPutAsync($id, $api_v1_creatives_id_tags_put_request, string $contentType = self::contentTypes['apiV1CreativesIdTagsPut'][0])
    {
        return $this->apiV1CreativesIdTagsPutAsyncWithHttpInfo($id, $api_v1_creatives_id_tags_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdTagsPutAsyncWithHttpInfo
     *
     * Replace a creative&#39;s tags (panel inline edit).
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest $api_v1_creatives_id_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdTagsPutAsyncWithHttpInfo($id, $api_v1_creatives_id_tags_put_request, string $contentType = self::contentTypes['apiV1CreativesIdTagsPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdTagsPutRequest($id, $api_v1_creatives_id_tags_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativesIdTagsPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest $api_v1_creatives_id_tags_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdTagsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdTagsPutRequest($id, $api_v1_creatives_id_tags_put_request, string $contentType = self::contentTypes['apiV1CreativesIdTagsPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdTagsPut'
            );
        }

        // verify the required parameter 'api_v1_creatives_id_tags_put_request' is set
        if ($api_v1_creatives_id_tags_put_request === null || (is_array($api_v1_creatives_id_tags_put_request) && count($api_v1_creatives_id_tags_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_id_tags_put_request when calling apiV1CreativesIdTagsPut'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}/tags';
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
        if (isset($api_v1_creatives_id_tags_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_id_tags_put_request));
            } else {
                $httpBody = $api_v1_creatives_id_tags_put_request;
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
     * Operation apiV1CreativesIdUsedInGet
     *
     * List ads / pools referencing this creative.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdUsedInGet($id, string $contentType = self::contentTypes['apiV1CreativesIdUsedInGet'][0])
    {
        $this->apiV1CreativesIdUsedInGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CreativesIdUsedInGetWithHttpInfo
     *
     * List ads / pools referencing this creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdUsedInGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdUsedInGet'][0])
    {
        $request = $this->apiV1CreativesIdUsedInGetRequest($id, $contentType);

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
     * Operation apiV1CreativesIdUsedInGetAsync
     *
     * List ads / pools referencing this creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdUsedInGetAsync($id, string $contentType = self::contentTypes['apiV1CreativesIdUsedInGet'][0])
    {
        return $this->apiV1CreativesIdUsedInGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdUsedInGetAsyncWithHttpInfo
     *
     * List ads / pools referencing this creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdUsedInGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdUsedInGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdUsedInGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativesIdUsedInGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdUsedInGetRequest($id, string $contentType = self::contentTypes['apiV1CreativesIdUsedInGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdUsedInGet'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}/used-in';
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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1CreativesUploadPost
     *
     * Upload creative files.
     *
     * @param  \SplFileObject[] $files One or more creative files. (required)
     * @param  string|null $type audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). (optional, default to 'banner')
     * @param  int|null $banner_size Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. (optional)
     * @param  string|null $tags tags (optional)
     * @param  int|null $folder_id File the batch into this existing media-bank folder. Omit or 0 for unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder with this name under folder_id and file the batch there. (optional)
     * @param  string|null $upload_zip Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. (optional)
     * @param  string|null $folder_from_zip ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. (optional)
     * @param  string|null $folder_from_zip_paths ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. (optional)
     * @param  \SplFileObject|null $zipfile With upload_zip&#x3D;1: the archive to extract. (optional)
     * @param  string|null $icontags type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. (optional)
     * @param  string|null $properties Comma-separated property ids stamped on every created creative. (optional)
     * @param  string|null $skiperrors 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesUploadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesUploadPost($files, $type = 'banner', $banner_size = null, $tags = null, $folder_id = null, $folder_name = null, $upload_zip = null, $folder_from_zip = null, $folder_from_zip_paths = null, $zipfile = null, $icontags = null, $properties = null, $skiperrors = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CreativesUploadPost'][0])
    {
        $this->apiV1CreativesUploadPostWithHttpInfo($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);
    }

    /**
     * Operation apiV1CreativesUploadPostWithHttpInfo
     *
     * Upload creative files.
     *
     * @param  \SplFileObject[] $files One or more creative files. (required)
     * @param  string|null $type audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). (optional, default to 'banner')
     * @param  int|null $banner_size Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. (optional)
     * @param  string|null $tags (optional)
     * @param  int|null $folder_id File the batch into this existing media-bank folder. Omit or 0 for unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder with this name under folder_id and file the batch there. (optional)
     * @param  string|null $upload_zip Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. (optional)
     * @param  string|null $folder_from_zip ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. (optional)
     * @param  string|null $folder_from_zip_paths ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. (optional)
     * @param  \SplFileObject|null $zipfile With upload_zip&#x3D;1: the archive to extract. (optional)
     * @param  string|null $icontags type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. (optional)
     * @param  string|null $properties Comma-separated property ids stamped on every created creative. (optional)
     * @param  string|null $skiperrors 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesUploadPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesUploadPostWithHttpInfo($files, $type = 'banner', $banner_size = null, $tags = null, $folder_id = null, $folder_name = null, $upload_zip = null, $folder_from_zip = null, $folder_from_zip_paths = null, $zipfile = null, $icontags = null, $properties = null, $skiperrors = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CreativesUploadPost'][0])
    {
        $request = $this->apiV1CreativesUploadPostRequest($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);

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
     * Operation apiV1CreativesUploadPostAsync
     *
     * Upload creative files.
     *
     * @param  \SplFileObject[] $files One or more creative files. (required)
     * @param  string|null $type audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). (optional, default to 'banner')
     * @param  int|null $banner_size Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. (optional)
     * @param  string|null $tags (optional)
     * @param  int|null $folder_id File the batch into this existing media-bank folder. Omit or 0 for unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder with this name under folder_id and file the batch there. (optional)
     * @param  string|null $upload_zip Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. (optional)
     * @param  string|null $folder_from_zip ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. (optional)
     * @param  string|null $folder_from_zip_paths ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. (optional)
     * @param  \SplFileObject|null $zipfile With upload_zip&#x3D;1: the archive to extract. (optional)
     * @param  string|null $icontags type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. (optional)
     * @param  string|null $properties Comma-separated property ids stamped on every created creative. (optional)
     * @param  string|null $skiperrors 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesUploadPostAsync($files, $type = 'banner', $banner_size = null, $tags = null, $folder_id = null, $folder_name = null, $upload_zip = null, $folder_from_zip = null, $folder_from_zip_paths = null, $zipfile = null, $icontags = null, $properties = null, $skiperrors = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CreativesUploadPost'][0])
    {
        return $this->apiV1CreativesUploadPostAsyncWithHttpInfo($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesUploadPostAsyncWithHttpInfo
     *
     * Upload creative files.
     *
     * @param  \SplFileObject[] $files One or more creative files. (required)
     * @param  string|null $type audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). (optional, default to 'banner')
     * @param  int|null $banner_size Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. (optional)
     * @param  string|null $tags (optional)
     * @param  int|null $folder_id File the batch into this existing media-bank folder. Omit or 0 for unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder with this name under folder_id and file the batch there. (optional)
     * @param  string|null $upload_zip Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. (optional)
     * @param  string|null $folder_from_zip ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. (optional)
     * @param  string|null $folder_from_zip_paths ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. (optional)
     * @param  \SplFileObject|null $zipfile With upload_zip&#x3D;1: the archive to extract. (optional)
     * @param  string|null $icontags type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. (optional)
     * @param  string|null $properties Comma-separated property ids stamped on every created creative. (optional)
     * @param  string|null $skiperrors 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesUploadPostAsyncWithHttpInfo($files, $type = 'banner', $banner_size = null, $tags = null, $folder_id = null, $folder_name = null, $upload_zip = null, $folder_from_zip = null, $folder_from_zip_paths = null, $zipfile = null, $icontags = null, $properties = null, $skiperrors = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CreativesUploadPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesUploadPostRequest($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip, $contentType);

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
     * Create request for operation 'apiV1CreativesUploadPost'
     *
     * @param  \SplFileObject[] $files One or more creative files. (required)
     * @param  string|null $type audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). (optional, default to 'banner')
     * @param  int|null $banner_size Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. (optional)
     * @param  string|null $tags (optional)
     * @param  int|null $folder_id File the batch into this existing media-bank folder. Omit or 0 for unfiled. (optional)
     * @param  string|null $folder_name Create-or-reuse a folder with this name under folder_id and file the batch there. (optional)
     * @param  string|null $upload_zip Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. (optional)
     * @param  string|null $folder_from_zip ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. (optional)
     * @param  string|null $folder_from_zip_paths ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. (optional)
     * @param  \SplFileObject|null $zipfile With upload_zip&#x3D;1: the archive to extract. (optional)
     * @param  string|null $icontags type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. (optional)
     * @param  string|null $properties Comma-separated property ids stamped on every created creative. (optional)
     * @param  string|null $skiperrors 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. (optional)
     * @param  string|null $use_overlays 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. (optional)
     * @param  string|null $clone_per_overlay With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. (optional)
     * @param  \SplFileObject[]|null $overlay Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. (optional)
     * @param  \SplFileObject[]|null $overlays_zip ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesUploadPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesUploadPostRequest($files, $type = 'banner', $banner_size = null, $tags = null, $folder_id = null, $folder_name = null, $upload_zip = null, $folder_from_zip = null, $folder_from_zip_paths = null, $zipfile = null, $icontags = null, $properties = null, $skiperrors = null, $use_overlays = null, $clone_per_overlay = null, $overlay = null, $overlays_zip = null, string $contentType = self::contentTypes['apiV1CreativesUploadPost'][0])
    {

        // verify the required parameter 'files' is set
        if ($files === null || (is_array($files) && count($files) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $files when calling apiV1CreativesUploadPost'
            );
        }


















        $resourcePath = '/api/v1/creatives/upload';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;




        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'type' => $type,
            'files' => $files,
            'banner_size' => $banner_size,
            'tags' => $tags,
            'folder_id' => $folder_id,
            'folder_name' => $folder_name,
            'upload_zip' => $upload_zip,
            'folder_from_zip' => $folder_from_zip,
            'folder_from_zip_paths' => $folder_from_zip_paths,
            'zipfile' => $zipfile,
            'icontags' => $icontags,
            'properties' => $properties,
            'skiperrors' => $skiperrors,
            'use_overlays' => $use_overlays,
            'clone_per_overlay' => $clone_per_overlay,
            'overlay' => $overlay,
            'overlays_zip' => $overlays_zip,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

        $multipart = true;
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

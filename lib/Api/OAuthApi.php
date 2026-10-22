<?php
/**
 * OAuthApi
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
 * OAuthApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class OAuthApi
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
        'apiPublicIntegrationsHooksTokenPost' => [
            'application/json',
        ],
        'apiPublicOauthIntrospectPost' => [
            'application/x-www-form-urlencoded',
        ],
        'apiPublicOauthMetadataGet' => [
            'application/json',
        ],
        'apiPublicOauthRevokePost' => [
            'application/x-www-form-urlencoded',
        ],
        'apiPublicOauthTokenPost' => [
            'application/x-www-form-urlencoded',
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
     * Operation apiPublicIntegrationsHooksTokenPost
     *
     * Incoming webhook — create a lead, contact or task from JSON.
     *
     * @param  string $token whin_… from the connector. (required)
     * @param  array<string,mixed> $request_body request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicIntegrationsHooksTokenPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response
     */
    public function apiPublicIntegrationsHooksTokenPost($token, $request_body, string $contentType = self::contentTypes['apiPublicIntegrationsHooksTokenPost'][0])
    {
        list($response) = $this->apiPublicIntegrationsHooksTokenPostWithHttpInfo($token, $request_body, $contentType);
        return $response;
    }

    /**
     * Operation apiPublicIntegrationsHooksTokenPostWithHttpInfo
     *
     * Incoming webhook — create a lead, contact or task from JSON.
     *
     * @param  string $token whin_… from the connector. (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicIntegrationsHooksTokenPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiPublicIntegrationsHooksTokenPostWithHttpInfo($token, $request_body, string $contentType = self::contentTypes['apiPublicIntegrationsHooksTokenPost'][0])
    {
        $request = $this->apiPublicIntegrationsHooksTokenPostRequest($token, $request_body, $contentType);

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
                        '\GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response',
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
                '\GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiPublicIntegrationsHooksTokenPostAsync
     *
     * Incoming webhook — create a lead, contact or task from JSON.
     *
     * @param  string $token whin_… from the connector. (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicIntegrationsHooksTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicIntegrationsHooksTokenPostAsync($token, $request_body, string $contentType = self::contentTypes['apiPublicIntegrationsHooksTokenPost'][0])
    {
        return $this->apiPublicIntegrationsHooksTokenPostAsyncWithHttpInfo($token, $request_body, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiPublicIntegrationsHooksTokenPostAsyncWithHttpInfo
     *
     * Incoming webhook — create a lead, contact or task from JSON.
     *
     * @param  string $token whin_… from the connector. (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicIntegrationsHooksTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicIntegrationsHooksTokenPostAsyncWithHttpInfo($token, $request_body, string $contentType = self::contentTypes['apiPublicIntegrationsHooksTokenPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response';
        $request = $this->apiPublicIntegrationsHooksTokenPostRequest($token, $request_body, $contentType);

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
     * Create request for operation 'apiPublicIntegrationsHooksTokenPost'
     *
     * @param  string $token whin_… from the connector. (required)
     * @param  array<string,mixed> $request_body (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicIntegrationsHooksTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiPublicIntegrationsHooksTokenPostRequest($token, $request_body, string $contentType = self::contentTypes['apiPublicIntegrationsHooksTokenPost'][0])
    {

        // verify the required parameter 'token' is set
        if ($token === null || (is_array($token) && count($token) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $token when calling apiPublicIntegrationsHooksTokenPost'
            );
        }

        // verify the required parameter 'request_body' is set
        if ($request_body === null || (is_array($request_body) && count($request_body) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $request_body when calling apiPublicIntegrationsHooksTokenPost'
            );
        }


        $resourcePath = '/api/public/integrations/hooks/{token}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($token !== null) {
            $resourcePath = str_replace(
                '{' . 'token' . '}',
                ObjectSerializer::toPathValue($token),
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
     * Operation apiPublicOauthIntrospectPost
     *
     * Introspect one of the calling app&#39;s tokens (RFC 7662).
     *
     * @param  string $token token (required)
     * @param  string|null $client_id client_id (optional)
     * @param  string|null $client_secret client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthIntrospectPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response
     */
    public function apiPublicOauthIntrospectPost($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthIntrospectPost'][0])
    {
        list($response) = $this->apiPublicOauthIntrospectPostWithHttpInfo($token, $client_id, $client_secret, $contentType);
        return $response;
    }

    /**
     * Operation apiPublicOauthIntrospectPostWithHttpInfo
     *
     * Introspect one of the calling app&#39;s tokens (RFC 7662).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthIntrospectPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiPublicOauthIntrospectPostWithHttpInfo($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthIntrospectPost'][0])
    {
        $request = $this->apiPublicOauthIntrospectPostRequest($token, $client_id, $client_secret, $contentType);

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
                        '\GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response',
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
                '\GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiPublicOauthIntrospectPostAsync
     *
     * Introspect one of the calling app&#39;s tokens (RFC 7662).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthIntrospectPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthIntrospectPostAsync($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthIntrospectPost'][0])
    {
        return $this->apiPublicOauthIntrospectPostAsyncWithHttpInfo($token, $client_id, $client_secret, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiPublicOauthIntrospectPostAsyncWithHttpInfo
     *
     * Introspect one of the calling app&#39;s tokens (RFC 7662).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthIntrospectPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthIntrospectPostAsyncWithHttpInfo($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthIntrospectPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response';
        $request = $this->apiPublicOauthIntrospectPostRequest($token, $client_id, $client_secret, $contentType);

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
     * Create request for operation 'apiPublicOauthIntrospectPost'
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthIntrospectPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiPublicOauthIntrospectPostRequest($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthIntrospectPost'][0])
    {

        // verify the required parameter 'token' is set
        if ($token === null || (is_array($token) && count($token) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $token when calling apiPublicOauthIntrospectPost'
            );
        }




        $resourcePath = '/api/public/oauth/introspect';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;




        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'token' => $token,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

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
     * Operation apiPublicOauthMetadataGet
     *
     * Authorization server metadata (RFC 8414) for this host.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthMetadataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array<string,mixed>
     */
    public function apiPublicOauthMetadataGet(string $contentType = self::contentTypes['apiPublicOauthMetadataGet'][0])
    {
        list($response) = $this->apiPublicOauthMetadataGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiPublicOauthMetadataGetWithHttpInfo
     *
     * Authorization server metadata (RFC 8414) for this host.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthMetadataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of array<string,mixed>, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiPublicOauthMetadataGetWithHttpInfo(string $contentType = self::contentTypes['apiPublicOauthMetadataGet'][0])
    {
        $request = $this->apiPublicOauthMetadataGetRequest($contentType);

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
                        'array<string,mixed>',
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
                'array<string,mixed>',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        'array<string,mixed>',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiPublicOauthMetadataGetAsync
     *
     * Authorization server metadata (RFC 8414) for this host.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthMetadataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthMetadataGetAsync(string $contentType = self::contentTypes['apiPublicOauthMetadataGet'][0])
    {
        return $this->apiPublicOauthMetadataGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiPublicOauthMetadataGetAsyncWithHttpInfo
     *
     * Authorization server metadata (RFC 8414) for this host.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthMetadataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthMetadataGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiPublicOauthMetadataGet'][0])
    {
        $returnType = 'array<string,mixed>';
        $request = $this->apiPublicOauthMetadataGetRequest($contentType);

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
     * Create request for operation 'apiPublicOauthMetadataGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthMetadataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiPublicOauthMetadataGetRequest(string $contentType = self::contentTypes['apiPublicOauthMetadataGet'][0])
    {


        $resourcePath = '/api/public/oauth/metadata';
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
     * Operation apiPublicOauthRevokePost
     *
     * Revoke an access or refresh token (RFC 7009).
     *
     * @param  string $token token (required)
     * @param  string|null $client_id client_id (optional)
     * @param  string|null $client_secret client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthRevokePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiPublicOauthRevokePost($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthRevokePost'][0])
    {
        $this->apiPublicOauthRevokePostWithHttpInfo($token, $client_id, $client_secret, $contentType);
    }

    /**
     * Operation apiPublicOauthRevokePostWithHttpInfo
     *
     * Revoke an access or refresh token (RFC 7009).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthRevokePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiPublicOauthRevokePostWithHttpInfo($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthRevokePost'][0])
    {
        $request = $this->apiPublicOauthRevokePostRequest($token, $client_id, $client_secret, $contentType);

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
     * Operation apiPublicOauthRevokePostAsync
     *
     * Revoke an access or refresh token (RFC 7009).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthRevokePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthRevokePostAsync($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthRevokePost'][0])
    {
        return $this->apiPublicOauthRevokePostAsyncWithHttpInfo($token, $client_id, $client_secret, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiPublicOauthRevokePostAsyncWithHttpInfo
     *
     * Revoke an access or refresh token (RFC 7009).
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthRevokePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthRevokePostAsyncWithHttpInfo($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthRevokePost'][0])
    {
        $returnType = '';
        $request = $this->apiPublicOauthRevokePostRequest($token, $client_id, $client_secret, $contentType);

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
     * Create request for operation 'apiPublicOauthRevokePost'
     *
     * @param  string $token (required)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthRevokePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiPublicOauthRevokePostRequest($token, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthRevokePost'][0])
    {

        // verify the required parameter 'token' is set
        if ($token === null || (is_array($token) && count($token) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $token when calling apiPublicOauthRevokePost'
            );
        }




        $resourcePath = '/api/public/oauth/revoke';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;




        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'token' => $token,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

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
     * Operation apiPublicOauthTokenPost
     *
     * Exchange an authorization code, or rotate a refresh token.
     *
     * @param  string $grant_type grant_type (required)
     * @param  string|null $code oac_… (authorization_code) (optional)
     * @param  string|null $redirect_uri redirect_uri (optional)
     * @param  string|null $code_verifier 43–128 chars (optional)
     * @param  string|null $refresh_token ort_… (refresh_token) (optional)
     * @param  string|null $scope Space-separated; refresh may only narrow. (optional)
     * @param  string|null $client_id client_id (optional)
     * @param  string|null $client_secret client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthTokenPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiPublicOauthTokenPost200Response
     */
    public function apiPublicOauthTokenPost($grant_type, $code = null, $redirect_uri = null, $code_verifier = null, $refresh_token = null, $scope = null, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthTokenPost'][0])
    {
        list($response) = $this->apiPublicOauthTokenPostWithHttpInfo($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret, $contentType);
        return $response;
    }

    /**
     * Operation apiPublicOauthTokenPostWithHttpInfo
     *
     * Exchange an authorization code, or rotate a refresh token.
     *
     * @param  string $grant_type (required)
     * @param  string|null $code oac_… (authorization_code) (optional)
     * @param  string|null $redirect_uri (optional)
     * @param  string|null $code_verifier 43–128 chars (optional)
     * @param  string|null $refresh_token ort_… (refresh_token) (optional)
     * @param  string|null $scope Space-separated; refresh may only narrow. (optional)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthTokenPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiPublicOauthTokenPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiPublicOauthTokenPostWithHttpInfo($grant_type, $code = null, $redirect_uri = null, $code_verifier = null, $refresh_token = null, $scope = null, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthTokenPost'][0])
    {
        $request = $this->apiPublicOauthTokenPostRequest($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret, $contentType);

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
                        '\GoAdServerApi\Model\ApiPublicOauthTokenPost200Response',
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
                '\GoAdServerApi\Model\ApiPublicOauthTokenPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiPublicOauthTokenPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiPublicOauthTokenPostAsync
     *
     * Exchange an authorization code, or rotate a refresh token.
     *
     * @param  string $grant_type (required)
     * @param  string|null $code oac_… (authorization_code) (optional)
     * @param  string|null $redirect_uri (optional)
     * @param  string|null $code_verifier 43–128 chars (optional)
     * @param  string|null $refresh_token ort_… (refresh_token) (optional)
     * @param  string|null $scope Space-separated; refresh may only narrow. (optional)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthTokenPostAsync($grant_type, $code = null, $redirect_uri = null, $code_verifier = null, $refresh_token = null, $scope = null, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthTokenPost'][0])
    {
        return $this->apiPublicOauthTokenPostAsyncWithHttpInfo($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiPublicOauthTokenPostAsyncWithHttpInfo
     *
     * Exchange an authorization code, or rotate a refresh token.
     *
     * @param  string $grant_type (required)
     * @param  string|null $code oac_… (authorization_code) (optional)
     * @param  string|null $redirect_uri (optional)
     * @param  string|null $code_verifier 43–128 chars (optional)
     * @param  string|null $refresh_token ort_… (refresh_token) (optional)
     * @param  string|null $scope Space-separated; refresh may only narrow. (optional)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiPublicOauthTokenPostAsyncWithHttpInfo($grant_type, $code = null, $redirect_uri = null, $code_verifier = null, $refresh_token = null, $scope = null, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthTokenPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiPublicOauthTokenPost200Response';
        $request = $this->apiPublicOauthTokenPostRequest($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret, $contentType);

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
     * Create request for operation 'apiPublicOauthTokenPost'
     *
     * @param  string $grant_type (required)
     * @param  string|null $code oac_… (authorization_code) (optional)
     * @param  string|null $redirect_uri (optional)
     * @param  string|null $code_verifier 43–128 chars (optional)
     * @param  string|null $refresh_token ort_… (refresh_token) (optional)
     * @param  string|null $scope Space-separated; refresh may only narrow. (optional)
     * @param  string|null $client_id (optional)
     * @param  string|null $client_secret (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiPublicOauthTokenPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiPublicOauthTokenPostRequest($grant_type, $code = null, $redirect_uri = null, $code_verifier = null, $refresh_token = null, $scope = null, $client_id = null, $client_secret = null, string $contentType = self::contentTypes['apiPublicOauthTokenPost'][0])
    {

        // verify the required parameter 'grant_type' is set
        if ($grant_type === null || (is_array($grant_type) && count($grant_type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $grant_type when calling apiPublicOauthTokenPost'
            );
        }









        $resourcePath = '/api/public/oauth/token';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;




        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'grant_type' => $grant_type,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'code_verifier' => $code_verifier,
            'refresh_token' => $refresh_token,
            'scope' => $scope,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

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

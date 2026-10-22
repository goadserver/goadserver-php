<?php
/**
 * TrackerAutomationApi
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
 * TrackerAutomationApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class TrackerAutomationApi
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
        'apiV1TrackerExportTypeGet' => [
            'application/json',
        ],
        'apiV1TrackerImportTypePost' => [
            'multipart/form-data',
        ],
        'apiV1TrackerReportSchedulesGet' => [
            'application/json',
        ],
        'apiV1TrackerReportSchedulesIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerReportSchedulesIdGet' => [
            'application/json',
        ],
        'apiV1TrackerReportSchedulesIdPut' => [
            'application/json',
        ],
        'apiV1TrackerReportSchedulesPost' => [
            'application/json',
        ],
        'apiV1TrackerRulesGet' => [
            'application/json',
        ],
        'apiV1TrackerRulesIdDelete' => [
            'application/json',
        ],
        'apiV1TrackerRulesIdExecutionsGet' => [
            'application/json',
        ],
        'apiV1TrackerRulesIdGet' => [
            'application/json',
        ],
        'apiV1TrackerRulesIdPut' => [
            'application/json',
        ],
        'apiV1TrackerRulesIdRunPost' => [
            'application/json',
        ],
        'apiV1TrackerRulesPost' => [
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
     * Operation apiV1TrackerExportTypeGet
     *
     * Export tracker entities as CSV.
     *
     * @param  string $type What to export. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerExportTypeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return string
     */
    public function apiV1TrackerExportTypeGet($type, string $contentType = self::contentTypes['apiV1TrackerExportTypeGet'][0])
    {
        list($response) = $this->apiV1TrackerExportTypeGetWithHttpInfo($type, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerExportTypeGetWithHttpInfo
     *
     * Export tracker entities as CSV.
     *
     * @param  string $type What to export. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerExportTypeGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of string, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerExportTypeGetWithHttpInfo($type, string $contentType = self::contentTypes['apiV1TrackerExportTypeGet'][0])
    {
        $request = $this->apiV1TrackerExportTypeGetRequest($type, $contentType);

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
                        'string',
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
                'string',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        'string',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerExportTypeGetAsync
     *
     * Export tracker entities as CSV.
     *
     * @param  string $type What to export. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerExportTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerExportTypeGetAsync($type, string $contentType = self::contentTypes['apiV1TrackerExportTypeGet'][0])
    {
        return $this->apiV1TrackerExportTypeGetAsyncWithHttpInfo($type, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerExportTypeGetAsyncWithHttpInfo
     *
     * Export tracker entities as CSV.
     *
     * @param  string $type What to export. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerExportTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerExportTypeGetAsyncWithHttpInfo($type, string $contentType = self::contentTypes['apiV1TrackerExportTypeGet'][0])
    {
        $returnType = 'string';
        $request = $this->apiV1TrackerExportTypeGetRequest($type, $contentType);

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
     * Create request for operation 'apiV1TrackerExportTypeGet'
     *
     * @param  string $type What to export. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerExportTypeGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerExportTypeGetRequest($type, string $contentType = self::contentTypes['apiV1TrackerExportTypeGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1TrackerExportTypeGet'
            );
        }


        $resourcePath = '/api/v1/tracker/export/{type}';
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
            ['text/csv', ],
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
     * Operation apiV1TrackerImportTypePost
     *
     * Bulk-import offers from CSV.
     *
     * @param  string $type What to import. (required)
     * @param  \SplFileObject $file The CSV, at most 10 MiB. (required)
     * @param  string|null $commit \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. (optional)
     * @param  string|null $overwrite \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerImportTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerImportTypePost($type, $file, $commit = null, $overwrite = null, string $contentType = self::contentTypes['apiV1TrackerImportTypePost'][0])
    {
        list($response) = $this->apiV1TrackerImportTypePostWithHttpInfo($type, $file, $commit, $overwrite, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerImportTypePostWithHttpInfo
     *
     * Bulk-import offers from CSV.
     *
     * @param  string $type What to import. (required)
     * @param  \SplFileObject $file The CSV, at most 10 MiB. (required)
     * @param  string|null $commit \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. (optional)
     * @param  string|null $overwrite \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerImportTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerImportTypePostWithHttpInfo($type, $file, $commit = null, $overwrite = null, string $contentType = self::contentTypes['apiV1TrackerImportTypePost'][0])
    {
        $request = $this->apiV1TrackerImportTypePostRequest($type, $file, $commit, $overwrite, $contentType);

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
     * Operation apiV1TrackerImportTypePostAsync
     *
     * Bulk-import offers from CSV.
     *
     * @param  string $type What to import. (required)
     * @param  \SplFileObject $file The CSV, at most 10 MiB. (required)
     * @param  string|null $commit \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. (optional)
     * @param  string|null $overwrite \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerImportTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerImportTypePostAsync($type, $file, $commit = null, $overwrite = null, string $contentType = self::contentTypes['apiV1TrackerImportTypePost'][0])
    {
        return $this->apiV1TrackerImportTypePostAsyncWithHttpInfo($type, $file, $commit, $overwrite, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerImportTypePostAsyncWithHttpInfo
     *
     * Bulk-import offers from CSV.
     *
     * @param  string $type What to import. (required)
     * @param  \SplFileObject $file The CSV, at most 10 MiB. (required)
     * @param  string|null $commit \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. (optional)
     * @param  string|null $overwrite \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerImportTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerImportTypePostAsyncWithHttpInfo($type, $file, $commit = null, $overwrite = null, string $contentType = self::contentTypes['apiV1TrackerImportTypePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerImportTypePostRequest($type, $file, $commit, $overwrite, $contentType);

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
     * Create request for operation 'apiV1TrackerImportTypePost'
     *
     * @param  string $type What to import. (required)
     * @param  \SplFileObject $file The CSV, at most 10 MiB. (required)
     * @param  string|null $commit \\\&quot;true\\\&quot; to write. Absent &#x3D; validate and report only. (optional)
     * @param  string|null $overwrite \\\&quot;true\\\&quot; to update rows whose id column matches one of the caller&#39;s own. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerImportTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerImportTypePostRequest($type, $file, $commit = null, $overwrite = null, string $contentType = self::contentTypes['apiV1TrackerImportTypePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1TrackerImportTypePost'
            );
        }

        // verify the required parameter 'file' is set
        if ($file === null || (is_array($file) && count($file) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $file when calling apiV1TrackerImportTypePost'
            );
        }




        $resourcePath = '/api/v1/tracker/import/{type}';
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

        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'file' => $file,
            'commit' => $commit,
            'overwrite' => $overwrite,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

        $multipart = true;
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
     * Operation apiV1TrackerReportSchedulesGet
     *
     * List scheduled report deliveries.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response
     */
    public function apiV1TrackerReportSchedulesGet(string $contentType = self::contentTypes['apiV1TrackerReportSchedulesGet'][0])
    {
        list($response) = $this->apiV1TrackerReportSchedulesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerReportSchedulesGetWithHttpInfo
     *
     * List scheduled report deliveries.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerReportSchedulesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerReportSchedulesGet'][0])
    {
        $request = $this->apiV1TrackerReportSchedulesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerReportSchedulesGetAsync
     *
     * List scheduled report deliveries.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesGetAsync(string $contentType = self::contentTypes['apiV1TrackerReportSchedulesGet'][0])
    {
        return $this->apiV1TrackerReportSchedulesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerReportSchedulesGetAsyncWithHttpInfo
     *
     * List scheduled report deliveries.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerReportSchedulesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesGet200Response';
        $request = $this->apiV1TrackerReportSchedulesGetRequest($contentType);

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
     * Create request for operation 'apiV1TrackerReportSchedulesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerReportSchedulesGetRequest(string $contentType = self::contentTypes['apiV1TrackerReportSchedulesGet'][0])
    {


        $resourcePath = '/api/v1/tracker/report-schedules';
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
     * Operation apiV1TrackerReportSchedulesIdDelete
     *
     * Delete a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerReportSchedulesIdDelete($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerReportSchedulesIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdDeleteWithHttpInfo
     *
     * Delete a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerReportSchedulesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdDelete'][0])
    {
        $request = $this->apiV1TrackerReportSchedulesIdDeleteRequest($id, $contentType);

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
     * Operation apiV1TrackerReportSchedulesIdDeleteAsync
     *
     * Delete a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdDelete'][0])
    {
        return $this->apiV1TrackerReportSchedulesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdDeleteAsyncWithHttpInfo
     *
     * Delete a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerReportSchedulesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerReportSchedulesIdDelete'
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerReportSchedulesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerReportSchedulesIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/report-schedules/{id}';
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
     * Operation apiV1TrackerReportSchedulesIdGet
     *
     * Get one report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response
     */
    public function apiV1TrackerReportSchedulesIdGet($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdGet'][0])
    {
        list($response) = $this->apiV1TrackerReportSchedulesIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdGetWithHttpInfo
     *
     * Get one report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerReportSchedulesIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdGet'][0])
    {
        $request = $this->apiV1TrackerReportSchedulesIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdGetAsync
     *
     * Get one report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdGet'][0])
    {
        return $this->apiV1TrackerReportSchedulesIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdGetAsyncWithHttpInfo
     *
     * Get one report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdGet200Response';
        $request = $this->apiV1TrackerReportSchedulesIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerReportSchedulesIdGet'
     *
     * @param  int $id Schedule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerReportSchedulesIdGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerReportSchedulesIdGet'
            );
        }


        $resourcePath = '/api/v1/tracker/report-schedules/{id}';
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
     * Operation apiV1TrackerReportSchedulesIdPut
     *
     * Update a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest $api_v1_tracker_report_schedules_id_put_request api_v1_tracker_report_schedules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerReportSchedulesIdPut($id, $api_v1_tracker_report_schedules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdPut'][0])
    {
        list($response) = $this->apiV1TrackerReportSchedulesIdPutWithHttpInfo($id, $api_v1_tracker_report_schedules_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdPutWithHttpInfo
     *
     * Update a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest $api_v1_tracker_report_schedules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerReportSchedulesIdPutWithHttpInfo($id, $api_v1_tracker_report_schedules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdPut'][0])
    {
        $request = $this->apiV1TrackerReportSchedulesIdPutRequest($id, $api_v1_tracker_report_schedules_id_put_request, $contentType);

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
     * Operation apiV1TrackerReportSchedulesIdPutAsync
     *
     * Update a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest $api_v1_tracker_report_schedules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdPutAsync($id, $api_v1_tracker_report_schedules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdPut'][0])
    {
        return $this->apiV1TrackerReportSchedulesIdPutAsyncWithHttpInfo($id, $api_v1_tracker_report_schedules_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerReportSchedulesIdPutAsyncWithHttpInfo
     *
     * Update a report schedule.
     *
     * @param  int $id Schedule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest $api_v1_tracker_report_schedules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesIdPutAsyncWithHttpInfo($id, $api_v1_tracker_report_schedules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerReportSchedulesIdPutRequest($id, $api_v1_tracker_report_schedules_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerReportSchedulesIdPut'
     *
     * @param  int $id Schedule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesIdPutRequest $api_v1_tracker_report_schedules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerReportSchedulesIdPutRequest($id, $api_v1_tracker_report_schedules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerReportSchedulesIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_report_schedules_id_put_request' is set
        if ($api_v1_tracker_report_schedules_id_put_request === null || (is_array($api_v1_tracker_report_schedules_id_put_request) && count($api_v1_tracker_report_schedules_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_report_schedules_id_put_request when calling apiV1TrackerReportSchedulesIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/report-schedules/{id}';
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
        if (isset($api_v1_tracker_report_schedules_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_report_schedules_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_report_schedules_id_put_request;
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
     * Operation apiV1TrackerReportSchedulesPost
     *
     * Schedule a recurring report.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest $api_v1_tracker_report_schedules_post_request api_v1_tracker_report_schedules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerReportSchedulesPost($api_v1_tracker_report_schedules_post_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesPost'][0])
    {
        list($response) = $this->apiV1TrackerReportSchedulesPostWithHttpInfo($api_v1_tracker_report_schedules_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerReportSchedulesPostWithHttpInfo
     *
     * Schedule a recurring report.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest $api_v1_tracker_report_schedules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerReportSchedulesPostWithHttpInfo($api_v1_tracker_report_schedules_post_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesPost'][0])
    {
        $request = $this->apiV1TrackerReportSchedulesPostRequest($api_v1_tracker_report_schedules_post_request, $contentType);

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
     * Operation apiV1TrackerReportSchedulesPostAsync
     *
     * Schedule a recurring report.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest $api_v1_tracker_report_schedules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesPostAsync($api_v1_tracker_report_schedules_post_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesPost'][0])
    {
        return $this->apiV1TrackerReportSchedulesPostAsyncWithHttpInfo($api_v1_tracker_report_schedules_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerReportSchedulesPostAsyncWithHttpInfo
     *
     * Schedule a recurring report.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest $api_v1_tracker_report_schedules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerReportSchedulesPostAsyncWithHttpInfo($api_v1_tracker_report_schedules_post_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerReportSchedulesPostRequest($api_v1_tracker_report_schedules_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerReportSchedulesPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerReportSchedulesPostRequest $api_v1_tracker_report_schedules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerReportSchedulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerReportSchedulesPostRequest($api_v1_tracker_report_schedules_post_request, string $contentType = self::contentTypes['apiV1TrackerReportSchedulesPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_report_schedules_post_request' is set
        if ($api_v1_tracker_report_schedules_post_request === null || (is_array($api_v1_tracker_report_schedules_post_request) && count($api_v1_tracker_report_schedules_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_report_schedules_post_request when calling apiV1TrackerReportSchedulesPost'
            );
        }


        $resourcePath = '/api/v1/tracker/report-schedules';
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
        if (isset($api_v1_tracker_report_schedules_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_report_schedules_post_request));
            } else {
                $httpBody = $api_v1_tracker_report_schedules_post_request;
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
     * Operation apiV1TrackerRulesGet
     *
     * List automation rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerRulesGet200Response
     */
    public function apiV1TrackerRulesGet(string $contentType = self::contentTypes['apiV1TrackerRulesGet'][0])
    {
        list($response) = $this->apiV1TrackerRulesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesGetWithHttpInfo
     *
     * List automation rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerRulesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerRulesGet'][0])
    {
        $request = $this->apiV1TrackerRulesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerRulesGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerRulesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerRulesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerRulesGetAsync
     *
     * List automation rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesGetAsync(string $contentType = self::contentTypes['apiV1TrackerRulesGet'][0])
    {
        return $this->apiV1TrackerRulesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesGetAsyncWithHttpInfo
     *
     * List automation rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1TrackerRulesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerRulesGet200Response';
        $request = $this->apiV1TrackerRulesGetRequest($contentType);

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
     * Create request for operation 'apiV1TrackerRulesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesGetRequest(string $contentType = self::contentTypes['apiV1TrackerRulesGet'][0])
    {


        $resourcePath = '/api/v1/tracker/rules';
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
     * Operation apiV1TrackerRulesIdDelete
     *
     * Delete a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
     */
    public function apiV1TrackerRulesIdDelete($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdDelete'][0])
    {
        list($response) = $this->apiV1TrackerRulesIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesIdDeleteWithHttpInfo
     *
     * Delete a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdDelete'][0])
    {
        $request = $this->apiV1TrackerRulesIdDeleteRequest($id, $contentType);

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
     * Operation apiV1TrackerRulesIdDeleteAsync
     *
     * Delete a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdDelete'][0])
    {
        return $this->apiV1TrackerRulesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesIdDeleteAsyncWithHttpInfo
     *
     * Delete a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response';
        $request = $this->apiV1TrackerRulesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesIdDelete'
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerRulesIdDelete'
            );
        }


        $resourcePath = '/api/v1/tracker/rules/{id}';
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
     * Operation apiV1TrackerRulesIdExecutionsGet
     *
     * Execution history for one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdExecutionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerRulesIdExecutionsGet($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdExecutionsGet'][0])
    {
        list($response) = $this->apiV1TrackerRulesIdExecutionsGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesIdExecutionsGetWithHttpInfo
     *
     * Execution history for one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdExecutionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesIdExecutionsGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdExecutionsGet'][0])
    {
        $request = $this->apiV1TrackerRulesIdExecutionsGetRequest($id, $contentType);

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
     * Operation apiV1TrackerRulesIdExecutionsGetAsync
     *
     * Execution history for one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdExecutionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdExecutionsGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdExecutionsGet'][0])
    {
        return $this->apiV1TrackerRulesIdExecutionsGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesIdExecutionsGetAsyncWithHttpInfo
     *
     * Execution history for one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdExecutionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdExecutionsGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdExecutionsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerRulesIdExecutionsGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesIdExecutionsGet'
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdExecutionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesIdExecutionsGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdExecutionsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerRulesIdExecutionsGet'
            );
        }


        $resourcePath = '/api/v1/tracker/rules/{id}/executions';
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
     * Operation apiV1TrackerRulesIdGet
     *
     * Get one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response
     */
    public function apiV1TrackerRulesIdGet($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdGet'][0])
    {
        list($response) = $this->apiV1TrackerRulesIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesIdGetWithHttpInfo
     *
     * Get one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdGet'][0])
    {
        $request = $this->apiV1TrackerRulesIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response',
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
                '\GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1TrackerRulesIdGetAsync
     *
     * Get one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdGetAsync($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdGet'][0])
    {
        return $this->apiV1TrackerRulesIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesIdGetAsyncWithHttpInfo
     *
     * Get one rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerRulesIdGet200Response';
        $request = $this->apiV1TrackerRulesIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesIdGet'
     *
     * @param  int $id Rule ID. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesIdGetRequest($id, string $contentType = self::contentTypes['apiV1TrackerRulesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerRulesIdGet'
            );
        }


        $resourcePath = '/api/v1/tracker/rules/{id}';
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
     * Operation apiV1TrackerRulesIdPut
     *
     * Update a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest $api_v1_tracker_rules_id_put_request api_v1_tracker_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerRulesIdPut($id, $api_v1_tracker_rules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerRulesIdPut'][0])
    {
        list($response) = $this->apiV1TrackerRulesIdPutWithHttpInfo($id, $api_v1_tracker_rules_id_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesIdPutWithHttpInfo
     *
     * Update a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest $api_v1_tracker_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesIdPutWithHttpInfo($id, $api_v1_tracker_rules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerRulesIdPut'][0])
    {
        $request = $this->apiV1TrackerRulesIdPutRequest($id, $api_v1_tracker_rules_id_put_request, $contentType);

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
     * Operation apiV1TrackerRulesIdPutAsync
     *
     * Update a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest $api_v1_tracker_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdPutAsync($id, $api_v1_tracker_rules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerRulesIdPut'][0])
    {
        return $this->apiV1TrackerRulesIdPutAsyncWithHttpInfo($id, $api_v1_tracker_rules_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesIdPutAsyncWithHttpInfo
     *
     * Update a rule.
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest $api_v1_tracker_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdPutAsyncWithHttpInfo($id, $api_v1_tracker_rules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerRulesIdPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerRulesIdPutRequest($id, $api_v1_tracker_rules_id_put_request, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesIdPut'
     *
     * @param  int $id Rule ID. (required)
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesIdPutRequest $api_v1_tracker_rules_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesIdPutRequest($id, $api_v1_tracker_rules_id_put_request, string $contentType = self::contentTypes['apiV1TrackerRulesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerRulesIdPut'
            );
        }

        // verify the required parameter 'api_v1_tracker_rules_id_put_request' is set
        if ($api_v1_tracker_rules_id_put_request === null || (is_array($api_v1_tracker_rules_id_put_request) && count($api_v1_tracker_rules_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_rules_id_put_request when calling apiV1TrackerRulesIdPut'
            );
        }


        $resourcePath = '/api/v1/tracker/rules/{id}';
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
        if (isset($api_v1_tracker_rules_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_rules_id_put_request));
            } else {
                $httpBody = $api_v1_tracker_rules_id_put_request;
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
     * Operation apiV1TrackerRulesIdRunPost
     *
     * Run a rule now.
     *
     * @param  int $id Rule ID. (required)
     * @param  string|null $window Override the evaluation window for this run. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
     */
    public function apiV1TrackerRulesIdRunPost($id, $window = null, string $contentType = self::contentTypes['apiV1TrackerRulesIdRunPost'][0])
    {
        list($response) = $this->apiV1TrackerRulesIdRunPostWithHttpInfo($id, $window, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesIdRunPostWithHttpInfo
     *
     * Run a rule now.
     *
     * @param  int $id Rule ID. (required)
     * @param  string|null $window Override the evaluation window for this run. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdRunPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesIdRunPostWithHttpInfo($id, $window = null, string $contentType = self::contentTypes['apiV1TrackerRulesIdRunPost'][0])
    {
        $request = $this->apiV1TrackerRulesIdRunPostRequest($id, $window, $contentType);

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
     * Operation apiV1TrackerRulesIdRunPostAsync
     *
     * Run a rule now.
     *
     * @param  int $id Rule ID. (required)
     * @param  string|null $window Override the evaluation window for this run. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdRunPostAsync($id, $window = null, string $contentType = self::contentTypes['apiV1TrackerRulesIdRunPost'][0])
    {
        return $this->apiV1TrackerRulesIdRunPostAsyncWithHttpInfo($id, $window, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesIdRunPostAsyncWithHttpInfo
     *
     * Run a rule now.
     *
     * @param  int $id Rule ID. (required)
     * @param  string|null $window Override the evaluation window for this run. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesIdRunPostAsyncWithHttpInfo($id, $window = null, string $contentType = self::contentTypes['apiV1TrackerRulesIdRunPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response';
        $request = $this->apiV1TrackerRulesIdRunPostRequest($id, $window, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesIdRunPost'
     *
     * @param  int $id Rule ID. (required)
     * @param  string|null $window Override the evaluation window for this run. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesIdRunPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesIdRunPostRequest($id, $window = null, string $contentType = self::contentTypes['apiV1TrackerRulesIdRunPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1TrackerRulesIdRunPost'
            );
        }



        $resourcePath = '/api/v1/tracker/rules/{id}/run';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $window,
            'window', // param base name
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
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1TrackerRulesPost
     *
     * Create an automation rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest $api_v1_tracker_rules_post_request api_v1_tracker_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
     */
    public function apiV1TrackerRulesPost($api_v1_tracker_rules_post_request, string $contentType = self::contentTypes['apiV1TrackerRulesPost'][0])
    {
        list($response) = $this->apiV1TrackerRulesPostWithHttpInfo($api_v1_tracker_rules_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1TrackerRulesPostWithHttpInfo
     *
     * Create an automation rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest $api_v1_tracker_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1TrackerRulesPostWithHttpInfo($api_v1_tracker_rules_post_request, string $contentType = self::contentTypes['apiV1TrackerRulesPost'][0])
    {
        $request = $this->apiV1TrackerRulesPostRequest($api_v1_tracker_rules_post_request, $contentType);

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
     * Operation apiV1TrackerRulesPostAsync
     *
     * Create an automation rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest $api_v1_tracker_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesPostAsync($api_v1_tracker_rules_post_request, string $contentType = self::contentTypes['apiV1TrackerRulesPost'][0])
    {
        return $this->apiV1TrackerRulesPostAsyncWithHttpInfo($api_v1_tracker_rules_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1TrackerRulesPostAsyncWithHttpInfo
     *
     * Create an automation rule.
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest $api_v1_tracker_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1TrackerRulesPostAsyncWithHttpInfo($api_v1_tracker_rules_post_request, string $contentType = self::contentTypes['apiV1TrackerRulesPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response';
        $request = $this->apiV1TrackerRulesPostRequest($api_v1_tracker_rules_post_request, $contentType);

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
     * Create request for operation 'apiV1TrackerRulesPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1TrackerRulesPostRequest $api_v1_tracker_rules_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1TrackerRulesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1TrackerRulesPostRequest($api_v1_tracker_rules_post_request, string $contentType = self::contentTypes['apiV1TrackerRulesPost'][0])
    {

        // verify the required parameter 'api_v1_tracker_rules_post_request' is set
        if ($api_v1_tracker_rules_post_request === null || (is_array($api_v1_tracker_rules_post_request) && count($api_v1_tracker_rules_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_tracker_rules_post_request when calling apiV1TrackerRulesPost'
            );
        }


        $resourcePath = '/api/v1/tracker/rules';
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
        if (isset($api_v1_tracker_rules_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_tracker_rules_post_request));
            } else {
                $httpBody = $api_v1_tracker_rules_post_request;
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

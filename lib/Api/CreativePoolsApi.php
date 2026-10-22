<?php
/**
 * CreativePoolsApi
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
 * CreativePoolsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class CreativePoolsApi
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
        'apiV1CreativePoolsGet' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdAddCreativesPost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdAddUrlsPost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdClonePost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdDelete' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdGet' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsExportGet' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsGet' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsItemIdDelete' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsItemIdPut' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsItemIdTogglePut' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsMassActionPut' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsPost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdItemsRecoverPost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdPut' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdRecoverPost' => [
            'application/json',
        ],
        'apiV1CreativePoolsIdUsedInGet' => [
            'application/json',
        ],
        'apiV1CreativePoolsPost' => [
            'application/json',
        ],
        'apiV1CreativesBulkDeletePost' => [
            'application/json',
        ],
        'apiV1CreativesBulkUndeletePost' => [
            'application/json',
        ],
        'apiV1CreativesIdUndeletePut' => [
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
     * Operation apiV1CreativePoolsGet
     *
     * List creative pools.
     *
     * @param  string|null $filter_type filter_type (optional)
     * @param  string|null $filter_types Comma-separated list of pool types (banner, video, reel, image, native, creator). (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $filter_orientation The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $fit_orientation Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. (optional)
     * @param  string|null $select_stats 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter|null $per_page per_page (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativePoolsGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CreativePoolsGet($filter_type = null, $filter_types = null, $filter_title = null, $filter_id = null, $filter_name = null, $filter_orientation = null, $state = 'live', $search = null, $fit_orientation = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsGet'][0])
    {
        list($response) = $this->apiV1CreativePoolsGetWithHttpInfo($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsGetWithHttpInfo
     *
     * List creative pools.
     *
     * @param  string|null $filter_type (optional)
     * @param  string|null $filter_types Comma-separated list of pool types (banner, video, reel, image, native, creator). (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $filter_orientation The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $fit_orientation Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. (optional)
     * @param  string|null $select_stats 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter|null $per_page (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativePoolsGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsGetWithHttpInfo($filter_type = null, $filter_types = null, $filter_title = null, $filter_id = null, $filter_name = null, $filter_orientation = null, $state = 'live', $search = null, $fit_orientation = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsGet'][0])
    {
        $request = $this->apiV1CreativePoolsGetRequest($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response',
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
     * Operation apiV1CreativePoolsGetAsync
     *
     * List creative pools.
     *
     * @param  string|null $filter_type (optional)
     * @param  string|null $filter_types Comma-separated list of pool types (banner, video, reel, image, native, creator). (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $filter_orientation The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $fit_orientation Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. (optional)
     * @param  string|null $select_stats 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter|null $per_page (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsGetAsync($filter_type = null, $filter_types = null, $filter_title = null, $filter_id = null, $filter_name = null, $filter_orientation = null, $state = 'live', $search = null, $fit_orientation = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsGet'][0])
    {
        return $this->apiV1CreativePoolsGetAsyncWithHttpInfo($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsGetAsyncWithHttpInfo
     *
     * List creative pools.
     *
     * @param  string|null $filter_type (optional)
     * @param  string|null $filter_types Comma-separated list of pool types (banner, video, reel, image, native, creator). (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $filter_orientation The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $fit_orientation Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. (optional)
     * @param  string|null $select_stats 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter|null $per_page (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsGetAsyncWithHttpInfo($filter_type = null, $filter_types = null, $filter_title = null, $filter_id = null, $filter_name = null, $filter_orientation = null, $state = 'live', $search = null, $fit_orientation = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response';
        $request = $this->apiV1CreativePoolsGetRequest($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsGet'
     *
     * @param  string|null $filter_type (optional)
     * @param  string|null $filter_types Comma-separated list of pool types (banner, video, reel, image, native, creator). (optional)
     * @param  string|null $filter_title Title contains. (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Same as filter_title. (optional)
     * @param  string|null $filter_orientation The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. (optional)
     * @param  string|null $state deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. (optional, default to 'live')
     * @param  string|null $search Title contains. (optional)
     * @param  string|null $fit_orientation Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. (optional)
     * @param  string|null $select_stats 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter|null $per_page (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsGetRequest($filter_type = null, $filter_types = null, $filter_title = null, $filter_id = null, $filter_name = null, $filter_orientation = null, $state = 'live', $search = null, $fit_orientation = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsGet'][0])
    {


















        $resourcePath = '/api/v1/creative-pools';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $filter_types,
            'filter_types', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_title,
            'filter_title', // param base name
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
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_orientation,
            'filter_orientation', // param base name
            'string', // openApiType
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
            $search,
            'search', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $fit_orientation,
            'fit_orientation', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $select_stats,
            'select_stats', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $from,
            'from', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $to,
            'to', // param base name
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
     * Operation apiV1CreativePoolsIdAddCreativesPost
     *
     * Add media-bank creatives to a pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest $api_v1_creative_pools_id_add_creatives_post_request api_v1_creative_pools_id_add_creatives_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdAddCreativesPost($id, $api_v1_creative_pools_id_add_creatives_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'][0])
    {
        $this->apiV1CreativePoolsIdAddCreativesPostWithHttpInfo($id, $api_v1_creative_pools_id_add_creatives_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdAddCreativesPostWithHttpInfo
     *
     * Add media-bank creatives to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest $api_v1_creative_pools_id_add_creatives_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdAddCreativesPostWithHttpInfo($id, $api_v1_creative_pools_id_add_creatives_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'][0])
    {
        $request = $this->apiV1CreativePoolsIdAddCreativesPostRequest($id, $api_v1_creative_pools_id_add_creatives_post_request, $contentType);

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
     * Operation apiV1CreativePoolsIdAddCreativesPostAsync
     *
     * Add media-bank creatives to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest $api_v1_creative_pools_id_add_creatives_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdAddCreativesPostAsync($id, $api_v1_creative_pools_id_add_creatives_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'][0])
    {
        return $this->apiV1CreativePoolsIdAddCreativesPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_add_creatives_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdAddCreativesPostAsyncWithHttpInfo
     *
     * Add media-bank creatives to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest $api_v1_creative_pools_id_add_creatives_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdAddCreativesPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_add_creatives_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdAddCreativesPostRequest($id, $api_v1_creative_pools_id_add_creatives_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdAddCreativesPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest $api_v1_creative_pools_id_add_creatives_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdAddCreativesPostRequest($id, $api_v1_creative_pools_id_add_creatives_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddCreativesPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdAddCreativesPost'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_add_creatives_post_request' is set
        if ($api_v1_creative_pools_id_add_creatives_post_request === null || (is_array($api_v1_creative_pools_id_add_creatives_post_request) && count($api_v1_creative_pools_id_add_creatives_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_add_creatives_post_request when calling apiV1CreativePoolsIdAddCreativesPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/add-creatives';
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
        if (isset($api_v1_creative_pools_id_add_creatives_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_add_creatives_post_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_add_creatives_post_request;
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
     * Operation apiV1CreativePoolsIdAddUrlsPost
     *
     * Add hotlinked image / video / HTML URLs to a pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest $api_v1_creative_pools_id_add_urls_post_request api_v1_creative_pools_id_add_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdAddUrlsPost($id, $api_v1_creative_pools_id_add_urls_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'][0])
    {
        $this->apiV1CreativePoolsIdAddUrlsPostWithHttpInfo($id, $api_v1_creative_pools_id_add_urls_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdAddUrlsPostWithHttpInfo
     *
     * Add hotlinked image / video / HTML URLs to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest $api_v1_creative_pools_id_add_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdAddUrlsPostWithHttpInfo($id, $api_v1_creative_pools_id_add_urls_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'][0])
    {
        $request = $this->apiV1CreativePoolsIdAddUrlsPostRequest($id, $api_v1_creative_pools_id_add_urls_post_request, $contentType);

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
     * Operation apiV1CreativePoolsIdAddUrlsPostAsync
     *
     * Add hotlinked image / video / HTML URLs to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest $api_v1_creative_pools_id_add_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdAddUrlsPostAsync($id, $api_v1_creative_pools_id_add_urls_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'][0])
    {
        return $this->apiV1CreativePoolsIdAddUrlsPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_add_urls_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdAddUrlsPostAsyncWithHttpInfo
     *
     * Add hotlinked image / video / HTML URLs to a pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest $api_v1_creative_pools_id_add_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdAddUrlsPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_add_urls_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdAddUrlsPostRequest($id, $api_v1_creative_pools_id_add_urls_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdAddUrlsPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest $api_v1_creative_pools_id_add_urls_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdAddUrlsPostRequest($id, $api_v1_creative_pools_id_add_urls_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdAddUrlsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdAddUrlsPost'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_add_urls_post_request' is set
        if ($api_v1_creative_pools_id_add_urls_post_request === null || (is_array($api_v1_creative_pools_id_add_urls_post_request) && count($api_v1_creative_pools_id_add_urls_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_add_urls_post_request when calling apiV1CreativePoolsIdAddUrlsPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/add-urls';
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
        if (isset($api_v1_creative_pools_id_add_urls_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_add_urls_post_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_add_urls_post_request;
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
     * Operation apiV1CreativePoolsIdClonePost
     *
     * Clone a creative pool with all its items.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CreativePoolsIdClonePost($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdClonePost'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdClonePostWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdClonePostWithHttpInfo
     *
     * Clone a creative pool with all its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdClonePostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdClonePost'][0])
    {
        $request = $this->apiV1CreativePoolsIdClonePostRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response',
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
                '\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response',
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
     * Operation apiV1CreativePoolsIdClonePostAsync
     *
     * Clone a creative pool with all its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdClonePostAsync($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdClonePost'][0])
    {
        return $this->apiV1CreativePoolsIdClonePostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdClonePostAsyncWithHttpInfo
     *
     * Clone a creative pool with all its items.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdClonePostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdClonePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response';
        $request = $this->apiV1CreativePoolsIdClonePostRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdClonePost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdClonePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdClonePostRequest($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdClonePost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdClonePost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/clone';
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
     * Operation apiV1CreativePoolsIdDelete
     *
     * Soft-delete a creative pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response|\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete409Response
     */
    public function apiV1CreativePoolsIdDelete($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdDelete'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdDeleteWithHttpInfo
     *
     * Soft-delete a creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response|\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete409Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdDelete'][0])
    {
        $request = $this->apiV1CreativePoolsIdDeleteRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response',
                        $request,
                        $response,
                    );
                case 409:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete409Response',
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
                '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete409Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CreativePoolsIdDeleteAsync
     *
     * Soft-delete a creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdDelete'][0])
    {
        return $this->apiV1CreativePoolsIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete a creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response';
        $request = $this->apiV1CreativePoolsIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdDelete'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}';
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
     * Operation apiV1CreativePoolsIdGet
     *
     * Show a single creative pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response
     */
    public function apiV1CreativePoolsIdGet($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdGet'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdGetWithHttpInfo
     *
     * Show a single creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdGet'][0])
    {
        $request = $this->apiV1CreativePoolsIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CreativePoolsIdGetAsync
     *
     * Show a single creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdGetAsync($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdGet'][0])
    {
        return $this->apiV1CreativePoolsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdGetAsyncWithHttpInfo
     *
     * Show a single creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response';
        $request = $this->apiV1CreativePoolsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdGetRequest($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdGet'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}';
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
     * Operation apiV1CreativePoolsIdItemsExportGet
     *
     * Download pool items&#39; creative files as a ZIP.
     *
     * @param  int $id id (required)
     * @param  string $ids Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \SplFileObject|\GoAdServerApi\Model\Error
     */
    public function apiV1CreativePoolsIdItemsExportGet($id, $ids, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsExportGet'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdItemsExportGetWithHttpInfo($id, $ids, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdItemsExportGetWithHttpInfo
     *
     * Download pool items&#39; creative files as a ZIP.
     *
     * @param  int $id (required)
     * @param  string $ids Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsExportGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \SplFileObject|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsExportGetWithHttpInfo($id, $ids, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsExportGet'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsExportGetRequest($id, $ids, $contentType);

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
                        '\SplFileObject',
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
                '\SplFileObject',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\SplFileObject',
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
     * Operation apiV1CreativePoolsIdItemsExportGetAsync
     *
     * Download pool items&#39; creative files as a ZIP.
     *
     * @param  int $id (required)
     * @param  string $ids Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsExportGetAsync($id, $ids, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsExportGet'][0])
    {
        return $this->apiV1CreativePoolsIdItemsExportGetAsyncWithHttpInfo($id, $ids, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsExportGetAsyncWithHttpInfo
     *
     * Download pool items&#39; creative files as a ZIP.
     *
     * @param  int $id (required)
     * @param  string $ids Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsExportGetAsyncWithHttpInfo($id, $ids, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsExportGet'][0])
    {
        $returnType = '\SplFileObject';
        $request = $this->apiV1CreativePoolsIdItemsExportGetRequest($id, $ids, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsExportGet'
     *
     * @param  int $id (required)
     * @param  string $ids Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsExportGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsExportGetRequest($id, $ids, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsExportGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsExportGet'
            );
        }

        // verify the required parameter 'ids' is set
        if ($ids === null || (is_array($ids) && count($ids) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $ids when calling apiV1CreativePoolsIdItemsExportGet'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/export';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $ids,
            'ids', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
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
            ['application/zip', 'application/json', ],
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
     * Operation apiV1CreativePoolsIdItemsGet
     *
     * List items in a creative pool.
     *
     * @param  int $id id (required)
     * @param  string|null $include_sizes Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. (optional)
     * @param  string|null $select_stats 1 &#x3D; add stats per item for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  int|null $filter_bansizeid Banner size id. (optional)
     * @param  int|null $filter_isactive filter_isactive (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativesGet200Response
     */
    public function apiV1CreativePoolsIdItemsGet($id, $include_sizes = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_bansizeid = null, $filter_isactive = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsGet'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdItemsGetWithHttpInfo($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdItemsGetWithHttpInfo
     *
     * List items in a creative pool.
     *
     * @param  int $id (required)
     * @param  string|null $include_sizes Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. (optional)
     * @param  string|null $select_stats 1 &#x3D; add stats per item for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_bansizeid Banner size id. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativesGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsGetWithHttpInfo($id, $include_sizes = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_bansizeid = null, $filter_isactive = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsGet'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsGetRequest($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsGetAsync
     *
     * List items in a creative pool.
     *
     * @param  int $id (required)
     * @param  string|null $include_sizes Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. (optional)
     * @param  string|null $select_stats 1 &#x3D; add stats per item for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_bansizeid Banner size id. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsGetAsync($id, $include_sizes = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_bansizeid = null, $filter_isactive = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsGet'][0])
    {
        return $this->apiV1CreativePoolsIdItemsGetAsyncWithHttpInfo($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsGetAsyncWithHttpInfo
     *
     * List items in a creative pool.
     *
     * @param  int $id (required)
     * @param  string|null $include_sizes Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. (optional)
     * @param  string|null $select_stats 1 &#x3D; add stats per item for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_bansizeid Banner size id. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsGetAsyncWithHttpInfo($id, $include_sizes = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_bansizeid = null, $filter_isactive = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativesGet200Response';
        $request = $this->apiV1CreativePoolsIdItemsGetRequest($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsGet'
     *
     * @param  int $id (required)
     * @param  string|null $include_sizes Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. (optional)
     * @param  string|null $select_stats 1 &#x3D; add stats per item for the from / to window (default the last 7 days). (optional)
     * @param  \DateTime|null $from With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. (optional)
     * @param  \DateTime|null $to With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). (optional)
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  int|null $filter_bansizeid Banner size id. (optional)
     * @param  int|null $filter_isactive (optional)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsGetRequest($id, $include_sizes = null, $select_stats = null, $from = null, $to = null, $page = 1, $per_page = 20, $filter_bansizeid = null, $filter_isactive = null, $sort_by = 'id', $sort_dir = 'desc', string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsGet'
            );
        }






        if ($per_page !== null && $per_page > 5000) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling CreativePoolsApi.apiV1CreativePoolsIdItemsGet, must be smaller than or equal to 5000.');
        }
        





        $resourcePath = '/api/v1/creative-pools/{id}/items';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $include_sizes,
            'include_sizes', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $select_stats,
            'select_stats', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $from,
            'from', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $to,
            'to', // param base name
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
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_bansizeid,
            'filter_bansizeid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_isactive,
            'filter_isactive', // param base name
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
     * Operation apiV1CreativePoolsIdItemsItemIdDelete
     *
     * Soft-delete a single pool item.
     *
     * @param  int $id id (required)
     * @param  int $item_id item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsItemIdDelete($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'][0])
    {
        $this->apiV1CreativePoolsIdItemsItemIdDeleteWithHttpInfo($id, $item_id, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdDeleteWithHttpInfo
     *
     * Soft-delete a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsItemIdDeleteWithHttpInfo($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsItemIdDeleteRequest($id, $item_id, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsItemIdDeleteAsync
     *
     * Soft-delete a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdDeleteAsync($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'][0])
    {
        return $this->apiV1CreativePoolsIdItemsItemIdDeleteAsyncWithHttpInfo($id, $item_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdDeleteAsyncWithHttpInfo
     *
     * Soft-delete a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdDeleteAsyncWithHttpInfo($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsItemIdDeleteRequest($id, $item_id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsItemIdDelete'
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsItemIdDeleteRequest($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsItemIdDelete'
            );
        }

        // verify the required parameter 'item_id' is set
        if ($item_id === null || (is_array($item_id) && count($item_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $item_id when calling apiV1CreativePoolsIdItemsItemIdDelete'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/{itemId}';
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
        if ($item_id !== null) {
            $resourcePath = str_replace(
                '{' . 'itemId' . '}',
                ObjectSerializer::toPathValue($item_id),
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
     * Operation apiV1CreativePoolsIdItemsItemIdPut
     *
     * Update a single pool item.
     *
     * @param  int $id id (required)
     * @param  int $item_id item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest $api_v1_creative_pools_id_items_item_id_put_request api_v1_creative_pools_id_items_item_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsItemIdPut($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'][0])
    {
        $this->apiV1CreativePoolsIdItemsItemIdPutWithHttpInfo($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdPutWithHttpInfo
     *
     * Update a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest $api_v1_creative_pools_id_items_item_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsItemIdPutWithHttpInfo($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsItemIdPutRequest($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, $contentType);

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
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPut409Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdPutAsync
     *
     * Update a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest $api_v1_creative_pools_id_items_item_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdPutAsync($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'][0])
    {
        return $this->apiV1CreativePoolsIdItemsItemIdPutAsyncWithHttpInfo($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdPutAsyncWithHttpInfo
     *
     * Update a single pool item.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest $api_v1_creative_pools_id_items_item_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdPutAsyncWithHttpInfo($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsItemIdPutRequest($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsItemIdPut'
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest $api_v1_creative_pools_id_items_item_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsItemIdPutRequest($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsItemIdPut'
            );
        }

        // verify the required parameter 'item_id' is set
        if ($item_id === null || (is_array($item_id) && count($item_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $item_id when calling apiV1CreativePoolsIdItemsItemIdPut'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_items_item_id_put_request' is set
        if ($api_v1_creative_pools_id_items_item_id_put_request === null || (is_array($api_v1_creative_pools_id_items_item_id_put_request) && count($api_v1_creative_pools_id_items_item_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_items_item_id_put_request when calling apiV1CreativePoolsIdItemsItemIdPut'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/{itemId}';
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
        if ($item_id !== null) {
            $resourcePath = str_replace(
                '{' . 'itemId' . '}',
                ObjectSerializer::toPathValue($item_id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_creative_pools_id_items_item_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_items_item_id_put_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_items_item_id_put_request;
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
     * Operation apiV1CreativePoolsIdItemsItemIdTogglePut
     *
     * Toggle a pool item on / off.
     *
     * @param  int $id id (required)
     * @param  int $item_id item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsItemIdTogglePut($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'][0])
    {
        $this->apiV1CreativePoolsIdItemsItemIdTogglePutWithHttpInfo($id, $item_id, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdTogglePutWithHttpInfo
     *
     * Toggle a pool item on / off.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsItemIdTogglePutWithHttpInfo($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsItemIdTogglePutRequest($id, $item_id, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsItemIdTogglePutAsync
     *
     * Toggle a pool item on / off.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdTogglePutAsync($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'][0])
    {
        return $this->apiV1CreativePoolsIdItemsItemIdTogglePutAsyncWithHttpInfo($id, $item_id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsItemIdTogglePutAsyncWithHttpInfo
     *
     * Toggle a pool item on / off.
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsItemIdTogglePutAsyncWithHttpInfo($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsItemIdTogglePutRequest($id, $item_id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsItemIdTogglePut'
     *
     * @param  int $id (required)
     * @param  int $item_id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsItemIdTogglePutRequest($id, $item_id, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsItemIdTogglePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsItemIdTogglePut'
            );
        }

        // verify the required parameter 'item_id' is set
        if ($item_id === null || (is_array($item_id) && count($item_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $item_id when calling apiV1CreativePoolsIdItemsItemIdTogglePut'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/{itemId}/toggle';
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
        if ($item_id !== null) {
            $resourcePath = str_replace(
                '{' . 'itemId' . '}',
                ObjectSerializer::toPathValue($item_id),
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
     * Operation apiV1CreativePoolsIdItemsMassActionPut
     *
     * Bulk-change items of a creative pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest $api_v1_creative_pools_id_items_mass_action_put_request api_v1_creative_pools_id_items_mass_action_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsMassActionPut($id, $api_v1_creative_pools_id_items_mass_action_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'][0])
    {
        $this->apiV1CreativePoolsIdItemsMassActionPutWithHttpInfo($id, $api_v1_creative_pools_id_items_mass_action_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsMassActionPutWithHttpInfo
     *
     * Bulk-change items of a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest $api_v1_creative_pools_id_items_mass_action_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsMassActionPutWithHttpInfo($id, $api_v1_creative_pools_id_items_mass_action_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsMassActionPutRequest($id, $api_v1_creative_pools_id_items_mass_action_put_request, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsMassActionPutAsync
     *
     * Bulk-change items of a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest $api_v1_creative_pools_id_items_mass_action_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsMassActionPutAsync($id, $api_v1_creative_pools_id_items_mass_action_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'][0])
    {
        return $this->apiV1CreativePoolsIdItemsMassActionPutAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_mass_action_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsMassActionPutAsyncWithHttpInfo
     *
     * Bulk-change items of a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest $api_v1_creative_pools_id_items_mass_action_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsMassActionPutAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_mass_action_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsMassActionPutRequest($id, $api_v1_creative_pools_id_items_mass_action_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsMassActionPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest $api_v1_creative_pools_id_items_mass_action_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsMassActionPutRequest($id, $api_v1_creative_pools_id_items_mass_action_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsMassActionPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsMassActionPut'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_items_mass_action_put_request' is set
        if ($api_v1_creative_pools_id_items_mass_action_put_request === null || (is_array($api_v1_creative_pools_id_items_mass_action_put_request) && count($api_v1_creative_pools_id_items_mass_action_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_items_mass_action_put_request when calling apiV1CreativePoolsIdItemsMassActionPut'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/mass-action';
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
        if (isset($api_v1_creative_pools_id_items_mass_action_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_items_mass_action_put_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_items_mass_action_put_request;
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
     * Operation apiV1CreativePoolsIdItemsPost
     *
     * Add an item to a creative pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest $api_v1_creative_pools_id_items_post_request api_v1_creative_pools_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsPost($id, $api_v1_creative_pools_id_items_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsPost'][0])
    {
        $this->apiV1CreativePoolsIdItemsPostWithHttpInfo($id, $api_v1_creative_pools_id_items_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsPostWithHttpInfo
     *
     * Add an item to a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest $api_v1_creative_pools_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsPostWithHttpInfo($id, $api_v1_creative_pools_id_items_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsPost'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsPostRequest($id, $api_v1_creative_pools_id_items_post_request, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsPostAsync
     *
     * Add an item to a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest $api_v1_creative_pools_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsPostAsync($id, $api_v1_creative_pools_id_items_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsPost'][0])
    {
        return $this->apiV1CreativePoolsIdItemsPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsPostAsyncWithHttpInfo
     *
     * Add an item to a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest $api_v1_creative_pools_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsPostRequest($id, $api_v1_creative_pools_id_items_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest $api_v1_creative_pools_id_items_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsPostRequest($id, $api_v1_creative_pools_id_items_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsPost'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_items_post_request' is set
        if ($api_v1_creative_pools_id_items_post_request === null || (is_array($api_v1_creative_pools_id_items_post_request) && count($api_v1_creative_pools_id_items_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_items_post_request when calling apiV1CreativePoolsIdItemsPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items';
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
        if (isset($api_v1_creative_pools_id_items_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_items_post_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_items_post_request;
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
     * Operation apiV1CreativePoolsIdItemsRecoverPost
     *
     * Put removed items back into a creative pool.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest $api_v1_creative_pools_id_items_recover_post_request api_v1_creative_pools_id_items_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdItemsRecoverPost($id, $api_v1_creative_pools_id_items_recover_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'][0])
    {
        $this->apiV1CreativePoolsIdItemsRecoverPostWithHttpInfo($id, $api_v1_creative_pools_id_items_recover_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdItemsRecoverPostWithHttpInfo
     *
     * Put removed items back into a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest $api_v1_creative_pools_id_items_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdItemsRecoverPostWithHttpInfo($id, $api_v1_creative_pools_id_items_recover_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'][0])
    {
        $request = $this->apiV1CreativePoolsIdItemsRecoverPostRequest($id, $api_v1_creative_pools_id_items_recover_post_request, $contentType);

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
     * Operation apiV1CreativePoolsIdItemsRecoverPostAsync
     *
     * Put removed items back into a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest $api_v1_creative_pools_id_items_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsRecoverPostAsync($id, $api_v1_creative_pools_id_items_recover_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'][0])
    {
        return $this->apiV1CreativePoolsIdItemsRecoverPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_recover_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdItemsRecoverPostAsyncWithHttpInfo
     *
     * Put removed items back into a creative pool.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest $api_v1_creative_pools_id_items_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdItemsRecoverPostAsyncWithHttpInfo($id, $api_v1_creative_pools_id_items_recover_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdItemsRecoverPostRequest($id, $api_v1_creative_pools_id_items_recover_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdItemsRecoverPost'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest $api_v1_creative_pools_id_items_recover_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdItemsRecoverPostRequest($id, $api_v1_creative_pools_id_items_recover_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdItemsRecoverPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdItemsRecoverPost'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_items_recover_post_request' is set
        if ($api_v1_creative_pools_id_items_recover_post_request === null || (is_array($api_v1_creative_pools_id_items_recover_post_request) && count($api_v1_creative_pools_id_items_recover_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_items_recover_post_request when calling apiV1CreativePoolsIdItemsRecoverPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/items/recover';
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
        if (isset($api_v1_creative_pools_id_items_recover_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_items_recover_post_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_items_recover_post_request;
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
     * Operation apiV1CreativePoolsIdPut
     *
     * Update creative pool meta.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest $api_v1_creative_pools_id_put_request api_v1_creative_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdPut($id, $api_v1_creative_pools_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdPut'][0])
    {
        $this->apiV1CreativePoolsIdPutWithHttpInfo($id, $api_v1_creative_pools_id_put_request, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdPutWithHttpInfo
     *
     * Update creative pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest $api_v1_creative_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdPutWithHttpInfo($id, $api_v1_creative_pools_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdPut'][0])
    {
        $request = $this->apiV1CreativePoolsIdPutRequest($id, $api_v1_creative_pools_id_put_request, $contentType);

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
     * Operation apiV1CreativePoolsIdPutAsync
     *
     * Update creative pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest $api_v1_creative_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdPutAsync($id, $api_v1_creative_pools_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdPut'][0])
    {
        return $this->apiV1CreativePoolsIdPutAsyncWithHttpInfo($id, $api_v1_creative_pools_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdPutAsyncWithHttpInfo
     *
     * Update creative pool meta.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest $api_v1_creative_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdPutAsyncWithHttpInfo($id, $api_v1_creative_pools_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdPutRequest($id, $api_v1_creative_pools_id_put_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest $api_v1_creative_pools_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdPutRequest($id, $api_v1_creative_pools_id_put_request, string $contentType = self::contentTypes['apiV1CreativePoolsIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdPut'
            );
        }

        // verify the required parameter 'api_v1_creative_pools_id_put_request' is set
        if ($api_v1_creative_pools_id_put_request === null || (is_array($api_v1_creative_pools_id_put_request) && count($api_v1_creative_pools_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_id_put_request when calling apiV1CreativePoolsIdPut'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}';
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
        if (isset($api_v1_creative_pools_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_id_put_request));
            } else {
                $httpBody = $api_v1_creative_pools_id_put_request;
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
     * Operation apiV1CreativePoolsIdRecoverPost
     *
     * Restore an archived creative pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativePoolsIdRecoverPost($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdRecoverPost'][0])
    {
        $this->apiV1CreativePoolsIdRecoverPostWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CreativePoolsIdRecoverPostWithHttpInfo
     *
     * Restore an archived creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdRecoverPostWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdRecoverPost'][0])
    {
        $request = $this->apiV1CreativePoolsIdRecoverPostRequest($id, $contentType);

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
     * Operation apiV1CreativePoolsIdRecoverPostAsync
     *
     * Restore an archived creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdRecoverPostAsync($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdRecoverPost'][0])
    {
        return $this->apiV1CreativePoolsIdRecoverPostAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdRecoverPostAsyncWithHttpInfo
     *
     * Restore an archived creative pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdRecoverPostAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdRecoverPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativePoolsIdRecoverPostRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdRecoverPost'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdRecoverPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdRecoverPostRequest($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdRecoverPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdRecoverPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/recover';
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
     * Operation apiV1CreativePoolsIdUsedInGet
     *
     * List campaigns / ads that reference this pool.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response
     */
    public function apiV1CreativePoolsIdUsedInGet($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdUsedInGet'][0])
    {
        list($response) = $this->apiV1CreativePoolsIdUsedInGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsIdUsedInGetWithHttpInfo
     *
     * List campaigns / ads that reference this pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsIdUsedInGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdUsedInGet'][0])
    {
        $request = $this->apiV1CreativePoolsIdUsedInGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response',
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
                '\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1CreativePoolsIdUsedInGetAsync
     *
     * List campaigns / ads that reference this pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdUsedInGetAsync($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdUsedInGet'][0])
    {
        return $this->apiV1CreativePoolsIdUsedInGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsIdUsedInGetAsyncWithHttpInfo
     *
     * List campaigns / ads that reference this pool.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsIdUsedInGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdUsedInGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response';
        $request = $this->apiV1CreativePoolsIdUsedInGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsIdUsedInGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsIdUsedInGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsIdUsedInGetRequest($id, string $contentType = self::contentTypes['apiV1CreativePoolsIdUsedInGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativePoolsIdUsedInGet'
            );
        }


        $resourcePath = '/api/v1/creative-pools/{id}/used-in';
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
     * Operation apiV1CreativePoolsPost
     *
     * Create a creative pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest $api_v1_creative_pools_post_request api_v1_creative_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1CreativePoolsPost($api_v1_creative_pools_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsPost'][0])
    {
        list($response) = $this->apiV1CreativePoolsPostWithHttpInfo($api_v1_creative_pools_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1CreativePoolsPostWithHttpInfo
     *
     * Create a creative pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest $api_v1_creative_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativePoolsPostWithHttpInfo($api_v1_creative_pools_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsPost'][0])
    {
        $request = $this->apiV1CreativePoolsPostRequest($api_v1_creative_pools_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
                '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response',
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
     * Operation apiV1CreativePoolsPostAsync
     *
     * Create a creative pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest $api_v1_creative_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsPostAsync($api_v1_creative_pools_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsPost'][0])
    {
        return $this->apiV1CreativePoolsPostAsyncWithHttpInfo($api_v1_creative_pools_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativePoolsPostAsyncWithHttpInfo
     *
     * Create a creative pool.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest $api_v1_creative_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativePoolsPostAsyncWithHttpInfo($api_v1_creative_pools_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response';
        $request = $this->apiV1CreativePoolsPostRequest($api_v1_creative_pools_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativePoolsPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest $api_v1_creative_pools_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativePoolsPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativePoolsPostRequest($api_v1_creative_pools_post_request, string $contentType = self::contentTypes['apiV1CreativePoolsPost'][0])
    {

        // verify the required parameter 'api_v1_creative_pools_post_request' is set
        if ($api_v1_creative_pools_post_request === null || (is_array($api_v1_creative_pools_post_request) && count($api_v1_creative_pools_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creative_pools_post_request when calling apiV1CreativePoolsPost'
            );
        }


        $resourcePath = '/api/v1/creative-pools';
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
        if (isset($api_v1_creative_pools_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creative_pools_post_request));
            } else {
                $httpBody = $api_v1_creative_pools_post_request;
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
     * Operation apiV1CreativesBulkDeletePost
     *
     * Soft-delete many media-bank creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest $api_v1_creatives_bulk_delete_post_request api_v1_creatives_bulk_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesBulkDeletePost($api_v1_creatives_bulk_delete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkDeletePost'][0])
    {
        $this->apiV1CreativesBulkDeletePostWithHttpInfo($api_v1_creatives_bulk_delete_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativesBulkDeletePostWithHttpInfo
     *
     * Soft-delete many media-bank creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest $api_v1_creatives_bulk_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkDeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesBulkDeletePostWithHttpInfo($api_v1_creatives_bulk_delete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkDeletePost'][0])
    {
        $request = $this->apiV1CreativesBulkDeletePostRequest($api_v1_creatives_bulk_delete_post_request, $contentType);

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
     * Operation apiV1CreativesBulkDeletePostAsync
     *
     * Soft-delete many media-bank creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest $api_v1_creatives_bulk_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkDeletePostAsync($api_v1_creatives_bulk_delete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkDeletePost'][0])
    {
        return $this->apiV1CreativesBulkDeletePostAsyncWithHttpInfo($api_v1_creatives_bulk_delete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesBulkDeletePostAsyncWithHttpInfo
     *
     * Soft-delete many media-bank creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest $api_v1_creatives_bulk_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkDeletePostAsyncWithHttpInfo($api_v1_creatives_bulk_delete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkDeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesBulkDeletePostRequest($api_v1_creatives_bulk_delete_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativesBulkDeletePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest $api_v1_creatives_bulk_delete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkDeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesBulkDeletePostRequest($api_v1_creatives_bulk_delete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkDeletePost'][0])
    {

        // verify the required parameter 'api_v1_creatives_bulk_delete_post_request' is set
        if ($api_v1_creatives_bulk_delete_post_request === null || (is_array($api_v1_creatives_bulk_delete_post_request) && count($api_v1_creatives_bulk_delete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_bulk_delete_post_request when calling apiV1CreativesBulkDeletePost'
            );
        }


        $resourcePath = '/api/v1/creatives/bulk-delete';
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
        if (isset($api_v1_creatives_bulk_delete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_bulk_delete_post_request));
            } else {
                $httpBody = $api_v1_creatives_bulk_delete_post_request;
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
     * Operation apiV1CreativesBulkUndeletePost
     *
     * Restore many deleted creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest $api_v1_creatives_bulk_undelete_post_request api_v1_creatives_bulk_undelete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkUndeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesBulkUndeletePost($api_v1_creatives_bulk_undelete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkUndeletePost'][0])
    {
        $this->apiV1CreativesBulkUndeletePostWithHttpInfo($api_v1_creatives_bulk_undelete_post_request, $contentType);
    }

    /**
     * Operation apiV1CreativesBulkUndeletePostWithHttpInfo
     *
     * Restore many deleted creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest $api_v1_creatives_bulk_undelete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkUndeletePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesBulkUndeletePostWithHttpInfo($api_v1_creatives_bulk_undelete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkUndeletePost'][0])
    {
        $request = $this->apiV1CreativesBulkUndeletePostRequest($api_v1_creatives_bulk_undelete_post_request, $contentType);

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
     * Operation apiV1CreativesBulkUndeletePostAsync
     *
     * Restore many deleted creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest $api_v1_creatives_bulk_undelete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkUndeletePostAsync($api_v1_creatives_bulk_undelete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkUndeletePost'][0])
    {
        return $this->apiV1CreativesBulkUndeletePostAsyncWithHttpInfo($api_v1_creatives_bulk_undelete_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesBulkUndeletePostAsyncWithHttpInfo
     *
     * Restore many deleted creatives.
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest $api_v1_creatives_bulk_undelete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesBulkUndeletePostAsyncWithHttpInfo($api_v1_creatives_bulk_undelete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkUndeletePost'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesBulkUndeletePostRequest($api_v1_creatives_bulk_undelete_post_request, $contentType);

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
     * Create request for operation 'apiV1CreativesBulkUndeletePost'
     *
     * @param  \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest $api_v1_creatives_bulk_undelete_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesBulkUndeletePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesBulkUndeletePostRequest($api_v1_creatives_bulk_undelete_post_request, string $contentType = self::contentTypes['apiV1CreativesBulkUndeletePost'][0])
    {

        // verify the required parameter 'api_v1_creatives_bulk_undelete_post_request' is set
        if ($api_v1_creatives_bulk_undelete_post_request === null || (is_array($api_v1_creatives_bulk_undelete_post_request) && count($api_v1_creatives_bulk_undelete_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_creatives_bulk_undelete_post_request when calling apiV1CreativesBulkUndeletePost'
            );
        }


        $resourcePath = '/api/v1/creatives/bulk-undelete';
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
        if (isset($api_v1_creatives_bulk_undelete_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_creatives_bulk_undelete_post_request));
            } else {
                $httpBody = $api_v1_creatives_bulk_undelete_post_request;
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
     * Operation apiV1CreativesIdUndeletePut
     *
     * Restore one deleted creative.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUndeletePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1CreativesIdUndeletePut($id, string $contentType = self::contentTypes['apiV1CreativesIdUndeletePut'][0])
    {
        $this->apiV1CreativesIdUndeletePutWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1CreativesIdUndeletePutWithHttpInfo
     *
     * Restore one deleted creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUndeletePut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1CreativesIdUndeletePutWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdUndeletePut'][0])
    {
        $request = $this->apiV1CreativesIdUndeletePutRequest($id, $contentType);

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
     * Operation apiV1CreativesIdUndeletePutAsync
     *
     * Restore one deleted creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUndeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdUndeletePutAsync($id, string $contentType = self::contentTypes['apiV1CreativesIdUndeletePut'][0])
    {
        return $this->apiV1CreativesIdUndeletePutAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1CreativesIdUndeletePutAsyncWithHttpInfo
     *
     * Restore one deleted creative.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUndeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1CreativesIdUndeletePutAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1CreativesIdUndeletePut'][0])
    {
        $returnType = '';
        $request = $this->apiV1CreativesIdUndeletePutRequest($id, $contentType);

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
     * Create request for operation 'apiV1CreativesIdUndeletePut'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1CreativesIdUndeletePut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1CreativesIdUndeletePutRequest($id, string $contentType = self::contentTypes['apiV1CreativesIdUndeletePut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1CreativesIdUndeletePut'
            );
        }


        $resourcePath = '/api/v1/creatives/{id}/undelete';
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

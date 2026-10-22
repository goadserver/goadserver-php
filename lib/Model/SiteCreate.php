<?php
/**
 * SiteCreate
 *
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

namespace GoAdServerApi\Model;

use \ArrayAccess;
use \GoAdServerApi\ObjectSerializer;

/**
 * SiteCreate Class Doc Comment
 *
 * @category Class
 * @description Site (publisher_sites) create body. Required: &#x60;name&#x60; + &#x60;url&#x60;. Module-gated fields become required when their module is active on the tenant — see &#x60;GET /api/v1/sites/_schema&#x60;.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SiteCreate implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'SiteCreate';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'url' => 'string',
        'description' => 'string',
        'main_catid' => 'int',
        'typeid' => 'int',
        'categories' => 'int[]',
        'sitelanguages' => 'string[]',
        'postbacks' => 'array<string,mixed>[]',
        'ad_domainid' => 'int',
        'tags' => 'int[]',
        'isapp' => 'bool',
        'app_bundle' => 'string',
        'app_store_url' => 'string',
        'app_platform' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'name' => null,
        'url' => null,
        'description' => null,
        'main_catid' => null,
        'typeid' => null,
        'categories' => null,
        'sitelanguages' => null,
        'postbacks' => null,
        'ad_domainid' => null,
        'tags' => null,
        'isapp' => null,
        'app_bundle' => null,
        'app_store_url' => null,
        'app_platform' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'url' => false,
        'description' => false,
        'main_catid' => false,
        'typeid' => false,
        'categories' => false,
        'sitelanguages' => false,
        'postbacks' => false,
        'ad_domainid' => false,
        'tags' => false,
        'isapp' => false,
        'app_bundle' => false,
        'app_store_url' => false,
        'app_platform' => false
    ];

    /**
     * If a nullable field gets set to null, insert it here
     *
     * @var boolean[]
     */
    protected array $openAPINullablesSetToNull = [];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of nullable properties
     *
     * @return array
     */
    protected static function openAPINullables(): array
    {
        return self::$openAPINullables;
    }

    /**
     * Array of nullable field names deliberately set to null
     *
     * @return boolean[]
     */
    private function getOpenAPINullablesSetToNull(): array
    {
        return $this->openAPINullablesSetToNull;
    }

    /**
     * Setter - Array of nullable field names deliberately set to null
     *
     * @param boolean[] $openAPINullablesSetToNull
     */
    private function setOpenAPINullablesSetToNull(array $openAPINullablesSetToNull): void
    {
        $this->openAPINullablesSetToNull = $openAPINullablesSetToNull;
    }

    /**
     * Checks if a property is nullable
     *
     * @param string $property
     * @return bool
     */
    public static function isNullable(string $property): bool
    {
        return self::openAPINullables()[$property] ?? false;
    }

    /**
     * Checks if a nullable property is set to null.
     *
     * @param string $property
     * @return bool
     */
    public function isNullableSetToNull(string $property): bool
    {
        return in_array($property, $this->getOpenAPINullablesSetToNull(), true);
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'name' => 'name',
        'url' => 'url',
        'description' => 'description',
        'main_catid' => 'main_catid',
        'typeid' => 'typeid',
        'categories' => 'categories',
        'sitelanguages' => 'sitelanguages',
        'postbacks' => 'postbacks',
        'ad_domainid' => 'ad_domainid',
        'tags' => 'tags',
        'isapp' => 'isapp',
        'app_bundle' => 'app_bundle',
        'app_store_url' => 'app_store_url',
        'app_platform' => 'app_platform'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'url' => 'setUrl',
        'description' => 'setDescription',
        'main_catid' => 'setMainCatid',
        'typeid' => 'setTypeid',
        'categories' => 'setCategories',
        'sitelanguages' => 'setSitelanguages',
        'postbacks' => 'setPostbacks',
        'ad_domainid' => 'setAdDomainid',
        'tags' => 'setTags',
        'isapp' => 'setIsapp',
        'app_bundle' => 'setAppBundle',
        'app_store_url' => 'setAppStoreUrl',
        'app_platform' => 'setAppPlatform'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'url' => 'getUrl',
        'description' => 'getDescription',
        'main_catid' => 'getMainCatid',
        'typeid' => 'getTypeid',
        'categories' => 'getCategories',
        'sitelanguages' => 'getSitelanguages',
        'postbacks' => 'getPostbacks',
        'ad_domainid' => 'getAdDomainid',
        'tags' => 'getTags',
        'isapp' => 'getIsapp',
        'app_bundle' => 'getAppBundle',
        'app_store_url' => 'getAppStoreUrl',
        'app_platform' => 'getAppPlatform'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    public const APP_PLATFORM_IOS = 'ios';
    public const APP_PLATFORM_ANDROID = 'android';
    public const APP_PLATFORM_OTHER = 'other';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getAppPlatformAllowableValues()
    {
        return [
            self::APP_PLATFORM_IOS,
            self::APP_PLATFORM_ANDROID,
            self::APP_PLATFORM_OTHER,
        ];
    }

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[]|null $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(?array $data = null)
    {
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('main_catid', $data ?? [], 1);
        $this->setIfExists('typeid', $data ?? [], 0);
        $this->setIfExists('categories', $data ?? [], null);
        $this->setIfExists('sitelanguages', $data ?? [], null);
        $this->setIfExists('postbacks', $data ?? [], null);
        $this->setIfExists('ad_domainid', $data ?? [], 0);
        $this->setIfExists('tags', $data ?? [], null);
        $this->setIfExists('isapp', $data ?? [], false);
        $this->setIfExists('app_bundle', $data ?? [], null);
        $this->setIfExists('app_store_url', $data ?? [], null);
        $this->setIfExists('app_platform', $data ?? [], null);
    }

    /**
     * Sets $this->container[$variableName] to the given data or to the given default Value; if $variableName
     * is nullable and its value is set to null in the $fields array, then mark it as "set to null" in the
     * $this->openAPINullablesSetToNull array
     *
     * @param string $variableName
     * @param array  $fields
     * @param mixed  $defaultValue
     */
    private function setIfExists(string $variableName, array $fields, $defaultValue): void
    {
        if (self::isNullable($variableName) && array_key_exists($variableName, $fields) && is_null($fields[$variableName])) {
            $this->openAPINullablesSetToNull[] = $variableName;
        }

        $this->container[$variableName] = $fields[$variableName] ?? $defaultValue;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['name'] === null) {
            $invalidProperties[] = "'name' can't be null";
        }
        if ($this->container['url'] === null) {
            $invalidProperties[] = "'url' can't be null";
        }
        if (!is_null($this->container['app_bundle']) && (mb_strlen($this->container['app_bundle']) > 255)) {
            $invalidProperties[] = "invalid value for 'app_bundle', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['app_store_url']) && (mb_strlen($this->container['app_store_url']) > 500)) {
            $invalidProperties[] = "invalid value for 'app_store_url', the character length must be smaller than or equal to 500.";
        }

        $allowedValues = $this->getAppPlatformAllowableValues();
        if (!is_null($this->container['app_platform']) && !in_array($this->container['app_platform'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'app_platform', must be one of '%s'",
                $this->container['app_platform'],
                implode("', '", $allowedValues)
            );
        }

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets name
     *
     * @return string
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     *
     * @param string $name name
     *
     * @return self
     */
    public function setName($name)
    {
        if (is_null($name)) {
            throw new \InvalidArgumentException('non-nullable name cannot be null');
        }
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string $url url
     *
     * @return self
     */
    public function setUrl($url)
    {
        if (is_null($url)) {
            throw new \InvalidArgumentException('non-nullable url cannot be null');
        }
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->container['description'];
    }

    /**
     * Sets description
     *
     * @param string|null $description description
     *
     * @return self
     */
    public function setDescription($description)
    {
        if (is_null($description)) {
            throw new \InvalidArgumentException('non-nullable description cannot be null');
        }
        $this->container['description'] = $description;

        return $this;
    }

    /**
     * Gets main_catid
     *
     * @return int|null
     */
    public function getMainCatid()
    {
        return $this->container['main_catid'];
    }

    /**
     * Sets main_catid
     *
     * @param int|null $main_catid Main-category id from /api/v1/lookups/main-categories.
     *
     * @return self
     */
    public function setMainCatid($main_catid)
    {
        if (is_null($main_catid)) {
            throw new \InvalidArgumentException('non-nullable main_catid cannot be null');
        }
        $this->container['main_catid'] = $main_catid;

        return $this;
    }

    /**
     * Gets typeid
     *
     * @return int|null
     */
    public function getTypeid()
    {
        return $this->container['typeid'];
    }

    /**
     * Sets typeid
     *
     * @param int|null $typeid Website-type id from /api/v1/lookups/website-types. Module-gated.
     *
     * @return self
     */
    public function setTypeid($typeid)
    {
        if (is_null($typeid)) {
            throw new \InvalidArgumentException('non-nullable typeid cannot be null');
        }
        $this->container['typeid'] = $typeid;

        return $this;
    }

    /**
     * Gets categories
     *
     * @return int[]|null
     */
    public function getCategories()
    {
        return $this->container['categories'];
    }

    /**
     * Sets categories
     *
     * @param int[]|null $categories Category ids from /api/v1/lookups/categories. Module-gated (modules.global.categories).
     *
     * @return self
     */
    public function setCategories($categories)
    {
        if (is_null($categories)) {
            throw new \InvalidArgumentException('non-nullable categories cannot be null');
        }
        $this->container['categories'] = $categories;

        return $this;
    }

    /**
     * Gets sitelanguages
     *
     * @return string[]|null
     */
    public function getSitelanguages()
    {
        return $this->container['sitelanguages'];
    }

    /**
     * Sets sitelanguages
     *
     * @param string[]|null $sitelanguages ISO language codes from /api/v1/lookups/website-languages. Module-gated (modules.global.website_languages).
     *
     * @return self
     */
    public function setSitelanguages($sitelanguages)
    {
        if (is_null($sitelanguages)) {
            throw new \InvalidArgumentException('non-nullable sitelanguages cannot be null');
        }
        $this->container['sitelanguages'] = $sitelanguages;

        return $this;
    }

    /**
     * Gets postbacks
     *
     * @return array<string,mixed>[]|null
     */
    public function getPostbacks()
    {
        return $this->container['postbacks'];
    }

    /**
     * Sets postbacks
     *
     * @param array<string,mixed>[]|null $postbacks Per-event postback URLs. Module-gated (modules.publisher.postbacks).
     *
     * @return self
     */
    public function setPostbacks($postbacks)
    {
        if (is_null($postbacks)) {
            throw new \InvalidArgumentException('non-nullable postbacks cannot be null');
        }
        $this->container['postbacks'] = $postbacks;

        return $this;
    }

    /**
     * Gets ad_domainid
     *
     * @return int|null
     */
    public function getAdDomainid()
    {
        return $this->container['ad_domainid'];
    }

    /**
     * Sets ad_domainid
     *
     * @param int|null $ad_domainid Ad-domain id when the tenant uses a separate ad-serving domain.
     *
     * @return self
     */
    public function setAdDomainid($ad_domainid)
    {
        if (is_null($ad_domainid)) {
            throw new \InvalidArgumentException('non-nullable ad_domainid cannot be null');
        }
        $this->container['ad_domainid'] = $ad_domainid;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return int[]|null
     */
    public function getTags()
    {
        return $this->container['tags'];
    }

    /**
     * Sets tags
     *
     * @param int[]|null $tags Site groups (campaign_groups ids). A zone with no groups of its own inherits these.
     *
     * @return self
     */
    public function setTags($tags)
    {
        if (is_null($tags)) {
            throw new \InvalidArgumentException('non-nullable tags cannot be null');
        }
        $this->container['tags'] = $tags;

        return $this;
    }

    /**
     * Gets isapp
     *
     * @return bool|null
     */
    public function getIsapp()
    {
        return $this->container['isapp'];
    }

    /**
     * Sets isapp
     *
     * @param bool|null $isapp A mobile app instead of a website (in-app ads through Prebid Mobile or an SSP's OpenRTB app requests, docs/IN_APP.md). `url` is then set by the server to https://<bundle pseudo-domain>.inapp (lowercase, other characters → '-', e.g. com.example.game → com-example-game.inapp) — the address the ad server runs the app's traffic under — and the site skips the verification meta tag (publish the ads.txt line as app-ads.txt on the developer website). Campaign domain filters and blocklists take apps as `app:<bundle>`.
     *
     * @return self
     */
    public function setIsapp($isapp)
    {
        if (is_null($isapp)) {
            throw new \InvalidArgumentException('non-nullable isapp cannot be null');
        }
        $this->container['isapp'] = $isapp;

        return $this;
    }

    /**
     * Gets app_bundle
     *
     * @return string|null
     */
    public function getAppBundle()
    {
        return $this->container['app_bundle'];
    }

    /**
     * Sets app_bundle
     *
     * @param string|null $app_bundle With isapp (required): the bundle the SDK sends as app.bundle — Android package name, iOS bundle id or numeric App Store id.
     *
     * @return self
     */
    public function setAppBundle($app_bundle)
    {
        if (is_null($app_bundle)) {
            throw new \InvalidArgumentException('non-nullable app_bundle cannot be null');
        }
        if ((mb_strlen($app_bundle) > 255)) {
            throw new \InvalidArgumentException('invalid length for $app_bundle when calling SiteCreate., must be smaller than or equal to 255.');
        }

        $this->container['app_bundle'] = $app_bundle;

        return $this;
    }

    /**
     * Gets app_store_url
     *
     * @return string|null
     */
    public function getAppStoreUrl()
    {
        return $this->container['app_store_url'];
    }

    /**
     * Sets app_store_url
     *
     * @param string|null $app_store_url With isapp: the store page (http/https).
     *
     * @return self
     */
    public function setAppStoreUrl($app_store_url)
    {
        if (is_null($app_store_url)) {
            throw new \InvalidArgumentException('non-nullable app_store_url cannot be null');
        }
        if ((mb_strlen($app_store_url) > 500)) {
            throw new \InvalidArgumentException('invalid length for $app_store_url when calling SiteCreate., must be smaller than or equal to 500.');
        }

        $this->container['app_store_url'] = $app_store_url;

        return $this;
    }

    /**
     * Gets app_platform
     *
     * @return string|null
     */
    public function getAppPlatform()
    {
        return $this->container['app_platform'];
    }

    /**
     * Sets app_platform
     *
     * @param string|null $app_platform With isapp; guessed from app_store_url when omitted.
     *
     * @return self
     */
    public function setAppPlatform($app_platform)
    {
        if (is_null($app_platform)) {
            throw new \InvalidArgumentException('non-nullable app_platform cannot be null');
        }
        $allowedValues = $this->getAppPlatformAllowableValues();
        if (!in_array($app_platform, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'app_platform', must be one of '%s'",
                    $app_platform,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['app_platform'] = $app_platform;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer|string $offset Offset
     *
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet(mixed $offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}



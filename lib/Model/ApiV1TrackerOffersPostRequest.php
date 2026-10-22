<?php
/**
 * ApiV1TrackerOffersPostRequest
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
 * ApiV1TrackerOffersPostRequest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1TrackerOffersPostRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_tracker_offers_post_request';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'name' => 'string',
        'destination_url' => 'string',
        'urls' => '\GoAdServerApi\Model\ApiV1TrackerOffersPostRequestUrlsInner[]',
        'payout' => 'float',
        'revshare_pct' => 'float',
        'paytype' => 'string',
        'conv_type' => 'string',
        'country' => 'string',
        'affiliate_network_id' => 'int',
        'failover_offer_id' => 'int',
        'daily_cap' => 'int',
        'total_cap' => 'int',
        'notes' => 'string',
        'paused' => 'bool',
        'as_failover' => 'bool',
        'tags' => 'string',
        'offertypeid' => 'int',
        'regionids' => 'int[]',
        'device_type_ids' => 'int[]',
        'osids' => 'int[]',
        'browserids' => 'int[]',
        'carrierids' => 'int[]',
        'ispids' => 'int[]'
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
        'destination_url' => null,
        'urls' => null,
        'payout' => null,
        'revshare_pct' => null,
        'paytype' => null,
        'conv_type' => null,
        'country' => null,
        'affiliate_network_id' => null,
        'failover_offer_id' => null,
        'daily_cap' => null,
        'total_cap' => null,
        'notes' => null,
        'paused' => null,
        'as_failover' => null,
        'tags' => null,
        'offertypeid' => null,
        'regionids' => null,
        'device_type_ids' => null,
        'osids' => null,
        'browserids' => null,
        'carrierids' => null,
        'ispids' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'name' => false,
        'destination_url' => false,
        'urls' => false,
        'payout' => false,
        'revshare_pct' => false,
        'paytype' => false,
        'conv_type' => false,
        'country' => false,
        'affiliate_network_id' => false,
        'failover_offer_id' => false,
        'daily_cap' => false,
        'total_cap' => false,
        'notes' => false,
        'paused' => false,
        'as_failover' => false,
        'tags' => false,
        'offertypeid' => false,
        'regionids' => false,
        'device_type_ids' => false,
        'osids' => false,
        'browserids' => false,
        'carrierids' => false,
        'ispids' => false
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
        'destination_url' => 'destination_url',
        'urls' => 'urls',
        'payout' => 'payout',
        'revshare_pct' => 'revshare_pct',
        'paytype' => 'paytype',
        'conv_type' => 'conv_type',
        'country' => 'country',
        'affiliate_network_id' => 'affiliate_network_id',
        'failover_offer_id' => 'failover_offer_id',
        'daily_cap' => 'daily_cap',
        'total_cap' => 'total_cap',
        'notes' => 'notes',
        'paused' => 'paused',
        'as_failover' => 'as_failover',
        'tags' => 'tags',
        'offertypeid' => 'offertypeid',
        'regionids' => 'regionids',
        'device_type_ids' => 'device_type_ids',
        'osids' => 'osids',
        'browserids' => 'browserids',
        'carrierids' => 'carrierids',
        'ispids' => 'ispids'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'name' => 'setName',
        'destination_url' => 'setDestinationUrl',
        'urls' => 'setUrls',
        'payout' => 'setPayout',
        'revshare_pct' => 'setRevsharePct',
        'paytype' => 'setPaytype',
        'conv_type' => 'setConvType',
        'country' => 'setCountry',
        'affiliate_network_id' => 'setAffiliateNetworkId',
        'failover_offer_id' => 'setFailoverOfferId',
        'daily_cap' => 'setDailyCap',
        'total_cap' => 'setTotalCap',
        'notes' => 'setNotes',
        'paused' => 'setPaused',
        'as_failover' => 'setAsFailover',
        'tags' => 'setTags',
        'offertypeid' => 'setOffertypeid',
        'regionids' => 'setRegionids',
        'device_type_ids' => 'setDeviceTypeIds',
        'osids' => 'setOsids',
        'browserids' => 'setBrowserids',
        'carrierids' => 'setCarrierids',
        'ispids' => 'setIspids'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'name' => 'getName',
        'destination_url' => 'getDestinationUrl',
        'urls' => 'getUrls',
        'payout' => 'getPayout',
        'revshare_pct' => 'getRevsharePct',
        'paytype' => 'getPaytype',
        'conv_type' => 'getConvType',
        'country' => 'getCountry',
        'affiliate_network_id' => 'getAffiliateNetworkId',
        'failover_offer_id' => 'getFailoverOfferId',
        'daily_cap' => 'getDailyCap',
        'total_cap' => 'getTotalCap',
        'notes' => 'getNotes',
        'paused' => 'getPaused',
        'as_failover' => 'getAsFailover',
        'tags' => 'getTags',
        'offertypeid' => 'getOffertypeid',
        'regionids' => 'getRegionids',
        'device_type_ids' => 'getDeviceTypeIds',
        'osids' => 'getOsids',
        'browserids' => 'getBrowserids',
        'carrierids' => 'getCarrierids',
        'ispids' => 'getIspids'
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
        $this->setIfExists('destination_url', $data ?? [], null);
        $this->setIfExists('urls', $data ?? [], null);
        $this->setIfExists('payout', $data ?? [], null);
        $this->setIfExists('revshare_pct', $data ?? [], null);
        $this->setIfExists('paytype', $data ?? [], null);
        $this->setIfExists('conv_type', $data ?? [], null);
        $this->setIfExists('country', $data ?? [], null);
        $this->setIfExists('affiliate_network_id', $data ?? [], null);
        $this->setIfExists('failover_offer_id', $data ?? [], null);
        $this->setIfExists('daily_cap', $data ?? [], null);
        $this->setIfExists('total_cap', $data ?? [], null);
        $this->setIfExists('notes', $data ?? [], null);
        $this->setIfExists('paused', $data ?? [], null);
        $this->setIfExists('as_failover', $data ?? [], null);
        $this->setIfExists('tags', $data ?? [], null);
        $this->setIfExists('offertypeid', $data ?? [], null);
        $this->setIfExists('regionids', $data ?? [], null);
        $this->setIfExists('device_type_ids', $data ?? [], null);
        $this->setIfExists('osids', $data ?? [], null);
        $this->setIfExists('browserids', $data ?? [], null);
        $this->setIfExists('carrierids', $data ?? [], null);
        $this->setIfExists('ispids', $data ?? [], null);
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
     * Gets destination_url
     *
     * @return string|null
     */
    public function getDestinationUrl()
    {
        return $this->container['destination_url'];
    }

    /**
     * Sets destination_url
     *
     * @param string|null $destination_url Landing URL. Creates the offer's first URL row when the offer is created.
     *
     * @return self
     */
    public function setDestinationUrl($destination_url)
    {
        if (is_null($destination_url)) {
            throw new \InvalidArgumentException('non-nullable destination_url cannot be null');
        }
        $this->container['destination_url'] = $destination_url;

        return $this;
    }

    /**
     * Gets urls
     *
     * @return \GoAdServerApi\Model\ApiV1TrackerOffersPostRequestUrlsInner[]|null
     */
    public function getUrls()
    {
        return $this->container['urls'];
    }

    /**
     * Sets urls
     *
     * @param \GoAdServerApi\Model\ApiV1TrackerOffersPostRequestUrlsInner[]|null $urls Multiple landing URLs with rotation weights. Use instead of destination_url for a rotator.
     *
     * @return self
     */
    public function setUrls($urls)
    {
        if (is_null($urls)) {
            throw new \InvalidArgumentException('non-nullable urls cannot be null');
        }
        $this->container['urls'] = $urls;

        return $this;
    }

    /**
     * Gets payout
     *
     * @return float|null
     */
    public function getPayout()
    {
        return $this->container['payout'];
    }

    /**
     * Sets payout
     *
     * @param float|null $payout Fixed payout per conversion, in account currency.
     *
     * @return self
     */
    public function setPayout($payout)
    {
        if (is_null($payout)) {
            throw new \InvalidArgumentException('non-nullable payout cannot be null');
        }
        $this->container['payout'] = $payout;

        return $this;
    }

    /**
     * Gets revshare_pct
     *
     * @return float|null
     */
    public function getRevsharePct()
    {
        return $this->container['revshare_pct'];
    }

    /**
     * Sets revshare_pct
     *
     * @param float|null $revshare_pct Revenue share percentage, when the offer pays a share rather than a fixed amount.
     *
     * @return self
     */
    public function setRevsharePct($revshare_pct)
    {
        if (is_null($revshare_pct)) {
            throw new \InvalidArgumentException('non-nullable revshare_pct cannot be null');
        }
        $this->container['revshare_pct'] = $revshare_pct;

        return $this;
    }

    /**
     * Gets paytype
     *
     * @return string|null
     */
    public function getPaytype()
    {
        return $this->container['paytype'];
    }

    /**
     * Sets paytype
     *
     * @param string|null $paytype Payout model — fixed CPA or revshare.
     *
     * @return self
     */
    public function setPaytype($paytype)
    {
        if (is_null($paytype)) {
            throw new \InvalidArgumentException('non-nullable paytype cannot be null');
        }
        $this->container['paytype'] = $paytype;

        return $this;
    }

    /**
     * Gets conv_type
     *
     * @return string|null
     */
    public function getConvType()
    {
        return $this->container['conv_type'];
    }

    /**
     * Sets conv_type
     *
     * @param string|null $conv_type Conversion type letter (A–Z) this offer settles against. See /conversion-types.
     *
     * @return self
     */
    public function setConvType($conv_type)
    {
        if (is_null($conv_type)) {
            throw new \InvalidArgumentException('non-nullable conv_type cannot be null');
        }
        $this->container['conv_type'] = $conv_type;

        return $this;
    }

    /**
     * Gets country
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->container['country'];
    }

    /**
     * Sets country
     *
     * @param string|null $country Comma-separated country codes the offer targets. Empty = worldwide.
     *
     * @return self
     */
    public function setCountry($country)
    {
        if (is_null($country)) {
            throw new \InvalidArgumentException('non-nullable country cannot be null');
        }
        $this->container['country'] = $country;

        return $this;
    }

    /**
     * Gets affiliate_network_id
     *
     * @return int|null
     */
    public function getAffiliateNetworkId()
    {
        return $this->container['affiliate_network_id'];
    }

    /**
     * Sets affiliate_network_id
     *
     * @param int|null $affiliate_network_id Owning affiliate network (see /affiliate-networks). 0 = none.
     *
     * @return self
     */
    public function setAffiliateNetworkId($affiliate_network_id)
    {
        if (is_null($affiliate_network_id)) {
            throw new \InvalidArgumentException('non-nullable affiliate_network_id cannot be null');
        }
        $this->container['affiliate_network_id'] = $affiliate_network_id;

        return $this;
    }

    /**
     * Gets failover_offer_id
     *
     * @return int|null
     */
    public function getFailoverOfferId()
    {
        return $this->container['failover_offer_id'];
    }

    /**
     * Sets failover_offer_id
     *
     * @param int|null $failover_offer_id Offer to redirect to once this one caps out. 0 = none.
     *
     * @return self
     */
    public function setFailoverOfferId($failover_offer_id)
    {
        if (is_null($failover_offer_id)) {
            throw new \InvalidArgumentException('non-nullable failover_offer_id cannot be null');
        }
        $this->container['failover_offer_id'] = $failover_offer_id;

        return $this;
    }

    /**
     * Gets daily_cap
     *
     * @return int|null
     */
    public function getDailyCap()
    {
        return $this->container['daily_cap'];
    }

    /**
     * Sets daily_cap
     *
     * @param int|null $daily_cap Conversions per day before the offer stops serving. 0 = uncapped.
     *
     * @return self
     */
    public function setDailyCap($daily_cap)
    {
        if (is_null($daily_cap)) {
            throw new \InvalidArgumentException('non-nullable daily_cap cannot be null');
        }
        $this->container['daily_cap'] = $daily_cap;

        return $this;
    }

    /**
     * Gets total_cap
     *
     * @return int|null
     */
    public function getTotalCap()
    {
        return $this->container['total_cap'];
    }

    /**
     * Sets total_cap
     *
     * @param int|null $total_cap Lifetime conversion cap. 0 = uncapped.
     *
     * @return self
     */
    public function setTotalCap($total_cap)
    {
        if (is_null($total_cap)) {
            throw new \InvalidArgumentException('non-nullable total_cap cannot be null');
        }
        $this->container['total_cap'] = $total_cap;

        return $this;
    }

    /**
     * Gets notes
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->container['notes'];
    }

    /**
     * Sets notes
     *
     * @param string|null $notes notes
     *
     * @return self
     */
    public function setNotes($notes)
    {
        if (is_null($notes)) {
            throw new \InvalidArgumentException('non-nullable notes cannot be null');
        }
        $this->container['notes'] = $notes;

        return $this;
    }

    /**
     * Gets paused
     *
     * @return bool|null
     */
    public function getPaused()
    {
        return $this->container['paused'];
    }

    /**
     * Sets paused
     *
     * @param bool|null $paused paused
     *
     * @return self
     */
    public function setPaused($paused)
    {
        if (is_null($paused)) {
            throw new \InvalidArgumentException('non-nullable paused cannot be null');
        }
        $this->container['paused'] = $paused;

        return $this;
    }

    /**
     * Gets as_failover
     *
     * @return bool|null
     */
    public function getAsFailover()
    {
        return $this->container['as_failover'];
    }

    /**
     * Sets as_failover
     *
     * @param bool|null $as_failover Mark the offer as a failover-only destination — it is not picked directly.
     *
     * @return self
     */
    public function setAsFailover($as_failover)
    {
        if (is_null($as_failover)) {
            throw new \InvalidArgumentException('non-nullable as_failover cannot be null');
        }
        $this->container['as_failover'] = $as_failover;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return string|null
     */
    public function getTags()
    {
        return $this->container['tags'];
    }

    /**
     * Sets tags
     *
     * @param string|null $tags Comma-separated tracker tags.
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
     * Gets offertypeid
     *
     * @return int|null
     */
    public function getOffertypeid()
    {
        return $this->container['offertypeid'];
    }

    /**
     * Sets offertypeid
     *
     * @param int|null $offertypeid offertypeid
     *
     * @return self
     */
    public function setOffertypeid($offertypeid)
    {
        if (is_null($offertypeid)) {
            throw new \InvalidArgumentException('non-nullable offertypeid cannot be null');
        }
        $this->container['offertypeid'] = $offertypeid;

        return $this;
    }

    /**
     * Gets regionids
     *
     * @return int[]|null
     */
    public function getRegionids()
    {
        return $this->container['regionids'];
    }

    /**
     * Sets regionids
     *
     * @param int[]|null $regionids regionids
     *
     * @return self
     */
    public function setRegionids($regionids)
    {
        if (is_null($regionids)) {
            throw new \InvalidArgumentException('non-nullable regionids cannot be null');
        }
        $this->container['regionids'] = $regionids;

        return $this;
    }

    /**
     * Gets device_type_ids
     *
     * @return int[]|null
     */
    public function getDeviceTypeIds()
    {
        return $this->container['device_type_ids'];
    }

    /**
     * Sets device_type_ids
     *
     * @param int[]|null $device_type_ids device_type_ids
     *
     * @return self
     */
    public function setDeviceTypeIds($device_type_ids)
    {
        if (is_null($device_type_ids)) {
            throw new \InvalidArgumentException('non-nullable device_type_ids cannot be null');
        }
        $this->container['device_type_ids'] = $device_type_ids;

        return $this;
    }

    /**
     * Gets osids
     *
     * @return int[]|null
     */
    public function getOsids()
    {
        return $this->container['osids'];
    }

    /**
     * Sets osids
     *
     * @param int[]|null $osids osids
     *
     * @return self
     */
    public function setOsids($osids)
    {
        if (is_null($osids)) {
            throw new \InvalidArgumentException('non-nullable osids cannot be null');
        }
        $this->container['osids'] = $osids;

        return $this;
    }

    /**
     * Gets browserids
     *
     * @return int[]|null
     */
    public function getBrowserids()
    {
        return $this->container['browserids'];
    }

    /**
     * Sets browserids
     *
     * @param int[]|null $browserids browserids
     *
     * @return self
     */
    public function setBrowserids($browserids)
    {
        if (is_null($browserids)) {
            throw new \InvalidArgumentException('non-nullable browserids cannot be null');
        }
        $this->container['browserids'] = $browserids;

        return $this;
    }

    /**
     * Gets carrierids
     *
     * @return int[]|null
     */
    public function getCarrierids()
    {
        return $this->container['carrierids'];
    }

    /**
     * Sets carrierids
     *
     * @param int[]|null $carrierids carrierids
     *
     * @return self
     */
    public function setCarrierids($carrierids)
    {
        if (is_null($carrierids)) {
            throw new \InvalidArgumentException('non-nullable carrierids cannot be null');
        }
        $this->container['carrierids'] = $carrierids;

        return $this;
    }

    /**
     * Gets ispids
     *
     * @return int[]|null
     */
    public function getIspids()
    {
        return $this->container['ispids'];
    }

    /**
     * Sets ispids
     *
     * @param int[]|null $ispids ispids
     *
     * @return self
     */
    public function setIspids($ispids)
    {
        if (is_null($ispids)) {
            throw new \InvalidArgumentException('non-nullable ispids cannot be null');
        }
        $this->container['ispids'] = $ispids;

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



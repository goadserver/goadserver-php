<?php
/**
 * AdspaceCreateScreenSettings
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
 * AdspaceCreateScreenSettings Class Doc Comment
 *
 * @category Class
 * @description DOOH screens (screens, adzone type 45): a digital-signage screen. The zone also needs its banner size (adsizeid / banner_settings.sizeid, e.g. 1920x1080). The screen&#39;s player pulls a playlist: GET https://&lt;ad domain&gt;/screen.go?spaceid&#x3D;&lt;zone&gt;&amp;key&#x3D;&lt;screen_key&gt;[&amp;n&#x3D;1-20][&amp;format&#x3D;vast] → {screen:{spaceid, width, height, orientation, venue_type}, open, refresh_seconds, ads:[{id, type: image|video|iframe|html, url, html, width, height, duration, title, proof_url}]} (format&#x3D;vast: VAST 4 with one Ad per item in play order, Impression &#x3D; proof_url; iframe/html items are left out). Fire proof_url when an ad has really played — proof of play, counted once per play and only within 3 hours of the pull; it books one impression whose cost is the CPM bid × audience_per_play. Outside opening_hours the playlist is empty (open: false). act&#x3D;player&amp;spaceid&#x3D;&amp;key&#x3D; is a ready full-screen web player for any browser in kiosk mode. Screens play only banner campaigns with options.screens.on (campaign field &#x60;screens&#x60;). screen_key is generated on create and returned read-only (also in GET /adspaces/{id}/code → screen); rotate_key: true issues a new one.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class AdspaceCreateScreenSettings implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'AdspaceCreate_screen_settings';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'venue_type' => 'string',
        'lat' => 'float',
        'lon' => 'float',
        'address' => 'string',
        'width' => 'int',
        'height' => 'int',
        'orientation' => 'string',
        'audience_per_play' => 'float',
        'opening_hours' => 'string',
        'timezone' => 'string',
        'slot_seconds' => 'int',
        'playlist_size' => 'int',
        'screen_key' => 'string',
        'rotate_key' => 'bool'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'venue_type' => null,
        'lat' => null,
        'lon' => null,
        'address' => null,
        'width' => null,
        'height' => null,
        'orientation' => null,
        'audience_per_play' => null,
        'opening_hours' => null,
        'timezone' => null,
        'slot_seconds' => null,
        'playlist_size' => null,
        'screen_key' => null,
        'rotate_key' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'venue_type' => false,
        'lat' => false,
        'lon' => false,
        'address' => false,
        'width' => false,
        'height' => false,
        'orientation' => false,
        'audience_per_play' => false,
        'opening_hours' => false,
        'timezone' => false,
        'slot_seconds' => false,
        'playlist_size' => false,
        'screen_key' => false,
        'rotate_key' => false
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
        'venue_type' => 'venue_type',
        'lat' => 'lat',
        'lon' => 'lon',
        'address' => 'address',
        'width' => 'width',
        'height' => 'height',
        'orientation' => 'orientation',
        'audience_per_play' => 'audience_per_play',
        'opening_hours' => 'opening_hours',
        'timezone' => 'timezone',
        'slot_seconds' => 'slot_seconds',
        'playlist_size' => 'playlist_size',
        'screen_key' => 'screen_key',
        'rotate_key' => 'rotate_key'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'venue_type' => 'setVenueType',
        'lat' => 'setLat',
        'lon' => 'setLon',
        'address' => 'setAddress',
        'width' => 'setWidth',
        'height' => 'setHeight',
        'orientation' => 'setOrientation',
        'audience_per_play' => 'setAudiencePerPlay',
        'opening_hours' => 'setOpeningHours',
        'timezone' => 'setTimezone',
        'slot_seconds' => 'setSlotSeconds',
        'playlist_size' => 'setPlaylistSize',
        'screen_key' => 'setScreenKey',
        'rotate_key' => 'setRotateKey'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'venue_type' => 'getVenueType',
        'lat' => 'getLat',
        'lon' => 'getLon',
        'address' => 'getAddress',
        'width' => 'getWidth',
        'height' => 'getHeight',
        'orientation' => 'getOrientation',
        'audience_per_play' => 'getAudiencePerPlay',
        'opening_hours' => 'getOpeningHours',
        'timezone' => 'getTimezone',
        'slot_seconds' => 'getSlotSeconds',
        'playlist_size' => 'getPlaylistSize',
        'screen_key' => 'getScreenKey',
        'rotate_key' => 'getRotateKey'
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

    public const VENUE_TYPE_RETAIL = 'retail';
    public const VENUE_TYPE_MALL = 'mall';
    public const VENUE_TYPE_SUPERMARKET = 'supermarket';
    public const VENUE_TYPE_RESTAURANT = 'restaurant';
    public const VENUE_TYPE_BAR = 'bar';
    public const VENUE_TYPE_HOTEL = 'hotel';
    public const VENUE_TYPE_GYM = 'gym';
    public const VENUE_TYPE_OFFICE = 'office';
    public const VENUE_TYPE_TRANSIT = 'transit';
    public const VENUE_TYPE_AIRPORT = 'airport';
    public const VENUE_TYPE_GAS_STATION = 'gas_station';
    public const VENUE_TYPE_PHARMACY = 'pharmacy';
    public const VENUE_TYPE_HOSPITAL = 'hospital';
    public const VENUE_TYPE_UNIVERSITY = 'university';
    public const VENUE_TYPE_CINEMA = 'cinema';
    public const VENUE_TYPE_OUTDOOR = 'outdoor';
    public const VENUE_TYPE_OTHER = 'other';
    public const ORIENTATION_LANDSCAPE = 'landscape';
    public const ORIENTATION_PORTRAIT = 'portrait';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getVenueTypeAllowableValues()
    {
        return [
            self::VENUE_TYPE_RETAIL,
            self::VENUE_TYPE_MALL,
            self::VENUE_TYPE_SUPERMARKET,
            self::VENUE_TYPE_RESTAURANT,
            self::VENUE_TYPE_BAR,
            self::VENUE_TYPE_HOTEL,
            self::VENUE_TYPE_GYM,
            self::VENUE_TYPE_OFFICE,
            self::VENUE_TYPE_TRANSIT,
            self::VENUE_TYPE_AIRPORT,
            self::VENUE_TYPE_GAS_STATION,
            self::VENUE_TYPE_PHARMACY,
            self::VENUE_TYPE_HOSPITAL,
            self::VENUE_TYPE_UNIVERSITY,
            self::VENUE_TYPE_CINEMA,
            self::VENUE_TYPE_OUTDOOR,
            self::VENUE_TYPE_OTHER,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getOrientationAllowableValues()
    {
        return [
            self::ORIENTATION_LANDSCAPE,
            self::ORIENTATION_PORTRAIT,
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
        $this->setIfExists('venue_type', $data ?? [], 'other');
        $this->setIfExists('lat', $data ?? [], 0);
        $this->setIfExists('lon', $data ?? [], 0);
        $this->setIfExists('address', $data ?? [], '');
        $this->setIfExists('width', $data ?? [], 1920);
        $this->setIfExists('height', $data ?? [], 1080);
        $this->setIfExists('orientation', $data ?? [], 'landscape');
        $this->setIfExists('audience_per_play', $data ?? [], 1);
        $this->setIfExists('opening_hours', $data ?? [], '');
        $this->setIfExists('timezone', $data ?? [], '');
        $this->setIfExists('slot_seconds', $data ?? [], 10);
        $this->setIfExists('playlist_size', $data ?? [], 6);
        $this->setIfExists('screen_key', $data ?? [], null);
        $this->setIfExists('rotate_key', $data ?? [], null);
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

        $allowedValues = $this->getVenueTypeAllowableValues();
        if (!is_null($this->container['venue_type']) && !in_array($this->container['venue_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'venue_type', must be one of '%s'",
                $this->container['venue_type'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['lat']) && ($this->container['lat'] > 90)) {
            $invalidProperties[] = "invalid value for 'lat', must be smaller than or equal to 90.";
        }

        if (!is_null($this->container['lat']) && ($this->container['lat'] < -90)) {
            $invalidProperties[] = "invalid value for 'lat', must be bigger than or equal to -90.";
        }

        if (!is_null($this->container['lon']) && ($this->container['lon'] > 180)) {
            $invalidProperties[] = "invalid value for 'lon', must be smaller than or equal to 180.";
        }

        if (!is_null($this->container['lon']) && ($this->container['lon'] < -180)) {
            $invalidProperties[] = "invalid value for 'lon', must be bigger than or equal to -180.";
        }

        if (!is_null($this->container['address']) && (mb_strlen($this->container['address']) > 200)) {
            $invalidProperties[] = "invalid value for 'address', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['width']) && ($this->container['width'] > 16384)) {
            $invalidProperties[] = "invalid value for 'width', must be smaller than or equal to 16384.";
        }

        if (!is_null($this->container['width']) && ($this->container['width'] < 0)) {
            $invalidProperties[] = "invalid value for 'width', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['height']) && ($this->container['height'] > 16384)) {
            $invalidProperties[] = "invalid value for 'height', must be smaller than or equal to 16384.";
        }

        if (!is_null($this->container['height']) && ($this->container['height'] < 0)) {
            $invalidProperties[] = "invalid value for 'height', must be bigger than or equal to 0.";
        }

        $allowedValues = $this->getOrientationAllowableValues();
        if (!is_null($this->container['orientation']) && !in_array($this->container['orientation'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'orientation', must be one of '%s'",
                $this->container['orientation'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['audience_per_play']) && ($this->container['audience_per_play'] > 10000)) {
            $invalidProperties[] = "invalid value for 'audience_per_play', must be smaller than or equal to 10000.";
        }

        if (!is_null($this->container['audience_per_play']) && ($this->container['audience_per_play'] < 1)) {
            $invalidProperties[] = "invalid value for 'audience_per_play', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['slot_seconds']) && ($this->container['slot_seconds'] > 120)) {
            $invalidProperties[] = "invalid value for 'slot_seconds', must be smaller than or equal to 120.";
        }

        if (!is_null($this->container['slot_seconds']) && ($this->container['slot_seconds'] < 5)) {
            $invalidProperties[] = "invalid value for 'slot_seconds', must be bigger than or equal to 5.";
        }

        if (!is_null($this->container['playlist_size']) && ($this->container['playlist_size'] > 20)) {
            $invalidProperties[] = "invalid value for 'playlist_size', must be smaller than or equal to 20.";
        }

        if (!is_null($this->container['playlist_size']) && ($this->container['playlist_size'] < 1)) {
            $invalidProperties[] = "invalid value for 'playlist_size', must be bigger than or equal to 1.";
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
     * Gets venue_type
     *
     * @return string|null
     */
    public function getVenueType()
    {
        return $this->container['venue_type'];
    }

    /**
     * Sets venue_type
     *
     * @param string|null $venue_type Where the screen hangs; campaigns can target venue types. Anything else is 422 `screen_settings.venue_type`.
     *
     * @return self
     */
    public function setVenueType($venue_type)
    {
        if (is_null($venue_type)) {
            throw new \InvalidArgumentException('non-nullable venue_type cannot be null');
        }
        $allowedValues = $this->getVenueTypeAllowableValues();
        if (!in_array($venue_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'venue_type', must be one of '%s'",
                    $venue_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['venue_type'] = $venue_type;

        return $this;
    }

    /**
     * Gets lat
     *
     * @return float|null
     */
    public function getLat()
    {
        return $this->container['lat'];
    }

    /**
     * Sets lat
     *
     * @param float|null $lat Latitude, for campaign geo-fences (out of range 422).
     *
     * @return self
     */
    public function setLat($lat)
    {
        if (is_null($lat)) {
            throw new \InvalidArgumentException('non-nullable lat cannot be null');
        }

        if (($lat > 90)) {
            throw new \InvalidArgumentException('invalid value for $lat when calling AdspaceCreateScreenSettings., must be smaller than or equal to 90.');
        }
        if (($lat < -90)) {
            throw new \InvalidArgumentException('invalid value for $lat when calling AdspaceCreateScreenSettings., must be bigger than or equal to -90.');
        }

        $this->container['lat'] = $lat;

        return $this;
    }

    /**
     * Gets lon
     *
     * @return float|null
     */
    public function getLon()
    {
        return $this->container['lon'];
    }

    /**
     * Sets lon
     *
     * @param float|null $lon Longitude (out of range 422).
     *
     * @return self
     */
    public function setLon($lon)
    {
        if (is_null($lon)) {
            throw new \InvalidArgumentException('non-nullable lon cannot be null');
        }

        if (($lon > 180)) {
            throw new \InvalidArgumentException('invalid value for $lon when calling AdspaceCreateScreenSettings., must be smaller than or equal to 180.');
        }
        if (($lon < -180)) {
            throw new \InvalidArgumentException('invalid value for $lon when calling AdspaceCreateScreenSettings., must be bigger than or equal to -180.');
        }

        $this->container['lon'] = $lon;

        return $this;
    }

    /**
     * Gets address
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->container['address'];
    }

    /**
     * Sets address
     *
     * @param string|null $address For your own reference; plain text.
     *
     * @return self
     */
    public function setAddress($address)
    {
        if (is_null($address)) {
            throw new \InvalidArgumentException('non-nullable address cannot be null');
        }
        if ((mb_strlen($address) > 200)) {
            throw new \InvalidArgumentException('invalid length for $address when calling AdspaceCreateScreenSettings., must be smaller than or equal to 200.');
        }

        $this->container['address'] = $address;

        return $this;
    }

    /**
     * Gets width
     *
     * @return int|null
     */
    public function getWidth()
    {
        return $this->container['width'];
    }

    /**
     * Sets width
     *
     * @param int|null $width Screen width in px (clamped).
     *
     * @return self
     */
    public function setWidth($width)
    {
        if (is_null($width)) {
            throw new \InvalidArgumentException('non-nullable width cannot be null');
        }

        if (($width > 16384)) {
            throw new \InvalidArgumentException('invalid value for $width when calling AdspaceCreateScreenSettings., must be smaller than or equal to 16384.');
        }
        if (($width < 0)) {
            throw new \InvalidArgumentException('invalid value for $width when calling AdspaceCreateScreenSettings., must be bigger than or equal to 0.');
        }

        $this->container['width'] = $width;

        return $this;
    }

    /**
     * Gets height
     *
     * @return int|null
     */
    public function getHeight()
    {
        return $this->container['height'];
    }

    /**
     * Sets height
     *
     * @param int|null $height Screen height in px (clamped).
     *
     * @return self
     */
    public function setHeight($height)
    {
        if (is_null($height)) {
            throw new \InvalidArgumentException('non-nullable height cannot be null');
        }

        if (($height > 16384)) {
            throw new \InvalidArgumentException('invalid value for $height when calling AdspaceCreateScreenSettings., must be smaller than or equal to 16384.');
        }
        if (($height < 0)) {
            throw new \InvalidArgumentException('invalid value for $height when calling AdspaceCreateScreenSettings., must be bigger than or equal to 0.');
        }

        $this->container['height'] = $height;

        return $this;
    }

    /**
     * Gets orientation
     *
     * @return string|null
     */
    public function getOrientation()
    {
        return $this->container['orientation'];
    }

    /**
     * Sets orientation
     *
     * @param string|null $orientation orientation
     *
     * @return self
     */
    public function setOrientation($orientation)
    {
        if (is_null($orientation)) {
            throw new \InvalidArgumentException('non-nullable orientation cannot be null');
        }
        $allowedValues = $this->getOrientationAllowableValues();
        if (!in_array($orientation, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'orientation', must be one of '%s'",
                    $orientation,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['orientation'] = $orientation;

        return $this;
    }

    /**
     * Gets audience_per_play
     *
     * @return float|null
     */
    public function getAudiencePerPlay()
    {
        return $this->container['audience_per_play'];
    }

    /**
     * Sets audience_per_play
     *
     * @param float|null $audience_per_play People who see one play (clamped). A proof of play costs the CPM bid × this.
     *
     * @return self
     */
    public function setAudiencePerPlay($audience_per_play)
    {
        if (is_null($audience_per_play)) {
            throw new \InvalidArgumentException('non-nullable audience_per_play cannot be null');
        }

        if (($audience_per_play > 10000)) {
            throw new \InvalidArgumentException('invalid value for $audience_per_play when calling AdspaceCreateScreenSettings., must be smaller than or equal to 10000.');
        }
        if (($audience_per_play < 1)) {
            throw new \InvalidArgumentException('invalid value for $audience_per_play when calling AdspaceCreateScreenSettings., must be bigger than or equal to 1.');
        }

        $this->container['audience_per_play'] = $audience_per_play;

        return $this;
    }

    /**
     * Gets opening_hours
     *
     * @return string|null
     */
    public function getOpeningHours()
    {
        return $this->container['opening_hours'];
    }

    /**
     * Sets opening_hours
     *
     * @param string|null $opening_hours Daily on-air window HH:MM-HH:MM in `timezone` (22:00-06:00 spans midnight); empty = always. Malformed 422 `screen_settings.opening_hours`.
     *
     * @return self
     */
    public function setOpeningHours($opening_hours)
    {
        if (is_null($opening_hours)) {
            throw new \InvalidArgumentException('non-nullable opening_hours cannot be null');
        }
        $this->container['opening_hours'] = $opening_hours;

        return $this;
    }

    /**
     * Gets timezone
     *
     * @return string|null
     */
    public function getTimezone()
    {
        return $this->container['timezone'];
    }

    /**
     * Sets timezone
     *
     * @param string|null $timezone IANA time zone for opening_hours; empty = the ad server's. Unknown 422.
     *
     * @return self
     */
    public function setTimezone($timezone)
    {
        if (is_null($timezone)) {
            throw new \InvalidArgumentException('non-nullable timezone cannot be null');
        }
        $this->container['timezone'] = $timezone;

        return $this;
    }

    /**
     * Gets slot_seconds
     *
     * @return int|null
     */
    public function getSlotSeconds()
    {
        return $this->container['slot_seconds'];
    }

    /**
     * Sets slot_seconds
     *
     * @param int|null $slot_seconds Seconds an image ad stays on screen (clamped); a video plays to its end.
     *
     * @return self
     */
    public function setSlotSeconds($slot_seconds)
    {
        if (is_null($slot_seconds)) {
            throw new \InvalidArgumentException('non-nullable slot_seconds cannot be null');
        }

        if (($slot_seconds > 120)) {
            throw new \InvalidArgumentException('invalid value for $slot_seconds when calling AdspaceCreateScreenSettings., must be smaller than or equal to 120.');
        }
        if (($slot_seconds < 5)) {
            throw new \InvalidArgumentException('invalid value for $slot_seconds when calling AdspaceCreateScreenSettings., must be bigger than or equal to 5.');
        }

        $this->container['slot_seconds'] = $slot_seconds;

        return $this;
    }

    /**
     * Gets playlist_size
     *
     * @return int|null
     */
    public function getPlaylistSize()
    {
        return $this->container['playlist_size'];
    }

    /**
     * Sets playlist_size
     *
     * @param int|null $playlist_size Ads per pull (clamped).
     *
     * @return self
     */
    public function setPlaylistSize($playlist_size)
    {
        if (is_null($playlist_size)) {
            throw new \InvalidArgumentException('non-nullable playlist_size cannot be null');
        }

        if (($playlist_size > 20)) {
            throw new \InvalidArgumentException('invalid value for $playlist_size when calling AdspaceCreateScreenSettings., must be smaller than or equal to 20.');
        }
        if (($playlist_size < 1)) {
            throw new \InvalidArgumentException('invalid value for $playlist_size when calling AdspaceCreateScreenSettings., must be bigger than or equal to 1.');
        }

        $this->container['playlist_size'] = $playlist_size;

        return $this;
    }

    /**
     * Gets screen_key
     *
     * @return string|null
     */
    public function getScreenKey()
    {
        return $this->container['screen_key'];
    }

    /**
     * Sets screen_key
     *
     * @param string|null $screen_key The key the player sends. Ignored on write.
     *
     * @return self
     */
    public function setScreenKey($screen_key)
    {
        if (is_null($screen_key)) {
            throw new \InvalidArgumentException('non-nullable screen_key cannot be null');
        }
        $this->container['screen_key'] = $screen_key;

        return $this;
    }

    /**
     * Gets rotate_key
     *
     * @return bool|null
     */
    public function getRotateKey()
    {
        return $this->container['rotate_key'];
    }

    /**
     * Sets rotate_key
     *
     * @param bool|null $rotate_key true issues a new screen_key (the current player stops).
     *
     * @return self
     */
    public function setRotateKey($rotate_key)
    {
        if (is_null($rotate_key)) {
            throw new \InvalidArgumentException('non-nullable rotate_key cannot be null');
        }
        $this->container['rotate_key'] = $rotate_key;

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



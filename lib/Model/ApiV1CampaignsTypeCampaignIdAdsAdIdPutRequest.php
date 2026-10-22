<?php
/**
 * ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest
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
 * ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_campaigns__type___campaignId__ads__adId__put_request';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'title' => 'string',
        'description' => 'string',
        'destinationurl' => 'string',
        'displayurl' => 'string',
        'previewurl' => 'string',
        'bid' => 'float',
        'isactive' => 'int',
        'sort_weight' => 'int',
        'position' => 'int',
        'displaytype' => 'string',
        'properties' => 'string',
        'url_poolid' => 'int',
        'creative_id' => 'int',
        'icon_id' => 'int',
        'use_imptracking' => 'bool',
        'imptrackingurl' => 'string',
        'usekeywords' => 'bool',
        'keywords' => 'string',
        'ad_options' => 'object',
        'smart_network_id' => 'int',
        'chat_followup' => 'string',
        'chat_price' => 'string',
        'keyword_mode' => 'string',
        'video_overlays' => '\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner[]',
        'creatives' => 'object',
        'banner_type' => 'string',
        'banner_url' => 'string',
        'banner_iframe_url' => 'string',
        'banner_html' => 'string',
        'banner_pool_id' => 'int',
        'image_type' => 'string',
        'image_url' => 'string',
        'image_pool_id' => 'int',
        'video_type' => 'string',
        'video_url' => 'string',
        'companion_url' => 'string',
        'companion_width' => 'int',
        'companion_height' => 'int',
        'video_pool_id' => 'int',
        'html_code' => 'string',
        'html_total_items' => 'int',
        'icon_type' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'title' => null,
        'description' => null,
        'destinationurl' => null,
        'displayurl' => null,
        'previewurl' => null,
        'bid' => null,
        'isactive' => null,
        'sort_weight' => null,
        'position' => null,
        'displaytype' => null,
        'properties' => null,
        'url_poolid' => null,
        'creative_id' => null,
        'icon_id' => null,
        'use_imptracking' => null,
        'imptrackingurl' => null,
        'usekeywords' => null,
        'keywords' => null,
        'ad_options' => null,
        'smart_network_id' => null,
        'chat_followup' => null,
        'chat_price' => null,
        'keyword_mode' => null,
        'video_overlays' => null,
        'creatives' => null,
        'banner_type' => null,
        'banner_url' => null,
        'banner_iframe_url' => null,
        'banner_html' => null,
        'banner_pool_id' => null,
        'image_type' => null,
        'image_url' => null,
        'image_pool_id' => null,
        'video_type' => null,
        'video_url' => null,
        'companion_url' => null,
        'companion_width' => null,
        'companion_height' => null,
        'video_pool_id' => null,
        'html_code' => null,
        'html_total_items' => null,
        'icon_type' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'title' => false,
        'description' => false,
        'destinationurl' => false,
        'displayurl' => false,
        'previewurl' => false,
        'bid' => false,
        'isactive' => false,
        'sort_weight' => false,
        'position' => false,
        'displaytype' => false,
        'properties' => false,
        'url_poolid' => false,
        'creative_id' => false,
        'icon_id' => false,
        'use_imptracking' => false,
        'imptrackingurl' => false,
        'usekeywords' => false,
        'keywords' => false,
        'ad_options' => false,
        'smart_network_id' => false,
        'chat_followup' => false,
        'chat_price' => false,
        'keyword_mode' => false,
        'video_overlays' => false,
        'creatives' => false,
        'banner_type' => false,
        'banner_url' => false,
        'banner_iframe_url' => false,
        'banner_html' => false,
        'banner_pool_id' => false,
        'image_type' => false,
        'image_url' => false,
        'image_pool_id' => false,
        'video_type' => false,
        'video_url' => false,
        'companion_url' => false,
        'companion_width' => false,
        'companion_height' => false,
        'video_pool_id' => false,
        'html_code' => false,
        'html_total_items' => false,
        'icon_type' => false
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
        'title' => 'title',
        'description' => 'description',
        'destinationurl' => 'destinationurl',
        'displayurl' => 'displayurl',
        'previewurl' => 'previewurl',
        'bid' => 'bid',
        'isactive' => 'isactive',
        'sort_weight' => 'sort_weight',
        'position' => 'position',
        'displaytype' => 'displaytype',
        'properties' => 'properties',
        'url_poolid' => 'url_poolid',
        'creative_id' => 'creative_id',
        'icon_id' => 'icon_id',
        'use_imptracking' => 'use_imptracking',
        'imptrackingurl' => 'imptrackingurl',
        'usekeywords' => 'usekeywords',
        'keywords' => 'keywords',
        'ad_options' => 'ad_options',
        'smart_network_id' => 'smart_network_id',
        'chat_followup' => 'chat_followup',
        'chat_price' => 'chat_price',
        'keyword_mode' => 'keyword_mode',
        'video_overlays' => 'video_overlays',
        'creatives' => 'creatives',
        'banner_type' => 'banner_type',
        'banner_url' => 'banner_url',
        'banner_iframe_url' => 'banner_iframe_url',
        'banner_html' => 'banner_html',
        'banner_pool_id' => 'banner_pool_id',
        'image_type' => 'image_type',
        'image_url' => 'image_url',
        'image_pool_id' => 'image_pool_id',
        'video_type' => 'video_type',
        'video_url' => 'video_url',
        'companion_url' => 'companion_url',
        'companion_width' => 'companion_width',
        'companion_height' => 'companion_height',
        'video_pool_id' => 'video_pool_id',
        'html_code' => 'html_code',
        'html_total_items' => 'html_total_items',
        'icon_type' => 'icon_type'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'title' => 'setTitle',
        'description' => 'setDescription',
        'destinationurl' => 'setDestinationurl',
        'displayurl' => 'setDisplayurl',
        'previewurl' => 'setPreviewurl',
        'bid' => 'setBid',
        'isactive' => 'setIsactive',
        'sort_weight' => 'setSortWeight',
        'position' => 'setPosition',
        'displaytype' => 'setDisplaytype',
        'properties' => 'setProperties',
        'url_poolid' => 'setUrlPoolid',
        'creative_id' => 'setCreativeId',
        'icon_id' => 'setIconId',
        'use_imptracking' => 'setUseImptracking',
        'imptrackingurl' => 'setImptrackingurl',
        'usekeywords' => 'setUsekeywords',
        'keywords' => 'setKeywords',
        'ad_options' => 'setAdOptions',
        'smart_network_id' => 'setSmartNetworkId',
        'chat_followup' => 'setChatFollowup',
        'chat_price' => 'setChatPrice',
        'keyword_mode' => 'setKeywordMode',
        'video_overlays' => 'setVideoOverlays',
        'creatives' => 'setCreatives',
        'banner_type' => 'setBannerType',
        'banner_url' => 'setBannerUrl',
        'banner_iframe_url' => 'setBannerIframeUrl',
        'banner_html' => 'setBannerHtml',
        'banner_pool_id' => 'setBannerPoolId',
        'image_type' => 'setImageType',
        'image_url' => 'setImageUrl',
        'image_pool_id' => 'setImagePoolId',
        'video_type' => 'setVideoType',
        'video_url' => 'setVideoUrl',
        'companion_url' => 'setCompanionUrl',
        'companion_width' => 'setCompanionWidth',
        'companion_height' => 'setCompanionHeight',
        'video_pool_id' => 'setVideoPoolId',
        'html_code' => 'setHtmlCode',
        'html_total_items' => 'setHtmlTotalItems',
        'icon_type' => 'setIconType'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'title' => 'getTitle',
        'description' => 'getDescription',
        'destinationurl' => 'getDestinationurl',
        'displayurl' => 'getDisplayurl',
        'previewurl' => 'getPreviewurl',
        'bid' => 'getBid',
        'isactive' => 'getIsactive',
        'sort_weight' => 'getSortWeight',
        'position' => 'getPosition',
        'displaytype' => 'getDisplaytype',
        'properties' => 'getProperties',
        'url_poolid' => 'getUrlPoolid',
        'creative_id' => 'getCreativeId',
        'icon_id' => 'getIconId',
        'use_imptracking' => 'getUseImptracking',
        'imptrackingurl' => 'getImptrackingurl',
        'usekeywords' => 'getUsekeywords',
        'keywords' => 'getKeywords',
        'ad_options' => 'getAdOptions',
        'smart_network_id' => 'getSmartNetworkId',
        'chat_followup' => 'getChatFollowup',
        'chat_price' => 'getChatPrice',
        'keyword_mode' => 'getKeywordMode',
        'video_overlays' => 'getVideoOverlays',
        'creatives' => 'getCreatives',
        'banner_type' => 'getBannerType',
        'banner_url' => 'getBannerUrl',
        'banner_iframe_url' => 'getBannerIframeUrl',
        'banner_html' => 'getBannerHtml',
        'banner_pool_id' => 'getBannerPoolId',
        'image_type' => 'getImageType',
        'image_url' => 'getImageUrl',
        'image_pool_id' => 'getImagePoolId',
        'video_type' => 'getVideoType',
        'video_url' => 'getVideoUrl',
        'companion_url' => 'getCompanionUrl',
        'companion_width' => 'getCompanionWidth',
        'companion_height' => 'getCompanionHeight',
        'video_pool_id' => 'getVideoPoolId',
        'html_code' => 'getHtmlCode',
        'html_total_items' => 'getHtmlTotalItems',
        'icon_type' => 'getIconType'
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

    public const ISACTIVE_NUMBER_0 = 0;
    public const ISACTIVE_NUMBER_1 = 1;
    public const KEYWORD_MODE_SPECIFIC = 'specific';
    public const KEYWORD_MODE_PREFERENCE = 'preference';
    public const BANNER_TYPE_CREATIVE = 'creative';
    public const BANNER_TYPE_POOL = 'pool';
    public const BANNER_TYPE_HOTLINK_IMAGE = 'hotlink_image';
    public const BANNER_TYPE_HOTLINK_VIDEO = 'hotlink_video';
    public const BANNER_TYPE_IFRAME = 'iframe';
    public const BANNER_TYPE_HTML = 'html';
    public const IMAGE_TYPE_CREATIVE = 'creative';
    public const IMAGE_TYPE_POOL = 'pool';
    public const IMAGE_TYPE_HTML = 'html';
    public const IMAGE_TYPE_NONE = 'none';
    public const VIDEO_TYPE_URL = 'url';
    public const VIDEO_TYPE_XML = 'xml';
    public const VIDEO_TYPE_HTML = 'html';
    public const VIDEO_TYPE_CREATIVE = 'creative';
    public const VIDEO_TYPE_POOL = 'pool';
    public const ICON_TYPE_CREATIVE = 'creative';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getIsactiveAllowableValues()
    {
        return [
            self::ISACTIVE_NUMBER_0,
            self::ISACTIVE_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getKeywordModeAllowableValues()
    {
        return [
            self::KEYWORD_MODE_SPECIFIC,
            self::KEYWORD_MODE_PREFERENCE,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getBannerTypeAllowableValues()
    {
        return [
            self::BANNER_TYPE_CREATIVE,
            self::BANNER_TYPE_POOL,
            self::BANNER_TYPE_HOTLINK_IMAGE,
            self::BANNER_TYPE_HOTLINK_VIDEO,
            self::BANNER_TYPE_IFRAME,
            self::BANNER_TYPE_HTML,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getImageTypeAllowableValues()
    {
        return [
            self::IMAGE_TYPE_CREATIVE,
            self::IMAGE_TYPE_POOL,
            self::IMAGE_TYPE_HTML,
            self::IMAGE_TYPE_NONE,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getVideoTypeAllowableValues()
    {
        return [
            self::VIDEO_TYPE_URL,
            self::VIDEO_TYPE_XML,
            self::VIDEO_TYPE_HTML,
            self::VIDEO_TYPE_CREATIVE,
            self::VIDEO_TYPE_POOL,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getIconTypeAllowableValues()
    {
        return [
            self::ICON_TYPE_CREATIVE,
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
        $this->setIfExists('title', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('destinationurl', $data ?? [], null);
        $this->setIfExists('displayurl', $data ?? [], null);
        $this->setIfExists('previewurl', $data ?? [], null);
        $this->setIfExists('bid', $data ?? [], null);
        $this->setIfExists('isactive', $data ?? [], null);
        $this->setIfExists('sort_weight', $data ?? [], null);
        $this->setIfExists('position', $data ?? [], null);
        $this->setIfExists('displaytype', $data ?? [], null);
        $this->setIfExists('properties', $data ?? [], null);
        $this->setIfExists('url_poolid', $data ?? [], null);
        $this->setIfExists('creative_id', $data ?? [], null);
        $this->setIfExists('icon_id', $data ?? [], null);
        $this->setIfExists('use_imptracking', $data ?? [], null);
        $this->setIfExists('imptrackingurl', $data ?? [], null);
        $this->setIfExists('usekeywords', $data ?? [], null);
        $this->setIfExists('keywords', $data ?? [], null);
        $this->setIfExists('ad_options', $data ?? [], null);
        $this->setIfExists('smart_network_id', $data ?? [], null);
        $this->setIfExists('chat_followup', $data ?? [], null);
        $this->setIfExists('chat_price', $data ?? [], null);
        $this->setIfExists('keyword_mode', $data ?? [], 'specific');
        $this->setIfExists('video_overlays', $data ?? [], null);
        $this->setIfExists('creatives', $data ?? [], null);
        $this->setIfExists('banner_type', $data ?? [], null);
        $this->setIfExists('banner_url', $data ?? [], null);
        $this->setIfExists('banner_iframe_url', $data ?? [], null);
        $this->setIfExists('banner_html', $data ?? [], null);
        $this->setIfExists('banner_pool_id', $data ?? [], null);
        $this->setIfExists('image_type', $data ?? [], null);
        $this->setIfExists('image_url', $data ?? [], null);
        $this->setIfExists('image_pool_id', $data ?? [], null);
        $this->setIfExists('video_type', $data ?? [], null);
        $this->setIfExists('video_url', $data ?? [], null);
        $this->setIfExists('companion_url', $data ?? [], null);
        $this->setIfExists('companion_width', $data ?? [], 300);
        $this->setIfExists('companion_height', $data ?? [], 250);
        $this->setIfExists('video_pool_id', $data ?? [], null);
        $this->setIfExists('html_code', $data ?? [], null);
        $this->setIfExists('html_total_items', $data ?? [], null);
        $this->setIfExists('icon_type', $data ?? [], null);
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

        $allowedValues = $this->getIsactiveAllowableValues();
        if (!is_null($this->container['isactive']) && !in_array($this->container['isactive'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'isactive', must be one of '%s'",
                $this->container['isactive'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['chat_followup']) && (mb_strlen($this->container['chat_followup']) > 80)) {
            $invalidProperties[] = "invalid value for 'chat_followup', the character length must be smaller than or equal to 80.";
        }

        if (!is_null($this->container['chat_price']) && (mb_strlen($this->container['chat_price']) > 24)) {
            $invalidProperties[] = "invalid value for 'chat_price', the character length must be smaller than or equal to 24.";
        }

        $allowedValues = $this->getKeywordModeAllowableValues();
        if (!is_null($this->container['keyword_mode']) && !in_array($this->container['keyword_mode'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'keyword_mode', must be one of '%s'",
                $this->container['keyword_mode'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['video_overlays']) && (count($this->container['video_overlays']) > 8)) {
            $invalidProperties[] = "invalid value for 'video_overlays', number of items must be less than or equal to 8.";
        }

        $allowedValues = $this->getBannerTypeAllowableValues();
        if (!is_null($this->container['banner_type']) && !in_array($this->container['banner_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'banner_type', must be one of '%s'",
                $this->container['banner_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getImageTypeAllowableValues();
        if (!is_null($this->container['image_type']) && !in_array($this->container['image_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'image_type', must be one of '%s'",
                $this->container['image_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getVideoTypeAllowableValues();
        if (!is_null($this->container['video_type']) && !in_array($this->container['video_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'video_type', must be one of '%s'",
                $this->container['video_type'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getIconTypeAllowableValues();
        if (!is_null($this->container['icon_type']) && !in_array($this->container['icon_type'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'icon_type', must be one of '%s'",
                $this->container['icon_type'],
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
     * Gets title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->container['title'];
    }

    /**
     * Sets title
     *
     * @param string|null $title title
     *
     * @return self
     */
    public function setTitle($title)
    {
        if (is_null($title)) {
            throw new \InvalidArgumentException('non-nullable title cannot be null');
        }
        $this->container['title'] = $title;

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
     * Gets destinationurl
     *
     * @return string|null
     */
    public function getDestinationurl()
    {
        return $this->container['destinationurl'];
    }

    /**
     * Sets destinationurl
     *
     * @param string|null $destinationurl Click URL. Alias: destination_url.
     *
     * @return self
     */
    public function setDestinationurl($destinationurl)
    {
        if (is_null($destinationurl)) {
            throw new \InvalidArgumentException('non-nullable destinationurl cannot be null');
        }
        $this->container['destinationurl'] = $destinationurl;

        return $this;
    }

    /**
     * Gets displayurl
     *
     * @return string|null
     */
    public function getDisplayurl()
    {
        return $this->container['displayurl'];
    }

    /**
     * Sets displayurl
     *
     * @param string|null $displayurl Display URL (required content for native / push / offer ads). Alias: display_url.
     *
     * @return self
     */
    public function setDisplayurl($displayurl)
    {
        if (is_null($displayurl)) {
            throw new \InvalidArgumentException('non-nullable displayurl cannot be null');
        }
        $this->container['displayurl'] = $displayurl;

        return $this;
    }

    /**
     * Gets previewurl
     *
     * @return string|null
     */
    public function getPreviewurl()
    {
        return $this->container['previewurl'];
    }

    /**
     * Sets previewurl
     *
     * @param string|null $previewurl Alias: preview_url.
     *
     * @return self
     */
    public function setPreviewurl($previewurl)
    {
        if (is_null($previewurl)) {
            throw new \InvalidArgumentException('non-nullable previewurl cannot be null');
        }
        $this->container['previewurl'] = $previewurl;

        return $this;
    }

    /**
     * Gets bid
     *
     * @return float|null
     */
    public function getBid()
    {
        return $this->container['bid'];
    }

    /**
     * Sets bid
     *
     * @param float|null $bid Currency units of your account currency, like the panel (see the bid endpoint for the floor and the bid_unit contract).
     *
     * @return self
     */
    public function setBid($bid)
    {
        if (is_null($bid)) {
            throw new \InvalidArgumentException('non-nullable bid cannot be null');
        }
        $this->container['bid'] = $bid;

        return $this;
    }

    /**
     * Gets isactive
     *
     * @return int|null
     */
    public function getIsactive()
    {
        return $this->container['isactive'];
    }

    /**
     * Sets isactive
     *
     * @param int|null $isactive Alias: is_active (boolean accepted). Prefer PATCH …/active, which also runs the publisher-approval and notification side effects.
     *
     * @return self
     */
    public function setIsactive($isactive)
    {
        if (is_null($isactive)) {
            throw new \InvalidArgumentException('non-nullable isactive cannot be null');
        }
        $allowedValues = $this->getIsactiveAllowableValues();
        if (!in_array($isactive, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'isactive', must be one of '%s'",
                    $isactive,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['isactive'] = $isactive;

        return $this;
    }

    /**
     * Gets sort_weight
     *
     * @return int|null
     */
    public function getSortWeight()
    {
        return $this->container['sort_weight'];
    }

    /**
     * Sets sort_weight
     *
     * @param int|null $sort_weight sort_weight
     *
     * @return self
     */
    public function setSortWeight($sort_weight)
    {
        if (is_null($sort_weight)) {
            throw new \InvalidArgumentException('non-nullable sort_weight cannot be null');
        }
        $this->container['sort_weight'] = $sort_weight;

        return $this;
    }

    /**
     * Gets position
     *
     * @return int|null
     */
    public function getPosition()
    {
        return $this->container['position'];
    }

    /**
     * Sets position
     *
     * @param int|null $position position
     *
     * @return self
     */
    public function setPosition($position)
    {
        if (is_null($position)) {
            throw new \InvalidArgumentException('non-nullable position cannot be null');
        }
        $this->container['position'] = $position;

        return $this;
    }

    /**
     * Gets displaytype
     *
     * @return string|null
     */
    public function getDisplaytype()
    {
        return $this->container['displaytype'];
    }

    /**
     * Sets displaytype
     *
     * @param string|null $displaytype Video ads: comma-separated placements (1 pre-roll, 2 mid-roll, 3 post-roll, 4 in-video slider, 5 interstitial, 6 in-page, 7 video carousel); \"0\" = all.
     *
     * @return self
     */
    public function setDisplaytype($displaytype)
    {
        if (is_null($displaytype)) {
            throw new \InvalidArgumentException('non-nullable displaytype cannot be null');
        }
        $this->container['displaytype'] = $displaytype;

        return $this;
    }

    /**
     * Gets properties
     *
     * @return string|null
     */
    public function getProperties()
    {
        return $this->container['properties'];
    }

    /**
     * Sets properties
     *
     * @param string|null $properties properties
     *
     * @return self
     */
    public function setProperties($properties)
    {
        if (is_null($properties)) {
            throw new \InvalidArgumentException('non-nullable properties cannot be null');
        }
        $this->container['properties'] = $properties;

        return $this;
    }

    /**
     * Gets url_poolid
     *
     * @return int|null
     */
    public function getUrlPoolid()
    {
        return $this->container['url_poolid'];
    }

    /**
     * Sets url_poolid
     *
     * @param int|null $url_poolid Click-URL rotation pool (0 = none). Alias: url_pool_id.
     *
     * @return self
     */
    public function setUrlPoolid($url_poolid)
    {
        if (is_null($url_poolid)) {
            throw new \InvalidArgumentException('non-nullable url_poolid cannot be null');
        }
        $this->container['url_poolid'] = $url_poolid;

        return $this;
    }

    /**
     * Gets creative_id
     *
     * @return int|null
     */
    public function getCreativeId()
    {
        return $this->container['creative_id'];
    }

    /**
     * Sets creative_id
     *
     * @param int|null $creative_id creative_id
     *
     * @return self
     */
    public function setCreativeId($creative_id)
    {
        if (is_null($creative_id)) {
            throw new \InvalidArgumentException('non-nullable creative_id cannot be null');
        }
        $this->container['creative_id'] = $creative_id;

        return $this;
    }

    /**
     * Gets icon_id
     *
     * @return int|null
     */
    public function getIconId()
    {
        return $this->container['icon_id'];
    }

    /**
     * Sets icon_id
     *
     * @param int|null $icon_id Push icon creative (with icon_type=creative).
     *
     * @return self
     */
    public function setIconId($icon_id)
    {
        if (is_null($icon_id)) {
            throw new \InvalidArgumentException('non-nullable icon_id cannot be null');
        }
        $this->container['icon_id'] = $icon_id;

        return $this;
    }

    /**
     * Gets use_imptracking
     *
     * @return bool|null
     */
    public function getUseImptracking()
    {
        return $this->container['use_imptracking'];
    }

    /**
     * Sets use_imptracking
     *
     * @param bool|null $use_imptracking use_imptracking
     *
     * @return self
     */
    public function setUseImptracking($use_imptracking)
    {
        if (is_null($use_imptracking)) {
            throw new \InvalidArgumentException('non-nullable use_imptracking cannot be null');
        }
        $this->container['use_imptracking'] = $use_imptracking;

        return $this;
    }

    /**
     * Gets imptrackingurl
     *
     * @return string|null
     */
    public function getImptrackingurl()
    {
        return $this->container['imptrackingurl'];
    }

    /**
     * Sets imptrackingurl
     *
     * @param string|null $imptrackingurl imptrackingurl
     *
     * @return self
     */
    public function setImptrackingurl($imptrackingurl)
    {
        if (is_null($imptrackingurl)) {
            throw new \InvalidArgumentException('non-nullable imptrackingurl cannot be null');
        }
        $this->container['imptrackingurl'] = $imptrackingurl;

        return $this;
    }

    /**
     * Gets usekeywords
     *
     * @return bool|null
     */
    public function getUsekeywords()
    {
        return $this->container['usekeywords'];
    }

    /**
     * Sets usekeywords
     *
     * @param bool|null $usekeywords usekeywords
     *
     * @return self
     */
    public function setUsekeywords($usekeywords)
    {
        if (is_null($usekeywords)) {
            throw new \InvalidArgumentException('non-nullable usekeywords cannot be null');
        }
        $this->container['usekeywords'] = $usekeywords;

        return $this;
    }

    /**
     * Gets keywords
     *
     * @return string|null
     */
    public function getKeywords()
    {
        return $this->container['keywords'];
    }

    /**
     * Sets keywords
     *
     * @param string|null $keywords Comma- or newline-separated. Trimmed, lowercased and deduplicated on save.
     *
     * @return self
     */
    public function setKeywords($keywords)
    {
        if (is_null($keywords)) {
            throw new \InvalidArgumentException('non-nullable keywords cannot be null');
        }
        $this->container['keywords'] = $keywords;

        return $this;
    }

    /**
     * Gets ad_options
     *
     * @return object|null
     */
    public function getAdOptions()
    {
        return $this->container['ad_options'];
    }

    /**
     * Sets ad_options
     *
     * @param object|null $ad_options SmartAds `{fixed_position, smart_options}`; `chat` {followup, price} for AI chat ads (set those with chat_followup / chat_price); `keyword_preference` for Search Ads (set with keyword_mode).
     *
     * @return self
     */
    public function setAdOptions($ad_options)
    {
        if (is_null($ad_options)) {
            throw new \InvalidArgumentException('non-nullable ad_options cannot be null');
        }
        $this->container['ad_options'] = $ad_options;

        return $this;
    }

    /**
     * Gets smart_network_id
     *
     * @return int|null
     */
    public function getSmartNetworkId()
    {
        return $this->container['smart_network_id'];
    }

    /**
     * Sets smart_network_id
     *
     * @param int|null $smart_network_id smart_network_id
     *
     * @return self
     */
    public function setSmartNetworkId($smart_network_id)
    {
        if (is_null($smart_network_id)) {
            throw new \InvalidArgumentException('non-nullable smart_network_id cannot be null');
        }
        $this->container['smart_network_id'] = $smart_network_id;

        return $this;
    }

    /**
     * Gets chat_followup
     *
     * @return string|null
     */
    public function getChatFollowup()
    {
        return $this->container['chat_followup'];
    }

    /**
     * Sets chat_followup
     *
     * @param string|null $chat_followup Search Ads (adtype 42) ron ads: a sponsored follow-up question for AI chat zones (chat_followups > 0); picking it asks the chat that question and counts as a click. Stored in ad_options.chat.followup (merged — other ad_options keys are kept); empty string clears.
     *
     * @return self
     */
    public function setChatFollowup($chat_followup)
    {
        if (is_null($chat_followup)) {
            throw new \InvalidArgumentException('non-nullable chat_followup cannot be null');
        }
        if ((mb_strlen($chat_followup) > 80)) {
            throw new \InvalidArgumentException('invalid length for $chat_followup when calling ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest., must be smaller than or equal to 80.');
        }

        $this->container['chat_followup'] = $chat_followup;

        return $this;
    }

    /**
     * Gets chat_price
     *
     * @return string|null
     */
    public function getChatPrice()
    {
        return $this->container['chat_price'];
    }

    /**
     * Sets chat_price
     *
     * @param string|null $chat_price Search Ads ron ads: the price on AI chat product cards (chat_style products). Stored in ad_options.chat.price; product feeds fill it.
     *
     * @return self
     */
    public function setChatPrice($chat_price)
    {
        if (is_null($chat_price)) {
            throw new \InvalidArgumentException('non-nullable chat_price cannot be null');
        }
        if ((mb_strlen($chat_price) > 24)) {
            throw new \InvalidArgumentException('invalid length for $chat_price when calling ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest., must be smaller than or equal to 24.');
        }

        $this->container['chat_price'] = $chat_price;

        return $this;
    }

    /**
     * Gets keyword_mode
     *
     * @return string|null
     */
    public function getKeywordMode()
    {
        return $this->container['keyword_mode'];
    }

    /**
     * Sets keyword_mode
     *
     * @param string|null $keyword_mode Search Ads (adtype 42) ron ads: how the ad's own keywords (usekeywords + keywords) act on a search. specific = shown only when a keyword matches the search; preference = shown for any search and ranked first when a keyword matches, at most 6 keywords (422 on keywords otherwise). Stored as ad_options.keyword_preference (true, or absent = specific); other ad_options keys are kept. Other zone types always treat ad keywords as specific.
     *
     * @return self
     */
    public function setKeywordMode($keyword_mode)
    {
        if (is_null($keyword_mode)) {
            throw new \InvalidArgumentException('non-nullable keyword_mode cannot be null');
        }
        $allowedValues = $this->getKeywordModeAllowableValues();
        if (!in_array($keyword_mode, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'keyword_mode', must be one of '%s'",
                    $keyword_mode,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['keyword_mode'] = $keyword_mode;

        return $this;
    }

    /**
     * Gets video_overlays
     *
     * @return \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner[]|null
     */
    public function getVideoOverlays()
    {
        return $this->container['video_overlays'];
    }

    /**
     * Sets video_overlays
     *
     * @param \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner[]|null $video_overlays Shoppable video (ron video ads, adtype 4 / 20): timed hotspots and product cards over the ad. A click counts as the ad's click and lands on the overlay's url (empty = the ad's destination). Served to goCtrl's video players and in VAST (Extension goadserver-overlays + a SIMID InteractiveCreativeFile, VAST 4.1). Stored in ad_options.overlays (merged — other ad_options keys are kept); [] clears. Per-overlay shown / clicks: GET /campaigns/{type}/{campaignId}/ads/{adId}/overlay-stats.
     *
     * @return self
     */
    public function setVideoOverlays($video_overlays)
    {
        if (is_null($video_overlays)) {
            throw new \InvalidArgumentException('non-nullable video_overlays cannot be null');
        }

        if ((count($video_overlays) > 8)) {
            throw new \InvalidArgumentException('invalid value for $video_overlays when calling ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest., number of items must be less than or equal to 8.');
        }
        $this->container['video_overlays'] = $video_overlays;

        return $this;
    }

    /**
     * Gets creatives
     *
     * @return object|null
     */
    public function getCreatives()
    {
        return $this->container['creatives'];
    }

    /**
     * Sets creatives
     *
     * @param object|null $creatives The creative blob verbatim (object or JSON string).
     *
     * @return self
     */
    public function setCreatives($creatives)
    {
        if (is_null($creatives)) {
            throw new \InvalidArgumentException('non-nullable creatives cannot be null');
        }
        $this->container['creatives'] = $creatives;

        return $this;
    }

    /**
     * Gets banner_type
     *
     * @return string|null
     */
    public function getBannerType()
    {
        return $this->container['banner_type'];
    }

    /**
     * Sets banner_type
     *
     * @param string|null $banner_type banner_type
     *
     * @return self
     */
    public function setBannerType($banner_type)
    {
        if (is_null($banner_type)) {
            throw new \InvalidArgumentException('non-nullable banner_type cannot be null');
        }
        $allowedValues = $this->getBannerTypeAllowableValues();
        if (!in_array($banner_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'banner_type', must be one of '%s'",
                    $banner_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['banner_type'] = $banner_type;

        return $this;
    }

    /**
     * Gets banner_url
     *
     * @return string|null
     */
    public function getBannerUrl()
    {
        return $this->container['banner_url'];
    }

    /**
     * Sets banner_url
     *
     * @param string|null $banner_url banner_url
     *
     * @return self
     */
    public function setBannerUrl($banner_url)
    {
        if (is_null($banner_url)) {
            throw new \InvalidArgumentException('non-nullable banner_url cannot be null');
        }
        $this->container['banner_url'] = $banner_url;

        return $this;
    }

    /**
     * Gets banner_iframe_url
     *
     * @return string|null
     */
    public function getBannerIframeUrl()
    {
        return $this->container['banner_iframe_url'];
    }

    /**
     * Sets banner_iframe_url
     *
     * @param string|null $banner_iframe_url banner_iframe_url
     *
     * @return self
     */
    public function setBannerIframeUrl($banner_iframe_url)
    {
        if (is_null($banner_iframe_url)) {
            throw new \InvalidArgumentException('non-nullable banner_iframe_url cannot be null');
        }
        $this->container['banner_iframe_url'] = $banner_iframe_url;

        return $this;
    }

    /**
     * Gets banner_html
     *
     * @return string|null
     */
    public function getBannerHtml()
    {
        return $this->container['banner_html'];
    }

    /**
     * Sets banner_html
     *
     * @param string|null $banner_html banner_html
     *
     * @return self
     */
    public function setBannerHtml($banner_html)
    {
        if (is_null($banner_html)) {
            throw new \InvalidArgumentException('non-nullable banner_html cannot be null');
        }
        $this->container['banner_html'] = $banner_html;

        return $this;
    }

    /**
     * Gets banner_pool_id
     *
     * @return int|null
     */
    public function getBannerPoolId()
    {
        return $this->container['banner_pool_id'];
    }

    /**
     * Sets banner_pool_id
     *
     * @param int|null $banner_pool_id banner_pool_id
     *
     * @return self
     */
    public function setBannerPoolId($banner_pool_id)
    {
        if (is_null($banner_pool_id)) {
            throw new \InvalidArgumentException('non-nullable banner_pool_id cannot be null');
        }
        $this->container['banner_pool_id'] = $banner_pool_id;

        return $this;
    }

    /**
     * Gets image_type
     *
     * @return string|null
     */
    public function getImageType()
    {
        return $this->container['image_type'];
    }

    /**
     * Sets image_type
     *
     * @param string|null $image_type image_type
     *
     * @return self
     */
    public function setImageType($image_type)
    {
        if (is_null($image_type)) {
            throw new \InvalidArgumentException('non-nullable image_type cannot be null');
        }
        $allowedValues = $this->getImageTypeAllowableValues();
        if (!in_array($image_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'image_type', must be one of '%s'",
                    $image_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['image_type'] = $image_type;

        return $this;
    }

    /**
     * Gets image_url
     *
     * @return string|null
     */
    public function getImageUrl()
    {
        return $this->container['image_url'];
    }

    /**
     * Sets image_url
     *
     * @param string|null $image_url image_url
     *
     * @return self
     */
    public function setImageUrl($image_url)
    {
        if (is_null($image_url)) {
            throw new \InvalidArgumentException('non-nullable image_url cannot be null');
        }
        $this->container['image_url'] = $image_url;

        return $this;
    }

    /**
     * Gets image_pool_id
     *
     * @return int|null
     */
    public function getImagePoolId()
    {
        return $this->container['image_pool_id'];
    }

    /**
     * Sets image_pool_id
     *
     * @param int|null $image_pool_id image_pool_id
     *
     * @return self
     */
    public function setImagePoolId($image_pool_id)
    {
        if (is_null($image_pool_id)) {
            throw new \InvalidArgumentException('non-nullable image_pool_id cannot be null');
        }
        $this->container['image_pool_id'] = $image_pool_id;

        return $this;
    }

    /**
     * Gets video_type
     *
     * @return string|null
     */
    public function getVideoType()
    {
        return $this->container['video_type'];
    }

    /**
     * Sets video_type
     *
     * @param string|null $video_type video_type
     *
     * @return self
     */
    public function setVideoType($video_type)
    {
        if (is_null($video_type)) {
            throw new \InvalidArgumentException('non-nullable video_type cannot be null');
        }
        $allowedValues = $this->getVideoTypeAllowableValues();
        if (!in_array($video_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'video_type', must be one of '%s'",
                    $video_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['video_type'] = $video_type;

        return $this;
    }

    /**
     * Gets video_url
     *
     * @return string|null
     */
    public function getVideoUrl()
    {
        return $this->container['video_url'];
    }

    /**
     * Sets video_url
     *
     * @param string|null $video_url video_url
     *
     * @return self
     */
    public function setVideoUrl($video_url)
    {
        if (is_null($video_url)) {
            throw new \InvalidArgumentException('non-nullable video_url cannot be null');
        }
        $this->container['video_url'] = $video_url;

        return $this;
    }

    /**
     * Gets companion_url
     *
     * @return string|null
     */
    public function getCompanionUrl()
    {
        return $this->container['companion_url'];
    }

    /**
     * Sets companion_url
     *
     * @param string|null $companion_url Audio ads (adtype 43): an https companion image shown while the ad plays (VAST CompanionAds; the goCtrl audio plugin's bar). Stored as creatives.image {type: url}.
     *
     * @return self
     */
    public function setCompanionUrl($companion_url)
    {
        if (is_null($companion_url)) {
            throw new \InvalidArgumentException('non-nullable companion_url cannot be null');
        }
        $this->container['companion_url'] = $companion_url;

        return $this;
    }

    /**
     * Gets companion_width
     *
     * @return int|null
     */
    public function getCompanionWidth()
    {
        return $this->container['companion_width'];
    }

    /**
     * Sets companion_width
     *
     * @param int|null $companion_width companion_width
     *
     * @return self
     */
    public function setCompanionWidth($companion_width)
    {
        if (is_null($companion_width)) {
            throw new \InvalidArgumentException('non-nullable companion_width cannot be null');
        }
        $this->container['companion_width'] = $companion_width;

        return $this;
    }

    /**
     * Gets companion_height
     *
     * @return int|null
     */
    public function getCompanionHeight()
    {
        return $this->container['companion_height'];
    }

    /**
     * Sets companion_height
     *
     * @param int|null $companion_height companion_height
     *
     * @return self
     */
    public function setCompanionHeight($companion_height)
    {
        if (is_null($companion_height)) {
            throw new \InvalidArgumentException('non-nullable companion_height cannot be null');
        }
        $this->container['companion_height'] = $companion_height;

        return $this;
    }

    /**
     * Gets video_pool_id
     *
     * @return int|null
     */
    public function getVideoPoolId()
    {
        return $this->container['video_pool_id'];
    }

    /**
     * Sets video_pool_id
     *
     * @param int|null $video_pool_id video_pool_id
     *
     * @return self
     */
    public function setVideoPoolId($video_pool_id)
    {
        if (is_null($video_pool_id)) {
            throw new \InvalidArgumentException('non-nullable video_pool_id cannot be null');
        }
        $this->container['video_pool_id'] = $video_pool_id;

        return $this;
    }

    /**
     * Gets html_code
     *
     * @return string|null
     */
    public function getHtmlCode()
    {
        return $this->container['html_code'];
    }

    /**
     * Sets html_code
     *
     * @param string|null $html_code html_code
     *
     * @return self
     */
    public function setHtmlCode($html_code)
    {
        if (is_null($html_code)) {
            throw new \InvalidArgumentException('non-nullable html_code cannot be null');
        }
        $this->container['html_code'] = $html_code;

        return $this;
    }

    /**
     * Gets html_total_items
     *
     * @return int|null
     */
    public function getHtmlTotalItems()
    {
        return $this->container['html_total_items'];
    }

    /**
     * Sets html_total_items
     *
     * @param int|null $html_total_items html_total_items
     *
     * @return self
     */
    public function setHtmlTotalItems($html_total_items)
    {
        if (is_null($html_total_items)) {
            throw new \InvalidArgumentException('non-nullable html_total_items cannot be null');
        }
        $this->container['html_total_items'] = $html_total_items;

        return $this;
    }

    /**
     * Gets icon_type
     *
     * @return string|null
     */
    public function getIconType()
    {
        return $this->container['icon_type'];
    }

    /**
     * Sets icon_type
     *
     * @param string|null $icon_type icon_type
     *
     * @return self
     */
    public function setIconType($icon_type)
    {
        if (is_null($icon_type)) {
            throw new \InvalidArgumentException('non-nullable icon_type cannot be null');
        }
        $allowedValues = $this->getIconTypeAllowableValues();
        if (!in_array($icon_type, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'icon_type', must be one of '%s'",
                    $icon_type,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['icon_type'] = $icon_type;

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



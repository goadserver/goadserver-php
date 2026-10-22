<?php
/**
 * AdspaceCreateSearchSettings
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
 * AdspaceCreateSearchSettings Class Doc Comment
 *
 * @category Class
 * @description Search Ads zones (search_ads, adzone type 42): native-style ads shown for what visitors type into the publisher&#39;s own search box, through the goCtrl &#x60;search&#x60; plugin. They serve only Search Ads campaigns (ad type 42, &#x60;searchads&#x60; — a copy of native bought as its own campaign type), never regular natives. The query is matched against campaign keyword targeting; campaigns without keywords serve on any query. Queries on the built-in blocked-term list (minors, non-consent, violence, …) never get an ad and leave no stats row; &#x60;blocked_terms&#x60; adds the zone&#39;s own list. These values are written into the generated embed code (GET /api/v1/adspaces/{id}/code).
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class AdspaceCreateSearchSettings implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'AdspaceCreate_search_settings';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'mode' => 'string',
        'input_selector' => 'string',
        'panel_selector' => 'string',
        'container_selector' => 'string',
        'max_links' => 'int',
        'chat_container' => 'string',
        'chat_answer' => 'string',
        'chat_prompt' => 'string',
        'chat_every' => 'int',
        'chat_style' => 'string',
        'chat_products' => 'int',
        'chat_followups' => 'int',
        'chat_input' => 'string',
        'chat_send' => 'string',
        'results' => 'int',
        'recent' => 'int',
        'min_chars' => 'int',
        'theme' => 'string',
        'label' => 'string',
        'layout' => 'string',
        'placeholder' => 'string',
        'submit_url' => 'string',
        'query_param' => 'string',
        'ad_position' => 'string',
        'on_open' => 'bool',
        'recent_pick' => 'string',
        'show_description' => 'bool',
        'show_url' => 'bool',
        'blocked_terms' => '\GoAdServerApi\Model\AdspaceCreateSearchSettingsBlockedTerms',
        'runtime' => 'array<string,mixed>'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'mode' => null,
        'input_selector' => null,
        'panel_selector' => null,
        'container_selector' => null,
        'max_links' => null,
        'chat_container' => null,
        'chat_answer' => null,
        'chat_prompt' => null,
        'chat_every' => null,
        'chat_style' => null,
        'chat_products' => null,
        'chat_followups' => null,
        'chat_input' => null,
        'chat_send' => null,
        'results' => null,
        'recent' => null,
        'min_chars' => null,
        'theme' => null,
        'label' => null,
        'layout' => null,
        'placeholder' => null,
        'submit_url' => null,
        'query_param' => null,
        'ad_position' => null,
        'on_open' => null,
        'recent_pick' => null,
        'show_description' => null,
        'show_url' => null,
        'blocked_terms' => null,
        'runtime' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'mode' => false,
        'input_selector' => false,
        'panel_selector' => false,
        'container_selector' => false,
        'max_links' => false,
        'chat_container' => false,
        'chat_answer' => false,
        'chat_prompt' => false,
        'chat_every' => false,
        'chat_style' => false,
        'chat_products' => false,
        'chat_followups' => false,
        'chat_input' => false,
        'chat_send' => false,
        'results' => false,
        'recent' => false,
        'min_chars' => false,
        'theme' => false,
        'label' => false,
        'layout' => false,
        'placeholder' => false,
        'submit_url' => false,
        'query_param' => false,
        'ad_position' => false,
        'on_open' => false,
        'recent_pick' => false,
        'show_description' => false,
        'show_url' => false,
        'blocked_terms' => false,
        'runtime' => false
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
        'mode' => 'mode',
        'input_selector' => 'input_selector',
        'panel_selector' => 'panel_selector',
        'container_selector' => 'container_selector',
        'max_links' => 'max_links',
        'chat_container' => 'chat_container',
        'chat_answer' => 'chat_answer',
        'chat_prompt' => 'chat_prompt',
        'chat_every' => 'chat_every',
        'chat_style' => 'chat_style',
        'chat_products' => 'chat_products',
        'chat_followups' => 'chat_followups',
        'chat_input' => 'chat_input',
        'chat_send' => 'chat_send',
        'results' => 'results',
        'recent' => 'recent',
        'min_chars' => 'min_chars',
        'theme' => 'theme',
        'label' => 'label',
        'layout' => 'layout',
        'placeholder' => 'placeholder',
        'submit_url' => 'submit_url',
        'query_param' => 'query_param',
        'ad_position' => 'ad_position',
        'on_open' => 'on_open',
        'recent_pick' => 'recent_pick',
        'show_description' => 'show_description',
        'show_url' => 'show_url',
        'blocked_terms' => 'blocked_terms',
        'runtime' => 'runtime'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'mode' => 'setMode',
        'input_selector' => 'setInputSelector',
        'panel_selector' => 'setPanelSelector',
        'container_selector' => 'setContainerSelector',
        'max_links' => 'setMaxLinks',
        'chat_container' => 'setChatContainer',
        'chat_answer' => 'setChatAnswer',
        'chat_prompt' => 'setChatPrompt',
        'chat_every' => 'setChatEvery',
        'chat_style' => 'setChatStyle',
        'chat_products' => 'setChatProducts',
        'chat_followups' => 'setChatFollowups',
        'chat_input' => 'setChatInput',
        'chat_send' => 'setChatSend',
        'results' => 'setResults',
        'recent' => 'setRecent',
        'min_chars' => 'setMinChars',
        'theme' => 'setTheme',
        'label' => 'setLabel',
        'layout' => 'setLayout',
        'placeholder' => 'setPlaceholder',
        'submit_url' => 'setSubmitUrl',
        'query_param' => 'setQueryParam',
        'ad_position' => 'setAdPosition',
        'on_open' => 'setOnOpen',
        'recent_pick' => 'setRecentPick',
        'show_description' => 'setShowDescription',
        'show_url' => 'setShowUrl',
        'blocked_terms' => 'setBlockedTerms',
        'runtime' => 'setRuntime'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'mode' => 'getMode',
        'input_selector' => 'getInputSelector',
        'panel_selector' => 'getPanelSelector',
        'container_selector' => 'getContainerSelector',
        'max_links' => 'getMaxLinks',
        'chat_container' => 'getChatContainer',
        'chat_answer' => 'getChatAnswer',
        'chat_prompt' => 'getChatPrompt',
        'chat_every' => 'getChatEvery',
        'chat_style' => 'getChatStyle',
        'chat_products' => 'getChatProducts',
        'chat_followups' => 'getChatFollowups',
        'chat_input' => 'getChatInput',
        'chat_send' => 'getChatSend',
        'results' => 'getResults',
        'recent' => 'getRecent',
        'min_chars' => 'getMinChars',
        'theme' => 'getTheme',
        'label' => 'getLabel',
        'layout' => 'getLayout',
        'placeholder' => 'getPlaceholder',
        'submit_url' => 'getSubmitUrl',
        'query_param' => 'getQueryParam',
        'ad_position' => 'getAdPosition',
        'on_open' => 'getOnOpen',
        'recent_pick' => 'getRecentPick',
        'show_description' => 'getShowDescription',
        'show_url' => 'getShowUrl',
        'blocked_terms' => 'getBlockedTerms',
        'runtime' => 'getRuntime'
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

    public const MODE_DROPDOWN = 'dropdown';
    public const MODE_PANEL = 'panel';
    public const MODE_BOX = 'box';
    public const MODE_RESULTS = 'results';
    public const MODE_INTEXT = 'intext';
    public const MODE_CHAT = 'chat';
    public const CHAT_STYLE_CARD = 'card';
    public const CHAT_STYLE_PRODUCTS = 'products';
    public const THEME_AUTO = 'auto';
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const LAYOUT_CARD = 'card';
    public const LAYOUT_SUGGESTION = 'suggestion';
    public const LAYOUT_RESULT = 'result';
    public const AD_POSITION_TOP = 'top';
    public const AD_POSITION_BOTTOM = 'bottom';
    public const RECENT_PICK_SUBMIT = 'submit';
    public const RECENT_PICK_FILL = 'fill';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getModeAllowableValues()
    {
        return [
            self::MODE_DROPDOWN,
            self::MODE_PANEL,
            self::MODE_BOX,
            self::MODE_RESULTS,
            self::MODE_INTEXT,
            self::MODE_CHAT,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getChatStyleAllowableValues()
    {
        return [
            self::CHAT_STYLE_CARD,
            self::CHAT_STYLE_PRODUCTS,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getThemeAllowableValues()
    {
        return [
            self::THEME_AUTO,
            self::THEME_LIGHT,
            self::THEME_DARK,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getLayoutAllowableValues()
    {
        return [
            self::LAYOUT_CARD,
            self::LAYOUT_SUGGESTION,
            self::LAYOUT_RESULT,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getAdPositionAllowableValues()
    {
        return [
            self::AD_POSITION_TOP,
            self::AD_POSITION_BOTTOM,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getRecentPickAllowableValues()
    {
        return [
            self::RECENT_PICK_SUBMIT,
            self::RECENT_PICK_FILL,
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
        $this->setIfExists('mode', $data ?? [], 'dropdown');
        $this->setIfExists('input_selector', $data ?? [], null);
        $this->setIfExists('panel_selector', $data ?? [], null);
        $this->setIfExists('container_selector', $data ?? [], null);
        $this->setIfExists('max_links', $data ?? [], 3);
        $this->setIfExists('chat_container', $data ?? [], null);
        $this->setIfExists('chat_answer', $data ?? [], null);
        $this->setIfExists('chat_prompt', $data ?? [], null);
        $this->setIfExists('chat_every', $data ?? [], 1);
        $this->setIfExists('chat_style', $data ?? [], 'card');
        $this->setIfExists('chat_products', $data ?? [], 3);
        $this->setIfExists('chat_followups', $data ?? [], 0);
        $this->setIfExists('chat_input', $data ?? [], null);
        $this->setIfExists('chat_send', $data ?? [], null);
        $this->setIfExists('results', $data ?? [], 1);
        $this->setIfExists('recent', $data ?? [], 5);
        $this->setIfExists('min_chars', $data ?? [], 3);
        $this->setIfExists('theme', $data ?? [], 'auto');
        $this->setIfExists('label', $data ?? [], 'Ad');
        $this->setIfExists('layout', $data ?? [], 'card');
        $this->setIfExists('placeholder', $data ?? [], 'Search');
        $this->setIfExists('submit_url', $data ?? [], '');
        $this->setIfExists('query_param', $data ?? [], 'q');
        $this->setIfExists('ad_position', $data ?? [], 'top');
        $this->setIfExists('on_open', $data ?? [], false);
        $this->setIfExists('recent_pick', $data ?? [], 'submit');
        $this->setIfExists('show_description', $data ?? [], true);
        $this->setIfExists('show_url', $data ?? [], true);
        $this->setIfExists('blocked_terms', $data ?? [], null);
        $this->setIfExists('runtime', $data ?? [], null);
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

        $allowedValues = $this->getModeAllowableValues();
        if (!is_null($this->container['mode']) && !in_array($this->container['mode'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'mode', must be one of '%s'",
                $this->container['mode'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['input_selector']) && (mb_strlen($this->container['input_selector']) > 200)) {
            $invalidProperties[] = "invalid value for 'input_selector', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['panel_selector']) && (mb_strlen($this->container['panel_selector']) > 200)) {
            $invalidProperties[] = "invalid value for 'panel_selector', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['container_selector']) && (mb_strlen($this->container['container_selector']) > 200)) {
            $invalidProperties[] = "invalid value for 'container_selector', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['max_links']) && ($this->container['max_links'] > 10)) {
            $invalidProperties[] = "invalid value for 'max_links', must be smaller than or equal to 10.";
        }

        if (!is_null($this->container['max_links']) && ($this->container['max_links'] < 1)) {
            $invalidProperties[] = "invalid value for 'max_links', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['chat_container']) && (mb_strlen($this->container['chat_container']) > 200)) {
            $invalidProperties[] = "invalid value for 'chat_container', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['chat_answer']) && (mb_strlen($this->container['chat_answer']) > 200)) {
            $invalidProperties[] = "invalid value for 'chat_answer', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['chat_prompt']) && (mb_strlen($this->container['chat_prompt']) > 200)) {
            $invalidProperties[] = "invalid value for 'chat_prompt', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['chat_every']) && ($this->container['chat_every'] > 10)) {
            $invalidProperties[] = "invalid value for 'chat_every', must be smaller than or equal to 10.";
        }

        if (!is_null($this->container['chat_every']) && ($this->container['chat_every'] < 1)) {
            $invalidProperties[] = "invalid value for 'chat_every', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getChatStyleAllowableValues();
        if (!is_null($this->container['chat_style']) && !in_array($this->container['chat_style'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'chat_style', must be one of '%s'",
                $this->container['chat_style'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['chat_products']) && ($this->container['chat_products'] > 4)) {
            $invalidProperties[] = "invalid value for 'chat_products', must be smaller than or equal to 4.";
        }

        if (!is_null($this->container['chat_products']) && ($this->container['chat_products'] < 2)) {
            $invalidProperties[] = "invalid value for 'chat_products', must be bigger than or equal to 2.";
        }

        if (!is_null($this->container['chat_followups']) && ($this->container['chat_followups'] > 2)) {
            $invalidProperties[] = "invalid value for 'chat_followups', must be smaller than or equal to 2.";
        }

        if (!is_null($this->container['chat_followups']) && ($this->container['chat_followups'] < 0)) {
            $invalidProperties[] = "invalid value for 'chat_followups', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['chat_input']) && (mb_strlen($this->container['chat_input']) > 200)) {
            $invalidProperties[] = "invalid value for 'chat_input', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['chat_send']) && (mb_strlen($this->container['chat_send']) > 200)) {
            $invalidProperties[] = "invalid value for 'chat_send', the character length must be smaller than or equal to 200.";
        }

        if (!is_null($this->container['results']) && ($this->container['results'] > 10)) {
            $invalidProperties[] = "invalid value for 'results', must be smaller than or equal to 10.";
        }

        if (!is_null($this->container['results']) && ($this->container['results'] < 1)) {
            $invalidProperties[] = "invalid value for 'results', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['recent']) && ($this->container['recent'] > 10)) {
            $invalidProperties[] = "invalid value for 'recent', must be smaller than or equal to 10.";
        }

        if (!is_null($this->container['recent']) && ($this->container['recent'] < 0)) {
            $invalidProperties[] = "invalid value for 'recent', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['min_chars']) && ($this->container['min_chars'] > 10)) {
            $invalidProperties[] = "invalid value for 'min_chars', must be smaller than or equal to 10.";
        }

        if (!is_null($this->container['min_chars']) && ($this->container['min_chars'] < 1)) {
            $invalidProperties[] = "invalid value for 'min_chars', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getThemeAllowableValues();
        if (!is_null($this->container['theme']) && !in_array($this->container['theme'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'theme', must be one of '%s'",
                $this->container['theme'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['label']) && (mb_strlen($this->container['label']) > 30)) {
            $invalidProperties[] = "invalid value for 'label', the character length must be smaller than or equal to 30.";
        }

        $allowedValues = $this->getLayoutAllowableValues();
        if (!is_null($this->container['layout']) && !in_array($this->container['layout'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'layout', must be one of '%s'",
                $this->container['layout'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['placeholder']) && (mb_strlen($this->container['placeholder']) > 60)) {
            $invalidProperties[] = "invalid value for 'placeholder', the character length must be smaller than or equal to 60.";
        }

        if (!is_null($this->container['submit_url']) && (mb_strlen($this->container['submit_url']) > 500)) {
            $invalidProperties[] = "invalid value for 'submit_url', the character length must be smaller than or equal to 500.";
        }

        if (!is_null($this->container['query_param']) && (mb_strlen($this->container['query_param']) > 40)) {
            $invalidProperties[] = "invalid value for 'query_param', the character length must be smaller than or equal to 40.";
        }

        $allowedValues = $this->getAdPositionAllowableValues();
        if (!is_null($this->container['ad_position']) && !in_array($this->container['ad_position'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'ad_position', must be one of '%s'",
                $this->container['ad_position'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getRecentPickAllowableValues();
        if (!is_null($this->container['recent_pick']) && !in_array($this->container['recent_pick'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'recent_pick', must be one of '%s'",
                $this->container['recent_pick'],
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
     * Gets mode
     *
     * @return string|null
     */
    public function getMode()
    {
        return $this->container['mode'];
    }

    /**
     * Sets mode
     *
     * @param string|null $mode chat = AI chat ads: the goCtrl `chatads` plugin watches the site's chat widget (chat_container / chat_answer / chat_prompt) and puts a labelled ad card under a finished answer, matched on the visitor's prompt like a search (impression when half on screen). AI apps without a web widget call the ad server directly — GET https://<ad domain>/chatad.go?spaceid=<zone>&q=<prompt>&a=<answer>&n=1-4&ip=<visitor ip>&agent=<visitor user agent> answers {ads:[{title, description, display_url, image, click_url, impression_url, click_log_url, followup, price}], label}; the prompt and the answer are both checked against the blocked-term list; fire impression_url only when the ad is shown. followup (when the advertiser set one) is a sponsored follow-up question: show it as a chip, and when the user picks it ask your chat that question and fire click_log_url (a click without a redirect). price is for product cards. intext = in-text ads, no search box: the goCtrl `intext` plugin picks the most frequent words and two-word phrases of the article (container_selector), asks the zone for an ad per term as if it were a search, and turns the first occurrence of each term that got an ad into a link with a hover card (impression counted when the card opens), at most max_links per page; the generated code is a single intext init, no placement element. dropdown = the plugin opens its own box under the input (ads + recent searches); panel = ads go into a placement element inside the site's existing suggestions panel; box = the plugin draws the search box itself into a placement div (gcs_box_<id>, in the generated code) and opens the dropdown under it — input_selector is not used; results = the ads are listed in the page itself, in the normal page flow instead of a floating dropdown: in the site's own div id_<id> when the page has one (optional), otherwise right under the search box. The box is the site's own (input_selector) or, with input_selector empty, one the plugin draws (gcs_box_<id>). The list updates as the visitor types and on Enter; a search in the page URL (query_param) is filled in on load.
     *
     * @return self
     */
    public function setMode($mode)
    {
        if (is_null($mode)) {
            throw new \InvalidArgumentException('non-nullable mode cannot be null');
        }
        $allowedValues = $this->getModeAllowableValues();
        if (!in_array($mode, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'mode', must be one of '%s'",
                    $mode,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['mode'] = $mode;

        return $this;
    }

    /**
     * Gets input_selector
     *
     * @return string|null
     */
    public function getInputSelector()
    {
        return $this->container['input_selector'];
    }

    /**
     * Sets input_selector
     *
     * @param string|null $input_selector CSS selector matching exactly one search input.
     *
     * @return self
     */
    public function setInputSelector($input_selector)
    {
        if (is_null($input_selector)) {
            throw new \InvalidArgumentException('non-nullable input_selector cannot be null');
        }
        if ((mb_strlen($input_selector) > 200)) {
            throw new \InvalidArgumentException('invalid length for $input_selector when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['input_selector'] = $input_selector;

        return $this;
    }

    /**
     * Gets panel_selector
     *
     * @return string|null
     */
    public function getPanelSelector()
    {
        return $this->container['panel_selector'];
    }

    /**
     * Sets panel_selector
     *
     * @param string|null $panel_selector panel mode: the site's suggestions panel; ads are only requested while it is visible.
     *
     * @return self
     */
    public function setPanelSelector($panel_selector)
    {
        if (is_null($panel_selector)) {
            throw new \InvalidArgumentException('non-nullable panel_selector cannot be null');
        }
        if ((mb_strlen($panel_selector) > 200)) {
            throw new \InvalidArgumentException('invalid length for $panel_selector when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['panel_selector'] = $panel_selector;

        return $this;
    }

    /**
     * Gets container_selector
     *
     * @return string|null
     */
    public function getContainerSelector()
    {
        return $this->container['container_selector'];
    }

    /**
     * Sets container_selector
     *
     * @param string|null $container_selector intext mode: the article text to link in; empty = the page's <article>, else <main>, else the body. Links, headings, nav, header, footer and form fields are never touched.
     *
     * @return self
     */
    public function setContainerSelector($container_selector)
    {
        if (is_null($container_selector)) {
            throw new \InvalidArgumentException('non-nullable container_selector cannot be null');
        }
        if ((mb_strlen($container_selector) > 200)) {
            throw new \InvalidArgumentException('invalid length for $container_selector when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['container_selector'] = $container_selector;

        return $this;
    }

    /**
     * Gets max_links
     *
     * @return int|null
     */
    public function getMaxLinks()
    {
        return $this->container['max_links'];
    }

    /**
     * Sets max_links
     *
     * @param int|null $max_links intext mode: most ad links per page (clamped).
     *
     * @return self
     */
    public function setMaxLinks($max_links)
    {
        if (is_null($max_links)) {
            throw new \InvalidArgumentException('non-nullable max_links cannot be null');
        }

        if (($max_links > 10)) {
            throw new \InvalidArgumentException('invalid value for $max_links when calling AdspaceCreateSearchSettings., must be smaller than or equal to 10.');
        }
        if (($max_links < 1)) {
            throw new \InvalidArgumentException('invalid value for $max_links when calling AdspaceCreateSearchSettings., must be bigger than or equal to 1.');
        }

        $this->container['max_links'] = $max_links;

        return $this;
    }

    /**
     * Gets chat_container
     *
     * @return string|null
     */
    public function getChatContainer()
    {
        return $this->container['chat_container'];
    }

    /**
     * Sets chat_container
     *
     * @param string|null $chat_container chat mode: the chat's message list.
     *
     * @return self
     */
    public function setChatContainer($chat_container)
    {
        if (is_null($chat_container)) {
            throw new \InvalidArgumentException('non-nullable chat_container cannot be null');
        }
        if ((mb_strlen($chat_container) > 200)) {
            throw new \InvalidArgumentException('invalid length for $chat_container when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['chat_container'] = $chat_container;

        return $this;
    }

    /**
     * Gets chat_answer
     *
     * @return string|null
     */
    public function getChatAnswer()
    {
        return $this->container['chat_answer'];
    }

    /**
     * Sets chat_answer
     *
     * @param string|null $chat_answer chat mode: the assistant's answer bubbles; the card goes under the newest finished one.
     *
     * @return self
     */
    public function setChatAnswer($chat_answer)
    {
        if (is_null($chat_answer)) {
            throw new \InvalidArgumentException('non-nullable chat_answer cannot be null');
        }
        if ((mb_strlen($chat_answer) > 200)) {
            throw new \InvalidArgumentException('invalid length for $chat_answer when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['chat_answer'] = $chat_answer;

        return $this;
    }

    /**
     * Gets chat_prompt
     *
     * @return string|null
     */
    public function getChatPrompt()
    {
        return $this->container['chat_prompt'];
    }

    /**
     * Sets chat_prompt
     *
     * @param string|null $chat_prompt chat mode: the visitor's messages; the newest is the prompt.
     *
     * @return self
     */
    public function setChatPrompt($chat_prompt)
    {
        if (is_null($chat_prompt)) {
            throw new \InvalidArgumentException('non-nullable chat_prompt cannot be null');
        }
        if ((mb_strlen($chat_prompt) > 200)) {
            throw new \InvalidArgumentException('invalid length for $chat_prompt when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['chat_prompt'] = $chat_prompt;

        return $this;
    }

    /**
     * Gets chat_every
     *
     * @return int|null
     */
    public function getChatEvery()
    {
        return $this->container['chat_every'];
    }

    /**
     * Sets chat_every
     *
     * @param int|null $chat_every chat mode: an ad under every Nth answer (clamped).
     *
     * @return self
     */
    public function setChatEvery($chat_every)
    {
        if (is_null($chat_every)) {
            throw new \InvalidArgumentException('non-nullable chat_every cannot be null');
        }

        if (($chat_every > 10)) {
            throw new \InvalidArgumentException('invalid value for $chat_every when calling AdspaceCreateSearchSettings., must be smaller than or equal to 10.');
        }
        if (($chat_every < 1)) {
            throw new \InvalidArgumentException('invalid value for $chat_every when calling AdspaceCreateSearchSettings., must be bigger than or equal to 1.');
        }

        $this->container['chat_every'] = $chat_every;

        return $this;
    }

    /**
     * Gets chat_style
     *
     * @return string|null
     */
    public function getChatStyle()
    {
        return $this->container['chat_style'];
    }

    /**
     * Sets chat_style
     *
     * @param string|null $chat_style chat mode: card = one ad card under the answer; products = a row of chat_products product cards with their price (ad_options.chat.price, filled by product feeds).
     *
     * @return self
     */
    public function setChatStyle($chat_style)
    {
        if (is_null($chat_style)) {
            throw new \InvalidArgumentException('non-nullable chat_style cannot be null');
        }
        $allowedValues = $this->getChatStyleAllowableValues();
        if (!in_array($chat_style, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'chat_style', must be one of '%s'",
                    $chat_style,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['chat_style'] = $chat_style;

        return $this;
    }

    /**
     * Gets chat_products
     *
     * @return int|null
     */
    public function getChatProducts()
    {
        return $this->container['chat_products'];
    }

    /**
     * Sets chat_products
     *
     * @param int|null $chat_products chat mode, products style: cards in the row (clamped).
     *
     * @return self
     */
    public function setChatProducts($chat_products)
    {
        if (is_null($chat_products)) {
            throw new \InvalidArgumentException('non-nullable chat_products cannot be null');
        }

        if (($chat_products > 4)) {
            throw new \InvalidArgumentException('invalid value for $chat_products when calling AdspaceCreateSearchSettings., must be smaller than or equal to 4.');
        }
        if (($chat_products < 2)) {
            throw new \InvalidArgumentException('invalid value for $chat_products when calling AdspaceCreateSearchSettings., must be bigger than or equal to 2.');
        }

        $this->container['chat_products'] = $chat_products;

        return $this;
    }

    /**
     * Gets chat_followups
     *
     * @return int|null
     */
    public function getChatFollowups()
    {
        return $this->container['chat_followups'];
    }

    /**
     * Sets chat_followups
     *
     * @param int|null $chat_followups chat mode: sponsored follow-up chips per answer, 0 = off. A chip shows an ad's own follow-up question (ad_options.chat.followup); picking it counts a click (no redirect) and asks the chat: a cancelable goctrl-followup event {question, hash, sid} on chat_container first, else the question is typed into chat_input and chat_send clicked (or Enter / the form submitted).
     *
     * @return self
     */
    public function setChatFollowups($chat_followups)
    {
        if (is_null($chat_followups)) {
            throw new \InvalidArgumentException('non-nullable chat_followups cannot be null');
        }

        if (($chat_followups > 2)) {
            throw new \InvalidArgumentException('invalid value for $chat_followups when calling AdspaceCreateSearchSettings., must be smaller than or equal to 2.');
        }
        if (($chat_followups < 0)) {
            throw new \InvalidArgumentException('invalid value for $chat_followups when calling AdspaceCreateSearchSettings., must be bigger than or equal to 0.');
        }

        $this->container['chat_followups'] = $chat_followups;

        return $this;
    }

    /**
     * Gets chat_input
     *
     * @return string|null
     */
    public function getChatInput()
    {
        return $this->container['chat_input'];
    }

    /**
     * Sets chat_input
     *
     * @param string|null $chat_input chat mode with follow-ups: the chat's text box.
     *
     * @return self
     */
    public function setChatInput($chat_input)
    {
        if (is_null($chat_input)) {
            throw new \InvalidArgumentException('non-nullable chat_input cannot be null');
        }
        if ((mb_strlen($chat_input) > 200)) {
            throw new \InvalidArgumentException('invalid length for $chat_input when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['chat_input'] = $chat_input;

        return $this;
    }

    /**
     * Gets chat_send
     *
     * @return string|null
     */
    public function getChatSend()
    {
        return $this->container['chat_send'];
    }

    /**
     * Sets chat_send
     *
     * @param string|null $chat_send chat mode with follow-ups: the send button; empty = Enter / the form's submit.
     *
     * @return self
     */
    public function setChatSend($chat_send)
    {
        if (is_null($chat_send)) {
            throw new \InvalidArgumentException('non-nullable chat_send cannot be null');
        }
        if ((mb_strlen($chat_send) > 200)) {
            throw new \InvalidArgumentException('invalid length for $chat_send when calling AdspaceCreateSearchSettings., must be smaller than or equal to 200.');
        }

        $this->container['chat_send'] = $chat_send;

        return $this;
    }

    /**
     * Gets results
     *
     * @return int|null
     */
    public function getResults()
    {
        return $this->container['results'];
    }

    /**
     * Sets results
     *
     * @param int|null $results Ads per search.
     *
     * @return self
     */
    public function setResults($results)
    {
        if (is_null($results)) {
            throw new \InvalidArgumentException('non-nullable results cannot be null');
        }

        if (($results > 10)) {
            throw new \InvalidArgumentException('invalid value for $results when calling AdspaceCreateSearchSettings., must be smaller than or equal to 10.');
        }
        if (($results < 1)) {
            throw new \InvalidArgumentException('invalid value for $results when calling AdspaceCreateSearchSettings., must be bigger than or equal to 1.');
        }

        $this->container['results'] = $results;

        return $this;
    }

    /**
     * Gets recent
     *
     * @return int|null
     */
    public function getRecent()
    {
        return $this->container['recent'];
    }

    /**
     * Sets recent
     *
     * @param int|null $recent dropdown mode: recent searches listed (0 = off).
     *
     * @return self
     */
    public function setRecent($recent)
    {
        if (is_null($recent)) {
            throw new \InvalidArgumentException('non-nullable recent cannot be null');
        }

        if (($recent > 10)) {
            throw new \InvalidArgumentException('invalid value for $recent when calling AdspaceCreateSearchSettings., must be smaller than or equal to 10.');
        }
        if (($recent < 0)) {
            throw new \InvalidArgumentException('invalid value for $recent when calling AdspaceCreateSearchSettings., must be bigger than or equal to 0.');
        }

        $this->container['recent'] = $recent;

        return $this;
    }

    /**
     * Gets min_chars
     *
     * @return int|null
     */
    public function getMinChars()
    {
        return $this->container['min_chars'];
    }

    /**
     * Sets min_chars
     *
     * @param int|null $min_chars Characters typed before ads are requested.
     *
     * @return self
     */
    public function setMinChars($min_chars)
    {
        if (is_null($min_chars)) {
            throw new \InvalidArgumentException('non-nullable min_chars cannot be null');
        }

        if (($min_chars > 10)) {
            throw new \InvalidArgumentException('invalid value for $min_chars when calling AdspaceCreateSearchSettings., must be smaller than or equal to 10.');
        }
        if (($min_chars < 1)) {
            throw new \InvalidArgumentException('invalid value for $min_chars when calling AdspaceCreateSearchSettings., must be bigger than or equal to 1.');
        }

        $this->container['min_chars'] = $min_chars;

        return $this;
    }

    /**
     * Gets theme
     *
     * @return string|null
     */
    public function getTheme()
    {
        return $this->container['theme'];
    }

    /**
     * Sets theme
     *
     * @param string|null $theme Colour scheme of the dropdown (dropdown and box mode) and of the results-mode ads.
     *
     * @return self
     */
    public function setTheme($theme)
    {
        if (is_null($theme)) {
            throw new \InvalidArgumentException('non-nullable theme cannot be null');
        }
        $allowedValues = $this->getThemeAllowableValues();
        if (!in_array($theme, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'theme', must be one of '%s'",
                    $theme,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['theme'] = $theme;

        return $this;
    }

    /**
     * Gets label
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->container['label'];
    }

    /**
     * Sets label
     *
     * @param string|null $label Disclosure label on every result; HTML and quote characters are stripped.
     *
     * @return self
     */
    public function setLabel($label)
    {
        if (is_null($label)) {
            throw new \InvalidArgumentException('non-nullable label cannot be null');
        }
        if ((mb_strlen($label) > 30)) {
            throw new \InvalidArgumentException('invalid length for $label when calling AdspaceCreateSearchSettings., must be smaller than or equal to 30.');
        }

        $this->container['label'] = $label;

        return $this;
    }

    /**
     * Gets layout
     *
     * @return string|null
     */
    public function getLayout()
    {
        return $this->container['layout'];
    }

    /**
     * Sets layout
     *
     * @param string|null $layout A zone created without layout starts as suggestion, and without native_settings.orientation as square; a stored zone that never had the key reads as card. With native_settings.orientation square the card and result images are drawn square. How each ad looks: card = image, title, description and display URL; suggestion = one compact line (small image + title) like a search suggestion; result = reads like an organic search result (label + display URL, the title as a link, the full description, a small image on the right), made for results mode. Picks the tpl_item in the generated code.
     *
     * @return self
     */
    public function setLayout($layout)
    {
        if (is_null($layout)) {
            throw new \InvalidArgumentException('non-nullable layout cannot be null');
        }
        $allowedValues = $this->getLayoutAllowableValues();
        if (!in_array($layout, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'layout', must be one of '%s'",
                    $layout,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['layout'] = $layout;

        return $this;
    }

    /**
     * Gets placeholder
     *
     * @return string|null
     */
    public function getPlaceholder()
    {
        return $this->container['placeholder'];
    }

    /**
     * Sets placeholder
     *
     * @param string|null $placeholder box mode, and results mode without input_selector: text in the empty search box; HTML and quote characters are stripped.
     *
     * @return self
     */
    public function setPlaceholder($placeholder)
    {
        if (is_null($placeholder)) {
            throw new \InvalidArgumentException('non-nullable placeholder cannot be null');
        }
        if ((mb_strlen($placeholder) > 60)) {
            throw new \InvalidArgumentException('invalid length for $placeholder when calling AdspaceCreateSearchSettings., must be smaller than or equal to 60.');
        }

        $this->container['placeholder'] = $placeholder;

        return $this;
    }

    /**
     * Gets submit_url
     *
     * @return string|null
     */
    public function getSubmitUrl()
    {
        return $this->container['submit_url'];
    }

    /**
     * Sets submit_url
     *
     * @param string|null $submit_url box mode: where a search goes on Enter; {q} = the URL-encoded search text (without {q} it is appended as ?q=). http(s) only — anything else is stored as empty. Empty = the box only shows ads and recent searches.
     *
     * @return self
     */
    public function setSubmitUrl($submit_url)
    {
        if (is_null($submit_url)) {
            throw new \InvalidArgumentException('non-nullable submit_url cannot be null');
        }
        if ((mb_strlen($submit_url) > 500)) {
            throw new \InvalidArgumentException('invalid length for $submit_url when calling AdspaceCreateSearchSettings., must be smaller than or equal to 500.');
        }

        $this->container['submit_url'] = $submit_url;

        return $this;
    }

    /**
     * Gets query_param
     *
     * @return string|null
     */
    public function getQueryParam()
    {
        return $this->container['query_param'];
    }

    /**
     * Sets query_param
     *
     * @param string|null $query_param results mode: the URL parameter a search is filled in from when the page loads (https://example.com/search?q=… → q). Letters, digits, _ . - only; anything else is stored as q.
     *
     * @return self
     */
    public function setQueryParam($query_param)
    {
        if (is_null($query_param)) {
            throw new \InvalidArgumentException('non-nullable query_param cannot be null');
        }
        if ((mb_strlen($query_param) > 40)) {
            throw new \InvalidArgumentException('invalid length for $query_param when calling AdspaceCreateSearchSettings., must be smaller than or equal to 40.');
        }

        $this->container['query_param'] = $query_param;

        return $this;
    }

    /**
     * Gets ad_position
     *
     * @return string|null
     */
    public function getAdPosition()
    {
        return $this->container['ad_position'];
    }

    /**
     * Sets ad_position
     *
     * @param string|null $ad_position dropdown and box mode: the ads above (top) or below (bottom) the recent searches in the plugin's own dropdown. In panel mode the ads go wherever the site put the placement div.
     *
     * @return self
     */
    public function setAdPosition($ad_position)
    {
        if (is_null($ad_position)) {
            throw new \InvalidArgumentException('non-nullable ad_position cannot be null');
        }
        $allowedValues = $this->getAdPositionAllowableValues();
        if (!in_array($ad_position, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'ad_position', must be one of '%s'",
                    $ad_position,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['ad_position'] = $ad_position;

        return $this;
    }

    /**
     * Gets on_open
     *
     * @return bool|null
     */
    public function getOnOpen()
    {
        return $this->container['on_open'];
    }

    /**
     * Sets on_open
     *
     * @param bool|null $on_open dropdown, box and panel mode (in panel mode the ad goes into the slot inside the site's own suggestions panel when the box gets focus): when the visitor opens the box with nothing (or fewer than min_chars characters) typed, show an untargeted ad (campaigns without keywords); the query-matched ad replaces it once the text is long enough. The short text is never sent as keywords, and an ad still on screen is reused rather than asked for again.
     *
     * @return self
     */
    public function setOnOpen($on_open)
    {
        if (is_null($on_open)) {
            throw new \InvalidArgumentException('non-nullable on_open cannot be null');
        }
        $this->container['on_open'] = $on_open;

        return $this;
    }

    /**
     * Gets recent_pick
     *
     * @return string|null
     */
    public function getRecentPick()
    {
        return $this->container['recent_pick'];
    }

    /**
     * Sets recent_pick
     *
     * @param string|null $recent_pick dropdown and box mode: picking a recent search submits it (submit) or only puts it into the box and shows the ads for it (fill).
     *
     * @return self
     */
    public function setRecentPick($recent_pick)
    {
        if (is_null($recent_pick)) {
            throw new \InvalidArgumentException('non-nullable recent_pick cannot be null');
        }
        $allowedValues = $this->getRecentPickAllowableValues();
        if (!in_array($recent_pick, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'recent_pick', must be one of '%s'",
                    $recent_pick,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['recent_pick'] = $recent_pick;

        return $this;
    }

    /**
     * Gets show_description
     *
     * @return bool|null
     */
    public function getShowDescription()
    {
        return $this->container['show_description'];
    }

    /**
     * Sets show_description
     *
     * @param bool|null $show_description card and result layouts: show the ad's description. The suggestion layout never does.
     *
     * @return self
     */
    public function setShowDescription($show_description)
    {
        if (is_null($show_description)) {
            throw new \InvalidArgumentException('non-nullable show_description cannot be null');
        }
        $this->container['show_description'] = $show_description;

        return $this;
    }

    /**
     * Gets show_url
     *
     * @return bool|null
     */
    public function getShowUrl()
    {
        return $this->container['show_url'];
    }

    /**
     * Sets show_url
     *
     * @param bool|null $show_url card and result layouts: show the ad's display URL. The suggestion layout never does.
     *
     * @return self
     */
    public function setShowUrl($show_url)
    {
        if (is_null($show_url)) {
            throw new \InvalidArgumentException('non-nullable show_url cannot be null');
        }
        $this->container['show_url'] = $show_url;

        return $this;
    }

    /**
     * Gets blocked_terms
     *
     * @return \GoAdServerApi\Model\AdspaceCreateSearchSettingsBlockedTerms|null
     */
    public function getBlockedTerms()
    {
        return $this->container['blocked_terms'];
    }

    /**
     * Sets blocked_terms
     *
     * @param \GoAdServerApi\Model\AdspaceCreateSearchSettingsBlockedTerms|null $blocked_terms blocked_terms
     *
     * @return self
     */
    public function setBlockedTerms($blocked_terms)
    {
        if (is_null($blocked_terms)) {
            throw new \InvalidArgumentException('non-nullable blocked_terms cannot be null');
        }
        $this->container['blocked_terms'] = $blocked_terms;

        return $this;
    }

    /**
     * Gets runtime
     *
     * @return array<string,mixed>|null
     */
    public function getRuntime()
    {
        return $this->container['runtime'];
    }

    /**
     * Sets runtime
     *
     * @param array<string,mixed>|null $runtime Read-only, derived on every save (search-box modes only; absent for intext and chat): the zone's display settings as the goCtrl search plugin applies them at page load over the values in the pasted code — results, min_chars, recent, theme, ad_position, on_open, recent_pick, placeholder, submit_url, param, tpl_body, tpl_row, tpl_item. A change to those settings reaches pages without new code; selectors and placement still come from the code. Ignored when sent.
     *
     * @return self
     */
    public function setRuntime($runtime)
    {
        if (is_null($runtime)) {
            throw new \InvalidArgumentException('non-nullable runtime cannot be null');
        }
        $this->container['runtime'] = $runtime;

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



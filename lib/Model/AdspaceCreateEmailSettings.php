<?php
/**
 * AdspaceCreateEmailSettings
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
 * AdspaceCreateEmailSettings Class Doc Comment
 *
 * @category Class
 * @description Email display zones (email_display, adzone type 44): image banners in a newsletter, picked when the email is opened. The zone also needs its banner size (adsizeid / banner_settings.sizeid). The code (GET /adspaces/{id}/code → ad_tag) is HTML for the newsletter template: per slot a linked image, https://&lt;ad domain&gt;/mailad.go?spaceid&#x3D;&lt;zone&gt;&amp;slot&#x3D;&lt;n&gt;&amp;rid&#x3D;&lt;merge tag&gt;&amp;act&#x3D;img inside a link with act&#x3D;click. The first image fetch per (zone, slot, recipient) picks a banner (image creatives only: html, iframe and video banners are skipped), counts the impression and keeps the choice for ttl_days; a re-open gets the same creative uncounted and the link goes to the ad that was shown. A click with no kept choice (images off) picks an ad then. No ad: the image is a transparent pixel, the link goes to fallback_link (or answers 204). Image proxies (Gmail, Apple Mail Privacy Protection) fetch from their own network: geo targeting sees the proxy, and Apple prefetches.
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class AdspaceCreateEmailSettings implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'AdspaceCreate_email_settings';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'slots' => 'int',
        'merge_tag' => 'string',
        'label' => 'string',
        'ttl_days' => 'int',
        'fallback_link' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'slots' => null,
        'merge_tag' => null,
        'label' => null,
        'ttl_days' => null,
        'fallback_link' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'slots' => false,
        'merge_tag' => false,
        'label' => false,
        'ttl_days' => false,
        'fallback_link' => false
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
        'slots' => 'slots',
        'merge_tag' => 'merge_tag',
        'label' => 'label',
        'ttl_days' => 'ttl_days',
        'fallback_link' => 'fallback_link'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'slots' => 'setSlots',
        'merge_tag' => 'setMergeTag',
        'label' => 'setLabel',
        'ttl_days' => 'setTtlDays',
        'fallback_link' => 'setFallbackLink'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'slots' => 'getSlots',
        'merge_tag' => 'getMergeTag',
        'label' => 'getLabel',
        'ttl_days' => 'getTtlDays',
        'fallback_link' => 'getFallbackLink'
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
        $this->setIfExists('slots', $data ?? [], 1);
        $this->setIfExists('merge_tag', $data ?? [], '{{email_id}}');
        $this->setIfExists('label', $data ?? [], 'Advertisement');
        $this->setIfExists('ttl_days', $data ?? [], 7);
        $this->setIfExists('fallback_link', $data ?? [], '');
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

        if (!is_null($this->container['slots']) && ($this->container['slots'] > 6)) {
            $invalidProperties[] = "invalid value for 'slots', must be smaller than or equal to 6.";
        }

        if (!is_null($this->container['slots']) && ($this->container['slots'] < 1)) {
            $invalidProperties[] = "invalid value for 'slots', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['merge_tag']) && (mb_strlen($this->container['merge_tag']) > 40)) {
            $invalidProperties[] = "invalid value for 'merge_tag', the character length must be smaller than or equal to 40.";
        }

        if (!is_null($this->container['label']) && (mb_strlen($this->container['label']) > 30)) {
            $invalidProperties[] = "invalid value for 'label', the character length must be smaller than or equal to 30.";
        }

        if (!is_null($this->container['ttl_days']) && ($this->container['ttl_days'] > 30)) {
            $invalidProperties[] = "invalid value for 'ttl_days', must be smaller than or equal to 30.";
        }

        if (!is_null($this->container['ttl_days']) && ($this->container['ttl_days'] < 1)) {
            $invalidProperties[] = "invalid value for 'ttl_days', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['fallback_link']) && (mb_strlen($this->container['fallback_link']) > 500)) {
            $invalidProperties[] = "invalid value for 'fallback_link', the character length must be smaller than or equal to 500.";
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
     * Gets slots
     *
     * @return int|null
     */
    public function getSlots()
    {
        return $this->container['slots'];
    }

    /**
     * Sets slots
     *
     * @param int|null $slots Ad slots in the newsletter, each its own image + link (clamped).
     *
     * @return self
     */
    public function setSlots($slots)
    {
        if (is_null($slots)) {
            throw new \InvalidArgumentException('non-nullable slots cannot be null');
        }

        if (($slots > 6)) {
            throw new \InvalidArgumentException('invalid value for $slots when calling AdspaceCreateEmailSettings., must be smaller than or equal to 6.');
        }
        if (($slots < 1)) {
            throw new \InvalidArgumentException('invalid value for $slots when calling AdspaceCreateEmailSettings., must be bigger than or equal to 1.');
        }

        $this->container['slots'] = $slots;

        return $this;
    }

    /**
     * Gets merge_tag
     *
     * @return string|null
     */
    public function getMergeTag()
    {
        return $this->container['merge_tag'];
    }

    /**
     * Sets merge_tag
     *
     * @param string|null $merge_tag Your email tool's per-recipient merge tag, written into the code as rid. Use a recipient id, not the address. No spaces, quotes, < > or &; anything else falls back to the default.
     *
     * @return self
     */
    public function setMergeTag($merge_tag)
    {
        if (is_null($merge_tag)) {
            throw new \InvalidArgumentException('non-nullable merge_tag cannot be null');
        }
        if ((mb_strlen($merge_tag) > 40)) {
            throw new \InvalidArgumentException('invalid length for $merge_tag when calling AdspaceCreateEmailSettings., must be smaller than or equal to 40.');
        }

        $this->container['merge_tag'] = $merge_tag;

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
     * @param string|null $label Disclosure above each slot and the image alt text; plain text (empty = the default).
     *
     * @return self
     */
    public function setLabel($label)
    {
        if (is_null($label)) {
            throw new \InvalidArgumentException('non-nullable label cannot be null');
        }
        if ((mb_strlen($label) > 30)) {
            throw new \InvalidArgumentException('invalid length for $label when calling AdspaceCreateEmailSettings., must be smaller than or equal to 30.');
        }

        $this->container['label'] = $label;

        return $this;
    }

    /**
     * Gets ttl_days
     *
     * @return int|null
     */
    public function getTtlDays()
    {
        return $this->container['ttl_days'];
    }

    /**
     * Sets ttl_days
     *
     * @param int|null $ttl_days Days the ad picked for a recipient's open is kept (clamped).
     *
     * @return self
     */
    public function setTtlDays($ttl_days)
    {
        if (is_null($ttl_days)) {
            throw new \InvalidArgumentException('non-nullable ttl_days cannot be null');
        }

        if (($ttl_days > 30)) {
            throw new \InvalidArgumentException('invalid value for $ttl_days when calling AdspaceCreateEmailSettings., must be smaller than or equal to 30.');
        }
        if (($ttl_days < 1)) {
            throw new \InvalidArgumentException('invalid value for $ttl_days when calling AdspaceCreateEmailSettings., must be bigger than or equal to 1.');
        }

        $this->container['ttl_days'] = $ttl_days;

        return $this;
    }

    /**
     * Gets fallback_link
     *
     * @return string|null
     */
    public function getFallbackLink()
    {
        return $this->container['fallback_link'];
    }

    /**
     * Sets fallback_link
     *
     * @param string|null $fallback_link http(s) URL a click goes to when no ad can be picked; anything else is stored empty.
     *
     * @return self
     */
    public function setFallbackLink($fallback_link)
    {
        if (is_null($fallback_link)) {
            throw new \InvalidArgumentException('non-nullable fallback_link cannot be null');
        }
        if ((mb_strlen($fallback_link) > 500)) {
            throw new \InvalidArgumentException('invalid length for $fallback_link when calling AdspaceCreateEmailSettings., must be smaller than or equal to 500.');
        }

        $this->container['fallback_link'] = $fallback_link;

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



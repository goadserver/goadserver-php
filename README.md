# GoAdServerApi

Public REST API for advertisers and publishers on the GoAdServer platform.

**Authentication.** All endpoints (except `/api/public/_*`) require an API
key passed as `Authorization: Bearer gas_live_…`. Create and manage keys
in your account settings — each key has its own scopes, optional resource
pinning, optional IP allowlist, and per-key rate limit.

**System rules apply over API rules.** The public API never lets a key
bypass restrictions the same user faces in the panel:

- **Source names** (`stats_source_names` right). When the user lacks
  this right, advertiser stats responses return `{id, name}` with
  `name` equal to the id — no human label leaks through.
- **SubID encoding** (`modules.global.subid_encoding`). When enabled
  for the system, the user only ever sees encoded SubIDs in the panel.
  The API mirrors this both ways: `subid_excl`/`subid_incl` filter
  endpoints accept the encoded form on write (decoded server-side
  before storage) and emit the encoded form on read.
- **Module gating.** Endpoints whose module is disabled for the
  tenant don't appear in the spec or routes.txt at all.

**Client SDKs.** Each SDK is generated from this spec and published as
a public GitHub repository under the `goadserver` organization. The
same tarball is also embedded in the API server and downloadable from
`/api/public/sdk/<lang>.tgz` — useful when GitHub is unreachable.

> **Per-network host.** The default `https://up.go-adserver.com`
> baked into the published SDKs is a reference host. Every white-label
> tenant runs on its own panel domain — override the host on first use
> (`setHost()` / `Configuration.host` / `basePath` etc.). Or, fetch the
> spec from your own tenant (`/api/public/openapi.yaml` returns it with
> *your* hostname as the default) and regenerate the SDK locally — the
> regenerated client has your URL baked in.

- **PHP** — `github.com/goadserver/goadserver-php`
  (namespace `GoAdServer\\Api`, composer name `goadserver/sdk`).

  ```bash
  composer config repositories.goadserver vcs https://github.com/goadserver/goadserver-php
  composer require goadserver/sdk:dev-main
  ```

  ```php
  $cfg = GoAdServer\\Api\\Configuration::getDefaultConfiguration()
          ->setHost('https://up.go-adserver.com')
          ->setAccessToken('gas_live_…');
  $api = new GoAdServer\\Api\\Api\\AccountAPIApi(null, $cfg);
  $me  = $api->apiV1AccountGet();
  ```

- **Go** — `github.com/goadserver/goadserver-go`.

  ```bash
  go get github.com/goadserver/goadserver-go@latest
  ```

  ```go
  import gas \"github.com/goadserver/goadserver-go\"

  cfg := gas.NewConfiguration()
  cfg.Servers = gas.ServerConfigurations{{URL: \"https://up.go-adserver.com\"}}
  cli := gas.NewAPIClient(cfg)
  ctx := context.WithValue(context.Background(),
      gas.ContextAccessToken, \"gas_live_…\")
  me, _, err := cli.AccountAPI.ApiV1AccountGet(ctx).Execute()
  ```

- **Python** — `github.com/goadserver/goadserver-python` (package
  `goadserver`). Pulls `pydantic` + `urllib3` automatically.

  ```bash
  pip install git+https://github.com/goadserver/goadserver-python.git
  ```

  ```python
  from goadserver import ApiClient, Configuration
  from goadserver.api import AccountApi, CampaignsApi

  cfg = Configuration(host=\"https://up.go-adserver.com\")
  cfg.access_token = \"gas_live_…\"
  client = ApiClient(cfg)

  me = AccountApi(client).api_v1_account_get()
  camps = CampaignsApi(client).api_v1_campaigns_get(per_page=10)
  ```

- **JavaScript / TypeScript** — `github.com/goadserver/goadserver-js`
  (npm name `goadserver-sdk`, generator `typescript-fetch`).

  ```bash
  npm install github:goadserver/goadserver-js
  ```

  ```ts
  import { Configuration, AccountApi, CampaignsApi }
    from \"goadserver-sdk\";

  const cfg = new Configuration({
    basePath: \"https://up.go-adserver.com\",
    accessToken: \"gas_live_…\",
  });

  const me   = await new AccountApi(cfg).apiV1AccountGet();
  const list = await new CampaignsApi(cfg).apiV1CampaignsGet({ perPage: 10 });
  ```

  The CJS build is broadly compatible (Node + bundlers); the ESM
  build is missing trailing `.js` extensions in its imports, a known
  `typescript-fetch` quirk — most consumers use CJS.

**Offline / firewalled install.** If the customer's network can't
reach GitHub, every SDK's source is also served as a tarball from the
API host:

```bash
curl -O https://up.go-adserver.com/api/public/sdk/python.tgz
tar xzf python.tgz && pip install ./python
```

Round-trip smoke tests for all four SDKs live at `clients/_smoke/`
in the platform repo.



## Installation & Usage

### Requirements

PHP 8.1 and later.

### Composer

To install the bindings via [Composer](https://getcomposer.org/), add the following to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/goadserver/goadserver-php.git"
    }
  ],
  "require": {
    "goadserver/goadserver-php": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
<?php
require_once('/path/to/GoAdServerApi/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountGet: ', $e->getMessage(), PHP_EOL;
}

```

## API Endpoints

All URIs are relative to *https://up.go-adserver.com*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*AccountApi* | [**apiV1AccountGet**](docs/Api/AccountApi.md#apiv1accountget) | **GET** /api/v1/account | Identity check — returns the authenticated user + scopes of the calling key.
*AdspacesApi* | [**apiV1AdspacesGet**](docs/Api/AdspacesApi.md#apiv1adspacesget) | **GET** /api/v1/adspaces | List ad zones owned by the caller.
*AdspacesApi* | [**apiV1AdspacesIdActivePatch**](docs/Api/AdspacesApi.md#apiv1adspacesidactivepatch) | **PATCH** /api/v1/adspaces/{id}/active | Activate or deactivate an adspace.
*AdspacesApi* | [**apiV1AdspacesIdGet**](docs/Api/AdspacesApi.md#apiv1adspacesidget) | **GET** /api/v1/adspaces/{id} | Get a single adspace.
*CampaignsApi* | [**apiV1CampaignsGet**](docs/Api/CampaignsApi.md#apiv1campaignsget) | **GET** /api/v1/campaigns | List the caller&#39;s campaigns across all types (or one type).
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidsget) | **GET** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | List per-zone bid overrides for a RON ad.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidsput) | **PUT** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | Replace the per-zone bid overrides on a RON ad.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidszoneiddelete) | **DELETE** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/{zoneId} | Clear a single per-zone bid override.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidactivepatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/active | Pause or unpause a single ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidbidpatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bid | Set a single ad&#39;s bid.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdClonePost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidclonepost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/clone | Clone a single ad within a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadiddelete) | **DELETE** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Soft-delete an ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Show one ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Update an ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads | List ads on a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsPost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadspost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads | Create an ad on a campaign (smart-default, JSON-only).
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdSchemaAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidschemaadsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/_schema/ads | Field metadata for ad creation, scoped to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdActivePatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidactivepatch) | **PATCH** /api/v1/campaigns/{type}/{id}/active | Pause or unpause a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdBidsPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidbidspatch) | **PATCH** /api/v1/campaigns/{type}/{id}/bids | Mass update bids on every ad in a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdClonePost**](docs/Api/CampaignsApi.md#apiv1campaignstypeidclonepost) | **POST** /api/v1/campaigns/{type}/{id}/clone | Clone a campaign and all its ads.
*CampaignsApi* | [**apiV1CampaignsTypeIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypeiddelete) | **DELETE** /api/v1/campaigns/{type}/{id} | Soft-delete a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfiltersfiltertypefilteriddelete) | **DELETE** /api/v1/campaigns/{type}/{id}/filters/{filter_type}/{filter_id} | Detach a filter from this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfiltersget) | **GET** /api/v1/campaigns/{type}/{id}/filters | List filters attached to this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersPost**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfilterspost) | **POST** /api/v1/campaigns/{type}/{id}/filters | Attach a filter to this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidget) | **GET** /api/v1/campaigns/{type}/{id} | Get a single campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdMinBidGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidminbidget) | **GET** /api/v1/campaigns/{type}/{id}/min-bid | Get the campaign&#39;s minimum acceptable CPC bid.
*CampaignsApi* | [**apiV1CampaignsTypePost**](docs/Api/CampaignsApi.md#apiv1campaignstypepost) | **POST** /api/v1/campaigns/{type} | Create a new campaign (smart-default).
*CampaignsApi* | [**apiV1CampaignsTypeSchemaAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeschemaadsget) | **GET** /api/v1/campaigns/{type}/_schema/ads | Field metadata for ad creation.
*CampaignsApi* | [**apiV1CampaignsTypeSchemaGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeschemaget) | **GET** /api/v1/campaigns/{type}/_schema | Field metadata for create/update.
*CreativePoolsApi* | [**apiV1CreativePoolsGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsget) | **GET** /api/v1/creative-pools | List creative pools.
*CreativePoolsApi* | [**apiV1CreativePoolsIdClonePost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidclonepost) | **POST** /api/v1/creative-pools/{id}/clone | Clone a creative pool with all its items.
*CreativePoolsApi* | [**apiV1CreativePoolsIdDelete**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiddelete) | **DELETE** /api/v1/creative-pools/{id} | Soft-delete a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidget) | **GET** /api/v1/creative-pools/{id} | Show a single creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsget) | **GET** /api/v1/creative-pools/{id}/items | List items in a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdDelete**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemiddelete) | **DELETE** /api/v1/creative-pools/{id}/items/{itemId} | Soft-delete a single pool item.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdPut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemidput) | **PUT** /api/v1/creative-pools/{id}/items/{itemId} | Update a single pool item.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdTogglePut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemidtoggleput) | **PUT** /api/v1/creative-pools/{id}/items/{itemId}/toggle | Toggle a pool item on / off.
*CreativePoolsApi* | [**apiV1CreativePoolsIdPut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidput) | **PUT** /api/v1/creative-pools/{id} | Update creative pool meta.
*CreativePoolsApi* | [**apiV1CreativePoolsIdUsedInGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidusedinget) | **GET** /api/v1/creative-pools/{id}/used-in | List campaigns / ads that reference this pool.
*CreativePoolsApi* | [**apiV1CreativePoolsPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolspost) | **POST** /api/v1/creative-pools | Create a creative pool.
*FiltersApi* | [**apiV1FiltersGet**](docs/Api/FiltersApi.md#apiv1filtersget) | **GET** /api/v1/filters | List all filters across types.
*FiltersApi* | [**apiV1FiltersPost**](docs/Api/FiltersApi.md#apiv1filterspost) | **POST** /api/v1/filters | Create a filter and populate it with items.
*FiltersApi* | [**apiV1FiltersTypeIdActivePatch**](docs/Api/FiltersApi.md#apiv1filterstypeidactivepatch) | **PATCH** /api/v1/filters/{type}/{id}/active | Activate or deactivate a filter (exclusion types only).
*FiltersApi* | [**apiV1FiltersTypeIdDelete**](docs/Api/FiltersApi.md#apiv1filterstypeiddelete) | **DELETE** /api/v1/filters/{type}/{id} | Delete a filter (and its items, for IP pools).
*FiltersApi* | [**apiV1FiltersTypeIdGet**](docs/Api/FiltersApi.md#apiv1filterstypeidget) | **GET** /api/v1/filters/{type}/{id} | Get a single filter with its items.
*FiltersApi* | [**apiV1FiltersTypeIdItemsItemDelete**](docs/Api/FiltersApi.md#apiv1filterstypeiditemsitemdelete) | **DELETE** /api/v1/filters/{type}/{id}/items/{item} | Remove a single item from a filter.
*FiltersApi* | [**apiV1FiltersTypeIdItemsPost**](docs/Api/FiltersApi.md#apiv1filterstypeiditemspost) | **POST** /api/v1/filters/{type}/{id}/items | Add items to an existing filter.
*FundsApi* | [**apiV1FundsGet**](docs/Api/FundsApi.md#apiv1fundsget) | **GET** /api/v1/funds | Current advertiser balance + pending deposits.
*PublicApi* | [**apiPublicOpenapiJsonGet**](docs/Api/PublicApi.md#apipublicopenapijsonget) | **GET** /api/public/openapi.json | OpenAPI spec as JSON.
*PublicApi* | [**apiPublicOpenapiYamlGet**](docs/Api/PublicApi.md#apipublicopenapiyamlget) | **GET** /api/public/openapi.yaml | Raw OpenAPI spec (this document).
*PublicApi* | [**apiPublicRoutesTxtGet**](docs/Api/PublicApi.md#apipublicroutestxtget) | **GET** /api/public/routes.txt | Flat list of public routes — agent-friendly.
*RetargetingApi* | [**apiV1RetargetingPixelsGet**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsget) | **GET** /api/v1/retargeting/pixels | List the caller&#39;s retargeting pixels.
*RetargetingApi* | [**apiV1RetargetingPixelsIdActivePatch**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidactivepatch) | **PATCH** /api/v1/retargeting/pixels/{id}/active | Activate or deactivate a retargeting pixel.
*RetargetingApi* | [**apiV1RetargetingPixelsIdDelete**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsiddelete) | **DELETE** /api/v1/retargeting/pixels/{id} | Soft-delete a retargeting pixel and clear its captured visitors.
*RetargetingApi* | [**apiV1RetargetingPixelsIdGet**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidget) | **GET** /api/v1/retargeting/pixels/{id} | Get one retargeting pixel.
*RetargetingApi* | [**apiV1RetargetingPixelsIdPut**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidput) | **PUT** /api/v1/retargeting/pixels/{id} | Rename a retargeting pixel.
*RetargetingApi* | [**apiV1RetargetingPixelsPost**](docs/Api/RetargetingApi.md#apiv1retargetingpixelspost) | **POST** /api/v1/retargeting/pixels | Create a retargeting pixel.
*SitesApi* | [**apiV1SitesGet**](docs/Api/SitesApi.md#apiv1sitesget) | **GET** /api/v1/sites | List the caller&#39;s websites.
*StatsAdvertiserApi* | [**apiV1AdvertiserStatsGet**](docs/Api/StatsAdvertiserApi.md#apiv1advertiserstatsget) | **GET** /api/v1/advertiser/stats | Display, native and CPM/RON campaign stats.
*StatsAdvertiserOffersApi* | [**apiV1AdvertiserOffersStatsGet**](docs/Api/StatsAdvertiserOffersApi.md#apiv1advertiseroffersstatsget) | **GET** /api/v1/advertiser/offers/stats | CPA / affiliate offer performance — advertiser side.
*StatsAdvertiserRTBApi* | [**apiV1AdvertiserRtbStatsGet**](docs/Api/StatsAdvertiserRTBApi.md#apiv1advertiserrtbstatsget) | **GET** /api/v1/advertiser/rtb/stats | DSP / RTB-side stats.
*StatsAffiliateApi* | [**apiV1PublisherOffersStatsGet**](docs/Api/StatsAffiliateApi.md#apiv1publisheroffersstatsget) | **GET** /api/v1/publisher/offers/stats | Affiliate / smartlink offer performance — publisher side.
*StatsPublisherApi* | [**apiV1PublisherStatsGet**](docs/Api/StatsPublisherApi.md#apiv1publisherstatsget) | **GET** /api/v1/publisher/stats | Publisher earnings (sites / adzones).
*StatsPublisherSSPApi* | [**apiV1PublisherSspStatsGet**](docs/Api/StatsPublisherSSPApi.md#apiv1publishersspstatsget) | **GET** /api/v1/publisher/ssp/stats | SSP / smart-campaign earnings.
*StatsPublisherSmartZonesApi* | [**apiV1PublisherSmartzonesStatsGet**](docs/Api/StatsPublisherSmartZonesApi.md#apiv1publishersmartzonesstatsget) | **GET** /api/v1/publisher/smartzones/stats | SmartZone (waterfall) earnings.
*TrackingApi* | [**apiV1TrackingConversionTypesGet**](docs/Api/TrackingApi.md#apiv1trackingconversiontypesget) | **GET** /api/v1/tracking/conversion-types | Available conversion types for the caller&#39;s account.
*TrackingApi* | [**apiV1TrackingPixelsGet**](docs/Api/TrackingApi.md#apiv1trackingpixelsget) | **GET** /api/v1/tracking/pixels | List the caller&#39;s conversion-tracking pixels.
*TrackingApi* | [**apiV1TrackingPixelsIdDelete**](docs/Api/TrackingApi.md#apiv1trackingpixelsiddelete) | **DELETE** /api/v1/tracking/pixels/{id} | Delete a pixel.
*TrackingApi* | [**apiV1TrackingPixelsIdGet**](docs/Api/TrackingApi.md#apiv1trackingpixelsidget) | **GET** /api/v1/tracking/pixels/{id} | Get one pixel.
*TrackingApi* | [**apiV1TrackingPixelsIdPut**](docs/Api/TrackingApi.md#apiv1trackingpixelsidput) | **PUT** /api/v1/tracking/pixels/{id} | Update a pixel&#39;s name or conversion type.
*TrackingApi* | [**apiV1TrackingPixelsPost**](docs/Api/TrackingApi.md#apiv1trackingpixelspost) | **POST** /api/v1/tracking/pixels | Create a new tracking pixel.
*URLPoolsApi* | [**apiV1UrlPoolsGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsget) | **GET** /api/v1/url-pools | List URL pools.
*URLPoolsApi* | [**apiV1UrlPoolsIdClonePost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidclonepost) | **POST** /api/v1/url-pools/{id}/clone | Clone a URL pool with all its URLs.
*URLPoolsApi* | [**apiV1UrlPoolsIdDelete**](docs/Api/URLPoolsApi.md#apiv1urlpoolsiddelete) | **DELETE** /api/v1/url-pools/{id} | Soft-delete a URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidget) | **GET** /api/v1/url-pools/{id} | Show one URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidput) | **PUT** /api/v1/url-pools/{id} | Update URL pool meta.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsget) | **GET** /api/v1/url-pools/{id}/urls | List URLs in a pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsPost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlspost) | **POST** /api/v1/url-pools/{id}/urls | Add a URL to a pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdClonePost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidclonepost) | **POST** /api/v1/url-pools/{id}/urls/{urlId}/clone | Clone a single URL within the same pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdDelete**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurliddelete) | **DELETE** /api/v1/url-pools/{id}/urls/{urlId} | Soft-delete a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidget) | **GET** /api/v1/url-pools/{id}/urls/{urlId} | Show a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId} | Update a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdTogglePut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidtoggleput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/toggle | Toggle a URL on / off.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdWeightPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidweightput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/weight | Set a URL&#39;s rotation weight.
*URLPoolsApi* | [**apiV1UrlPoolsIdUsedInGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidusedinget) | **GET** /api/v1/url-pools/{id}/used-in | List campaigns / ads that reference this URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsPost**](docs/Api/URLPoolsApi.md#apiv1urlpoolspost) | **POST** /api/v1/url-pools | Create a URL pool.

## Models

- [ApiKeyDetails](docs/Model/ApiKeyDetails.md)
- [ApiKeyDetailsCreated](docs/Model/ApiKeyDetailsCreated.md)
- [ApiV1AccountGet200Response](docs/Model/ApiV1AccountGet200Response.md)
- [ApiV1AccountGet200ResponseApiKey](docs/Model/ApiV1AccountGet200ResponseApiKey.md)
- [ApiV1AdspacesGet200Response](docs/Model/ApiV1AdspacesGet200Response.md)
- [ApiV1AdspacesGet200ResponseDataInner](docs/Model/ApiV1AdspacesGet200ResponseDataInner.md)
- [ApiV1AdspacesGet200ResponseDataInnerSite](docs/Model/ApiV1AdspacesGet200ResponseDataInnerSite.md)
- [ApiV1CampaignsGet200Response](docs/Model/ApiV1CampaignsGet200Response.md)
- [ApiV1CampaignsGet200ResponseDataInner](docs/Model/ApiV1CampaignsGet200ResponseDataInner.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200ResponseZoneBidsInner](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200ResponseZoneBidsInner.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequestZoneBidsInner](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequestZoneBidsInner.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsGet200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsGet200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsPostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsPostRequest.md)
- [ApiV1CampaignsTypeIdActivePatch200Response](docs/Model/ApiV1CampaignsTypeIdActivePatch200Response.md)
- [ApiV1CampaignsTypeIdActivePatchRequest](docs/Model/ApiV1CampaignsTypeIdActivePatchRequest.md)
- [ApiV1CampaignsTypeIdBidsPatch200Response](docs/Model/ApiV1CampaignsTypeIdBidsPatch200Response.md)
- [ApiV1CampaignsTypeIdBidsPatchRequest](docs/Model/ApiV1CampaignsTypeIdBidsPatchRequest.md)
- [ApiV1CampaignsTypeIdClonePost201Response](docs/Model/ApiV1CampaignsTypeIdClonePost201Response.md)
- [ApiV1CampaignsTypeIdClonePostRequest](docs/Model/ApiV1CampaignsTypeIdClonePostRequest.md)
- [ApiV1CampaignsTypeIdDelete200Response](docs/Model/ApiV1CampaignsTypeIdDelete200Response.md)
- [ApiV1CampaignsTypeIdFiltersGet200Response](docs/Model/ApiV1CampaignsTypeIdFiltersGet200Response.md)
- [ApiV1CampaignsTypeIdFiltersGet200ResponseFiltersInner](docs/Model/ApiV1CampaignsTypeIdFiltersGet200ResponseFiltersInner.md)
- [ApiV1CampaignsTypeIdFiltersPost200Response](docs/Model/ApiV1CampaignsTypeIdFiltersPost200Response.md)
- [ApiV1CampaignsTypeIdFiltersPostRequest](docs/Model/ApiV1CampaignsTypeIdFiltersPostRequest.md)
- [ApiV1CampaignsTypeIdMinBidGet200Response](docs/Model/ApiV1CampaignsTypeIdMinBidGet200Response.md)
- [ApiV1CampaignsTypePostRequest](docs/Model/ApiV1CampaignsTypePostRequest.md)
- [ApiV1CampaignsTypeSchemaAdsGet200Response](docs/Model/ApiV1CampaignsTypeSchemaAdsGet200Response.md)
- [ApiV1CampaignsTypeSchemaGet200Response](docs/Model/ApiV1CampaignsTypeSchemaGet200Response.md)
- [ApiV1CampaignsTypeSchemaGet200ResponseFieldsInner](docs/Model/ApiV1CampaignsTypeSchemaGet200ResponseFieldsInner.md)
- [ApiV1CreativePoolsGet200Response](docs/Model/ApiV1CreativePoolsGet200Response.md)
- [ApiV1CreativePoolsGet200ResponseDataInner](docs/Model/ApiV1CreativePoolsGet200ResponseDataInner.md)
- [ApiV1CreativePoolsIdClonePost201Response](docs/Model/ApiV1CreativePoolsIdClonePost201Response.md)
- [ApiV1CreativePoolsIdDelete200Response](docs/Model/ApiV1CreativePoolsIdDelete200Response.md)
- [ApiV1CreativePoolsIdDelete409Response](docs/Model/ApiV1CreativePoolsIdDelete409Response.md)
- [ApiV1CreativePoolsIdGet200Response](docs/Model/ApiV1CreativePoolsIdGet200Response.md)
- [ApiV1CreativePoolsIdItemsItemIdPutRequest](docs/Model/ApiV1CreativePoolsIdItemsItemIdPutRequest.md)
- [ApiV1CreativePoolsIdPutRequest](docs/Model/ApiV1CreativePoolsIdPutRequest.md)
- [ApiV1CreativePoolsIdUsedInGet200Response](docs/Model/ApiV1CreativePoolsIdUsedInGet200Response.md)
- [ApiV1CreativePoolsPostRequest](docs/Model/ApiV1CreativePoolsPostRequest.md)
- [ApiV1FiltersGet200Response](docs/Model/ApiV1FiltersGet200Response.md)
- [ApiV1FiltersPostRequest](docs/Model/ApiV1FiltersPostRequest.md)
- [ApiV1FiltersTypeIdActivePatch200Response](docs/Model/ApiV1FiltersTypeIdActivePatch200Response.md)
- [ApiV1FiltersTypeIdItemsItemDelete200Response](docs/Model/ApiV1FiltersTypeIdItemsItemDelete200Response.md)
- [ApiV1FiltersTypeIdItemsPost200Response](docs/Model/ApiV1FiltersTypeIdItemsPost200Response.md)
- [ApiV1FiltersTypeIdItemsPostRequest](docs/Model/ApiV1FiltersTypeIdItemsPostRequest.md)
- [ApiV1FundsGet200Response](docs/Model/ApiV1FundsGet200Response.md)
- [ApiV1RetargetingPixelsGet200Response](docs/Model/ApiV1RetargetingPixelsGet200Response.md)
- [ApiV1RetargetingPixelsIdActivePatch200Response](docs/Model/ApiV1RetargetingPixelsIdActivePatch200Response.md)
- [ApiV1RetargetingPixelsIdPutRequest](docs/Model/ApiV1RetargetingPixelsIdPutRequest.md)
- [ApiV1RetargetingPixelsPostRequest](docs/Model/ApiV1RetargetingPixelsPostRequest.md)
- [ApiV1SitesGet200Response](docs/Model/ApiV1SitesGet200Response.md)
- [ApiV1SitesGet200ResponseDataInner](docs/Model/ApiV1SitesGet200ResponseDataInner.md)
- [ApiV1TrackingConversionTypesGet200Response](docs/Model/ApiV1TrackingConversionTypesGet200Response.md)
- [ApiV1TrackingPixelsGet200Response](docs/Model/ApiV1TrackingPixelsGet200Response.md)
- [ApiV1TrackingPixelsIdPutRequest](docs/Model/ApiV1TrackingPixelsIdPutRequest.md)
- [ApiV1TrackingPixelsPostRequest](docs/Model/ApiV1TrackingPixelsPostRequest.md)
- [ApiV1UrlPoolsIdPutRequest](docs/Model/ApiV1UrlPoolsIdPutRequest.md)
- [ApiV1UrlPoolsIdUrlsPostRequest](docs/Model/ApiV1UrlPoolsIdUrlsPostRequest.md)
- [ApiV1UrlPoolsIdUrlsUrlIdPutRequest](docs/Model/ApiV1UrlPoolsIdUrlsUrlIdPutRequest.md)
- [ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest](docs/Model/ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest.md)
- [ApiV1UrlPoolsPostRequest](docs/Model/ApiV1UrlPoolsPostRequest.md)
- [Error](docs/Model/Error.md)
- [FilterDetail](docs/Model/FilterDetail.md)
- [FilterDetailAllOfRanges](docs/Model/FilterDetailAllOfRanges.md)
- [FilterSummary](docs/Model/FilterSummary.md)
- [ResourceFilter](docs/Model/ResourceFilter.md)
- [RetargetingPixel](docs/Model/RetargetingPixel.md)
- [StatsResponse](docs/Model/StatsResponse.md)
- [TrackingPixel](docs/Model/TrackingPixel.md)

## Authorization

Authentication schemes defined for the API:
### apiKey

- **Type**: Bearer authentication (gas_live_<32 chars>)

## Tests

To run the tests, use:

```bash
composer install
vendor/bin/phpunit
```

## Author



## About this package

This PHP package is automatically generated by the [OpenAPI Generator](https://openapi-generator.tech) project:

- API version: `1.0.0`
    - Generator version: `7.21.0`
- Build package: `org.openapitools.codegen.languages.PhpClientCodegen`

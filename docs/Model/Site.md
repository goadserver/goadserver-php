# Site

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**url** | **string** |  | [optional]
**description** | **string** |  | [optional]
**typeid** | **int** |  | [optional]
**main_catid** | **int** |  | [optional]
**categories** | **int[]** |  | [optional]
**sitelanguages** | **string[]** |  | [optional]
**postbacks** | **mixed** |  | [optional]
**ad_domainid** | **int** |  | [optional]
**domainid** | **int** | Resolved tracker_domains.id for the site URL. | [optional]
**is_verified** | **bool** | True after the publisher completes the verification flow. | [optional]
**is_deleted** | **bool** |  | [optional]
**date_added** | **\DateTime** |  | [optional]
**tags** | **int[]** | Site groups (campaign_groups ids). | [optional]
**sitestate** | **int** | Review state: 1 &#x3D; validated, 0 &#x3D; pending review, -1 &#x3D; rejected. | [optional]
**sitestate_name** | **string** |  | [optional]
**isvalid** | **bool** | Approved by staff (or auto-validated). | [optional]
**rejectedreason** | **string** | Why staff rejected the site (meaningful when sitestate &#x3D; -1). | [optional]
**isapp** | **bool** | A mobile app (see SiteCreate.isapp); url is then its .inapp pseudo-domain. | [optional]
**app_bundle** | **string** |  | [optional]
**app_store_url** | **string** |  | [optional]
**app_platform** | **string** |  | [optional]
**verify_code** | **string** | MD5 of (userid + \&quot;_\&quot; + url) — the unique token the publisher must include in the verify meta tag. Only present when need_verification is on. | [optional]
**verify_tag_prefix** | **string** | Tag-name prefix the verify meta tag uses (default \&quot;ero_verify\&quot;). Only present when need_verification is on. | [optional]
**verify_meta_tag** | **string** | Pre-rendered &lt;meta&gt; tag the publisher pastes on the site root before POSTing /verify. Only present when need_verification is on. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

# ApiV1CampaignsTypeCampaignIdAdsPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**title** | **string** |  |
**bid** | **float** | Currency units of your account currency, like the panel (see the bid endpoint for the floor and the bid_unit contract). |
**destination_url** | **string** |  | [optional]
**description** | **string** |  | [optional]
**display_url** | **string** | Required on native (3), push (7), offer (21) and search ads (42) campaigns. | [optional]
**preview_url** | **string** |  | [optional]
**is_active** | **bool** |  | [optional] [default to true]
**sort_weight** | **int** |  | [optional] [default to 0]
**banner_pool_id** | **int** | Reference an existing banner pool (most common). | [optional]
**video_pool_id** | **int** |  | [optional]
**url_pool_id** | **int** | Independent click-URL rotation pool — combinable with any banner ref. | [optional]
**creative_id** | **int** | Reference a single creative directly. | [optional]
**banner_url** | **string** | Direct image URL (hosted-elsewhere creatives). On a native campaign this is auto-routed to &#x60;creatives.image&#x60; — but for clarity prefer &#x60;native_image_url&#x60; when uploading a native creative by URL. | [optional]
**banner_html** | **string** | Raw HTML/JS payload (tag-based ad units). Click tracking: the HTML must send its clicks through the ad server with &#x60;{click_url}&#x60; — &#x60;{click_url}https://landing…&#x60; counts the click and redirects to that landing; a bare &#x60;{click_url}&#x60; redirects to &#x60;destination_url&#x60;. Revive&#39;s &#x60;{clickurl}&#x60; is not interpreted and passes through unchanged. URL macros such as &#x60;{spaceid}&#x60;, &#x60;{siteid}&#x60;, &#x60;{domain}&#x60;, &#x60;{page}&#x60;, &#x60;{adid}&#x60;, &#x60;{timestamp}&#x60; are filled in the HTML as well. | [optional]
**native_image_url** | **string** | Direct image URL for a NATIVE ad — creates a native ad in one call with no creative pool. The native headline/body come from &#x60;title&#x60; + &#x60;description&#x60;; &#x60;destination_url&#x60; is the click target. | [optional]
**banner_iframe_url** | **string** | Iframe banner — the banner is rendered by loading this URL in an iframe. | [optional]
**video_url** | **string** | Video creative URL: a VAST tag with &#x60;video_type: xml&#x60;, otherwise a hotlinked video file (duration + bitrate are measured on save). | [optional]
**video_type** | **string** | Kind of &#x60;video_url&#x60;. Audio ads (adtype 43) use &#x60;url&#x60; with a hotlinked https audio file, or video_type&#x3D;creative + creative_id of an &#x60;audio&#x60; creative (POST /creatives/upload type&#x3D;audio). | [optional]
**companion_url** | **string** | Audio ads (adtype 43): an https companion image shown while the ad plays (VAST CompanionAds; the goCtrl audio plugin&#39;s bar). Stored as creatives.image {type: url}. | [optional]
**companion_width** | **int** |  | [optional] [default to 300]
**companion_height** | **int** |  | [optional] [default to 250]
**image_pool_id** | **int** | Native / skin / offer / creator / search ads (adtype 3, 18, 21, 22, 42): fill the image from a creative pool (the panel&#39;s pool mode). Creator ads are refused when the pool has no creative of the campaign&#39;s orientation. | [optional]
**html_code** | **string** | Native ads (adtype 3): whole-spot HTML replacing the image grid (the only mode native SmartAds accept). Video ads (adtype 4 / 20): a self-rendering HTML video snippet; needs a Video Slider / In-page Video zone in the campaign&#39;s zone whitelist. Not valid for other ad types (use &#x60;banner_html&#x60;). Click tracking: the HTML must send its clicks through the ad server with &#x60;{click_url}&#x60; — &#x60;{click_url}https://landing…&#x60; counts the click and redirects to that landing; a bare &#x60;{click_url}&#x60; redirects to &#x60;destination_url&#x60;. Revive&#39;s &#x60;{clickurl}&#x60; is not interpreted and passes through unchanged. URL macros such as &#x60;{spaceid}&#x60;, &#x60;{siteid}&#x60;, &#x60;{domain}&#x60;, &#x60;{page}&#x60;, &#x60;{adid}&#x60;, &#x60;{timestamp}&#x60; are filled in the HTML as well. | [optional]
**html_total_items** | **int** | With native &#x60;html_code&#x60;: the number of native items the block renders; the ad only serves into zones asking for exactly this many. | [optional] [default to 1]
**icon_id** | **int** | Push ads (adtype 7): notification icon — a creative from your library (POST /creatives/upload with &#x60;type&#x3D;icon&#x60;). Stored as &#x60;creatives.icon&#x60; + the &#x60;icon_id&#x60; column. | [optional]
**icon_type** | **string** | Optional; the only icon mode is a library creative. | [optional]
**use_imptracking** | **bool** | Fire &#x60;imptrackingurl&#x60; as a third-party impression pixel (ron / cpm ads). | [optional] [default to false]
**imptrackingurl** | **string** | Third-party impression tracking URL (http/https). | [optional]
**usekeywords** | **bool** | Ad-level keyword targeting (ron / cpm ads). Forced off when &#x60;keywords&#x60; is empty. | [optional] [default to false]
**keywords** | **string** | Comma- or newline-separated keywords. Trimmed, lowercased and deduplicated on save. | [optional]
**ad_options** | **object** | SmartAds options &#x60;{fixed_position, smart_options}&#x60; (object or JSON string; ron ads). | [optional]
**smart_network_id** | **int** | SmartAds: the smart network this ad buys through (ron ads). | [optional]
**chat_followup** | **string** | Search Ads (adtype 42) ron ads: a sponsored follow-up question for AI chat zones (chat_followups &gt; 0); picking it asks the chat that question and counts as a click. Stored in ad_options.chat.followup (merged — other ad_options keys are kept); empty string clears. | [optional]
**chat_price** | **string** | Search Ads ron ads: the price on AI chat product cards (chat_style products). Stored in ad_options.chat.price; product feeds fill it. | [optional]
**keyword_mode** | **string** | Search Ads (adtype 42) ron ads: how the ad&#39;s own keywords (usekeywords + keywords) act on a search. specific &#x3D; shown only when a keyword matches the search; preference &#x3D; shown for any search and ranked first when a keyword matches, at most 6 keywords (422 on keywords otherwise). Stored as ad_options.keyword_preference (true, or absent &#x3D; specific); other ad_options keys are kept. Other zone types always treat ad keywords as specific. | [optional] [default to 'specific']
**video_overlays** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner[]**](ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner.md) | Shoppable video (ron video ads, adtype 4 / 20): timed hotspots and product cards over the ad. A click counts as the ad&#39;s click and lands on the overlay&#39;s url (empty &#x3D; the ad&#39;s destination). Served to goCtrl&#39;s video players and in VAST (Extension goadserver-overlays + a SIMID InteractiveCreativeFile, VAST 4.1). Stored in ad_options.overlays (merged — other ad_options keys are kept); [] clears. Per-overlay shown / clicks: GET /campaigns/{type}/{campaignId}/ads/{adId}/overlay-stats. | [optional]
**position** | **int** |  | [optional]
**displaytype** | **string** | Video ads: comma-separated placements — 1 pre-roll, 2 mid-roll, 3 post-roll, 4 in-video slider, 5 interstitial, 6 in-page, 7 video carousel; \&quot;0\&quot; &#x3D; all. | [optional]
**properties** | **string** | Ad properties blob, stored as sent. | [optional]
**bansizeid** | **int** | Auto-derived for pool/creative refs; required for url/html on size-aware adtypes. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

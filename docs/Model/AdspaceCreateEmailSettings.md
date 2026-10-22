# AdspaceCreateEmailSettings

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**slots** | **int** | Ad slots in the newsletter, each its own image + link (clamped). | [optional] [default to 1]
**merge_tag** | **string** | Your email tool&#39;s per-recipient merge tag, written into the code as rid. Use a recipient id, not the address. No spaces, quotes, &lt; &gt; or &amp;; anything else falls back to the default. | [optional] [default to '{{email_id}}']
**label** | **string** | Disclosure above each slot and the image alt text; plain text (empty &#x3D; the default). | [optional] [default to 'Advertisement']
**ttl_days** | **int** | Days the ad picked for a recipient&#39;s open is kept (clamped). | [optional] [default to 7]
**fallback_link** | **string** | http(s) URL a click goes to when no ad can be picked; anything else is stored empty. | [optional] [default to '']

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

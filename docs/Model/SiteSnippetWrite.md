# SiteSnippetWrite

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**snippet_key** | **string** | Your label for the snippet. | [optional]
**tag_type** | **string** |  |
**position** | **string** |  |
**enabled** | **bool** |  | [optional] [default to true]
**attrs** | **array<string,string>** | Tag attributes, max 20, each value max 2048 chars. Only the attributes the tag type allows (e.g. meta: name/content/property/http-equiv/charset/itemprop; script: src/type/async/defer/crossorigin/integrity/nonce/id). src/href must be http(s) or protocol-relative. | [optional]
**content** | **string** | Inline body for script/style; empty for meta/link. | [optional]
**conditions** | [**\GoAdServerApi\Model\SiteSnippetWriteConditions**](SiteSnippetWriteConditions.md) |  | [optional]
**sortorder** | **int** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

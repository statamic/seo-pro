<template>
    <div>
        <dropdown-list>
            <dropdown-item
                v-if="canEditLinkCollections"
                v-text="__('seo-pro::messages.collection_linking_behavior')"
                :redirect="cp_url('seo-pro/links/config/collections')"
            />
            <dropdown-item
                v-if="canEditLinkSites"
                v-text="__('seo-pro::messages.site_linking_behavior')"
                :redirect="cp_url('seo-pro/links/config/sites')"
            />
            <div v-if="canEditLinkCollections || canEditLinkSites" class="divider"></div>
            <dropdown-item
                v-text="__('seo-pro::messages.global_automatic_links')"
                :redirect="cp_url('seo-pro/links/automatic')"
            />
        </dropdown-list>
    </div>
</template>

<script>
export default {

    props: [
        'canEditLinkCollections',
        'canEditLinkSites',
    ],

}
</script>
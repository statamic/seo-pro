<script>
export default {

    methods: {

        makeAutomaticLinkResource(link) {
            return {
                title: link.link_text,
                delete_url: cp_url(`seo-pro/links/automatic/${link.id}`),
            };
        },

        makeEntrySettingsResource(entry) {
            const id = data_get(entry, 'entry_id', data_get(entry, 'id', null));

            return {
                title: entry.title,
                reset_url: cp_url(`seo-pro/links/link/${id}`),
            };
        },

        makeSiteSettingsResource(site) {
            return {
                title: site.name,
                reset_url: cp_url(`seo-pro/links/config/sites/${site.handle}`),
            };
        },

        makeCollectionSettingsResource(collection) {
            return {
                title: collection.title,
                reset_url: cp_url(`seo-pro/links/config/collections/${collection.handle}`),
            };
        },

    },

};
</script>
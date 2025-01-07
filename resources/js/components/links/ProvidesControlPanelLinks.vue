<script>
export default {

    methods: {

        makeSuggestionsUrl(entryId) {
            return cp_url(`seo-pro/links/${entryId}/suggestions`);
        },

        makeRelatedUrl(entryId) {
            return cp_url(`seo-pro/links/${entryId}/related`);
        },

        makeInternalLinksUrl(entryId) {
            return cp_url(`seo-pro/links/${entryId}/internal`);
        },

    },

}
</script>
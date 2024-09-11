<template>
    <div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-scroll">
        <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
            <h1 class="flex-1 flex items-center text-xl">
                Site Settings
            </h1>

            <button
                type="button"
                class="btn-close"
                @click="closeEditor"
                v-html="'&times'"
            />
        </header>

        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <h2 class="flex-1">
                    {{ site.name }}
                </h2>

                <publish-container
                    class="mb-6"
                    name="site-settings"
                    :blueprint="blueprint"
                    :meta="meta"
                    :values="siteData"
                    @updated="updateValues"
                >
                    <div slot-scope="{ setFieldValue, setFieldMeta }">
                        <publish-fields
                            :fields="fields"
                            @updated="setFieldValue"
                            @meta-updated="setFieldMeta"
                        />
                    </div>
                </publish-container>

                <button
                    class="btn-primary w-full"
                    @click="saveSiteSettings"
                    :class="{ 'opacity-50': false }"
                    v-text="__('Save')" />
            </div>
        </div>
    </div>
</template>

<script>

export default {

    props: [
        'blueprint',
        'fields',
        'meta',
        'values',
        'site',
    ],

    data() {
        return {
            initialValues: _.clone(this.values),
            siteData: _.clone(this.values),
        };
    },

    methods: {

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.siteData = _.clone(values);
        },

        saveSiteSettings() {
            this.$axios.put(cp_url(`seo-pro/links/config/sites/${this.site.handle}`), this.siteData).then(response => {
                this.$emit('saved');
            }).catch(err => {
            });
        },

    },

    mounted() {
        if (! this.site) {
            this.siteData = _.clone(this.initialValues);

            return;
        }

        this.siteData = {
            ignored_phrases: this.site.ignored_phrases,
            keyword_threshold: this.site.keyword_threshold,
            min_internal_links: this.site.min_internal_links,
            max_internal_links: this.site.max_internal_links,
            min_external_links: this.site.min_external_links,
            max_external_links: this.site.max_external_links,
        };
    },

};
</script>
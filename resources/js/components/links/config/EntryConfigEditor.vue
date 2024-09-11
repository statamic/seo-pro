<template>
    <div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-scroll">
        <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
            <h1 class="flex-1 flex items-center text-xl">
                Entry Settings
            </h1>

            <button
                type="button"
                class="btn-close"
                @click="closeEditor"
                v-html="'&times'"
            />
        </header>

        <div v-if="loading" class="loading">
            <loading-graphic />
        </div>

        <div class="flex-1 overflow-auto" v-if="!loading">
            <div class="p-6">
                <publish-container
                    class="mb-6"
                    name="sit-settings"
                    :blueprint="blueprint"
                    :meta="meta"
                    :values="entryData"
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
                    @click="saveEntrySettings"
                    :class="{ 'opacity-50': false }"
                    v-text="__('Save')" />
            </div>
        </div>
    </div>
</template>

<script>

export default {

    props: [
        'entry',
        'blueprint',
        'fields',
        'meta',
        'values',
    ],

    data() {
        return {
            entryData: _.clone(this.values),
            loading: false,
        };
    },

    methods: {

        getLinkUrl() {
            return cp_url(`seo-pro/links/link/${this.entry}`);
        },

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.entryData = _.clone(values);
        },

        saveEntrySettings() {
            this.$axios.put(this.getLinkUrl(), this.entryData).then(response => {
                this.$emit('saved');
            });
        },

        getEntrySettings() {
            this.loading = true;
            this.$axios.get(this.getLinkUrl()).then(response => {
                this.entryData = {
                    can_be_suggested: response.data.can_be_suggested,
                    include_in_reporting: response.data.include_in_reporting
                };

                this.loading = false;
            });
        },

    },

    mounted() {
        this.getEntrySettings();
    },

};
</script>
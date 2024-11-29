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
                    :errors="errors"
                    name="entry-settings"
                    :blueprint="blueprint"
                    :meta="editMeta"
                    :values="values"
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
import HandlesRequestErrors from './../HandlesRequestErrors.vue';

export default {
    mixins: [HandlesRequestErrors],

    props: [
        'entry',
        'blueprint',
        'fields',
        'meta',
        'values',
    ],

    data() {
        return {
            errors: {},
            editMeta: null,
            values: [],
            updatedValues: [],
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
            this.updatedValues = _.clone(values);
        },

        saveEntrySettings() {
            this.$axios.put(this.getLinkUrl(), this.updatedValues).then(response => {
                this.$emit('saved');
            }).catch(err => this.handleAxiosError(err));
        },

        getEntrySettings() {
            this.loading = true;
            this.$axios.get(this.getLinkUrl()).then(response => {
                this.meta = response.data.meta;
                this.values = response.data.values;
                this.updatedValues = _.clone(this.values);

                this.loading = false;
            }).catch(err => {
                this.loading = false;
                this.handleAxiosError(err);
            });
        },

    },

    mounted() {
        this.editMeta = _.clone(this.meta);
        this.getEntrySettings();
    },

}
</script>
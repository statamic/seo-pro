<template>
    <div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-auto">
        <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
            <h1 class="flex-1 flex items-center text-xl">{{ __('seo-pro::messages.collection_linking_behavior') }}</h1>

            <button
                type="button"
                class="btn-close"
                @click="closeEditor"
                v-html="'&times'"
            />
        </header>

        <div v-if="loading" class="flex h-full text-center items-center justify-center">
            <loading-graphic />
        </div>

        <div class="flex-1 overflow-auto" v-if="!loading">
            <div class="p-6">
                <h2 class="flex-1">{{ collection.title }}</h2>

                <publish-container
                    class="mb-6"
                    name="collection-behavior"
                    :errors="errors"
                    :blueprint="blueprint"
                    :meta="editMeta"
                    :values="values"
                    :site="site"
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
                    :disabled="saving"
                    @click="saveCollectionBehavior"
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
        'blueprint',
        'fields',
        'meta',
        'collection',
    ],

    data() {
        return {
            loading: true,
            errors: {},
            editMeta: null,
            values: [],
            updatedValues: [],
            saving: false,
        };
    },

    watch: {

        saving(saving) {
            this.$progress.loading(saving);
        },

    },

    computed: {

        site() {
            return this.$config.get('selectedSite');
        },

    },

    methods: {

        collectionUrl() {
            return cp_url(`seo-pro/links/config/collections/${this.collection.handle}`);
        },

        getValues() {
            this.loading = true;
            this.$axios.get(this.collectionUrl()).then(response => {
                this.meta = response.data.meta;
                this.values = response.data.values;
                this.updatedValues = _.clone(this.values);

                this.loading = false;
            }).catch(err => {
                this.handleAxiosError(err);
                this.loading = false;
            });
        },

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.updatedValues = _.clone(values);
        },

        saveCollectionBehavior() {
            this.saving = true;
            this.$axios.put(this.collectionUrl(), this.updatedValues).then(response => {
                this.$emit('saved');
                this.$toast.success(__('seo-pro::messages.collection_settings_updated'));
                this.saving = false;
            }).catch(err => {
                this.handleAxiosError(err);
                this.saving = false;
            });
        },

    },

    mounted() {
        this.editMeta = _.clone(this.meta);
        this.getValues();
    },

}
</script>
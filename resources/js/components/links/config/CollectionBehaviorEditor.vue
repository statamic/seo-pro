<template>
    <div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-scroll">
        <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
            <h1 class="flex-1 flex items-center text-xl">
                Collection Behavior
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
                    {{ collection.title }}
                </h2>

                <publish-container
                    class="mb-6"
                    name="collection-behavior"
                    :blueprint="blueprint"
                    :meta="meta"
                    :values="behaviorData"
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
                    @click="saveCollectionBehavior"
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
        'collection',
    ],

    data() {
        return {
            initialValues: _.clone(this.values),
            behaviorData: _.clone(this.values),
        };
    },

    methods: {

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.behaviorData = _.clone(values);
        },

        saveCollectionBehavior() {
            this.$axios.put(cp_url(`seo-pro/links/config/collections/${this.collection.handle}`), this.behaviorData).then(response => {
                this.$emit('saved');
            }).catch(err => {
            });
        },

    },

    mounted() {
        if (! this.collection) {
            this.behaviorData = _.clone(this.initialValues);

            return;
        }

        this.behaviorData = {
            allow_cross_collection_suggestions: this.collection.allow_cross_collection_suggestions,
            allow_cross_site_linking: this.collection.allow_cross_site_linking,
            allowed_collections: this.collection.allowed_collections,
            linking_enabled: this.collection.linking_enabled,
        };
    },

};
</script>
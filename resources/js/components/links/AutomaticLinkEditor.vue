<template>
    <div class="flex flex-col relative bg-gray-100 dar:bg-dark-800 h-full overflow-scroll">
        <header class="flex items-center sticky top-0 inset-x-0 bg-white dark:bg-dark-550 shadow dark:shadow-dark px-8 py-2 z-1 h-13">
            <h1 class="flex-1 flex items-center text-xl">{{ title }}</h1>

            <button
                type="button"
                class="btn-close"
                @click="closeEditor"
                v-html="'&times'"
            />
        </header>

        <div class="flex-1 overflow-auto">
            <div
                v-if="values"
                class="p-6"
            >
                <publish-container
                    class="mb-6"
                    name="sit-settings"
                    :blueprint="blueprint"
                    :errors="errors"
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
                    @click="updateLink"
                    :class="{ 'opacity-50': false }"
                    v-text="__('Save')" />
            </div>
        </div>
    </div>
</template>

<script>
import HandlesRequestErrors from './HandlesRequestErrors.vue';

export default {
    mixins: [HandlesRequestErrors],

    props: [
        'blueprint',
        'fields',
        'meta',
        'link',
        'initialValues',
        'mode',
    ],

    computed: {

        title() {
            if (this.mode === 'new') {
                return __('seo-pro::messages.create_automatic_link');
            }

            return __('seo-pro::messages.edit_automatic_link');
        },

        site() {
            return this.$config.get('selectedSite');
        },

    },

    data() {
        return {
            errors: {},
            editMeta: null,
            values: null,
            updatedValues: [],
        };
    },

    methods: {

        linkUrl() {
            return cp_url(`seo-pro/links/automatic/${this.link.id}`);
        },

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.updatedValues = _.clone(values);
        },

        isCreatingLink() {
            return this.mode === 'new';
        },

        updateLink() {
            if (this.isCreatingLink()) {
                this.doAddLink();

                return;
            }

            this.doUpdateLink();
        },

        getValues() {
            this.$axios.get(this.linkUrl()).then(response => {
                this.meta = response.data.meta;
                this.values = response.data.values;
                this.updatedValues = _.clone(this.values);
            }).catch(err => this.handleAxiosError(err));
        },

        doAddLink() {
            const newLink = {
                site: this.site,
                ...this.updatedValues
            };

            this.$axios.post(cp_url('seo-pro/links/automatic'), newLink).then(response => {
                this.closeEditor();
                this.$toast.success(__('seo-pro::messages.global_link_saved'));
            }).catch(err => this.handleAxiosError(err));
        },

        doUpdateLink() {
            if (! this.link) {
                return;
            }

            this.$axios.post(this.linkUrl(), this.updatedValues).then(response => {
                this.closeEditor();
                this.$toast.success(__('seo-pro::messages.global_link_updated'));
            }).catch(err => this.handleAxiosError(err));
        },

    },

    mounted() {
        this.editMeta = _.clone(this.meta);
        this.values = _.clone(this.initialValues);
        this.updatedValues = _.clone(this.values);

        if (! this.isCreatingLink()) {
            this.getValues();
        }
    },

}
</script>
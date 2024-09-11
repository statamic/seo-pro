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
            <div class="p-6">
                <publish-container
                    class="mb-6"
                    name="sit-settings"
                    :blueprint="blueprint"
                    :meta="meta"
                    :values="linkData"
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

export default {

    props: [
        'site',
        'blueprint',
        'fields',
        'meta',
        'values',
        'link',
        'mode',
    ],

    computed: {
        title() {
            if (this.mode === 'new') {
                return 'Create Link';
            }

            return 'Edit Link';
        }
    },

    data() {
        return {
            initialValues: _.clone(this.values),
            linkData: _.clone(this.values),
        };
    },

    methods: {

        closeEditor() {
            this.$emit('closed');
        },

        updateValues(values) {
            this.linkData = _.clone(values);
        },

        updateLink() {
            if (this.mode === 'new') {
                this.doAddLink();

                return;
            }

            this.doUpdateLink();
        },

        doAddLink() {
            const newLink = {
                site: this.site,
                ...this.linkData
            };

            this.$axios.post(cp_url('seo-pro/links/automatic'), newLink).then(response => {
                this.closeEditor();
            }).catch(err => {

            });
        },

        doUpdateLink() {
            if (! this.link) {
                return;
            }

            this.$axios.post(cp_url(`seo-pro/links/automatic/${this.link.id}`), this.linkData).then(response => {
                this.closeEditor();
            }).catch(err => {
            });
        },

    },

    mounted() {
        if (! this.link) {
            this.linkData = _.clone(this.initialValues);

            return;
        }

        this.linkData = _.clone(this.link);
    },

};
</script>
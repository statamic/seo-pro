<template>
    <div class="publish-fields">
        <publish-field
            v-for="field in fields"
            :key="field.handle"
            :config="field"
            :value="value[field.handle]"
            :meta="meta.meta[field.handle]"
            :read-only="! field.localizable"
            :errors="errors(field.handle)"
            class="form-group"
            @meta-updated="metaUpdated(field.handle, $event)"
            @focus="$emit('focus')"
            @blur="$emit('blur')"
            @input="updateKey(field.handle, $event)"
        />
    </div>
</template>

<style>
.seo_pro-fieldtype > .field-inner > label {
    display: none !important;
}

.seo_pro-fieldtype,
.seo_pro-fieldtype .publish-fields {
    padding: 0 !important;
}
</style>

<script>
export default {

    mixins: [Fieldtype],

    inject: ['storeName'],

    computed: {
        fields() {
            return _.chain(this.meta.fields)
                .map(field => {
                    return {
                        handle: field.handle,
                        ...field.field
                    };
                })
                .values()
                .value();
        }
    },

    methods: {
        updateKey(handle, value) {
            let seoValue = this.value;

            Vue.set(seoValue, handle, value);

            this.update(seoValue);
        },

        metaUpdated(handle, value) {
            this.$emit('meta-updated', {
                ...this.meta,
                meta: {
                    ...this.meta.meta,
                    [handle]: value,
                },
            });
        },

        errors(handle) {
            const state = this.$store.state.publish[this.storeName];
            if (!state) return [];
            return state.errors['seo.'+handle] || [];
        },
    },

}
</script>

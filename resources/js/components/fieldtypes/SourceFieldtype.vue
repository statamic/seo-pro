<template>

    <div class="flex">

        <div class="source-type-select pr-4">
            <v-select
                :options="sourceTypeSelectOptions"
                :reduce="option => option.value"
                :disabled="! config.localizable"
                :clearable="false"
                :value="source"
                @input="sourceDropdownChanged"
            />
        </div>

        <div class="flex-1">
            <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder mt-1">
                <template v-if="placeholder !== false">
                    {{ placeholder }}
                </template>
            </div>

            <div v-else-if="source === 'field'" class="source-field-select">
                <!-- TODO: Implement field suggestions v-select -->
                <text-input :value="sourceField" @input="sourceFieldChanged" :disabled="! config.localizable" />
            </div>

            <component
                v-else-if="source === 'custom'"
                :is="componentName"
                :name="name"
                :config="fieldConfig"
                :value="value.value"
                :meta="meta.fieldMeta"
                :read-only="! config.localizable"
                handle="source_value"
                @input="customValueChanged">
            </component>
        </div>
    </div>
</template>

<style>
    .source-type-select {
        width: 20rem;
    }

    .inherit-placeholder {
        padding-top: 5px;
    }

    .source-field-select .selectize-dropdown,
    .source-field-select .selectize-input span {
        font-family: 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace';
        font-size: 12px;
    }
</style>

<script>
export default {

    mixins: [Fieldtype],

    data() {
        return {
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false,
            allowedFieldtypes: []
        }
    },

    computed: {

        source() {
            return this.value.source;
        },

        sourceField() {
            return this.value.source === 'field'
                ? this.value.value
                : null;
        },

        componentName() {
            return this.config.field.type.replace('.', '-') + '-fieldtype';
        },

        sourceTypeSelectOptions() {
            let options = [];

            if (this.config.field !== false) {
                options.push({ label: __('seo-pro::messages.custom'), value: 'custom' });
            }

            if (this.config.from_field !== false) {
                options.unshift({ label: __('seo-pro::messages.from_field'), value: 'field' });
            }

            if (this.config.inherit !== false) {
                options.unshift({ label: __('seo-pro::messages.inherit'), value: 'inherit' });
            }

            if (this.config.disableable) {
                options.push({ label: __('seo-pro::messages.disable'), value: 'disable' });
            }

            return options;
        },

        fieldConfig() {
            return Object.assign(this.config.field, { placeholder: this.config.placeholder });
        },

        placeholder() {
            return this.config.placeholder;
        },

    },

    mounted() {
        let types = this.config.allowed_fieldtypes || ['text', 'textarea', 'markdown', 'redactor'];
        this.allowedFieldtypes = types.concat(this.config.merge_allowed_fieldtypes || []);
        // this.bindChangeWatcher();
    },

    methods: {

        sourceDropdownChanged(value) {
            let newValue = this.value;

            newValue.source = value;

            if (value !== 'field') {
                newValue.value = this.meta.defaultValue;
                this.$emit('meta-updated', {...this.meta, fieldMeta: this.meta.defaultFieldMeta});
            }

            this.update(newValue);
        },

        sourceFieldChanged(field) {
            this.update({...this.value, value: field});
        },

        customValueChanged(value) {
            this.update({...this.value, value});
        },

    },

}
</script>

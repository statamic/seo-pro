<template>
    <div class="flex">

        <div class="source-type-select pr-2">
            <v-select
                :options="sourceTypeSelectOptions"
                :reduce="option => option.value"
                :disabled="! config.localizable"
                :clearable="false"
                v-model="source"
            />
        </div>

        <div class="flex-1">
            <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder">
                <template v-if="placeholder !== false">
                    {{ placeholder }}
                </template>
            </div>

            <div v-else-if="source === 'field'" class="source-field-select">
                <!-- TODO: Implement field suggestions v-select -->
                <text-input v-model="sourceField" :disabled="! config.localizable" />
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
                @input="updateValue">
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
            source: this.value.source,
            sourceField: this.value.source === 'field' ? this.value.value : null,
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false,
            allowedFieldtypes: []
        }
    },

    computed: {

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

    watch: {

        source(val) {
            this.value.source = val;

            if (val === 'field') {
                this.value.value = Array.isArray(this.sourceField) ? this.sourceField[0] : this.sourceField;
            } else {
                this.value.value = this.meta.defaultValue;
            }
        },

        sourceField(val) {
            this.value.value = Array.isArray(val) ? val[0] : val;
        },

    },

    mounted() {
        let types = this.config.allowed_fieldtypes || ['text', 'textarea', 'markdown', 'redactor'];
        this.allowedFieldtypes = types.concat(this.config.merge_allowed_fieldtypes || []);
        // this.bindChangeWatcher();
    },

    methods: {

        updateValue(value) {
            let newValue = this.value;

            newValue.value = value;

            this.update(newValue);
        },

    },

}
</script>

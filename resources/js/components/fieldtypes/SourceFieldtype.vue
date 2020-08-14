<template>
    <div class="flex">

        <div class="source-type-select pr-2">
            <v-select
                :options="sourceTypeSelectOptions"
                :reduce="option => option.value"
                :disabled="isReadOnly"
                v-model="source"
            />
        </div>

        <div class="flex-1">
            <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder">
                {{ config.placeholder }}
            </div>

            <div v-else-if="source === 'field'" class="source-field-select">
                <!-- TODO: Implement field suggestions v-select -->
                <text-input v-model="sourceField" :disabled="isReadOnly" />
            </div>

            <component
                v-else-if="source === 'custom'"
                :is="componentName"
                :name="name"
                :config="fieldConfig"
                :value="value.value"
                :meta="meta.fieldMeta"
                :read-only="isReadOnly"
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
            source: null,
            sourceField: null,
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
                options.push({ label: 'Custom', value: 'custom' });
            }

            if (this.config.from_field !== false) {
                options.unshift({ label: 'From Field', value: 'field' });
            }

            if (this.config.inherit !== false) {
                options.unshift({ label: 'Inherit', value: 'inherit' });
            }

            if (this.config.disableable) {
                options.push({ label: 'Disable', value: 'disable' });
            }

            return options;
        },

        fieldConfig() {
            return Object.assign(this.config.field, { placeholder: this.config.placeholder });
        }

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

        if (this.value.source === 'field') {
            this.sourceField = this.value.value;
        }

        this.source = this.value.source;

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

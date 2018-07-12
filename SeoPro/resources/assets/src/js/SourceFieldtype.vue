<template>

    <div class="flex">

        <div class="source-type-select pr-2">
            <select-fieldtype :data.sync="source" :options="sourceTypeSelectOptions"></select-fieldtype>
        </div>

        <div class="flex-1">
            <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder">
                {{ config.placeholder }}
            </div>

            <div v-if="source === 'field'">
                <suggest-fieldtype :data.sync="sourceField" :config="suggestConfig"></suggest-fieldtype>
            </div>

            <component
                v-if="source === 'custom'"
                :is="componentName"
                :name="name"
                :data.sync="customText"
                :config="fieldConfig"
                :leave-alert="true">
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

</style>


<script>
export default {

    mixins: [Fieldtype],

    data() {
        return {
            source: null,
            customText: null,
            sourceField: null,
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false
        }
    },

    computed: {

        componentName() {
            return this.config.field.type.replace('.', '-') + '-fieldtype';
        },

        sourceTypeSelectOptions() {
            let options = [
                { text: 'Custom', value: 'custom' }
            ];

            if (this.config.from_field !== false) {
                options.unshift({ text: 'From Field', value: 'field' });
            }

            if (this.config.inherit !== false) {
                options.unshift({ text: 'Inherit', value: 'inherit' });
            }

            return options;
        },

        suggestConfig() {
            return {
                type: 'suggest',
                mode: 'seo_pro',
                max_items: 1,
                create: true,
            }
        },

        fieldConfig() {
            return Object.assign(this.config.field, { placeholder: this.config.placeholder });
        }

    },

    watch: {

        source(val) {
            this.data.source = val;

            if (val === 'field') {
                this.data.value = Array.isArray(this.sourceField) ? this.sourceField[0] : this.sourceField;
            } else {
                this.data.value = this.customText;
            }
        },

        sourceField(val) {
            this.data.value = Array.isArray(val) ? val[0] : val;
        },

        customText(val) {
            this.data.value = val;
        }

    },

    ready() {
        if (this.data.source === 'field') {
            this.sourceField = [this.data.value];
        } else {
            this.customText = this.data.value;
        }

        // Set source after so that the suggest fields don't load before they potentially have data.
        this.source = this.data.source;

        this.bindChangeWatcher();
    }

}
</script>

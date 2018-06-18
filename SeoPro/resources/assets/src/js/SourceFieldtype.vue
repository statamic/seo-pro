<template>

    <div class="flex">

        <div class="source-type-select pr-2">
            <select-fieldtype :data.sync="source" :options="sourceTypeSelectOptions"></select-fieldtype>
        </div>

        <div class="flex-1">
            <div v-if="data.source === 'field'">
                <suggest-fieldtype :data.sync="sourceField" :config="suggestConfig"></suggest-fieldtype>
            </div>

            <component
                v-if="data.source === 'custom'"
                :is="componentName"
                :name="name"
                :data.sync="customText"
                :config="config"
                :leave-alert="true">
            </component>
        </div>
    </div>

</template>


<style>

    .source-type-select {
        width: 20rem;
    }

</style>


<script>
export default {

    mixins: [Fieldtype],

    data() {
        return {
            source: this.data.source,
            customText: null,
            sourceField: null
        }
    },

    computed: {

        componentName() {
            return this.config.field.type.replace('.', '-') + '-fieldtype';
        },

        sourceTypeSelectOptions() {
            return [
                { text: 'From Field', value: 'field' },
                { text: 'Custom Text', value: 'custom' }
            ]
        },

        suggestConfig() {
            return {
                type: 'suggest',
                mode: 'seo_pro',
                max_items: 1,
                create: true,
            }
        },

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
    }

}
</script>

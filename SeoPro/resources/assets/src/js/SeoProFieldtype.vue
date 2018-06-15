<template>

    <publish-fields
        :fields="fields"
        :data.sync="data"
        :regular-title-field="true"
    ></publish-fields>

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

    computed: {

        fields() {
            return _.chain(this.config.fields).map((field, name) => {
                field.name = name;
                return field;
            }).values().value();
        }

    }

}
</script>

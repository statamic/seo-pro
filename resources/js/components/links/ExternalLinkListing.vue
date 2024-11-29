<template>
    <div>
        <div v-if="loading" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!loading"
            ref="data-list"
            :columns="columns"
            :rows="externalLinks"
            :sort="true"
            sort-column="link"
        >
            <div>
                <div class="card internalLinks p-0 relative">
                    <div v-show="externalLinks.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="externalLinks.length"
                        :allow-bulk-actions="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                    >
                        <template slot="cell-link" slot-scope="{ row: item }">
                            <a class="title-index-field inline-flex items-center" :href="item.link" @click.stop target="_blank">
                                <span>{{ item.link }}</span>
                            </a>
                        </template>
                    </data-list-table>
                </div>
            </div>
        </data-list>
    </div>
</template>

<script>
export default  {
    props: [
        'entry',
    ],

    data() {
        return {
            columns: [
                {
                    label: __('seo-pro::messages.external_link'),
                    field: 'link'
                },
            ],
            loading: false,
            externalLinks: [],
        }
    },

    methods: {

        loadData() {
            this.loading = true;

            this.$axios.get(cp_url(`seo-pro/links/${this.entry}/external`)).then(response => {
                this.externalLinks = response.data;
                this.loading = false;
            });
        }

    },

    mounted() {
        this.loadData();
    }
}
</script>
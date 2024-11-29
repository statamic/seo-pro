<template>
    <div>
        <div v-if="loading" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!loading"
            ref="data-list"
            :columns="columns"
            :rows="inboundLinks"
            :sort="true"
            sort-column="uri"
        >
            <div>
                <div class="card internalLinks p-0 relative">
                    <div v-show="inboundLinks.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="inboundLinks.length"
                        :allow-bulk-actions="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                    >
                        <template slot="cell-entry.title" slot-scope="{ row: item }">
                            <a v-if="item.can_edit_entry" class="title-index-field inline-flex items-center" :href="item.entry.edit_url" @click.stop>
                                <span>{{ item.entry.title ?? item.entry.uri }}</span>
                            </a>
                            <span v-else>{{ item.entry.title ?? item.entry.uri }}</span>
                        </template>
                    </data-list-table>
                </div>
            </div>
        </data-list>

    </div>
</template>

<script>
import HandlesRequestErrors from './HandlesRequestErrors.vue';

export default  {
    mixins: [HandlesRequestErrors],

    props: [
        'entry',
    ],

    data() {
        return {
            columns: [
                {
                    label: __('Entry'),
                    field: 'entry.title'
                },
                {
                    label: __('seo-pro::messages.link_target'),
                    field: 'uri'
                },
            ],
            loading: false,
            inboundLinks: [],
        }
    },

    methods: {

        loadData() {
            this.loading = true;

            this.$axios.get(cp_url(`seo-pro/links/${this.entry}/inbound`)).then(response => {
                this.inboundLinks = response.data;
                this.loading = false;
            }).catch(err => {
                this.loading = false;
                this.handleAxiosError(err);
            });
        },

    },

    mounted() {
        this.loadData();
    },

}
</script>
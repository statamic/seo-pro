<template>

    <div>

        <div class="flex items-center mb-3">
            <h1 class="flex-1">Pulse</h1>
        </div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <div v-else>

            <div class="card flush dossier">
                <div class="dossier-table-wrapper">
                    <table class="dossier">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>URL</th>
                                <th class="text-center">Unique</th>
                            </tr>
                        </thead>
                        <tbody>

                            <tr v-for="item in items">
                                <td class="cell-title first-cell">{{ item.title }}</td>
                                <td class="cell-slug">{{ item.url }}</td>
                                <td class="text-center">
                                    <span class="icon-status status-{{ item.unique ? 'live' : 'hidden' }}"></span>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</template>


<script>
export default {

    data() {
        return {
            loading: true,
            items: null
        }
    },

    ready() {

        this.$http.get(cp_url('addons/seo-pro/pulse/summary')).then(response => {
            this.items = response.data;
            this.loading = false;
        });

    }

}
</script>

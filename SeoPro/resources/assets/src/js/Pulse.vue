<template>

    <div>

        <div class="flex items-center mb-3">
            <h1 class="flex-1">Pulse</h1>
        </div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <div v-else>

            <div class="card">
                <div class="metrics">
                    <div class="metric simple">
                        <div class="count">
                            <small>SEO Pro Grade</small>
                            <h2>{{ grade }}</h2>
                        </div>
                    </div>
                    <div class="metric simple">
                        <div class="count">
                            <small>Complete</small>
                            <h2>{{ completionPercent }}%</h2>
                        </div>
                    </div>
                </div>
            </div>

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
                                <td class="first-cell">{{ item.title }}</td>
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

    computed: {

        completionPercent() {
            const total = this.items.length;
            const unique = this.items.filter(item => item.unique).length;
            return Math.round(unique / total * 100);
        },

        grade() {
            const score = this.completionPercent;
            if (score >= 97) return 'A+';
            if (score >= 93) return 'A';
            if (score >= 90) return 'A-';
            if (score >= 87) return 'B+';
            if (score >= 83) return 'B';
            if (score >= 80) return 'B-';
            if (score >= 77) return 'C+';
            if (score >= 73) return 'C';
            if (score >= 70) return 'C-';
            if (score >= 67) return 'D+';
            if (score >= 63) return 'D';
            if (score >= 60) return 'D-';
            return 'F';
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

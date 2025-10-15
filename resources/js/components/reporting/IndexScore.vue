<template>

    <div>
        <div v-if="score">
            <seo-pro-status-icon :status="statusByScore" class="inline-block ml-1 mr-3" />
            {{ score }}%
        </div>
        <loading-graphic v-else :text="null" :inline="true" />
    </div>

</template>


<script>
export default {

    props: [
        'id',
        'initialStatus',
        'initialScore',
    ],

    data() {
        return {
            status: this.initialStatus,
            score: this.initialScore,
        }
    },

    computed: {
        statusByScore() {
            if (! this.score) {
                return this.status;
            }

            // Ensure we color status icon on index view to match site score color on report show view
            if (this.score < 70) {
                return 'fail';
            } else if (this.score < 90) {
                return 'warning';
            } else if (this.score >= 90) {
                return 'pass';
            }
        },
    },

    created() {
        if (! this.score) this.updateScore();
    },

    methods: {
        updateScore() {
            Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}/pages`)).then(response => {
                if (response.data.status === 'pending' || response.data.status === 'generating') {
                    setTimeout(() => this.updateScore(), 1000);
                    return;
                }

                this.status = response.data.status;
                this.score = response.data.score;
            });
        },
    },

}
</script>

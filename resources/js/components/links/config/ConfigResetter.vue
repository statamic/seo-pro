<template>
    <confirmation-modal
        v-if="resetting"
        :title="modalTitle"
        :bodyText="modalBody"
        :buttonText="__('Reset')"
        :danger="true"
        @confirm="confirmed"
        @cancel="cancel"
    >
    </confirmation-modal>
</template>

<script>
export default {

    props: {
        resource: {
            type: Object
        },
        resourceTitle: {
            type: String
        },
        route: {
            type: String,
        },
        redirect: {
            type: String
        },
        reload: {
            type: Boolean
        },
        mode: {
            type: String,
            default: 'config',
        }
    },

    data() {
        return {
            resetting: false,
            redirectFromServer: null,
        }
    },

    computed: {
        title() {
            return data_get(this.resource, 'title', this.resourceTitle);
        },

        modalTitle() {
            if (this.mode === 'suggestions') {
                return __('Reset :resource Suggestions', {resource: __(this.title)});
            }

            return __('Reset :resource Configuration', {resource: __(this.title)});
        },

        modalBody() {
            if (this.mode === 'suggestions') {
                return __('Are you sure you want to reset the suggestions?');
            }

            return __('Are you sure you want to reset the configuration?');
        },

        resetUrl() {
            let url = data_get(this.resource, 'reset_url', this.route);
            if (! url) console.error('ConfigResetter cannot find reset url');
            return url;
        },

        redirectUrl() {
            return this.redirect || this.redirectFromServer;
        },
    },

    methods: {
        confirm() {
            this.resetting = true;
        },

        confirmed() {
            this.$axios.delete(this.resetUrl)
                .then(response => {
                    this.redirectFromServer = data_get(response, 'data.redirect');
                    this.success();
                })
                .catch(() => {
                    this.$toast.error(__('Something went wrong'));
                });
        },

        success() {
            if (this.redirectUrl) {
                location.href = this.redirectUrl;
                return;
            }

            if (this.reload) {
                location.reload();
                return;
            }

            this.$toast.success(__('Configuration Reset'));
            this.$emit('reset');
        },

        cancel() {
            this.resetting = false;
        }
    }
}
</script>

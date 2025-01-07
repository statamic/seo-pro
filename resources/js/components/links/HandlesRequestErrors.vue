<script>
export default {

    methods: {

        handleAxiosError(e) {
            if (e.response && e.response.status === 422) {
                const { message, errors } = e.response.data;

                if (typeof this.error !== 'undefined') {
                    this.error = message;
                }

                if (typeof this.errors !== 'undefined') {
                    this.errors = errors;
                }

                this.$toast.error(message);
                this.$reveal.invalid();
            } else if (e.response) {
                this.$toast.error(e.response.data.message);
            } else {
                this.$toast.error(e || 'Something went wrong');
            }
        },

    },

}
</script>
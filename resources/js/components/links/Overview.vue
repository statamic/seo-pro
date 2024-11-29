<template>
    <div>
        <div class="flex flex-wrap -mx-4 mb-6" v-if="report">
            <div class="w-1/3 px-4">
                <div class="card py-2">
                    <h2 class="text-sm text-gray-800 flex">
                        <status-icon :status="getInternalLinkStatus()"></status-icon>
                        <span class="ml-2">{{ __('seo-pro::messages.internal_links') }}</span>
                    </h2>
                    <div class="text-lg flex align-middle">{{ report.overview.internal_link_count }}</div>
                </div>
            </div>
            <div class="w-1/3 px-4">
                <div class="card py-2">
                    <h2 class="text-sm text-gray-800 flex">
                        <status-icon :status="getExternalLinkStatus()"></status-icon>
                        <span class="ml-2">{{ __('seo-pro::messages.external_links') }}</span>
                    </h2>
                    <div class="text-lg">{{ report.overview.external_link_count }}</div>
                </div>
            </div>
            <div class="w-1/3 px-4">
                <div class="card py-2">
                    <h2 class="text-sm text-gray-800">{{ __('seo-pro::messages.inbound_internal_links') }}</h2>
                    <div class="text-lg">{{ report.overview.inbound_internal_link_count }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import StatusIcon from '../reporting/StatusIcon.vue';

export default {
    components: {StatusIcon},
    props: [
        'report',
    ],
    methods: {
        getLinkStatus(linkCount, min, max) {
            if (! this.report) {
                return 'pending';
            }

            if (min !== 0 && linkCount === 0) {
                return 'fail';
            }

            if (linkCount < min || linkCount > max) {
                return 'warning';
            }

            return 'pass';
        },
        getExternalLinkStatus() {
            return this.getLinkStatus(
                this.report.overview.external_link_count,
                this.report.preferences.min_external_link_count,
                this.report.preferences.max_internal_link_count,
            );
        },
        getInternalLinkStatus() {
            return this.getLinkStatus(
                this.report.overview.internal_link_count,
                this.report.preferences.min_internal_link_count,
                this.report.preferences.max_internal_link_count,
            );
        }
    }
}
</script>
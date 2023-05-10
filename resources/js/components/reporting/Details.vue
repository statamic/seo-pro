
<template>

    <modal
        name="report-details"
        @closed="$emit('closed')"
        :click-to-close="true"
    >
        <div class="p-0">
            <h1 class="p-6 bg-grey-20 border-b">
                {{ __('seo-pro::messages.page_details') }}
            </h1>

            <div class="p-6 -mb-4">
            <div v-for="item in item.results" class="flex mb-4 leading-normal">
                <div>
                    <span class="icon-status" :class="{'icon-status-live': item.status === 'pass', 'icon-status-error': item.status === 'fail', 'icon-status-warning': item.status === 'warning'}"></span>
                </div>
                <div class="flex-1 pl-4">
                    <span :class="{ 'font-bold': item.status !== 'pass' }" v-html="item.description"></span>
                    <div class="text-grey text-xs" v-if="item.comment" v-html="item.comment"></div>
                </div>
            </div>
            </div>

            <div class="p-4 pl-6 bg-grey-20 border-t text-left font-mono text-grey-60 text-xs">
                {{ item.url }}
            </div>
        </div>
    </modal>

</template>


<script>
export default {

    props: ['item']

}
</script>

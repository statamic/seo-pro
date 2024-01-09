
<template>

    <modal
        name="report-details"
        @closed="$emit('closed')"
        :click-to-close="true"
    >
        <div class="p-0">
            <h1 class="p-4 bg-gray-200 border-b text-lg">
                {{ __('seo-pro::messages.page_details') }}
            </h1>

            <div class="modal-body">
                <div v-for="item in item.results" class="flex px-4 leading-normal pb-2" :class="{ 'bg-red-100': item.status !== 'pass' }">
                    <status-icon :status="item.status" class="mr-3 mt-2" />
                    <div class="flex-1 mt-2 prose text-gray-700">
                        <div class="text-gray-900" v-html="item.description"></div>
                        <div class="text-xs" :class="{ 'text-red-800': item.status !== 'pass' }" v-if="item.comment" v-html="item.comment"></div>
                    </div>
                </div>
            </div>

            <footer class="px-5 py-3 bg-gray-200 rounded-b-lg border-t flex items-center font-mono text-xs">
                <a :href="item.url" target="_blank" class="font-normal text-gray-700 hover:text-gray-800 mr-4" v-text="item.url" />
            </footer>
        </div>
    </modal>

</template>


<script>
import StatusIcon from './StatusIcon.vue';

export default {
    props: ['item'],

    components: {
        StatusIcon
    }
}
</script>


<template>

    <modal
        name="report-details"
        @closed="$emit('closed')"
        :click-to-close="true"
    >
        <div class="p-0">
            <h1 class="p-4 bg-gray-200 dark:bg-dark-650 border-b dark:border-dark-900 text-lg">
                {{ __('seo-pro::messages.page_details') }}
            </h1>

            <div class="modal-body">
                <div v-for="item in item.results" class="flex px-4 leading-normal pb-2" :class="{ 'bg-red-100 dark:bg-dark-400': item.status !== 'pass' }">
                    <status-icon :status="item.status" class="mr-3 mt-2" />
                    <div class="flex-1 mt-2 prose text-gray-700 dark:text-dark-150">
                        <div class="text-gray-900 dark:text-dark-100" v-html="item.description"></div>
                        <div class="text-xs" :class="{ 'text-red-800 dark:text-dark-150': item.status !== 'pass' }" v-if="item.comment" v-html="item.comment"></div>
                    </div>
                </div>
            </div>

            <footer class="px-4 py-3 dark:bg-dark-700 dark:border-dark-900 rounded-b-lg border-t flex items-center text-sm flex">
                <a v-if="item.url" :href="item.url" target="_blank" class="font-normal font-mono text-xs text-gray-700 hover:text-blue grow truncate" v-text="item.url" />
                <a v-if="item.url" :href="item.url" target="_blank" class="font-normal text-gray-700 hover:text-blue ml-8" v-text="__('seo-pro::messages.visit')" />
                <a v-if="item.edit_url" :href="item.edit_url" target="_blank" class="font-normal text-gray-700 hover:text-blue ml-4" v-text="__('Edit')" />
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

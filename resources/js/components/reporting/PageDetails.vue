
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
                
                <!-- Metadata Summary Section -->
                <div v-if="item.metadata" class="mt-4 px-4 pb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-dark-100 mb-2">SEO Metadata Summary</h3>
                    <div class="space-y-1 text-xs">
                        <div v-if="item.metadata.published_date" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Published:</span>
                            <span class="text-gray-900 dark:text-dark-100">{{ item.metadata.published_date }}</span>
                        </div>
                        <div v-if="item.metadata.updated_date" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Updated:</span>
                            <span class="text-gray-900 dark:text-dark-100">{{ item.metadata.updated_date }}</span>
                        </div>
                        <div v-if="item.metadata.author" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Author:</span>
                            <span class="text-gray-900 dark:text-dark-100">{{ item.metadata.author }}</span>
                        </div>
                        <div v-if="item.metadata.og_type" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">OG Type:</span>
                            <span class="text-gray-900 dark:text-dark-100">{{ item.metadata.og_type }}</span>
                        </div>
                        <div v-if="item.metadata.og_description" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">OG Description:</span>
                            <span class="text-gray-900 dark:text-dark-100 truncate">{{ item.metadata.og_description }}</span>
                        </div>
                        <div v-if="item.metadata.og_image" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">OG Image:</span>
                            <span class="text-gray-900 dark:text-dark-100">✓</span>
                        </div>
                        <div v-if="item.metadata.twitter_card" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Twitter Card:</span>
                            <span class="text-gray-900 dark:text-dark-100">{{ item.metadata.twitter_card }}</span>
                        </div>
                        <div v-if="item.metadata.twitter_title" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Twitter Title:</span>
                            <span class="text-gray-900 dark:text-dark-100 truncate">{{ item.metadata.twitter_title }}</span>
                        </div>
                        <div v-if="item.metadata.twitter_description" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Twitter Desc:</span>
                            <span class="text-gray-900 dark:text-dark-100 truncate">{{ item.metadata.twitter_description }}</span>
                        </div>
                        <div v-if="item.metadata.twitter_image" class="flex">
                            <span class="font-semibold w-32 text-gray-700 dark:text-dark-150">Twitter Image:</span>
                            <span class="text-gray-900 dark:text-dark-100">✓</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="px-4 py-3 dark:bg-dark-700 dark:border-dark-900 rounded-b-lg border-t flex items-center text-sm flex">
                <a v-if="item.url" :href="item.url" target="_blank" class="font-normal font-mono text-xs text-gray-700 hover:text-blue grow truncate" v-text="item.url" />
                <a v-if="item.url" :href="item.url" target="_blank" class="font-normal text-gray-700 hover:text-blue ml-8" v-text="__('Visit')" />
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

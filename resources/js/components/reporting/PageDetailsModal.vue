<script setup>
import StatusIcon from './StatusIcon.vue';
import { Modal, Button } from '@statamic/cms/ui';
import { ref } from 'vue';

const emit = defineEmits(['closed']);
defineProps({ page: Object });

const open = ref(true);

const close = () => {
	open.value = false;
	setTimeout(() => emit('closed'), 200);
};
</script>

<template>
    <Modal
	    :title="__('seo-pro::messages.page_details')"
	    :open
	    @dismissed="close"
	    @update:model-value="close"
    >
	    <div class="flex flex-col gap-3">
		    <div
		        v-for="item in page.results"
		        class="flex leading-normal p-2 rounded-xl"
		        :class="{ 'bg-red-50 dark:bg-dark-400': item.status !== 'pass' }"
		    >
			    <StatusIcon :status="item.status" class="mr-3" />
			    <div class="flex-1 prose text-gray-700">
				    <div class="text-gray-900 dark:text-dark-100" v-html="item.description" />
				    <div class="text-xs" :class="{ 'text-red-500': item.status !== 'pass' }" v-if="item.comment" v-html="item.comment" />
			    </div>
		    </div>
	    </div>

	    <template #footer>
		    <div class="flex items-center justify-between pt-3 pb-1">
			    <a v-if="page.url" :href="page.url" target="_blank" class="font-normal font-mono text-xs text-gray-700 hover:text-blue grow truncate" v-text="page.url" />

			    <div class="flex items-center justify-end space-x-3">
				    <Button v-if="page.url" :href="page.url" target="_blank" variant="ghost" :text="__('Visit')" />
				    <Button v-if="page.edit_url" :href="page.edit_url" target="_blank" variant="ghost" :text="__('Edit')" />
			    </div>
		    </div>
	    </template>
    </Modal>
</template>
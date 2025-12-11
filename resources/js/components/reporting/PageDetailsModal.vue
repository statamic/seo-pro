<script setup>
import StatusIcon from './StatusIcon.vue';
import { Modal, Button, Heading, Description } from '@statamic/cms/ui';
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
		        class="flex leading-normal p-2 rounded-lg gap-x-3"
		        :class="{ 'bg-red-50 dark:!bg-dark-400': item.status !== 'pass' }"
		    >
			    <StatusIcon :status="item.status" class="mt-1" />
			    <div class="flex-1 prose text-gray-700">
				    <Heading size="sm" class="text-gray-900 dark:!text-dark-100" :text="item.description" />
				    <Description :class="{ 'text-red-500': item.status !== 'pass' }" v-if="item.comment" :text="item.comment" />
			    </div>
		    </div>
	    </div>

	    <template #footer>
		    <div class="flex items-center justify-between pt-3 pb-1">
			    <a v-if="page.url" :href="page.url" target="_blank" class="font-normal font-mono text-xs ps-2 text-gray-700 dark:!text-gray-100 hover:text-blue-500! grow truncate" v-text="page.url" />
                <Button v-if="page.edit_url" :href="page.edit_url" target="_blank" :text="__('Edit Entry')" />
		    </div>
	    </template>
    </Modal>
</template>

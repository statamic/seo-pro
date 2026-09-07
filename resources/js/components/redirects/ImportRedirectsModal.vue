<script setup>
import { Modal, Description, ErrorMessage, Button, PublishContainer, PublishFieldsProvider, PublishFields } from '@statamic/cms/ui';
import { ref, getCurrentInstance } from 'vue';

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const emit = defineEmits(['closed', 'imported']);

const open = ref(true);
const busy = ref(false);
const error = ref(null);
const values = ref({ file: [] });
const meta = ref({
	file: {
		uploadUrl: cp_url('fieldtypes/files/upload'),
	},
});

const fields = [
	{
		handle: 'file',
		type: 'files',
		display: __('CSV File'),
		max_files: 1,
		allowed_extensions: ['csv'],
	},
];

const submit = () => {
	if (!values.value.file.length) return;

	busy.value = true;
	error.value = null;

	$axios.post(cp_url('seo-pro/redirects/import'), values.value)
		.then((response) => {
			const { created, updated } = response.data;

			Statamic.$toast.success(__('seo-pro::messages.redirects_imported', { created, updated }));
			emit('imported');
			close();
		})
		.catch((e) => {
			if (e.response?.status === 422) {
				error.value = e.response.data.message || e.response.data.errors?.file?.[0];
			} else {
				error.value = __('Something went wrong');
			}
		})
		.finally(() => {
			busy.value = false;
		});
};

const close = () => {
	open.value = false;
	setTimeout(() => emit('closed'), 200);
};
</script>

<template>
	<Modal
		:title="__('seo-pro::messages.import_redirects')"
		:open
		:dismissable="!busy"
		@dismissed="close"
		@update:model-value="close"
	>
		<Description class="mb-6">
			<p>{{ __('seo-pro::messages.import_redirects_instructions') }}</p>
			<ul class="list-disc list-inside mt-2">
				<li>{{ __('seo-pro::messages.source') }} ({{ __('required') }})</li>
				<li>{{ __('seo-pro::messages.destination') }} ({{ __('required') }})</li>
				<li>{{ __('seo-pro::messages.response_code') }}</li>
				<li>{{ __('seo-pro::messages.enabled') }}</li>
				<li>{{ __('seo-pro::messages.description') }}</li>
			</ul>
			<p class="mt-2">{{ __('seo-pro::messages.import_redirects_instructions_2') }}</p>
		</Description>

		<PublishContainer
			:blueprint="fields"
			:meta
			:track-dirty-state="false"
			v-model="values"
		>
			<PublishFieldsProvider :fields>
				<PublishFields />
			</PublishFieldsProvider>
		</PublishContainer>

		<ErrorMessage v-if="error" :text="error" />

		<template #footer>
			<div class="flex items-center justify-end space-x-3 pt-3 pb-1">
				<Button
					variant="ghost"
					:disabled="busy"
					:text="__('Cancel')"
					@click="close"
				/>
				<Button
					type="submit"
					variant="primary"
					:disabled="busy || !values.file.length"
					:text="__('seo-pro::messages.import')"
					@click="submit"
				/>
			</div>
		</template>
	</Modal>
</template>

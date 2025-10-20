<script setup>
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { Header, Button, PublishContainer, PublishTabs } from '@statamic/cms/ui';
import { Pipeline, Request, BeforeSaveHooks, AfterSaveHooks, PipelineStopped } from '@statamic/cms/save-pipeline';
import { Head } from '@statamic/cms/inertia';

const props = defineProps({
	blueprint: {
		type: Object,
		required: true,
	},
	initialValues: {
		type: Object,
		required: true,
		default: () => ({}),
	},
	initialMeta: {
		type: Object,
		required: true,
		default: () => ({}),
	},
	action: {
		type: String,
		required: true,
	},
});

const container = useTemplateRef('container');
const values = ref(props.initialValues);
const meta = ref(props.initialMeta);
const errors = ref({});
const saving = ref(false);

function save() {
	new Pipeline()
		.provide({ container, errors, saving })
		.through([
			new BeforeSaveHooks('site-default'),
			new Request(props.action, 'patch'),
			new AfterSaveHooks('site-default'),
		])
		.then((response) => {
			Statamic.$toast.success(__('Saved'));
		});
}

let saveKeyBinding;

onMounted(() => {
	saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (e) => {
		e.preventDefault();
		save();
	});
});

onUnmounted(() => saveKeyBinding.destroy());
</script>

<template>
	<Head :title="__('seo-pro::messages.site_defaults')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.site_defaults')" icon="earth">
			<Button v-if="!readOnly" variant="primary" :text="__('Save')" @click="save" :disabled="saving" />
		</Header>
		<PublishContainer
			ref="container"
			:blueprint="blueprint"
			:meta="meta"
			:errors="errors"
			as-config
			v-model="values"
		>
			<PublishTabs />
		</PublishContainer>
	</div>
</template>
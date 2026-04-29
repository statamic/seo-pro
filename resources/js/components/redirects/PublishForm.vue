<script setup>
import { PublishContainer, PublishTabs, Header, Button } from '@statamic/cms/ui';
import { nanoid as uniqid } from 'nanoid';
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';
import { router } from '@statamic/cms/inertia';

const emit = defineEmits(['saved']);

const props = defineProps({
	title: {
		type: String,
		default: () => uniqid(),
	},
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
	submitUrl: {
		type: [String, null],
		default: null,
	},
	submitMethod: {
		type: String,
		default: 'patch',
	},
	readOnly: {
		type: Boolean,
		default: false,
	},
});

const containerName = Statamic.$slug.separatedBy('_').create(props.title);
const container = useTemplateRef('container');
const values = ref(props.initialValues);
const meta = ref(props.initialMeta);
const errors = ref({});
const saving = ref(false);

function save() {
	new Pipeline()
		.provide({ container, errors, saving })
		.through([
			new Request(props.submitUrl, props.submitMethod),
		])
		.then((response) => {
			Statamic.$toast.success(__('Saved'));
			emit('saved', response);
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
	<Header :title="title" icon="moved">
		<Button v-if="!readOnly" variant="primary" :text="__('Save')" @click="save" :disabled="saving" />
	</Header>
	<PublishContainer
		ref="container"
		:name="containerName"
		:blueprint
		:meta
		:errors
		:read-only
		v-model="values"
	>
		<PublishTabs />
	</PublishContainer>
</template>

<script setup>
import { PublishContainer, PublishTabs, Header, Button, Panel, Heading, Switch } from '@statamic/cms/ui';
import { nanoid as uniqid } from 'nanoid';
import { onMounted, onUnmounted, ref, useTemplateRef, computed } from 'vue';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';

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
	isCreating: {
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
const isDirty = computed(() => Statamic.$dirty.has(containerName));

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

const testRedirect = () => {
	let wildcardIndex = 0;
	let url = props.initialValues.source;

	url = url.replace(/\*/g, () => {
		wildcardIndex++;
		return `wildcard${wildcardIndex}`;
	});

	window.open(url, '_blank');
};
</script>

<template>
	<Header :title="title" icon="moved">
		<Button v-if="!isCreating" :text="__('Test Redirect')" :disabled="isDirty" @click="testRedirect" />
		<Button v-if="!readOnly" variant="primary" :text="__('Save')" :disabled="saving" @click="save" />
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
		<PublishTabs>
			<template #actions>
				<Panel class="flex justify-between px-5! py-3! dark:bg-gray-800!">
					<Heading :text="__('Enabled')" />
					<Switch
						:model-value="values.enabled"
						:disabled="readOnly"
						@update:model-value="container.setFieldValue('enabled', $event)"
					/>
				</Panel>
			</template>
		</PublishTabs>
	</PublishContainer>
</template>

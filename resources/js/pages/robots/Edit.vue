<script setup>
import { computed, getCurrentInstance, onMounted, onUnmounted, ref, watch } from 'vue';
import { deepClone } from '@statamic/cms';
import { Head } from '@statamic/cms/inertia';
import {
	Alert,
	Badge,
	Button,
	CodeEditor,
	Description,
	DocsCallout,
	Field,
	Header,
	Heading,
	Input,
	Panel,
	Select,
	Subheading,
	Switch,
} from '@statamic/cms/ui';
const props = defineProps({
	values: { type: Object, required: true },
	preview: { type: String, required: true },
	action: { type: String, required: true },
	generateUrl: { type: String, required: true },
	previewUrl: { type: String, required: true },
	liveUrl: { type: String, required: true },
	file: { type: Object, required: true },
});

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const form = ref(deepClone(props.values));
const rendered = ref(props.preview);
const action = ref(props.action);
const generateUrl = ref(props.generateUrl);
const previewUrl = ref(props.previewUrl);
const liveUrl = ref(props.liveUrl);
const file = ref(props.file);
const saving = ref(false);
const generating = ref(false);
const confirmingGeneration = ref(false);
const errors = ref({});
const errorHeading = ref(__('Unable to save robots.txt settings'));
const previewError = ref(false);
const dirtyStateName = 'seo-pro-robots';

const importDescription = computed(() => {
	if (file.value.importable) {
		return __('You can import the existing file into custom mode before generating. Generating without importing will overwrite it.');
	}

	if (file.value.import_issue === 'too_large') {
		return __('The existing file is larger than 500 KiB and cannot be imported. Generating will overwrite it.');
	}

	if (file.value.import_issue === 'invalid_utf8') {
		return __('The existing file is not valid UTF-8 and cannot be imported. Generating will overwrite it.');
	}

	return __('The existing file could not be read and cannot be imported. Generating will overwrite it.');
});

const accessOptions = [
	{ value: 'neutral', label: __('No explicit rule') },
	{ value: 'allow', label: __('Allow') },
	{ value: 'disallow', label: __('Block') },
];

const signalOptions = [
	{ value: null, label: __('No preference') },
	{ value: 'yes', label: __('Allow') },
	{ value: 'no', label: __('Do not allow') },
];

const useOptions = [
	{ value: null, label: __('No preference') },
	{ value: 'immediate', label: __('Immediate use only') },
	{ value: 'reference', label: __('Reference and link back') },
	{ value: 'full', label: __('Full use') },
];

const modeOptions = [
	{ value: 'managed', label: __('Managed') },
	{ value: 'custom', label: __('Custom source') },
];

const presets = [
	{
		value: 'neutral',
		label: __('Neutral'),
		description: __('Allow normal crawling without expressing AI-specific preferences.'),
		ai: { search: 'neutral', agent: 'neutral', training: 'neutral' },
		signals: { search: null, ai_input: null, ai_train: null, use: null },
	},
	{
		value: 'discoverable',
		label: __('Discoverable, not trainable'),
		description: __('Allow AI search and user-directed agents while blocking training crawlers.'),
		ai: { search: 'allow', agent: 'allow', training: 'disallow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'no', use: 'reference' },
	},
	{
		value: 'full',
		label: __('Full AI access'),
		description: __('Allow search, agents, and training, and permit all declared content uses.'),
		ai: { search: 'allow', agent: 'allow', training: 'allow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'yes', use: 'full' },
	},
	{
		value: 'search_only',
		label: __('AI search only'),
		description: __('Allow answer-engine discovery while blocking user agents and training crawlers.'),
		ai: { search: 'allow', agent: 'disallow', training: 'disallow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'no', use: 'reference' },
	},
	{
		value: 'private',
		label: __('Block AI access'),
		description: __('Block known AI search, agent, and training crawlers.'),
		ai: { search: 'disallow', agent: 'disallow', training: 'disallow' },
		signals: { search: 'no', ai_input: 'no', ai_train: 'no', use: 'immediate' },
	},
];

function applyPreset(preset) {
	form.value.preset = preset.value;
	form.value.ai = deepClone(preset.ai);
	form.value.content_signals = deepClone(preset.signals);
}

function markCustom() {
	form.value.preset = 'custom';
}

function addPath(type) {
	form.value[type].push('/');
	markCustom();
}

function removePath(type, index) {
	form.value[type].splice(index, 1);
	markCustom();
}

function payload() {
	return deepClone(form.value);
}

function clearDirtyStateIfUnchanged(submitted) {
	if (JSON.stringify(form.value) === JSON.stringify(submitted)) {
		Statamic.$dirty.remove(dirtyStateName);
	}
}

function requestGeneration() {
	if (saving.value || generating.value) return;

	confirmingGeneration.value = true;
}

function save() {
	if (saving.value || generating.value) return;

	saving.value = true;
	errors.value = {};
	errorHeading.value = __('Unable to save robots.txt settings');
	const submitted = payload();

	$axios.patch(action.value, submitted)
		.then((response) => {
			rendered.value = response.data.preview;
			file.value = response.data.file;
			clearDirtyStateIfUnchanged(submitted);
			Statamic.$toast.success(__('Settings saved'));
		})
		.catch((error) => {
			errors.value = error.response?.data?.errors ?? {};
			Statamic.$toast.error(__('Unable to save robots.txt settings.'));
		})
		.finally(() => saving.value = false);
}

function generate() {
	if (saving.value || generating.value) return;

	confirmingGeneration.value = false;
	generating.value = true;
	errors.value = {};
	errorHeading.value = __('Unable to generate robots.txt');
	const submitted = payload();

	$axios.post(generateUrl.value, submitted)
		.then((response) => {
			rendered.value = response.data.preview;
			file.value = response.data.file;
			clearDirtyStateIfUnchanged(submitted);
			Statamic.$toast.success(__('robots.txt generated'));
		})
		.catch((error) => {
			errors.value = error.response?.data?.errors ?? {};
			Statamic.$toast.error(__('Unable to generate robots.txt.'));
		})
		.finally(() => generating.value = false);
}

function importPhysicalFile() {
	form.value.mode = 'custom';
	form.value.custom_source = file.value.contents;
	form.value.preset = 'custom';
}

let previewTimer;
watch(form, () => {
	Statamic.$dirty.add(dirtyStateName);

	clearTimeout(previewTimer);
	previewTimer = setTimeout(() => {
		$axios.post(previewUrl.value, payload())
			.then((response) => {
				rendered.value = response.data.preview;
				previewError.value = false;
			})
			.catch(() => previewError.value = true);
	}, 250);
}, { deep: true });

let saveKeyBinding;
onMounted(() => {
	saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (event) => {
		event.preventDefault();
		save();
	});
});

onUnmounted(() => {
	clearTimeout(previewTimer);
	saveKeyBinding.destroy();
});
</script>

<template>
	<Head :title="__('seo-pro::messages.robots')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.robots')" icon="earth">
			<Button v-if="file.exists" :href="liveUrl" target="_blank" variant="ghost" icon="external-link" :text="__('View live file')" />
			<Button variant="default" :text="__('Generate')" :disabled="saving || generating" :loading="generating" @click="requestGeneration" />
			<Button variant="primary" :text="__('Save')" :disabled="saving || generating" :loading="saving" @click="save" />
		</Header>

		<div class="space-y-6">
			<Alert
				v-if="!file.exists"
				variant="default"
				:heading="__('No robots.txt file exists')"
				:text="__('Configure the policy below, then select Generate to create robots.txt.')"
			/>

			<Alert
				v-else-if="!file.managed"
				variant="warning"
			>
				<Heading :text="__('An existing robots.txt file is not currently managed by SEO Pro')" />
				<Description :text="importDescription" />
				<div v-if="file.importable" class="mt-3">
					<Button size="sm" variant="default" :text="__('Import existing file')" @click="importPhysicalFile" />
				</div>
			</Alert>

			<Alert
				v-else-if="file.outdated"
				variant="warning"
				:heading="__('robots.txt needs to be generated')"
				:text="__('The saved settings differ from the current file. Select Generate to update robots.txt.')"
			/>

			<Alert v-else variant="success" :heading="__('robots.txt is managed by SEO Pro')" />

			<Alert v-if="Object.keys(errors).length" variant="error" :heading="errorHeading">
				<ul class="list-disc ps-5">
					<li v-for="(messages, field) in errors" :key="field" v-text="messages[0]" />
				</ul>
			</Alert>

			<Panel class="p-6 space-y-6">
				<Field :label="__('Editing mode')" :instructions="__('Managed mode generates validated directives. Custom source mode serves exactly what you enter.')">
					<Select class="max-w-sm" :options="modeOptions" v-model="form.mode" />
				</Field>
			</Panel>

			<template v-if="form.mode === 'managed'">
				<section>
					<Subheading size="lg" class="mb-2" :text="__('Policy preset')" />
					<div class="grid gap-3 md:grid-cols-2">
						<button
							v-for="preset in presets"
							:key="preset.value"
							type="button"
							class="rounded-lg border p-4 text-start hover:border-gray-400 dark:border-gray-700"
							:class="{ 'border-blue-500! ring-1 ring-blue-500': form.preset === preset.value }"
							@click="applyPreset(preset)"
						>
							<Heading :text="preset.label" />
							<Description class="mt-1" :text="preset.description" />
						</button>
					</div>
				</section>

				<Panel class="p-6">
					<Heading size="lg" :text="__('AI crawler access')" />
					<Description class="mt-1 mb-5" :text="__('Access rules control whether known crawlers may fetch your pages. They are requests, not security controls.')" />
					<div class="divide-y dark:divide-gray-700">
						<Field class="py-4" :label="__('AI search')" :instructions="__('Crawlers that build indexes for answer and search experiences.')">
							<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.search" @update:model-value="markCustom" />
						</Field>
						<Field class="py-4" :label="__('User-directed agents')" :instructions="__('Agents fetching a page in response to a user request.')">
							<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.agent" @update:model-value="markCustom" />
						</Field>
						<Field class="py-4" :label="__('Model training')" :instructions="__('Crawlers collecting content to train or fine-tune models.')">
							<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.training" @update:model-value="markCustom" />
						</Field>
					</div>
				</Panel>

				<Panel class="p-6">
					<Heading size="lg" :text="__('Content Signals')" />
					<Description class="mt-1 mb-5" :text="__('Declare how fetched content may be used. These preferences do not technically prevent scraping.')" />
					<div class="grid gap-5 md:grid-cols-2">
						<Field :label="__('Search indexing')"><Select :options="signalOptions" v-model="form.content_signals.search" @update:model-value="markCustom" /></Field>
						<Field :label="__('AI input and grounding')"><Select :options="signalOptions" v-model="form.content_signals.ai_input" @update:model-value="markCustom" /></Field>
						<Field :label="__('AI training')"><Select :options="signalOptions" v-model="form.content_signals.ai_train" @update:model-value="markCustom" /></Field>
						<Field :label="__('Content use')">
							<template #label><span class="flex items-center gap-2">{{ __('Content use') }} <Badge size="sm" :text="__('Experimental')" /></span></template>
							<Select :options="useOptions" v-model="form.content_signals.use" @update:model-value="markCustom" />
						</Field>
					</div>
				</Panel>

				<Panel class="p-6 space-y-6">
					<div>
						<Heading size="lg" :text="__('General crawler rules')" />
						<Description class="mt-1" :text="__('Paths apply to all crawlers unless SEO Pro emits a more specific AI crawler group.')" />
					</div>

					<Field :label="__('Allowed paths')">
						<div class="space-y-2">
							<div v-for="(path, index) in form.allow" :key="`allow-${index}`" class="flex gap-2">
								<Input class="font-mono" v-model="form.allow[index]" @update:model-value="markCustom" />
								<Button icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removePath('allow', index)" />
							</div>
							<Button size="sm" variant="ghost" icon="plus" :text="__('Add allowed path')" @click="addPath('allow')" />
						</div>
					</Field>

					<Field :label="__('Disallowed paths')">
						<div class="space-y-2">
							<div v-for="(path, index) in form.disallow" :key="`disallow-${index}`" class="flex gap-2">
								<Input class="font-mono" v-model="form.disallow[index]" @update:model-value="markCustom" />
								<Button icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removePath('disallow', index)" />
							</div>
							<Button size="sm" variant="ghost" icon="plus" :text="__('Add disallowed path')" @click="addPath('disallow')" />
						</div>
					</Field>

					<div class="flex items-start justify-between gap-6">
						<div>
							<Heading :text="__('Include sitemap')" />
							<Description :text="__('Add the absolute SEO Pro sitemap URL to robots.txt.')" />
						</div>
						<Switch v-model="form.include_sitemap" @update:model-value="markCustom" />
					</div>
				</Panel>
			</template>

			<Panel v-else class="p-6">
				<Heading size="lg" :text="__('Custom robots.txt source')" />
				<Description class="mt-1 mb-5" :text="__('SEO Pro will normalize line endings and add a final newline, but will not otherwise modify this source.')" />
				<CodeEditor v-model="form.custom_source" mode="nginx" :allow-mode-selection="false" :show-mode-label="false" title="robots.txt" />
			</Panel>

			<Panel v-if="form.mode === 'managed'" class="p-6">
				<div class="flex items-center justify-between mb-4">
					<div>
						<Heading size="lg" :text="__('Generated preview')" />
						<Description :text="liveUrl" />
					</div>
					<Badge v-if="previewError" color="red" :text="__('Preview unavailable')" />
				</div>
				<CodeEditor :model-value="rendered" mode="nginx" :allow-mode-selection="false" :show-mode-label="false" read-only title="robots.txt preview" />
			</Panel>
		</div>

		<DocsCallout :topic="__('Robots.txt')" url="https://statamic.com/addons/statamic/seo-pro/docs" />

		<confirmation-modal
			:open="confirmingGeneration"
			:title="__('Generate robots.txt?')"
			:body-text="__('This will overwrite robots.txt if it already exists. This action cannot be undone.')"
			:button-text="__('Generate')"
			:danger="true"
			@update:open="confirmingGeneration = $event"
			@confirm="generate"
			@cancel="confirmingGeneration = false"
		/>
	</div>
</template>

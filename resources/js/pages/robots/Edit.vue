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
	TabContent,
	TabList,
	Tabs,
	TabTrigger,
} from '@statamic/cms/ui';
const props = defineProps({
	values: { type: Object, required: true },
	preview: { type: String, required: true },
	action: { type: String, required: true },
	generateUrl: { type: String, required: true },
	previewUrl: { type: String, required: true },
	liveUrl: { type: String, required: true },
	sitemapUrlsAreEnvironmentDependent: { type: Boolean, required: true },
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
const errorHeading = ref(__('seo-pro::messages.robots_txt.unable_to_save_settings'));
const previewError = ref(false);
const dirtyStateName = 'seo-pro-robots';
const activeTab = ref('robots.txt');

const importDescription = computed(() => {
	if (file.value.importable) {
		return __('seo-pro::messages.robots_txt.existing_file_importable');
	}

	if (file.value.import_issue === 'too_large') {
		return __('seo-pro::messages.robots_txt.existing_file_too_large');
	}

	if (file.value.import_issue === 'invalid_utf8') {
		return __('seo-pro::messages.robots_txt.existing_file_invalid_utf8');
	}

	return __('seo-pro::messages.robots_txt.existing_file_unreadable');
});

const accessOptions = [
	{ value: 'neutral', label: __('seo-pro::messages.robots_txt.no_explicit_rule') },
	{ value: 'allow', label: __('seo-pro::messages.robots_txt.allow') },
	{ value: 'disallow', label: __('seo-pro::messages.robots_txt.block') },
];

const signalOptions = [
	{ value: null, label: __('seo-pro::messages.robots_txt.no_preference') },
	{ value: 'yes', label: __('seo-pro::messages.robots_txt.allow') },
	{ value: 'no', label: __('seo-pro::messages.robots_txt.do_not_allow') },
];

const useOptions = [
	{ value: null, label: __('seo-pro::messages.robots_txt.no_preference') },
	{ value: 'immediate', label: __('seo-pro::messages.robots_txt.immediate_use_only') },
	{ value: 'reference', label: __('seo-pro::messages.robots_txt.reference_and_link_back') },
	{ value: 'full', label: __('seo-pro::messages.robots_txt.full_use') },
];

const modeOptions = [
	{ value: 'managed', label: __('seo-pro::messages.robots_txt.managed') },
	{ value: 'custom', label: __('seo-pro::messages.robots_txt.custom_source') },
];

const sitemapModeOptions = [
	{ value: 'automatic', label: __('seo-pro::messages.robots_txt.automatic_from_statamic_sites') },
	{ value: 'custom', label: __('seo-pro::messages.robots_txt.custom_canonical_urls') },
];

const presets = [
	{
		value: 'neutral',
		label: __('seo-pro::messages.robots_txt.neutral'),
		description: __('seo-pro::messages.robots_txt.neutral_description'),
		ai: { search: 'neutral', agent: 'neutral', training: 'neutral' },
		signals: { search: null, ai_input: null, ai_train: null, use: null },
	},
	{
		value: 'discoverable',
		label: __('seo-pro::messages.robots_txt.discoverable_not_trainable'),
		description: __('seo-pro::messages.robots_txt.discoverable_not_trainable_description'),
		ai: { search: 'allow', agent: 'allow', training: 'disallow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'no', use: 'reference' },
	},
	{
		value: 'full',
		label: __('seo-pro::messages.robots_txt.full_ai_access'),
		description: __('seo-pro::messages.robots_txt.full_ai_access_description'),
		ai: { search: 'allow', agent: 'allow', training: 'allow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'yes', use: 'full' },
	},
	{
		value: 'search_only',
		label: __('seo-pro::messages.robots_txt.ai_search_only'),
		description: __('seo-pro::messages.robots_txt.ai_search_only_description'),
		ai: { search: 'allow', agent: 'disallow', training: 'disallow' },
		signals: { search: 'yes', ai_input: 'yes', ai_train: 'no', use: 'reference' },
	},
	{
		value: 'private',
		label: __('seo-pro::messages.robots_txt.block_ai_access'),
		description: __('seo-pro::messages.robots_txt.block_ai_access_description'),
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

function addSitemapUrl() {
	form.value.sitemap_urls.push('https://');
	markCustom();
}

function removeSitemapUrl(index) {
	form.value.sitemap_urls.splice(index, 1);
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
	errorHeading.value = __('seo-pro::messages.robots_txt.unable_to_save_settings');
	const submitted = payload();

	$axios.patch(action.value, submitted)
		.then((response) => {
			rendered.value = response.data.preview;
			file.value = response.data.file;
			clearDirtyStateIfUnchanged(submitted);
			Statamic.$toast.success(__('Addon settings saved'));
		})
		.catch((error) => {
			errors.value = error.response?.data?.errors ?? {};
			Statamic.$toast.error(__('seo-pro::messages.robots_txt.unable_to_save_settings'));
		})
		.finally(() => saving.value = false);
}

function generate() {
	if (saving.value || generating.value) return;

	confirmingGeneration.value = false;
	generating.value = true;
	errors.value = {};
	errorHeading.value = __('seo-pro::messages.robots_txt.unable_to_generate');
	const submitted = payload();

	$axios.post(generateUrl.value, submitted)
		.then((response) => {
			rendered.value = response.data.preview;
			file.value = response.data.file;
			clearDirtyStateIfUnchanged(submitted);
			Statamic.$toast.success(response.data.changed
				? __('seo-pro::messages.robots_txt_generated')
				: __('seo-pro::messages.robots_txt.already_up_to_date'));
		})
		.catch((error) => {
			errors.value = error.response?.data?.errors ?? {};
			Statamic.$toast.error(__('seo-pro::messages.robots_txt.unable_to_generate'));
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
			<Button variant="primary" :text="__('Save')" :disabled="saving || generating" :loading="saving" @click="save" />
		</Header>

		<Tabs v-model="activeTab" :unmount-on-hide="false">
			<TabList>
				<TabTrigger name="robots.txt" text="robots.txt" />
				<TabTrigger name="llms.txt" text="llms.txt" />
			</TabList>

			<TabContent name="robots.txt">
				<div class="pt-6">
					<div class="flex flex-wrap items-center justify-end gap-2 pb-6 sm:gap-3">
						<Button v-if="file.exists" :href="liveUrl" target="_blank" variant="ghost" icon="external-link" :text="__('seo-pro::messages.robots_txt.view_live_file')" />
						<Button variant="default" :text="__('Generate')" :disabled="saving || generating" :loading="generating" @click="requestGeneration" />
					</div>

					<div class="space-y-6">
						<Alert
							v-if="!file.exists"
							variant="default"
							:heading="__('seo-pro::messages.robots_txt.no_file')"
							:text="__('seo-pro::messages.robots_txt.no_file_description')"
						/>

						<Alert
							v-else-if="!file.managed"
							variant="warning"
						>
							<Heading :text="__('seo-pro::messages.robots_txt.existing_file_unmanaged')" />
							<Description :text="importDescription" />
							<div v-if="file.importable" class="mt-3">
								<Button size="sm" variant="default" :text="__('seo-pro::messages.robots_txt.import_existing_file')" @click="importPhysicalFile" />
							</div>
						</Alert>

						<Alert
							v-else-if="file.outdated"
							variant="warning"
							:heading="__('seo-pro::messages.robots_txt.needs_generation')"
							:text="__('seo-pro::messages.robots_txt.needs_generation_description')"
						/>

						<Alert v-else variant="success" :heading="__('seo-pro::messages.robots_txt.managed_by_seo_pro')" />

						<Alert v-if="Object.keys(errors).length" variant="error" :heading="errorHeading">
							<ul class="list-disc ps-5">
								<li v-for="(messages, field) in errors" :key="field" v-text="messages[0]" />
							</ul>
						</Alert>

						<Panel class="p-6 space-y-6">
							<Field :label="__('seo-pro::messages.robots_txt.editing_mode')" :instructions="__('seo-pro::messages.robots_txt.editing_mode_instructions')">
								<Select class="max-w-sm" :options="modeOptions" v-model="form.mode" />
							</Field>
						</Panel>

						<template v-if="form.mode === 'managed'">
							<section>
								<Subheading size="lg" class="mb-2" :text="__('seo-pro::messages.robots_txt.policy_preset')" />
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
								<Heading size="lg" :text="__('seo-pro::messages.robots_txt.ai_crawler_access')" />
								<Description class="mt-1 mb-5" :text="__('seo-pro::messages.robots_txt.ai_crawler_access_description')" />
								<div class="divide-y dark:divide-gray-700">
									<Field class="py-4" :label="__('seo-pro::messages.robots_txt.ai_search')" :instructions="__('seo-pro::messages.robots_txt.ai_search_description')">
										<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.search" @update:model-value="markCustom" />
									</Field>
									<Field class="py-4" :label="__('seo-pro::messages.robots_txt.user_directed_agents')" :instructions="__('seo-pro::messages.robots_txt.user_directed_agents_description')">
										<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.agent" @update:model-value="markCustom" />
									</Field>
									<Field class="py-4" :label="__('seo-pro::messages.robots_txt.model_training')" :instructions="__('seo-pro::messages.robots_txt.model_training_description')">
										<Select class="max-w-sm" :options="accessOptions" v-model="form.ai.training" @update:model-value="markCustom" />
									</Field>
								</div>
							</Panel>

							<Panel class="p-6">
								<Heading size="lg" :text="__('seo-pro::messages.robots_txt.content_signals')" />
								<Description class="mt-1 mb-5" :text="__('seo-pro::messages.robots_txt.content_signals_description')" />
								<div class="grid gap-5 md:grid-cols-2">
									<Field :label="__('seo-pro::messages.robots_txt.search_indexing')"><Select :options="signalOptions" v-model="form.content_signals.search" @update:model-value="markCustom" /></Field>
									<Field :label="__('seo-pro::messages.robots_txt.ai_input_and_grounding')"><Select :options="signalOptions" v-model="form.content_signals.ai_input" @update:model-value="markCustom" /></Field>
									<Field :label="__('seo-pro::messages.robots_txt.ai_training')"><Select :options="signalOptions" v-model="form.content_signals.ai_train" @update:model-value="markCustom" /></Field>
									<Field :label="__('seo-pro::messages.robots_txt.content_use')">
										<template #label><span class="flex items-center gap-2">{{ __('seo-pro::messages.robots_txt.content_use') }} <Badge size="sm" :text="__('seo-pro::messages.robots_txt.experimental')" /></span></template>
										<Select :options="useOptions" v-model="form.content_signals.use" @update:model-value="markCustom" />
									</Field>
								</div>
							</Panel>

							<Panel class="p-6 space-y-6">
								<div>
									<Heading size="lg" :text="__('seo-pro::messages.robots_txt.general_crawler_rules')" />
									<Description class="mt-1" :text="__('seo-pro::messages.robots_txt.general_crawler_rules_description')" />
								</div>

								<Field :label="__('seo-pro::messages.robots_txt.allowed_paths')">
									<div class="space-y-2">
										<div v-for="(path, index) in form.allow" :key="`allow-${index}`" class="flex gap-2">
											<Input class="font-mono" v-model="form.allow[index]" @update:model-value="markCustom" />
											<Button icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removePath('allow', index)" />
										</div>
										<Button size="sm" variant="ghost" icon="plus" :text="__('seo-pro::messages.robots_txt.add_allowed_path')" @click="addPath('allow')" />
									</div>
								</Field>

								<Field :label="__('seo-pro::messages.robots_txt.disallowed_paths')">
									<div class="space-y-2">
										<div v-for="(path, index) in form.disallow" :key="`disallow-${index}`" class="flex gap-2">
											<Input class="font-mono" v-model="form.disallow[index]" @update:model-value="markCustom" />
											<Button icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removePath('disallow', index)" />
										</div>
										<Button size="sm" variant="ghost" icon="plus" :text="__('seo-pro::messages.robots_txt.add_disallowed_path')" @click="addPath('disallow')" />
									</div>
								</Field>

								<div class="flex items-start justify-between gap-6">
									<div>
										<Heading :text="__('seo-pro::messages.robots_txt.include_sitemap')" />
										<Description :text="__('seo-pro::messages.robots_txt.include_sitemap_description')" />
									</div>
									<Switch v-model="form.include_sitemap" @update:model-value="markCustom" />
								</div>

								<template v-if="form.include_sitemap">
									<Field :label="__('seo-pro::messages.robots_txt.sitemap_source')" :instructions="__('seo-pro::messages.robots_txt.sitemap_source_instructions')">
										<Select class="max-w-sm" :options="sitemapModeOptions" v-model="form.sitemap_mode" @update:model-value="markCustom" />
									</Field>

									<Alert
										v-if="form.sitemap_mode === 'automatic' && sitemapUrlsAreEnvironmentDependent"
										variant="warning"
										:heading="__('seo-pro::messages.robots_txt.environment_dependent_sitemap_urls')"
										:text="__('seo-pro::messages.robots_txt.environment_dependent_sitemap_urls_description')"
									/>

									<Field
										v-if="form.sitemap_mode === 'custom'"
										:label="__('seo-pro::messages.robots_txt.canonical_sitemap_urls')"
										:instructions="__('seo-pro::messages.robots_txt.canonical_sitemap_urls_instructions')"
									>
										<div class="space-y-2">
											<div v-for="(url, index) in form.sitemap_urls" :key="`sitemap-${index}`" class="flex gap-2">
												<Input type="url" class="font-mono" v-model="form.sitemap_urls[index]" @update:model-value="markCustom" />
												<Button icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removeSitemapUrl(index)" />
											</div>
											<Button size="sm" variant="ghost" icon="plus" :text="__('seo-pro::messages.robots_txt.add_sitemap_url')" @click="addSitemapUrl" />
										</div>
									</Field>
								</template>
							</Panel>
						</template>

						<Panel v-else class="p-6">
							<Heading size="lg" :text="__('seo-pro::messages.robots_txt.custom_source_heading')" />
							<Description class="mt-1 mb-5" :text="__('seo-pro::messages.robots_txt.custom_source_description')" />
							<CodeEditor v-model="form.custom_source" mode="nginx" :allow-mode-selection="false" :show-mode-label="false" title="robots.txt" />
						</Panel>

						<Panel v-if="form.mode === 'managed'" class="p-6">
							<div class="flex items-center justify-between mb-4">
								<div>
									<Heading size="lg" :text="__('seo-pro::messages.robots_txt.generated_preview')" />
									<Description :text="liveUrl" />
								</div>
								<Badge v-if="previewError" color="red" :text="__('seo-pro::messages.robots_txt.preview_unavailable')" />
							</div>
							<CodeEditor :model-value="rendered" mode="nginx" :allow-mode-selection="false" :show-mode-label="false" read-only :title="__('seo-pro::messages.robots_txt.preview_title')" />
						</Panel>
					</div>

					<DocsCallout topic="robots.txt" url="https://statamic.com/addons/statamic/seo-pro/docs" />

					<confirmation-modal
						:open="confirmingGeneration"
						:title="__('seo-pro::messages.robots_txt.generate_confirmation')"
						:body-text="__('seo-pro::messages.robots_txt.generate_confirmation_description')"
						:button-text="__('Generate')"
						:danger="true"
						@update:open="confirmingGeneration = $event"
						@confirm="generate"
						@cancel="confirmingGeneration = false"
					/>
				</div>
			</TabContent>

			<TabContent name="llms.txt">
				<div class="pt-6" />
			</TabContent>
		</Tabs>
	</div>
</template>

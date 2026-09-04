<script setup>
import { computed, getCurrentInstance, onMounted, onUnmounted, ref, watch } from 'vue';
import { deepClone } from '@statamic/cms';
import {
	Alert,
	Badge,
	Button,
	CodeEditor,
	Combobox,
	Description,
	Field,
	Heading,
	Input,
	Panel,
	Select,
	Subheading,
	Switch,
	Textarea,
} from '@statamic/cms/ui';
import SiteSelector from '../../components/SiteSelector.vue';

const props = defineProps({
	editUrl: { type: String, required: true },
});

const emit = defineEmits(['saving']);
const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const loaded = ref(false);
const loading = ref(false);
const saving = ref(false);
const generating = ref(false);
const confirmingGeneration = ref(false);
const form = ref(null);
const rendered = ref('');
const action = ref('');
const generateUrl = ref('');
const previewUrl = ref('');
const liveUrl = ref('');
const file = ref({});
const sites = ref([]);
const site = ref('');
const collectionOptions = ref([]);
const entryOptions = ref([]);
const errors = ref({});
const previewError = ref(false);
const hydrating = ref(false);
const dirtyStateName = 'seo-pro-llms';

const modeOptions = [
	{ value: 'managed', label: __('seo-pro::messages.llms_txt.managed') },
	{ value: 'custom', label: __('seo-pro::messages.llms_txt.custom_source') },
];

const isBusy = computed(() => loading.value || saving.value || generating.value);
const hasUnmanagedFile = computed(() => file.value.exists && !file.value.managed);

function hydrate(data) {
	hydrating.value = true;
	form.value = deepClone(data.values);
	rendered.value = data.preview;
	action.value = data.action;
	generateUrl.value = data.generateUrl;
	previewUrl.value = data.previewUrl;
	liveUrl.value = data.liveUrl;
	file.value = data.file;
	sites.value = data.sites;
	site.value = data.site;
	collectionOptions.value = data.collectionOptions;
	entryOptions.value = data.entryOptions;
	errors.value = {};
	previewError.value = false;
	loaded.value = true;
	Statamic.$dirty.remove(dirtyStateName);
	queueMicrotask(() => hydrating.value = false);
}

function load(selectedSite = null) {
	if (loading.value) return;

	loading.value = true;
	$axios.get(props.editUrl, { params: selectedSite ? { site: selectedSite } : {} })
		.then((response) => hydrate(response.data))
		.catch(() => Statamic.$toast.error(__('seo-pro::messages.llms_txt.unable_to_load_settings')))
		.finally(() => loading.value = false);
}

function payload() {
	return { ...deepClone(form.value), site: site.value };
}

function clearDirtyStateIfUnchanged(submitted) {
	const current = { ...deepClone(form.value), site: site.value };

	if (JSON.stringify(current) === JSON.stringify(submitted)) {
		Statamic.$dirty.remove(dirtyStateName);
	}
}

function save() {
	if (!loaded.value || isBusy.value) return;

	saving.value = true;
	emit('saving', true);
	errors.value = {};
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
			Statamic.$toast.error(__('seo-pro::messages.llms_txt.unable_to_save_settings'));
		})
		.finally(() => {
			saving.value = false;
			emit('saving', false);
		});
}

function requestGeneration() {
	if (!form.value?.enabled || isBusy.value || hasUnmanagedFile.value) return;

	confirmingGeneration.value = true;
}

function generate() {
	confirmingGeneration.value = false;
	generating.value = true;
	emit('saving', true);
	errors.value = {};
	const submitted = payload();

	$axios.post(generateUrl.value, submitted)
		.then((response) => {
			rendered.value = response.data.preview;
			file.value = response.data.file;
			clearDirtyStateIfUnchanged(submitted);
			Statamic.$toast.success(response.data.changed
				? __('seo-pro::messages.llms_txt_generated')
				: __('seo-pro::messages.llms_txt.already_up_to_date'));
		})
		.catch((error) => {
			errors.value = error.response?.data?.errors ?? {};
			Statamic.$toast.error(__('seo-pro::messages.llms_txt.unable_to_generate'));
		})
		.finally(() => {
			generating.value = false;
			emit('saving', false);
		});
}

function switchSite(selectedSite) {
	if (selectedSite === site.value || isBusy.value) return;

	if (Statamic.$dirty.has(dirtyStateName) && !window.confirm(__('Are you sure? Unsaved changes will be lost.'))) {
		return;
	}

	load(selectedSite);
}

function addSection() {
	form.value.sections.push({ title: '', links: [] });
}

function removeSection(index) {
	form.value.sections.splice(index, 1);
}

function addLink(section) {
	section.links.push({ title: '', url: 'https://', description: '' });
}

function removeLink(section, index) {
	section.links.splice(index, 1);
}

let previewTimer;
watch(form, () => {
	if (!loaded.value || hydrating.value) return;

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

onMounted(() => load());
onUnmounted(() => clearTimeout(previewTimer));

defineExpose({ save });
</script>

<template>
	<div class="pt-6">
		<div v-if="loading && !loaded" class="py-12 text-center text-gray-500">{{ __('Loading...') }}</div>

		<template v-else-if="loaded">
			<div class="flex flex-wrap items-center justify-between gap-3 pb-6">
				<SiteSelector
					v-if="sites.length > 1"
					:sites="sites"
					:model-value="site"
					@update:modelValue="switchSite"
				/>
				<div v-else />

				<div class="flex flex-wrap items-center gap-2 sm:gap-3">
					<Button v-if="form.enabled || file.exists" :href="liveUrl" target="_blank" variant="ghost" icon="external-link" :text="__('seo-pro::messages.llms_txt.view_live_file')" />
					<Button
						v-if="form.enabled"
						variant="default"
						:text="__('Generate')"
						:disabled="isBusy || hasUnmanagedFile"
						:loading="generating"
						@click="requestGeneration"
					/>
				</div>
			</div>

			<div class="space-y-6">
				<Alert
					v-if="hasUnmanagedFile"
					variant="warning"
					:heading="__('seo-pro::messages.llms_txt.existing_file_unmanaged')"
					:text="__('seo-pro::messages.llms_txt.existing_file_unmanaged_description')"
				/>
				<Alert
					v-else-if="!form.enabled"
					variant="default"
					:heading="__('seo-pro::messages.llms_txt.disabled')"
					:text="__('seo-pro::messages.llms_txt.disabled_description')"
				/>
				<Alert
					v-else-if="file.managed && file.outdated"
					variant="warning"
					:heading="__('seo-pro::messages.llms_txt.needs_generation')"
					:text="__('seo-pro::messages.llms_txt.needs_generation_description')"
				/>
				<Alert
					v-else-if="file.managed"
					variant="success"
					:heading="__('seo-pro::messages.llms_txt.managed_by_seo_pro')"
				/>
				<Alert
					v-else
					variant="success"
					:heading="__('seo-pro::messages.llms_txt.cached_route_active')"
					:text="__('seo-pro::messages.llms_txt.cached_route_active_description')"
				/>

				<Alert v-if="Object.keys(errors).length" variant="error" :heading="__('seo-pro::messages.llms_txt.unable_to_save_settings')">
					<ul class="list-disc ps-5">
						<li v-for="(messages, field) in errors" :key="field" v-text="messages[0]" />
					</ul>
				</Alert>

				<Panel class="p-6">
					<div class="flex items-start justify-between gap-6">
						<div>
							<Heading :text="__('seo-pro::messages.llms_txt.enable')" />
							<Description :text="__('seo-pro::messages.llms_txt.enable_description')" />
						</div>
						<Switch v-model="form.enabled" />
					</div>
				</Panel>

				<template v-if="form.enabled">
					<Panel class="p-6">
						<Field :label="__('seo-pro::messages.llms_txt.editing_mode')" :instructions="__('seo-pro::messages.llms_txt.editing_mode_instructions')">
							<Select class="max-w-sm" :options="modeOptions" v-model="form.mode" />
						</Field>
					</Panel>

					<template v-if="form.mode === 'managed'">
						<Panel class="p-6 space-y-6">
							<Field :label="__('seo-pro::messages.llms_txt.title')" :instructions="__('seo-pro::messages.llms_txt.title_instructions')" required>
								<Input v-model="form.title" class="font-mono" />
							</Field>
							<Field :label="__('seo-pro::messages.llms_txt.summary')" :instructions="__('seo-pro::messages.llms_txt.summary_instructions')">
								<Textarea v-model="form.summary" rows="3" />
							</Field>
							<Field :label="__('seo-pro::messages.llms_txt.details')" :instructions="__('seo-pro::messages.llms_txt.details_instructions')">
								<Textarea v-model="form.details" rows="5" class="font-mono" />
							</Field>
						</Panel>

						<Panel class="p-6 space-y-6">
							<div>
								<Heading size="lg" :text="__('seo-pro::messages.llms_txt.statamic_content')" />
								<Description class="mt-1" :text="__('seo-pro::messages.llms_txt.statamic_content_description')" />
							</div>
							<Field :label="__('seo-pro::messages.llms_txt.collections')" :instructions="__('seo-pro::messages.llms_txt.collections_instructions')">
								<Combobox
									v-model="form.collections"
									multiple
									searchable
									:options="collectionOptions"
									:placeholder="__('seo-pro::messages.llms_txt.select_collections')"
								/>
							</Field>
							<Field :label="__('seo-pro::messages.llms_txt.entries')" :instructions="__('seo-pro::messages.llms_txt.entries_instructions')">
								<Combobox
									v-model="form.entries"
									multiple
									searchable
									:options="entryOptions"
									:placeholder="__('seo-pro::messages.llms_txt.select_entries')"
								/>
							</Field>
						</Panel>

						<section>
							<div class="flex items-center justify-between gap-4 mb-3">
								<div>
									<Subheading size="lg" :text="__('seo-pro::messages.llms_txt.sections')" />
									<Description :text="__('seo-pro::messages.llms_txt.sections_description')" />
								</div>
								<Button size="sm" variant="ghost" icon="plus" :text="__('seo-pro::messages.llms_txt.add_section')" @click="addSection" />
							</div>

							<div class="space-y-4">
								<Panel v-for="(section, sectionIndex) in form.sections" :key="sectionIndex" class="p-6 space-y-5">
									<div class="flex gap-2">
										<Field class="flex-1" :label="__('seo-pro::messages.llms_txt.section_title')">
											<Input v-model="section.title" />
										</Field>
										<Button class="self-end" icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removeSection(sectionIndex)" />
									</div>

									<div class="space-y-3">
										<div v-for="(link, linkIndex) in section.links" :key="linkIndex" class="rounded-lg border p-4 space-y-3 dark:border-gray-700">
											<div class="grid gap-3 md:grid-cols-2">
												<Field :label="__('seo-pro::messages.llms_txt.link_title')"><Input v-model="link.title" /></Field>
												<Field :label="__('seo-pro::messages.llms_txt.link_url')"><Input v-model="link.url" class="font-mono" /></Field>
											</div>
											<div class="flex gap-2">
												<Field class="flex-1" :label="__('seo-pro::messages.llms_txt.link_description')"><Textarea v-model="link.description" rows="2" /></Field>
												<Button class="self-end" icon="trash" variant="ghost" :aria-label="__('Remove')" @click="removeLink(section, linkIndex)" />
											</div>
										</div>
										<Button size="sm" variant="ghost" icon="plus" :text="__('seo-pro::messages.llms_txt.add_link')" @click="addLink(section)" />
									</div>
								</Panel>
							</div>
						</section>
					</template>

					<Panel v-else class="p-6">
						<Heading size="lg" :text="__('seo-pro::messages.llms_txt.custom_source_heading')" />
						<Description class="mt-1 mb-5" :text="__('seo-pro::messages.llms_txt.custom_source_description')" />
						<CodeEditor v-model="form.custom_source" mode="markdown" :allow-mode-selection="false" :show-mode-label="false" title="llms.txt" />
					</Panel>

					<Panel v-if="form.mode === 'managed'" class="p-6">
						<div class="flex items-center justify-between mb-4">
							<div>
								<Heading size="lg" :text="__('seo-pro::messages.llms_txt.generated_preview')" />
								<Description :text="liveUrl" />
							</div>
							<Badge v-if="previewError" color="red" :text="__('seo-pro::messages.llms_txt.preview_unavailable')" />
						</div>
						<CodeEditor :model-value="rendered" mode="markdown" :allow-mode-selection="false" :show-mode-label="false" read-only :title="__('seo-pro::messages.llms_txt.preview_title')" />
					</Panel>
				</template>
			</div>

			<confirmation-modal
				:open="confirmingGeneration"
				:title="__('seo-pro::messages.llms_txt.generate_confirmation')"
				:body-text="__('seo-pro::messages.llms_txt.generate_confirmation_description')"
				:button-text="__('Generate')"
				@update:open="confirmingGeneration = $event"
				@confirm="generate"
				@cancel="confirmingGeneration = false"
			/>
		</template>
	</div>
</template>

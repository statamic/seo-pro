<script setup>
import { onMounted, onUnmounted, ref, useTemplateRef, computed, nextTick, getCurrentInstance } from 'vue';
import { Header, Dropdown, DropdownMenu, DropdownItem, Button, PublishContainer } from '@statamic/cms/ui';
import { Pipeline, Request, BeforeSaveHooks, AfterSaveHooks } from '@statamic/cms/save-pipeline';
import { Head, router } from '@statamic/cms/inertia';
import SiteSelector from '../../components/SiteSelector.vue';
import ConfigureModal from '../../components/site-defaults/ConfigureModal.vue';

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const props = defineProps({
	blueprint: Object,
	initialReference: String,
	initialValues: Object,
	initialMeta: Object,
	initialLocalizations: Array,
	initialLocalizedFields: Array,
	initialHasOrigin: Boolean,
	initialOriginValues: Object,
	initialOriginMeta: Object,
	initialSite: String,
	action: String,
	configureUrl: String,
});

const container = useTemplateRef('container');
const reference = ref(props.initialReference);
const values = ref(props.initialValues);
const meta = ref(props.initialMeta);
const errors = ref({});
const localizing = ref(false);
const localizations = ref(props.initialLocalizations);
const localizedFields = ref(props.initialLocalizedFields);
const hasOrigin = ref(props.initialHasOrigin);
const originValues = ref(props.initialOriginValues);
const originMeta = ref(props.initialOriginMeta);
const site = ref(props.initialSite);
const syncFieldConfirmationText = ref(__('messages.sync_entry_field_confirmation_text'));
const pendingLocalization = ref(null);
const saving = ref(false);
const configureModalOpen = ref(false);

function save() {
	new Pipeline()
		.provide({ container, errors, saving })
		.through([
			new BeforeSaveHooks('site-defaults'),
			new Request(props.action, 'patch', {
				site: site.value,
				_localized: localizedFields.value,
			}),
			new AfterSaveHooks('site-defaults'),
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

const isDirty = computed(() => Statamic.$dirty.has('site-defaults'));
const showLocalizationSelector = computed(() => localizations.value.length > 1);

const localizationSelected = (localizationHandle) => {
	let localization = localizations.value.find((localization) => localization.handle === localizationHandle);

	if (localization.active) return;

	if (isDirty.value) {
		pendingLocalization.value = localization;
		return;
	}

	switchToLocalization(localization);
};

const confirmSwitchLocalization = () => {
	switchToLocalization(pendingLocalization.value);
	pendingLocalization.value = null;
};

const switchToLocalization = (localization) => {
	localizing.value = localization.handle;

	window.history.replaceState({}, '', localization.url);

	$axios.get(localization.url).then((response) => {
		const data = response.data;
		reference.value = data.initialReference;
		values.value = data.initialValues;
		originValues.value = data.initialOriginValues;
		originMeta.value = data.initialOriginMeta;
		meta.value = data.initialMeta;
		localizations.value = data.initialLocalizations;
		localizedFields.value = data.initialLocalizedFields;
		hasOrigin.value = data.initialHasOrigin;
		site.value = localization.handle;
		localizing.value = false;
		nextTick(() => container.value.clearDirtyState());
	});
};
</script>

<template>
	<Head :title="__('seo-pro::messages.site_defaults')" />

	<div class="max-w-5xl mx-auto">
		<Header :title="__('seo-pro::messages.site_defaults')" icon="earth">
			<Dropdown v-if="showLocalizationSelector">
				<template #trigger>
					<Button icon="dots" variant="ghost" :aria-label="__('Open dropdown menu')" />
				</template>
				<DropdownMenu>
					<DropdownItem :text="__('Configure')" icon="cog" @click="configureModalOpen = true" />
				</DropdownMenu>
			</Dropdown>

			<SiteSelector
				v-if="showLocalizationSelector"
				:sites="localizations"
				:model-value="site"
				@update:modelValue="localizationSelected"
			/>

			<Button variant="primary" :text="__('Save')" @click="save" :disabled="saving" />
		</Header>

		<PublishContainer
			ref="container"
			name="site-defaults"
			:reference
			:blueprint
			v-model="values"
			:meta
			:errors
			:site
			:origin-values
			:origin-meta
			v-model:modified-fields="localizedFields"
			:sync-field-confirmation-text
			:track-dirty-state="true"
			as-config
		/>

		<ConfigureModal
			v-if="configureModalOpen"
			:route="configureUrl"
			@saved="() => router.reload()"
			@closed="configureModalOpen = false"
		/>

		<confirmation-modal
			v-if="pendingLocalization"
			:title="__('Unsaved Changes')"
			:body-text="__('Are you sure? Unsaved changes will be lost.')"
			:button-text="__('Continue')"
			:danger="true"
			@confirm="confirmSwitchLocalization"
			@cancel="pendingLocalization = null"
		/>
	</div>
</template>
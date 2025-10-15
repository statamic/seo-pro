<script setup>
import { Fieldtype } from '@statamic/cms';
import { Select, Input, PublishField, injectPublishContext } from '@statamic/cms/ui';
import { computed } from "vue";

const { blueprint } = injectPublishContext();

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update, updateMeta, isReadOnly } = Fieldtype.use(emit, props);
defineExpose(expose);

const source = computed(() => props.value.source);
const sourceField = computed(() => props.value.source === 'field' ? props.value.value : null);

const sourceTypeSelectOptions = computed(() => {
	let options = [];

	if (props.config.field !== false) {
		options.push({ label: __('seo-pro::messages.custom'), value: 'custom' });
	}

	if (props.config.from_field !== false) {
		options.unshift({ label: __('seo-pro::messages.from_field'), value: 'field' });
	}

	if (props.config.inherit !== false) {
		options.unshift({ label: __('seo-pro::messages.inherit'), value: 'inherit' });
	}

	if (props.config.disableable) {
		options.push({ label: __('seo-pro::messages.disable'), value: 'disable' });
	}

	return options;
});

const fieldConfig = computed(() => Object.assign(props.config.field, { placeholder: props.config.placeholder }));
const placeholder = computed(() => props.config.placeholder);

const sourceFieldOptions = computed(() => {
	const allowedFieldtypes = [
		'text',
		'textarea',
		'markdown',
		'bard',
		...props.config.allowed_fieldtypes ?? [],
	];

	return blueprint.value.tabs
		.flatMap(tab => tab.sections)
		.flatMap(section => section.fields)
		.filter(field => allowedFieldtypes.includes(field.type))
		.map(field => ({ value: field.handle, label: field.display ?? field.handle }) );
});

const shouldShowSourceFieldDropdown = computed(() => sourceFieldOptions.value.length > 1);

const sourceTypeDropdownChanged = (value) => {
	let newValue = props.value;

	newValue.source = value;

	if (value !== 'field') {
		newValue.value = props.meta.defaultValue;
		updateMeta({ ...props.meta, fieldMeta: props.meta.defaultFieldMeta });
	}

	update(newValue);
};

const sourceFieldChanged = (field) => update({ ...props.value, value: field });
</script>

<template>
    <div class="flex gap-y-2 gap-x-4">
	    <!-- todo: make w-full in live preview and smaller breakpoints -->
        <div class="w-80">
            <Select
	            class="w-full"
                :options="sourceTypeSelectOptions"
                :disabled="isReadOnly"
                :clearable="false"
                :model-value="source"
                @update:model-value="sourceTypeDropdownChanged"
            />
        </div>

        <div class="flex-1">
            <div v-if="source === 'inherit'" class="text-sm text-grey inherit-placeholder">
                <template v-if="placeholder !== false">
                    {{ placeholder }}
                </template>
            </div>

            <div v-else-if="source === 'field'">
                <Select
	                v-if="shouldShowSourceFieldDropdown"
	                class="w-full"
	                :placeholder="__('Field')"
	                :options="sourceFieldOptions"
                    :model-value="sourceField"
                    @update:model-value="sourceFieldChanged"
                >
	                <template #option="option">
		                <div class="flex items-center">
			                <span v-text="option.label" />
			                <span
				                v-text="option.value"
				                class="font-mono text-2xs text-gray-500 dark:text-dark-150"
				                :class="{ 'ml-2': option.label }"
			                />
		                </div>
	                </template>
	                <template #selected-option="{ option }">
		                <div class="flex items-center">
			                <span v-text="option.label" />
			                <span
				                v-text="option.value"
				                class="font-mono text-2xs text-gray-500 dark:text-dark-150"
				                :class="{ 'ml-2': option.label }"
			                />
		                </div>
	                </template>
                </Select>

                <Input
	                v-else
	                class="font-mono"
	                :disabled="isReadOnly"
	                :model-value="sourceField"
	                @update:model-value="sourceFieldChanged"
                />
            </div>

	        <PublishField
		        v-else-if="source === 'custom'"
		        :config="fieldConfig"
		        :field-path-prefix="`seo.${handle}.value`"
		        :meta-path-prefix="`seo.meta.${handle}`"
		        v-slot="{ fieldtypeComponent, fieldtypeComponentProps, fieldtypeComponentEvents }"
	        >
		        <Component
			        ref="fieldtype"
			        :is="fieldtypeComponent"
			        v-bind="fieldtypeComponentProps"
			        v-on="fieldtypeComponentEvents"
		        />
	        </PublishField>
        </div>
    </div>
</template>
<script setup>
import {Fieldtype} from '@statamic/cms';
import { Input } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { name, isReadOnly, update, expose } = Fieldtype.use(emit, props);

function onInput(value) {
	if (!value) {
		return update(value);
	}

	const siteUrl = props.meta.site_url;

	if (siteUrl && value.startsWith(siteUrl)) {
		value = value.substring(siteUrl.length);
	}

	if (!value.startsWith('/')) {
		value = '/' + value;
	}

	update(value);
}

defineExpose(expose);
</script>

<template>
    <Input
        :model-value="value"
        :focus="config.focus"
        :read-only="isReadOnly"
        :prepend="meta.site_url"
        :placeholder="config.placeholder"
        :name="name"
        :id="id"
        @update:model-value="onInput"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
    />
</template>

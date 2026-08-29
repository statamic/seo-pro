<script setup>
import { Fieldtype } from '@statamic/cms';
import { TimePicker } from '@statamic/cms/ui';
import { parseTime } from '@internationalized/date';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { isReadOnly, update, expose } = Fieldtype.use(emit, props);
defineExpose(expose);

function timeValue(day, type) {
    const time = props.value?.[day]?.[type];

    return time ? parseTime(time) : null;
}

function updateDay(day, type, time) {
    update({
        ...(props.value || {}),
        [day]: {
            ...(props.value?.[day] || {}),
            [type]: time ? time.toString().slice(0, 5) : null,
        },
    });
}
</script>

<template>
    <table class="w-full">
        <thead>
            <tr class="text-start text-sm text-gray-700 dark:text-gray-300">
                <th class="p-2 font-medium"></th>
                <th class="p-2 font-medium text-start">{{ __('seo-pro::messages.opening') }}</th>
                <th class="p-2 font-medium text-start">{{ __('seo-pro::messages.closing') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(label, day) in meta.days" :key="day">
                <td class="p-2 text-sm font-medium whitespace-nowrap">{{ label }}</td>
                <td class="p-2">
                    <TimePicker
                        granularity="minute"
                        :model-value="timeValue(day, 'opening')"
                        :read-only="isReadOnly"
                        @update:model-value="updateDay(day, 'opening', $event)"
                    />
                </td>
                <td class="p-2">
                    <TimePicker
                        granularity="minute"
                        :model-value="timeValue(day, 'closing')"
                        :read-only="isReadOnly"
                        @update:model-value="updateDay(day, 'closing', $event)"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>

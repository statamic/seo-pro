<template>
    <modal
        name="ignore-suggestion-confirmation"
        v-if="suggestion != null"
    >
        <div class="confirmation-modal flex flex-col h-full">
            <div class="text-lg font-medium p-4 pb-0">{{ title }}</div>
            <div class="flex-1 px-4 py-6 text-gray dark:text-dark-150">
                <div class="px-2">
                    <div class="publish-fields @container">
                        <div class="form-group publish-field w-full" v-if="mode === 'suggestion'">
                            <div class="field-inner">
                                <label class="publish-field-label">
                                    <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">{{ __('seo-pro::messages.ignore_action') }}</span>
                                </label>
                            </div>

                            <select-input
                                class="w-fit-content"
                                :options="actions"
                                :value="action"
                                @input="updateAction"
                            ></select-input>
                        </div>

                        <div class="form-group publish-field w-full" v-if="mode === 'related'">
                            <p>{{ __('seo-pro::messages.do_not_suggest_entry') }}</p>
                        </div>

                        <div class="form-group publish-field w-full">
                            <div class="field-inner">
                                <label class="publish-field-label">
                                    <span class="rtl:ml-1 ltr:mr-1 v-popper--has-tooltip">{{ __('seo-pro::messages.ignore_scope') }}</span>
                                </label>
                            </div>

                            <select-input
                                class="w-fit-content"
                                :options="scopes"
                                :value="scope"
                                @input="updateScope"
                            ></select-input>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-200 dark:bg-dark-550 border-t dark:border-dark-900 flex items-center justify-end text-sm">
                <button class="text-gray dark:text-dark-150 hover:text-gray-900 dak:hover:text-dark-100"
                        @click="closeModal"
                        v-text="__('Cancel')" />
                <button class="rtl:mr-4 ltr:ml-4 btn-danger"
                        @click="save"
                        v-text="confirm"
                />
            </div>
        </div>
    </modal>
</template>

<script>
import HandlesRequestErrors from './../HandlesRequestErrors.vue';

export default {
    mixins: [HandlesRequestErrors],

    props: [
        'entryId',
        'site',
        'suggestion',
        'mode',
    ],

    data() {
        return {
            scopes: [
                {
                    value: 'entry',
                    label: __('seo-pro::messages.scope_this_entry'),
                },
                {
                    value: 'all_entries',
                    label: __('seo-pro::messages.scope_all_entries'),
                },
            ],
            actions: [
                {
                    value: 'ignore_entry',
                    label: __('seo-pro::messages.action_ignore_entry'),
                },
                {
                    value: 'ignore_phrase',
                    label: __('seo-pro::messages.action_ignore_phrase'),
                }
            ],
            action: 'ignore_entry',
            scope: 'entry',
            isSaving: false,
        };
    },

    computed: {

        title() {
            return this.mode === 'suggestion' ? __('seo-pro::messages.ignore_suggestion') : __('seo-pro::messages.ignore_related_content');
        },

        confirm() {
            return this.mode === 'suggestion' ? __('seo-pro::messages.ignore_suggestion') : __('seo-pro::messages.ignore_entry');
        },

    },

    methods: {

        updateAction(val) {
            this.action = val;
        },

        updateScope(val) {
            this.scope = val;
        },

        closeModal() {
            this.suggestion = null;
            this.$emit('closed');
        },

        save() {
            const payload = {
                site: this.site,
                action: this.mode === 'related' ? 'ignore_entry' : this.action,
                scope: this.scope,
                phrase: this.suggestion.phrase,
                entry: this.entryId,
                ignored_entry: this.suggestion.entry,
            };

            this.$axios.post(cp_url('seo-pro/links/ignored-suggestions'), payload).then(response => {
                this.$toast.success(__('seo-pro::messages.suggestion_saved'));
                this.$emit('saved');
            }).catch(err => this.handleAxiosError(err));
        },

    },

}
</script>
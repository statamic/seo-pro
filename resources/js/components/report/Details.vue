
<template>

    <modal :show="true" @closed="$emit('closed')" :dismissible="true">
        <template slot="header">
            <h1>{{ translate('addons.SeoPro::messages.page_details') }}</h1>
        </template>

        <template slot="body">

            <div class="">
                <div v-for="item in item.results" class="flex mb-2 leading-normal">
                    <div>
                        <span class="icon-status" :class="{'icon-status-live': item.status === 'pass', 'icon-status-error': item.status === 'fail', 'icon-status-warning': item.status === 'warning'}"></span>
                    </div>
                    <div class="flex-1 pl-2">
                        <span :class="{ 'font-bold': item.status !== 'pass' }">{{{ item.description }}}</span>
                        <div class="text-grey text-xs" v-if="item.comment">{{{ item.comment }}}</div>
                    </div>
                </div>
            </div>

        </template>
        <template slot="footer"><div class="font-mono text-left text-grey text-xs">{{ item.url }}</div></template>
    </modal>

</template>


<script>

export default {

    props: ['item']

}
</script>

function o(s,t,e,a,r,d,u,h){var i=typeof s=="function"?s.options:s;t&&(i.render=t,i.staticRenderFns=e,i._compiled=!0),a&&(i.functional=!0),d&&(i._scopeId="data-v-"+d);var l;if(u?(l=function(n){n=n||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,!n&&typeof __VUE_SSR_CONTEXT__<"u"&&(n=__VUE_SSR_CONTEXT__),r&&r.call(this,n),n&&n._registeredComponents&&n._registeredComponents.add(u)},i._ssrRegister=l):r&&(l=h?function(){r.call(this,(i.functional?this.parent:this).$root.$options.shadowRoot)}:r),l)if(i.functional){i._injectStyles=l;var v=i.render;i.render=function(m,f){return l.call(f),v(m,f)}}else{var p=i.beforeCreate;i.beforeCreate=p?[].concat(p,l):[l]}return{exports:s,options:i}}const g={mixins:[Fieldtype],computed:{fields(){return _.chain(this.meta.fields).map(s=>({handle:s.handle,...s.field})).values().value()}},methods:{updateKey(s,t){let e=this.value;Vue.set(e,s,t),this.update(e)}}};var C=function(){var t=this,e=t._self._c;return e("div",{staticClass:"publish-fields"},t._l(t.fields,function(a){return e("publish-field",{key:a.handle,staticClass:"form-group",attrs:{config:a,value:t.value[a.handle],meta:t.meta.meta[a.handle],"read-only":!a.localizable},on:{"meta-updated":function(r){return t.metaUpdated(a.handle,r)},focus:function(r){return t.$emit("focus")},blur:function(r){return t.$emit("blur")},input:function(r){return t.updateKey(a.handle,r)}}})}),1)},x=[],y=o(g,C,x,!1,null,null,null,null);const b=y.exports;const $={mixins:[Fieldtype],data(){return{autoBindChangeWatcher:!1,changeWatcherWatchDeep:!1,allowedFieldtypes:[]}},computed:{source(){return this.value.source},sourceField(){return this.value.source==="field"?this.value.value:null},componentName(){return this.config.field.type.replace(".","-")+"-fieldtype"},sourceTypeSelectOptions(){let s=[];return this.config.field!==!1&&s.push({label:__("seo-pro::messages.custom"),value:"custom"}),this.config.from_field!==!1&&s.unshift({label:__("seo-pro::messages.from_field"),value:"field"}),this.config.inherit!==!1&&s.unshift({label:__("seo-pro::messages.inherit"),value:"inherit"}),this.config.disableable&&s.push({label:__("seo-pro::messages.disable"),value:"disable"}),s},fieldConfig(){return Object.assign(this.config.field,{placeholder:this.config.placeholder})},placeholder(){return this.config.placeholder}},mounted(){let s=this.config.allowed_fieldtypes||["text","textarea","markdown","redactor"];this.allowedFieldtypes=s.concat(this.config.merge_allowed_fieldtypes||[])},methods:{sourceDropdownChanged(s){this.value.source=s,s!=="field"&&(this.value.value=this.meta.defaultValue,this.meta.fieldMeta=this.meta.defaultFieldMeta)},sourceFieldChanged(s){this.value.value=s},customValueChanged(s){let t=this.value;t.value=s,this.update(t)}}};var S=function(){var t=this,e=t._self._c;return e("div",{staticClass:"flex"},[e("div",{staticClass:"source-type-select pr-4"},[e("v-select",{attrs:{options:t.sourceTypeSelectOptions,reduce:a=>a.value,disabled:!t.config.localizable,clearable:!1,value:t.source},on:{input:t.sourceDropdownChanged}})],1),e("div",{staticClass:"flex-1"},[t.source==="inherit"?e("div",{staticClass:"text-sm text-grey inherit-placeholder mt-1"},[t.placeholder!==!1?[t._v(" "+t._s(t.placeholder)+" ")]:t._e()],2):t.source==="field"?e("div",{staticClass:"source-field-select"},[e("text-input",{attrs:{value:t.sourceField,disabled:!t.config.localizable},on:{input:t.sourceFieldChanged}})],1):t.source==="custom"?e(t.componentName,{tag:"component",attrs:{name:t.name,config:t.fieldConfig,value:t.value.value,meta:t.meta.fieldMeta,"read-only":!t.config.localizable,handle:"source_value"},on:{input:t.customValueChanged}}):t._e()],1)])},w=[],F=o($,S,w,!1,null,null,null,null);const R=F.exports,T={props:["status"]};var k=function(){var t=this,e=t._self._c;return e("div",[t.status==="pending"?e("span",{staticClass:"icon icon-circular-graph animation-spin"}):e("span",{staticClass:"little-dot",class:{"bg-green-600":t.status==="pass","bg-red-500":t.status==="fail","bg-yellow-dark":t.status==="warning"}})])},D=[],V=o(T,k,D,!1,null,null,null,null);const c=V.exports,P={props:["id","initialStatus","initialScore"],data(){return{status:this.initialStatus,score:this.initialScore}},created(){this.score||this.updateScore()},methods:{updateScore(){Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}`)).then(s=>{if(s.data.status==="pending"||s.data.status==="generating"){setTimeout(()=>this.updateScore(),1e3);return}this.status=s.data.status,this.score=s.data.score})}}};var z=function(){var t=this,e=t._self._c;return t.score?e("div",[e("seo-pro-status-icon",{staticClass:"inline-block mr-3",attrs:{status:t.status}}),t._v(" "+t._s(t.score)+"% ")],1):t._e()},M=[],N=o(P,z,M,!1,null,null,null,null);const O=N.exports,W={props:["item"],components:{StatusIcon:c}};var U=function(){var t=this,e=t._self._c;return e("modal",{attrs:{name:"report-details","click-to-close":!0},on:{closed:function(a){return t.$emit("closed")}}},[e("div",{staticClass:"p-0"},[e("h1",{staticClass:"p-4 bg-gray-200 border-b text-lg"},[t._v(" "+t._s(t.__("seo-pro::messages.page_details"))+" ")]),e("div",{staticClass:"modal-body"},t._l(t.item.results,function(a){return e("div",{staticClass:"flex px-4 leading-normal pb-2",class:{"bg-red-100":a.status!=="pass"}},[e("status-icon",{staticClass:"mr-3 mt-2",attrs:{status:a.status}}),e("div",{staticClass:"flex-1 mt-2 prose text-gray-700"},[e("div",{staticClass:"text-gray-900",domProps:{innerHTML:t._s(a.description)}}),a.comment?e("div",{staticClass:"text-xs",class:{"text-red-800":a.status!=="pass"},domProps:{innerHTML:t._s(a.comment)}}):t._e()])],1)}),0),e("footer",{staticClass:"px-5 py-3 bg-gray-200 rounded-b-lg border-t flex items-center font-mono text-xs"},[t._v(" "+t._s(t.item.url)+" ")])])])},q=[],E=o(W,U,q,!1,null,null,null,null);const G=E.exports,H={props:["date"],data(){return{text:null}},mounted(){this.update()},methods:{update(){this.text=moment(this.date*1e3).fromNow(),setTimeout(()=>this.update(),6e4)}}};var I=function(){var t=this,e=t._self._c;return e("span",[t._v(t._s(t.text))])},K=[],L=o(H,I,K,!1,null,null,null,null);const X=L.exports,B={components:{ReportDetails:G,RelativeDate:X,StatusIcon:c},props:["initialReport"],data(){return{loading:!1,report:this.initialReport,selected:null}},computed:{isGenerating(){return this.initialReport.status==="pending"||this.initialReport.status==="generating"},id(){return this.report.id}},mounted(){this.load()},methods:{load(){this.loading=!0,Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}`)).then(s=>{if(s.data.status==="pending"||s.data.status==="generating"){setTimeout(()=>this.load(),1e3);return}this.report=s.data,this.loading=!1})}}};var A=function(){var t=this,e=t._self._c;return e("div",[e("header",{staticClass:"flex items-center mb-6"},[e("h1",{staticClass:"flex-1"},[t._v(t._s(t.__("seo-pro::messages.seo_report")))]),t.loading?t._e():e("a",{staticClass:"btn-primary",attrs:{href:t.cp_url("seo-pro/reports/create")}},[t._v(t._s(t.__("seo-pro::messages.generate_report")))])]),t.loading?e("div",{staticClass:"card loading"},[t.isGenerating?e("loading-graphic",{attrs:{text:t.__("seo-pro::messages.report_is_being_generated")}}):e("loading-graphic")],1):!t.loading&&t.report?e("div",[e("div",{staticClass:"flex flex-wrap -mx-4"},[e("div",{staticClass:"w-1/3 px-4"},[e("div",{staticClass:"card py-2"},[e("h2",{staticClass:"text-sm text-gray-700"},[t._v(t._s(t.__("seo-pro::messages.generated")))]),e("div",{staticClass:"text-lg"},[e("relative-date",{attrs:{date:t.report.date}})],1)])]),e("div",{staticClass:"w-1/3 px-4"},[e("div",{staticClass:"card py-2"},[e("h2",{staticClass:"text-sm text-gray-700"},[t._v(t._s(t.__("Pages Crawled")))]),e("div",{staticClass:"text-lg"},[t._v(t._s(t.report.pages.length))])])]),e("div",{staticClass:"w-1/3 px-4"},[e("div",{staticClass:"card py-2"},[e("h2",{staticClass:"text-sm text-gray-700"},[t._v(t._s(t.__("Site Score")))]),e("div",{staticClass:"text-lg flex items-center"},[e("div",{staticClass:"bg-gray-200 h-3 w-full rounded flex mr-2"},[e("div",{staticClass:"h-3 rounded-l",class:{"bg-red-500":t.report.score<70,"bg-yellow-dark":t.report.score<90,"bg-green-500":t.report.score>=90},style:`width: ${t.report.score}%`})]),e("span",[t._v(t._s(t.report.score)+"%")])])])])]),e("div",{staticClass:"card p-0 mt-6"},[e("table",{staticClass:"data-table"},[e("tbody",t._l(t.report.results,function(a){return e("tr",[e("td",{staticClass:"w-8 text-center"},[e("status-icon",{attrs:{status:a.status}})],1),e("td",{staticClass:"pl-0"},[t._v(t._s(a.description))]),e("td",{staticClass:"text-grey text-right"},[t._v(t._s(a.comment))])])}),0)])]),e("div",{staticClass:"card p-0 mt-6"},[e("table",{staticClass:"data-table"},[e("tbody",t._l(t.report.pages,function(a){return e("tr",[e("td",{staticClass:"w-8 text-center"},[e("status-icon",{attrs:{status:a.status}})],1),e("td",{staticClass:"pl-0"},[e("a",{attrs:{href:""},on:{click:function(r){r.preventDefault(),t.selected=a.id}}},[t._v(t._s(a.url))]),t.selected===a.id?e("report-details",{attrs:{item:a},on:{closed:function(r){t.selected=null}}}):t._e()],1),e("td",{staticClass:"text-right text-xs pr-0 whitespace-no-wrap"},[e("a",{staticClass:"text-gray-700 mr-4 hover:text-grey-80",domProps:{textContent:t._s(t.__("Details"))},on:{click:function(r){r.preventDefault(),t.selected=a.id}}}),a.edit_url?e("a",{staticClass:"mr-4 text-gray-700 hover:text-gray-800",attrs:{target:"_blank",href:a.edit_url},domProps:{textContent:t._s(t.__("Edit"))}}):t._e()])])}),0)])])]):t._e()])},J=[],Q=o(B,A,J,!1,null,null,null,null);const Y=Q.exports;Statamic.$components.register("seo_pro-fieldtype",b);Statamic.$components.register("seo_pro_source-fieldtype",R);Statamic.$components.register("seo-pro-status-icon",c);Statamic.$components.register("seo-pro-report",Y);Statamic.$components.register("seo-pro-index-score",O);

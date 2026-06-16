import{e as m,D as l,t as p,k as r,l as t,j as d,F as u,N as g,s as _}from"./index-D9TAK_M5.js";import{V as h}from"./VendorAdminLayout-D3R9b49Z.js";import{D as b}from"./Datatable-BnTPbD48.js";import{s as v,e as y}from"./modernAlert-DuHrM4a-.js";import{_ as H}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{H as f}from"./history-B5GLFDal.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const w={name:"ActivityLogsIndex",layout:h,components:{DataTable:b,Head:m,History:f},computed:{columns(){return[{data:"user_display",name:"user_name",render:(e,a,s)=>this.userCell(s)},{data:"ip_address",name:"ip_address"},{data:"user_agent",name:"user_agent",orderable:!1,searchable:!1,render:(e,a,s)=>this.agentCell(s)},{data:"log_name",name:"log_name",render:e=>`<span class="log-chip">${this.escapeHtml(e)}</span>`},{data:"event",name:"event",render:e=>`<span class="event-chip event-chip--${this.escapeHtml(String(e).toLowerCase())}">${this.escapeHtml(e)}</span>`},{data:"subject_label",name:"subject_label"},{data:"created_at",name:"created_at"},{data:"show_url",name:"show_url",orderable:!1,searchable:!1,render:e=>`
            <button type="button" class="show-action" onclick="window.showActivityLog('${this.escapeHtml(e)}')" title="Show activity log">
              <i class="bi bi-eye"></i>
            </button>
          `}]},flash(){return this.$page.props.flash},columnDefs(){return[{targets:"_all",className:"align-middle"},{targets:7,className:"align-middle text-end action-column"}]}},mounted(){window.showActivityLog=e=>g.visit(e)},beforeUnmount(){delete window.showActivityLog},methods:{userCell(e){return`
        <button type="button" class="activity-user" onclick="window.showActivityLog('${this.escapeHtml(e.show_url)}')">
          <span class="avatar-dot">${this.escapeHtml(e.user_initials||"U")}</span>
          <span>
            <strong>${this.escapeHtml(e.user_name||"System")}</strong>
            ${e.user_role?`<em>${this.escapeHtml(e.user_role)}</em>`:""}
            <small>${this.escapeHtml(e.user_email||"")}</small>
          </span>
        </button>
      `},agentCell(e){return`
        <span class="agent-line">
          <i class="bi bi-display" title="From Desktop"></i><span>${this.escapeHtml(e.is_desktop||"-")}</span>
          <i class="bi bi-windows" title="Platform"></i><span>${this.escapeHtml(e.platform||"-")}</span>
          <i class="bi bi-globe2" title="Browser"></i><span>${this.escapeHtml(e.browser||"-")}</span>
        </span>
      `},escapeHtml(e=""){return String(e).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;")}},watch:{flash:{handler(e){e!=null&&e.message&&v(e.message),e!=null&&e.error&&y(e.error)},immediate:!0,deep:!0}}},$={class:"activity-page"},L={class:"activity-title"};function A(e,a,s,D,C,n){const i=l("Head"),o=l("History"),c=l("DataTable");return _(),p(u,null,[r(i,{title:"Activity Logs"}),t("div",$,[t("div",L,[r(o,{size:20}),a[0]||(a[0]=t("h1",null,"Activity Logs",-1))]),r(c,{id:"activityLogsTable",url:e.route("vendor.activities.activity-logs.getdata"),columns:n.columns,columnDefs:n.columnDefs,order:[[6,"desc"]],searchPlaceholder:"Search here...",wrapperClass:"activity-table"},{header:d(()=>[...a[1]||(a[1]=[t("tr",null,[t("th",null,"User"),t("th",null,"IP Address"),t("th",null,"Agent"),t("th",null,"Log Name"),t("th",null,"Event"),t("th",null,"Subject"),t("th",null,"Logged at"),t("th")],-1)])]),_:1},8,["url","columns","columnDefs"])])],64)}const U=H(w,[["render",A],["__scopeId","data-v-f092b9d0"]]);export{U as default};

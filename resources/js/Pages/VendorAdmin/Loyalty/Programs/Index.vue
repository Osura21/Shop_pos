<template>
  <IndexTable
    title="Loyalty Programs"
    subtitle="Manage earning rules and point expiry."
    icon="bi bi-"
    permission="loyalty-programs"
    table-id="loyaltyProgramsTable"
    data-route="vendor.loyalty.programs.getdata"
    create-route="vendor.loyalty.programs.create"
    create-label="Create Loyalty Program"
    edit-route="vendor.loyalty.programs.edit"
    destroy-route="vendor.loyalty.programs.destroy"
    deleteModalTitle="Delete this earning rule"
    deleteModalCancelLabel="Keep Earning Rule" 
    deleteModalConfirmLabel="Delete Earning Rule"
    :headers="headers"
    :columns="columns"
  />
</template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import IndexTable from '../Shared/IndexTable.vue'
import { actionsColumn, checkboxColumn, rawColumn, textColumn } from '../Shared/helpers'

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const headers = [
  { label: '', style: 'width:44px' },
  { label: 'Name' },
  { label: 'Earning' },
  { label: 'Points Expire After' },
  { label: 'Status' },
  { label: 'Created At' },
  { label: 'Updated At' },
  { label: 'Actions', class: 'text-end' },
]

const columns = [
  checkboxColumn(),
  textColumn('name'),
  textColumn('earning_rate'),
  textColumn('points_expire_after_days'),
  rawColumn('status_badge', 'is_active'),
  textColumn('created_at'),
  textColumn('updated_at'),
]


watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) {
      alertSuccess((flash.message))
    }

    if (flash?.error) {
      alertError((flash.error))
    }
  },
  { immediate: true }
)

</script>

<template>
  <IndexTable
    title="Loyalty Promotions"
    subtitle="Boost earning with multipliers, bonuses, and member campaigns."
    icon="bi bi-"
    permission="loyalty-promotions"
    table-id="loyaltyPromotionsTable"
    data-route="vendor.loyalty.promotions.getdata"
    create-route="vendor.loyalty.promotions.create"
    create-label="Create Loyalty Promotion"
    deleteModalTitle="Delete this promotion"
    deleteModalCancelLabel="Keep Promotion"
    deleteModalConfirmLabel="Delete Promotion"
    edit-route="vendor.loyalty.promotions.edit"
    destroy-route="vendor.loyalty.promotions.destroy"
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
  { label: 'Program' },
  { label: 'Type' },
  { label: 'Value' },
  { label: 'Used' },
  { label: 'Customers' },
  { label: 'Status' },
  { label: 'Created At' },
  { label: 'Updated At' },
  { label: 'Actions', class: 'text-end' },
]

const columns = [
  checkboxColumn(),
  textColumn('name'),
  textColumn('program_name'),
  rawColumn('type_badge', 'type'),
  textColumn('value'),
  textColumn('used_count'),
  textColumn('customers_count'),
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

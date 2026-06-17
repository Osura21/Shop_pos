<template>
  <IndexTable
    title="Loyalty Rewards"
    subtitle="Create point redemptions for discounts, vouchers, free items, and tier upgrades."
    icon="bi bi-"
    permission="loyalty-rewards"
    table-id="loyaltyRewardsTable"
    data-route="vendor.loyalty.rewards.getdata"
    create-route="vendor.loyalty.rewards.create"
    create-label="Create Loyalty Reward"
    deleteModalTitle="Delete this reward"
    deleteModalCancelLabel="Keep Reward"
    deleteModalConfirmLabel="Delete Reward"
    edit-route="vendor.loyalty.rewards.edit"
    destroy-route="vendor.loyalty.rewards.destroy"
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
import { actionsColumn, checkboxColumn, imageNameColumn, rawColumn, textColumn } from '../Shared/helpers'


const page = usePage()


defineOptions({ layout: VendorAdminLayout })

const headers = [
  { label: '', style: 'width:44px' },
  { label: 'Name' },
  { label: 'Program' },
  { label: 'Type' },
  { label: 'Points' },
  { label: 'Redeemed' },
  { label: 'Customers' },
  { label: 'Status' },
  { label: 'Created At' },
  { label: 'Updated At' },
  { label: 'Actions', class: 'text-end' },
]

const columns = [
  checkboxColumn(),
imageNameColumn('name', 'icon_url', 'R'),
  textColumn('program_name'),
  rawColumn('type_badge', 'type'),
  textColumn('points_cost'),
  textColumn('redeemed_count'),
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

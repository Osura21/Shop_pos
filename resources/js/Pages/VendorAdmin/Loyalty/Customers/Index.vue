<template>
  <IndexTable
    title="Loyalty Customers"
    subtitle="View customer loyalty balances, tiers, and activity."
    icon="bi bi-person-"
    table-id="loyaltyCustomersTable"
    data-route="vendor.loyalty.customers.getdata"
    :headers="headers"
    :columns="columns"
    :order="[[5, 'desc']]"
  />
</template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import IndexTable from '../Shared/IndexTable.vue'
import { textColumn } from '../Shared/helpers'

defineOptions({ layout: VendorAdminLayout })


const page = usePage()


const headers = [
  { label: 'Customer' },
  { label: 'Program' },
  { label: 'Tier' },
  { label: 'Points Balance' },
  { label: 'Lifetime Points' },
  { label: 'Last Earned At' },
  { label: 'Last Redeemed At' },
  { label: 'Created At' },
]

const columns = [
  textColumn('customer_name'),
  textColumn('program_name'),
  textColumn('tier_name'),
  textColumn('points_balance'),
  textColumn('lifetime_points'),
  textColumn('last_earned_at'),
  textColumn('last_redeemed_at'),
  textColumn('created_at'),
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

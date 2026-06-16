<template>
  <IndexTable
    title="Loyalty Transactions"
    subtitle="Audit points earned, redeemed, adjusted, and expired."
    icon="bi bi-arrow-"
    table-id="loyaltyTransactionsTable"
    data-route="vendor.loyalty.transactions.getdata"
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
import { rawColumn, textColumn } from '../Shared/helpers'

defineOptions({ layout: VendorAdminLayout })


const page = usePage()


const headers = [
  { label: 'Customer' },
  { label: 'Description' },
  { label: 'Type' },
  { label: 'Points' },
  { label: 'Amount' },
  { label: 'Created At' },
]

const columns = [
  textColumn('customer_name'),
  textColumn('description'),
  rawColumn('type_badge', 'type'),
  textColumn('points'),
  textColumn('amount'),
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

<template>
  <IndexTable
    title="Loyalty Gifts"
    subtitle="Track issued reward gifts and voucher codes."
    icon="bi bi-"
    table-id="loyaltyGiftsTable"
    data-route="vendor.loyalty.gifts.getdata"
    :headers="headers"
    :columns="columns"
    :order="[[8, 'desc']]"
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
  { label: 'Program' },
  { label: 'Reward' },
  { label: 'Status' },
  { label: 'Type' },
  { label: 'Points Spent' },
  { label: 'Valid Until' },
  { label: 'Used At' },
  { label: 'Created At' },
]

const columns = [
  textColumn('customer_name'),
  textColumn('program_name'),
  textColumn('reward_name'),
  rawColumn('status_badge', 'status'),
  rawColumn('type_badge', 'type'),
  textColumn('points_spent'),
  textColumn('valid_until'),
  textColumn('used_at'),
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

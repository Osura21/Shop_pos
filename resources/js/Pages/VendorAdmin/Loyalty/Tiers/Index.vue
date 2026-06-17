<template>
  <IndexTable
    title="Loyalty Tiers"
    subtitle="Define spend levels, multipliers, and tier benefits."
    icon="bi bi-"
    permission="loyalty-tiers"
    table-id="loyaltyTiersTable"
    data-route="vendor.loyalty.tiers.getdata"
    create-route="vendor.loyalty.tiers.create"
    deleteModalTitle="Delete this tier"
    deleteModalCancelLabel="Keep Tier"
    deleteModalConfirmLabel="Delete Tier"
    create-label="Create Loyalty Tier"
    edit-route="vendor.loyalty.tiers.edit"
    destroy-route="vendor.loyalty.tiers.destroy"
    :headers="headers"
    :columns="columns"
  />
</template>

<script setup>
import { Head, router, useForm, usePage, } from '@inertiajs/vue3'
import { computed, onMounted, watch, onBeforeUnmount, onUnmounted, ref } from 'vue'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import IndexTable from '../Shared/IndexTable.vue'
import { actionsColumn, checkboxColumn, imageNameColumn, rawColumn, textColumn } from '../Shared/helpers'

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const headers = [
  { label: '', style: 'width:44px' },
  { label: 'Name' },
  { label: 'Program' },
  { label: 'Minimum Spend' },
  { label: 'Multiplier' },
  { label: 'Status' },
  { label: 'Created At' },
  { label: 'Updated At' },
  { label: 'Actions', class: 'text-end' },
]

const columns = [
  checkboxColumn(),
imageNameColumn('name', 'icon_url', 'T'),
  textColumn('program_name'),
  textColumn('minimum_spend'),
  textColumn('multiplier'),
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

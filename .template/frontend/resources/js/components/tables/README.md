# components/tables — Data Table Patterns
READ-ONLY reference.

## Pattern
```vue
<VTextField v-model="search" placeholder="Cari..." prepend-inner-icon="ri-search-line" />
<VDataTable
  :headers="headers"
  :items="items"
  :search="search"
>
  <template #item.actions="{ item }">
    <IconBtn @click="onEdit(item)"><VIcon icon="ri-edit-line" /></IconBtn>
    <IconBtn @click="onDelete(item)" color="error"><VIcon icon="ri-delete-bin-line" /></IconBtn>
  </template>
</VDataTable>
```

Gunakan untuk semua halaman list.

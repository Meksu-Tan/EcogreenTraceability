# components/dialogs — Dialog Patterns
READ-ONLY reference.

## Pattern
```vue
<VDialog v-model="isOpen" max-width="500">
  <VCard>
    <VCardTitle>{{ title }}</VCardTitle>
    <VCardText>...</VCardText>
    <VCardActions>
      <VSpacer />
      <VBtn variant="text" @click="isOpen = false">Batal</VBtn>
      <VBtn color="primary" @click="onConfirm">Konfirmasi</VBtn>
    </VCardActions>
  </VCard>
</VDialog>
```

Gunakan pola ini untuk: delete confirm, form modal, alert dialog.

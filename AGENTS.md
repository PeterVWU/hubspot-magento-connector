# Repository guidance

## Sales-rep ownership sync

- Forward sync maps Magento `salesrep_rep_id` values to HubSpot owner IDs through
  `src/config/salesrep-mapping.js`.
- Reverse sync maps HubSpot contact owners back to Magento customer sales reps.
  An empty HubSpot owner normally clears the Magento assignment by setting
  `salesrep_rep_id` to `0`.
- Magento reps listed in `OWNER_REVERSE_PROTECTED_SALESREP_IDS` are exceptions:
  HubSpot must never overwrite their existing Magento customer assignments.
- Rep `175` (Anissa, `anissa@vapeguysinc.com`) is protected by default. Keep this
  default unless the business requirement changes explicitly.
- To protect additional reps, add their Magento IDs to the comma-separated
  environment variable, retaining `175`, for example:
  `OWNER_REVERSE_PROTECTED_SALESREP_IDS=175,200,201`.
- Protected reps are not excluded from forward synchronization. Their customers
  and orders must continue syncing to HubSpot; when no owner mapping exists, the
  connector leaves the HubSpot owner unchanged.
- Apply protection to every path that writes HubSpot ownership back to Magento,
  including normal reverse sync and quarantined retries. Add or update tests for
  both paths when changing this behavior.

## Verification

Run `npm test` after changing synchronization, mapping, or configuration logic.

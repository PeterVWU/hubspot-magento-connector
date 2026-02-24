/**
 * Maps Magento admin user IDs (salesrep_rep_id attribute) to HubSpot owner IDs.
 * Used during customer and order sync to set hubspot_owner_id on contacts and deals.
 *
 * Unmapped reps (inactive, no HubSpot account, or unknown) are left as null —
 * contacts/deals will be imported without an assigned owner.
 *
 * Magento ID → HubSpot owner ID
 */
export const SALESREP_OWNER_MAP = {
  '24':  '162307854', // Ethan N           → Ethan Nishimura
  '73':  '162267329', // Anthony Barr      → Anthony Barr
  '81':  '82803951',  // Will Sauer        → William Sauer
  '90':  '160430368', // Peter Chen        → peter@vapewholesaleusa.com
  '97':  '82803951',  // Will Sauer (alt)  → William Sauer
  '121': '162267328', // Olivia J          → Olivia J
  '129': '160223418', // Louie Siason      → Louie Siason
  '130': '159885664', // Marco Blanco      → Marco Blanco
  '131': '160222010', // Kenneth Taboclaon → kenneth@vapewholesaleusa.com
  '143': '162267324', // Alina Zhantayeva  → Alina Zhant.
  '146': '162576605', // Tammy Tu          → Tammy Tu
  '153': '162267326', // Crystal L         → Crystal Lu
};

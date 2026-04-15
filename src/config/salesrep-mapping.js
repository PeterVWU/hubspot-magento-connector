/**
 * Sales rep mapping between Magento admin users (salesrep_rep_id) and
 * HubSpot owner IDs. Source of truth: hubspot-magento-users-map.csv.
 *
 * Unmapped reps (inactive, no HubSpot account, or unknown) are left as null —
 * contacts/deals will be synced without an assigned owner.
 */

// Magento admin ID → HubSpot owner ID (forward sync: Magento → HubSpot)
export const SALESREP_OWNER_MAP = {
  '24':  '162307854', // Ethan Nishimura
  '73':  '162267329', // Anthony Barr
  '81':  '82803951',  // William Sauer
  '90':  '160430368', // Peter Chen
  '121': '162267328', // Olivia J
  '129': '160223418', // Louie Siason
  '130': '159885664', // Marco Blanco
  '143': '162267324', // Alina Zhant.
  '148': '163699950', // Mya Mya
  '153': '162267326', // Crystal Lu
  '164': '163699961', // WU Naffy
};

// HubSpot owner ID → Magento admin ID (reverse sync: HubSpot → Magento)
export const OWNER_SALESREP_MAP = {
  '162307854': '24',  // Ethan Nishimura
  '162267329': '73',  // Anthony Barr
  '82803951':  '81',  // William Sauer
  '160430368': '90',  // Peter Chen
  '162267328': '121', // Olivia J
  '160223418': '129', // Louie Siason
  '159885664': '130', // Marco Blanco
  '162267324': '143', // Alina Zhant.
  '163699950': '148', // Mya Mya
  '162267326': '153', // Crystal Lu
  '163699961': '164', // WU Naffy
};

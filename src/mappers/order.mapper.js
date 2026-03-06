const STAGE_MAP = {
  pending: 'Checkout Completed',
  pending_payment: 'Checkout Completed',
  processing: 'Processing',
  complete: 'Completed',
  canceled: 'Cancelled',
  closed: 'Cancelled',
  holded: 'On Hold',
};

export function mapOrderToDeal(order, pipelineId, stageMap, ownerId = null) {
  const stageName = STAGE_MAP[order.status] || 'Checkout Completed';
  const stageId = stageMap[stageName];

  return {
    dealname: `Order #${order.increment_id}`,
    amount: String(order.grand_total || '0'),
    pipeline: pipelineId,
    dealstage: stageId || Object.values(stageMap)[0],
    order_number: String(order.increment_id),
    closedate: order.created_at ? new Date(order.created_at).getTime() : undefined,
    ...(ownerId ? { hubspot_owner_id: ownerId } : {}),
  };
}

export function mapOrderItemToLineItem(item, hubspotProductId) {
  return {
    name: item.name || '',
    quantity: String(item.qty_ordered || 1),
    price: String(item.price_incl_tax || item.price || '0'),
    hs_sku: item.sku || '',
    ...(hubspotProductId ? { hs_product_id: hubspotProductId } : {}),
  };
}

export function getOrderItemsForSync(order) {
  const items = order.items || [];
  const parentIds = new Set(
    items.filter(i => i.product_type === 'configurable').map(i => i.item_id),
  );
  return items.filter(i => !parentIds.has(i.item_id));
}

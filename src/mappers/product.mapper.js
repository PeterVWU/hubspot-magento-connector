export function mapProductToHubspot(product) {
  const description = (product.custom_attributes || [])
    .find(a => a.attribute_code === 'description')?.value || '';

  return {
    name: product.name || '',
    hs_sku: product.sku || '',
    price: String(product.price || '0'),
    description: stripHtml(description),
  };
}

function stripHtml(html) {
  return html.replace(/<[^>]*>/g, '').trim();
}

import type { CatalogNode } from '../api';

/** Human-readable name of a catalog node: services use `title`, others use `name`. */
export function catalogNodeLabel(node: CatalogNode): string {
  return node.item_type === 'service' ? node.title ?? 'Untitled service' : node.name ?? 'Unnamed item';
}

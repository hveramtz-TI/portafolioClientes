import type { CatalogItemType, CatalogNode } from '../api';

/** The only legal parent type for each catalog item type. Rubros are roots. */
const PARENT_TYPE: Record<CatalogItemType, CatalogItemType | null> = {
  rubro: null,
  categoria: 'rubro',
  service: 'categoria',
};

/** The parent type a node of `type` may be attached to, or `null` for roots. */
export function parentTypeFor(type: CatalogItemType): CatalogItemType | null {
  return PARENT_TYPE[type];
}

/** Every node of the given type, depth-first, across the whole tree. */
export function collectNodesByType(nodes: CatalogNode[], type: CatalogItemType): CatalogNode[] {
  const result: CatalogNode[] = [];

  for (const node of nodes) {
    if (node.item_type === type) result.push(node);
    if (node.children?.length) result.push(...collectNodesByType(node.children, type));
  }

  return result;
}

/**
 * Type-coherent parent candidates for a node of `childType`, optionally
 * excluding one id (the node's current parent, which cannot be re-selected).
 */
export function parentCandidatesFor(
  nodes: CatalogNode[],
  childType: CatalogItemType,
  excludeId?: string | null,
): CatalogNode[] {
  const parentType = parentTypeFor(childType);
  if (!parentType) return [];

  return collectNodesByType(nodes, parentType).filter((node) => node.id !== excludeId);
}

'use client';

import { Link2, Pencil, Plus, Power, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { CatalogItemType, CatalogNode } from '../api';
import { CatalogBadges } from './catalog-badges';
import { catalogNodeLabel as nodeLabel } from './catalog-node-label';

interface CatalogTreeProps {
  nodes: CatalogNode[];
  onEdit: (node: CatalogNode) => void;
  onStatus: (node: CatalogNode) => void;
  onDelete: (node: CatalogNode) => void;
  onAddChild: (parent: CatalogNode, childType: CatalogItemType) => void;
  onAttach: (orphan: CatalogNode) => void;
}

type TreeNodeProps = Omit<CatalogTreeProps, 'nodes'> & { node: CatalogNode; depth: number };

/** A rubro can only own categorias and a categoria can only own services. */
function childTypeFor(node: CatalogNode): CatalogItemType | null {
  if (node.item_type === 'rubro') return 'categoria';
  if (node.item_type === 'categoria') return 'service';
  return null;
}

/** Only a categoria or service can be unattached: a rubro is a root by design. */
function isOrphan(node: CatalogNode): boolean {
  return node.item_type !== 'rubro' && node.parent_fork_id === null;
}

function TreeNode({ node, depth, onEdit, onStatus, onDelete, onAddChild, onAttach }: TreeNodeProps) {
  const childType = childTypeFor(node);
  const orphan = isOrphan(node);

  return (
    <li className="border-l border-border pl-4" style={{ marginLeft: depth * 12 }}>
      <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-3">
            <h3 className="font-medium">{nodeLabel(node)}</h3>
            <CatalogBadges node={node} />
            {orphan ? <Badge variant="outline" className="border-destructive/50 text-destructive">Unattached</Badge> : null}
          </div>
          <p className="mt-1 text-sm text-muted-foreground">
            {node.item_type === 'service' && node.value !== null && node.value !== undefined ? `$${node.value.toLocaleString('es-CL')}` : node.description || 'No description'}
            {node.tags?.length ? ` · ${node.tags.join(', ')}` : ''}
          </p>
        </div>
        <div className="flex shrink-0 gap-1">
          {childType ? (
            <Button variant="ghost" size="sm" onClick={() => onAddChild(node, childType)}>
              <Plus />
              {childType === 'categoria' ? 'Add category' : 'Add service'}
            </Button>
          ) : null}
          {orphan ? (
            <Button variant="ghost" size="sm" aria-label={`Attach ${nodeLabel(node)}`} onClick={() => onAttach(node)}>
              <Link2 />
              Attach
            </Button>
          ) : null}
          <Button variant="ghost" size="icon-sm" aria-label={`Edit ${nodeLabel(node)}`} onClick={() => onEdit(node)}><Pencil /></Button>
          <Button variant="ghost" size="icon-sm" aria-label={`${node.status === 'activo' ? 'Deactivate' : 'Reactivate'} ${nodeLabel(node)}`} onClick={() => onStatus(node)}><Power /></Button>
          <Button variant="ghost" size="icon-sm" aria-label={`Delete ${nodeLabel(node)}`} onClick={() => onDelete(node)}><Trash2 /></Button>
        </div>
      </div>
      {node.children?.length ? (
        <ul className="mt-3 space-y-3">{node.children.map((child) => <TreeNode key={child.id} {...{ node: child, depth: depth + 1, onEdit, onStatus, onDelete, onAddChild, onAttach }} />)}</ul>
      ) : null}
    </li>
  );
}

export function CatalogTree(props: CatalogTreeProps) {
  return <ul className="space-y-3">{props.nodes.map((node) => <TreeNode key={node.id} {...props} node={node} depth={0} />)}</ul>;
}

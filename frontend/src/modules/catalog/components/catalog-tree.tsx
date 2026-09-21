'use client';

import { Pencil, Power, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { CatalogNode } from '../api';
import { CatalogBadges } from './catalog-badges';

interface CatalogTreeProps {
  nodes: CatalogNode[];
  onEdit: (node: CatalogNode) => void;
  onStatus: (node: CatalogNode) => void;
  onDelete: (node: CatalogNode) => void;
}

type TreeNodeProps = Omit<CatalogTreeProps, 'nodes'> & { node: CatalogNode; depth: number };

function nodeLabel(node: CatalogNode): string {
  return node.item_type === 'service' ? node.title ?? 'Untitled service' : node.name ?? 'Unnamed item';
}

function TreeNode({ node, depth, onEdit, onStatus, onDelete }: TreeNodeProps) {
  return (
    <li className="border-l border-border pl-4" style={{ marginLeft: depth * 12 }}>
      <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-3">
            <h3 className="font-medium">{nodeLabel(node)}</h3>
            <CatalogBadges node={node} />
          </div>
          <p className="mt-1 text-sm text-muted-foreground">
            {node.item_type === 'service' && node.value !== null && node.value !== undefined ? `$${node.value.toLocaleString('es-CL')}` : node.description || 'No description'}
            {node.tags?.length ? ` · ${node.tags.join(', ')}` : ''}
          </p>
        </div>
        <div className="flex shrink-0 gap-1">
          <Button variant="ghost" size="icon-sm" aria-label={`Edit ${nodeLabel(node)}`} onClick={() => onEdit(node)}><Pencil /></Button>
          <Button variant="ghost" size="icon-sm" aria-label={`${node.status === 'activo' ? 'Deactivate' : 'Reactivate'} ${nodeLabel(node)}`} onClick={() => onStatus(node)}><Power /></Button>
          <Button variant="ghost" size="icon-sm" aria-label={`Delete ${nodeLabel(node)}`} onClick={() => onDelete(node)}><Trash2 /></Button>
        </div>
      </div>
      {node.children?.length ? (
        <ul className="mt-3 space-y-3">{node.children.map((child) => <TreeNode key={child.id} {...{ node: child, depth: depth + 1, onEdit, onStatus, onDelete }} />)}</ul>
      ) : null}
    </li>
  );
}

export function CatalogTree(props: CatalogTreeProps) {
  return <ul className="space-y-3">{props.nodes.map((node) => <TreeNode key={node.id} {...props} node={node} depth={0} />)}</ul>;
}

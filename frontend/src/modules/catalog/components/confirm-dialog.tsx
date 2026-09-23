'use client';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import type { CatalogNode } from '../api';
import { catalogNodeLabel } from './catalog-node-label';

export interface ConfirmDialogProps {
  open: boolean;
  item: CatalogNode;
  action: 'delete' | 'deactivate';
  onConfirm: () => void;
  onCancel: () => void;
}

function hasDescendants(item: CatalogNode): boolean {
  return (item.children?.length ?? 0) > 0;
}

/**
 * Descendant-aware confirmation wording. A node with children warns about the
 * whole subtree; a leaf warns only about itself.
 */
export function confirmMessage(item: CatalogNode, action: 'delete' | 'deactivate'): string {
  const subject = hasDescendants(item)
    ? `"${catalogNodeLabel(item)}" and its descendants`
    : `"${catalogNodeLabel(item)}"`;

  return action === 'delete'
    ? `${subject} will be permanently deleted. This action cannot be undone.`
    : `${subject} will be deactivated. You can reactivate it later.`;
}

export function ConfirmDialog({ open, item, action, onConfirm, onCancel }: ConfirmDialogProps) {
  return (
    <Dialog open={open} onOpenChange={(next) => !next && onCancel()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{action === 'delete' ? 'Delete item' : 'Deactivate item'}</DialogTitle>
          <DialogDescription>{confirmMessage(item, action)}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button type="button" variant="outline" onClick={onCancel}>
            Cancel
          </Button>
          <Button type="button" variant={action === 'delete' ? 'destructive' : 'secondary'} onClick={onConfirm}>
            {action === 'delete' ? 'Delete' : 'Deactivate'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

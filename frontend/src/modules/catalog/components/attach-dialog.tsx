'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { CatalogNode } from '../api';
import { catalogNodeLabel } from './catalog-node-label';
import { parentCandidatesFor } from './catalog-node-candidates';

export interface AttachDialogProps {
  open: boolean;
  orphan: CatalogNode;
  tree: CatalogNode[];
  onAttach: (parentForkId: string) => void;
  onCancel: () => void;
  /** Server message from a rejected attach, shown inside the dialog. */
  error?: string;
}

/**
 * Repairs an orphan fork root (a categoria or service with no parent) by
 * attaching it to a type-coherent parent already present in the user's tree.
 */
export function AttachDialog({ open, orphan, tree, onAttach, onCancel, error }: AttachDialogProps) {
  const [selected, setSelected] = useState<string | null>(null);
  const candidates = parentCandidatesFor(tree, orphan.item_type);
  const label = catalogNodeLabel(orphan);

  // Reset the selection when the dialog opens or targets another orphan.
  const resetKey = `${open ? 'open' : 'closed'}|${orphan.id}`;
  const [activeKey, setActiveKey] = useState(resetKey);
  if (activeKey !== resetKey) {
    setActiveKey(resetKey);
    setSelected(null);
  }

  return (
    <Dialog open={open} onOpenChange={(next) => !next && onCancel()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Attach item</DialogTitle>
          <DialogDescription>
            {candidates.length > 0
              ? `Choose a parent for "${label}".`
              : `"${label}" has no compatible parent to attach to.`}
          </DialogDescription>
        </DialogHeader>
        {error ? (
          <div role="alert" className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </div>
        ) : null}
        {candidates.length > 0 ? (
          <div className="space-y-1">
            <span className="text-sm font-medium">Parent</span>
            <Select value={selected ?? ''} onValueChange={setSelected}>
              <SelectTrigger aria-label="Parent">
                <SelectValue placeholder="Choose a parent" />
              </SelectTrigger>
              <SelectContent>
                {candidates.map((candidate) => (
                  <SelectItem key={candidate.id} value={candidate.id}>
                    {catalogNodeLabel(candidate)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        ) : null}
        <DialogFooter>
          <Button type="button" variant="outline" onClick={onCancel}>
            Cancel
          </Button>
          <Button type="button" disabled={selected === null} onClick={() => selected && onAttach(selected)}>
            Attach
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

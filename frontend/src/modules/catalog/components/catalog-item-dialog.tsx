'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { CatalogItemType, CatalogNode, CreatePersonalItemInput } from '../api';

interface CatalogItemDialogProps {
  open: boolean;
  item?: CatalogNode | null;
  onOpenChange: (open: boolean) => void;
  onSave: (input: CreatePersonalItemInput) => Promise<void>;
}

export function CatalogItemDialog({ open, item, onOpenChange, onSave }: CatalogItemDialogProps) {
  const type: CatalogItemType = item?.item_type ?? 'rubro';
  const [name, setName] = useState(item?.name ?? item?.title ?? '');
  const [description, setDescription] = useState(item?.description ?? '');
  const [value, setValue] = useState(item?.value?.toString() ?? '');
  const [saving, setSaving] = useState(false);

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      await onSave({ ...(type === 'service' ? { title: name, value: value ? Number(value) : undefined } : { name }), description });
      onOpenChange(false);
    } finally {
      setSaving(false);
    }
  }

  return <Dialog open={open} onOpenChange={onOpenChange}><DialogContent><DialogHeader><DialogTitle>{item ? 'Edit personal item' : 'New personal rubro'}</DialogTitle><DialogDescription>{item ? 'Update the visible values for this item.' : 'Create an item that belongs only to your catalog.'}</DialogDescription></DialogHeader><form onSubmit={submit} className="space-y-4"><Input aria-label={type === 'service' ? 'Title' : 'Name'} value={name} onChange={(event) => setName(event.target.value)} required placeholder={type === 'service' ? 'Service title' : 'Rubro name'} /><Input aria-label="Description" value={description} onChange={(event) => setDescription(event.target.value)} placeholder="Description" />{type === 'service' ? <Input aria-label="Value" type="number" min="0" value={value} onChange={(event) => setValue(event.target.value)} placeholder="Value" /> : null}<DialogFooter><Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button><Button type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save'}</Button></DialogFooter></form></DialogContent></Dialog>;
}

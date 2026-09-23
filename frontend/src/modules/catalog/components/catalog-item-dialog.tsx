'use client';

import { useState } from 'react';
import { Undo2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { CatalogItemType, CatalogNode, CreatePersonalItemInput } from '../api';
import { buildCatalogInput, toFormErrors, toInputValue, type FormErrors } from './catalog-item-dialog.helpers';
import { catalogNodeLabel } from './catalog-node-label';
import { parentCandidatesFor } from './catalog-node-candidates';

export interface CatalogCreateIntent {
  type: CatalogItemType;
  /** Absent for a standalone rubro create; set to the parent fork id for child creates. */
  parentForkId?: string | null;
}

export interface CatalogItemDialogProps {
  open: boolean;
  item?: CatalogNode | null;
  createIntent?: CatalogCreateIntent | null;
  /** The user's catalog tree, used to offer type-coherent move destinations. */
  tree?: CatalogNode[];
  onOpenChange: (open: boolean) => void;
  onSave: (input: CreatePersonalItemInput) => Promise<void>;
  validationErrors?: Record<string, string[]>;
  formError?: string;
}

const TYPE_NOUN: Record<CatalogItemType, string> = {
  rubro: 'rubro',
  categoria: 'category',
  service: 'service',
};

/** Backend-enforced service tag whitelist (personalization R4, S4.1). */
export const SERVICE_TAGS = ['frontend', 'backend', 'fullstack', 'devops', 'mobile'] as const;

function dialogTitle(type: CatalogItemType, isEdit: boolean): string {
  return `${isEdit ? 'Edit' : 'New'} ${TYPE_NOUN[type]}`;
}

export function CatalogItemDialog({ open, item, createIntent, tree, onOpenChange, onSave, validationErrors, formError }: CatalogItemDialogProps) {
  const isEdit = Boolean(item);
  const type: CatalogItemType = item?.item_type ?? createIntent?.type ?? 'rubro';
  const isService = type === 'service';

  const [name, setName] = useState(item?.name ?? '');
  const [title, setTitle] = useState(item?.title ?? '');
  const [description, setDescription] = useState(item?.description ?? '');
  const [value, setValue] = useState(toInputValue(item?.value));
  const [tags, setTags] = useState<string[]>(item?.tags ?? []);
  const [destination, setDestination] = useState<string | null>(null);
  const [reverted, setReverted] = useState<string[]>([]);
  const [localErrors, setLocalErrors] = useState<FormErrors>({});
  const [saving, setSaving] = useState(false);

  const fieldErrors = validationErrors ?? localErrors.validationErrors;
  const topLevelError = formError ?? localErrors.formError;

  // Only a service can move, and only to a categoria other than its current parent.
  const destinations = isEdit && isService
    ? parentCandidatesFor(tree ?? [], 'service', item?.parent_fork_id)
    : [];

  // Reset the form when the dialog opens or targets another item, without an effect.
  const resetKey = `${open ? 'open' : 'closed'}|${item?.id ?? 'new'}|${createIntent?.type ?? ''}|${createIntent?.parentForkId ?? ''}`;
  const [activeKey, setActiveKey] = useState(resetKey);
  if (activeKey !== resetKey) {
    setActiveKey(resetKey);
    setName(item?.name ?? '');
    setTitle(item?.title ?? '');
    setDescription(item?.description ?? '');
    setValue(toInputValue(item?.value));
    setTags(item?.tags ?? []);
    setDestination(null);
    setReverted([]);
    setLocalErrors({});
  }

  function clearReverted(field: string) {
    setReverted((current) => current.filter((entry) => entry !== field));
  }

  function revertField(field: string) {
    setReverted((current) => (current.includes(field) ? current : [...current, field]));
    if (field === 'name') setName('');
    if (field === 'title') setTitle('');
    if (field === 'description') setDescription('');
    if (field === 'value') setValue('');
    if (field === 'tags') setTags([]);
  }

  function toggleTag(tag: string, checked: boolean) {
    setTags((current) => (checked ? [...current, tag] : current.filter((selected) => selected !== tag)));
    clearReverted('tags');
  }

  function isOverridden(field: string): boolean {
    return Boolean(item?.overridden_fields.includes(field));
  }

  function revertControl(field: string) {
    if (!isOverridden(field)) return null;
    return (
      <Button type="button" variant="ghost" size="sm" aria-label={`Revert ${field} to base`} onClick={() => revertField(field)}>
        <Undo2 />
        Revert to base
      </Button>
    );
  }

  function fieldPlaceholder(field: string, fallback: string): string {
    return reverted.includes(field) ? 'Inherited from base' : fallback;
  }

  function fieldError(field: string) {
    const message = fieldErrors?.[field]?.[0];
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setLocalErrors({});
    try {
      const parentForkId = isEdit ? destination : createIntent?.parentForkId;
      const input = buildCatalogInput(type, item, { name, title, description, value, tags, reverted }, parentForkId);
      await onSave(input);
      onOpenChange(false);
    } catch (error) {
      if (!validationErrors && !formError) {
        setLocalErrors(toFormErrors(error));
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{dialogTitle(type, isEdit)}</DialogTitle>
          <DialogDescription>{isEdit ? 'Update the visible values for this item.' : 'Create an item that belongs only to your catalog.'}</DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          {topLevelError ? (
            <div role="alert" className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {topLevelError}
            </div>
          ) : null}
          {isService ? (
            <div className="space-y-1">
              <div className="flex items-end gap-2">
                <Input
                  aria-label="Title"
                  className="flex-1"
                  value={title}
                  onChange={(event) => { setTitle(event.target.value); clearReverted('title'); }}
                  required={!isEdit}
                  placeholder={fieldPlaceholder('title', 'Service title')}
                />
                {revertControl('title')}
              </div>
              {fieldError('title')}
            </div>
          ) : (
            <div className="space-y-1">
              <div className="flex items-end gap-2">
                <Input
                  aria-label="Name"
                  className="flex-1"
                  value={name}
                  onChange={(event) => { setName(event.target.value); clearReverted('name'); }}
                  required={!isEdit}
                  placeholder={fieldPlaceholder('name', type === 'categoria' ? 'Category name' : 'Rubro name')}
                />
                {revertControl('name')}
              </div>
              {fieldError('name')}
            </div>
          )}
          <div className="space-y-1">
            <div className="flex items-end gap-2">
              <Input
                aria-label="Description"
                className="flex-1"
                value={description}
                onChange={(event) => { setDescription(event.target.value); clearReverted('description'); }}
                placeholder={fieldPlaceholder('description', 'Description')}
              />
              {revertControl('description')}
            </div>
            {fieldError('description')}
          </div>
          {isService ? (
            <div className="space-y-1">
              <div className="flex items-end gap-2">
                <Input
                  aria-label="Value"
                  className="flex-1"
                  type="number"
                  min="0"
                  value={value}
                  onChange={(event) => { setValue(event.target.value); clearReverted('value'); }}
                  placeholder={fieldPlaceholder('value', 'Value')}
                />
                {revertControl('value')}
              </div>
              {fieldError('value')}
            </div>
          ) : null}
          {destinations.length > 0 ? (
            <div className="space-y-1">
              <span className="text-sm font-medium">Destination category</span>
              <Select value={destination ?? ''} onValueChange={setDestination}>
                <SelectTrigger aria-label="Destination category">
                  <SelectValue placeholder="Keep current category" />
                </SelectTrigger>
                <SelectContent>
                  {destinations.map((candidate) => (
                    <SelectItem key={candidate.id} value={candidate.id}>
                      {catalogNodeLabel(candidate)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {fieldError('parent_fork_id')}
            </div>
          ) : null}
          {isService ? (
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium">Tags</span>
                {revertControl('tags')}
              </div>
              <div className="flex flex-wrap gap-3" role="group" aria-label="Tags">
                {SERVICE_TAGS.map((tag) => (
                  <label key={tag} className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      className="size-4 rounded border-input"
                      checked={tags.includes(tag)}
                      onChange={(event) => toggleTag(tag, event.target.checked)}
                    />
                    {tag}
                  </label>
                ))}
              </div>
              {fieldError('tags')}
            </div>
          ) : null}
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

'use client';

import { useCallback, useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { TableSkeleton } from '@/components/shared/table-skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { CatalogTree } from '@/modules/catalog/components/catalog-tree';
import { CatalogItemDialog, type CatalogCreateIntent } from '@/modules/catalog/components/catalog-item-dialog';
import { ConfirmDialog } from '@/modules/catalog/components/confirm-dialog';
import { AttachDialog } from '@/modules/catalog/components/attach-dialog';
import { useAuth } from '@/hooks/useAuth';
import { createPersonalItem, deletePersonalItem, forkBaseItem, getBaseRubros, getUserCatalogTree, updatePersonalItem, updatePersonalItemStatus, type BaseRubro, type CatalogItemType, type CatalogNode, type CatalogOrigin, type CatalogStatus, type CreatePersonalItemInput } from '@/modules/catalog/api';

interface PendingConfirmation {
  item: CatalogNode;
  action: 'delete' | 'deactivate';
}

interface DialogErrors {
  validationErrors?: Record<string, string[]>;
  formError?: string;
}

const GENERIC_SAVE_ERROR = 'The item could not be saved.';

/**
 * Maps a rejected mutation into dialog-level errors: 422 keeps the field map,
 * 409 keeps the server's conflict message, anything else is generic.
 */
function toDialogErrors(error: unknown): DialogErrors {
  if (error instanceof ApiError) {
    if (error.status === 422 && error.errors) return { validationErrors: error.errors };
    if (error.status === 409) return { formError: error.message };
  }
  return { formError: GENERIC_SAVE_ERROR };
}

/**
 * Flattens a rejected mutation into the single message the attach dialog
 * shows: the server's first field message when present, else the server
 * message, else the generic fallback.
 */
function toAttachError(error: unknown): string {
  const mapped = toDialogErrors(error);
  if (mapped.formError) return mapped.formError;
  const firstFieldMessage = Object.values(mapped.validationErrors ?? {})[0]?.[0];
  return firstFieldMessage ?? GENERIC_SAVE_ERROR;
}

export default function CatalogoPage() {
  const { user } = useAuth();
  // Personalization belongs to regular owners: the backend policy denies the
  // admin role (403), so the page must not offer those actions at all.
  const isAdmin = user?.role === 'admin';

  const [rubros, setRubros] = useState<BaseRubro[]>([]);
  const [tree, setTree] = useState<CatalogNode[]>([]);
  const [status, setStatus] = useState<CatalogStatus>('all');
  const [origin, setOrigin] = useState<CatalogOrigin | 'all'>('all');
  const [loadingBase, setLoadingBase] = useState(true);
  const [loadingTree, setLoadingTree] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dialogItem, setDialogItem] = useState<CatalogNode | null | undefined>(undefined);
  const [createIntent, setCreateIntent] = useState<CatalogCreateIntent | null>(null);
  const [dialogErrors, setDialogErrors] = useState<DialogErrors>({});
  const [confirmation, setConfirmation] = useState<PendingConfirmation | null>(null);
  const [attachOrphan, setAttachOrphan] = useState<CatalogNode | null>(null);
  const [attachError, setAttachError] = useState<string | null>(null);
  const [refresh, setRefresh] = useState(0);

  useEffect(() => { let active = true; getBaseRubros().then((data) => active && setRubros(data)).catch(() => active && setError('The base catalog could not be loaded.')).finally(() => active && setLoadingBase(false)); return () => { active = false; }; }, []);
  useEffect(() => { let active = true; getUserCatalogTree({ status, origin: origin === 'all' ? undefined : origin }).then((data) => active && setTree(data)).catch(() => active && setError('Your catalog could not be loaded.')).finally(() => active && setLoadingTree(false)); return () => { active = false; }; }, [status, origin, refresh]);

  const reload = useCallback(() => { setLoadingTree(true); setRefresh((value) => value + 1); }, []);

  async function fork(id: string) { try { await forkBaseItem('rubro', id); reload(); } catch { setError('The rubro could not be selected.'); } }

  function openCreateRubro() { setDialogErrors({}); setCreateIntent(null); setDialogItem(null); }
  function openEdit(node: CatalogNode) { setDialogErrors({}); setCreateIntent(null); setDialogItem(node); }
  function addChild(parent: CatalogNode, childType: CatalogItemType) { setDialogErrors({}); setDialogItem(null); setCreateIntent({ type: childType, parentForkId: parent.id }); }
  function closeDialog() { setDialogItem(undefined); setCreateIntent(null); setDialogErrors({}); }

  async function save(input: CreatePersonalItemInput) {
    setDialogErrors({});
    try {
      if (dialogItem) await updatePersonalItem(dialogItem.item_type, dialogItem.id, input);
      else await createPersonalItem(createIntent?.type ?? 'rubro', input);
      reload();
    } catch (saveError) {
      // Surface the server's field/form errors in the dialog and keep it open.
      setDialogErrors(toDialogErrors(saveError));
      throw saveError;
    }
  }

  async function applyStatus(node: CatalogNode) {
    try { await updatePersonalItemStatus(node.item_type, node.id, node.status === 'activo' ? 'desactivado' : 'activo'); reload(); } catch { setError('The status could not be changed.'); }
  }

  function openAttach(node: CatalogNode) { setAttachError(null); setAttachOrphan(node); }
  function closeAttach() { setAttachOrphan(null); setAttachError(null); }

  async function attach(parentForkId: string) {
    const orphan = attachOrphan;
    if (!orphan) return;
    try {
      await updatePersonalItem(orphan.item_type, orphan.id, { parent_fork_id: parentForkId });
      closeAttach();
      reload();
    } catch (attachFailure) {
      // Keep the dialog open so the user can pick another parent.
      setAttachError(toAttachError(attachFailure));
    }
  }

  function changeStatus(node: CatalogNode) {
    // Deactivating cascades to descendants, so it always asks for confirmation first.
    if (node.status === 'activo') { setConfirmation({ item: node, action: 'deactivate' }); return; }
    void applyStatus(node);
  }

  async function confirmPending() {
    const pending = confirmation;
    if (!pending) return;
    setConfirmation(null);
    try {
      if (pending.action === 'delete') await deletePersonalItem(pending.item.item_type, pending.item.id);
      else await updatePersonalItemStatus(pending.item.item_type, pending.item.id, 'desactivado');
      reload();
    } catch {
      setError(pending.action === 'delete' ? 'The item could not be deleted.' : 'The status could not be changed.');
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <PageHeader title="Catalog" description="Choose the base services you offer and shape your personal catalog." />
      {error ? <div role="alert" className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}
      <Tabs defaultValue="base">
        <TabsList>
          <TabsTrigger value="base">Base Catalog</TabsTrigger>
          <TabsTrigger value="mine">My Catalog</TabsTrigger>
        </TabsList>
        <TabsContent value="base" className="pt-4">
          <div className="mb-4 flex items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">Select a base rubro to copy its categories and services.</p>
            {isAdmin ? null : <Button onClick={openCreateRubro}>New personal rubro</Button>}
          </div>
          {loadingBase ? <TableSkeleton /> : rubros.length === 0 ? <EmptyState message="No base rubros are available." /> : (
            <div className="grid gap-3 md:grid-cols-2">
              {rubros.map((rubro) => (
                <div key={rubro.id} className="flex items-start justify-between gap-4 rounded-lg border bg-card p-4">
                  <div>
                    <div className="flex items-center gap-2"><h2 className="font-medium">{rubro.name}</h2><Badge variant="outline">Base</Badge></div>
                    <p className="mt-1 text-sm text-muted-foreground">{rubro.description || 'Ready to add to your catalog.'}</p>
                  </div>
                  {isAdmin ? null : <Button size="sm" onClick={() => fork(rubro.id)}>Select</Button>}
                </div>
              ))}
            </div>
          )}
        </TabsContent>
        <TabsContent value="mine" className="space-y-4 pt-4">
          {isAdmin ? (
            <EmptyState message="Personalization is for non-admin users. The base catalog remains available to browse." />
          ) : (
            <>
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h2 className="font-medium">Your catalog tree</h2>
                  <p className="text-sm text-muted-foreground">Effective status and origin reflect inheritance and overrides.</p>
                </div>
                <div className="flex gap-2">
                  <Select value={status} onValueChange={(value) => { setLoadingTree(true); setStatus(value as CatalogStatus); }}>
                    <SelectTrigger aria-label="Filter by status" className="w-36"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All statuses</SelectItem>
                      <SelectItem value="activo">Active</SelectItem>
                      <SelectItem value="desactivado">Inactive</SelectItem>
                    </SelectContent>
                  </Select>
                  <Select value={origin} onValueChange={(value) => { setLoadingTree(true); setOrigin(value as CatalogOrigin | 'all'); }}>
                    <SelectTrigger aria-label="Filter by origin" className="w-36"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All origins</SelectItem>
                      <SelectItem value="base">Base</SelectItem>
                      <SelectItem value="override">Override</SelectItem>
                      <SelectItem value="personal">Personal</SelectItem>
                    </SelectContent>
                  </Select>
                  <Button onClick={openCreateRubro}>Add rubro</Button>
                </div>
              </div>
              {loadingTree ? <TableSkeleton rows={5} /> : tree.length === 0 ? <EmptyState message="Your catalog is empty for these filters." /> : (
                <CatalogTree nodes={tree} onEdit={openEdit} onStatus={changeStatus} onDelete={(node) => setConfirmation({ item: node, action: 'delete' })} onAddChild={addChild} onAttach={openAttach} />
              )}
            </>
          )}
        </TabsContent>
      </Tabs>
      <CatalogItemDialog
        key={`${dialogItem?.id ?? 'new'}|${createIntent?.type ?? ''}|${createIntent?.parentForkId ?? ''}`}
        open={dialogItem !== undefined || createIntent !== null}
        item={dialogItem}
        createIntent={createIntent}
        tree={tree}
        onOpenChange={(open) => !open && closeDialog()}
        onSave={save}
        validationErrors={dialogErrors.validationErrors}
        formError={dialogErrors.formError}
      />
      {confirmation ? (
        <ConfirmDialog
          open
          item={confirmation.item}
          action={confirmation.action}
          onConfirm={confirmPending}
          onCancel={() => setConfirmation(null)}
        />
      ) : null}
      {attachOrphan ? (
        <AttachDialog
          open
          orphan={attachOrphan}
          tree={tree}
          onAttach={attach}
          onCancel={closeAttach}
          error={attachError ?? undefined}
        />
      ) : null}
    </div>
  );
}

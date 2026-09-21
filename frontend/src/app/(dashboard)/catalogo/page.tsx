'use client';

import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { TableSkeleton } from '@/components/shared/table-skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { CatalogTree } from '@/modules/catalog/components/catalog-tree';
import { CatalogItemDialog } from '@/modules/catalog/components/catalog-item-dialog';
import { createPersonalItem, deletePersonalItem, forkBaseItem, getBaseRubros, getUserCatalogTree, updatePersonalItem, updatePersonalItemStatus, type BaseRubro, type CatalogNode, type CatalogOrigin, type CatalogStatus } from '@/modules/catalog/api';

export default function CatalogoPage() {
  const [rubros, setRubros] = useState<BaseRubro[]>([]);
  const [tree, setTree] = useState<CatalogNode[]>([]);
  const [status, setStatus] = useState<CatalogStatus>('all');
  const [origin, setOrigin] = useState<CatalogOrigin | 'all'>('all');
  const [loadingBase, setLoadingBase] = useState(true);
  const [loadingTree, setLoadingTree] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dialogItem, setDialogItem] = useState<CatalogNode | null | undefined>(undefined);
  const [refresh, setRefresh] = useState(0);

  useEffect(() => { let active = true; getBaseRubros().then((data) => active && setRubros(data)).catch(() => active && setError('The base catalog could not be loaded.')).finally(() => active && setLoadingBase(false)); return () => { active = false; }; }, []);
  useEffect(() => { let active = true; getUserCatalogTree({ status, origin: origin === 'all' ? undefined : origin }).then((data) => active && setTree(data)).catch(() => active && setError('Your catalog could not be loaded.')).finally(() => active && setLoadingTree(false)); return () => { active = false; }; }, [status, origin, refresh]);

  const reload = useCallback(() => { setLoadingTree(true); setRefresh((value) => value + 1); }, []);
  async function fork(id: string) { try { await forkBaseItem('rubro', id); reload(); } catch { setError('The rubro could not be selected.'); } }
  async function save(input: Parameters<typeof createPersonalItem>[1]) { try { if (dialogItem) await updatePersonalItem(dialogItem.item_type, dialogItem.id, input); else await createPersonalItem('rubro', input); reload(); } catch { setError('The item could not be saved.'); throw new Error('save failed'); } }
  async function changeStatus(node: CatalogNode) { try { await updatePersonalItemStatus(node.item_type, node.id, node.status === 'activo' ? 'desactivado' : 'activo'); reload(); } catch { setError('The status could not be changed.'); } }
  async function remove(node: CatalogNode) { if (!window.confirm(`Delete ${node.name ?? node.title ?? 'this item'} and its descendants?`)) return; try { await deletePersonalItem(node.item_type, node.id); reload(); } catch { setError('The item could not be deleted.'); } }

  return <div className="flex flex-col gap-6"><PageHeader title="Catalog" description="Choose the base services you offer and shape your personal catalog." />{error ? <div role="alert" className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}<Tabs defaultValue="base"><TabsList><TabsTrigger value="base">Base Catalog</TabsTrigger><TabsTrigger value="mine">My Catalog</TabsTrigger></TabsList><TabsContent value="base" className="pt-4"><div className="mb-4 flex items-center justify-between gap-3"><p className="text-sm text-muted-foreground">Select a base rubro to copy its categories and services.</p><Button onClick={() => setDialogItem(null)}>New personal rubro</Button></div>{loadingBase ? <TableSkeleton /> : rubros.length === 0 ? <EmptyState message="No base rubros are available." /> : <div className="grid gap-3 md:grid-cols-2">{rubros.map((rubro) => <div key={rubro.id} className="flex items-start justify-between gap-4 rounded-lg border bg-card p-4"><div><div className="flex items-center gap-2"><h2 className="font-medium">{rubro.name}</h2><Badge variant="outline">Base</Badge></div><p className="mt-1 text-sm text-muted-foreground">{rubro.description || 'Ready to add to your catalog.'}</p></div><Button size="sm" onClick={() => fork(rubro.id)}>Select</Button></div>)}</div>}</TabsContent><TabsContent value="mine" className="space-y-4 pt-4"><div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="font-medium">Your catalog tree</h2><p className="text-sm text-muted-foreground">Effective status and origin reflect inheritance and overrides.</p></div><div className="flex gap-2"><Select value={status} onValueChange={(value) => { setLoadingTree(true); setStatus(value as CatalogStatus); }}><SelectTrigger aria-label="Filter by status" className="w-36"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All statuses</SelectItem><SelectItem value="activo">Active</SelectItem><SelectItem value="desactivado">Inactive</SelectItem></SelectContent></Select><Select value={origin} onValueChange={(value) => { setLoadingTree(true); setOrigin(value as CatalogOrigin | 'all'); }}><SelectTrigger aria-label="Filter by origin" className="w-36"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All origins</SelectItem><SelectItem value="base">Base</SelectItem><SelectItem value="override">Override</SelectItem><SelectItem value="personal">Personal</SelectItem></SelectContent></Select><Button onClick={() => setDialogItem(null)}>Add rubro</Button></div></div>{loadingTree ? <TableSkeleton rows={5} /> : tree.length === 0 ? <EmptyState message="Your catalog is empty for these filters." /> : <CatalogTree nodes={tree} onEdit={setDialogItem} onStatus={changeStatus} onDelete={remove} />}</TabsContent></Tabs><CatalogItemDialog key={dialogItem?.id ?? 'new'} open={dialogItem !== undefined} item={dialogItem} onOpenChange={(open) => !open && setDialogItem(undefined)} onSave={save} /></div>;
}

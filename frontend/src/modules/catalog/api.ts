import { apiFetch } from '@/lib/api';

export type CatalogItemType = 'rubro' | 'categoria' | 'service';
export type CatalogStatus = 'activo' | 'desactivado' | 'all';
export type CatalogOrigin = 'personal' | 'override' | 'base';

export interface BaseRubro {
  id: string;
  name: string;
  description: string | null;
  status: Exclude<CatalogStatus, 'all'>;
}

export interface CatalogNode {
  id: string;
  base_id: string | null;
  item_type: CatalogItemType;
  parent_fork_id: string | null;
  sort_order: number;
  name?: string | null;
  title?: string | null;
  description?: string | null;
  value?: number | null;
  tags?: string[] | null;
  status: Exclude<CatalogStatus, 'all'>;
  origin: CatalogOrigin;
  overridden_fields: string[];
  children?: CatalogNode[];
}

export interface CatalogFilters {
  status?: CatalogStatus;
  origin?: CatalogOrigin;
}

function queryString(filters: CatalogFilters): string {
  const query = new URLSearchParams();
  if (filters.status && filters.status !== 'all') query.set('status', filters.status);
  if (filters.origin) query.set('origin', filters.origin);
  const value = query.toString();
  return value ? `?${value}` : '';
}

export function getBaseRubros(status: CatalogStatus = 'activo'): Promise<BaseRubro[]> {
  return apiFetch<BaseRubro[]>(`/api/rubros?status=${status}`);
}

export function getUserCatalogTree(filters: CatalogFilters = {}): Promise<CatalogNode[]> {
  return apiFetch<CatalogNode[]>(`/api/user-catalog/tree${queryString(filters)}`);
}

export interface CreatePersonalItemInput {
  name?: string | null;
  title?: string | null;
  description?: string | null;
  value?: number | null;
  tags?: string[] | null;
  parent_fork_id?: string | null;
}

/** Laravel 422 validation failure shape (field-level messages). */
export interface LaravelValidationError {
  message: string;
  errors: Record<string, string[]>;
}

/** Laravel 409 conflict shape (form-level message). */
export interface LaravelConflictError {
  message: string;
}

export function forkBaseItem(type: CatalogItemType, baseId: string): Promise<unknown> {
  const route = type === 'rubro' ? 'rubros' : type === 'categoria' ? 'categorias' : 'services';
  return apiFetch(`/api/user-catalog/${route}/${baseId}/fork`, { method: 'POST' });
}

export function createPersonalItem(type: CatalogItemType, input: CreatePersonalItemInput): Promise<CatalogNode> {
  const route = type === 'rubro' ? 'rubros' : type === 'categoria' ? 'categorias' : 'services';
  return apiFetch<CatalogNode>(`/api/user-catalog/${route}`, {
    method: 'POST',
    body: JSON.stringify({ item_type: type, ...input }),
  });
}

export function updatePersonalItem(type: CatalogItemType, id: string, input: CreatePersonalItemInput): Promise<CatalogNode> {
  const route = type === 'rubro' ? 'rubros' : type === 'categoria' ? 'categorias' : 'services';
  return apiFetch<CatalogNode>(`/api/user-catalog/${route}/${id}`, {
    method: 'PUT',
    body: JSON.stringify(input),
  });
}

export function updatePersonalItemStatus(type: CatalogItemType, id: string, status: 'activo' | 'desactivado'): Promise<CatalogNode> {
  const route = type === 'rubro' ? 'rubros' : type === 'categoria' ? 'categorias' : 'services';
  return apiFetch<CatalogNode>(`/api/user-catalog/${route}/${id}/${status === 'activo' ? 'reactivate' : 'deactivate'}`, { method: 'PATCH' });
}

export function deletePersonalItem(type: CatalogItemType, id: string): Promise<void> {
  const route = type === 'rubro' ? 'rubros' : type === 'categoria' ? 'categorias' : 'services';
  return apiFetch<void>(`/api/user-catalog/${route}/${id}`, { method: 'DELETE' });
}

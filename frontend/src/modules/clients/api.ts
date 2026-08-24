import { apiFetch } from '@/lib/api';
import type { Client } from './types';

export type ClientStatus = 'activo' | 'desactivado' | 'all';

export interface GetClientsParams {
  status?: ClientStatus;
  search?: string;
}

/**
 * Obtiene el listado de clientes filtrado por estado y búsqueda.
 */
export function getClients(params: GetClientsParams = {}): Promise<Client[]> {
  const query = new URLSearchParams();

  if (params.status) {
    query.set('status', params.status);
  }

  if (params.search) {
    query.set('search', params.search);
  }

  const qs = query.toString();

  return apiFetch<Client[]>(`/api/clients${qs ? `?${qs}` : ''}`);
}

export interface CreateClientInput {
  name: string;
  surname?: string | null;
  rut: string;
  email: string;
  phone: string;
  address?: string | null;
  notes?: string | null;
  website?: string | null;
}

/**
 * Crea un cliente nuevo.
 */
export function createClient(input: CreateClientInput): Promise<Client> {
  return apiFetch<Client>('/api/clients', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

/**
 * Actualiza un cliente existente.
 */
export function updateClient(id: string, input: CreateClientInput): Promise<Client> {
  return apiFetch<Client>(`/api/clients/${id}`, {
    method: 'PUT',
    body: JSON.stringify(input),
  });
}

/**
 * Cambia el estado de un cliente (activar / desactivar).
 */
export function updateClientStatus(id: string, status: 'activo' | 'desactivado'): Promise<Client> {
  return apiFetch<Client>(`/api/clients/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  });
}

/**
 * Elimina definitivamente un cliente.
 */
export function deleteClient(id: string): Promise<void> {
  return apiFetch<void>(`/api/clients/${id}`, { method: 'DELETE' });
}

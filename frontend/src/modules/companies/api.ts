import { apiFetch } from '@/lib/api';
import type { Company } from './types';

export interface GetCompaniesParams {
  search?: string;
}

/**
 * Obtiene el listado de empresas filtrado por búsqueda.
 */
export function getCompanies(params: GetCompaniesParams = {}): Promise<Company[]> {
  const query = new URLSearchParams();

  if (params.search) {
    query.set('search', params.search);
  }

  const qs = query.toString();

  return apiFetch<Company[]>(`/api/companies${qs ? `?${qs}` : ''}`);
}

export interface CreateCompanyInput {
  name: string;
  rut: string;
  email: string;
  phone: string;
  address?: string | null;
  website?: string | null;
}

/**
 * Crea una empresa nueva.
 */
export function createCompany(input: CreateCompanyInput): Promise<Company> {
  return apiFetch<Company>('/api/companies', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

/**
 * Actualiza una empresa existente.
 */
export function updateCompany(id: string, input: CreateCompanyInput): Promise<Company> {
  return apiFetch<Company>(`/api/companies/${id}`, {
    method: 'PUT',
    body: JSON.stringify(input),
  });
}

/**
 * Elimina definitivamente una empresa.
 */
export function deleteCompany(id: string): Promise<void> {
  return apiFetch<void>(`/api/companies/${id}`, { method: 'DELETE' });
}

/**
 * Obtiene una empresa con sus clientes asociados.
 */
export function getCompany(
  id: string
): Promise<Company & { clients: { id: string; name: string; surname: string | null }[] }> {
  return apiFetch(`/api/companies/${id}`);
}

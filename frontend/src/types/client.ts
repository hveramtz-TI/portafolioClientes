export interface Client {
  id: string;
  name: string;
  surname: string | null;
  rut: string;
  email: string;
  phone: string;
  address: string | null;
  company_id: string | null;
  notes: string | null;
  website: string | null;
  status: 'activo' | 'desactivado';
  created_at: string;
  updated_at: string;
}
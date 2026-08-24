export interface Company {
  id: string;
  name: string;
  rut: string;
  email: string;
  phone: string;
  address: string | null;
  website: string | null;
  clients_count: number;
  created_at: string;
  updated_at: string;
}

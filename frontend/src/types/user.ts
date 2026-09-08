export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  role: 'admin' | 'user';
  created_at: string;
  updated_at: string;
}
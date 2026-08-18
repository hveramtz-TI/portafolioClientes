const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8010';

/**
 * Obtiene el token CSRF de la cookie XSRF-TOKEN
 */
function getCsrfToken(): string | null {
  if (typeof document === 'undefined') return null;
  
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  if (!match) return null;
  
  return decodeURIComponent(match[1]);
}

/**
 * Realiza una petición fetch con manejo automático de CSRF
 */
export async function apiFetch<T = unknown>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_URL}${endpoint}`;
  
  const headers: HeadersInit = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...options.headers,
  };

  // Agregar token CSRF si existe
  const csrfToken = getCsrfToken();
  if (csrfToken) {
    (headers as Record<string, string>)['X-XSRF-TOKEN'] = csrfToken;
  }

  const response = await fetch(url, {
    ...options,
    headers,
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Error en la petición' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  // Manejar respuestas vacías (204 No Content)
  if (response.status === 204) {
    return {} as T;
  }

  return response.json();
}

/**
 * Obtiene el cookie CSRF de Laravel Sanctum
 * Debe llamarse antes de cualquier petición POST/PUT/DELETE
 */
export async function ensureCsrfCookie(): Promise<void> {
  await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
  });
}

/**
 * Cliente API con métodos específicos
 */
export const api = {
  /**
   * Login de usuario
   */
  async login(email: string, password: string) {
    await ensureCsrfCookie();
    return apiFetch<{ user: User }>('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  },

  /**
   * Logout de usuario
   */
  async logout() {
    return apiFetch('/api/logout', {
      method: 'POST',
    });
  },

  /**
   * Obtener usuario autenticado
   */
  async getUser() {
    return apiFetch<User>('/api/user');
  },

  /**
   * Obtener lista de usuarios (solo admin)
   */
  async getUsers() {
    return apiFetch<User[]>('/api/users');
  },
};

/**
 * Tipo User
 */
export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  role: 'admin' | 'user';
  created_at: string;
  updated_at: string;
}

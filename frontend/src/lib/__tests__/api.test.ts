import { api, apiFetch, ensureCsrfCookie } from '../api';

// Mock de fetch global
global.fetch = jest.fn();

describe('api client', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Limpiar cookies
    document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
  });

  describe('ensureCsrfCookie', () => {
    it('debe llamar a /sanctum/csrf-cookie con credentials', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({ ok: true });

      await ensureCsrfCookie();

      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8010/sanctum/csrf-cookie',
        { credentials: 'include' }
      );
    });
  });

  describe('apiFetch', () => {
    it('debe incluir credentials: include en todas las peticiones', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ data: 'test' }),
      });

      await apiFetch('/api/test');

      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8010/api/test',
        expect.objectContaining({
          credentials: 'include',
        })
      );
    });

    it('debe agregar X-XSRF-TOKEN si existe la cookie', async () => {
      // Setear cookie CSRF
      document.cookie = 'XSRF-TOKEN=test-token; path=/';

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ data: 'test' }),
      });

      await apiFetch('/api/test');

      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8010/api/test',
        expect.objectContaining({
          headers: expect.objectContaining({
            'X-XSRF-TOKEN': 'test-token',
          }),
        })
      );
    });

    it('debe lanzar error si la respuesta no es ok', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: () => Promise.resolve({ message: 'Unauthorized' }),
      });

      await expect(apiFetch('/api/test')).rejects.toThrow('Unauthorized');
    });

    it('debe manejar respuestas 204 No Content', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 204,
      });

      const result = await apiFetch('/api/test');
      expect(result).toEqual({});
    });
  });

  describe('api.login', () => {
    it('debe obtener CSRF cookie antes de hacer login', async () => {
      // Mock para csrf-cookie
      (global.fetch as jest.Mock).mockResolvedValueOnce({ ok: true });
      // Mock para login
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ user: { id: '1', name: 'Test' } }),
      });

      await api.login('test@example.com', 'password');

      expect(global.fetch).toHaveBeenCalledTimes(2);
      expect(global.fetch).toHaveBeenNthCalledWith(
        1,
        'http://localhost:8010/sanctum/csrf-cookie',
        expect.any(Object)
      );
    });
  });

  describe('api.logout', () => {
    it('debe hacer POST a /api/logout', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 204,
      });

      await api.logout();

      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8010/api/logout',
        expect.objectContaining({
          method: 'POST',
          credentials: 'include',
        })
      );
    });
  });

  describe('api.getUser', () => {
    it('debe hacer GET a /api/user', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ id: '1', name: 'Test User' }),
      });

      const user = await api.getUser();

      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8010/api/user',
        expect.objectContaining({
          credentials: 'include',
        })
      );
      expect(user).toEqual({ id: '1', name: 'Test User' });
    });
  });
});

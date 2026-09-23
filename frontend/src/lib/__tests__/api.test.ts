import { api, apiFetch, ensureCsrfCookie, ApiError } from '../api';

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

  describe('apiFetch error contract', () => {
    it('exposes status 422 and the Laravel errors map', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () =>
          Promise.resolve({
            message: 'The given data was invalid.',
            errors: { name: ['The name has already been taken.'] },
          }),
      });

      const error: unknown = await apiFetch('/api/test').catch((err) => err);

      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(422);
      expect((error as ApiError).errors).toEqual({
        name: ['The name has already been taken.'],
      });
      expect((error as ApiError).message).toBe('The given data was invalid.');
    });

    it('exposes status 409 and the server message', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 409,
        json: () => Promise.resolve({ message: 'Duplicate fork identity.' }),
      });

      const error: unknown = await apiFetch('/api/test').catch((err) => err);

      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(409);
      expect((error as ApiError).message).toBe('Duplicate fork identity.');
    });

    it('exposes status 500 and the server message for non-validation errors', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ message: 'Server exploded' }),
      });

      const error: unknown = await apiFetch('/api/test').catch((err) => err);

      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(500);
      expect((error as ApiError).message).toBe('Server exploded');
    });

    it('keeps the existing generic message when the error body is not JSON', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 502,
        json: () => Promise.reject(new Error('invalid json')),
      });

      const error: unknown = await apiFetch('/api/test').catch((err) => err);

      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(502);
      expect((error as ApiError).message).toBe('Error en la petición');
    });

    it('falls back to the HTTP status when the JSON body has no message', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: () => Promise.resolve({}),
      });

      const error: unknown = await apiFetch('/api/test').catch((err) => err);

      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(404);
      expect((error as ApiError).message).toBe('HTTP 404');
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

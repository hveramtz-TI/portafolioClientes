import { proxy } from '../proxy';
import { NextRequest, NextResponse } from 'next/server';

// Mock de NextResponse
jest.mock('next/server', () => ({
  NextResponse: {
    redirect: jest.fn(),
    next: jest.fn(),
  },
}));

describe('proxy', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const createMockRequest = (pathname: string, cookies: Record<string, string> = {}) => {
    return {
      nextUrl: {
        pathname,
      },
      url: 'http://localhost:3010',
      cookies: {
        get: (name: string) => cookies[name] || null,
      },
    } as unknown as NextRequest;
  };

  describe('rutas protegidas', () => {
    it('debe redirigir a /login si no hay sesión en /dashboard', () => {
      const request = createMockRequest('/dashboard', {});
      
      proxy(request);

      expect(NextResponse.redirect).toHaveBeenCalledWith(
        expect.objectContaining({
          pathname: '/login',
          searchParams: expect.any(URLSearchParams),
        })
      );
    });

    it('debe permitir acceso a /dashboard si hay sesión', () => {
      const request = createMockRequest('/dashboard', {
        'portafolioclientes-session': 'session-token',
      });

      proxy(request);

      expect(NextResponse.next).toHaveBeenCalled();
      expect(NextResponse.redirect).not.toHaveBeenCalled();
    });

    it('debe redirigir rutas anidadas de /dashboard', () => {
      const request = createMockRequest('/dashboard/settings', {});

      proxy(request);

      expect(NextResponse.redirect).toHaveBeenCalled();
    });
  });

  describe('página de login', () => {
    it('debe redirigir a /dashboard si ya hay sesión en /login', () => {
      const request = createMockRequest('/login', {
        'portafolioclientes-session': 'session-token',
      });

      proxy(request);

      expect(NextResponse.redirect).toHaveBeenCalledWith(
        expect.objectContaining({
          pathname: '/dashboard',
        })
      );
    });

    it('debe permitir acceso a /login si no hay sesión', () => {
      const request = createMockRequest('/login', {});

      proxy(request);

      expect(NextResponse.next).toHaveBeenCalled();
    });
  });

  describe('rutas públicas', () => {
    it('debe permitir acceso a rutas públicas sin sesión', () => {
      const request = createMockRequest('/', {});

      proxy(request);

      expect(NextResponse.next).toHaveBeenCalled();
    });
  });
});

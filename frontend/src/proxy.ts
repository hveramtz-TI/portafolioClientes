import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // La única ruta pública es /login. Todo lo demás requiere sesión,
  // excepto la landing del producto en /, que es pública solo sin sesión.
  if (pathname === '/login' || pathname.startsWith('/login/')) {
    return NextResponse.next();
  }

  const sessionCookie = request.cookies.get('portafolioclientes-session');

  // Raíz: sin sesión → landing pública; con sesión → dashboard.
  if (pathname === '/') {
    if (sessionCookie) {
      return NextResponse.redirect(new URL('/dashboard', request.url));
    }
    return NextResponse.next();
  }

  // Protección por defecto: sin cookie de sesión, a login.
  if (!sessionCookie) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - api (API routes)
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!api|_next/static|_next/image|favicon.ico).*)',
  ],
};

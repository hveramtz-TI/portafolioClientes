import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AuthProvider, useAuth } from '../AuthContext';
import { api } from '../../lib/api';

// Mock del módulo api
jest.mock('../../lib/api', () => ({
  api: {
    login: jest.fn(),
    logout: jest.fn(),
    getUser: jest.fn(),
  },
}));

const mockedApi = api as jest.Mocked<typeof api>;

// Componente de prueba que usa el hook
function TestComponent() {
  const { user, loading, error, login, logout } = useAuth();

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!user) {
    return (
      <div>
        <div>Not authenticated</div>
        <button onClick={() => login('test@example.com', 'password')}>Login</button>
      </div>
    );
  }

  return (
    <div>
      <div>Welcome, {user.name}</div>
      <button onClick={logout}>Logout</button>
    </div>
  );
}

describe('AuthContext', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('debe mostrar loading inicialmente', () => {
    mockedApi.getUser.mockImplementation(() => new Promise(() => {})); // Nunca resuelve

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('debe mostrar "Not authenticated" si no hay sesión', async () => {
    mockedApi.getUser.mockRejectedValueOnce(new Error('401'));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Not authenticated')).toBeInTheDocument();
    });
  });

  it('debe mostrar el usuario si hay sesión', async () => {
    const mockUser = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null,
      role: 'user' as const,
      created_at: '2024-01-01',
      updated_at: '2024-01-01',
    };

    mockedApi.getUser.mockResolvedValueOnce(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Welcome, Test User')).toBeInTheDocument();
    });
  });

  it('debe hacer login correctamente', async () => {
    const user = userEvent.setup();
    const mockUser = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null,
      role: 'user' as const,
      created_at: '2024-01-01',
      updated_at: '2024-01-01',
    };

    mockedApi.getUser.mockRejectedValueOnce(new Error('401'));
    mockedApi.login.mockResolvedValueOnce({ user: mockUser });

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Not authenticated')).toBeInTheDocument();
    });

    await user.click(screen.getByText('Login'));

    await waitFor(() => {
      expect(screen.getByText('Welcome, Test User')).toBeInTheDocument();
    });

    expect(mockedApi.login).toHaveBeenCalledWith('test@example.com', 'password');
  });

  it('debe hacer logout correctamente', async () => {
    const user = userEvent.setup();
    const mockUser = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null,
      role: 'user' as const,
      created_at: '2024-01-01',
      updated_at: '2024-01-01',
    };

    mockedApi.getUser.mockResolvedValueOnce(mockUser);
    mockedApi.logout.mockResolvedValueOnce({});

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Welcome, Test User')).toBeInTheDocument();
    });

    await user.click(screen.getByText('Logout'));

    await waitFor(() => {
      expect(screen.getByText('Not authenticated')).toBeInTheDocument();
    });

    expect(mockedApi.logout).toHaveBeenCalled();
  });

  it('debe lanzar error si useAuth se usa fuera de AuthProvider', () => {
    // Suprimir errores de consola para esta prueba
    const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    function BadComponent() {
      useAuth();
      return null;
    }

    expect(() => render(<BadComponent />)).toThrow(
      'useAuth debe usarse dentro de un AuthProvider'
    );

    consoleSpy.mockRestore();
  });
});

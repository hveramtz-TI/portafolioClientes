import { render, screen } from '@testing-library/react'
import Page from '../src/app/page'

// Mock de useAuth
jest.mock('../src/hooks/useAuth', () => ({
  useAuth: jest.fn(() => ({
    user: null,
    loading: false,
  })),
}))

// Mock de useRouter
const mockReplace = jest.fn()
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    replace: mockReplace,
  }),
}))

describe('Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders loading state initially', () => {
    render(<Page />)
    expect(screen.getByText('Cargando...')).toBeInTheDocument()
  })

  it('redirects to /login when not authenticated', () => {
    render(<Page />)
    expect(mockReplace).toHaveBeenCalledWith('/login')
  })

  it('redirects to /dashboard when authenticated', () => {
    const { useAuth } = require('../src/hooks/useAuth')
    useAuth.mockReturnValue({
      user: { id: '1', name: 'Test', email: 'test@example.com', role: 'user' },
      loading: false,
    })

    render(<Page />)
    expect(mockReplace).toHaveBeenCalledWith('/dashboard')
  })
})

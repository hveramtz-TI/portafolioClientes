import { render } from '@testing-library/react'
import Page from '../src/app/page'

// Mock de useAuth
jest.mock('../src/hooks/useAuth', () => ({
  useAuth: jest.fn(() => ({
    user: null,
    loading: false,
  })),
}))

// Mock de useRouter
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    replace: jest.fn(),
  }),
}))

it('renders homepage unchanged', () => {
  const { container } = render(<Page />)
  expect(container).toMatchSnapshot()
})

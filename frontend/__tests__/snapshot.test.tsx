import { render } from '@testing-library/react'
import LandingPage from '../src/app/(public)/page'

// Mock next/link so the RSC landing renders as plain anchors in jsdom.
jest.mock('next/link', () => {
  const MockLink = ({
    children,
    href,
    ...props
  }: {
    children: React.ReactNode
    href: string
    [key: string]: unknown
  }) => (
    <a href={href} {...props}>
      {children}
    </a>
  )
  MockLink.displayName = 'MockLink'
  return MockLink
})

it('renders the public landing page unchanged', async () => {
  const { container } = render(<LandingPage />)
  expect(container).toMatchSnapshot()
})

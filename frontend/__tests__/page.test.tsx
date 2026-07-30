import { render, screen } from '@testing-library/react'
import Page from '../src/app/page'

describe('Page', () => {
  it('renders a heading', () => {
    render(<Page />)

    const heading = screen.getByRole('heading', { level: 1 })

    expect(heading).toBeInTheDocument()
  })

  it('renders the Next.js logo', () => {
    render(<Page />)

    const logo = screen.getByAltText('Next.js logo')

    expect(logo).toBeInTheDocument()
  })

  it('renders documentation link', () => {
    render(<Page />)

    const docLink = screen.getByText('Documentation')

    expect(docLink).toBeInTheDocument()
    expect(docLink).toHaveAttribute('href', expect.stringContaining('nextjs.org/docs'))
  })
})

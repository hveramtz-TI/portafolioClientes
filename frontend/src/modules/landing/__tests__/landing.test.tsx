import { render, screen } from '@testing-library/react';
import LandingPage from '@/app/(public)/page';
import { metadata } from '@/app/(public)/page';
import PublicLayout from '@/app/(public)/layout';

jest.mock('next/font/google', () => ({
  Inter: () => ({ variable: 'mock-inter-variable' }),
}));

jest.mock('next/link', () => {
  const MockLink = ({
    children,
    href,
    ...props
  }: {
    children: React.ReactNode;
    href: string;
    [key: string]: unknown;
  }) => (
    <a href={href} {...props}>
      {children}
    </a>
  );
  MockLink.displayName = 'MockLink';
  return MockLink;
});

describe('LandingPage', () => {
  it('renders all six sections in order', async () => {
    const { container } = render(<LandingPage />);

    const main = container.querySelector('main');
    expect(main).not.toBeNull();

    const nav = screen.getByRole('navigation', { name: /navegación principal/i });
    expect(nav).toBeInTheDocument();

    // Hero h1
    expect(
      screen.getByRole('heading', { level: 1, name: /tu portafolio de clientes/i })
    ).toBeInTheDocument();

    // Value props heading
    expect(
      screen.getByRole('heading', { level: 2, name: /qué hace la plataforma/i })
    ).toBeInTheDocument();

    // Editorial heading
    expect(
      screen.getByRole('heading', { level: 2, name: /llevan adelante cada relación/i })
    ).toBeInTheDocument();

    // CTA band heading
    expect(
      screen.getByRole('heading', { level: 2, name: /ordenar tu cartera/i })
    ).toBeInTheDocument();

    // Footer wordmark + "That's My Client" (visible in nav sr-only + footer)
    expect(screen.getAllByText("That's My Client").length).toBeGreaterThanOrEqual(1);
  });

  it('exports static TMC metadata (D11)', () => {
    expect(metadata.title).toContain('TMC');
    expect(metadata.description).toContain('That’s My Client');
  });

  it('does not import api/fetch in the landing page', () => {
    const source = LandingPage.toString();
    expect(source).not.toContain('fetch(');
    expect(source).not.toContain('useAuth');
  });
});

describe('placeholder contract (D9)', () => {
  it('renders hero-media and portrait-1 slots with accessible labels', async () => {
    render(<LandingPage />);

    const heroMedia = screen.getByRole('img', { name: /hero-media/i });
    expect(heroMedia).toBeInTheDocument();

    const portrait = screen.getByRole('img', { name: /portrait-1/i });
    expect(portrait).toBeInTheDocument();
  });

  it('marks ghost watermark and orbits as aria-hidden (a11y)', async () => {
    const { container } = render(<LandingPage />);

    const sm = screen.getByRole('main') ?? container.querySelector('main');
    expect(sm).not.toBeNull();

    // Orbits SVG is decorative
    const svg = container.querySelector('svg[aria-hidden="true"]');
    expect(svg).toBeInTheDocument();
  });
});

describe('trademark guardrail (D6)', () => {
  it('does not contain Mastercard branding in page source', () => {
    const source = LandingPage.toString();
    expect(source).not.toContain('Mastercard');
    expect(source).not.toContain('#EB001B');
    expect(source).not.toContain('#F79E1B');
  });
});

describe('copy gate', () => {
  it('does not claim catalog/requests/orders as available features', () => {
    const { container } = render(<LandingPage />);
    const text = container.textContent ?? '';
    expect(text).not.toMatch(/disponible/i);
  });
});

describe('D5 Inter font wiring', () => {
  it('applies the Inter variable to the landing root wrapper', () => {
    const { container } = render(
      <PublicLayout>
        <span>child</span>
      </PublicLayout>
    );
    const wrapper = container.querySelector('.tmc-landing');
    expect(wrapper).toBeInTheDocument();
    expect(wrapper).toHaveStyle({ fontFamily: 'var(--font-inter)' });
  });
});

import type { Metadata } from 'next';
import { LandingNav } from '@/modules/landing/components/nav';
import { LandingHero } from '@/modules/landing/components/hero';
import { LandingValueProps } from '@/modules/landing/components/value-props';
import { LandingEditorial } from '@/modules/landing/components/editorial';
import { LandingCtaBand } from '@/modules/landing/components/cta-band';
import { LandingFooter } from '@/modules/landing/components/footer';

export const metadata: Metadata = {
  title: 'TMC — That’s My Client',
  description:
    'TMC (That’s My Client) organiza tu portafolio de clientes: empresas, catálogo, solicitudes y órdenes, en un solo lugar.',
};

export default function LandingPage() {
  return (
    <>
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus-visible:fixed focus-visible:left-4 focus-visible:top-4 focus-visible:z-[60] focus-visible:rounded-md focus-visible:bg-tmc-ink focus-visible:px-4 focus-visible:py-3 focus-visible:text-tmc-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent-light"
      >
        Saltar al contenido principal
      </a>
      <main id="main-content" className="bg-tmc-canvas text-tmc-ink">
        <LandingNav />
        <LandingHero />
        <LandingValueProps />
        <LandingEditorial />
        <LandingCtaBand />
        <LandingFooter />
      </main>
    </>
  );
}

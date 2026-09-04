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
    <main className="bg-tmc-canvas text-tmc-ink">
      <LandingNav />
      <LandingHero />
      <LandingValueProps />
      <LandingEditorial />
      <LandingCtaBand />
      <LandingFooter />
    </main>
  );
}

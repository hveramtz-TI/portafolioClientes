import Link from 'next/link';
import { PlaceholderSlot } from './placeholder';

export function LandingHero() {
  return (
    <section className="mx-auto max-w-[1200px] px-4 pt-16 md:pt-24">
      <div className="max-w-2xl">
        <p className="mb-6 text-sm font-bold uppercase tracking-normal text-tmc-accent">
          <span aria-hidden="true" className="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-tmc-accent" />
          Plataforma de gestión
        </p>

        <h1
          className="mb-6 text-tmc-hero font-medium leading-[64px] text-tmc-ink"
          style={{ fontSize: 'var(--text-tmc-hero)', lineHeight: 'var(--text-tmc-hero--line-height)', fontWeight: 500 }}
        >
          Tu portafolio de clientes, en un solo lugar.
        </h1>

        <p className="mb-8 text-lg leading-relaxed text-tmc-neutral-5" style={{ fontWeight: 450 }}>
          TMC (That&apos;s My Client) organiza tu relación con cada cliente y
          empresa, su catálogo y sus solicitudes, para que tengas todo claro
          antes de tu próxima reunión.
        </p>

        <Link
          href="/login"
          className="inline-flex items-center justify-center rounded-[var(--radius-tmc-cta)] bg-tmc-ink px-8 py-4 text-xl font-normal text-tmc-canvas transition-colors hover:bg-tmc-neutral-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent"
        >
          Ingresar
        </Link>
      </div>

      <div className="mt-12">
        <PlaceholderSlot
          label="hero-media"
          aspect="16 / 9"
          className="rounded-[var(--radius-tmc-frame)]"
        />
      </div>
    </section>
  );
}

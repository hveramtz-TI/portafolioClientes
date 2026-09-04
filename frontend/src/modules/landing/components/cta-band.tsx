import Link from 'next/link';

export function LandingCtaBand() {
  return (
    <section id="comenzar" className="mx-auto max-w-[1200px] px-4 py-16">
      <div className="rounded-[var(--radius-tmc-frame)] bg-tmc-ink px-8 py-16 text-center md:px-16">
        <h2
          className="mx-auto mb-8 max-w-2xl text-tmc-section font-medium leading-[44px] text-tmc-canvas"
          style={{ fontWeight: 500 }}
        >
          Empezá a ordenar tu cartera de clientes hoy.
        </h2>
        <Link
          href="/login"
          className="inline-flex items-center justify-center rounded-[var(--radius-tmc-cta)] bg-tmc-canvas px-8 py-4 text-xl font-normal text-tmc-ink transition-colors hover:bg-tmc-neutral-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent-light"
        >
          Ingresar a TMC
        </Link>
      </div>
    </section>
  );
}

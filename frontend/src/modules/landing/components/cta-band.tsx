import { CtaButton } from './cta-button';

export function LandingCtaBand() {
  return (
    <section id="comenzar" className="scroll-mt-28 mx-auto max-w-[1200px] px-4 py-16">
      <div className="rounded-[var(--radius-tmc-frame)] bg-tmc-ink px-8 py-16 text-center md:px-16">
        <h2
          className="mx-auto mb-8 max-w-2xl text-balance text-tmc-section font-medium leading-[44px] text-tmc-canvas"
          style={{ fontWeight: 500 }}
        >
          Empezá a ordenar tu cartera de clientes hoy.
        </h2>
        <CtaButton href="/login" variant="light" size="lg">
          Ingresar a TMC
        </CtaButton>
      </div>
    </section>
  );
}

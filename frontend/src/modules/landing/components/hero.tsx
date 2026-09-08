import { PlaceholderSlot } from './placeholder';
import { CtaButton } from './cta-button';
import { LandingEyebrow } from './eyebrow';

export function LandingHero() {
  return (
    <section className="mx-auto max-w-[1200px] px-4 pt-16 md:pt-24">
      <div className="grid items-center gap-12 md:grid-cols-[minmax(0,0.9fr)_minmax(360px,1.1fr)] md:gap-16">
        <div className="max-w-2xl">
          <LandingEyebrow className="mb-6">Del trabajo al portafolio</LandingEyebrow>

          <h1
            className="mb-6 text-balance text-[clamp(44px,6vw,64px)] font-medium leading-[1] text-tmc-ink"
            style={{ fontWeight: 500 }}
          >
            Tu trabajo real puede convertirse en tu portafolio profesional.
          </h1>

          <p className="mb-8 max-w-xl text-pretty text-lg leading-relaxed text-tmc-neutral-5" style={{ fontWeight: 450 }}>
            TMC te ayuda a pasar de trabajo realizado a información organizada,
            evidencia profesional y un portafolio que habla por vos.
          </p>

          <CtaButton href="/login" variant="dark" size="lg">
            Ingresar
          </CtaButton>
        </div>

        <div className="relative mx-auto flex aspect-square w-full max-w-[560px] items-center justify-center md:translate-y-8">
          <svg
            aria-hidden="true"
            viewBox="0 0 520 420"
            className="absolute inset-0 h-full w-full text-tmc-accent/60"
            fill="none"
          >
            <ellipse
              cx="260"
              cy="210"
              rx="220"
              ry="132"
              stroke="currentColor"
              strokeWidth="1.5"
              transform="rotate(-14 260 210)"
            />
            <circle cx="77" cy="245" r="4" fill="currentColor" />
            <circle cx="430" cy="143" r="4" fill="currentColor" />
          </svg>

          <div className="relative w-[72%] max-w-[420px]">
            <PlaceholderSlot
              label="hero-media"
              aspect="16 / 9"
              className="rounded-[var(--radius-tmc-frame)]"
            />
          </div>

          <ol
            aria-label="Cómo TMC transforma tu trabajo"
            className="pointer-events-none absolute inset-0 list-none text-sm font-medium text-tmc-ink"
          >
            <li className="absolute left-0 top-[48%] -translate-y-1/2 rounded-full bg-tmc-surface px-3 py-2">
              Trabajo real
            </li>
            <li className="absolute right-0 top-[22%] rounded-full bg-tmc-surface px-3 py-2">
              Información organizada
            </li>
            <li className="absolute bottom-[12%] left-1/2 -translate-x-1/2 rounded-full bg-tmc-surface px-3 py-2">
              Portafolio profesional
            </li>
          </ol>
        </div>
      </div>
    </section>
  );
}

import Link from 'next/link';
import { PlaceholderSlot } from './placeholder';

export function LandingEditorial() {
  return (
    <section id="editorial" className="relative mx-auto max-w-[1200px] overflow-hidden px-4 py-16">
      {/* Ghost watermark headline (decorative, paired with a real-text heading below) */}
      <p
        aria-hidden="true"
        className="pointer-events-none absolute left-4 top-8 text-[96px] font-bold leading-none tracking-tight text-tmc-surface select-none"
      >
        TMC
      </p>

      <div className="relative grid items-center gap-12 md:grid-cols-2">
        <div className="relative flex justify-center">
          {/* Decorative orange orbits */}
          <svg
            aria-hidden="true"
            viewBox="0 0 200 200"
            className="absolute inset-0 -z-10 h-full w-full text-tmc-accent/40"
            fill="none"
          >
            <circle cx="100" cy="100" r="80" stroke="currentColor" strokeWidth="1.5" />
            <circle cx="100" cy="100" r="55" stroke="currentColor" strokeWidth="1" />
          </svg>

          <PlaceholderSlot
            label="portrait-1"
            aspect="1 / 1"
            className="w-56 rounded-full md:w-64"
          />

          <Link
            href="/login"
            className="absolute -bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-tmc-canvas px-5 py-2 text-sm font-medium text-tmc-ink shadow-sm transition-colors hover:bg-tmc-neutral-2"
          >
            Conocé TMC
          </Link>
        </div>

        <div>
          <p className="mb-6 text-sm font-bold uppercase tracking-normal text-tmc-accent">
            <span aria-hidden="true" className="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-tmc-accent" />
            Por qué TMC
          </p>
          <h2 className="mb-6 text-tmc-section font-medium leading-[44px] text-tmc-ink">
            Hecho para quienes llevan adelante cada relación.
          </h2>
          <p className="text-base leading-relaxed text-tmc-neutral-5">
            TMC nace de una idea simple: que la información de tus clientes
            deje de estar dispersa y viva donde la necesitás, organizada y
            lista para usar.
          </p>
        </div>
      </div>
    </section>
  );
}

import { PlaceholderSlot } from './placeholder';
import { LandingEyebrow } from './eyebrow';

export function LandingEditorial() {
  return (
    <section id="editorial" className="scroll-mt-28 relative mx-auto max-w-[1200px] overflow-hidden px-4 py-16">
      {/* Ghost watermark headline (decorative, paired with a real-text heading below) */}
      <p
        aria-hidden="true"
        className="pointer-events-none absolute left-4 top-10 text-[clamp(56px,8vw,80px)] font-bold leading-none tracking-tight text-tmc-neutral-2/60 select-none"
      >
        TMC
      </p>

      <div className="relative grid items-center gap-12 md:grid-cols-2">
        <div className="relative flex justify-center">
          <PlaceholderSlot
            label="portrait-1"
            aspect="1 / 1"
            className="w-56 rounded-full md:w-64"
          />
        </div>

        <div>
          <LandingEyebrow className="mb-6">Por qué TMC</LandingEyebrow>
          <h2 className="mb-6 text-balance text-[clamp(30px,4vw,36px)] font-medium leading-[1.2] text-tmc-ink">
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

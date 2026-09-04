const valueProps = [
  {
    title: 'Clientes y empresas',
    description:
      'Una vista clara de cada cliente y su empresa, con su historial siempre a mano.',
  },
  {
    title: 'Catálogo personalizable',
    description:
      'Organiza rubros, categorías y servicios para reflejar tu propia oferta.',
  },
  {
    title: 'Solicitudes y órdenes',
    description:
      'Seguí cada pedido desde que llega hasta que se concreta, sin perder el hilo.',
  },
  {
    title: 'Panel general',
    description:
      'Un lugar central para entender el estado de tu cartera de un vistazo.',
  },
];

export function LandingValueProps() {
  return (
    <section id="que-hace" className="mx-auto max-w-[1200px] px-4 py-16">
      <h2 className="mb-12 text-tmc-section font-medium text-tmc-ink">
        Qué hace la plataforma
      </h2>

      <div className="grid gap-4 sm:grid-cols-2">
        {valueProps.map((prop) => (
          <article
            key={prop.title}
            className="rounded-[var(--radius-tmc-pill)] bg-tmc-surface p-6"
          >
            <h3 className="mb-2 text-tmc-card font-medium text-tmc-ink">
              {prop.title}
            </h3>
            <p className="text-base leading-relaxed text-tmc-neutral-5">
              {prop.description}
            </p>
          </article>
        ))}
      </div>
    </section>
  );
}

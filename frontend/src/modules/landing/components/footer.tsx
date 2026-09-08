import Link from 'next/link';

const columns = [
  {
    title: 'Producto',
    links: [
      { label: 'Qué hace', href: '#que-hace' },
      { label: 'Por qué TMC', href: '#editorial' },
      { label: 'Comenzar', href: '#comenzar' },
    ],
  },
  {
    title: 'Recursos',
    links: [{ label: 'Ingresar', href: '/login' }],
  },
  {
    title: 'Compañía',
    links: [{ label: 'Acerca de', href: '#editorial' }],
  },
];

export function LandingFooter() {
  return (
    <footer className="bg-tmc-ink text-tmc-canvas">
      <div className="mx-auto max-w-[1200px] px-4 py-16">
        <div className="mb-12 flex items-center gap-2">
          <span className="text-base font-medium" style={{ fontWeight: 500 }}>
            TMC
          </span>
          <span className="text-sm text-tmc-canvas/70">That&apos;s My Client</span>
        </div>

        <h2 className="mb-16 max-w-3xl text-balance text-[clamp(36px,6vw,64px)] font-medium leading-[1.05] text-tmc-canvas">
          Tu trabajo ya tiene una historia. TMC te ayuda a presentarla.
        </h2>

        <div className="grid grid-cols-2 gap-8 md:grid-cols-3">
          {columns.map((column) => (
            <div key={column.title}>
              <h3 className="mb-4 text-balance text-sm font-bold text-tmc-canvas">
                {column.title}
              </h3>
              <ul className="space-y-2">
                {column.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      href={link.href}
                      className="text-sm text-tmc-canvas/70 transition-colors hover:text-tmc-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent-light"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </footer>
  );
}
